<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Project;
use App\Models\TeamWorkload;

class ExecutiveController extends Controller
{
    private const ID_MONTHS = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Ags', 'Sep', 'Okt', 'Nov', 'Des'];

    private const STATUS_LABEL = [
        'on-track'  => 'On Track',
        'attention' => 'Needs Attention',
        'critical'  => 'Critical',
    ];

    public function index()
    {
        $totalProjects = Project::count();
        $aiCount       = Project::where('ai_wbs_generated', true)->count();
        $aiPct         = $totalProjects > 0 ? (int) round($aiCount / $totalProjects * 100) : 0;
        $avgLoad       = (int) round(TeamWorkload::avg('load_pct') ?? 0);
        $totalClients  = Client::count();

        $metrics = [
            ['key' => 'projects', 'icon' => 'folder',          'label' => 'Total Projects',     'value' => (string) $totalProjects, 'foot' => '+12 this quarter',           'foot_icon' => 'arrow-trending-up',  'foot_color' => 'text-emerald-600 font-medium', 'progress' => null],
            ['key' => 'ai',       'icon' => 'cpu-chip',        'label' => 'AI Task Automation', 'value' => $aiPct . '%',            'foot' => 'WBS berhasil di-generate AI', 'foot_icon' => 'bolt',                'foot_color' => 'text-violet-600 font-medium',  'progress' => null],
            ['key' => 'workload', 'icon' => 'users',           'label' => 'Team Workload',      'value' => $avgLoad . '%',          'foot' => $this->loadFootLabel($avgLoad),'foot_icon' => null,                  'foot_color' => null,                            'progress' => $avgLoad],
            ['key' => 'clients',  'icon' => 'building-office', 'label' => 'Active Clients',     'value' => (string) $totalClients,  'foot' => '3 renewals upcoming',        'foot_icon' => 'information-circle',  'foot_color' => 'text-slate-500',               'progress' => null],
        ];

        $featured = Project::with(['client', 'lead'])
            ->where('is_featured', true)
            ->orderBy('id')
            ->get();

        $projects = $featured->map(function (Project $p) {
            $leadName     = $p->lead?->name;
            $leadInitials = $this->initials($leadName ?? '?');

            return [
                'id'            => $p->id,
                'code'          => $p->code,
                'color'         => $p->color,
                'name'          => $p->name,
                'client'        => $p->client?->name ?? '—',
                'lead'          => $leadName ? $this->firstName($leadName) : '—',
                'lead_initials' => $leadInitials,
                'phase'         => $p->phase,
                'due'           => $this->formatDueId($p->due_at),
                'progress'      => (int) $p->progress,
                'status'        => $p->status,
                'status_label'  => self::STATUS_LABEL[$p->status] ?? $p->status,
            ];
        })->all();

        $statusCounts = Project::selectRaw('status, COUNT(*) as c')
            ->groupBy('status')
            ->pluck('c', 'status');

        $projectStats = [
            'onTrack'   => (int) ($statusCounts['on-track']  ?? 0),
            'attention' => (int) ($statusCounts['attention'] ?? 0),
            'critical'  => (int) ($statusCounts['critical']  ?? 0),
        ];

        $roleLabel = [
            'fullstack_dev' => 'Fullstack Developer',
            'uiux_designer' => 'UI/UX Senior',
            'sa_qa'         => 'SA / QA',
            'ceo_pm'        => 'CEO / PM',
        ];

        $teamLoadRows = TeamWorkload::with(['user.roles'])
            ->orderByDesc('load_pct')
            ->get();

        $teamLoad = $teamLoadRows->map(function (TeamWorkload $tw) use ($roleLabel) {
            $user     = $tw->user;
            $roleSlug = $user?->roles?->first()?->name;

            return [
                'name'     => $user?->name ?? '—',
                'initials' => $this->initials($user?->name ?? '?'),
                'role'     => $roleLabel[$roleSlug] ?? ($roleSlug ?? '—'),
                'load'     => (int) $tw->load_pct,
                'tasks'    => (int) $tw->active_tasks,
                'sim'      => (bool) $tw->is_sim,
            ];
        })->all();

        $poolDefs = [
            ['label' => 'Fullstack Pool', 'role' => 'fullstack_dev'],
            ['label' => 'UI/UX Senior',   'role' => 'uiux_designer'],
            ['label' => 'SA / QA',        'role' => 'sa_qa'],
        ];

        $pools = collect($poolDefs)->map(function ($def) use ($teamLoadRows) {
            $rows  = $teamLoadRows->filter(fn (TeamWorkload $tw) => $tw->user?->roles?->first()?->name === $def['role']);
            $count = $rows->count();
            $avg   = $count > 0 ? (int) round($rows->avg('load_pct')) : 0;

            return [
                'label' => $def['label'],
                'avg'   => $avg,
                'count' => $count,
            ];
        })->all();

        return view('executive.index', [
            'title'        => 'Executive Monitor',
            'metrics'      => $metrics,
            'projects'     => $projects,
            'projectStats' => $projectStats,
            'teamLoad'     => $teamLoad,
            'pools'        => $pools,
        ]);
    }

    private function loadFootLabel(int $load): string
    {
        if ($load > 85)  return 'Critical capacity';
        if ($load >= 70) return 'Near capacity';
        return 'Optimal capacity';
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

    private function formatDueId(?\Illuminate\Support\Carbon $date): string
    {
        if (! $date) {
            return '—';
        }
        $month = self::ID_MONTHS[((int) $date->format('n')) - 1] ?? $date->format('M');
        return $date->format('d') . ' ' . $month;
    }
}
