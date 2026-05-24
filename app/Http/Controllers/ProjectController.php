<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Project;
use App\Models\ProjectModule;
use App\Models\ProjectTask;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProjectController extends Controller
{
    private const ID_MONTHS = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Ags', 'Sep', 'Okt', 'Nov', 'Des'];

    private const STATUS_UI = [
        'on-track'  => ['pill' => 'bg-emerald-100 text-emerald-700', 'dot' => 'bg-emerald-500', 'label' => 'Active'],
        'attention' => ['pill' => 'bg-amber-100 text-amber-700',     'dot' => 'bg-amber-500',   'label' => 'Needs Attention'],
        'critical'  => ['pill' => 'bg-rose-100 text-rose-700',       'dot' => 'bg-rose-500',    'label' => 'Critical'],
    ];

    private const DESC_MAP = [
        'AC' => 'Sales pipeline & customer dashboard internal untuk tim sales B2B PT Maju Jaya. Mencakup modul autentikasi, dashboard ringkas, dan project hub Kanban.',
        'BP' => 'Self-service portal untuk merchant onboarding & verifikasi dokumen end-to-end.',
        'GA' => 'API gateway terpusat untuk integrasi sistem internal & partner enterprise.',
        'DL' => 'Mobile + dashboard untuk monitoring armada & tracking pengiriman real-time.',
        'EX' => 'Internal tooling exchange data antar sistem legacy klien enterprise.',
        'ZN' => 'Customer-facing mobile app untuk loyalty program & point reward.',
        'OT' => 'Modul onboarding karyawan dengan e-signing & dokumen otomatis.',
        'KP' => 'Point-of-sale lightweight untuk SMB ritel dengan multi-cabang.',
    ];

    private const PROJECT_META = [
        'AC' => ['team' => ['AR', 'IK', 'YP'], 'team_more' => 1, 'tasks_done' => 21, 'tasks_total' => 42, 'mom' => 6],
        'BP' => ['team' => ['YP', 'AR'],       'team_more' => 0, 'tasks_done' => 8,  'tasks_total' => 28, 'mom' => 4],
        'GA' => ['team' => ['IK', 'AR', 'FA'], 'team_more' => 0, 'tasks_done' => 26, 'tasks_total' => 30, 'mom' => 8],
        'DL' => ['team' => ['FA', 'IK', 'YP'], 'team_more' => 2, 'tasks_done' => 15, 'tasks_total' => 48, 'mom' => 3],
        'EX' => ['team' => ['AR', 'YP'],       'team_more' => 0, 'tasks_done' => 2,  'tasks_total' => 18, 'mom' => 2],
        'ZN' => ['team' => ['YP', 'FA', 'IK'], 'team_more' => 0, 'tasks_done' => 19, 'tasks_total' => 34, 'mom' => 5],
        'OT' => ['team' => ['AR', 'YP'],       'team_more' => 0, 'tasks_done' => 24, 'tasks_total' => 24, 'mom' => 9],
        'KP' => ['team' => ['IK', 'AR'],       'team_more' => 1, 'tasks_done' => 30, 'tasks_total' => 38, 'mom' => 7],
    ];

    private const MODULE_STATUS_LABELS = [
        'pending_design' => 'Menunggu Desain',
        'approved'       => 'Disetujui',
        'waiting_dev'    => 'Menunggu Dev',
        'revision'       => 'Perlu Revisi',
    ];

    private const TASK_STATUS_LABELS = [
        'planned'     => 'Todo',
        'in_progress' => 'Doing',
        'review'      => 'Review',
        'done'        => 'Done',
    ];

    private const TASK_PRIORITY_LABELS = [
        'low'    => 'Low',
        'medium' => 'Medium',
        'high'   => 'High',
    ];

    private const OPERATIONAL_ROLES = ['sa_qa', 'uiux_designer', 'ui_ux', 'fullstack_dev'];

    public function index(Request $request)
    {
        $archiveScope = $request->query('archive', 'active');
        if (! in_array($archiveScope, ['active', 'archived', 'all'], true)) {
            $archiveScope = 'active';
        }

        $projects = Project::with(['client', 'lead'])
            ->when($archiveScope === 'active', fn ($query) => $query->whereNull('archived_at'))
            ->when($archiveScope === 'archived', fn ($query) => $query->whereNotNull('archived_at'))
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get()
            ->map(fn (Project $project) => $this->projectRow($project))
            ->all();

        return view('projects.index', [
            'title'    => 'Project Master',
            'projects' => $projects,
            'clients'  => Client::orderBy('name')->get(['id', 'name']),
            'archiveScope' => $archiveScope,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code'        => ['required', 'string', 'max:4', 'regex:/^[A-Za-z0-9]+$/', Rule::unique('projects', 'code')],
            'name'        => ['required', 'string', 'max:160'],
            'client_id'   => ['required', Rule::exists('clients', 'id')],
            'description' => ['nullable', 'string', 'max:2000'],
            'due_at'      => ['nullable', 'date'],
        ], [
            'code.required'      => 'Kode proyek wajib diisi.',
            'code.regex'         => 'Kode hanya boleh huruf dan angka.',
            'code.unique'        => 'Kode proyek sudah digunakan.',
            'name.required'      => 'Nama proyek wajib diisi.',
            'client_id.required' => 'Klien wajib dipilih.',
            'client_id.exists'   => 'Klien tidak valid.',
            'due_at.date'        => 'Due date tidak valid.',
        ]);

        $project = Project::create([
            'code'             => mb_strtoupper($validated['code']),
            'color'            => '#7C3AED',
            'name'             => $validated['name'],
            'description'      => $validated['description'] ?? null,
            'client_id'        => $validated['client_id'],
            'lead_user_id'     => null,
            'phase'            => 'Planning',
            'due_at'           => $validated['due_at'] ?? null,
            'progress'         => 0,
            'status'           => 'on-track',
            'ai_wbs_generated' => false,
            'is_featured'      => false,
        ]);

        AuditLogger::logCreated($project, 'Project Master', 'Membuat proyek <strong>' . e($project->name) . '</strong>');

        return redirect()
            ->route('projects.show', $project)
            ->with('status', 'Proyek "' . $project->name . '" berhasil dibuat.');
    }

    public function update(Request $request, Project $project)
    {
        $validated = $this->validateProject($request, $project);

        $original = $project->getOriginal();

        $project->update([
            'code'        => mb_strtoupper($validated['code']),
            'name'        => $validated['name'],
            'client_id'   => $validated['client_id'],
            'description' => $validated['description'] ?? null,
            'due_at'      => $validated['due_at'] ?? null,
        ]);

        AuditLogger::logUpdated($project, 'Project Master', 'Memperbarui proyek <strong>' . e($project->name) . '</strong>', $original);

        return redirect()
            ->back()
            ->with('status', 'Proyek "' . $project->name . '" berhasil diperbarui.');
    }

    public function archive(Project $project)
    {
        if (! $project->archived_at) {
            $project->forceFill(['archived_at' => now()])->save();
            AuditLogger::logArchived($project, 'Project Master', 'Mengarsipkan proyek <strong>' . e($project->name) . '</strong>');
        }

        return redirect()
            ->route('projects.index')
            ->with('status', 'Proyek "' . $project->name . '" berhasil diarsipkan.');
    }

    public function restore(Project $project)
    {
        if ($project->archived_at) {
            $project->forceFill(['archived_at' => null])->save();
            AuditLogger::logRestored($project, 'Project Master', 'Memulihkan proyek <strong>' . e($project->name) . '</strong>');
        }

        return redirect()
            ->route('projects.index')
            ->with('status', 'Proyek "' . $project->name . '" berhasil dipulihkan.');
    }

    public function show(Project $project)
    {
        $project->load([
            'client',
            'lead',
            'modules' => fn ($query) => $query->with('tasks')->orderBy('sort_order')->orderBy('id'),
            'tasks' => fn ($query) => $query->with(['module', 'assignee'])->orderBy('sort_order')->orderBy('id'),
        ]);

        $statusUi = self::STATUS_UI[$project->status] ?? self::STATUS_UI['on-track'];

        $leadName     = $project->lead?->name;
        $leadInitials = $leadName ? $this->initials($leadName) : '—';

        $desc = $project->description ?: (self::DESC_MAP[$project->code] ?? 'Belum ada deskripsi untuk proyek ini.');

        $due       = $project->due_at ? $this->formatDateId($project->due_at) : '—';
        $createdAt = $project->created_at ? $this->formatDateLongId($project->created_at) : '—';

        return view('projects.show', [
            'title'        => $project->name,
            'project'      => $project,
            'desc'         => $desc,
            'createdBy'    => 'Ahmad Rafiadly A.',
            'createdAt'    => $createdAt,
            'dueFormatted' => $due,
            'statusUi'     => $statusUi,
            'leadName'     => $leadName,
            'leadInitials' => $leadInitials,
            'useReferenceProjectData' => $this->usesReferenceProjectData($project),
            'clients'      => Client::orderBy('name')->get(['id', 'name']),
            'dbModules'    => $this->projectModuleRows($project),
            'dbKanban'     => $this->projectKanbanColumns($project),
            'dbStatusCards'=> $this->projectModuleStatusCards($project),
            'dbMetrics'    => $this->projectMetrics($project),
            'dbTabs'       => $this->projectTabs($project),
            'dbTaskTotal'  => $project->tasks->count(),
            'dbTaskDone'   => $project->tasks->where('status', 'done')->count(),
            'moduleStatusOptions' => self::MODULE_STATUS_LABELS,
            'taskStatusOptions' => self::TASK_STATUS_LABELS,
            'taskPriorityOptions' => self::TASK_PRIORITY_LABELS,
            'assigneeOptions' => $this->operationalUsers(),
        ]);
    }

    public function storeModule(Request $request, Project $project)
    {
        $this->ensureCanEditProjectDetail();

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:180'],
            'description' => ['nullable', 'string', 'max:2000'],
            'status' => ['required', Rule::in(array_keys(self::MODULE_STATUS_LABELS))],
            'estimate_hours' => ['nullable', 'integer', 'min:0', 'max:999'],
        ], [
            'title.required' => 'Judul modul wajib diisi.',
            'status.required' => 'Status modul wajib dipilih.',
            'status.in' => 'Status modul tidak valid.',
            'estimate_hours.integer' => 'Estimasi jam harus berupa angka.',
        ]);

        $module = $project->modules()->create([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'status' => $validated['status'],
            'estimate_hours' => (int) ($validated['estimate_hours'] ?? 0),
            'sort_order' => ((int) $project->modules()->max('sort_order')) + 1,
        ]);

        AuditLogger::log('wbs_module_created', 'Project Master', 'Menambah modul WBS <strong>' . e($module->title) . '</strong> pada proyek <strong>' . e($project->name) . '</strong>', $module);

        return redirect()
            ->to(route('projects.show', $project) . '#overview')
            ->with('status', 'Modul WBS "' . $module->title . '" berhasil dibuat.');
    }

    public function storeTask(Request $request, Project $project)
    {
        $this->ensureCanEditProjectDetail();
        $assigneeIds = $this->operationalUsers()->pluck('id')->all();

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:180'],
            'project_module_id' => ['nullable', Rule::exists('project_modules', 'id')->where('project_id', $project->id)],
            'assigned_to' => ['nullable', Rule::in($assigneeIds)],
            'status' => ['required', Rule::in(array_keys(self::TASK_STATUS_LABELS))],
            'priority' => ['required', Rule::in(array_keys(self::TASK_PRIORITY_LABELS))],
            'due_date' => ['nullable', 'date'],
            'estimate_hours' => ['nullable', 'integer', 'min:0', 'max:999'],
            'description' => ['nullable', 'string', 'max:2000'],
        ], [
            'title.required' => 'Judul task wajib diisi.',
            'project_module_id.exists' => 'Modul WBS tidak valid.',
            'assigned_to.exists' => 'Assignee tidak valid.',
            'status.required' => 'Status task wajib dipilih.',
            'status.in' => 'Status task tidak valid.',
            'priority.required' => 'Prioritas task wajib dipilih.',
            'priority.in' => 'Prioritas task tidak valid.',
            'due_date.date' => 'Due date task tidak valid.',
            'estimate_hours.integer' => 'Estimasi jam harus berupa angka.',
        ]);

        $task = $project->tasks()->create([
            'title' => $validated['title'],
            'project_module_id' => $validated['project_module_id'] ?? null,
            'assigned_to' => $validated['assigned_to'] ?? null,
            'status' => $validated['status'],
            'priority' => $validated['priority'],
            'due_date' => $validated['due_date'] ?? null,
            'estimate_hours' => (int) ($validated['estimate_hours'] ?? 0),
            'description' => $validated['description'] ?? null,
            'sort_order' => ((int) $project->tasks()->max('sort_order')) + 1,
        ]);

        AuditLogger::log('task_created', 'Project Master', 'Menambah task <strong>' . e($task->title) . '</strong> pada proyek <strong>' . e($project->name) . '</strong>', $task);

        return redirect()
            ->to(route('projects.show', $project) . '#workspace')
            ->with('status', 'Task "' . $task->title . '" berhasil dibuat.');
    }

    public function updateTaskStatus(Request $request, Project $project, ProjectTask $task)
    {
        $this->ensureCanEditProjectDetail();
        abort_unless($task->project_id === $project->id, 404);

        $validated = $request->validate([
            'status' => ['required', Rule::in(array_keys(self::TASK_STATUS_LABELS))],
        ], [
            'status.required' => 'Status task wajib dipilih.',
            'status.in' => 'Status task tidak valid.',
        ]);

        $original = $task->getOriginal();
        $task->update(['status' => $validated['status']]);

        AuditLogger::log('task_status_changed', 'Project Master', 'Mengubah status task <strong>' . e($task->title) . '</strong> menjadi <strong>' . e(self::TASK_STATUS_LABELS[$task->status]) . '</strong>', $task, ['status' => $original['status'] ?? null], ['status' => $task->status]);

        return redirect()
            ->to(route('projects.show', $project) . '#workspace')
            ->with('status', 'Status task "' . $task->title . '" berhasil diperbarui.');
    }

    private function validateProject(Request $request, ?Project $project = null): array
    {
        return $request->validate([
            'code'        => ['required', 'string', 'max:4', 'regex:/^[A-Za-z0-9]+$/', Rule::unique('projects', 'code')->ignore($project?->id)],
            'name'        => ['required', 'string', 'max:160'],
            'client_id'   => ['required', Rule::exists('clients', 'id')],
            'description' => ['nullable', 'string', 'max:2000'],
            'due_at'      => ['nullable', 'date'],
        ], [
            'code.required'      => 'Kode proyek wajib diisi.',
            'code.regex'         => 'Kode hanya boleh huruf dan angka.',
            'code.unique'        => 'Kode proyek sudah digunakan.',
            'name.required'      => 'Nama proyek wajib diisi.',
            'client_id.required' => 'Klien wajib dipilih.',
            'client_id.exists'   => 'Klien tidak valid.',
            'due_at.date'        => 'Due date tidak valid.',
        ]);
    }

    private function usesReferenceProjectData(Project $project): bool
    {
        return array_key_exists($project->code, self::PROJECT_META);
    }

    private function ensureCanEditProjectDetail(): void
    {
        abort_if(auth()->user()?->roles?->first()?->name === 'ceo_pm', 403);
    }

    private function projectModuleRows(Project $project): array
    {
        return $project->modules->map(function (ProjectModule $module) {
            $taskTotal = $module->tasks->count();
            $taskDone = $module->tasks->where('status', 'done')->count();

            return [
                'id' => $module->id,
                'name' => $module->title,
                'description' => $module->description,
                'tasks_done' => $taskDone,
                'tasks_total' => $taskTotal,
                'hours' => (int) $module->estimate_hours,
                'status' => self::MODULE_STATUS_LABELS[$module->status] ?? $module->status,
                'status_key' => $module->status,
            ];
        })->all();
    }

    private function projectModuleStatusCards(Project $project): array
    {
        $counts = $project->modules->countBy('status');

        return [
            ['count' => (int) ($counts['approved'] ?? 0),       'label' => 'Disetujui',       'bg' => '#ECFDF5', 'border' => '#A7F3D0', 'value' => '#047857', 'caption' => '#059669'],
            ['count' => (int) ($counts['waiting_dev'] ?? 0),    'label' => 'Menunggu Dev',    'bg' => '#EFF6FF', 'border' => '#BFDBFE', 'value' => '#1D4ED8', 'caption' => '#2563EB'],
            ['count' => (int) ($counts['pending_design'] ?? 0), 'label' => 'Menunggu Design', 'bg' => '#FFFBEB', 'border' => '#FDE68A', 'value' => '#B45309', 'caption' => '#D97706'],
            ['count' => (int) ($counts['revision'] ?? 0),       'label' => 'Perlu Revisi',    'bg' => '#FFF1F2', 'border' => '#FECDD3', 'value' => '#BE123C', 'caption' => '#E11D48'],
        ];
    }

    private function projectMetrics(Project $project): array
    {
        $moduleTotal = $project->modules->count();
        $moduleApproved = $project->modules->where('status', 'approved')->count();
        $taskTotal = $project->tasks->count();
        $taskDone = $project->tasks->where('status', 'done')->count();
        $taskOpen = max(0, $taskTotal - $taskDone);

        return [
            ['code' => 'MOD',  'value' => $moduleApproved . '/' . $moduleTotal, 'label' => 'Modul Disetujui',    'color' => '#3B82F6', 'progress' => $this->percentage($moduleApproved, $moduleTotal), 'sub' => $moduleTotal > 0 ? $moduleTotal . ' modul terdefinisi' : 'Belum ada modul'],
            ['code' => 'TASK', 'value' => $taskDone . '/' . $taskTotal,         'label' => 'Task Selesai',       'color' => '#7C3AED', 'progress' => $this->percentage($taskDone, $taskTotal),         'sub' => $taskDone . ' Selesai • ' . $taskOpen . ' Open'],
            ['code' => 'MOM',  'value' => '0/0',                                'label' => 'MoM AI Rapi',        'color' => '#10B981', 'progress' => 0,                                           'sub' => 'Belum ada MoM'],
            ['code' => 'QC',   'value' => '0%',                                 'label' => 'Tingkat Lulus Test', 'color' => '#F59E0B', 'progress' => 0,                                           'sub' => 'Pass Rate'],
        ];
    }

    private function projectTabs(Project $project): array
    {
        return [
            ['id' => 'overview',   'label' => 'Overview',         'count' => $project->modules->count()],
            ['id' => 'workspace',  'label' => 'Kanban Workspace', 'count' => $project->tasks->count()],
            ['id' => 'aiplanning', 'label' => 'AI Planning',      'count' => 0],
            ['id' => 'qc',         'label' => 'Quality Control',  'count' => 0],
        ];
    }

    private function projectKanbanColumns(Project $project): array
    {
        $columnDefs = [
            ['id' => 'todo',    'status' => 'planned',     'label' => 'Todo',   'color' => '#475569', 'bg' => '#F1F5F9'],
            ['id' => 'doing',   'status' => 'in_progress', 'label' => 'Doing',  'color' => '#2563EB', 'bg' => '#DBEAFE'],
            ['id' => 'testing', 'status' => 'review',      'label' => 'Review', 'color' => '#D97706', 'bg' => '#FEF3C7'],
            ['id' => 'done',    'status' => 'done',        'label' => 'Done',   'color' => '#059669', 'bg' => '#D1FAE5'],
        ];

        return collect($columnDefs)->map(function (array $column) use ($project) {
            $tasks = $project->tasks
                ->where('status', $column['status'])
                ->map(fn (ProjectTask $task) => $this->projectTaskRow($task))
                ->values()
                ->all();

            return $column + ['tasks' => $tasks];
        })->all();
    }

    private function projectTaskRow(ProjectTask $task): array
    {
        return [
            'id' => $task->id,
            'module' => $task->module?->title ?? 'Tanpa Modul',
            'priority' => self::TASK_PRIORITY_LABELS[$task->priority] ?? $task->priority,
            'priority_key' => $task->priority,
            'title' => $task->title,
            'description' => $task->description,
            'assignee' => $task->assignee?->name ?? 'Belum Ditugaskan',
            'status' => $task->status,
            'due' => $task->due_date ? $this->formatDateId($task->due_date) : null,
            'hours' => (int) $task->estimate_hours,
        ];
    }

    private function operationalUsers()
    {
        return User::query()
            ->with('roles')
            ->whereNull('archived_at')
            ->whereDoesntHave('roles', fn ($query) => $query->where('name', 'ceo_pm'))
            ->whereHas('roles', fn ($query) => $query->whereIn('name', self::OPERATIONAL_ROLES))
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    private function percentage(int $done, int $total): int
    {
        return $total > 0 ? (int) round($done / $total * 100) : 0;
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

    private function formatDateId(\Illuminate\Support\Carbon $date): string
    {
        $month = self::ID_MONTHS[((int) $date->format('n')) - 1] ?? $date->format('M');
        return $date->format('d') . ' ' . $month;
    }

    private function formatDateLongId(\Illuminate\Support\Carbon $date): string
    {
        $month = self::ID_MONTHS[((int) $date->format('n')) - 1] ?? $date->format('M');
        return $date->format('d') . ' ' . $month . ' ' . $date->format('Y');
    }

    private function projectRow(Project $project): array
    {
        $meta = self::PROJECT_META[$project->code] ?? null;
        $team = $meta['team'] ?? ($project->lead ? [$this->initials($project->lead->name)] : []);

        return [
            'id'           => $project->id,
            'code'         => $project->code,
            'color'        => $project->color,
            'name'         => $project->name,
            'client'       => $project->client?->name ?? '—',
            'client_id'    => $project->client_id,
            'desc'         => $project->description ?: (self::DESC_MAP[$project->code] ?? 'Belum ada deskripsi untuk proyek ini.'),
            'phase'        => $project->phase,
            'phase_key'    => $this->phaseKey($project->phase),
            'due'          => $project->due_at ? $this->formatDateId($project->due_at) : '—',
            'due_at'       => $project->due_at?->format('Y-m-d'),
            'progress'     => (int) $project->progress,
            'status'       => $project->status,
            'status_label' => $this->statusLabel($project->status),
            'archived'     => (bool) $project->archived_at,
            'team'         => $team,
            'team_more'    => (int) ($meta['team_more'] ?? 0),
            'tasks_done'   => (int) ($meta['tasks_done'] ?? 0),
            'tasks_total'  => (int) ($meta['tasks_total'] ?? 0),
            'mom'          => (int) ($meta['mom'] ?? 0),
            'ai_flag'      => (bool) $project->ai_wbs_generated,
        ];
    }

    private function phaseKey(string $phase): string
    {
        return match (mb_strtolower($phase)) {
            'design' => 'design',
            'development' => 'dev',
            'qa' => 'qa',
            'done' => 'done',
            default => 'planning',
        };
    }

    private function statusLabel(string $status): string
    {
        return match ($status) {
            'attention' => 'Needs Attention',
            'critical' => 'Critical',
            default => 'On Track',
        };
    }
}
