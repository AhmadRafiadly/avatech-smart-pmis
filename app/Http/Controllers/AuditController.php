<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\User;
use App\Support\AppTime;
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
        'wbs_module_updated'       => 'MODUL WBS DIPERBARUI',
        'wbs_module_deleted'       => 'MODUL WBS DIHAPUS',
        'task_created'             => 'TASK BARU',
        'task_updated'             => 'TASK DIPERBARUI',
        'task_assigned'            => 'TASK DITUGASKAN',
        'task_deleted'             => 'TASK DIHAPUS',
        'task_status_changed'      => 'STATUS TASK DIUBAH',
        'mom_created'              => 'MOM BARU',
        'mom_summary_updated'      => 'MOM DIPERBARUI',
        'ai_mom_fixed'             => 'AI MOM DIRAPIKAN',
        'ai_wbs_generated'         => 'WBS AI DIBUAT',
        'ai_test_cases_generated'  => 'AI TEST CASE DIBUAT',
        'qc_created'               => 'QC BARU',
        'qc_status_updated'        => 'QC DIPERBARUI',
        'qc_updated'               => 'QC DETAIL DIUBAH',
        'qc_deleted'               => 'QC DIHAPUS',
        'wbs_pdf_exported'         => 'WBS PDF DIEKSPOR',
        'test_case_pdf_exported'   => 'TEST CASE PDF DIEKSPOR',
    ];

    public function index(Request $request)
    {
        $logs = $this->buildQuery($request, applyChip: false)->limit(500)->get();

        $events = $logs->map(fn (AuditLog $log) => $this->mapEntry($log))->all();
        $filters = $this->selectedFilters($request);
        $isOperationalView = $this->isOperationalViewer();

        return view('audit.index', [
            // Browser tab title — operational view reads as "Activity Log".
            'title'             => $isOperationalView ? 'Activity Log' : 'Audit Trail',
            'isOperationalView' => $isOperationalView,
            'events'            => $events,
            'actorOptions'      => $this->actorOptions(),
            'todayCount'        => $this->todayCount(),
            'activeChip'        => $filters['chip'],
            'selectedActor'     => $filters['actor'],
            'selectedRange'     => $filters['range'],
        ]);
    }

    /**
     * Dropdown options for the actor filter — sourced from the users table so
     * the list stays in sync with Team Management even when a user has no
     * audit activity yet. Mirrors Team Management's default scope by hiding
     * archived users.
     */
    /**
     * Roles that are allowed to see the full company-wide audit feed.
     * Operational roles get a self-only view.
     */
    private const FULL_AUDIT_ROLES = ['ceo_pm', 'admin', 'super_admin', 'developer'];

    private function isOperationalViewer(): bool
    {
        $role = auth()->user()?->roles()->first()?->name;

        return $role && ! in_array($role, self::FULL_AUDIT_ROLES, true);
    }

    private function actorOptions(): array
    {
        // Operational viewers can only filter to themselves — they should
        // never see other users in the actor dropdown.
        if ($this->isOperationalViewer()) {
            return array_filter([auth()->user()?->name]);
        }

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

        $filename = 'audit-' . AppTime::now()->format('Y-m-d-His') . '.csv';

        return response()->streamDownload(function () use ($logs) {
            $handle = fopen('php://output', 'w');
            // BOM for Excel UTF-8
            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, ['ID', 'Date', 'Time', 'Actor', 'Module', 'Tag', 'Action', 'Description', 'IP', 'User Agent']);
            foreach ($logs as $log) {
                $created = AppTime::cast($log->created_at) ?: AppTime::now();
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
            'generatedAt' => AppTime::now(),
            'filters'     => [
                'chip'  => $this->normalizeChip((string) $request->query('chip', 'all')),
                'actor' => $request->query('actor', 'all'),
                'range' => $request->query('range', 'all'),
            ],
        ]);
    }

    /**
     * Resolve a safe deep link for an audit row so notifications/activity
     * entries can jump to the right Project Detail tab. Falls back to
     * /audit when the parent project can't be resolved — we never want to
     * accidentally point a notification at the wrong project.
     */
    public static function deepLinkForLog(AuditLog $log): string
    {
        if ($log->module === 'Project Master') {
            $projectId = self::resolveProjectIdForLog($log);
            if ($projectId) {
                $anchor = self::deepLinkAnchorForAction((string) $log->action);
                return url('/projects/' . $projectId) . $anchor;
            }
        }

        if ($log->module === 'Client Directory') {
            $clientId = self::resolveClientIdForLog($log);
            if ($clientId) {
                return route('clients.index', ['open' => 'client:' . $clientId, 'client' => $clientId]);
            }
        }

        return route('audit.index');
    }

    private static function deepLinkAnchorForAction(string $action): string
    {
        return match (true) {
            str_starts_with($action, 'qc_')   => '#qc',
            str_starts_with($action, 'mom_')  => '#aiplanning',
            $action === 'ai_mom_fixed'        => '#aiplanning',
            $action === 'ai_wbs_generated'    => '#aiplanning',
            $action === 'ai_test_cases_generated' => '#qc',
            str_starts_with($action, 'wbs_module_') => '#overview',
            str_starts_with($action, 'task_') => '#workspace',
            default                           => '',
        };
    }

    /**
     * Resolve the parent project's name for an audit row (used to render
     * the "ERP Test 1 · Project Master" context line under each entry).
     * Cached per request so a 500-row audit page doesn't fan out into 500
     * SELECTs even when many rows share the same project.
     */
    public static function projectNameForLog(AuditLog $log): ?string
    {
        static $cache = [];
        $pid = self::resolveProjectIdForLog($log);
        if ($pid === null) {
            return null;
        }
        if (! array_key_exists($pid, $cache)) {
            try {
                $cache[$pid] = (string) (\App\Models\Project::query()->whereKey($pid)->value('name') ?? '') ?: null;
            } catch (\Throwable $e) {
                $cache[$pid] = null;
            }
        }
        return $cache[$pid];
    }

    public static function contextNameForLog(AuditLog $log): ?string
    {
        if ($projectName = self::projectNameForLog($log)) {
            return $projectName;
        }

        $clientId = self::resolveClientIdForLog($log);
        if (! $clientId) {
            return null;
        }

        static $cache = [];
        if (! array_key_exists($clientId, $cache)) {
            try {
                $cache[$clientId] = (string) (\App\Models\Client::query()->whereKey($clientId)->value('name') ?? '') ?: null;
            } catch (\Throwable $e) {
                $cache[$clientId] = null;
            }
        }

        return $cache[$clientId];
    }

    /**
     * Pull project_id out of the audit row. Prefers the snapshot we
     * persisted in new_values / old_values; for older rows that don't
     * carry it, falls back to a single SELECT against the auditable
     * model. Returns null if neither path resolves a real project — the
     * caller will then route to /audit instead of a wrong /projects/{id}.
     */
    public static function resolveProjectIdForLog(AuditLog $log): ?int
    {
        foreach ([(array) ($log->new_values ?? []), (array) ($log->old_values ?? [])] as $vals) {
            if (isset($vals['project_id']) && (int) $vals['project_id'] > 0) {
                return (int) $vals['project_id'];
            }
        }

        $type = (string) $log->auditable_type;
        $id   = (int) $log->auditable_id;
        if ($id <= 0 || $type === '') {
            return null;
        }

        if ($type === \App\Models\Project::class) {
            return $id;
        }

        $modelClass = match (true) {
            str_ends_with($type, '\\ProjectTask')   => \App\Models\ProjectTask::class,
            str_ends_with($type, '\\ProjectModule') => \App\Models\ProjectModule::class,
            str_ends_with($type, '\\ProjectMom')    => \App\Models\ProjectMom::class,
            str_ends_with($type, '\\ProjectQcTest') => \App\Models\ProjectQcTest::class,
            default                                 => null,
        };
        if ($modelClass === null) {
            return null;
        }

        try {
            $pid = (int) $modelClass::query()->whereKey($id)->value('project_id');
            return $pid > 0 ? $pid : null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    public static function resolveClientIdForLog(AuditLog $log): ?int
    {
        foreach ([(array) ($log->new_values ?? []), (array) ($log->old_values ?? [])] as $vals) {
            if (isset($vals['client_id']) && (int) $vals['client_id'] > 0) {
                return (int) $vals['client_id'];
            }
        }

        $type = (string) $log->auditable_type;
        $id = (int) $log->auditable_id;
        if ($id <= 0 || $type === '') {
            return null;
        }

        if ($type === \App\Models\Client::class || str_ends_with($type, '\\Client')) {
            return $id;
        }

        return null;
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

        // Hard scope for operational roles — they only ever see their own logs.
        if ($this->isOperationalViewer()) {
            $query->where('user_id', auth()->id());
        }

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
                $query->where('created_at', '>=', AppTime::now()->subDays($days)->startOfDay());
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
            $query = AuditLog::where('created_at', '>=', AppTime::now()->startOfDay());
            if ($this->isOperationalViewer()) {
                $query->where('user_id', auth()->id());
            }
            return $query->count();
        } catch (\Throwable $e) {
            return 0;
        }
    }

    private function mapEntry(AuditLog $log): array
    {
        $created = AppTime::cast($log->created_at) ?: AppTime::now();
        $today = AppTime::now()->startOfDay();
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

        /* Deep link + project name resolution. project_name doubles as
         * the "has a safe project deep link" flag for the view layer —
         * if it's null we don't render a project context line and (on
         * /audit) we keep the row non-clickable to avoid sending the
         * user back to /audit in a loop. */
        $projectName = self::contextNameForLog($log);
        $deepLink    = self::deepLinkForLog($log);
        $hasDeepLink = $projectName !== null && (str_contains($deepLink, '/projects/') || str_contains($deepLink, '/clients'));

        return [
            'id'           => $log->id,
            'date'         => $date,
            'time'         => $created->format('H:i'),
            'actor'        => $actor,
            'initials'     => $this->initials($actor),
            'tag'          => $tag,
            'filter'       => $filter,
            'module'       => $log->module,
            'text'         => $log->description ?: $this->fallbackDescription($log, $actor),
            'days'         => (int) $logDay->diffInDays($today),
            'deep_link'    => $hasDeepLink ? $deepLink : null,
            'project_name' => $projectName,
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
