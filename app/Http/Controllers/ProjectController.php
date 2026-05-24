<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Project;
use App\Models\ProjectMom;
use App\Models\ProjectModule;
use App\Models\ProjectTask;
use App\Models\User;
use App\Services\AiPlanner;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
        $user = $request->user();
        $role = $user?->roles()->first()?->name;

        // Operational users see the role-aware Projects page (assigned-only).
        if (in_array($role, self::OPERATIONAL_ROLES, true)) {
            return $this->operationalIndex($user, $role);
        }

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

    /**
     * Operational variant of the projects list. Filters to projects the
     * current user is actually assigned to via team_assignments, hides
     * create/archive controls, and surfaces a role-specific primary action.
     */
    private function operationalIndex(\App\Models\User $user, string $role)
    {
        $assignedProjectIds = \App\Models\TeamAssignment::query()
            ->where('user_id', $user->id)
            ->pluck('project_id')
            ->unique()
            ->values();

        $projects = Project::with('client')
            ->whereIn('id', $assignedProjectIds)
            ->whereNull('archived_at')
            ->withCount([
                'tasks',
                'tasks as tasks_done_count' => fn ($q) => $q->whereIn('status', ['done', 'completed']),
                'tasks as tasks_mine_count' => fn ($q) => $q->where('assigned_to', $user->id),
            ])
            ->orderByDesc('updated_at')
            ->get();

        /* Primary action per role. We deliberately avoid copy that implies
         * features not yet wired up (QC DB flow, design upload). Every label
         * here links to a real Project Detail tab anchor. */
        $actionPresets = match ($role) {
            'sa_qa' => [
                'label'  => 'Buka Workspace',
                'icon'   => 'squares-2x2',
                'anchor' => '#workspace',
            ],
            'uiux_designer', 'ui_ux' => [
                'label'  => 'Buka Project',
                'icon'   => 'arrow-up-right',
                'anchor' => '',
            ],
            default => [ /* fullstack_dev */
                'label'  => 'Buka Kanban',
                'icon'   => 'squares-2x2',
                'anchor' => '#workspace',
            ],
        };

        return view('projects.operational-index', [
            'title'         => 'Projects',
            'projects'      => $projects,
            'actionPresets' => $actionPresets,
            'role'          => $role,
            'currentUser'   => $user,
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
        if ($redirect = $this->ensureOperationalCanAccessProject($project)) {
            return $redirect;
        }

        $project->load([
            'client',
            'lead',
            'modules' => fn ($query) => $query->with('tasks')->orderBy('sort_order')->orderBy('id'),
            'tasks' => fn ($query) => $query->with(['module', 'assignee'])->orderBy('sort_order')->orderBy('id'),
            'moms' => fn ($query) => $query->with('creator:id,name')->orderByDesc('meeting_date')->orderByDesc('id'),
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
            'dbMoms'       => $this->projectMomRows($project),
            'aiReady'      => AiPlanner::isConfigured(),
            'aiProvider'   => AiPlanner::providerLabel(),
            'moduleStatusOptions' => self::MODULE_STATUS_LABELS,
            'taskStatusOptions' => self::TASK_STATUS_LABELS,
            'taskPriorityOptions' => self::TASK_PRIORITY_LABELS,
            'assigneeOptions' => $this->operationalUsers(),
        ]);
    }

    public function storeModule(Request $request, Project $project)
    {
        $this->ensureCanEditProjectDetail();
        if ($redirect = $this->ensureOperationalCanAccessProject($project)) {
            return $redirect;
        }

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
        if ($redirect = $this->ensureOperationalCanAccessProject($project)) {
            return $redirect;
        }
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
        if ($redirect = $this->ensureOperationalCanAccessProject($project)) {
            return $redirect;
        }
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

    public function storeMom(Request $request, Project $project)
    {
        $this->ensureCanEditProjectDetail();
        if ($redirect = $this->ensureOperationalCanAccessProject($project)) {
            return $redirect;
        }

        $validated = $request->validate([
            'meeting_date' => ['required', 'date'],
            'notes'        => ['required', 'string', 'max:8000'],
            'summary'      => ['nullable', 'string', 'max:4000'],
        ], [
            'meeting_date.required' => 'Tanggal rapat wajib diisi.',
            'meeting_date.date'     => 'Tanggal rapat tidak valid.',
            'notes.required'        => 'Notulensi wajib diisi.',
            'notes.max'             => 'Notulensi terlalu panjang.',
        ]);

        $mom = $project->moms()->create([
            'created_by'   => $request->user()?->id,
            'meeting_date' => $validated['meeting_date'],
            'notes'        => $validated['notes'],
            'summary'      => $validated['summary'] ?? null,
            'status'       => 'draft',
        ]);

        AuditLogger::log(
            'mom_created',
            'Project Master',
            'Menambah MoM <strong>' . e($mom->meeting_date->format('d M Y')) . '</strong> pada proyek <strong>' . e($project->name) . '</strong>',
            $mom,
        );

        return redirect()
            ->to(route('projects.show', $project) . '#aiplanning')
            ->with('status', 'MoM untuk ' . $mom->meeting_date->format('d M Y') . ' berhasil disimpan.');
    }

    public function generateWbsFromMom(Request $request, Project $project)
    {
        $this->ensureCanEditProjectDetail();
        if ($redirect = $this->ensureOperationalCanAccessProject($project)) {
            return $redirect;
        }

        $backUrl = route('projects.show', $project) . '#aiplanning';

        $latestMom = $project->moms()
            ->orderByDesc('meeting_date')
            ->orderByDesc('id')
            ->first();
        if (! $latestMom) {
            return redirect()->to($backUrl)
                ->with('status', 'Tambahkan MoM terlebih dahulu sebelum generate WBS.');
        }

        if (! AiPlanner::isConfigured()) {
            return redirect()->to($backUrl)
                ->with('status', 'AI belum dikonfigurasi. Set GEMINI_API_KEY pada .env.');
        }

        $project->loadMissing('client');
        $existingModuleTitles = $project->modules()->pluck('title')->all();

        $context = [
            'project_name'        => $project->name,
            'project_code'        => $project->code,
            'project_description' => trim(implode("\n", array_filter([
                (string) ($project->description ?: ''),
                'Klien: ' . ($project->client?->name ?: '-'),
                'Modul yang sudah ada: ' . (! empty($existingModuleTitles) ? implode(', ', $existingModuleTitles) : '-'),
            ]))),
            'mom_date'    => optional($latestMom->meeting_date)->format('Y-m-d'),
            'mom_summary' => (string) ($latestMom->summary ?: ''),
            'mom_notes'   => (string) $latestMom->notes,
        ];

        $result = AiPlanner::generateWbsDraft($context);

        if (! ($result['ok'] ?? false)) {
            return redirect()->to($backUrl)
                ->with('status', 'Generator AI gagal: ' . ($result['error'] ?? 'tidak diketahui.'));
        }

        $existingModuleByLower = [];
        foreach ($project->modules()->get(['id', 'title']) as $existing) {
            $existingModuleByLower[mb_strtolower($existing->title)] = $existing->id;
        }
        $maxModuleSort = (int) $project->modules()->max('sort_order');
        $maxTaskSort   = (int) $project->tasks()->max('sort_order');

        $createdModules = 0;
        $createdTasks   = 0;

        try {
            DB::transaction(function () use (
                $project,
                $result,
                &$existingModuleByLower,
                &$maxModuleSort,
                &$maxTaskSort,
                &$createdModules,
                &$createdTasks,
            ) {
                $drafts = $result['data']['modules'] ?? [];
                foreach ($drafts as $modDraft) {
                    $titleLower = mb_strtolower((string) ($modDraft['title'] ?? ''));
                    if ($titleLower === '' || isset($existingModuleByLower[$titleLower])) {
                        continue;
                    }

                    $maxModuleSort++;
                    $module = $project->modules()->create([
                        'title'          => $modDraft['title'],
                        'description'    => $modDraft['description'] ?? null,
                        'status'         => $modDraft['status'] ?? AiPlanner::DEFAULT_MODULE_STATUS,
                        'estimate_hours' => (int) ($modDraft['estimate_hours'] ?? 0),
                        'sort_order'     => $maxModuleSort,
                    ]);
                    $createdModules++;
                    $existingModuleByLower[$titleLower] = $module->id;

                    $existingTaskByLower = [];
                    foreach (($modDraft['tasks'] ?? []) as $taskDraft) {
                        $tTitleLower = mb_strtolower((string) ($taskDraft['title'] ?? ''));
                        if ($tTitleLower === '' || isset($existingTaskByLower[$tTitleLower])) {
                            continue;
                        }
                        $maxTaskSort++;
                        $project->tasks()->create([
                            'project_module_id' => $module->id,
                            'assigned_to'       => null,
                            'title'             => $taskDraft['title'],
                            'description'       => $taskDraft['description'] ?? null,
                            'status'            => $taskDraft['status'] ?? AiPlanner::DEFAULT_TASK_STATUS,
                            'priority'          => $taskDraft['priority'] ?? AiPlanner::DEFAULT_TASK_PRIORITY,
                            'estimate_hours'    => (int) ($taskDraft['estimate_hours'] ?? 0),
                            'sort_order'        => $maxTaskSort,
                        ]);
                        $createdTasks++;
                        $existingTaskByLower[$tTitleLower] = true;
                    }
                }
            });
        } catch (\Throwable $e) {
            report($e);
            return redirect()->to($backUrl)
                ->with('status', 'Generator AI gagal menyimpan draft: ' . $e->getMessage());
        }

        if ($createdModules === 0 && $createdTasks === 0) {
            return redirect()->to($backUrl)
                ->with('status', 'AI selesai, tapi semua modul/draft sudah ada — tidak ada item baru.');
        }

        AuditLogger::log(
            'ai_wbs_generated',
            'Project Master',
            'Generate WBS AI: <strong>' . $createdModules . '</strong> modul + <strong>' . $createdTasks . '</strong> task pada proyek <strong>' . e($project->name) . '</strong>',
            $project,
            null,
            [
                'modules_created' => $createdModules,
                'tasks_created'   => $createdTasks,
                'source_mom_id'   => $latestMom->id,
            ],
        );

        return redirect()->to($backUrl)
            ->with('status', 'AI WBS Generator selesai: ' . $createdModules . ' modul, ' . $createdTasks . ' task.');
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

    /**
     * Operational users (sa_qa / ui_ux / fullstack_dev) may only touch
     * projects they're actually assigned to via team_assignments. CEO/PM
     * and admin-tier are always allowed. Anyone else (no role, archived
     * user) gets a redirect back to /projects so they can't probe IDs.
     */
    private function ensureOperationalCanAccessProject(Project $project)
    {
        $user = auth()->user();
        if (! $user) {
            return redirect()->route('login');
        }

        $role = $user->roles()->first()?->name;
        if (! in_array($role, self::OPERATIONAL_ROLES, true)) {
            return null; // CEO/PM + admin tier pass through.
        }

        $assigned = \App\Models\TeamAssignment::query()
            ->where('user_id', $user->id)
            ->where('project_id', $project->id)
            ->exists();

        if (! $assigned) {
            return redirect()
                ->route('projects.index')
                ->with('status', 'Proyek tersebut tidak ditugaskan kepada Anda.');
        }

        return null;
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
        $momTotal = $project->relationLoaded('moms') ? $project->moms->count() : $project->moms()->count();

        return [
            ['code' => 'MOD',  'value' => $moduleApproved . '/' . $moduleTotal, 'label' => 'Modul Disetujui',    'color' => '#3B82F6', 'progress' => $this->percentage($moduleApproved, $moduleTotal), 'sub' => $moduleTotal > 0 ? $moduleTotal . ' modul terdefinisi' : 'Belum ada modul'],
            ['code' => 'TASK', 'value' => $taskDone . '/' . $taskTotal,         'label' => 'Task Selesai',       'color' => '#7C3AED', 'progress' => $this->percentage($taskDone, $taskTotal),         'sub' => $taskDone . ' Selesai • ' . $taskOpen . ' Open'],
            ['code' => 'MOM',  'value' => $momTotal . ' MoM',                   'label' => 'MoM Tersimpan',      'color' => '#10B981', 'progress' => $momTotal > 0 ? 100 : 0,                          'sub' => $momTotal > 0 ? 'Catatan rapat tersimpan' : 'Belum ada MoM'],
            ['code' => 'QC',   'value' => '0%',                                 'label' => 'Tingkat Lulus Test', 'color' => '#F59E0B', 'progress' => 0,                                           'sub' => 'Pass Rate'],
        ];
    }

    private function projectTabs(Project $project): array
    {
        $momTotal = $project->relationLoaded('moms') ? $project->moms->count() : $project->moms()->count();

        return [
            ['id' => 'overview',   'label' => 'Overview',         'count' => $project->modules->count()],
            ['id' => 'workspace',  'label' => 'Kanban Workspace', 'count' => $project->tasks->count()],
            ['id' => 'aiplanning', 'label' => 'AI Planning',      'count' => $momTotal],
            ['id' => 'qc',         'label' => 'Quality Control',  'count' => 0],
        ];
    }

    private function projectMomRows(Project $project): array
    {
        return $project->moms->map(function (ProjectMom $mom) {
            return [
                'id'           => $mom->id,
                'meeting_date' => optional($mom->meeting_date)->format('Y-m-d'),
                'date_label'   => $mom->meeting_date
                    ? $this->formatDateLongId($mom->meeting_date)
                    : '—',
                'notes'        => $mom->notes,
                'summary'      => $mom->summary,
                'status'       => $mom->status ?: 'draft',
                'creator'      => $mom->creator?->name,
                'created_at'   => $mom->created_at?->diffForHumans(),
            ];
        })->all();
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
