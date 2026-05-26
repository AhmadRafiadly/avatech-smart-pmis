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
        $prompt = self::buildWbsPrompt($context);

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

    public static function buildWbsPrompt(array $context): string
    {
        $projectName = self::str($context['project_name'] ?? '');
        $projectCode = self::str($context['project_code'] ?? '');
        $projectDesc = self::str($context['project_description'] ?? '');
        $projectClient = self::str($context['project_client'] ?? '');
        $projectPhase = self::str($context['project_phase'] ?? '');
        $projectStatus = self::str($context['project_status'] ?? '');
        $momDate     = self::str($context['mom_date'] ?? '');
        $momSummary  = self::str($context['mom_summary'] ?? '');
        $momNotes    = self::str($context['mom_notes'] ?? '');
        $existingModules = self::listContext($context['existing_module_titles'] ?? []);
        $existingTasks = self::listContext($context['existing_task_titles'] ?? []);

        $moduleStatuses = implode('|', self::ALLOWED_MODULE_STATUS);
        $taskStatuses   = implode('|', array_merge(['todo'], self::ALLOWED_TASK_STATUS));
        $taskPriorities = implode('|', self::ALLOWED_TASK_PRIORITY);

        $intro = 'Kamu adalah Smart-PMIS planning assistant untuk proyek perangkat lunak. '
            . 'Ubah MoM menjadi draft WBS yang praktis: modul kerja dan task implementasi yang bisa langsung dimasukkan ke project_modules dan project_tasks.';

        $rules = [
            'Jawab HANYA dengan JSON murni. Jangan tambahkan teks lain di luar JSON.',
            'Struktur: { "modules": [ { "title", "description", "status", "estimated_hours", "tasks": [ { "title", "description", "status", "priority", "estimated_hours" } ] } ] }.',
            'Maksimum ' . self::MAX_MODULES . ' modul, maksimum ' . self::MAX_TASKS_PER_MODULE . ' task per modul.',
            'status modul harus salah satu dari: ' . $moduleStatuses . '.',
            'status task harus salah satu dari: ' . $taskStatuses . '. Gunakan todo untuk task baru yang belum dimulai.',
            'priority task harus salah satu dari: ' . $taskPriorities . '. Default medium.',
            'estimated_hours: bilangan bulat 0-999 jam.',
            'Gunakan Bahasa Indonesia natural untuk title dan description. Singkat dan padat.',
            'Fokus pada kebutuhan software project dari MoM. Hindari task generik yang tidak relevan.',
            'Hindari duplikasi dengan judul modul/task yang sudah ada pada konteks aktual.',
            'Tidak boleh ada properti tambahan di luar yang disebut.',
        ];

        $exampleInput = "Contoh input:\n"
            . "Project: Sistem Booking Ruang Meeting\n"
            . 'MoM: User membutuhkan login role-based, kalender booking, approval admin, dan laporan penggunaan ruangan.';

        $exampleOutput = <<<'JSON'
Contoh output JSON:
{
  "modules": [
    {
      "title": "Autentikasi dan Manajemen Role",
      "description": "Modul login, role pengguna, dan pembatasan akses.",
      "status": "pending_design",
      "estimated_hours": 8,
      "tasks": [
        {
          "title": "Implementasi login role-based",
          "description": "Membuat autentikasi dan redirect berdasarkan role pengguna.",
          "status": "todo",
          "priority": "high",
          "estimated_hours": 4
        }
      ]
    },
    {
      "title": "Manajemen Booking Ruangan",
      "description": "Modul kalender booking, validasi jadwal, dan approval admin.",
      "status": "pending_design",
      "estimated_hours": 12,
      "tasks": [
        {
          "title": "Membuat form booking ruangan",
          "description": "Membuat input tanggal, waktu, ruangan, dan kebutuhan meeting.",
          "status": "todo",
          "priority": "medium",
          "estimated_hours": 4
        }
      ]
    }
  ]
}
JSON;

        $contextBlock = "Input aktual:\n"
            . '- Nama: ' . ($projectName !== '' ? $projectName : '-') . "\n"
            . '- Kode: ' . ($projectCode !== '' ? $projectCode : '-') . "\n"
            . '- Client: ' . ($projectClient !== '' ? $projectClient : '-') . "\n"
            . '- Phase: ' . ($projectPhase !== '' ? $projectPhase : '-') . "\n"
            . '- Status: ' . ($projectStatus !== '' ? $projectStatus : '-') . "\n"
            . '- Deskripsi: ' . ($projectDesc !== '' ? $projectDesc : '-') . "\n"
            . '- Modul yang sudah ada: ' . $existingModules . "\n"
            . '- Task yang sudah ada: ' . $existingTasks . "\n\n"
            . "MoM aktual:\n"
            . '- Tanggal: ' . ($momDate !== '' ? $momDate : '-') . "\n"
            . '- Ringkasan: ' . ($momSummary !== '' ? $momSummary : '-') . "\n"
            . '- Catatan mentah: ' . ($momNotes !== '' ? $momNotes : '-');

        return $intro
            . "\n\nInstruksi output:\n- " . implode("\n- ", $rules)
            . "\n\n" . $exampleInput
            . "\n\n" . $exampleOutput
            . "\n\nSekarang buat output JSON untuk input aktual berikut. Ingat: output akhir hanya JSON, tanpa markdown, tanpa komentar.\n\n"
            . $contextBlock;
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
            'status'         => self::normalizeTaskStatus($raw['status'] ?? null),
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

    private static function normalizeTaskStatus($value): string
    {
        if (! is_string($value)) {
            return self::DEFAULT_TASK_STATUS;
        }

        $normalized = strtolower(trim($value));
        $normalized = str_replace([' ', '-'], '_', $normalized);
        if (in_array($normalized, ['todo', 'backlog'], true)) {
            return self::DEFAULT_TASK_STATUS;
        }

        return in_array($normalized, self::ALLOWED_TASK_STATUS, true)
            ? $normalized
            : self::DEFAULT_TASK_STATUS;
    }

    private static function listContext($value): string
    {
        if (is_array($value)) {
            $items = array_values(array_filter(array_map(fn ($item) => self::str($item), $value)));

            return $items ? implode(', ', $items) : '-';
        }

        $text = self::str($value);

        return $text !== '' ? $text : '-';
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
