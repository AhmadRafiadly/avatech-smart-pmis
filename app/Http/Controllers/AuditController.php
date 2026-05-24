<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AuditController extends Controller
{
    private const ID_MONTHS = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Ags', 'Sep', 'Okt', 'Nov', 'Des'];

    /**
     * Internal filter key per UI chip → module column value.
     */
    private const CHIP_MODULE_MAP = [
        'proyek'   => 'Project Master',
        'klien'    => 'Client Directory',
        'tim'      => 'Team Management',
        'settings' => 'Settings',
        'login'    => 'Auth',
    ];

    private const STANDALONE_TAGS = [
        'password_updated'         => 'PASSWORD DIUBAH',
        'preferences_updated'      => 'SETTINGS DIUBAH',
        'integration_connected'    => 'INTEGRASI TERHUBUNG',
        'integration_disconnected' => 'INTEGRASI DIPUTUS',
        'assignment_created'       => 'PENUGASAN BARU',
        'wbs_module_created'       => 'MODUL WBS BARU',
        'task_created'             => 'TASK BARU',
        'task_status_changed'      => 'STATUS TASK DIUBAH',
    ];

    public function index(Request $request)
    {
        $logs = $this->buildQuery($request, applyChip: false)->limit(500)->get();

        $events = $logs->map(fn (AuditLog $log) => $this->mapEntry($log))->all();
        $filters = $this->selectedFilters($request);

        return view('audit.index', [
            'title'        => 'Audit Trail',
            'events'       => $events,
            'actorOptions' => $this->actorOptions(),
            'todayCount'   => $this->todayCount(),
            'activeChip'   => $filters['chip'],
            'selectedActor'=> $filters['actor'],
            'selectedRange'=> $filters['range'],
        ]);
    }

    /**
     * Dropdown options for the actor filter — sourced from the users table so
     * the list stays in sync with Team Management even when a user has no
     * audit activity yet. Mirrors Team Management's default scope by hiding
     * archived users.
     */
    private function actorOptions(): array
    {
        return User::query()
            ->whereNull('archived_at')
            ->orderBy('name')
            ->pluck('name')
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    public function exportCsv(Request $request): StreamedResponse
    {
        $logs = $this->buildQuery($request)->limit(5000)->get();

        $filename = 'audit-' . Carbon::now()->format('Y-m-d-His') . '.csv';

        return response()->streamDownload(function () use ($logs) {
            $handle = fopen('php://output', 'w');
            // BOM for Excel UTF-8
            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, ['ID', 'Date', 'Time', 'Actor', 'Module', 'Tag', 'Action', 'Description', 'IP', 'User Agent']);
            foreach ($logs as $log) {
                $created = $log->created_at ?: Carbon::now();
                fputcsv($handle, [
                    $log->id,
                    $created->format('Y-m-d'),
                    $created->format('H:i'),
                    $log->user?->name ?? 'Sistem',
                    $log->module,
                    self::tagForLog($log->module, $log->action, $log->auditable_type, $log->description),
                    $log->action,
                    trim(strip_tags((string) $log->description)),
                    $log->ip_address ?? '',
                    $log->user_agent ?? '',
                ]);
            }
            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=utf-8',
        ]);
    }

    public function report(Request $request)
    {
        $logs = $this->buildQuery($request)->limit(2000)->get();
        $events = $logs->map(fn (AuditLog $log) => $this->mapEntry($log))->all();

        return view('audit.report', [
            'title'       => 'Laporan Audit Trail',
            'events'      => $events,
            'generatedAt' => Carbon::now(),
            'filters'     => [
                'chip'  => $this->normalizeChip((string) $request->query('chip', 'all')),
                'actor' => $request->query('actor', 'all'),
                'range' => $request->query('range', 'all'),
            ],
        ]);
    }

    public static function categoryForModule(string $module, string $action = ''): string
    {
        return match ($module) {
            'Project Master'   => 'proyek',
            'Client Directory' => 'klien',
            'Team Management'  => 'tim',
            'Settings'         => 'settings',
            'Auth'             => 'login',
            default            => 'all',
        };
    }

    public static function tagForLog(string $module, string $action, ?string $auditableType = null, ?string $description = null): string
    {
        if (isset(self::STANDALONE_TAGS[$action])) {
            return self::STANDALONE_TAGS[$action];
        }

        // Backfill: older logs may have action='created' with auditable_type
        // pointing at TeamAssignment (or description starting with "Menambah penugasan"),
        // even though the action wasn't yet specialised as 'assignment_created'.
        if ($action === 'created' && $module === 'Team Management') {
            $isAssignmentByType = $auditableType !== null
                && str_ends_with($auditableType, '\\TeamAssignment');
            $isAssignmentByDesc = $description !== null
                && stripos(trim(strip_tags($description)), 'Menambah penugasan') === 0;
            if ($isAssignmentByType || $isAssignmentByDesc) {
                return self::STANDALONE_TAGS['assignment_created'];
            }
        }

        $actionSuffix = match ($action) {
            'created'  => 'BARU',
            'updated'  => 'DIPERBARUI',
            'archived' => 'DIARSIPKAN',
            'restored' => 'DIPULIHKAN',
            default    => mb_strtoupper(str_replace('_', ' ', $action)),
        };

        $modulePrefix = match ($module) {
            'Project Master'   => 'PROYEK',
            'Client Directory' => 'KLIEN',
            'Team Management'  => 'ANGGOTA',
            'Settings'         => 'SETTINGS',
            'Auth'             => 'AKUN',
            default            => mb_strtoupper($module),
        };

        return $modulePrefix . ' ' . $actionSuffix;
    }

    private function buildQuery(Request $request, bool $applyChip = true)
    {
        $query = AuditLog::with('user')
            ->orderByDesc('created_at')
            ->orderByDesc('id');

        $chip = $this->normalizeChip((string) $request->query('chip', 'all'));
        if ($applyChip && $chip !== 'all') {
            $query->where('module', self::CHIP_MODULE_MAP[$chip]);
        }

        $actor = trim((string) $request->query('actor', 'all'));
        if ($actor !== '' && $actor !== 'all') {
            $query->whereHas('user', fn ($u) => $u->where('name', $actor));
        }

        $range = (string) $request->query('range', 'all');
        if ($range !== '' && $range !== 'all') {
            $days = (int) $range;
            if ($days > 0) {
                $query->where('created_at', '>=', Carbon::now()->subDays($days)->startOfDay());
            }
        }

        return $query;
    }

    private function selectedFilters(Request $request): array
    {
        return [
            'chip' => $this->normalizeChip((string) $request->query('chip', 'all')),
            'actor' => trim((string) $request->query('actor', 'all')) ?: 'all',
            'range' => trim((string) $request->query('range', 'all')) ?: 'all',
        ];
    }

    private function normalizeChip(string $chip): string
    {
        $chip = trim($chip);

        return $chip !== '' && isset(self::CHIP_MODULE_MAP[$chip])
            ? $chip
            : 'all';
    }

    private function todayCount(): int
    {
        try {
            return AuditLog::where('created_at', '>=', Carbon::now()->startOfDay())->count();
        } catch (\Throwable $e) {
            return 0;
        }
    }

    private function mapEntry(AuditLog $log): array
    {
        $created = $log->created_at ?: Carbon::now();
        $today = Carbon::now()->startOfDay();
        $logDay = $created->copy()->startOfDay();

        if ($logDay->equalTo($today)) {
            $date = 'Hari Ini';
        } elseif ($logDay->equalTo($today->copy()->subDay())) {
            $date = 'Kemarin';
        } else {
            $date = $this->formatDateId($created);
        }

        $actor = $log->user?->name ?? 'Sistem';
        $tag = self::tagForLog($log->module, $log->action, $log->auditable_type, $log->description);
        $filter = self::categoryForModule($log->module, $log->action);

        return [
            'id'       => $log->id,
            'date'     => $date,
            'time'     => $created->format('H:i'),
            'actor'    => $actor,
            'initials' => $this->initials($actor),
            'tag'      => $tag,
            'filter'   => $filter,
            'module'   => $log->module,
            'text'     => $log->description ?: $this->fallbackDescription($log, $actor),
            'days'     => (int) $logDay->diffInDays($today),
        ];
    }

    private function fallbackDescription(AuditLog $log, string $actor): string
    {
        return e($actor) . ' melakukan ' . e(str_replace('_', ' ', $log->action)) . ' di ' . e($log->module);
    }

    private function formatDateId(Carbon $date): string
    {
        $month = self::ID_MONTHS[((int) $date->format('n')) - 1] ?? $date->format('M');

        return $date->format('d') . ' ' . $month;
    }

    private function initials(string $value): string
    {
        $parts = array_values(array_filter(preg_split('/\s+/', trim($value)) ?: []));
        if ($parts === []) {
            return '?';
        }
        if (count($parts) === 1) {
            return mb_strtoupper(mb_substr($parts[0], 0, 2));
        }

        return mb_strtoupper(mb_substr($parts[0], 0, 1) . mb_substr($parts[1], 0, 1));
    }
}
