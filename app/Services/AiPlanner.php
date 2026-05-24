<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Phase 3B-1. Gemini client + JSON parser for the WBS draft pipeline.
 *
 *  - isConfigured()         : readiness check used by the UI gate.
 *  - generateWbsDraft()     : single network call to Gemini, returns a
 *                             validated draft array. Never writes to the DB.
 *  - parseResponse()        : pure parser. Tinker-friendly so the contract
 *                             can be exercised without burning API quota.
 *
 * Result shape:
 *   ['ok' => bool, 'data' => ['modules' => [...]], 'error' => string|null]
 *
 * Allowed status / priority values are pinned to the same constants used
 * in ProjectController so a downstream importer can pass these straight
 * through without further normalization.
 */
class AiPlanner
{
    public const ALLOWED_MODULE_STATUS = ['pending_design', 'approved', 'waiting_dev', 'revision'];
    public const ALLOWED_TASK_STATUS   = ['planned', 'in_progress', 'review', 'done'];
    public const ALLOWED_TASK_PRIORITY = ['low', 'medium', 'high'];

    public const DEFAULT_MODULE_STATUS  = 'pending_design';
    public const DEFAULT_TASK_STATUS    = 'planned';
    public const DEFAULT_TASK_PRIORITY  = 'medium';

    public const MAX_MODULES         = 8;
    public const MAX_TASKS_PER_MODULE = 8;

    private const GEMINI_ENDPOINT = 'https://generativelanguage.googleapis.com/v1beta/models/%s:generateContent';
    private const HTTP_TIMEOUT_SECONDS = 30;

    /* ===================== Public API ===================== */

    public static function isConfigured(): bool
    {
        $provider = (string) (config('ai.provider') ?: 'gemini');

        $key = match ($provider) {
            'gemini' => config('ai.gemini.api_key'),
            default  => null,
        };

        return is_string($key) && trim($key) !== '';
    }

    public static function providerLabel(): string
    {
        return match ((string) (config('ai.provider') ?: 'gemini')) {
            'gemini' => 'Gemini',
            default  => 'AI',
        };
    }

    /**
     * Generate a WBS draft from MoM context. Single Gemini call; no DB writes.
     *
     * Expected $context keys (all optional, all coerced to string):
     *   - project_name
     *   - project_code
     *   - project_description
     *   - mom_notes        ← primary source material
     *   - mom_summary
     *   - mom_date
     *
     * @return array{ok: bool, data: array{modules: array}, error: ?string}
     */
    public static function generateWbsDraft(array $context): array
    {
        if (! self::isConfigured()) {
            return self::failure('AI belum dikonfigurasi. Set GEMINI_API_KEY pada .env.');
        }

        $model  = (string) (config('ai.gemini.model') ?: 'gemini-1.5-flash');
        $apiKey = (string) config('ai.gemini.api_key');
        $prompt = self::buildPrompt($context);

        try {
            $response = Http::timeout(self::HTTP_TIMEOUT_SECONDS)
                ->acceptJson()
                ->asJson()
                ->withQueryParameters(['key' => $apiKey])
                ->post(sprintf(self::GEMINI_ENDPOINT, $model), [
                    'contents' => [[
                        'role'  => 'user',
                        'parts' => [['text' => $prompt]],
                    ]],
                    'generationConfig' => [
                        'temperature'     => 0.4,
                        'responseMimeType' => 'application/json',
                    ],
                ]);
        } catch (Throwable $e) {
            report($e);

            return self::failure('Gagal menghubungi Gemini: ' . $e->getMessage());
        }

        if (! $response->successful()) {
            $detail = trim((string) ($response->json('error.message') ?? $response->body()));
            $detail = $detail !== '' ? $detail : ('HTTP ' . $response->status());

            return self::failure('Gemini menolak permintaan: ' . $detail);
        }

        $text = (string) ($response->json('candidates.0.content.parts.0.text') ?? '');
        if ($text === '') {
            return self::failure('Respons Gemini kosong.');
        }

        return self::parseResponse($text);
    }

    /**
     * Pure parser/validator. Public so tests + tinker can exercise the
     * full contract without hitting the network.
     *
     * @return array{ok: bool, data: array{modules: array}, error: ?string}
     */
    public static function parseResponse(string $raw): array
    {
        $clean = self::stripFences($raw);
        if ($clean === '') {
            return self::failure('Respons AI kosong.');
        }

        try {
            $decoded = json_decode($clean, true, 32, JSON_THROW_ON_ERROR);
        } catch (Throwable $e) {
            return self::failure('Format JSON tidak valid: ' . $e->getMessage());
        }

        if (! is_array($decoded)) {
            return self::failure('Format JSON tidak sesuai (bukan object).');
        }

        // Accept either {"modules": [...]} or a bare list of modules at root.
        $rawModules = $decoded['modules'] ?? $decoded;
        if (! is_array($rawModules)) {
            return self::failure('Field "modules" tidak ditemukan.');
        }

        $modules = [];
        $sortIndex = 1;
        foreach (array_values($rawModules) as $rawModule) {
            if (count($modules) >= self::MAX_MODULES) {
                break;
            }
            $normalized = self::normalizeModule($rawModule, $sortIndex);
            if ($normalized === null) {
                continue;
            }
            $modules[] = $normalized;
            $sortIndex++;
        }

        if (empty($modules)) {
            return self::failure('Tidak ada modul valid yang bisa diparsing dari respons.');
        }

        return [
            'ok'    => true,
            'data'  => ['modules' => $modules],
            'error' => null,
        ];
    }

    /* ===================== Internals ===================== */

    private static function buildPrompt(array $context): string
    {
        $projectName = self::str($context['project_name'] ?? '');
        $projectCode = self::str($context['project_code'] ?? '');
        $projectDesc = self::str($context['project_description'] ?? '');
        $momDate     = self::str($context['mom_date'] ?? '');
        $momSummary  = self::str($context['mom_summary'] ?? '');
        $momNotes    = self::str($context['mom_notes'] ?? '');

        $moduleStatuses = implode('|', self::ALLOWED_MODULE_STATUS);
        $taskStatuses   = implode('|', self::ALLOWED_TASK_STATUS);
        $taskPriorities = implode('|', self::ALLOWED_TASK_PRIORITY);

        $intro = "Kamu adalah Smart-PMIS planning assistant. Berdasarkan MoM rapat berikut, buat draft WBS"
            . " (Work Breakdown Structure) berupa daftar modul dan task untuk proyek perangkat lunak.";

        $rules = [
            'Jawab HANYA dengan JSON murni. Jangan tambahkan teks lain di luar JSON.',
            'Struktur: { "modules": [ { "title", "description", "status", "estimated_hours", "tasks": [ { "title", "description", "status", "priority", "estimate_hours" } ] } ] }.',
            'Maksimum ' . self::MAX_MODULES . ' modul, maksimum ' . self::MAX_TASKS_PER_MODULE . ' task per modul.',
            'status modul harus salah satu dari: ' . $moduleStatuses . '.',
            'status task harus salah satu dari: ' . $taskStatuses . '. Default planned.',
            'priority task harus salah satu dari: ' . $taskPriorities . '. Default medium.',
            'estimated_hours dan estimate_hours: bilangan bulat 0–999 jam.',
            'Gunakan Bahasa Indonesia natural untuk title + description. Singkat dan padat (≤ 160 karakter).',
            'Tidak boleh ada properti tambahan di luar yang disebut.',
        ];

        $contextBlock = "Konteks proyek:\n"
            . '- Nama: ' . ($projectName !== '' ? $projectName : '-') . "\n"
            . '- Kode: ' . ($projectCode !== '' ? $projectCode : '-') . "\n"
            . '- Deskripsi: ' . ($projectDesc !== '' ? $projectDesc : '-') . "\n\n"
            . "MoM:\n"
            . '- Tanggal: ' . ($momDate !== '' ? $momDate : '-') . "\n"
            . '- Ringkasan: ' . ($momSummary !== '' ? $momSummary : '-') . "\n"
            . '- Catatan mentah: ' . ($momNotes !== '' ? $momNotes : '-');

        return $intro . "\n\nAturan:\n- " . implode("\n- ", $rules) . "\n\n" . $contextBlock;
    }

    private static function stripFences(string $raw): string
    {
        $trim = trim($raw);
        if ($trim === '') {
            return '';
        }

        // Remove ```json ... ``` or ``` ... ``` wrapper if present.
        if (preg_match('/^```(?:json|JSON)?\s*([\s\S]*?)\s*```$/m', $trim, $m)) {
            $trim = trim($m[1]);
        }

        return $trim;
    }

    private static function normalizeModule($raw, int $sortOrder): ?array
    {
        if (! is_array($raw)) {
            return null;
        }

        $title = self::str($raw['title'] ?? '');
        if ($title === '') {
            return null;
        }

        $rawTasks = $raw['tasks'] ?? [];
        if (! is_array($rawTasks)) {
            $rawTasks = [];
        }

        $tasks = [];
        $taskOrder = 1;
        foreach (array_values($rawTasks) as $rawTask) {
            if (count($tasks) >= self::MAX_TASKS_PER_MODULE) {
                break;
            }
            $normalizedTask = self::normalizeTask($rawTask, $taskOrder);
            if ($normalizedTask === null) {
                continue;
            }
            $tasks[] = $normalizedTask;
            $taskOrder++;
        }

        return [
            'title'           => self::clip($title, 180),
            'description'     => self::clipNullable(self::str($raw['description'] ?? ''), 2000),
            'status'          => self::pickEnum($raw['status'] ?? null, self::ALLOWED_MODULE_STATUS, self::DEFAULT_MODULE_STATUS),
            'estimate_hours'  => self::intInRange(
                $raw['estimate_hours'] ?? ($raw['estimated_hours'] ?? 0),
                0,
                999,
            ),
            'sort_order'      => $sortOrder,
            'tasks'           => $tasks,
        ];
    }

    private static function normalizeTask($raw, int $sortOrder): ?array
    {
        if (! is_array($raw)) {
            return null;
        }

        $title = self::str($raw['title'] ?? '');
        if ($title === '') {
            return null;
        }

        return [
            'title'          => self::clip($title, 180),
            'description'    => self::clipNullable(self::str($raw['description'] ?? ''), 2000),
            'status'         => self::pickEnum($raw['status'] ?? null, self::ALLOWED_TASK_STATUS, self::DEFAULT_TASK_STATUS),
            'priority'       => self::pickEnum($raw['priority'] ?? null, self::ALLOWED_TASK_PRIORITY, self::DEFAULT_TASK_PRIORITY),
            'estimate_hours' => self::intInRange(
                $raw['estimate_hours'] ?? ($raw['estimated_hours'] ?? 0),
                0,
                999,
            ),
            'sort_order'     => $sortOrder,
        ];
    }

    private static function pickEnum($value, array $allowed, string $default): string
    {
        if (! is_string($value)) {
            return $default;
        }
        $normalized = strtolower(trim($value));
        $normalized = str_replace([' ', '-'], '_', $normalized);

        return in_array($normalized, $allowed, true) ? $normalized : $default;
    }

    private static function intInRange($value, int $min, int $max): int
    {
        if (is_string($value) && trim($value) === '') {
            return $min;
        }
        $n = (int) $value;
        if ($n < $min) {
            return $min;
        }
        if ($n > $max) {
            return $max;
        }

        return $n;
    }

    private static function str($value): string
    {
        if ($value === null) {
            return '';
        }
        return trim((string) $value);
    }

    private static function clip(string $value, int $max): string
    {
        return mb_strlen($value) > $max ? mb_substr($value, 0, $max) : $value;
    }

    private static function clipNullable(string $value, int $max): ?string
    {
        if ($value === '') {
            return null;
        }
        return self::clip($value, $max);
    }

    private static function failure(string $message): array
    {
        return [
            'ok'    => false,
            'data'  => ['modules' => []],
            'error' => $message,
        ];
    }
}
