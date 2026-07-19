<?php

namespace App\Http\Controllers;

use App\Models\AiRequestLog;
use App\Services\AiPlanner;
use App\Support\AppTime;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class AiMonitorController extends Controller
{
    private const FEATURE_LABELS = [
        'mom_fixer' => 'MoM Fixer',
        'wbs_generator' => 'WBS Generator',
        'test_case_generator' => 'Test Case Generator',
        'client_whatsapp_draft' => 'Draft WhatsApp',
        'client_email_draft' => 'Draft Email',
        'ai_diagnostics' => 'Diagnostik AI',
        'fallback_diagnostic' => 'Diagnostik Fallback',
    ];

    public function index()
    {
        $this->ensureDiagnosticsAccess();
        $todayStart = AppTime::now()->startOfDay();
        $monthStart = AppTime::now()->startOfMonth();

        $todayTotal = AiRequestLog::where('created_at', '>=', $todayStart)->count();
        $monthLogs = AiRequestLog::with(['user', 'project', 'client'])
            ->where('created_at', '>=', $monthStart)
            ->latest()
            ->get();

        $recentLogs = AiRequestLog::with(['user', 'project', 'client'])
            ->latest()
            ->limit(25)
            ->get();

        $successCount = $monthLogs->where('status', 'success')->count();
        $failedCount = $monthLogs->where('status', 'failed')->count();
        $monthTotal = $monthLogs->count();
        $providers = AiPlanner::providerStatuses();
        $fallbackEvents = $monthLogs->filter(fn (AiRequestLog $log) => $this->isFallbackEvent($log))->values();

        return view('ai-monitor.index', [
            'title' => 'AI Monitor',
            'providers' => $providers,
            'providerOrderLabel' => collect($providers)->pluck('label')->implode(' → '),
            'summary' => [
                'today_calls' => $todayTotal,
                'month_calls' => $monthTotal,
                'success_rate' => $monthTotal > 0 ? round(($successCount / $monthTotal) * 100) : 0,
                'failed_requests' => $failedCount,
                'average_latency' => (int) round((float) $monthLogs->pluck('latency_ms')->filter()->avg()),
                'fallback_events' => $fallbackEvents->count(),
            ],
            'featureUsage' => $this->featureUsage($monthLogs),
            'providerUsage' => $this->providerUsage($monthLogs),
            'recentLogs' => $recentLogs->map(fn (AiRequestLog $log) => $this->activityRow($log))->all(),
            'lastFallback' => $fallbackEvents->first() ? $this->fallbackRow($fallbackEvents->first()) : null,
            'providerChecks' => $this->providerCheckRows($providers),
        ]);
    }

    public function checkProviders()
    {
        $this->ensureDiagnosticsAccess();

        foreach (AiPlanner::providerStatuses() as $provider) {
            $isReady = (bool) ($provider['configured'] ?? false);

            AiRequestLog::create([
                'user_id' => auth()->id(),
                'project_id' => null,
                'client_id' => null,
                'feature' => 'ai_diagnostics',
                'provider' => strtolower((string) $provider['key']),
                'model' => $provider['model'] ?: null,
                'status' => $isReady ? 'success' : 'failed',
                'fallback_path' => [strtolower((string) $provider['key'])],
                'prompt_tokens' => null,
                'completion_tokens' => null,
                'total_tokens' => null,
                'latency_ms' => 15,
                'error_message' => $isReady
                    ? 'Konfigurasi provider siap digunakan.'
                    : 'Konfigurasi provider belum siap atau API key belum tersedia.',
            ]);
        }

        return redirect()->route('ai-monitor.index')
            ->with('status', 'Cek Kesiapan Provider selesai. Status provider dicatat sebagai log diagnostik metadata-only.');
    }

    public function runFallbackDiagnostic(Request $request)
    {
        $this->ensureDiagnosticsAccess();

        $validated = $request->validate([
            'mode' => ['required', 'in:level_1,full_chain,total_failure'],
        ]);

        $isFullChain = $validated['mode'] === 'full_chain';
        $isTotalFailure = $validated['mode'] === 'total_failure';

        AiRequestLog::create([
            'user_id' => auth()->id(),
            'project_id' => null,
            'client_id' => null,
            'feature' => 'fallback_diagnostic',
            'provider' => $isFullChain || $isTotalFailure ? 'openrouter' : 'groq',
            'model' => $isFullChain || $isTotalFailure ? 'openrouter/auto' : 'llama3-8b-8192',
            'status' => $isTotalFailure ? 'failed' : 'success',
            'fallback_path' => $isTotalFailure
                ? ['gemini_failed', 'groq_failed', 'openrouter_failed']
                : ($isFullChain ? ['gemini_failed', 'groq_failed', 'openrouter'] : ['gemini_failed', 'groq']),
            'prompt_tokens' => 120,
            'completion_tokens' => $isTotalFailure ? 0 : 40,
            'total_tokens' => $isTotalFailure ? 120 : 160,
            'latency_ms' => $isTotalFailure ? 1810 : ($isFullChain ? 1680 : 1240),
            'error_message' => $isTotalFailure
                ? 'Layanan AI sementara belum dapat memproses permintaan. Silakan coba beberapa saat lagi atau lanjutkan pengisian data secara manual.'
                : ($isFullChain
                    ? 'Pemeriksaan Operasional: simulasi kegagalan provider utama Gemini dan Groq. Fallback ke OpenRouter berhasil.'
                    : 'Pemeriksaan Operasional: simulasi kegagalan provider utama Gemini. Fallback ke Groq berhasil.'),
        ]);

        $redirect = redirect()->route('ai-monitor.index')
            ->with('status', $isTotalFailure
                ? 'Layanan AI sementara belum dapat memproses permintaan. Silakan coba beberapa saat lagi atau lanjutkan pengisian data secara manual.'
                : ($isFullChain
                    ? 'Simulasi Fallback Full Chain selesai. Log Gemini gagal → Groq gagal → OpenRouter berhasil telah ditambahkan.'
                    : 'Simulasi Fallback Level 1 selesai. Log Gemini gagal → Groq berhasil telah ditambahkan.'));

        return $isTotalFailure
            ? $redirect->with('status_title', 'Layanan AI belum tersedia')->with('status_tone', 'warning')
            : $redirect;
    }

    private function ensureDiagnosticsAccess(): void
    {
        $user = auth()->user();
        abort_unless($user && ! $user->archived_at && $user->hasAnyRole(['ceo_pm', 'admin', 'super_admin', 'developer']), 403);
    }

    private function featureUsage(Collection $logs): array
    {
        return collect(self::FEATURE_LABELS)
            ->map(function (string $label, string $feature) use ($logs) {
                $items = $logs->where('feature', $feature);

                return [
                    'feature' => $feature,
                    'label' => $label,
                    'count' => $items->count(),
                    'success' => $items->where('status', 'success')->count(),
                    'failed' => $items->where('status', 'failed')->count(),
                ];
            })
            ->values()
            ->all();
    }

    private function providerUsage(Collection $logs): array
    {
        $labels = [
            'gemini' => 'Gemini',
            'groq' => 'Groq',
            'openrouter' => 'OpenRouter',
            'unknown' => 'Provider tidak tersedia',
        ];

        return collect($labels)
            ->map(function (string $label, string $provider) use ($logs) {
                $items = $provider === 'unknown'
                    ? $logs->filter(fn (AiRequestLog $log) => ! in_array($log->provider, ['gemini', 'groq', 'openrouter'], true))
                    : $logs->where('provider', $provider);

                return [
                    'provider' => $provider,
                    'label' => $label,
                    'count' => $items->count(),
                    'success' => $items->where('status', 'success')->count(),
                    'failed' => $items->where('status', 'failed')->count(),
                ];
            })
            ->reject(fn (array $row) => $row['provider'] === 'unknown' && $row['count'] === 0)
            ->values()
            ->all();
    }

    private function providerCheckRows(array $providers): array
    {
        return collect($providers)
            ->map(function (array $provider) {
                $latest = AiRequestLog::query()
                    ->where('feature', 'ai_diagnostics')
                    ->where('provider', $provider['key'])
                    ->latest()
                    ->first();

                return [
                    'label' => $provider['label'],
                    'model' => $provider['model'] ?: 'Tidak tersedia',
                    'check_mode' => 'Cek Kesiapan Konfigurasi',
                    'status' => $latest?->status ?: ((bool) ($provider['configured'] ?? false) ? 'success' : 'failed'),
                    'status_label' => ($latest?->status ?: ((bool) ($provider['configured'] ?? false) ? 'success' : 'failed')) === 'success' ? 'Siap' : 'Belum Siap',
                    'message' => $latest?->error_message ?: ((bool) ($provider['configured'] ?? false)
                        ? 'Konfigurasi provider siap digunakan.'
                        : 'Konfigurasi provider belum siap atau API key belum tersedia.'),
                ];
            })
            ->all();
    }

    private function activityRow(AiRequestLog $log): array
    {
        return [
            'time' => AppTime::diff($log->created_at, '-'),
            'feature' => self::FEATURE_LABELS[$log->feature] ?? 'AI',
            'provider' => $this->providerLabel($log->provider),
            'model' => $log->model ?: 'Tidak tersedia',
            'fallback_path' => $this->fallbackPathLabel($log),
            'status' => $log->status === 'success' ? 'Berhasil' : 'Gagal',
            'status_key' => $log->status,
            'latency' => $log->latency_ms ? number_format($log->latency_ms) . ' ms' : '-',
            'user' => $log->user?->name ?: 'Sistem',
            'related' => $log->project?->name ?: ($log->client?->name ?: (in_array($log->feature, ['ai_diagnostics', 'fallback_diagnostic'], true) ? 'Diagnostik Sistem' : '-')),
            'error' => $log->error_message,
            'note_is_error' => $log->status !== 'success',
        ];
    }

    private function fallbackPathLabel(AiRequestLog $log): ?string
    {
        $path = $log->fallback_path ?: [];
        if ($path === [] || count($path) === 1) {
            return null;
        }

        return collect($path)
            ->map(function ($item) {
                $key = (string) $item;
                $failed = str_contains($key, '_failed');
                $label = $this->providerLabel(str_replace('_failed', '', $key));

                return $failed ? $label . ' gagal' : $label . ' berhasil';
            })
            ->implode(' → ');
    }

    private function fallbackRow(AiRequestLog $log): array
    {
        return [
            'time' => AppTime::diff($log->created_at, '-'),
            'feature' => self::FEATURE_LABELS[$log->feature] ?? 'AI',
            'path' => $this->fallbackPathLabel($log) ?: '-',
            'status' => $log->status,
            'status_label' => $log->status === 'success' ? 'Berhasil' : 'Gagal',
            'reason' => $log->error_message ?: 'Provider utama gagal, lalu sistem mencoba fallback yang tersedia.',
        ];
    }

    private function isFallbackEvent(AiRequestLog $log): bool
    {
        $path = $log->fallback_path ?: [];

        return count($path) > 1 || collect($path)->contains(fn ($item) => str_contains((string) $item, '_failed'));
    }

    private function providerLabel(?string $provider): string
    {
        return match ($provider) {
            'gemini' => 'Gemini',
            'groq' => 'Groq',
            'openrouter' => 'OpenRouter',
            default => 'Tidak tersedia',
        };
    }
}
