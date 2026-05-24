<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Project;
use App\Models\ProjectTask;
use App\Models\TeamAssignment;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

/**
 * Operational Dashboard.
 *
 * - CEO/PM is routed to the Executive Monitor (their canonical landing).
 * - SA/QA, UI/UX Designer, Fullstack Dev all render the operational view
 *   with role-aware copy + quick actions and DB-backed metrics.
 */
class DashboardController extends Controller
{
    private const OPEN_TASK_STATUSES = ['planned', 'in_progress', 'review'];
    private const DONE_STATUSES      = ['done', 'completed'];

    public function index(Request $request): View|RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $user->load('roles');

        $role = $user->roles->first()?->name;

        if ($role === 'ceo_pm') {
            return redirect()->route('executive.index');
        }

        $assignedProjectIds = TeamAssignment::query()
            ->where('user_id', $user->id)
            ->pluck('project_id')
            ->unique()
            ->values();

        $assignedProjects = Project::query()
            ->whereIn('id', $assignedProjectIds)
            ->whereNull('archived_at')
            ->with('client')
            ->withCount([
                'modules',
                'tasks',
                'tasks as tasks_done_count' => fn ($q) => $q->whereIn('status', self::DONE_STATUSES),
            ])
            ->orderByDesc('updated_at')
            ->get();

        $assignedTasks = ProjectTask::query()
            ->where('assigned_to', $user->id)
            ->with('project:id,code,color,name')
            ->orderByRaw("FIELD(status, 'in_progress', 'review', 'planned', 'done')")
            ->orderByDesc('updated_at')
            ->get();

        $todayStart = Carbon::now()->startOfDay();

        $todayActivityCount = AuditLog::query()
            ->where('user_id', $user->id)
            ->where('created_at', '>=', $todayStart)
            ->count();

        $recentActivities = AuditLog::query()
            ->where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit(8)
            ->get();

        $rolePresets = $this->rolePresets($role, $user, $assignedProjects->first());

        return view('dashboard.operational', [
            'title'              => 'Dashboard',
            'currentUser'        => $user,
            'role'               => $role,
            'roleLabel'          => $rolePresets['roleLabel'],
            'firstName'          => $rolePresets['firstName'],
            'initials'           => $rolePresets['initials'],
            'greetingLabel'      => $this->greetingLabel(),
            'todayLabel'         => Carbon::now()->translatedFormat('l, d F Y'),
            'subtitle'           => $rolePresets['subtitle'],
            'focusLine'          => $rolePresets['focusLine'],
            'projects'           => $assignedProjects,
            'tasks'              => $assignedTasks,
            'metrics'            => $this->metrics($assignedTasks, $assignedProjects, $todayActivityCount, $rolePresets['metricLabels']),
            'quickActions'       => $rolePresets['quickActions'],
            'insightTitle'       => $rolePresets['insightTitle'],
            'insightSubtitle'    => $rolePresets['insightSubtitle'],
            'insightItems'       => $this->insightItems($role, $assignedTasks, $assignedProjects),
            'recentActivities'   => $recentActivities,
        ]);
    }

    private function metrics($tasks, $projects, int $todayActivityCount, array $labels): array
    {
        $byStatus = $tasks->countBy('status');

        $assignedCount    = $projects->count();
        $openCount        = (int) ($byStatus->get('planned', 0) + $byStatus->get('in_progress', 0) + $byStatus->get('review', 0));
        $inProgressCount  = (int) $byStatus->get('in_progress', 0);
        $reviewCount      = (int) $byStatus->get('review', 0);
        $doneCount        = (int) ($byStatus->get('done', 0) + $byStatus->get('completed', 0));
        $blockerCount     = (int) $tasks->where('priority', 'high')->whereIn('status', self::OPEN_TASK_STATUSES)->count();

        return [
            [
                'key'    => 'projects',
                'label'  => $labels['projects'] ?? 'Proyek Ditugaskan',
                'value'  => $assignedCount,
                'sub'    => $assignedCount === 0 ? 'Belum ada penugasan' : 'Aktif minggu ini',
                'accent' => '#7C3AED', 'tileBg' => '#EDE9FE', 'tileFg' => '#6D28D9', 'icon' => 'folder-open',
            ],
            [
                'key'    => 'open',
                'label'  => $labels['open'] ?? 'Task Terbuka',
                'value'  => $openCount,
                'sub'    => $blockerCount > 0 ? $blockerCount . ' high priority' : 'Tidak ada blocker',
                'accent' => '#3B82F6', 'tileBg' => '#DBEAFE', 'tileFg' => '#1E40AF', 'icon' => 'list-bullet',
            ],
            [
                'key'    => 'progress',
                'label'  => $labels['progress'] ?? 'Sedang Dikerjakan',
                'value'  => $inProgressCount + $reviewCount,
                'sub'    => $reviewCount > 0 ? $reviewCount . ' menunggu review' : 'Belum ada item review',
                'accent' => '#F59E0B', 'tileBg' => '#FEF3C7', 'tileFg' => '#92400E', 'icon' => 'play-circle',
            ],
            [
                'key'    => 'activity',
                'label'  => 'Aktivitas Hari Ini',
                'value'  => $todayActivityCount,
                'sub'    => 'Tercatat di audit log Anda',
                'accent' => '#10B981', 'tileBg' => '#DCFCE7', 'tileFg' => '#166534', 'icon' => 'bolt',
            ],
        ];
    }

    private function insightItems(?string $role, $tasks, $projects): array
    {
        $items = [];

        $highOpen = $tasks->whereIn('status', self::OPEN_TASK_STATUSES)->where('priority', 'high');
        if ($highOpen->isNotEmpty()) {
            $items[] = [
                'tone'   => 'amber',
                'icon'   => 'exclamation-triangle',
                'text'   => $highOpen->count() . ' task prioritas tinggi masih terbuka. Selesaikan dulu untuk menjaga ritme sprint.',
            ];
        }

        $review = $tasks->where('status', 'review');
        if ($review->isNotEmpty()) {
            $items[] = [
                'tone'   => 'violet',
                'icon'   => 'eye',
                'text'   => $review->count() . ' task menunggu review. Cek tab Workspace pada proyek terkait.',
            ];
        }

        $overdue = $tasks->filter(fn ($t) => $t->due_date && $t->due_date->isPast() && ! in_array($t->status, self::DONE_STATUSES, true));
        if ($overdue->isNotEmpty()) {
            $items[] = [
                'tone'   => 'rose',
                'icon'   => 'clock',
                'text'   => $overdue->count() . ' task melewati due date. Update status atau geser jadwal.',
            ];
        }

        if (empty($items)) {
            $items[] = [
                'tone'   => 'slate',
                'icon'   => 'sparkles',
                'text'   => $projects->isEmpty()
                    ? 'Belum ada penugasan aktif. Hubungi PM Anda jika ini terasa kosong.'
                    : 'Tidak ada catatan mendesak. Lanjutkan eksekusi sesuai antrian Anda.',
            ];
        }

        return $items;
    }

    private function rolePresets(?string $role, User $user, ?Project $primaryProject): array
    {
        $nameParts = preg_split('/\s+/', trim($user->name)) ?: [$user->name];
        $firstName = $nameParts[0] ?? 'Tim';
        $initials  = mb_strtoupper(mb_substr($firstName, 0, 1) . (isset($nameParts[1]) ? mb_substr($nameParts[1], 0, 1) : mb_substr($firstName, 1, 1)));

        $projectsRoute = route('projects.index');
        $auditUrl      = route('audit.index') . '?actor=' . urlencode($user->name);
        $primaryShow   = $primaryProject
            ? route('projects.show', $primaryProject)
            : $projectsRoute;
        $primaryKanban = $primaryProject
            ? route('projects.show', $primaryProject) . '#workspace'
            : $projectsRoute;
        $primaryQc     = $primaryProject
            ? route('projects.show', $primaryProject) . '#qc'
            : $projectsRoute;
        $primaryAi     = $primaryProject
            ? route('projects.show', $primaryProject) . '#aiplanning'
            : $projectsRoute;

        return match ($role) {
            'sa_qa' => [
                'roleLabel'        => 'SA / QA',
                'firstName'        => $firstName,
                'initials'         => $initials,
                'subtitle'         => 'Pantau task dan review pekerjaan hari ini untuk menjaga ritme sprint.',
                'focusLine'        => $primaryProject ? 'Buka Workspace ' . $primaryProject->name : 'Belum ada proyek di antrian Anda.',
                'insightTitle'     => 'Priority Insight',
                'insightSubtitle'  => 'Ringkasan prioritas dari sistem',
                'metricLabels'     => [
                    'projects' => 'Proyek Ditugaskan',
                    'open'     => 'Task Terbuka',
                    'progress' => 'In Progress / Review',
                ],
                'quickActions'     => [
                    ['label' => 'Buka Workspace', 'sub' => 'Kanban + task aktif',          'icon' => 'squares-2x2',   'tileBg' => '#DBEAFE', 'tileFg' => '#1E40AF', 'href' => $primaryKanban],
                    ['label' => 'Buka Project',   'sub' => 'Detail proyek terbaru Anda',   'icon' => 'rectangle-stack','tileBg' => '#FEF3C7', 'tileFg' => '#92400E', 'href' => $primaryShow],
                    ['label' => 'Activity Log',   'sub' => 'Riwayat aktivitas Anda',       'icon' => 'clock',         'tileBg' => '#EDE9FE', 'tileFg' => '#6D28D9', 'href' => $auditUrl],
                    ['label' => 'Projects',       'sub' => 'Semua proyek yang ditugaskan',  'icon' => 'list-bullet',  'tileBg' => '#FCE7F3', 'tileFg' => '#9D174D', 'href' => $projectsRoute],
                ],
            ],
            'uiux_designer', 'ui_ux' => [
                'roleLabel'        => 'UI/UX Designer',
                'firstName'        => $firstName,
                'initials'         => $initials,
                'subtitle'         => 'Lanjutkan revisi desain dan upload mockup terbaru untuk tim eksekusi.',
                'focusLine'        => $primaryProject ? 'Revisi desain ' . $primaryProject->name : 'Belum ada proyek yang menunggu desain.',
                'insightTitle'     => 'Priority Insight',
                'insightSubtitle'  => 'Catatan dari sistem untuk Anda',
                'metricLabels'     => [
                    'projects' => 'Proyek Ditugaskan',
                    'open'     => 'Revisi & Mockup Terbuka',
                    'progress' => 'Sedang Dikerjakan',
                ],
                'quickActions'     => [
                    ['label' => 'Upload Design',  'sub' => 'Tambah mockup ke proyek',       'icon' => 'photo',         'tileBg' => '#FCE7F3', 'tileFg' => '#9D174D', 'href' => $primaryShow, 'disabled' => true, 'tooltip' => 'Upload Design akan tersedia setelah modul desain DB siap.'],
                    ['label' => 'Lihat Revisi',   'sub' => 'Daftar revisi yang menunggu',   'icon' => 'pencil-square', 'tileBg' => '#EDE9FE', 'tileFg' => '#6D28D9', 'href' => $primaryShow],
                    ['label' => 'Projects',       'sub' => 'Semua proyek yang ditugaskan',  'icon' => 'rectangle-stack','tileBg' => '#DBEAFE', 'tileFg' => '#1E40AF', 'href' => $projectsRoute],
                    ['label' => 'Activity Log',   'sub' => 'Riwayat aktivitas Anda',        'icon' => 'clock',         'tileBg' => '#FEF3C7', 'tileFg' => '#92400E', 'href' => $auditUrl],
                ],
            ],
            'fullstack_dev' => [
                'roleLabel'        => 'Fullstack Developer',
                'firstName'        => $firstName,
                'initials'         => $initials,
                'subtitle'         => 'Selesaikan task aktif, geser Kanban, dan tandai blocker sedini mungkin.',
                'focusLine'        => $primaryProject ? 'Eksekusi modul ' . $primaryProject->name : 'Belum ada proyek yang menunggu eksekusi.',
                'insightTitle'     => 'Priority Insight',
                'insightSubtitle'  => 'Catatan dari sistem untuk Anda',
                'metricLabels'     => [
                    'projects' => 'Proyek Ditugaskan',
                    'open'     => 'Task Terbuka',
                    'progress' => 'In Progress / Review',
                ],
                'quickActions'     => [
                    ['label' => 'Buka Kanban',    'sub' => 'Workspace task aktif',          'icon' => 'squares-2x2',   'tileBg' => '#DBEAFE', 'tileFg' => '#1E40AF', 'href' => $primaryKanban],
                    ['label' => 'Task Saya',      'sub' => 'Lihat semua task Anda',         'icon' => 'list-bullet',   'tileBg' => '#EDE9FE', 'tileFg' => '#6D28D9', 'href' => $primaryShow],
                    ['label' => 'Projects',       'sub' => 'Semua proyek yang ditugaskan',  'icon' => 'rectangle-stack','tileBg' => '#DCFCE7', 'tileFg' => '#166534', 'href' => $projectsRoute],
                    ['label' => 'Activity Log',   'sub' => 'Riwayat aktivitas Anda',        'icon' => 'clock',         'tileBg' => '#FEF3C7', 'tileFg' => '#92400E', 'href' => $auditUrl],
                ],
            ],
            default => [
                'roleLabel'        => 'Tim Avatech',
                'firstName'        => $firstName,
                'initials'         => $initials,
                'subtitle'         => 'Lihat penugasan dan aktivitas Anda hari ini.',
                'focusLine'        => 'Selamat datang di Smart-PMIS.',
                'insightTitle'     => 'System Insight',
                'insightSubtitle'  => 'Catatan dari sistem',
                'metricLabels'     => [],
                'quickActions'     => [
                    ['label' => 'Projects',     'sub' => 'Semua proyek yang ditugaskan', 'icon' => 'rectangle-stack', 'tileBg' => '#EDE9FE', 'tileFg' => '#6D28D9', 'href' => $projectsRoute],
                    ['label' => 'Activity Log', 'sub' => 'Riwayat aktivitas Anda',       'icon' => 'clock',           'tileBg' => '#FEF3C7', 'tileFg' => '#92400E', 'href' => $auditUrl],
                ],
            ],
        };
    }

    private function greetingLabel(): string
    {
        $hour = (int) Carbon::now()->format('G');
        return match (true) {
            $hour < 11 => 'Selamat Pagi',
            $hour < 15 => 'Selamat Siang',
            $hour < 18 => 'Selamat Sore',
            default    => 'Selamat Malam',
        };
    }
}
