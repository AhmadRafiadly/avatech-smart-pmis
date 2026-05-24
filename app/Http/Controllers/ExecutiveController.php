<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Client;
use App\Models\Project;
use App\Models\TeamAssignment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class ExecutiveController extends Controller
{
    private const ID_MONTHS = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Ags', 'Sep', 'Okt', 'Nov', 'Des'];

    private const STATUS_LABEL = [
        'on-track' => 'On Track',
        'attention' => 'Needs Attention',
        'critical' => 'Critical',
    ];

    private const OPERATIONAL_ROLES = ['sa_qa', 'uiux_designer', 'ui_ux', 'fullstack_dev'];

    private const CLOSED_ASSIGNMENT_STATUSES = ['done', 'completed', 'cancelled', 'canceled', 'archived'];

    public function index(Request $request)
    {
        /*
         * Executive Monitor is CEO/PM-only. Operational users who land here
         * (typed URL, stale bookmark) get redirected to their dashboard
         * so they never see executive overview data.
         */
        $role = $request->user()?->roles()->first()?->name;
        if ($role && $role !== 'ceo_pm' && ! in_array($role, ['admin', 'super_admin', 'developer'], true)) {
            return redirect()->route('dashboard.index');
        }

        $selectedMonth = $this->resolveMonth((string) $request->query('month', ''));
        $monthStart = $selectedMonth->copy()->startOfMonth();
        $monthEnd = $selectedMonth->copy()->endOfMonth();

        $activeProjectsQuery = Project::query()->whereNull('archived_at');
        $totalProjects = (clone $activeProjectsQuery)->count();
        $archivedProjects = Project::whereNotNull('archived_at')->count();
        $aiCount = (clone $activeProjectsQuery)->where('ai_wbs_generated', true)->count();
        $aiPct = $totalProjects > 0 ? (int) round($aiCount / $totalProjects * 100) : 0;

        $totalClients = Client::whereNull('archived_at')->count();
        $archivedClients = Client::whereNotNull('archived_at')->count();

        $teamMembers = User::with([
                'roles',
                'teamAssignments' => fn ($query) => $this->scopeAssignmentMonth($query, $monthStart, $monthEnd)
                    ->with('project'),
            ])
            ->whereNull('archived_at')
            ->whereDoesntHave('roles', fn ($query) => $query->where('name', 'ceo_pm'))
            ->whereHas('roles', fn ($query) => $query->whereIn('name', self::OPERATIONAL_ROLES))
            ->orderBy('name')
            ->get();

        $teamLoad = $teamMembers
            ->map(fn (User $user) => $this->teamLoadRow($user))
            ->sortByDesc('load')
            ->values()
            ->all();

        $avgLoad = count($teamLoad) > 0
            ? (int) round(collect($teamLoad)->avg('load'))
            : 0;

        $metrics = [
            ['key' => 'projects', 'icon' => 'folder', 'label' => 'Active Projects', 'value' => (string) $totalProjects, 'foot' => $archivedProjects . ' archived excluded', 'foot_icon' => 'archive-box', 'foot_color' => 'text-slate-500', 'progress' => null],
            ['key' => 'wbs', 'icon' => 'clipboard-document-check', 'label' => 'WBS Coverage', 'value' => $aiPct . '%', 'foot' => $aiCount . '/' . $totalProjects . ' active projects marked WBS-ready', 'foot_icon' => 'clipboard-document-list', 'foot_color' => 'text-violet-600 font-medium', 'progress' => null],
            ['key' => 'workload', 'icon' => 'users', 'label' => 'Team Workload', 'value' => $avgLoad . '%', 'foot' => $this->loadFootLabel($avgLoad, $teamMembers->count()), 'foot_icon' => null, 'foot_color' => null, 'progress' => min(100, $avgLoad)],
            ['key' => 'clients', 'icon' => 'building-office', 'label' => 'Active Clients', 'value' => (string) $totalClients, 'foot' => $archivedClients . ' archived excluded', 'foot_icon' => 'information-circle', 'foot_color' => 'text-slate-500', 'progress' => null],
        ];

        $projects = Project::with(['client', 'lead'])
            ->whereNull('archived_at')
            ->orderByDesc('is_featured')
            ->orderBy('id')
            ->get()
            ->map(function (Project $project) {
                $leadName = $project->lead?->name;

                return [
                    'id' => $project->id,
                    'code' => $project->code,
                    'color' => $project->color,
                    'name' => $project->name,
                    'client' => $project->client?->name ?? '-',
                    'lead' => $leadName ? $this->firstName($leadName) : '-',
                    'lead_initials' => $this->initials($leadName ?? '?'),
                    'phase' => $project->phase,
                    'due' => $this->formatDueId($project->due_at),
                    'progress' => (int) $project->progress,
                    'status' => $project->status,
                    'status_label' => self::STATUS_LABEL[$project->status] ?? $project->status,
                ];
            })
            ->all();

        $statusCounts = Project::whereNull('archived_at')
            ->selectRaw('status, COUNT(*) as c')
            ->groupBy('status')
            ->pluck('c', 'status');

        $projectStats = [
            'onTrack' => (int) ($statusCounts['on-track'] ?? 0),
            'attention' => (int) ($statusCounts['attention'] ?? 0),
            'critical' => (int) ($statusCounts['critical'] ?? 0),
        ];

        $poolDefs = [
            ['label' => 'Fullstack Pool', 'role' => 'fullstack_dev'],
            ['label' => 'UI/UX Senior', 'role' => 'uiux_designer'],
            ['label' => 'SA / QA', 'role' => 'sa_qa'],
        ];

        $pools = collect($poolDefs)->map(function (array $pool) use ($teamLoad) {
            $rows = collect($teamLoad)->filter(fn (array $row) => $row['role_slug'] === $pool['role']);
            $count = $rows->count();

            return [
                'label' => $pool['label'],
                'avg' => $count > 0 ? (int) round($rows->avg('load')) : 0,
                'count' => $count,
            ];
        })->all();

        $recentActivities = AuditLog::with('user')
            ->whereBetween('created_at', [$monthStart, $monthEnd])
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit(3)
            ->get()
            ->map(fn (AuditLog $log) => $this->activityCard($log))
            ->all();

        return view('executive.index', [
            'title' => 'Executive Monitor',
            'metrics' => $metrics,
            'projects' => $projects,
            'projectStats' => $projectStats,
            'teamLoad' => $teamLoad,
            'pools' => $pools,
            'recentActivities' => $recentActivities,
            'overloadAlert' => collect($teamLoad)->first(fn (array $row) => $row['load'] >= 85),
            'monthOptions' => $this->monthOptions($selectedMonth),
            'selectedMonth' => $selectedMonth->format('Y-m'),
            'selectedMonthLabel' => $this->formatMonthYearId($selectedMonth),
        ]);
    }

    private function scopeAssignmentMonth($query, Carbon $monthStart, Carbon $monthEnd)
    {
        return $query
            ->whereNotIn('status', self::CLOSED_ASSIGNMENT_STATUSES)
            ->where(function ($query) use ($monthStart, $monthEnd) {
                $query
                    ->whereBetween('due_date', [$monthStart->toDateString(), $monthEnd->toDateString()])
                    ->orWhere(function ($query) use ($monthStart, $monthEnd) {
                        $query
                            ->whereNull('due_date')
                            ->whereBetween('created_at', [$monthStart, $monthEnd]);
                    });
            });
    }

    private function teamLoadRow(User $user): array
    {
        $roleSlug = $user->roles->pluck('name')->first(fn ($role) => in_array($role, self::OPERATIONAL_ROLES, true));
        $poolSlug = $roleSlug === 'ui_ux' ? 'uiux_designer' : $roleSlug;
        $assignments = $user->teamAssignments;
        $hours = (int) $assignments->sum(fn (TeamAssignment $assignment) => (int) ($assignment->estimated_hours ?? 0));
        $capacity = 40;
        $load = $capacity > 0 ? (int) round($hours / $capacity * 100) : 0;

        return [
            'name' => $user->name,
            'initials' => $this->initials($user->name),
            'role' => $this->roleLabel($roleSlug),
            'role_slug' => $poolSlug,
            'load' => $load,
            'bar_load' => min(100, $load),
            'hours' => $hours,
            'capacity' => $capacity,
            'tasks' => $assignments->count(),
        ];
    }

    private function activityCard(AuditLog $log): array
    {
        $style = $this->moduleStyle($log->module);
        $actor = $log->user?->name ?? 'Sistem';
        $description = trim(strip_tags((string) $log->description));

        return [
            'badge' => AuditController::tagForLog($log->module, $log->action, $log->auditable_type, $log->description),
            'icon' => $style['icon'],
            'color' => $style['color'],
            'badge_bg' => $style['badge_bg'],
            'title' => $log->module . ' oleh ' . $actor,
            'body' => $description !== '' ? html_entity_decode($description) : $actor . ' melakukan ' . str_replace('_', ' ', $log->action) . ' di ' . $log->module . '.',
            'action' => 'Lihat Audit',
            'time' => $log->created_at?->diffForHumans() ?? 'baru saja',
            'href' => $this->auditHref($log->module),
        ];
    }

    private function auditHref(string $module): string
    {
        $chip = AuditController::categoryForModule($module);

        return $chip === 'all'
            ? route('audit.index')
            : route('audit.index', ['chip' => $chip]);
    }

    private function moduleStyle(string $module): array
    {
        return match ($module) {
            'Project Master' => ['icon' => 'folder', 'color' => '#5B21B6', 'badge_bg' => '#EDE9FE'],
            'Client Directory' => ['icon' => 'building-office', 'color' => '#166534', 'badge_bg' => '#DCFCE7'],
            'Team Management' => ['icon' => 'users', 'color' => '#86198F', 'badge_bg' => '#FAE8FF'],
            'Settings' => ['icon' => 'cog-6-tooth', 'color' => '#9A3412', 'badge_bg' => '#FED7AA'],
            'Auth' => ['icon' => 'shield-check', 'color' => '#334155', 'badge_bg' => '#F1F5F9'],
            default => ['icon' => 'clipboard-document-list', 'color' => '#64748B', 'badge_bg' => '#F1F5F9'],
        };
    }

    private function loadFootLabel(int $load, int $memberCount): string
    {
        if ($memberCount === 0) {
            return 'Belum ada anggota operasional aktif';
        }

        if ($load >= 85) return 'Overloaded capacity';
        if ($load >= 60) return 'Near capacity';
        return 'Optimal capacity';
    }

    private function roleLabel(?string $roleSlug): string
    {
        return match ($roleSlug) {
            'fullstack_dev' => 'Fullstack Developer',
            'uiux_designer', 'ui_ux' => 'UI/UX Senior',
            'sa_qa' => 'SA / QA',
            default => 'Operational Member',
        };
    }

    private function resolveMonth(string $value): Carbon
    {
        if (preg_match('/^\d{4}-\d{2}$/', $value) === 1) {
            try {
                return Carbon::createFromFormat('Y-m-d', $value . '-01')->startOfMonth();
            } catch (\Throwable) {
                // Use the current month when the query parameter is malformed.
            }
        }

        return Carbon::now()->startOfMonth();
    }

    private function monthOptions(Carbon $selectedMonth): array
    {
        return collect(range(0, 2))
            ->map(fn (int $offset) => Carbon::now()->startOfMonth()->subMonths($offset))
            ->push($selectedMonth)
            ->unique(fn (Carbon $month) => $month->format('Y-m'))
            ->sortByDesc(fn (Carbon $month) => $month->format('Y-m'))
            ->values()
            ->map(fn (Carbon $month) => [
                'value' => $month->format('Y-m'),
                'label' => $this->formatMonthYearId($month),
                'url' => route('executive.index', ['month' => $month->format('Y-m')]),
            ])
            ->all();
    }

    private function formatMonthYearId(Carbon $date): string
    {
        $month = self::ID_MONTHS[((int) $date->format('n')) - 1] ?? $date->format('M');

        return $month . ' ' . $date->format('Y');
    }

    private function firstName(string $fullName): string
    {
        $parts = preg_split('/\s+/', trim($fullName)) ?: [];
        return $parts[0] ?? $fullName;
    }

    private function initials(string $fullName): string
    {
        $parts = array_values(array_filter(preg_split('/\s+/', trim($fullName)) ?: []));
        if (empty($parts)) {
            return '?';
        }
        if (count($parts) === 1) {
            return mb_strtoupper(mb_substr($parts[0], 0, 2));
        }
        return mb_strtoupper(mb_substr($parts[0], 0, 1) . mb_substr($parts[1], 0, 1));
    }

    private function formatDueId(?Carbon $date): string
    {
        if (! $date) {
            return '-';
        }
        $month = self::ID_MONTHS[((int) $date->format('n')) - 1] ?? $date->format('M');
        return $date->format('d') . ' ' . $month;
    }
}
