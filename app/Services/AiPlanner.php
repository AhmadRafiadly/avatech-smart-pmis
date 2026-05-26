<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Multi-provider AI client + JSON parsers for planning workflows.
 *
 *  - isConfigured()         : readiness check used by the UI gate.
 *  - generateWbsDraft()     : provider fallback call, returns a
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

    public const MAX_MODULES         = 5;
    public const MAX_TASKS_PER_MODULE = 3;
    public const MAX_TEST_CASES       = 20;

    private const GEMINI_ENDPOINT = 'https://generativelanguage.googleapis.com/v1beta/models/%s:generateContent';
    private const GROQ_ENDPOINT = 'https://api.groq.com/openai/v1/chat/completions';
    private const OPENROUTER_ENDPOINT = 'https://openrouter.ai/api/v1/chat/completions';
    private const FRIENDLY_PROVIDER_ERROR = 'AI gagal menghasilkan respons. Coba ulangi beberapa saat lagi.';

    /* ===================== Public API ===================== */

    public static function isConfigured(): bool
    {
        return self::configuredProviders() !== [];
    }

    public static function providerLabel(): string
    {
        return self::isConfigured() ? 'AI siap digunakan' : 'AI';
    }

    /**
     * Generate a WBS draft from MoM context. Provider fallback call; no DB writes.
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
            return self::failure('AI belum dikonfigurasi. Set salah satu provider AI pada .env.');
        }

        $prompt = self::buildWbsPrompt($context);
        $providerResult = self::callConfiguredProviders($prompt, 0.4);
        if (! ($providerResult['ok'] ?? false)) {
            return self::failure($providerResult['error'] ?? self::FRIENDLY_PROVIDER_ERROR);
        }

        $parsed = self::parseResponse((string) $providerResult['text']);
        if ($parsed['ok'] ?? false) {
            $parsed['provider'] = $providerResult['provider'] ?? null;
        }

        return $parsed;
    }

    /**
     * Generate black-box QC test cases from WBS/task context. Provider
     * fallback call; no DB writes.
     *
     * @return array{ok: bool, data: array{test_cases: array}, error: ?string}
     */
    public static function generateTestCaseDraft(array $context): array
    {
        if (! self::isConfigured()) {
            return self::failure('AI belum dikonfigurasi. Set salah satu provider AI pada .env.', ['test_cases' => []]);
        }

        $prompt = self::buildTestCasePrompt($context);
        $providerResult = self::callConfiguredProviders($prompt, 0.35);
        if (! ($providerResult['ok'] ?? false)) {
            return self::failure($providerResult['error'] ?? self::FRIENDLY_PROVIDER_ERROR, ['test_cases' => []]);
        }

        $parsed = self::parseTestCaseResponse((string) $providerResult['text']);
        if ($parsed['ok'] ?? false) {
            $parsed['provider'] = $providerResult['provider'] ?? null;
        }

        return $parsed;
    }

    /**
     * Generate a structured MoM summary from raw meeting notes. Provider
     * fallback call; no DB writes.
     *
     * @return array{ok: bool, data: array{mom: array, formatted: string}, error: ?string}
     */
    public static function generateMomSummary(array $context): array
    {
        if (! self::isConfigured()) {
            return self::failure('AI belum dikonfigurasi. Set salah satu provider AI pada .env.', ['mom' => [], 'formatted' => '']);
        }

        $prompt = self::buildMomFixerPrompt($context);
        $providerResult = self::callConfiguredProviders($prompt, 0.25);
        if (! ($providerResult['ok'] ?? false)) {
            return self::failure($providerResult['error'] ?? self::FRIENDLY_PROVIDER_ERROR, ['mom' => [], 'formatted' => '']);
        }

        $parsed = self::parseMomSummaryResponse((string) $providerResult['text']);
        if ($parsed['ok'] ?? false) {
            $parsed['provider'] = $providerResult['provider'] ?? null;
        }

        return $parsed;
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

    /**
     * Pure parser/validator for AI-generated black-box test cases.
     *
     * @return array{ok: bool, data: array{test_cases: array}, error: ?string}
     */
    public static function parseTestCaseResponse(string $raw): array
    {
        $clean = self::stripFences($raw);
        if ($clean === '') {
            return self::failure('Respons AI kosong.', ['test_cases' => []]);
        }

        try {
            $decoded = json_decode($clean, true, 32, JSON_THROW_ON_ERROR);
        } catch (Throwable $e) {
            return self::failure('Format JSON tidak valid: ' . $e->getMessage(), ['test_cases' => []]);
        }

        if (! is_array($decoded)) {
            return self::failure('Format JSON tidak sesuai (bukan object).', ['test_cases' => []]);
        }

        // Accept either {"test_cases": [...]} or a bare list at root.
        $rawCases = $decoded['test_cases'] ?? $decoded;
        if (! is_array($rawCases)) {
            return self::failure('Field "test_cases" tidak ditemukan.', ['test_cases' => []]);
        }

        $cases = [];
        foreach (array_values($rawCases) as $rawCase) {
            if (count($cases) >= self::MAX_TEST_CASES) {
                break;
            }
            $normalized = self::normalizeTestCase($rawCase);
            if ($normalized === null) {
                continue;
            }
            $cases[] = $normalized;
        }

        if (empty($cases)) {
            return self::failure('Tidak ada test case valid yang bisa diparsing dari respons.', ['test_cases' => []]);
        }

        return [
            'ok'    => true,
            'data'  => ['test_cases' => $cases],
            'error' => null,
        ];
    }

    /**
     * Pure parser/formatter for AI-generated structured MoM summaries.
     *
     * @return array{ok: bool, data: array{mom: array, formatted: string}, error: ?string}
     */
    public static function parseMomSummaryResponse(string $raw): array
    {
        $clean = self::stripFences($raw);
        if ($clean === '') {
            return self::failure('Respons AI kosong.', ['mom' => [], 'formatted' => '']);
        }

        try {
            $decoded = json_decode($clean, true, 32, JSON_THROW_ON_ERROR);
        } catch (Throwable $e) {
            return self::failure('Format JSON tidak valid: ' . $e->getMessage(), ['mom' => [], 'formatted' => '']);
        }

        $mom = self::normalizeMomSummary($decoded);
        if ($mom === null) {
            return self::failure('Tidak ada struktur MoM valid yang bisa diparsing dari respons.', ['mom' => [], 'formatted' => '']);
        }

        $formatted = self::formatMomSummary($mom);
        if ($formatted === '') {
            return self::failure('MoM hasil AI kosong setelah diformat.', ['mom' => [], 'formatted' => '']);
        }

        return [
            'ok'    => true,
            'data'  => ['mom' => $mom, 'formatted' => $formatted],
            'error' => null,
        ];
    }

    /* ===================== Internals ===================== */

    /**
     * @return array<int, string>
     */
    private static function configuredProviders(): array
    {
        $providers = [];
        foreach (self::providerOrder() as $provider) {
            $key = self::providerApiKey($provider);
            if ($key !== '') {
                $providers[] = $provider;
            }
        }

        return $providers;
    }

    /**
     * @return array<int, string>
     */
    private static function providerOrder(): array
    {
        $order = config('ai.provider_order', ['gemini', 'groq', 'openrouter']);
        if (! is_array($order)) {
            $order = explode(',', (string) $order);
        }

        $allowed = ['gemini', 'groq', 'openrouter'];
        $providers = [];
        foreach ($order as $provider) {
            $provider = strtolower(trim((string) $provider));
            if (in_array($provider, $allowed, true) && ! in_array($provider, $providers, true)) {
                $providers[] = $provider;
            }
        }

        return $providers ?: ['gemini', 'groq', 'openrouter'];
    }

    private static function providerApiKey(string $provider): string
    {
        $key = config("ai.{$provider}.api_key");

        return is_string($key) ? trim($key) : '';
    }

    private static function providerModel(string $provider): string
    {
        $fallback = match ($provider) {
            'gemini' => 'gemini-1.5-flash',
            'groq' => 'llama-3.1-8b-instant',
            'openrouter' => 'openai/gpt-4o-mini',
            default => '',
        };

        $model = config("ai.{$provider}.model", $fallback);
        $model = is_string($model) ? trim($model) : '';

        return $model !== '' ? $model : $fallback;
    }

    private static function timeoutSeconds(): int
    {
        return max(1, (int) config('ai.timeout_seconds', 30));
    }

    /**
     * Calls configured providers in order and returns raw response text.
     *
     * @return array{ok: bool, text?: string, provider?: string, error?: string}
     */
    private static function callConfiguredProviders(string $prompt, float $temperature): array
    {
        $providers = self::configuredProviders();
        if ($providers === []) {
            return ['ok' => false, 'error' => 'AI belum dikonfigurasi. Set salah satu provider AI pada .env.'];
        }

        $errors = [];
        foreach ($providers as $provider) {
            $result = match ($provider) {
                'gemini' => self::callGemini($prompt, $temperature),
                'groq' => self::callGroq($prompt, $temperature),
                'openrouter' => self::callOpenRouter($prompt, $temperature),
                default => ['ok' => false, 'error' => 'Provider tidak dikenal.'],
            };

            if (($result['ok'] ?? false) && trim((string) ($result['text'] ?? '')) !== '') {
                return [
                    'ok' => true,
                    'provider' => $provider,
                    'text' => (string) $result['text'],
                ];
            }

            $errors[$provider] = (string) ($result['error'] ?? 'Respons kosong.');
        }

        Log::warning('AI provider fallback exhausted.', [
            'providers' => $providers,
            'errors' => $errors,
        ]);

        return ['ok' => false, 'error' => self::FRIENDLY_PROVIDER_ERROR];
    }

    /**
     * @return array{ok: bool, text?: string, error?: string}
     */
    private static function callGemini(string $prompt, float $temperature): array
    {
        try {
            $response = Http::timeout(self::timeoutSeconds())
                ->acceptJson()
                ->asJson()
                ->withQueryParameters(['key' => self::providerApiKey('gemini')])
                ->post(sprintf(self::GEMINI_ENDPOINT, self::providerModel('gemini')), [
                    'contents' => [[
                        'role'  => 'user',
                        'parts' => [['text' => $prompt]],
                    ]],
                    'generationConfig' => [
                        'temperature'      => $temperature,
                        'responseMimeType' => 'application/json',
                    ],
                ]);
        } catch (Throwable $e) {
            report($e);

            return ['ok' => false, 'error' => $e->getMessage()];
        }

        if (! $response->successful()) {
            return ['ok' => false, 'error' => self::providerError($response->status(), $response->json('error.message') ?? null)];
        }

        $text = trim((string) ($response->json('candidates.0.content.parts.0.text') ?? ''));

        return $text !== ''
            ? ['ok' => true, 'text' => $text]
            : ['ok' => false, 'error' => 'Respons kosong.'];
    }

    /**
     * @return array{ok: bool, text?: string, error?: string}
     */
    private static function callGroq(string $prompt, float $temperature): array
    {
        return self::callOpenAiCompatible(
            self::GROQ_ENDPOINT,
            self::providerApiKey('groq'),
            self::providerModel('groq'),
            $prompt,
            $temperature,
        );
    }

    /**
     * @return array{ok: bool, text?: string, error?: string}
     */
    private static function callOpenRouter(string $prompt, float $temperature): array
    {
        return self::callOpenAiCompatible(
            self::OPENROUTER_ENDPOINT,
            self::providerApiKey('openrouter'),
            self::providerModel('openrouter'),
            $prompt,
            $temperature,
            [
                'HTTP-Referer' => (string) config('app.url', ''),
                'X-Title' => 'Avatech Smart-PMIS',
            ],
        );
    }

    /**
     * @param array<string, string> $headers
     * @return array{ok: bool, text?: string, error?: string}
     */
    private static function callOpenAiCompatible(
        string $endpoint,
        string $apiKey,
        string $model,
        string $prompt,
        float $temperature,
        array $headers = [],
    ): array {
        try {
            $response = Http::timeout(self::timeoutSeconds())
                ->acceptJson()
                ->asJson()
                ->withToken($apiKey)
                ->withHeaders(array_filter($headers, fn ($value) => trim((string) $value) !== ''))
                ->post($endpoint, [
                    'model' => $model,
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => 'Return valid JSON only. Do not include markdown fences or commentary.',
                        ],
                        [
                            'role' => 'user',
                            'content' => $prompt,
                        ],
                    ],
                    'temperature' => $temperature,
                ]);
        } catch (Throwable $e) {
            report($e);

            return ['ok' => false, 'error' => $e->getMessage()];
        }

        if (! $response->successful()) {
            return ['ok' => false, 'error' => self::providerError($response->status(), $response->json('error.message') ?? null)];
        }

        $text = trim((string) ($response->json('choices.0.message.content') ?? ''));

        return $text !== ''
            ? ['ok' => true, 'text' => $text]
            : ['ok' => false, 'error' => 'Respons kosong.'];
    }

    private static function providerError(int $status, mixed $message): string
    {
        $message = is_string($message) ? trim($message) : '';

        return $message !== '' ? ('HTTP ' . $status . ': ' . $message) : ('HTTP ' . $status);
    }

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
        $momSourceLabel = $momSummary !== '' ? 'Proper MoM Summary' : 'Raw MoM Notes';
        $momSourceText = $momSummary !== '' ? $momSummary : $momNotes;
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
            'Buat draft WBS ringkas untuk tahap awal/MVP. Hindari terlalu banyak task kecil. Gabungkan task yang masih satu konteks teknis.',
            'Fokus pada core scope/MVP. Hindari ledakan CRUD per entitas kecuali eksplisit diminta di MoM.',
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
            . '- Sumber utama: ' . $momSourceLabel . "\n"
            . '- Isi sumber utama: ' . ($momSourceText !== '' ? $momSourceText : '-') . "\n"
            . '- Catatan mentah pendukung: ' . ($momSummary !== '' && $momNotes !== '' ? $momNotes : '-');

        return $intro
            . "\n\nInstruksi output:\n- " . implode("\n- ", $rules)
            . "\n\n" . $exampleInput
            . "\n\n" . $exampleOutput
            . "\n\nSekarang buat output JSON untuk input aktual berikut. Ingat: output akhir hanya JSON, tanpa markdown, tanpa komentar.\n\n"
            . $contextBlock;
    }

    public static function buildMomFixerPrompt(array $context): string
    {
        $projectName = self::str($context['project_name'] ?? '');
        $projectCode = self::str($context['project_code'] ?? '');
        $projectDesc = self::str($context['project_description'] ?? '');
        $projectClient = self::str($context['project_client'] ?? '');
        $momDate = self::str($context['mom_date'] ?? '');
        $rawNotes = self::str($context['mom_notes'] ?? '');

        $intro = 'Kamu adalah Smart-PMIS meeting analyst untuk proyek perangkat lunak. '
            . 'Rapikan notulensi mentah menjadi MoM formal yang terstruktur agar siap dipakai PM untuk WBS.';

        $rules = [
            'Jawab HANYA dengan JSON murni. Jangan tambahkan markdown fences atau teks lain.',
            'Struktur: { "title", "meeting_date", "summary", "sections": [ { "heading", "items" } ], "action_items", "open_questions", "technical_notes" }.',
            'sections[].items, action_items, open_questions, dan technical_notes harus berupa array string.',
            'Jangan mengarang keputusan client yang tidak ada pada notulensi mentah.',
            'Boleh mengelompokkan catatan berantakan menjadi section yang jelas.',
            'Jika catatan memuat beberapa rekap meeting, pertahankan heading/section meeting yang terpisah.',
            'Gunakan Bahasa Indonesia formal tetapi natural.',
            'Hindari output terlalu panjang.',
            'Jangan menghitung final project fee atau biaya.',
            'Jika menyebut estimasi, tulis bahwa estimasi perlu konfirmasi PM/Fullstack.',
        ];

        $exampleInput = <<<'TEXT'
Contoh raw MoM:
Rekap Meeting Stullo | Minggu 12 April 2026
Client mau PRD di awal sebelum development. Hero section mau fleksibel untuk video/foto. Home page butuh job list, wishlist, favorites, suggestion bar. Sertifikat harus bisa diakses via link/download. My profile customer perlu biodata, pengalaman kerja, pendidikan, bisa generate/download CV. Pembayaran perlu payment gateway, fintech partner, dan cicilan internal tanpa bunga/denda.
TEXT;

        $exampleOutput = <<<'JSON'
Contoh proper MoM JSON:
{
  "title": "Rekap Meeting Stullo",
  "meeting_date": "12 April 2026",
  "summary": "Client membutuhkan penyusunan PRD di tahap awal sebagai acuan development. Fokus kebutuhan meliputi pengembangan homepage, profil customer, sertifikat, serta integrasi pembayaran.",
  "sections": [
    {
      "heading": "Kebutuhan Dokumen dan Perencanaan",
      "items": [
        "PRD perlu disiapkan sebelum development sebagai acuan utama pengerjaan."
      ]
    },
    {
      "heading": "Homepage dan Engagement User",
      "items": [
        "Hero section fleksibel untuk video maupun gambar.",
        "Job list untuk menarik minat learner.",
        "Wishlist untuk produk yang belum pernah diorder.",
        "Favorites untuk personalized order.",
        "Suggestion bar atau notifikasi rekomendasi berdasarkan minat user."
      ]
    },
    {
      "heading": "Profil Customer dan Sertifikat",
      "items": [
        "Sertifikat dapat diakses melalui link atau download.",
        "My Profile Customer berisi biodata, pengalaman kerja, pendidikan, dan data pendukung opsional.",
        "Data profil dapat digenerate atau didownload sebagai CV."
      ]
    },
    {
      "heading": "Pembayaran",
      "items": [
        "Integrasi payment gateway dan fintech partner.",
        "Cicilan internal Stullo menggunakan skema markup/commission based tanpa bunga dan denda."
      ]
    }
  ],
  "action_items": [
    "Susun PRD awal sebelum development.",
    "Validasi detail metode pembayaran dan integrasi fintech/payment gateway.",
    "Konfirmasi prioritas fitur untuk MVP."
  ],
  "open_questions": [
    "Apakah fitur suggestion bar berbasis histori order atau minat manual user?",
    "Apakah CV generator membutuhkan format template tertentu?"
  ],
  "technical_notes": [
    "Estimasi jam dan biaya tetap perlu dikonfirmasi oleh tim Fullstack/PM setelah WBS dibuat."
  ]
}
JSON;

        $contextBlock = "Input aktual:\n"
            . '- Nama proyek: ' . ($projectName !== '' ? $projectName : '-') . "\n"
            . '- Kode proyek: ' . ($projectCode !== '' ? $projectCode : '-') . "\n"
            . '- Client: ' . ($projectClient !== '' ? $projectClient : '-') . "\n"
            . '- Deskripsi proyek: ' . ($projectDesc !== '' ? $projectDesc : '-') . "\n"
            . '- Tanggal MoM tersimpan: ' . ($momDate !== '' ? $momDate : '-') . "\n"
            . "Notulensi mentah aktual:\n"
            . ($rawNotes !== '' ? $rawNotes : '-');

        return $intro
            . "\n\nInstruksi output:\n- " . implode("\n- ", $rules)
            . "\n\n" . $exampleInput
            . "\n\n" . $exampleOutput
            . "\n\nSekarang rapikan input aktual berikut. Ingat: output akhir hanya JSON, tanpa markdown, tanpa komentar.\n\n"
            . $contextBlock;
    }

    public static function buildTestCasePrompt(array $context): string
    {
        $projectName = self::str($context['project_name'] ?? '');
        $projectCode = self::str($context['project_code'] ?? '');
        $projectDesc = self::str($context['project_description'] ?? '');
        $projectClient = self::str($context['project_client'] ?? '');
        $modules = self::listContext($context['module_context'] ?? []);
        $tasks = self::listContext($context['task_context'] ?? []);
        $existingQc = self::listContext($context['existing_qc_titles'] ?? []);
        $priorities = implode('|', self::ALLOWED_TASK_PRIORITY);

        $intro = 'Kamu adalah Smart-PMIS quality assurance assistant untuk proyek perangkat lunak. '
            . 'Buat draft black-box test case dari konteks WBS module dan task agar bisa langsung dimasukkan ke project_qc_tests.';

        $rules = [
            'Jawab HANYA dengan JSON murni. Jangan tambahkan teks lain di luar JSON.',
            'Struktur: { "test_cases": [ { "title", "scenario", "expected_result", "priority", "module_title" } ] }.',
            'Maksimum ' . self::MAX_TEST_CASES . ' test case.',
            'priority harus salah satu dari: ' . $priorities . '. Default medium.',
            'module_title harus cocok dengan salah satu module_title dari konteks aktual jika relevan.',
            'Gunakan Bahasa Indonesia natural. Title singkat, scenario dan expected_result jelas.',
            'Fokus pada black-box testing: input, aksi user, aturan bisnis, dan hasil yang terlihat.',
            'Hindari test case level kode/internal implementation kecuali memang terlihat sebagai requirement.',
            'Hindari duplikasi dengan judul test case yang sudah ada pada konteks aktual.',
            'Tidak boleh ada properti tambahan di luar yang disebut.',
        ];

        $exampleInput = "Contoh input:\n"
            . "Project: Sistem Booking Ruang Meeting\n"
            . "Module: Autentikasi dan Manajemen Role\n"
            . "Task: Implementasi login role-based\n"
            . 'Requirement: User dapat login dan diarahkan ke dashboard sesuai role.';

        $exampleOutput = <<<'JSON'
Contoh output JSON:
{
  "test_cases": [
    {
      "title": "Validasi login role-based",
      "scenario": "User login menggunakan akun dengan role yang valid.",
      "expected_result": "Sistem berhasil mengautentikasi user dan mengarahkan ke dashboard sesuai role.",
      "priority": "high",
      "module_title": "Autentikasi dan Manajemen Role"
    },
    {
      "title": "Validasi penolakan login dengan password salah",
      "scenario": "User mengisi email valid tetapi password salah.",
      "expected_result": "Sistem menolak login dan menampilkan pesan kredensial tidak valid.",
      "priority": "medium",
      "module_title": "Autentikasi dan Manajemen Role"
    }
  ]
}
JSON;

        $contextBlock = "Input aktual:\n"
            . '- Nama: ' . ($projectName !== '' ? $projectName : '-') . "\n"
            . '- Kode: ' . ($projectCode !== '' ? $projectCode : '-') . "\n"
            . '- Client: ' . ($projectClient !== '' ? $projectClient : '-') . "\n"
            . '- Deskripsi: ' . ($projectDesc !== '' ? $projectDesc : '-') . "\n"
            . '- Modul WBS: ' . $modules . "\n"
            . '- Task implementasi: ' . $tasks . "\n"
            . '- Test case yang sudah ada: ' . $existingQc;

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

    private static function normalizeTestCase($raw): ?array
    {
        if (! is_array($raw)) {
            return null;
        }

        $title = self::str($raw['title'] ?? '');
        $scenario = self::str($raw['scenario'] ?? '');
        if ($title === '' || $scenario === '') {
            return null;
        }

        return [
            'title'           => self::clip($title, 180),
            'scenario'        => self::clip($scenario, 4000),
            'expected_result' => self::clipNullable(self::str($raw['expected_result'] ?? ''), 4000),
            'priority'        => self::pickEnum($raw['priority'] ?? null, self::ALLOWED_TASK_PRIORITY, self::DEFAULT_TASK_PRIORITY),
            'module_title'    => self::clipNullable(self::str($raw['module_title'] ?? ''), 180),
        ];
    }

    private static function normalizeMomSummary($raw): ?array
    {
        if (! is_array($raw)) {
            return null;
        }

        $title = self::str($raw['title'] ?? '');
        $summary = self::str($raw['summary'] ?? '');
        if ($title === '' || $summary === '') {
            return null;
        }

        $sections = [];
        foreach (array_values(is_array($raw['sections'] ?? null) ? $raw['sections'] : []) as $section) {
            if (! is_array($section)) {
                continue;
            }
            $heading = self::str($section['heading'] ?? '');
            $items = self::stringList($section['items'] ?? []);
            if ($heading === '' || empty($items)) {
                continue;
            }
            $sections[] = [
                'heading' => self::clip($heading, 180),
                'items'   => array_map(fn ($item) => self::clip($item, 500), $items),
            ];
        }

        return [
            'title'           => self::clip($title, 180),
            'meeting_date'    => self::clipNullable(self::str($raw['meeting_date'] ?? ''), 80),
            'summary'         => self::clip($summary, 1200),
            'sections'        => array_slice($sections, 0, 8),
            'action_items'    => array_slice(self::stringList($raw['action_items'] ?? []), 0, 10),
            'open_questions'  => array_slice(self::stringList($raw['open_questions'] ?? []), 0, 10),
            'technical_notes' => array_slice(self::stringList($raw['technical_notes'] ?? []), 0, 10),
        ];
    }

    public static function formatMomSummary(array $mom): string
    {
        $lines = [];

        $title = self::str($mom['title'] ?? '');
        if ($title !== '') {
            $lines[] = $title;
        }

        $date = self::str($mom['meeting_date'] ?? '');
        if ($date !== '') {
            $lines[] = 'Tanggal: ' . $date;
        }

        $summary = self::str($mom['summary'] ?? '');
        if ($summary !== '') {
            $lines[] = '';
            $lines[] = 'Ringkasan:';
            $lines[] = $summary;
        }

        foreach (array_values(is_array($mom['sections'] ?? null) ? $mom['sections'] : []) as $index => $section) {
            $heading = self::str($section['heading'] ?? '');
            $items = self::stringList($section['items'] ?? []);
            if ($heading === '' || empty($items)) {
                continue;
            }
            $lines[] = '';
            $lines[] = ((int) $index + 1) . '. ' . $heading;
            foreach ($items as $item) {
                $lines[] = '- ' . $item;
            }
        }

        foreach ([
            'Action Items'   => $mom['action_items'] ?? [],
            'Open Questions' => $mom['open_questions'] ?? [],
            'Catatan Teknis' => $mom['technical_notes'] ?? [],
        ] as $heading => $items) {
            $items = self::stringList($items);
            if (empty($items)) {
                continue;
            }
            $lines[] = '';
            $lines[] = $heading . ':';
            foreach ($items as $item) {
                $lines[] = '- ' . $item;
            }
        }

        return trim(implode("\n", $lines));
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

    private static function stringList($value): array
    {
        if (! is_array($value)) {
            $text = self::str($value);
            return $text !== '' ? [$text] : [];
        }

        $items = [];
        foreach (array_values($value) as $item) {
            $text = self::str($item);
            if ($text !== '') {
                $items[] = $text;
            }
        }

        return $items;
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

    private static function failure(string $message, array $data = ['modules' => []]): array
    {
        return [
            'ok'    => false,
            'data'  => $data,
            'error' => $message,
        ];
    }
}
