<?php

namespace App\Http\Controllers;

use App\Models\AiRequestLog;
use App\Services\AiPlanner;
use App\Support\AppTime;
use Illuminate\Support\Collection;

class AiMonitorController extends Controller
{
    private const FEATURE_LABELS = [
        'mom_fixer' => 'MoM Fixer',
        'wbs_generator' => 'WBS Generator',
        'test_case_generator' => 'Test Case Generator',
        'client_whatsapp_draft' => 'Draft WhatsApp',
        'client_email_draft' => 'Draft Email',
    ];

    public function index()
    {
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

        $fallbackEvents = $monthLogs->filter(fn (AiRequestLog $log) => $this->isFallbackEvent($log))->values();

        return view('ai-monitor.index', [
            'title' => 'AI Monitor',
            'providers' => AiPlanner::providerStatuses(),
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
            'featureLabels' => self::FEATURE_LABELS,
        ]);
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
            'unknown' => 'Diagnostic: Provider tidak tersedia',
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

    private function activityRow(AiRequestLog $log): array
    {
        return [
            'time' => AppTime::diff($log->created_at, '-'),
            'feature' => self::FEATURE_LABELS[$log->feature] ?? 'AI',
            'provider' => $this->providerLabel($log->provider),
            'model' => $log->model ?: 'Tidak tersedia',
            'status' => $log->status === 'success' ? 'Berhasil' : 'Gagal',
            'status_key' => $log->status,
            'latency' => $log->latency_ms ? number_format($log->latency_ms) . ' ms' : '-',
            'user' => $log->user?->name ?: 'Sistem',
            'related' => $log->project?->name ?: ($log->client?->name ?: '-'),
            'error' => $log->error_message,
        ];
    }

    private function fallbackRow(AiRequestLog $log): array
    {
        return [
            'time' => AppTime::diff($log->created_at, '-'),
            'feature' => self::FEATURE_LABELS[$log->feature] ?? 'AI',
            'path' => collect($log->fallback_path ?: [])
                ->map(fn ($item) => str_replace('_failed', ' gagal', $this->providerLabel(str_replace('_failed', '', (string) $item))))
                ->implode(' -> '),
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
