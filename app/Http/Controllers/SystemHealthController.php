<?php

namespace App\Http\Controllers;

use App\Models\AiRequestLog;
use App\Models\AuditLog;
use App\Services\AiPlanner;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SystemHealthController extends Controller
{
    public function index()
    {
        return view('system-health.index', [
            'title' => 'System Health',
            'checks' => $this->checks(),
            'roleMatrix' => $this->roleMatrix(),
            'safetyNotes' => $this->safetyNotes(),
            'appInfo' => $this->appInfo(),
            'providerOrder' => collect(AiPlanner::providerStatuses())->pluck('label')->implode(' -> '),
        ]);
    }

    private function checks(): array
    {
        return [
            $this->databaseCheck(),
            $this->storageCheck(),
            $this->cacheCheck(),
            [
                'label' => 'PDF Export',
                'status' => class_exists(\Barryvdh\DomPDF\Facade\Pdf::class) || class_exists(\Dompdf\Dompdf::class) ? 'ready' : 'warning',
                'value' => class_exists(\Barryvdh\DomPDF\Facade\Pdf::class) || class_exists(\Dompdf\Dompdf::class) ? 'Siap' : 'Tidak Tersedia',
                'description' => class_exists(\Barryvdh\DomPDF\Facade\Pdf::class) || class_exists(\Dompdf\Dompdf::class)
                    ? 'Komponen PDF tersedia untuk export WBS dan test case.'
                    : 'Komponen PDF belum terdeteksi. Periksa paket export sebelum demo.',
                'icon' => 'document-text',
            ],
            [
                'label' => 'AI Provider',
                'status' => AiPlanner::isConfigured() ? 'ready' : 'warning',
                'value' => AiPlanner::isConfigured() ? 'Siap' : 'Perlu Perhatian',
                'description' => 'Urutan provider: ' . collect(AiPlanner::providerStatuses())->pluck('label')->implode(' -> '),
                'icon' => 'sparkles',
            ],
            [
                'label' => 'App Environment',
                'status' => app()->hasDebugModeEnabled() ? 'warning' : 'ready',
                'value' => app()->environment('production') ? 'Mode Produksi' : 'Mode Lokal',
                'description' => 'Environment: ' . app()->environment() . ' · Debug: ' . (app()->hasDebugModeEnabled() ? 'Aktif' : 'Nonaktif'),
                'icon' => 'server-stack',
            ],
            [
                'label' => 'Migration Readiness',
                'status' => $this->hasTable('migrations') ? 'ready' : 'warning',
                'value' => $this->hasTable('migrations') ? 'Siap' : 'Perlu Perhatian',
                'description' => $this->hasTable('migrations')
                    ? 'Tabel migrations tersedia. Tidak ada migrasi yang dijalankan dari halaman ini.'
                    : 'Tabel migrations belum terdeteksi. Jalankan pengecekan deployment secara manual.',
                'icon' => 'circle-stack',
            ],
            [
                'label' => 'Last Activity',
                'status' => $this->latestAuditTime() ? 'ready' : 'info',
                'value' => $this->latestAuditTime()?->diffForHumans() ?: 'Belum ada',
                'description' => $this->latestAuditTime()
                    ? 'Riwayat aktivitas terakhir tersedia dari Audit Trail.'
                    : 'Belum ada aktivitas yang tercatat.',
                'icon' => 'clock',
            ],
            [
                'label' => 'AI Monitor Logging',
                'status' => $this->hasTable('ai_request_logs') && class_exists(AiRequestLog::class) ? 'ready' : 'warning',
                'value' => $this->hasTable('ai_request_logs') ? 'Siap' : 'Tidak Tersedia',
                'description' => $this->hasTable('ai_request_logs')
                    ? 'Log metadata AI tersedia untuk AI Monitor.'
                    : 'Tabel log AI belum tersedia. Jalankan migrasi yang sesuai sebelum demo.',
                'icon' => 'cpu-chip',
            ],
        ];
    }

    private function databaseCheck(): array
    {
        try {
            DB::connection()->getPdo();

            return [
                'label' => 'Database',
                'status' => 'ready',
                'value' => 'Siap',
                'description' => 'Koneksi database aktif dan dapat digunakan.',
                'icon' => 'circle-stack',
            ];
        } catch (\Throwable) {
            return [
                'label' => 'Database',
                'status' => 'critical',
                'value' => 'Perlu Perhatian',
                'description' => 'Database belum dapat diakses. Periksa konfigurasi koneksi secara aman.',
                'icon' => 'circle-stack',
            ];
        }
    }

    private function storageCheck(): array
    {
        $path = storage_path('app');
        $writable = is_dir($path) && is_writable($path);

        return [
            'label' => 'Storage',
            'status' => $writable ? 'ready' : 'warning',
            'value' => $writable ? 'Siap' : 'Perlu Perhatian',
            'description' => $writable
                ? 'Storage aplikasi siap menulis file sementara dan export.'
                : 'Storage tidak writable. Periksa permission folder storage.',
            'icon' => 'archive-box',
        ];
    }

    private function cacheCheck(): array
    {
        try {
            $key = 'system_health_check_' . md5((string) now()->timestamp);
            Cache::put($key, 'ok', 30);
            $ok = Cache::get($key) === 'ok';
            Cache::forget($key);

            return [
                'label' => 'Cache',
                'status' => $ok ? 'ready' : 'warning',
                'value' => $ok ? 'Siap' : 'Perlu Perhatian',
                'description' => $ok ? 'Cache dapat ditulis dan dibaca.' : 'Cache belum merespons sesuai harapan.',
                'icon' => 'bolt',
            ];
        } catch (\Throwable) {
            return [
                'label' => 'Cache',
                'status' => 'warning',
                'value' => 'Perlu Perhatian',
                'description' => 'Cache belum dapat digunakan. Periksa driver cache sebelum deployment.',
                'icon' => 'bolt',
            ];
        }
    }

    private function roleMatrix(): array
    {
        return [
            ['role' => 'CEO/PM', 'access' => ['Executive Monitor', 'Project Master', 'Client Directory', 'Team Assignment', 'Smart Insights', 'AI Monitor', 'System Health', 'Audit Trail', 'Settings']],
            ['role' => 'System Analyst / Quality Assurance', 'access' => ['Operational Dashboard', 'Assigned Projects', 'MoM', 'AI MoM Fixer', 'AI WBS Generator', 'QC/Test Case', 'AI Test Case Generator', 'Activity Log']],
            ['role' => 'Fullstack Developer', 'access' => ['Operational Dashboard', 'Assigned Projects', 'Kanban Workspace', 'WBS/task editing', 'Task status update', 'Task assignment/estimation']],
            ['role' => 'UI/UX Designer', 'access' => ['Operational Dashboard', 'Assigned Projects', 'Workspace task sesuai assignment', 'Task status update sesuai scope']],
            ['role' => 'Admin / Super Admin / Developer', 'access' => ['Management pages', 'AI Monitor', 'System Health', 'Audit Trail', 'Settings', 'Filament admin panel jika role mengizinkan']],
        ];
    }

    private function safetyNotes(): array
    {
        return [
            'Output AI bersifat draf dan tetap membutuhkan validasi pengguna.',
            'AI Monitor hanya menyimpan metadata aman, bukan isi prompt penuh atau respons penuh.',
            'API key tidak ditampilkan pada antarmuka pengguna.',
            'Metadata AI mencakup provider, model, status, latensi, fitur, dan fallback path.',
            'Data client dan project tetap perlu ditangani dengan hati-hati.',
        ];
    }

    private function appInfo(): array
    {
        return [
            ['label' => 'App Name', 'value' => 'Avatech Smart-PMIS'],
            ['label' => 'Version', 'value' => 'v1.0 Demo Build'],
            ['label' => 'Environment', 'value' => app()->environment()],
            ['label' => 'Debug Mode', 'value' => app()->hasDebugModeEnabled() ? 'On' : 'Off'],
            ['label' => 'PHP Version', 'value' => PHP_VERSION],
            ['label' => 'Laravel Version', 'value' => Application::VERSION],
            ['label' => 'Waktu Sistem', 'value' => now()->format('d M Y H:i')],
            ['label' => 'Aktivitas Terakhir', 'value' => $this->latestAuditTime()?->diffForHumans() ?: 'Belum ada aktivitas'],
        ];
    }

    private function hasTable(string $table): bool
    {
        try {
            return Schema::hasTable($table);
        } catch (\Throwable) {
            return false;
        }
    }

    private function latestAuditTime(): ?\Illuminate\Support\Carbon
    {
        try {
            return AuditLog::latest('created_at')->first()?->created_at;
        } catch (\Throwable) {
            return null;
        }
    }
}
