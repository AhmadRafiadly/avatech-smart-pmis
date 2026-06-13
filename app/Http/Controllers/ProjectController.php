<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\AuditLog;
use App\Models\Project;
use App\Models\ProjectBlocker;
use App\Models\ProjectChangeRequest;
use App\Models\ProjectClientReview;
use App\Models\ProjectHandoverPack;
use App\Models\ProjectMom;
use App\Models\ProjectModule;
use App\Models\ProjectQcTest;
use App\Models\ProjectRequirementInboxItem;
use App\Models\ProjectSignoff;
use App\Models\ProjectTask;
use App\Models\ProjectTaskDependency;
use App\Models\ProjectTaskDesignDeliverable;
use App\Models\ProjectUatItem;
use App\Models\TeamAssignment;
use App\Models\User;
use App\Services\AiPlanner;
use App\Services\AuditLogger;
use App\Services\SmartInsightService;
use App\Support\AppTime;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
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
        'approved'       => 'Aktif',
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

    private const QC_STATUS_LABELS = [
        'pending' => 'Pending',
        'passed'  => 'Lulus',
        'failed'  => 'Gagal',
        'retest'  => 'Retest',
    ];

    private const QC_PRIORITY_LABELS = [
        'low'    => 'Low',
        'medium' => 'Medium',
        'high'   => 'High',
    ];

    private const OPERATIONAL_ROLES = ['sa_qa', 'uiux_designer', 'ui_ux', 'fullstack_dev'];
    private const QUICK_ASSIGN_ROLES = ['ceo_pm', 'admin', 'super_admin', 'developer'];
    private const QUICK_ASSIGN_RESPONSIBILITIES = [
        'saqa_mom_qc' => 'SA/QA & MoM/QC',
        'uiux_design' => 'UI/UX Designer',
        'fullstack_dev' => 'Fullstack Dev',
        'wordpress_support' => 'WordPress Support',
        'copywriting_support' => 'Copywriting Support',
    ];

    /* ---- Change Request / Scope Control ---------------------------------
     * Scope decisions (classify/approve/reject/convert) are limited to PM/SA
     * and admin-tier. Operational roles (dev/ui-ux) may file & submit internal
     * change requests but cannot make scope decisions. Enforced server-side. */
    private const CR_DECISION_ROLES = ['ceo_pm', 'sa_qa', 'admin', 'super_admin', 'developer'];

    private const CR_SOURCE_LABELS = [
        'whatsapp'    => 'WhatsApp',
        'meeting'     => 'Meeting',
        'client_call' => 'Telepon Klien',
        'internal'    => 'Internal',
        'email'       => 'Email',
        'other'       => 'Lainnya',
    ];

    private const CR_TYPE_LABELS = [
        'new_scope'            => 'Scope Baru',
        'revision'             => 'Revisi',
        'bug'                  => 'Bug',
        'content_update'       => 'Update Konten',
        'design_change'        => 'Perubahan Desain',
        'technical_adjustment' => 'Penyesuaian Teknis',
        'other'                => 'Lainnya',
    ];

    private const CR_PRIORITY_LABELS = [
        'low'    => 'Low',
        'medium' => 'Medium',
        'high'   => 'High',
        'urgent' => 'Urgent',
    ];

    private const CR_STATUS_LABELS = [
        'draft'        => 'Draft',
        'needs_review' => 'Perlu Review',
        'approved'     => 'Disetujui',
        'rejected'     => 'Ditolak',
        'converted'    => 'Jadi Task',
    ];

    private const INTAKE_SOURCE_LABELS = [
        'whatsapp'    => 'WhatsApp',
        'meeting'     => 'Meeting',
        'client_call' => 'Client Call',
        'email'       => 'Email',
        'internal'    => 'Internal',
        'other'       => 'Lainnya',
    ];

    private const INTAKE_STATUS_LABELS = [
        'new'       => 'Baru',
        'reviewed'  => 'Direview',
        'converted' => 'Dikonversi',
        'dismissed' => 'Diabaikan',
    ];

    private const BLOCKER_SOURCE_LABELS = [
        'client' => 'Client',
        'internal' => 'Internal',
        'technical' => 'Teknis',
        'design' => 'Design',
        'content' => 'Konten',
        'deployment' => 'Deployment',
        'dependency' => 'Dependency',
        'access' => 'Akses',
        'other' => 'Lainnya',
    ];

    private const BLOCKER_SEVERITY_LABELS = [
        'low' => 'Low',
        'medium' => 'Medium',
        'high' => 'High',
        'critical' => 'Critical',
    ];

    private const BLOCKER_STATUS_LABELS = [
        'open' => 'Open',
        'in_progress' => 'In Progress',
        'resolved' => 'Resolved',
        'cancelled' => 'Cancelled',
    ];

    private const DEPENDENCY_TYPE_LABELS = [
        'finish_to_start' => 'Finish to Start',
        'related' => 'Related',
        'blocks' => 'Blocks',
    ];

    private const CLIENT_REVIEW_MANAGER_ROLES = ['ceo_pm', 'sa_qa', 'admin', 'super_admin', 'developer'];

    private const CLIENT_REVIEW_STATUS_LABELS = [
        'draft' => 'Draft',
        'active' => 'Aktif',
        'approved' => 'Disetujui Client',
        'revision_requested' => 'Minta Revisi',
        'expired' => 'Kadaluarsa',
        'revoked' => 'Dicabut',
    ];

    private const CLIENT_REVIEW_TYPE_LABELS = [
        'mom' => 'MoM',
        'design' => 'Design',
        'progress' => 'Progress',
        'uat' => 'UAT',
        'handover' => 'Handover',
        'general' => 'General',
    ];

    private const UAT_MANAGER_ROLES = ['ceo_pm', 'sa_qa', 'admin', 'super_admin', 'developer'];

    private const UAT_CATEGORY_LABELS = [
        'functional' => 'Functional',
        'content' => 'Content',
        'design' => 'Design',
        'performance' => 'Performance',
        'security' => 'Security',
        'integration' => 'Integration',
        'deployment' => 'Deployment',
        'other' => 'Other',
    ];

    private const UAT_PRIORITY_LABELS = [
        'low' => 'Low',
        'medium' => 'Medium',
        'high' => 'High',
        'critical' => 'Critical',
    ];

    private const UAT_STATUS_LABELS = [
        'pending' => 'Pending',
        'passed' => 'Lulus',
        'failed' => 'Gagal',
        'blocked' => 'Tertahan',
        'revision_needed' => 'Butuh Revisi',
    ];

    private const SIGNOFF_TYPE_LABELS = [
        'uat' => 'Sign-off UAT',
        'handover' => 'Sign-off Handover',
        'final' => 'Final Sign-off',
    ];

    private const SIGNOFF_STATUS_LABELS = [
        'draft' => 'Draft',
        'ready' => 'Ready',
        'signed' => 'Signed',
        'revision_requested' => 'Minta Revisi',
        'revoked' => 'Dicabut',
    ];

    /* ---- Handover Pack (Milestone 4) ---------------------------------- */
    private const HANDOVER_STATUS_LABELS = [
        'draft'     => 'Draft',
        'generated' => 'Draf Tergenerate',
        'finalized' => 'Final',
        'revoked'   => 'Dicabut',
    ];

    private const HANDOVER_CREDENTIAL_LABELS = [
        'not_required' => 'Tidak Diperlukan',
        'pending'      => 'Menunggu Serah Terima',
        'handed_over'  => 'Sudah Diserahkan',
    ];

    // Handover pack management mirrors UAT/sign-off authority (PM/SA + admin tier).
    private const HANDOVER_MANAGER_ROLES = ['ceo_pm', 'sa_qa', 'admin', 'super_admin', 'developer'];

    public function __construct(private readonly SmartInsightService $insights)
    {
    }

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

        $projects = Project::with(['client', 'lead', 'modules', 'tasks', 'moms', 'qcTests'])
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

        $projects = Project::with([
                'client',
                'tasks' => fn ($query) => $query->select('id', 'project_id', 'assigned_to', 'status', 'estimate_hours'),
            ])
            ->whereIn('id', $assignedProjectIds)
            ->whereNull('archived_at')
            ->orderByDesc('updated_at')
            ->get()
            ->each(function (Project $project) use ($user) {
                $progressTasks = $project->tasks
                    ->reject(fn (ProjectTask $task) => $this->isExcludedProgressStatus($task->status))
                    ->values();

                $project->setAttribute('computed_progress', $this->projectProgress($project));
                $project->setAttribute('tasks_count', $progressTasks->count());
                $project->setAttribute('tasks_done_count', $progressTasks->filter(fn (ProjectTask $task) => $this->isDoneStatus($task->status))->count());
                $project->setAttribute('tasks_mine_count', $progressTasks->where('assigned_to', $user->id)->count());
            });

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
            'requires_design' => ['nullable', 'boolean'],
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
            'requires_design'  => $request->boolean('requires_design'),
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
            'requires_design' => $request->boolean('requires_design'),
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
            'tasks' => fn ($query) => $query->with([
                'module',
                'assignee',
                'designDeliverables' => fn ($deliverables) => $deliverables->with('creator:id,name')->orderBy('id'),
            ])->orderBy('sort_order')->orderBy('id'),
            'moms' => fn ($query) => $query->with('creator:id,name')->orderByDesc('meeting_date')->orderByDesc('id'),
            'qcTests' => fn ($query) => $query->with(['module:id,title', 'task:id,title', 'creator:id,name'])
                ->orderBy('created_at')
                ->orderBy('id'),
            'changeRequests' => fn ($query) => $query->with([
                'requester:id,name',
                'approver:id,name',
                'affectedModule:id,title',
                'createdTask:id,title',
            ])->orderByDesc('created_at')->orderByDesc('id'),
            'requirementInboxItems' => fn ($query) => $query->with([
                'capturedBy:id,name',
                'reviewer:id,name',
                'changeRequest:id,title',
                'task:id,title',
                'mom:id,meeting_date',
            ])->orderByDesc('created_at')->orderByDesc('id'),
            'blockers' => fn ($query) => $query->with([
                'task:id,title,status',
                'reporter:id,name',
                'assignee:id,name',
            ])->orderBy('due_date')
                ->orderByDesc('id'),
            'taskDependencies' => fn ($query) => $query->with([
                'task:id,title,status',
                'dependsOnTask:id,title,status',
                'creator:id,name',
            ])->orderByDesc('id'),
            'clientReviews' => fn ($query) => $query->with('creator:id,name')->orderByDesc('created_at')->orderByDesc('id'),
            'uatItems' => fn ($query) => $query->with('tester:id,name')->orderBy('sort_order')->orderBy('id'),
            'signoffs' => fn ($query) => $query->with(['creator:id,name', 'approver:id,name', 'clientReview:id,title,status'])->orderBy('type')->orderByDesc('id'),
            'handoverPacks' => fn ($query) => $query->with(['generatedBy:id,name', 'finalizedBy:id,name'])->orderByDesc('version')->orderByDesc('id'),
        ]);

        $statusUi = self::STATUS_UI[$project->status] ?? self::STATUS_UI['on-track'];

        $leadName     = $project->lead?->name;
        $leadInitials = $leadName ? $this->initials($leadName) : '-';
        $leadDisplay  = $leadName ?: 'Belum ditentukan';

        $desc = $project->description ?: (self::DESC_MAP[$project->code] ?? 'Belum ada deskripsi untuk proyek ini.');

        $due       = $project->due_at ? $this->formatDateId($project->due_at) : '-';
        $createdAt = $project->created_at ? $this->formatDateLongId(AppTime::cast($project->created_at)) : '-';

        return view('projects.show', [
            'title'        => $project->name,
            'project'      => $project,
            'desc'         => $desc,
            'createdAt'    => $createdAt,
            'dueFormatted' => $due,
            'statusUi'     => $statusUi,
            'leadName'     => $leadName,
            'leadDisplay'  => $leadDisplay,
            'leadInitials' => $leadInitials,
            'useReferenceProjectData' => $this->usesReferenceProjectData($project),
            'clients'      => Client::orderBy('name')->get(['id', 'name']),
            'dbModules'    => $this->projectModuleRows($project),
            'dbKanban'     => $this->projectKanbanColumns($project),
            'dbStatusCards'=> $this->projectModuleStatusCards($project),
            'dbMetrics'    => $this->projectMetrics($project),
            'dbTabs'       => $this->projectTabs($project),
            'dbTaskTotal'  => $project->tasks->reject(fn (ProjectTask $task) => $this->isExcludedProgressStatus($task->status))->count(),
            'dbTaskDone'   => $project->tasks->filter(fn (ProjectTask $task) => $this->isDoneStatus($task->status))->count(),
            'dbMoms'       => $this->projectMomRows($project),
            'dbQcTests'    => $this->projectQcTestRows($project),
            'dbQcSummary'  => $this->projectQcSummary($project),
            'dbActivities' => $this->projectActivityRows($project),
            'projectProgress' => $this->projectProgress($project),
            'canPrepareQc' => $this->canPrepareQc($project),
            'canExecuteQc' => $this->canExecuteQc($project),
            'qcStatusOptions'   => self::QC_STATUS_LABELS,
            'qcPriorityOptions' => self::QC_PRIORITY_LABELS,
            'aiReady'      => AiPlanner::isConfigured(),
            'aiProvider'   => AiPlanner::providerLabel(),
            'moduleStatusOptions' => self::MODULE_STATUS_LABELS,
            'taskStatusOptions' => self::TASK_STATUS_LABELS,
            'taskPriorityOptions' => self::TASK_PRIORITY_LABELS,
            'assigneeOptions' => $this->projectAssignedUsers($project),
            'quickAssignUsers' => $this->canQuickAssignTeam() ? $this->quickAssignUserRows($project) : [],
            'dbChangeRequests'   => $this->projectChangeRequestRows($project),
            'changeRequestSummary' => $this->projectChangeRequestSummary($project),
            'crSourceOptions'    => self::CR_SOURCE_LABELS,
            'crTypeOptions'      => self::CR_TYPE_LABELS,
            'crPriorityOptions'  => self::CR_PRIORITY_LABELS,
            'crStatusOptions'    => self::CR_STATUS_LABELS,
            'crCanContribute'    => $this->crCanContribute($project),
            'crCanDecide'        => $this->crCanDecide(),
            'requirementInbox' => $this->projectRequirementInboxRows($project),
            'requirementInboxSummary' => $this->projectRequirementInboxSummary($project),
            'intakeCanContribute' => $this->intakeCanContribute($project),
            'intakeCanDecide' => $this->intakeCanDecide(),
            'intakeSourceOptions' => self::INTAKE_SOURCE_LABELS,
            'intakeTypeOptions' => self::CR_TYPE_LABELS,
            'intakePriorityOptions' => self::CR_PRIORITY_LABELS,
            'intakeStatusOptions' => self::INTAKE_STATUS_LABELS,
            'projectBlockers' => $this->projectBlockerRows($project),
            'projectBlockerSummary' => $this->projectBlockerSummary($project),
            'projectDependencies' => $this->projectDependencyRows($project),
            'blockerCanContribute' => $this->blockerCanContribute($project),
            'blockerCanManage' => $this->blockerCanManage(),
            'blockerSourceOptions' => self::BLOCKER_SOURCE_LABELS,
            'blockerSeverityOptions' => self::BLOCKER_SEVERITY_LABELS,
            'blockerStatusOptions' => self::BLOCKER_STATUS_LABELS,
            'dependencyTypeOptions' => self::DEPENDENCY_TYPE_LABELS,
            'projectTaskOptions' => $this->projectTaskOptions($project),
            'clientReviews'      => $this->projectClientReviewRows($project),
            'clientReviewTypes'  => self::CLIENT_REVIEW_TYPE_LABELS,
            'clientReviewStatuses' => self::CLIENT_REVIEW_STATUS_LABELS,
            'canManageClientReviews' => $this->canManageClientReviews($project),
            'uatItems' => $this->projectUatItemRows($project),
            'signoffs' => $this->projectSignoffRows($project),
            'uatSummary' => $this->projectUatSummary($project),
            'signoffGate' => $this->projectSignoffGate($project),
            'approvedClientReviews' => $this->approvedClientReviewOptions($project),
            'canManageUatSignoff' => $this->canManageUatSignoff($project),
            'uatCategoryOptions' => self::UAT_CATEGORY_LABELS,
            'uatPriorityOptions' => self::UAT_PRIORITY_LABELS,
            'uatStatusOptions' => self::UAT_STATUS_LABELS,
            'signoffTypeOptions' => self::SIGNOFF_TYPE_LABELS,
            'handoverPacks' => $this->projectHandoverPackRows($project),
            'handoverReadiness' => $this->projectHandoverReadiness($project),
            'handoverCanManage' => $this->canManageHandover($project),
            'handoverStatusOptions' => self::HANDOVER_STATUS_LABELS,
            'handoverCredentialOptions' => self::HANDOVER_CREDENTIAL_LABELS,
        ]);
    }

    public function quickAssignTeam(Request $request, Project $project)
    {
        abort_unless($this->canQuickAssignTeam(), 403);

        $eligibleUserIds = User::whereHas('roles', fn ($query) => $query->whereIn('name', self::OPERATIONAL_ROLES))
            ->pluck('id')
            ->all();

        $validated = $request->validate([
            'assignments' => ['required', 'array'],
            'assignments.*.include' => ['nullable'],
            'assignments.*.user_id' => ['required', Rule::in($eligibleUserIds)],
            'assignments.*.responsibilities' => ['nullable', 'array'],
            'assignments.*.responsibilities.*' => ['string', Rule::in(array_keys(self::QUICK_ASSIGN_RESPONSIBILITIES))],
            'assignments.*.title' => ['nullable', 'string', 'max:160'],
            'assignments.*.type' => ['required', Rule::in(['task', 'review', 'support'])],
            'assignments.*.status' => ['required', Rule::in(['planned', 'in_progress', 'done'])],
            'assignments.*.estimated_hours' => ['nullable', 'integer', 'min:0', 'max:200'],
            'assignments.*.due_date' => ['nullable', 'date'],
            'assignments.*.notes' => ['nullable', 'string', 'max:1000'],
        ], [
            'assignments.required' => 'Pilih minimal satu anggota tim.',
            'assignments.*.user_id.in' => 'Anggota tim tidak valid untuk quick assign.',
            'assignments.*.responsibilities.*.in' => 'Responsibility Quick Assign tidak valid.',
            'assignments.*.type.in' => 'Tipe penugasan tidak valid.',
            'assignments.*.status.in' => 'Status penugasan tidak valid.',
            'assignments.*.estimated_hours.integer' => 'Estimasi jam harus berupa angka.',
            'assignments.*.estimated_hours.max' => 'Estimasi jam terlalu besar.',
        ]);

        $selected = collect($validated['assignments'])
            ->filter(fn (array $row) => ! empty($row['include']))
            ->values();

        if ($selected->isEmpty()) {
            return redirect()
                ->to(route('projects.show', $project) . '#overview')
                ->with('status', 'Pilih minimal satu anggota untuk Quick Assign.');
        }

        $created = 0;
        $updated = 0;
        $userNames = User::whereIn('id', $selected->pluck('user_id'))->pluck('name', 'id');

        foreach ($selected as $row) {
            $user = User::with('roles')->find((int) $row['user_id']);
            $responsibilities = $this->normalizeQuickAssignResponsibilities($row['responsibilities'] ?? [], $user);
            $defaults = $this->quickAssignDefaultsForResponsibilities($responsibilities, $user);
            $assignment = TeamAssignment::firstOrNew([
                'project_id' => $project->id,
                'user_id' => (int) $row['user_id'],
            ]);

            $wasExisting = $assignment->exists;
            $original = $assignment->getOriginal();
            $assignment->fill([
                'title' => trim((string) ($row['title'] ?? '')) ?: $defaults['title'],
                'type' => $row['type'],
                'responsibilities' => $responsibilities,
                'status' => $row['status'],
                'estimated_hours' => (int) ($row['estimated_hours'] ?? $defaults['estimated_hours']),
                'due_date' => $row['due_date'] ?? $project->due_at?->toDateString(),
                'notes' => trim((string) ($row['notes'] ?? '')) ?: $defaults['notes'],
            ]);
            $assignment->save();

            $person = $userNames[$assignment->user_id] ?? 'anggota tim';
            if (! $wasExisting) {
                $created++;
                AuditLogger::log(
                    'assignment_created',
                    'Team Management',
                    'Quick assign <strong>' . e($person) . '</strong> ke proyek <strong>' . e($project->name) . '</strong>',
                    $assignment,
                    null,
                    ['project_id' => $project->id, 'user_id' => $assignment->user_id, 'title' => $assignment->title, 'responsibilities' => $responsibilities],
                );
            } elseif ($assignment->wasChanged()) {
                $updated++;
                AuditLogger::log(
                    'assignment_updated',
                    'Team Management',
                    'Quick assign memperbarui penugasan <strong>' . e($person) . '</strong> pada proyek <strong>' . e($project->name) . '</strong>',
                    $assignment,
                    $original,
                    $assignment->getAttributes(),
                );
            }
        }

        return redirect()
            ->to(route('projects.show', $project) . '#overview')
            ->with('status', 'Quick Assign selesai: ' . $created . ' dibuat, ' . $updated . ' diperbarui.');
    }

    public function storeClientReview(Request $request, Project $project)
    {
        $this->ensureCanManageClientReviews($project);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:180'],
            'description' => ['nullable', 'string', 'max:2000'],
            'review_type' => ['nullable', Rule::in(ProjectClientReview::REVIEW_TYPES)],
            'status' => ['required', Rule::in(['draft', 'active'])],
            'expires_at' => ['nullable', 'date', 'after:now'],
            'client_name' => ['nullable', 'string', 'max:160'],
            'client_email' => ['nullable', 'email', 'max:190'],
            'internal_notes' => ['nullable', 'string', 'max:2000'],
            'include_mom' => ['nullable', 'boolean'],
            'include_design_deliverables' => ['nullable', 'boolean'],
            'include_progress' => ['nullable', 'boolean'],
            'include_qc_summary' => ['nullable', 'boolean'],
            'include_change_requests' => ['nullable', 'boolean'],
        ], [
            'title.required' => 'Judul review wajib diisi.',
            'review_type.in' => 'Tipe review client tidak valid.',
            'expires_at.after' => 'Tanggal kadaluarsa harus setelah waktu saat ini.',
        ]);

        $review = $project->clientReviews()->create([
            'created_by_user_id' => auth()->id(),
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'token' => $this->generateClientReviewToken(),
            'status' => $validated['status'],
            'review_type' => $validated['review_type'] ?? 'general',
            'expires_at' => $validated['expires_at'] ?? null,
            'client_name' => $validated['client_name'] ?? null,
            'client_email' => $validated['client_email'] ?? null,
            'internal_notes' => $validated['internal_notes'] ?? null,
            'include_mom' => $request->boolean('include_mom'),
            'include_design_deliverables' => $request->boolean('include_design_deliverables'),
            'include_progress' => $request->boolean('include_progress'),
            'include_qc_summary' => $request->boolean('include_qc_summary'),
            'include_change_requests' => $request->boolean('include_change_requests'),
        ]);

        AuditLogger::log(
            'client_review_created',
            'Client Review',
            'Membuat Client Review <strong>' . e($review->title) . '</strong> untuk proyek <strong>' . e($project->name) . '</strong>',
            $review,
            null,
            ['project_id' => $project->id, 'status' => $review->status, 'review_type' => $review->review_type],
        );

        return redirect()
            ->to(route('projects.show', $project) . '#clientportal')
            ->with('status', 'Client Review Link berhasil dibuat.');
    }

    public function updateClientReviewStatus(Request $request, Project $project, ProjectClientReview $clientReview)
    {
        $this->ensureCanManageClientReviews($project);
        abort_unless((int) $clientReview->project_id === (int) $project->id, 404);

        $validated = $request->validate([
            'status' => ['required', Rule::in(['active', 'revoked'])],
        ]);

        if ($validated['status'] === 'active') {
            if (! in_array($clientReview->status, ['draft', 'revoked'], true)) {
                return redirect()
                    ->to(route('projects.show', $project) . '#clientportal')
                    ->with('status', 'Review final tidak dapat diaktifkan ulang. Buat review link baru untuk ronde berikutnya.');
            }

            if ($clientReview->isExpired()) {
                return redirect()
                    ->to(route('projects.show', $project) . '#clientportal')
                    ->with('status', 'Review link sudah kadaluarsa. Buat review link baru dengan tanggal aktif yang valid.');
            }
        }

        $original = $clientReview->getOriginal();
        $clientReview->update(['status' => $validated['status']]);

        $action = $validated['status'] === 'active' ? 'client_review_activated' : 'client_review_revoked';
        $verb = $validated['status'] === 'active' ? 'Mengaktifkan' : 'Mencabut';

        AuditLogger::log(
            $action,
            'Client Review',
            $verb . ' Client Review <strong>' . e($clientReview->title) . '</strong> untuk proyek <strong>' . e($project->name) . '</strong>',
            $clientReview,
            ['status' => $original['status'] ?? null],
            ['status' => $clientReview->status],
        );

        return redirect()
            ->to(route('projects.show', $project) . '#clientportal')
            ->with('status', $validated['status'] === 'active' ? 'Client Review Link diaktifkan.' : 'Client Review Link dicabut.');
    }

    public function generateUatChecklist(Project $project)
    {
        $this->ensureCanManageUatSignoff($project);
        if (! $this->canRunUat($project)) {
            return $this->backToSignoff($project, 'UAT aktif setelah project masuk fase QC.');
        }

        $project->loadMissing('modules.tasks', 'tasks');
        $created = 0;

        $modules = $project->modules->isNotEmpty()
            ? $project->modules
            : collect([(object) ['title' => 'Scope Project', 'description' => $project->description]]);

        foreach ($modules as $index => $module) {
            $title = 'Validasi modul ' . $module->title;
            $item = ProjectUatItem::firstOrNew([
                'project_id' => $project->id,
                'title' => $title,
            ]);

            if (! $item->exists) {
                $created++;
            }

            $category = $this->inferUatCategory($module->title . ' ' . (string) ($module->description ?? ''));
            $item->fill([
                'description' => 'Pastikan modul "' . $module->title . '" berjalan sesuai scope, MoM, dan hasil QC.',
                'category' => $category,
                'priority' => in_array($category, ['security', 'integration', 'deployment'], true) ? 'high' : 'medium',
                'status' => $item->status ?: 'pending',
                'sort_order' => $index + 1,
            ])->save();
        }

        if ($created > 0) {
            AuditLogger::log(
                'uat_checklist_generated',
                'UAT & Sign-off',
                'Generate checklist UAT untuk proyek <strong>' . e($project->name) . '</strong>',
                $project,
                null,
                ['project_id' => $project->id, 'created' => $created],
            );
        }

        return $this->backToSignoff($project, $created > 0
            ? 'Checklist UAT berhasil dibuat: ' . $created . ' item baru.'
            : 'Checklist UAT sudah tersedia. Tidak ada item duplikat dibuat.');
    }

    public function storeUatItem(Request $request, Project $project)
    {
        $this->ensureCanManageUatSignoff($project);
        if (! $this->canRunUat($project)) {
            return $this->backToSignoff($project, 'UAT aktif setelah project masuk fase QC.');
        }

        $validated = $this->validateUatItem($request, $project);

        $item = $project->uatItems()->create([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'category' => $validated['category'] ?? 'functional',
            'priority' => $validated['priority'] ?? 'medium',
            'status' => 'pending',
            'evidence_url' => $validated['evidence_url'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'sort_order' => ((int) $project->uatItems()->max('sort_order')) + 1,
        ]);

        AuditLogger::log(
            'uat_item_created',
            'UAT & Sign-off',
            'Menambah item UAT <strong>' . e($item->title) . '</strong> pada proyek <strong>' . e($project->name) . '</strong>',
            $item,
            null,
            ['project_id' => $project->id, 'status' => $item->status],
        );

        return $this->backToSignoff($project, 'Item UAT berhasil ditambahkan.');
    }

    public function updateUatItem(Request $request, Project $project, ProjectUatItem $uatItem)
    {
        $this->ensureCanManageUatSignoff($project);
        abort_unless((int) $uatItem->project_id === (int) $project->id, 404);

        if (! $this->canRunUat($project)) {
            return $this->backToSignoff($project, 'UAT aktif setelah project masuk fase QC.');
        }

        $validated = $request->validate([
            'status' => ['required', Rule::in(ProjectUatItem::STATUSES)],
            'notes' => ['nullable', 'string', 'max:4000'],
            'evidence_url' => ['nullable', 'url', 'max:2048'],
        ], [
            'status.required' => 'Status UAT wajib dipilih.',
            'status.in' => 'Status UAT tidak valid.',
            'evidence_url.url' => 'Evidence URL harus berupa URL valid.',
        ]);

        $original = $uatItem->getOriginal();
        $uatItem->fill([
            'status' => $validated['status'],
            'notes' => $validated['notes'] ?? $uatItem->notes,
            'evidence_url' => $validated['evidence_url'] ?? $uatItem->evidence_url,
            'tested_by_user_id' => auth()->id(),
            'tested_at' => $validated['status'] !== 'pending' ? AppTime::now() : null,
        ])->save();

        AuditLogger::log(
            'uat_item_status_updated',
            'UAT & Sign-off',
            'Mengubah status UAT <strong>' . e($uatItem->title) . '</strong> menjadi <strong>' . e(self::UAT_STATUS_LABELS[$uatItem->status] ?? $uatItem->status) . '</strong>',
            $uatItem,
            ['status' => $original['status'] ?? null],
            ['status' => $uatItem->status, 'project_id' => $project->id],
        );

        return $this->backToSignoff($project, 'Status UAT berhasil diperbarui.');
    }

    public function storeSignoff(Request $request, Project $project)
    {
        $this->ensureCanManageUatSignoff($project);
        if (! $this->canRunUat($project)) {
            return $this->backToSignoff($project, 'Sign-off aktif setelah project masuk fase QC.');
        }

        $validated = $request->validate([
            'type' => ['required', Rule::in(['uat', 'handover'])],
            'signed_by_name' => ['required', 'string', 'max:160'],
            'signed_by_email' => ['nullable', 'email', 'max:190'],
            'signed_by_role' => ['nullable', 'string', 'max:120'],
            'client_review_id' => ['nullable', Rule::exists('project_client_reviews', 'id')->where('project_id', $project->id)->where('status', 'approved')],
            'notes' => ['nullable', 'string', 'max:4000'],
            'handover_summary' => ['nullable', 'string', 'max:4000'],
        ], [
            'type.required' => 'Tipe sign-off wajib dipilih.',
            'signed_by_name.required' => 'Nama penandatangan wajib diisi.',
            'client_review_id.exists' => 'Client Review approval tidak valid untuk proyek ini.',
        ]);

        $gate = $this->projectSignoffGate($project->fresh(['uatItems', 'signoffs']));
        if ($validated['type'] === 'uat' && ! $gate['can_sign_uat']) {
            return $this->backToSignoff($project, 'Sign-off UAT belum bisa dicatat: ' . implode(', ', $gate['missing']));
        }
        if ($validated['type'] === 'handover' && ! $gate['can_sign_handover']) {
            return $this->backToSignoff($project, 'Sign-off Handover belum bisa dicatat: ' . implode(', ', $gate['missing']));
        }

        $signoff = ProjectSignoff::updateOrCreate(
            ['project_id' => $project->id, 'type' => $validated['type']],
            [
                'status' => 'signed',
                'signed_by_name' => $validated['signed_by_name'],
                'signed_by_email' => $validated['signed_by_email'] ?? null,
                'signed_by_role' => $validated['signed_by_role'] ?? null,
                'signed_at' => AppTime::now(),
                'client_review_id' => $validated['client_review_id'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'handover_summary' => $validated['handover_summary'] ?? null,
                'created_by_user_id' => auth()->id(),
                'approved_by_user_id' => auth()->id(),
            ],
        );

        AuditLogger::log(
            $validated['type'] === 'uat' ? 'uat_signoff_recorded' : 'handover_signoff_recorded',
            'UAT & Sign-off',
            (self::SIGNOFF_TYPE_LABELS[$validated['type']] ?? 'Sign-off') . ' dicatat untuk proyek <strong>' . e($project->name) . '</strong>',
            $signoff,
            null,
            ['project_id' => $project->id, 'type' => $validated['type'], 'client_review_id' => $signoff->client_review_id],
        );

        return $this->backToSignoff($project, (self::SIGNOFF_TYPE_LABELS[$validated['type']] ?? 'Sign-off') . ' berhasil dicatat.');
    }

    public function completeProject(Project $project)
    {
        $this->ensureCanManageUatSignoff($project);
        if ($this->phaseKey((string) $project->phase) === 'done') {
            return $this->backToSignoff($project, 'Project sudah berada pada fase Done.');
        }

        $project->loadMissing('uatItems', 'signoffs');
        $gate = $this->projectSignoffGate($project);

        if (! $gate['can_complete']) {
            return $this->backToSignoff($project, 'Project belum dapat diselesaikan: ' . implode(', ', $gate['missing']));
        }

        $before = ['phase' => $project->phase, 'status' => $project->status, 'progress' => $project->progress];
        $project->forceFill([
            'phase' => 'Done',
            'status' => 'on-track',
            'progress' => $this->projectProgress($project),
        ])->save();

        AuditLogger::log(
            'project_marked_done',
            'UAT & Sign-off',
            'Menyelesaikan proyek <strong>' . e($project->name) . '</strong> setelah UAT dan handover disetujui',
            $project,
            $before,
            ['phase' => $project->phase, 'status' => $project->status, 'progress' => $project->progress],
        );

        return $this->backToSignoff($project, 'Project berhasil ditandai Done.');
    }

    /* ===================== Handover Pack (Milestone 4) ===================== */

    public function generateHandoverPack(Request $request, Project $project)
    {
        $this->ensureCanManageHandover($project);

        if (! in_array($this->phaseKey((string) $project->phase), ['qc', 'done'], true)) {
            return $this->backToHandover($project, 'Draft Handover Pack hanya dapat dibuat setelah project masuk fase QC atau Done.');
        }

        // Reuse the latest editable (non-finalized) pack so repeated "Generate"
        // clicks don't pile up versions; otherwise start a fresh version.
        $pack = $project->handoverPacks()
            ->whereIn('status', ['draft', 'generated'])
            ->orderByDesc('version')
            ->first();

        $isNew = false;
        if (! $pack) {
            $isNew = true;
            $pack = new ProjectHandoverPack([
                'title'   => 'Handover Pack ' . $project->name,
                'version' => ((int) $project->handoverPacks()->max('version')) + 1,
                'credential_handover_status' => 'pending',
            ]);
            $pack->project_id = $project->id;
        }

        $pack->generated_by_user_id = $request->user()?->id;
        $pack->status = 'generated';
        $pack->generated_at = now();
        if (blank($pack->summary)) {
            $pack->summary = (string) ($project->description ?: '');
        }
        $pack->save();

        $this->storeHandoverPackPdf($project, $pack);

        AuditLogger::log(
            'handover_pack_generated',
            'Project Master',
            'Membuat draft Handover Pack v' . $pack->version . ' untuk proyek <strong>' . e($project->name) . '</strong>',
            $pack,
            null,
            ['project_id' => $project->id, 'handover_pack_id' => $pack->id, 'version' => $pack->version],
        );

        return $this->backToHandover(
            $project,
            $isNew
                ? 'Draft Handover Pack v' . $pack->version . ' berhasil dibuat.'
                : 'Draft Handover Pack v' . $pack->version . ' diperbarui dari data terbaru.',
        );
    }

    public function updateHandoverPack(Request $request, Project $project, ProjectHandoverPack $handoverPack)
    {
        $this->ensureCanManageHandover($project);
        abort_unless($handoverPack->project_id === $project->id, 404);

        if (in_array($handoverPack->status, ['finalized', 'revoked'], true)) {
            return $this->backToHandover($project, 'Handover Pack yang sudah final tidak dapat diubah. Generate versi baru bila perlu.');
        }

        $validated = $request->validate([
            'title'                      => ['required', 'string', 'max:180'],
            'summary'                    => ['nullable', 'string', 'max:6000'],
            // max 255 to match the VARCHAR(255) columns (avoid "data too long" on save)
            'deployment_url'             => ['nullable', 'url', 'max:255'],
            'staging_url'                => ['nullable', 'url', 'max:255'],
            'repository_url'             => ['nullable', 'url', 'max:255'],
            'admin_url'                  => ['nullable', 'url', 'max:255'],
            'credential_handover_status' => ['nullable', Rule::in(ProjectHandoverPack::CREDENTIAL_STATUSES)],
            'maintenance_notes'          => ['nullable', 'string', 'max:6000'],
            'client_notes'               => ['nullable', 'string', 'max:6000'],
            'internal_notes'             => ['nullable', 'string', 'max:6000'],
        ], [
            'title.required'             => 'Judul handover pack wajib diisi.',
            'deployment_url.url'         => 'Production URL harus berupa URL valid.',
            'staging_url.url'            => 'Staging URL harus berupa URL valid.',
            'repository_url.url'         => 'Repository URL harus berupa URL valid.',
            'admin_url.url'              => 'Admin URL harus berupa URL valid.',
        ]);

        $handoverPack->fill([
            'title'                      => $validated['title'],
            'summary'                    => $validated['summary'] ?? null,
            'deployment_url'             => $validated['deployment_url'] ?? null,
            'staging_url'                => $validated['staging_url'] ?? null,
            'repository_url'             => $validated['repository_url'] ?? null,
            'admin_url'                  => $validated['admin_url'] ?? null,
            'credential_handover_status' => $validated['credential_handover_status'] ?? null,
            'maintenance_notes'          => $validated['maintenance_notes'] ?? null,
            'client_notes'               => $validated['client_notes'] ?? null,
            'internal_notes'             => $validated['internal_notes'] ?? null,
        ]);
        $handoverPack->save();

        // keep stored PDF in sync with edited notes (so preview reflects them)
        if ($handoverPack->pdf_path) {
            $this->storeHandoverPackPdf($project, $handoverPack);
        }

        AuditLogger::log(
            'handover_pack_updated',
            'Project Master',
            'Memperbarui catatan Handover Pack v' . $handoverPack->version . ' pada proyek <strong>' . e($project->name) . '</strong>',
            $handoverPack,
            ['project_id' => $project->id, 'handover_pack_id' => $handoverPack->id],
            ['project_id' => $project->id, 'handover_pack_id' => $handoverPack->id],
        );

        return $this->backToHandover($project, 'Catatan Handover Pack berhasil disimpan.');
    }

    public function finalizeHandoverPack(Request $request, Project $project, ProjectHandoverPack $handoverPack)
    {
        $this->ensureCanManageHandover($project);
        abort_unless($handoverPack->project_id === $project->id, 404);

        if ($handoverPack->status === 'finalized') {
            return $this->backToHandover($project, 'Handover Pack ini sudah final.');
        }

        $gate = $this->projectSignoffGate($project->loadMissing('uatItems', 'signoffs'));
        if (! $gate['can_complete']) {
            return $this->backToHandover(
                $project,
                'Handover Pack belum dapat difinalisasi. ' . implode(', ', $gate['missing']) . '.',
            );
        }

        $handoverPack->forceFill([
            'status'               => 'finalized',
            'finalized_by_user_id' => $request->user()?->id,
            'finalized_at'         => now(),
            'generated_at'         => $handoverPack->generated_at ?? now(),
        ])->save();

        $this->storeHandoverPackPdf($project, $handoverPack);

        AuditLogger::log(
            'handover_pack_finalized',
            'Project Master',
            'Memfinalisasi Handover Pack v' . $handoverPack->version . ' untuk proyek <strong>' . e($project->name) . '</strong>',
            $handoverPack,
            ['project_id' => $project->id, 'handover_pack_id' => $handoverPack->id, 'status' => 'generated'],
            ['project_id' => $project->id, 'handover_pack_id' => $handoverPack->id, 'status' => 'finalized'],
        );

        return $this->backToHandover($project, 'Handover Pack v' . $handoverPack->version . ' berhasil difinalisasi.');
    }

    public function previewHandoverPack(Project $project, ProjectHandoverPack $handoverPack)
    {
        if ($redirect = $this->ensureOperationalCanAccessProject($project)) {
            return $redirect;
        }
        abort_unless($handoverPack->project_id === $project->id, 404);

        // Always render fresh from current data so preview reflects latest edits.
        $pdf = $this->renderHandoverPack($project, $handoverPack);

        return $pdf->stream($this->handoverPackFilename($project, $handoverPack));
    }

    public function downloadHandoverPack(Project $project, ProjectHandoverPack $handoverPack)
    {
        if ($redirect = $this->ensureOperationalCanAccessProject($project)) {
            return $redirect;
        }
        abort_unless($handoverPack->project_id === $project->id, 404);

        $pdf = $this->renderHandoverPack($project, $handoverPack);

        AuditLogger::log(
            'handover_pack_downloaded',
            'Project Master',
            'Mengunduh Handover Pack v' . $handoverPack->version . ' proyek <strong>' . e($project->name) . '</strong>',
            $handoverPack,
            null,
            ['project_id' => $project->id, 'handover_pack_id' => $handoverPack->id],
        );

        return $pdf->download($this->handoverPackFilename($project, $handoverPack));
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
            ->to(route('projects.show', $project) . '#aiplanning')
            ->with('status', 'Modul WBS "' . $module->title . '" berhasil dibuat.');
    }

    public function updateModule(Request $request, Project $project, ProjectModule $module)
    {
        $this->ensureCanEditProjectDetail();
        if ($redirect = $this->ensureOperationalCanAccessProject($project)) {
            return $redirect;
        }
        abort_unless($module->project_id === $project->id, 404);

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

        $original = $module->getOriginal();
        $module->update([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'status' => $validated['status'],
            'estimate_hours' => (int) ($validated['estimate_hours'] ?? 0),
        ]);

        AuditLogger::log(
            'wbs_module_updated',
            'Project Master',
            'Memperbarui modul WBS <strong>' . e($module->title) . '</strong> pada proyek <strong>' . e($project->name) . '</strong>',
            $module,
            [
                'project_id' => $project->id,
                'title' => $original['title'] ?? null,
                'status' => $original['status'] ?? null,
                'estimate_hours' => $original['estimate_hours'] ?? null,
            ],
            [
                'project_id' => $project->id,
                'title' => $module->title,
                'status' => $module->status,
                'estimate_hours' => $module->estimate_hours,
            ],
        );

        return redirect()
            ->to(route('projects.show', $project) . '#aiplanning')
            ->with('status', 'Modul WBS "' . $module->title . '" berhasil diperbarui.');
    }

    public function destroyModule(Project $project, ProjectModule $module)
    {
        $this->ensureCanEditProjectDetail();
        if ($redirect = $this->ensureOperationalCanAccessProject($project)) {
            return $redirect;
        }
        abort_unless($module->project_id === $project->id, 404);

        if ($module->tasks()->exists()) {
            return redirect()
                ->to(route('projects.show', $project) . '#aiplanning')
                ->with('status', 'Modul masih memiliki task. Hapus atau pindahkan task terlebih dahulu.');
        }

        $title = $module->title;
        $moduleId = $module->id;
        $module->delete();

        AuditLogger::log(
            'wbs_module_deleted',
            'Project Master',
            'Menghapus modul WBS <strong>' . e($title) . '</strong> pada proyek <strong>' . e($project->name) . '</strong>',
            null,
            ['project_id' => $project->id, 'module_id' => $moduleId, 'title' => $title],
            null,
        );

        return redirect()
            ->to(route('projects.show', $project) . '#aiplanning')
            ->with('status', 'Modul WBS "' . $title . '" berhasil dihapus.');
    }

    public function storeTask(Request $request, Project $project)
    {
        $this->ensureCanEditProjectDetail();
        if ($redirect = $this->ensureOperationalCanAccessProject($project)) {
            return $redirect;
        }
        $assigneeIds = $this->projectAssignedUsers($project)->pluck('id')->all();

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
            'assigned_to.in' => 'Assignee harus anggota yang ditugaskan ke project ini.',
            'status.required' => 'Status task wajib dipilih.',
            'status.in' => 'Status task tidak valid.',
            'priority.required' => 'Prioritas task wajib dipilih.',
            'priority.in' => 'Prioritas task tidak valid.',
            'due_date.date' => 'Due date task tidak valid.',
            'estimate_hours.integer' => 'Estimasi jam harus berupa angka.',
        ]);

        if (in_array($this->phaseKey($project->phase), ['planning', 'design'], true) && $validated['status'] !== 'planned') {
            return redirect()
                ->to(route('projects.show', $project) . '#workspace')
                ->with('status', $this->phaseKey($project->phase) === 'design'
                    ? 'Task development aktif setelah handover desain selesai.'
                    : 'Task execution aktif setelah project keluar dari fase Planning.');
        }

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
            'is_design_deliverable' => $project->requires_design && $this->isDesignDeliverableDraft('', $validated['title'], $validated['description'] ?? null),
        ]);

        AuditLogger::log('task_created', 'Project Master', 'Menambah task <strong>' . e($task->title) . '</strong> pada proyek <strong>' . e($project->name) . '</strong>', $task);

        return redirect()
            ->to(route('projects.show', $project) . '#workspace')
            ->with('status', 'Task "' . $task->title . '" berhasil dibuat.');
    }

    public function updateTask(Request $request, Project $project, ProjectTask $task)
    {
        $this->ensureCanEditProjectDetail();
        if ($redirect = $this->ensureOperationalCanAccessProject($project)) {
            return $redirect;
        }
        abort_unless($task->project_id === $project->id, 404);

        $assigneeIds = $this->projectAssignedUsers($project)->pluck('id')->all();

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
            'assigned_to.in' => 'Assignee harus anggota yang ditugaskan ke project ini.',
            'status.required' => 'Status task wajib dipilih.',
            'status.in' => 'Status task tidak valid.',
            'priority.required' => 'Prioritas task wajib dipilih.',
            'priority.in' => 'Prioritas task tidak valid.',
            'due_date.date' => 'Due date task tidak valid.',
            'estimate_hours.integer' => 'Estimasi jam harus berupa angka.',
        ]);

        if ($blockMessage = $this->taskStatusTransitionBlockMessage($project, $task, $validated['status'])) {
            return redirect()
                ->to(route('projects.show', $project) . '#workspace')
                ->with('status', $blockMessage);
        }

        if ($validated['status'] === 'done'
            && (bool) $task->is_design_deliverable
            && ! $this->taskHasValidDesignDeliverable($task)) {
            throw ValidationException::withMessages([
                'status' => 'Tambahkan minimal satu Design Deliverable berisi link Figma/mockup atau PDF sebelum handover desain diselesaikan.',
            ]);
        }

        $original = $task->getOriginal();
        $task->update([
            'title' => $validated['title'],
            'project_module_id' => $validated['project_module_id'] ?? null,
            'assigned_to' => $validated['assigned_to'] ?? null,
            'status' => $validated['status'],
            'priority' => $validated['priority'],
            'due_date' => $validated['due_date'] ?? null,
            'estimate_hours' => (int) ($validated['estimate_hours'] ?? 0),
            'description' => $validated['description'] ?? null,
            'is_design_deliverable' => $task->is_design_deliverable || ($project->requires_design && $this->isDesignDeliverableDraft($task->module?->title ?? '', $validated['title'], $validated['description'] ?? null)),
        ]);

        $action = ((int) ($original['assigned_to'] ?? 0)) !== ((int) ($task->assigned_to ?? 0))
            ? 'task_assigned'
            : 'task_updated';

        AuditLogger::log(
            $action,
            'Project Master',
            'Memperbarui task <strong>' . e($task->title) . '</strong> pada proyek <strong>' . e($project->name) . '</strong>',
            $task,
            [
                'project_id' => $project->id,
                'assigned_to' => $original['assigned_to'] ?? null,
                'status' => $original['status'] ?? null,
                'priority' => $original['priority'] ?? null,
                'estimate_hours' => $original['estimate_hours'] ?? null,
            ],
            [
                'project_id' => $project->id,
                'assigned_to' => $task->assigned_to,
                'status' => $task->status,
                'priority' => $task->priority,
                'estimate_hours' => $task->estimate_hours,
            ],
        );

        $this->transitionProjectPhaseAfterDesignDone($project);
        $this->transitionProjectPhaseAfterDevelopmentDone($project);

        return redirect()
            ->to(route('projects.show', $project) . '#workspace')
            ->with('status', 'Task "' . $task->title . '" berhasil diperbarui.');
    }

    public function destroyTask(Project $project, ProjectTask $task)
    {
        $this->ensureCanEditProjectDetail();
        if ($redirect = $this->ensureOperationalCanAccessProject($project)) {
            return $redirect;
        }
        abort_unless($task->project_id === $project->id, 404);

        $title = $task->title;
        $taskId = $task->id;
        $task->delete();

        AuditLogger::log(
            'task_deleted',
            'Project Master',
            'Menghapus task <strong>' . e($title) . '</strong> pada proyek <strong>' . e($project->name) . '</strong>',
            null,
            ['project_id' => $project->id, 'task_id' => $taskId, 'title' => $title],
            null,
        );

        return redirect()
            ->to(route('projects.show', $project) . '#workspace')
            ->with('status', 'Task "' . $title . '" berhasil dihapus.');
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

        if ($blockMessage = $this->taskStatusTransitionBlockMessage($project, $task, $validated['status'])) {
            return redirect()
                ->to(route('projects.show', $project) . '#workspace')
                ->with('status', $blockMessage);
        }

        if ($validated['status'] === 'done'
            && (bool) $task->is_design_deliverable
            && ! $this->taskHasValidDesignDeliverable($task)) {
            throw ValidationException::withMessages([
                'status' => 'Tambahkan minimal satu Design Deliverable berisi link Figma/mockup atau PDF sebelum handover desain diselesaikan.',
            ]);
        }

        $original = $task->getOriginal();
        $task->update(['status' => $validated['status']]);

        AuditLogger::log('task_status_changed', 'Project Master', 'Mengubah status task <strong>' . e($task->title) . '</strong> menjadi <strong>' . e(self::TASK_STATUS_LABELS[$task->status]) . '</strong>', $task, ['status' => $original['status'] ?? null], ['status' => $task->status]);

        $this->transitionProjectPhaseAfterDesignDone($project);
        $this->transitionProjectPhaseAfterDevelopmentDone($project);

        return redirect()
            ->to(route('projects.show', $project) . '#workspace')
            ->with('status', 'Status task "' . $task->title . '" berhasil diperbarui.');
    }

    public function storeDesignDeliverable(Request $request, Project $project, ProjectTask $task)
    {
        $this->ensureCanEditDesignDeliverables($project, $task);

        $validated = $this->validateDesignDeliverable($request);
        if (! filled($validated['figma_url'] ?? null) && ! $request->hasFile('pdf_file')) {
            throw ValidationException::withMessages([
                'figma_url' => 'Isi link Figma/mockup atau unggah PDF mockup untuk deliverable desain.',
            ]);
        }

        $filePath = $request->hasFile('pdf_file')
            ? $request->file('pdf_file')->store('project-design-deliverables', 'public')
            : null;

        $deliverable = $task->designDeliverables()->create([
            'title' => $validated['title'],
            'figma_url' => $validated['figma_url'] ?? null,
            'pdf_file_path' => $filePath,
            'notes' => $validated['notes'] ?? null,
            'submitted_at' => now(),
            'created_by' => $request->user()?->id,
        ]);

        AuditLogger::log(
            'design_deliverable_created',
            'Project Master',
            'Menambah deliverable desain <strong>' . e($deliverable->title) . '</strong> pada proyek <strong>' . e($project->name) . '</strong>',
            $deliverable,
            null,
            ['project_id' => $project->id, 'task_id' => $task->id, 'has_pdf' => filled($filePath), 'has_url' => filled($deliverable->figma_url)],
        );

        return redirect()
            ->to(route('projects.show', $project) . '#workspace')
            ->with('status', 'Design Deliverable berhasil ditambahkan.');
    }

    public function updateDesignDeliverableRow(Request $request, Project $project, ProjectTask $task, ProjectTaskDesignDeliverable $deliverable)
    {
        $this->ensureCanEditDesignDeliverables($project, $task, $deliverable);

        $validated = $this->validateDesignDeliverable($request);
        $filePath = $deliverable->pdf_file_path;

        if ($request->hasFile('pdf_file')) {
            if ($filePath) {
                Storage::disk('public')->delete($filePath);
            }
            $filePath = $request->file('pdf_file')->store('project-design-deliverables', 'public');
        }

        if (! filled($validated['figma_url'] ?? null) && ! filled($filePath)) {
            throw ValidationException::withMessages([
                'figma_url' => 'Deliverable harus memiliki link Figma/mockup atau PDF mockup.',
            ]);
        }

        $original = $deliverable->getOriginal();
        $deliverable->update([
            'title' => $validated['title'],
            'figma_url' => $validated['figma_url'] ?? null,
            'pdf_file_path' => $filePath,
            'notes' => $validated['notes'] ?? null,
            'submitted_at' => now(),
        ]);

        AuditLogger::log(
            'design_deliverable_updated',
            'Project Master',
            'Memperbarui deliverable desain <strong>' . e($deliverable->title) . '</strong> pada proyek <strong>' . e($project->name) . '</strong>',
            $deliverable,
            $original,
            ['project_id' => $project->id, 'task_id' => $task->id, 'has_pdf' => filled($filePath), 'has_url' => filled($deliverable->figma_url)],
        );

        return redirect()
            ->to(route('projects.show', $project) . '#workspace')
            ->with('status', 'Design Deliverable berhasil diperbarui.');
    }

    public function destroyDesignDeliverable(Project $project, ProjectTask $task, ProjectTaskDesignDeliverable $deliverable)
    {
        $this->ensureCanEditDesignDeliverables($project, $task, $deliverable);

        $old = [
            'project_id' => $project->id,
            'task_id' => $task->id,
            'title' => $deliverable->title,
            'has_pdf' => filled($deliverable->pdf_file_path),
            'has_url' => filled($deliverable->figma_url),
        ];

        if ($deliverable->pdf_file_path) {
            Storage::disk('public')->delete($deliverable->pdf_file_path);
        }

        $deliverable->delete();

        AuditLogger::log(
            'design_deliverable_deleted',
            'Project Master',
            'Menghapus deliverable desain <strong>' . e($old['title']) . '</strong> pada proyek <strong>' . e($project->name) . '</strong>',
            null,
            $old,
            null,
        );

        return redirect()
            ->to(route('projects.show', $project) . '#workspace')
            ->with('status', 'Design Deliverable berhasil dihapus.');
    }

    public function previewDesignDeliverable(Project $project, ProjectTask $task, ProjectTaskDesignDeliverable $deliverable)
    {
        if ($redirect = $this->ensureOperationalCanAccessProject($project)) {
            return $redirect;
        }

        $this->ensureDesignDeliverableBelongsToTask($project, $task, $deliverable);

        $filename = $this->designDeliverablePdfFilename($deliverable);
        $path = $this->designDeliverablePdfPath($deliverable);
        $maxPreviewBytes = 10 * 1024 * 1024;
        $pdfDataUri = null;
        $previewMessage = null;

        if ((filesize($path) ?: 0) <= $maxPreviewBytes) {
            $pdfDataUri = 'data:application/pdf;base64,' . base64_encode(file_get_contents($path));
        } else {
            $previewMessage = 'File PDF terlalu besar untuk preview internal. Gunakan Download PDF untuk membuka file secara manual.';
        }

        return view('projects.design-deliverables.preview', [
            'title' => 'Preview PDF Mockup',
            'project' => $project,
            'task' => $task,
            'deliverable' => $deliverable,
            'filename' => $filename . '.pdf',
            'pdfDataUri' => $pdfDataUri,
            'previewMessage' => $previewMessage,
            'downloadUrl' => route('projects.tasks.design-deliverables.download', [$project, $task, $deliverable]),
        ]);
    }

    public function downloadDesignDeliverable(Project $project, ProjectTask $task, ProjectTaskDesignDeliverable $deliverable)
    {
        return $this->designDeliverableDownloadResponse($project, $task, $deliverable);
    }

    private function designDeliverableDownloadResponse(Project $project, ProjectTask $task, ProjectTaskDesignDeliverable $deliverable)
    {
        if ($redirect = $this->ensureOperationalCanAccessProject($project)) {
            return $redirect;
        }

        $this->ensureDesignDeliverableBelongsToTask($project, $task, $deliverable);

        $filename = $this->designDeliverablePdfFilename($deliverable);
        $path = $this->designDeliverablePdfPath($deliverable);

        return response()->download($path, $filename . '.pdf', [
            'Content-Type' => 'application/pdf',
        ]);
    }

    private function designDeliverablePdfFilename(ProjectTaskDesignDeliverable $deliverable): string
    {
        $filename = (string) str($deliverable->title)->slug('-');

        return $filename !== '' ? $filename : 'design-deliverable';
    }

    private function designDeliverablePdfPath(ProjectTaskDesignDeliverable $deliverable): string
    {
        $storagePath = str_replace('\\', '/', ltrim((string) $deliverable->pdf_file_path, '/'));

        abort_unless(
            filled($storagePath)
            && str_starts_with($storagePath, 'project-design-deliverables/')
            && Storage::disk('public')->exists($storagePath),
            404,
        );

        return Storage::disk('public')->path($storagePath);
    }

    public function updateDesignDeliverable(Request $request, Project $project, ProjectTask $task)
    {
        $this->ensureCanEditDesignDeliverables($project, $task);

        $validated = $request->validate([
            'deliverable_url' => ['nullable', 'url', 'max:2048'],
            'deliverable_file' => ['nullable', 'file', 'mimes:pdf', 'max:10240'],
        ], [
            'deliverable_url.url' => 'Link Figma/mockup harus berupa URL valid.',
            'deliverable_file.mimes' => 'File handover desain harus berupa PDF.',
            'deliverable_file.max' => 'File PDF maksimal 10MB.',
        ]);

        $original = $task->getOriginal();
        $filePath = $task->deliverable_file_path;
        if ($request->hasFile('deliverable_file')) {
            if ($filePath) {
                Storage::disk('public')->delete($filePath);
            }
            $filePath = $request->file('deliverable_file')->store('project-design-deliverables', 'public');
        }

        $hasDeliverable = filled($validated['deliverable_url'] ?? null) || filled($filePath);
        $task->forceFill([
            'deliverable_url' => $validated['deliverable_url'] ?? null,
            'deliverable_file_path' => $filePath,
            'deliverable_type' => $hasDeliverable ? 'uiux_mockup' : null,
            'deliverable_submitted_at' => $hasDeliverable ? now() : null,
        ])->save();

        AuditLogger::log(
            'design_deliverable_updated',
            'Project Master',
            'Memperbarui handover desain untuk task <strong>' . e($task->title) . '</strong> pada proyek <strong>' . e($project->name) . '</strong>',
            $task,
            [
                'project_id' => $project->id,
                'deliverable_url' => $original['deliverable_url'] ?? null,
                'deliverable_file_path' => $original['deliverable_file_path'] ?? null,
            ],
            [
                'project_id' => $project->id,
                'deliverable_url' => $task->deliverable_url,
                'has_deliverable_file' => filled($task->deliverable_file_path),
            ],
        );

        return redirect()
            ->to(route('projects.show', $project) . '#workspace')
            ->with('status', 'Handover desain berhasil diperbarui.');
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

    public function updateMomSummary(Request $request, Project $project, ProjectMom $mom)
    {
        $this->ensureCanEditProjectDetail();
        if ($redirect = $this->ensureOperationalCanAccessProject($project)) {
            return $redirect;
        }
        abort_unless($mom->project_id === $project->id, 404);

        $validated = $request->validate([
            'summary' => ['nullable', 'string', 'max:12000'],
        ], [
            'summary.max' => 'Ringkasan MoM terlalu panjang.',
        ]);

        $mom->update([
            'summary' => $validated['summary'] ?? null,
            'status'  => 'manual_updated',
        ]);

        AuditLogger::log(
            'mom_summary_updated',
            'Project Master',
            'Memperbarui ringkasan MoM pada proyek <strong>' . e($project->name) . '</strong>',
            $mom,
            null,
            ['project_id' => $project->id, 'mom_id' => $mom->id],
        );

        return redirect()
            ->to(route('projects.show', $project) . '#aiplanning')
            ->with('status', 'Ringkasan MoM berhasil diperbarui.');
    }

    public function fixLatestMom(Request $request, Project $project)
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

        if (! $latestMom || trim((string) $latestMom->notes) === '') {
            return redirect()->to($backUrl)
                ->with('status', 'Tambahkan MoM terlebih dahulu sebelum memakai AI MoM Fixer.');
        }

        if (! AiPlanner::isConfigured()) {
            return redirect()->to($backUrl)
                ->with('status', 'AI belum dikonfigurasi.');
        }

        $project->loadMissing('client');

        $result = AiPlanner::generateMomSummary([
            'user_id'             => $request->user()?->id,
            'project_id'          => $project->id,
            'project_name'        => $project->name,
            'project_code'        => $project->code,
            'project_description' => (string) ($project->description ?: ''),
            'project_client'      => (string) ($project->client?->name ?: ''),
            'mom_date'            => optional($latestMom->meeting_date)->format('Y-m-d'),
            'mom_notes'           => (string) $latestMom->notes,
        ]);

        if (! ($result['ok'] ?? false)) {
            return redirect()->to($backUrl)
                ->with('status', 'AI MoM Fixer gagal: ' . ($result['error'] ?? 'tidak diketahui.'));
        }

        $formatted = trim((string) ($result['data']['formatted'] ?? ''));
        if ($formatted === '') {
            return redirect()->to($backUrl)
                ->with('status', 'AI MoM Fixer gagal: hasil ringkasan kosong.');
        }

        /* HITL: jangan tulis ke DB di sini. Tampilkan draf editable lebih dulu;
         * penyimpanan + audit terjadi di applyMomFix() setelah user konfirmasi.
         * AI metadata sudah dicatat AiPlanner saat request (tetap metadata-only). */
        return redirect()->to($backUrl)
            ->with('ai_mom_preview', [
                'mom_id'       => $latestMom->id,
                'meeting_date' => optional($latestMom->meeting_date)->format('d M Y'),
                'summary'      => $formatted,
                'provider'     => $result['provider'] ?? null,
            ])
            ->with('status', 'Draf ringkasan AI siap ditinjau. Sunting bila perlu, lalu klik Simpan.');
    }

    /**
     * HITL confirm step for AI MoM Fixer. Saves the (possibly user-edited)
     * summary only after explicit user action. Audit logged here, not at preview.
     */
    public function applyMomFix(Request $request, Project $project)
    {
        $this->ensureCanEditProjectDetail();
        if ($redirect = $this->ensureOperationalCanAccessProject($project)) {
            return $redirect;
        }

        $backUrl = route('projects.show', $project) . '#aiplanning';

        $validated = $request->validate([
            'mom_id'  => ['required', Rule::exists('project_moms', 'id')->where('project_id', $project->id)],
            'summary' => ['required', 'string', 'max:12000'],
        ], [
            'mom_id.required'  => 'MoM target tidak valid.',
            'mom_id.exists'    => 'MoM target tidak ditemukan pada proyek ini.',
            'summary.required' => 'Ringkasan tidak boleh kosong saat menyimpan.',
            'summary.max'      => 'Ringkasan MoM terlalu panjang.',
        ]);

        $mom = $project->moms()->whereKey($validated['mom_id'])->firstOrFail();

        try {
            DB::transaction(function () use ($mom, $validated) {
                $mom->update([
                    'summary' => $validated['summary'],
                    'status'  => 'ai_fixed',
                ]);
            });
        } catch (\Throwable $e) {
            report($e);
            return redirect()->to($backUrl)
                ->with('status', 'Gagal menyimpan ringkasan MoM: ' . $e->getMessage());
        }

        AuditLogger::log(
            'ai_mom_fixed',
            'Project Master',
            'Menyimpan ringkasan MoM hasil AI (ditinjau pengguna) pada proyek <strong>' . e($project->name) . '</strong>',
            $mom,
            null,
            ['project_id' => $project->id, 'mom_id' => $mom->id],
        );

        return redirect()->to($backUrl)
            ->with('status', 'Ringkasan MoM hasil AI berhasil disimpan.');
    }

    public function generateWbsFromMom(Request $request, Project $project)
    {
        $this->ensureCanEditProjectDetail();
        if ($redirect = $this->ensureOperationalCanAccessProject($project)) {
            return $redirect;
        }

        $backUrl = route('projects.show', $project) . '#aiplanning';

        // Explicit source selection: the user may choose which MoM to use as the
        // WBS source (meeting history). Default to the latest MoM when none chosen.
        $validated = $request->validate([
            'source_mom_id' => ['nullable', Rule::exists('project_moms', 'id')->where('project_id', $project->id)],
        ], [
            'source_mom_id.exists' => 'MoM sumber tidak ditemukan pada proyek ini.',
        ]);

        $sourceMom = ! empty($validated['source_mom_id'])
            ? $project->moms()->whereKey($validated['source_mom_id'])->first()
            : $project->moms()->orderByDesc('meeting_date')->orderByDesc('id')->first();

        if (! $sourceMom) {
            return redirect()->to($backUrl)
                ->with('status', 'Buat MoM terlebih dahulu sebelum menggunakan AI WBS Generator.');
        }

        if (! AiPlanner::isConfigured()) {
            return redirect()->to($backUrl)
                ->with('status', 'AI belum dikonfigurasi.');
        }

        $project->loadMissing('client');
        $existingModuleTitles = $project->modules()->pluck('title')->all();
        $existingTaskTitles = $project->tasks()->pluck('title')->all();

        $context = [
            'user_id'             => $request->user()?->id,
            'project_id'          => $project->id,
            'project_name'        => $project->name,
            'project_code'        => $project->code,
            'project_description' => (string) ($project->description ?: ''),
            'project_client'      => (string) ($project->client?->name ?: ''),
            'project_phase'       => (string) ($project->phase ?: ''),
            'project_status'      => (string) ($project->status ?: ''),
            'requires_design'     => (bool) $project->requires_design,
            'existing_module_titles' => $existingModuleTitles,
            'existing_task_titles'   => $existingTaskTitles,
            'mom_date'           => optional($sourceMom->meeting_date)->format('Y-m-d'),
            'mom_summary'        => (string) ($sourceMom->summary ?: ''),
            'mom_notes'          => (string) $sourceMom->notes,
        ];

        $result = AiPlanner::generateWbsDraft($context);

        if (! ($result['ok'] ?? false)) {
            return redirect()->to($backUrl)
                ->with('status', 'Generator AI gagal: ' . ($result['error'] ?? 'tidak diketahui.'));
        }

        $modules = $result['data']['modules'] ?? [];
        if (empty($modules)) {
            return redirect()->to($backUrl)
                ->with('status', 'AI belum menghasilkan draf WBS yang bisa ditinjau.');
        }

        /* HITL: jangan tulis ke DB di sini. Tampilkan draf editable lebih dulu;
         * penyimpanan + audit terjadi di applyWbs() setelah user memilih & konfirmasi.
         * AI metadata sudah dicatat AiPlanner saat request (tetap metadata-only). */
        return redirect()->to($backUrl)
            ->with('ai_wbs_preview', [
                'modules'         => $modules,
                'source_mom_id'   => $sourceMom->id,
                'source_mom_date' => optional($sourceMom->meeting_date)->format('d M Y'),
                'provider'        => $result['provider'] ?? null,
            ])
            ->with('status', 'Draf WBS AI siap ditinjau. Sunting/pilih item, lalu klik Simpan.');
    }

    /**
     * HITL confirm step for AI WBS Generator. Persists only the included,
     * user-edited modules/tasks. Skip-duplicate logic preserved. Audit here.
     */
    public function applyWbs(Request $request, Project $project)
    {
        $this->ensureCanEditProjectDetail();
        if ($redirect = $this->ensureOperationalCanAccessProject($project)) {
            return $redirect;
        }

        $backUrl = route('projects.show', $project) . '#aiplanning';

        $validated = $request->validate([
            'source_mom_id'                 => ['nullable', Rule::exists('project_moms', 'id')->where('project_id', $project->id)],
            'modules'                       => ['required', 'array', 'min:1'],
            'modules.*.include'             => ['nullable'],
            'modules.*.title'               => ['nullable', 'string', 'max:180'],
            'modules.*.description'         => ['nullable', 'string', 'max:2000'],
            'modules.*.status'              => ['nullable', Rule::in(AiPlanner::ALLOWED_MODULE_STATUS)],
            'modules.*.estimate_hours'      => ['nullable', 'integer', 'min:0', 'max:999'],
            'modules.*.tasks'               => ['nullable', 'array'],
            'modules.*.tasks.*.include'     => ['nullable'],
            'modules.*.tasks.*.title'       => ['nullable', 'string', 'max:180'],
            'modules.*.tasks.*.description' => ['nullable', 'string', 'max:2000'],
            'modules.*.tasks.*.priority'    => ['nullable', Rule::in(AiPlanner::ALLOWED_TASK_PRIORITY)],
            'modules.*.tasks.*.estimate_hours' => ['nullable', 'integer', 'min:0', 'max:999'],
        ], [
            'source_mom_id.exists' => 'MoM sumber tidak ditemukan pada proyek ini.',
        ]);

        $existingModuleByLower = $project->modules()
            ->get(['id', 'title'])
            ->mapWithKeys(fn (ProjectModule $module) => [mb_strtolower($module->title) => $module->id])
            ->all();

        $existingTasksByModule = $project->tasks()
            ->get(['id', 'project_module_id', 'title'])
            ->groupBy('project_module_id')
            ->map(fn ($tasks) => $tasks->pluck('title')->map(fn ($title) => mb_strtolower((string) $title))->flip()->all())
            ->all();

        $maxModuleSort = (int) $project->modules()->max('sort_order');
        $maxTaskSort   = (int) $project->tasks()->max('sort_order');

        $createdModules = 0;
        $createdTasks   = 0;
        $draftModules = $project->requires_design
            ? $this->ensureDesignWbsDraft($validated['modules'])
            : $validated['modules'];
        $designAssigneeId = $project->requires_design ? $this->projectDesignAssigneeId($project) : null;

        try {
            DB::transaction(function () use (
                $project,
                $draftModules,
                $designAssigneeId,
                &$existingModuleByLower,
                &$existingTasksByModule,
                &$maxModuleSort,
                &$maxTaskSort,
                &$createdModules,
                &$createdTasks,
            ) {
                foreach ($draftModules as $modDraft) {
                    // Only persist modules the user kept checked.
                    if (! filter_var($modDraft['include'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
                        continue;
                    }
                    $title = trim((string) ($modDraft['title'] ?? ''));
                    $titleLower = mb_strtolower($title);
                    if ($titleLower === '') {
                        continue;
                    }

                    $moduleId = $existingModuleByLower[$titleLower] ?? null;
                    if ($moduleId) {
                        $module = ProjectModule::find($moduleId);
                        if (! $module || $module->project_id !== $project->id) {
                            continue;
                        }
                    } else {
                        $maxModuleSort++;
                        $module = $project->modules()->create([
                            'title'          => $title,
                            'description'    => $modDraft['description'] ?? null,
                            'status'         => $modDraft['status'] ?? AiPlanner::DEFAULT_MODULE_STATUS,
                            'estimate_hours' => (int) ($modDraft['estimate_hours'] ?? 0),
                            'sort_order'     => $maxModuleSort,
                        ]);
                        $createdModules++;
                        $existingModuleByLower[$titleLower] = $module->id;
                    }

                    $existingTaskByLower = $existingTasksByModule[$module->id] ?? [];
                    foreach (($modDraft['tasks'] ?? []) as $taskDraft) {
                        if (! filter_var($taskDraft['include'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
                            continue;
                        }
                        $tTitle = trim((string) ($taskDraft['title'] ?? ''));
                        $tTitleLower = mb_strtolower($tTitle);
                        if ($tTitleLower === '' || isset($existingTaskByLower[$tTitleLower])) {
                            continue;
                        }
                        $maxTaskSort++;
                        $isDesignDeliverable = $this->isDesignDeliverableDraft($module->title, $tTitle, $taskDraft['description'] ?? null);
                        $project->tasks()->create([
                            'project_module_id' => $module->id,
                            'assigned_to'       => $isDesignDeliverable ? $designAssigneeId : null,
                            'title'             => $tTitle,
                            'description'       => $taskDraft['description'] ?? null,
                            'status'            => AiPlanner::DEFAULT_TASK_STATUS,
                            'priority'          => $taskDraft['priority'] ?? AiPlanner::DEFAULT_TASK_PRIORITY,
                            'estimate_hours'    => (int) ($taskDraft['estimate_hours'] ?? 0),
                            'sort_order'        => $maxTaskSort,
                            'is_design_deliverable' => $isDesignDeliverable,
                        ]);
                        $createdTasks++;
                        $existingTaskByLower[$tTitleLower] = true;
                    }
                    $existingTasksByModule[$module->id] = $existingTaskByLower;
                }
            });
        } catch (\Throwable $e) {
            report($e);
            return redirect()->to($backUrl)
                ->with('status', 'Gagal menyimpan draf WBS: ' . $e->getMessage());
        }

        if ($createdModules === 0 && $createdTasks === 0) {
            return redirect()->to($backUrl)
                ->with('status', 'Tidak ada draf WBS yang disimpan (tidak ada item dipilih atau judul sudah tersedia).');
        }

        if (! $project->ai_wbs_generated) {
            $project->forceFill(['ai_wbs_generated' => true])->save();
        }

        $this->transitionProjectPhaseAfterWbs($project);

        AuditLogger::log(
            'ai_wbs_generated',
            'Project Master',
            'Menyimpan WBS hasil AI (ditinjau pengguna): <strong>' . $createdModules . '</strong> modul + <strong>' . $createdTasks . '</strong> task pada proyek <strong>' . e($project->name) . '</strong>',
            $project,
            null,
            [
                'project_id'    => $project->id,
                'module_count'  => $createdModules,
                'task_count'    => $createdTasks,
                'source_mom_id' => $validated['source_mom_id'] ?? null,
            ],
        );

        return redirect()->to($backUrl)
            ->with('status', 'WBS hasil AI berhasil disimpan: ' . $createdModules . ' modul, ' . $createdTasks . ' task.');
    }

    public function generateTestCases(Request $request, Project $project)
    {
        $this->ensureCanEditProjectDetail();
        if ($redirect = $this->ensureOperationalCanAccessProject($project)) {
            return $redirect;
        }

        $backUrl = route('projects.show', $project) . '#qc';

        if (! $this->canPrepareQc($project)) {
            return redirect()->to($backUrl)
                ->with('status', 'AI Test Case Generator tersedia setelah project masuk fase Development.');
        }

        $project->loadMissing([
            'client',
            'modules' => fn ($query) => $query->with('tasks')->orderBy('sort_order')->orderBy('id'),
            'tasks' => fn ($query) => $query->with('module')->orderBy('sort_order')->orderBy('id'),
            'qcTests',
        ]);

        if ($project->modules->isEmpty() && $project->tasks->isEmpty()) {
            return redirect()->to($backUrl)
                ->with('status', 'Buat atau simpan WBS terlebih dahulu sebelum menggunakan AI Test Case Generator.');
        }

        if (! AiPlanner::isConfigured()) {
            return redirect()->to($backUrl)
                ->with('status', 'AI belum dikonfigurasi.');
        }

        // Explicit source scope: the user may choose which WBS module to use as the
        // test-case source. Default to the first available module; if none selected
        // and no module exists, fall back to whole-project task context.
        $validated = $request->validate([
            'source_module_id' => ['nullable', Rule::exists('project_modules', 'id')->where('project_id', $project->id)],
        ], [
            'source_module_id.exists' => 'Modul sumber tidak ditemukan pada proyek ini.',
        ]);

        $sourceModule = null;
        if (! empty($validated['source_module_id'])) {
            $sourceModule = $project->modules->firstWhere('id', (int) $validated['source_module_id']);
        }

        // Restrict the AI context to the chosen module (and its tasks) when set.
        $modulesForContext = $sourceModule ? collect([$sourceModule]) : $project->modules;
        $tasksForContext = $sourceModule
            ? $project->tasks->where('project_module_id', $sourceModule->id)->values()
            : $project->tasks;

        if ($sourceModule && $modulesForContext->isEmpty() && $tasksForContext->isEmpty()) {
            return redirect()->to($backUrl)
                ->with('status', 'Modul yang dipilih belum memiliki konteks untuk generate test case.');
        }

        $moduleContext = $modulesForContext
            ->map(function (ProjectModule $module) {
                $taskTitles = $module->tasks
                    ->pluck('title')
                    ->filter()
                    ->values()
                    ->take(6)
                    ->implode('; ');

                return trim($module->title
                    . ($module->description ? ' - ' . $module->description : '')
                    . ($taskTitles ? ' | Task: ' . $taskTitles : ''));
            })
            ->filter()
            ->values()
            ->all();

        $taskContext = $tasksForContext
            ->map(function (ProjectTask $task) {
                return trim(($task->module?->title ? '[' . $task->module->title . '] ' : '')
                    . $task->title
                    . ($task->description ? ' - ' . $task->description : '')
                    . ' | status: ' . ($task->status ?: 'planned')
                    . ' | priority: ' . ($task->priority ?: 'medium'));
            })
            ->filter()
            ->values()
            ->all();

        $context = [
            'user_id'             => $request->user()?->id,
            'project_id'          => $project->id,
            'project_name'        => $project->name,
            'project_code'        => $project->code,
            'project_description' => (string) ($project->description ?: ''),
            'project_client'      => (string) ($project->client?->name ?: ''),
            'module_context'      => $moduleContext,
            'task_context'        => $taskContext,
            'existing_qc_titles'  => $project->qcTests->pluck('title')->filter()->values()->all(),
        ];

        $result = AiPlanner::generateTestCaseDraft($context);

        if (! ($result['ok'] ?? false)) {
            return redirect()->to($backUrl)
                ->with('status', 'Generator AI gagal: ' . ($result['error'] ?? 'tidak diketahui.'));
        }

        $testCases = $result['data']['test_cases'] ?? [];
        if (empty($testCases)) {
            return redirect()->to($backUrl)
                ->with('status', 'AI belum menghasilkan draf test case yang bisa ditinjau.');
        }

        /* HITL: jangan tulis ke DB di sini. Tampilkan draf editable lebih dulu;
         * penyimpanan + audit terjadi di applyTestCases() setelah user memilih & konfirmasi.
         * AI metadata sudah dicatat AiPlanner saat request (tetap metadata-only). */
        return redirect()->to($backUrl)
            ->with('ai_testcase_preview', [
                'test_cases'         => $testCases,
                'source_module_id'   => $sourceModule?->id,
                'source_module_name' => $sourceModule?->title,
                'provider'           => $result['provider'] ?? null,
            ])
            ->with('status', 'Draf test case AI siap ditinjau. Sunting/pilih item, lalu klik Simpan.');
    }

    /**
     * HITL confirm step for AI Test Case Generator. Persists only the included,
     * user-edited test cases. Skip-duplicate logic preserved. Audit here.
     */
    public function applyTestCases(Request $request, Project $project)
    {
        $this->ensureCanEditProjectDetail();
        if ($redirect = $this->ensureOperationalCanAccessProject($project)) {
            return $redirect;
        }

        $backUrl = route('projects.show', $project) . '#qc';

        if (! $this->canPrepareQc($project)) {
            return redirect()->to($backUrl)
                ->with('status', 'Test case AI baru dapat disimpan setelah project masuk fase Development.');
        }

        $validated = $request->validate([
            'source_module_id'               => ['nullable', Rule::exists('project_modules', 'id')->where('project_id', $project->id)],
            'test_cases'                     => ['required', 'array', 'min:1'],
            'test_cases.*.include'           => ['nullable'],
            'test_cases.*.title'             => ['nullable', 'string', 'max:180'],
            'test_cases.*.scenario'          => ['nullable', 'string', 'max:4000'],
            'test_cases.*.expected_result'   => ['nullable', 'string', 'max:4000'],
            'test_cases.*.priority'          => ['nullable', Rule::in(AiPlanner::ALLOWED_TASK_PRIORITY)],
            'test_cases.*.project_module_id' => ['nullable', Rule::exists('project_modules', 'id')->where('project_id', $project->id)],
        ], [
            'source_module_id.exists' => 'Modul sumber tidak ditemukan pada proyek ini.',
        ]);

        $existingTitles = $project->qcTests()
            ->pluck('title')
            ->map(fn ($title) => mb_strtolower((string) $title))
            ->filter()
            ->flip()
            ->all();

        $createdCases = 0;
        $moduleIdsUsed = [];

        try {
            DB::transaction(function () use (
                $project,
                $request,
                $validated,
                &$existingTitles,
                &$createdCases,
                &$moduleIdsUsed,
            ) {
                foreach ($validated['test_cases'] as $caseDraft) {
                    if (! filter_var($caseDraft['include'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
                        continue;
                    }
                    $title = trim((string) ($caseDraft['title'] ?? ''));
                    $scenario = trim((string) ($caseDraft['scenario'] ?? ''));
                    $titleLower = mb_strtolower($title);
                    if ($title === '' || $scenario === '' || isset($existingTitles[$titleLower])) {
                        continue;
                    }

                    $qc = $project->qcTests()->create([
                        'project_module_id' => $caseDraft['project_module_id'] ?? null,
                        'project_task_id'   => null,
                        'created_by'        => $request->user()?->id,
                        'title'             => $title,
                        'scenario'          => $scenario,
                        'expected_result'   => $caseDraft['expected_result'] ?? null,
                        'status'            => 'pending',
                        'priority'          => $caseDraft['priority'] ?? AiPlanner::DEFAULT_TASK_PRIORITY,
                    ]);

                    $createdCases++;
                    $existingTitles[$titleLower] = true;
                    if ($qc->project_module_id) {
                        $moduleIdsUsed[$qc->project_module_id] = true;
                    }
                }
            });
        } catch (\Throwable $e) {
            report($e);
            return redirect()->to($backUrl)
                ->with('status', 'Gagal menyimpan test case: ' . $e->getMessage());
        }

        if ($createdCases === 0) {
            return redirect()->to($backUrl)
                ->with('status', 'Tidak ada test case yang disimpan (tidak ada item dipilih atau judul sudah tersedia).');
        }

        AuditLogger::log(
            'ai_test_cases_generated',
            'Project Master',
            'Menyimpan test case hasil AI (ditinjau pengguna): <strong>' . $createdCases . '</strong> test case pada proyek <strong>' . e($project->name) . '</strong>',
            $project,
            null,
            [
                'project_id'       => $project->id,
                'test_case_count'  => $createdCases,
                'module_count'     => count($moduleIdsUsed),
                'source_module_id' => $validated['source_module_id'] ?? null,
            ],
        );

        return redirect()->to($backUrl)
            ->with('status', 'Test case hasil AI berhasil disimpan: ' . $createdCases . ' test case.');
    }

    public function storeQcTest(Request $request, Project $project)
    {
        $this->ensureCanEditProjectDetail();
        if ($redirect = $this->ensureOperationalCanAccessProject($project)) {
            return $redirect;
        }

        $validated = $request->validate([
            'title'             => ['required', 'string', 'max:180'],
            'scenario'          => ['required', 'string', 'max:4000'],
            'expected_result'   => ['nullable', 'string', 'max:4000'],
            'project_module_id' => ['nullable', Rule::exists('project_modules', 'id')->where('project_id', $project->id)],
            'project_task_id'   => ['nullable', Rule::exists('project_tasks', 'id')->where('project_id', $project->id)],
            'priority'          => ['required', Rule::in(array_keys(self::QC_PRIORITY_LABELS))],
        ], [
            'title.required'    => 'Judul test case wajib diisi.',
            'scenario.required' => 'Skenario test case wajib diisi.',
            'priority.required' => 'Prioritas wajib dipilih.',
            'priority.in'       => 'Prioritas tidak valid.',
        ]);

        $qc = $project->qcTests()->create([
            'project_module_id' => $validated['project_module_id'] ?? null,
            'project_task_id'   => $validated['project_task_id'] ?? null,
            'created_by'        => $request->user()?->id,
            'title'             => $validated['title'],
            'scenario'          => $validated['scenario'],
            'expected_result'   => $validated['expected_result'] ?? null,
            'status'            => 'pending',
            'priority'          => $validated['priority'],
        ]);

        AuditLogger::log(
            'qc_created',
            'Project Master',
            'Menambah QC <strong>' . e($qc->title) . '</strong> pada proyek <strong>' . e($project->name) . '</strong>',
            $qc,
            null,
            ['project_id' => $project->id, 'qc_test_id' => $qc->id, 'priority' => $qc->priority],
        );

        return redirect()
            ->to(route('projects.show', $project) . '#qc')
            ->with('status', 'Test case "' . $qc->title . '" berhasil dibuat.');
    }

    public function updateQcTest(Request $request, Project $project, ProjectQcTest $qc)
    {
        $this->ensureCanEditProjectDetail();
        if ($redirect = $this->ensureOperationalCanAccessProject($project)) {
            return $redirect;
        }
        abort_unless($qc->project_id === $project->id, 404);

        if (! $this->canExecuteQc($project)) {
            return redirect()
                ->to(route('projects.show', $project) . '#qc')
                ->with('status', 'Eksekusi QC baru dapat dilakukan setelah project masuk fase QC.');
        }

        $validated = $request->validate([
            'status'        => ['required', Rule::in(array_keys(self::QC_STATUS_LABELS))],
            'actual_result' => ['nullable', 'string', 'max:4000'],
            'notes'         => ['nullable', 'string', 'max:4000'],
        ], [
            'status.required' => 'Status QC wajib dipilih.',
            'status.in'       => 'Status QC tidak valid.',
        ]);

        $original = $qc->getOriginal();
        $newStatus = $validated['status'];

        $qc->fill([
            'status'        => $newStatus,
            'actual_result' => $validated['actual_result'] ?? $qc->actual_result,
            'notes'         => $validated['notes'] ?? $qc->notes,
        ]);

        /* Stamp tested_at when the row reaches a terminal verdict.
         * Retest clears it so the next pass/fail records a fresh timestamp. */
        if (in_array($newStatus, ['passed', 'failed'], true)) {
            $qc->tested_at = now();
        } elseif ($newStatus === 'retest') {
            $qc->tested_at = null;
        }

        $qc->save();

        AuditLogger::log(
            'qc_status_updated',
            'Project Master',
            'Mengubah status QC <strong>' . e($qc->title) . '</strong> menjadi <strong>' . e(self::QC_STATUS_LABELS[$newStatus]) . '</strong>',
            $qc,
            ['project_id' => $project->id, 'qc_test_id' => $qc->id, 'status' => $original['status'] ?? null],
            ['project_id' => $project->id, 'qc_test_id' => $qc->id, 'status' => $qc->status],
        );

        return redirect()
            ->to(route('projects.show', $project) . '#qc')
            ->with('status', 'Status QC "' . $qc->title . '" diperbarui ke ' . self::QC_STATUS_LABELS[$newStatus] . '.');
    }

    /**
     * Manual full edit of a QC/Test Case record (judul, skenario, expected
     * result, modul, prioritas). Status tetap dikelola lewat tombol
     * Lulus/Gagal/Retest (updateQcTest) agar alur verifikasi tidak berubah.
     */
    public function editQcTest(Request $request, Project $project, ProjectQcTest $qc)
    {
        $this->ensureCanEditProjectDetail();
        if ($redirect = $this->ensureOperationalCanAccessProject($project)) {
            return $redirect;
        }
        abort_unless($qc->project_id === $project->id, 404);

        $validated = $request->validate([
            'title'             => ['required', 'string', 'max:180'],
            'scenario'          => ['required', 'string', 'max:4000'],
            'expected_result'   => ['nullable', 'string', 'max:4000'],
            'project_module_id' => ['nullable', Rule::exists('project_modules', 'id')->where('project_id', $project->id)],
            'priority'          => ['required', Rule::in(array_keys(self::QC_PRIORITY_LABELS))],
        ], [
            'title.required'    => 'Judul test case wajib diisi.',
            'scenario.required' => 'Skenario test case wajib diisi.',
            'priority.required' => 'Prioritas wajib dipilih.',
            'priority.in'       => 'Prioritas tidak valid.',
        ]);

        $original = $qc->getOriginal();

        $qc->fill([
            'title'             => $validated['title'],
            'scenario'          => $validated['scenario'],
            'expected_result'   => $validated['expected_result'] ?? null,
            'project_module_id' => $validated['project_module_id'] ?? null,
            'priority'          => $validated['priority'],
        ]);
        $qc->save();

        AuditLogger::log(
            'qc_updated',
            'Project Master',
            'Mengubah detail QC <strong>' . e($qc->title) . '</strong> pada proyek <strong>' . e($project->name) . '</strong>',
            $qc,
            ['project_id' => $project->id, 'qc_test_id' => $qc->id, 'title' => $original['title'] ?? null, 'priority' => $original['priority'] ?? null],
            ['project_id' => $project->id, 'qc_test_id' => $qc->id, 'title' => $qc->title, 'priority' => $qc->priority],
        );

        return redirect()
            ->to(route('projects.show', $project) . '#qc')
            ->with('status', 'Test case "' . $qc->title . '" berhasil diperbarui.');
    }

    /**
     * Manual delete of a single QC/Test Case record. Only removes the
     * selected row; project/module/task data is untouched.
     */
    public function destroyQcTest(Request $request, Project $project, ProjectQcTest $qc)
    {
        $this->ensureCanEditProjectDetail();
        if ($redirect = $this->ensureOperationalCanAccessProject($project)) {
            return $redirect;
        }
        abort_unless($qc->project_id === $project->id, 404);

        $title = $qc->title;

        AuditLogger::log(
            'qc_deleted',
            'Project Master',
            'Menghapus QC <strong>' . e($title) . '</strong> dari proyek <strong>' . e($project->name) . '</strong>',
            $project,
            ['project_id' => $project->id, 'qc_test_id' => $qc->id, 'title' => $qc->title, 'status' => $qc->status, 'priority' => $qc->priority],
            null,
        );

        $qc->delete();

        return redirect()
            ->to(route('projects.show', $project) . '#qc')
            ->with('status', 'Test case "' . $title . '" berhasil dihapus.');
    }

    /* ===================== Dependencies & Blockers ===================== */

    public function storeBlocker(Request $request, Project $project)
    {
        if ($redirect = $this->ensureOperationalCanAccessProject($project)) {
            return $redirect;
        }
        abort_unless($this->blockerCanContribute($project), 403);

        $validated = $this->validateBlocker($request, $project);

        $blocker = $project->blockers()->create([
            'task_id' => $validated['task_id'] ?? null,
            'reported_by_user_id' => $request->user()?->id,
            'assigned_to_user_id' => $validated['assigned_to_user_id'] ?? null,
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'source' => $validated['source'] ?? null,
            'severity' => $validated['severity'],
            'status' => 'open',
            'due_date' => $validated['due_date'] ?? null,
        ]);

        AuditLogger::log(
            'blocker_created',
            'Project Master',
            'Menambah blocker <strong>' . e($blocker->title) . '</strong> pada proyek <strong>' . e($project->name) . '</strong>',
            $blocker,
            null,
            $this->safeBlockerAuditValues($blocker),
        );

        return $this->backToBlockers($project, 'Blocker berhasil ditambahkan.');
    }

    public function updateBlocker(Request $request, Project $project, ProjectBlocker $blocker)
    {
        if ($redirect = $this->ensureOperationalCanAccessProject($project)) {
            return $redirect;
        }
        abort_unless((int) $blocker->project_id === (int) $project->id, 404);

        $canManage = $this->blockerCanManage();
        $canResolveAssigned = (int) $blocker->assigned_to_user_id === (int) $request->user()?->id
            && $this->isAssignedOperationalToProject($project);
        abort_unless($canManage || $canResolveAssigned, 403);

        if (! $canManage && in_array($blocker->status, ['resolved', 'cancelled'], true)) {
            return $this->backToBlockers($project, 'Blocker yang sudah selesai/dibatalkan hanya dapat diubah oleh PM/SA.');
        }

        $validated = $this->validateBlockerUpdate($request, $project, $canManage);
        $targetStatus = $validated['status'];
        if (! $canManage && ! in_array($targetStatus, ['in_progress', 'resolved'], true)) {
            abort(403);
        }
        if ($targetStatus === 'resolved' && ! filled($validated['resolution_notes'] ?? null)) {
            throw ValidationException::withMessages([
                'resolution_notes' => 'Catatan Resolusi wajib diisi saat blocker diselesaikan.',
            ]);
        }

        $original = $blocker->getOriginal();
        $blocker->fill([
            'status' => $targetStatus,
            'resolution_notes' => $validated['resolution_notes'] ?? $blocker->resolution_notes,
        ]);

        if ($canManage) {
            $blocker->fill([
                'task_id' => $validated['task_id'] ?? null,
                'assigned_to_user_id' => $validated['assigned_to_user_id'] ?? null,
                'title' => $validated['title'],
                'description' => $validated['description'] ?? null,
                'source' => $validated['source'] ?? null,
                'severity' => $validated['severity'],
                'due_date' => $validated['due_date'] ?? null,
            ]);
        }

        $blocker->resolved_at = $targetStatus === 'resolved'
            ? ($blocker->resolved_at ?: AppTime::now())
            : null;
        if ($targetStatus !== 'resolved') {
            $blocker->resolution_notes = $targetStatus === 'cancelled'
                ? ($validated['resolution_notes'] ?? $blocker->resolution_notes)
                : null;
        }

        $blocker->save();

        $action = match ($blocker->status) {
            'resolved' => 'blocker_resolved',
            'cancelled' => 'blocker_cancelled',
            default => 'blocker_updated',
        };

        AuditLogger::log(
            $action,
            'Project Master',
            'Memperbarui blocker <strong>' . e($blocker->title) . '</strong> pada proyek <strong>' . e($project->name) . '</strong>',
            $blocker,
            [
                'project_id' => $project->id,
                'blocker_id' => $blocker->id,
                'status' => $original['status'] ?? null,
                'severity' => $original['severity'] ?? null,
            ],
            $this->safeBlockerAuditValues($blocker),
        );

        return $this->backToBlockers($project, 'Blocker berhasil diperbarui.');
    }

    public function storeTaskDependency(Request $request, Project $project)
    {
        if ($redirect = $this->ensureOperationalCanAccessProject($project)) {
            return $redirect;
        }
        abort_unless($this->blockerCanManage(), 403);

        $validated = $request->validate([
            'task_id' => ['required', Rule::exists('project_tasks', 'id')->where('project_id', $project->id)],
            'depends_on_task_id' => ['required', Rule::exists('project_tasks', 'id')->where('project_id', $project->id)],
            'type' => ['required', Rule::in(ProjectTaskDependency::TYPES)],
            'notes' => ['nullable', 'string', 'max:2000'],
        ], [
            'task_id.required' => 'Task utama wajib dipilih.',
            'depends_on_task_id.required' => 'Dependency task wajib dipilih.',
            'task_id.exists' => 'Task utama tidak valid.',
            'depends_on_task_id.exists' => 'Dependency task tidak valid.',
        ]);

        $taskId = (int) $validated['task_id'];
        $dependsOnId = (int) $validated['depends_on_task_id'];
        if ($taskId === $dependsOnId) {
            throw ValidationException::withMessages([
                'depends_on_task_id' => 'Task tidak dapat bergantung pada dirinya sendiri.',
            ]);
        }
        if ($project->taskDependencies()->where('task_id', $taskId)->where('depends_on_task_id', $dependsOnId)->exists()) {
            throw ValidationException::withMessages([
                'depends_on_task_id' => 'Dependency ini sudah tercatat.',
            ]);
        }
        if ($project->taskDependencies()->where('task_id', $dependsOnId)->where('depends_on_task_id', $taskId)->exists()) {
            throw ValidationException::withMessages([
                'depends_on_task_id' => 'Dependency ini membentuk siklus langsung.',
            ]);
        }
        if ($this->dependencyPathExists($project, $dependsOnId, $taskId)) {
            throw ValidationException::withMessages([
                'depends_on_task_id' => 'Dependency ini membentuk siklus antar task.',
            ]);
        }

        $dependency = $project->taskDependencies()->create([
            'task_id' => $taskId,
            'depends_on_task_id' => $dependsOnId,
            'type' => $validated['type'],
            'notes' => $validated['notes'] ?? null,
            'created_by_user_id' => $request->user()?->id,
        ]);

        AuditLogger::log(
            'dependency_created',
            'Project Master',
            'Menambah dependency task pada proyek <strong>' . e($project->name) . '</strong>',
            $dependency,
            null,
            $this->safeDependencyAuditValues($dependency),
        );

        return $this->backToBlockers($project, 'Dependency task berhasil ditambahkan.');
    }

    public function destroyTaskDependency(Project $project, ProjectTaskDependency $dependency)
    {
        if ($redirect = $this->ensureOperationalCanAccessProject($project)) {
            return $redirect;
        }
        abort_unless($this->blockerCanManage(), 403);
        abort_unless((int) $dependency->project_id === (int) $project->id, 404);

        $values = $this->safeDependencyAuditValues($dependency);
        $dependency->delete();

        AuditLogger::log(
            'dependency_removed',
            'Project Master',
            'Menghapus dependency task pada proyek <strong>' . e($project->name) . '</strong>',
            null,
            $values,
            null,
        );

        return $this->backToBlockers($project, 'Dependency task berhasil dihapus.');
    }

    /* ===================== Requirement Inbox ===================== */

    public function storeRequirementInboxItem(Project $project, Request $request)
    {
        if ($redirect = $this->ensureOperationalCanAccessProject($project)) {
            return $redirect;
        }
        abort_unless($this->intakeCanContribute($project), 403);

        $validated = $this->validateRequirementInbox($request);

        $item = $project->requirementInboxItems()->create([
            'captured_by_user_id' => $request->user()?->id,
            'source' => $validated['source'] ?? null,
            'channel_label' => $validated['channel_label'] ?? null,
            'occurred_on' => $validated['occurred_on'] ?? null,
            'raw_text' => $validated['raw_text'],
            'summary' => $validated['summary'] ?? null,
            'suggested_type' => $validated['suggested_type'] ?? null,
            'suggested_priority' => $validated['suggested_priority'] ?? null,
            'status' => 'new',
            'notes' => $validated['notes'] ?? null,
        ]);

        AuditLogger::log(
            'requirement_intake_created',
            'Project Master',
            'Menambah Requirement Inbox pada proyek <strong>' . e($project->name) . '</strong>',
            $item,
            null,
            [
                'project_id' => $project->id,
                'requirement_inbox_item_id' => $item->id,
                'source' => $item->source,
                'summary' => $this->safeIntakeAuditSummary($item),
            ],
        );

        return $this->backToIntake($project, 'Requirement Inbox berhasil ditambahkan.');
    }

    public function updateRequirementInboxItem(Project $project, ProjectRequirementInboxItem $inboxItem, Request $request)
    {
        if ($redirect = $this->ensureOperationalCanAccessProject($project)) {
            return $redirect;
        }
        abort_unless((int) $inboxItem->project_id === (int) $project->id, 404);

        if (in_array($inboxItem->status, ['converted', 'dismissed'], true)) {
            return $this->backToIntake($project, 'Item yang sudah dikonversi/diabaikan tidak dapat diedit.');
        }

        $isOwnerOpen = (int) $inboxItem->captured_by_user_id === (int) $request->user()?->id
            && in_array($inboxItem->status, ['new', 'reviewed'], true);
        abort_unless($this->intakeCanDecide() || $isOwnerOpen, 403);

        $validated = $this->validateRequirementInbox($request);
        $markReviewed = $this->intakeCanDecide() && $request->input('status') === 'reviewed';

        $inboxItem->fill([
            'source' => $validated['source'] ?? null,
            'channel_label' => $validated['channel_label'] ?? null,
            'occurred_on' => $validated['occurred_on'] ?? null,
            'raw_text' => $validated['raw_text'],
            'summary' => $validated['summary'] ?? null,
            'suggested_type' => $validated['suggested_type'] ?? null,
            'suggested_priority' => $validated['suggested_priority'] ?? null,
            'notes' => $validated['notes'] ?? null,
        ]);

        if ($markReviewed) {
            $inboxItem->status = 'reviewed';
            $inboxItem->reviewed_by_user_id = $request->user()?->id;
            $inboxItem->reviewed_at = AppTime::now();
        }

        $inboxItem->save();

        AuditLogger::log(
            'requirement_intake_updated',
            'Project Master',
            'Memperbarui klasifikasi Requirement Inbox pada proyek <strong>' . e($project->name) . '</strong>',
            $inboxItem,
            ['project_id' => $project->id, 'requirement_inbox_item_id' => $inboxItem->id],
            [
                'project_id' => $project->id,
                'requirement_inbox_item_id' => $inboxItem->id,
                'source' => $inboxItem->source,
                'status' => $inboxItem->status,
                'suggested_type' => $inboxItem->suggested_type,
                'suggested_priority' => $inboxItem->suggested_priority,
                'summary' => $this->safeIntakeAuditSummary($inboxItem),
            ],
        );

        return $this->backToIntake($project, 'Requirement Inbox berhasil diperbarui.');
    }

    public function convertRequirementToChangeRequest(Project $project, ProjectRequirementInboxItem $inboxItem)
    {
        if ($redirect = $this->ensureOperationalCanAccessProject($project)) {
            return $redirect;
        }
        abort_unless((int) $inboxItem->project_id === (int) $project->id, 404);
        abort_unless($this->intakeCanDecide(), 403);

        if ($this->intakeAlreadyConverted($inboxItem)) {
            return $this->backToIntake($project, 'Requirement Inbox ini sudah dikonversi atau diabaikan.');
        }

        $changeRequest = null;
        DB::transaction(function () use ($project, $inboxItem, &$changeRequest) {
            $title = $this->intakeConversionTitle($inboxItem);
            $changeRequest = $project->changeRequests()->create([
                'requested_by_user_id' => $inboxItem->captured_by_user_id ?: auth()->id(),
                'title' => $title,
                'description' => $inboxItem->raw_text,
                'source' => $inboxItem->source,
                'type' => $inboxItem->suggested_type,
                'priority' => $inboxItem->suggested_priority,
                'status' => 'needs_review',
                'affected_module_id' => null,
                'impact_summary' => $inboxItem->summary ?: $inboxItem->notes,
            ]);

            $inboxItem->forceFill([
                'status' => 'converted',
                'converted_to' => 'change_request',
                'converted_change_request_id' => $changeRequest->id,
                'reviewed_by_user_id' => auth()->id(),
                'reviewed_at' => AppTime::now(),
                'converted_at' => AppTime::now(),
            ])->save();
        });

        AuditLogger::log(
            'requirement_intake_converted_cr',
            'Project Master',
            'Mengonversi Requirement Inbox menjadi Change Request pada proyek <strong>' . e($project->name) . '</strong>',
            $inboxItem,
            ['project_id' => $project->id, 'requirement_inbox_item_id' => $inboxItem->id],
            [
                'project_id' => $project->id,
                'requirement_inbox_item_id' => $inboxItem->id,
                'change_request_id' => $changeRequest?->id,
                'summary' => $this->safeIntakeAuditSummary($inboxItem),
            ],
        );

        return $this->backToIntake($project, 'Requirement Inbox dikonversi menjadi Change Request dengan status perlu review.');
    }

    public function convertRequirementToTask(Project $project, ProjectRequirementInboxItem $inboxItem)
    {
        if ($redirect = $this->ensureOperationalCanAccessProject($project)) {
            return $redirect;
        }
        abort_unless((int) $inboxItem->project_id === (int) $project->id, 404);
        abort_unless($this->intakeCanDecide(), 403);

        if ($this->intakeAlreadyConverted($inboxItem)) {
            return $this->backToIntake($project, 'Requirement Inbox ini sudah dikonversi atau diabaikan.');
        }

        if ($this->phaseKey($project->phase) === 'done') {
            return $this->backToIntake($project, 'Project sudah selesai (Done). Requirement tidak dapat dikonversi menjadi task baru.');
        }

        $task = null;
        DB::transaction(function () use ($project, $inboxItem, &$task) {
            $module = $project->modules()->firstOrCreate(
                ['title' => 'Scope Tambahan'],
                [
                    'description' => 'Task tambahan hasil konversi Requirement Inbox.',
                    'status' => 'approved',
                    'estimate_hours' => 0,
                    'sort_order' => (int) $project->modules()->max('sort_order') + 1,
                ],
            );

            $descriptionParts = array_filter([
                'Sumber: ' . (self::INTAKE_SOURCE_LABELS[$inboxItem->source] ?? ($inboxItem->source ?: 'Requirement Inbox')),
                filled($inboxItem->channel_label) ? 'Channel: ' . $inboxItem->channel_label : null,
                'Raw intake: ' . $inboxItem->raw_text,
                filled($inboxItem->summary) ? 'Ringkasan: ' . $inboxItem->summary : null,
                filled($inboxItem->notes) ? 'Catatan internal: ' . $inboxItem->notes : null,
            ]);

            $task = $project->tasks()->create([
                'project_module_id' => $module->id,
                'assigned_to' => null,
                'title' => $this->intakeConversionTitle($inboxItem),
                'description' => implode("\n\n", $descriptionParts),
                'status' => 'planned',
                'priority' => $this->mapCrPriorityToTaskPriority($inboxItem->suggested_priority),
                'estimate_hours' => 0,
                'sort_order' => (int) $project->tasks()->max('sort_order') + 1,
            ]);

            $inboxItem->forceFill([
                'status' => 'converted',
                'converted_to' => 'task',
                'converted_task_id' => $task->id,
                'reviewed_by_user_id' => auth()->id(),
                'reviewed_at' => AppTime::now(),
                'converted_at' => AppTime::now(),
            ])->save();
        });

        AuditLogger::log(
            'requirement_intake_converted_task',
            'Project Master',
            'Mengonversi Requirement Inbox menjadi task pada proyek <strong>' . e($project->name) . '</strong>',
            $inboxItem,
            ['project_id' => $project->id, 'requirement_inbox_item_id' => $inboxItem->id],
            [
                'project_id' => $project->id,
                'requirement_inbox_item_id' => $inboxItem->id,
                'task_id' => $task?->id,
                'summary' => $this->safeIntakeAuditSummary($inboxItem),
            ],
        );

        $note = $this->phaseKey($project->phase) === 'design'
            ? ' Task masuk backlog dan mengikuti phase lock Design.'
            : '';

        return $this->backToIntake($project, 'Requirement Inbox dikonversi menjadi task baru.' . $note);
    }

    public function convertRequirementToMom(Project $project, ProjectRequirementInboxItem $inboxItem)
    {
        if ($redirect = $this->ensureOperationalCanAccessProject($project)) {
            return $redirect;
        }
        abort_unless((int) $inboxItem->project_id === (int) $project->id, 404);
        abort_unless($this->intakeCanDecide(), 403);

        if ($this->intakeAlreadyConverted($inboxItem)) {
            return $this->backToIntake($project, 'Requirement Inbox ini sudah dikonversi atau diabaikan.');
        }

        $mom = null;
        DB::transaction(function () use ($project, $inboxItem, &$mom) {
            $mom = $project->moms()->create([
                'created_by' => auth()->id(),
                'meeting_date' => $inboxItem->occurred_on ?: AppTime::now()->toDateString(),
                'notes' => $inboxItem->raw_text,
                'summary' => $inboxItem->summary,
                'status' => 'draft',
            ]);

            $inboxItem->forceFill([
                'status' => 'converted',
                'converted_to' => 'mom',
                'converted_mom_id' => $mom->id,
                'reviewed_by_user_id' => auth()->id(),
                'reviewed_at' => AppTime::now(),
                'converted_at' => AppTime::now(),
            ])->save();
        });

        AuditLogger::log(
            'requirement_intake_converted_mom',
            'Project Master',
            'Mengonversi Requirement Inbox menjadi MoM draft pada proyek <strong>' . e($project->name) . '</strong>',
            $inboxItem,
            ['project_id' => $project->id, 'requirement_inbox_item_id' => $inboxItem->id],
            [
                'project_id' => $project->id,
                'requirement_inbox_item_id' => $inboxItem->id,
                'mom_id' => $mom?->id,
                'summary' => $this->safeIntakeAuditSummary($inboxItem),
            ],
        );

        return $this->backToIntake($project, 'Requirement Inbox dikonversi menjadi MoM draft.');
    }

    public function dismissRequirementInboxItem(Project $project, ProjectRequirementInboxItem $inboxItem)
    {
        if ($redirect = $this->ensureOperationalCanAccessProject($project)) {
            return $redirect;
        }
        abort_unless((int) $inboxItem->project_id === (int) $project->id, 404);
        abort_unless($this->intakeCanDecide(), 403);

        if ($this->intakeAlreadyConverted($inboxItem)) {
            return $this->backToIntake($project, 'Item yang sudah dikonversi tidak dapat diabaikan.');
        }

        $inboxItem->forceFill([
            'status' => 'dismissed',
            'reviewed_by_user_id' => auth()->id(),
            'reviewed_at' => AppTime::now(),
        ])->save();

        AuditLogger::log(
            'requirement_intake_dismissed',
            'Project Master',
            'Mengabaikan Requirement Inbox pada proyek <strong>' . e($project->name) . '</strong>',
            $inboxItem,
            ['project_id' => $project->id, 'requirement_inbox_item_id' => $inboxItem->id],
            [
                'project_id' => $project->id,
                'requirement_inbox_item_id' => $inboxItem->id,
                'summary' => $this->safeIntakeAuditSummary($inboxItem),
            ],
        );

        return $this->backToIntake($project, 'Requirement Inbox diabaikan.');
    }

    /* ===================== Change Request / Scope Control ===================== */

    /**
     * Create a change request. Anyone with project access may file one
     * (operational users record internal notes); it always starts as draft.
     */
    public function storeChangeRequest(Request $request, Project $project)
    {
        if ($redirect = $this->ensureCanAccessChangeRequests($project)) {
            return $redirect;
        }
        abort_unless($this->crCanContribute($project), 403);

        $validated = $this->validateChangeRequest($request, $project);

        $cr = $project->changeRequests()->create([
            'requested_by_user_id' => $request->user()?->id,
            'title'                => $validated['title'],
            'description'          => $validated['description'] ?? null,
            'source'               => $validated['source'] ?? null,
            'type'                 => $validated['type'] ?? null,
            'priority'             => $validated['priority'] ?? null,
            'status'               => 'draft',
            'affected_module_id'   => $validated['affected_module_id'] ?? null,
            'estimated_hours'      => $validated['estimated_hours'] ?? null,
            'timeline_impact_days' => $validated['timeline_impact_days'] ?? null,
            'impact_summary'       => $validated['impact_summary'] ?? null,
        ]);

        AuditLogger::log(
            'change_request_created',
            'Project Master',
            'Menambah Change Request <strong>' . e($cr->title) . '</strong> pada proyek <strong>' . e($project->name) . '</strong>',
            $cr,
            null,
            ['project_id' => $project->id, 'change_request_id' => $cr->id, 'status' => $cr->status],
        );

        return $this->backToScope($project, 'Change Request "' . $cr->title . '" berhasil dibuat sebagai draft.');
    }

    /**
     * Edit change request fields. Full edit while not yet converted; once
     * converted only decision notes may be amended (no heavy edits).
     */
    public function updateChangeRequest(Request $request, Project $project, ProjectChangeRequest $changeRequest)
    {
        if ($redirect = $this->ensureCanAccessChangeRequests($project)) {
            return $redirect;
        }
        abort_unless($changeRequest->project_id === $project->id, 404);

        // Edit rights: PM/SA may always edit; the creator may edit only their own draft.
        $isOwnerDraft = $changeRequest->status === 'draft'
            && (int) $changeRequest->requested_by_user_id === (int) $request->user()?->id;
        abort_unless($this->crCanDecide() || $isOwnerDraft, 403);

        if ($changeRequest->status === 'converted') {
            $validated = $request->validate([
                'decision_notes' => ['nullable', 'string', 'max:4000'],
            ]);
            $original = $changeRequest->getOriginal();
            $changeRequest->update(['decision_notes' => $validated['decision_notes'] ?? null]);

            AuditLogger::log(
                'change_request_updated',
                'Project Master',
                'Memperbarui catatan Change Request <strong>' . e($changeRequest->title) . '</strong>',
                $changeRequest,
                ['project_id' => $project->id, 'change_request_id' => $changeRequest->id],
                ['project_id' => $project->id, 'change_request_id' => $changeRequest->id],
            );

            return $this->backToScope($project, 'Catatan Change Request diperbarui.');
        }

        $validated = $this->validateChangeRequest($request, $project);

        $changeRequest->update([
            'title'                => $validated['title'],
            'description'          => $validated['description'] ?? null,
            'source'               => $validated['source'] ?? null,
            'type'                 => $validated['type'] ?? null,
            'priority'             => $validated['priority'] ?? null,
            'affected_module_id'   => $validated['affected_module_id'] ?? null,
            'estimated_hours'      => $validated['estimated_hours'] ?? null,
            'timeline_impact_days' => $validated['timeline_impact_days'] ?? null,
            'impact_summary'       => $validated['impact_summary'] ?? null,
            'decision_notes'       => $validated['decision_notes'] ?? $changeRequest->decision_notes,
        ]);

        AuditLogger::log(
            'change_request_updated',
            'Project Master',
            'Memperbarui Change Request <strong>' . e($changeRequest->title) . '</strong>',
            $changeRequest,
            ['project_id' => $project->id, 'change_request_id' => $changeRequest->id],
            ['project_id' => $project->id, 'change_request_id' => $changeRequest->id],
        );

        return $this->backToScope($project, 'Change Request "' . $changeRequest->title . '" diperbarui.');
    }

    /**
     * Safe status transitions:
     *   draft        -> needs_review   (contributor may submit)
     *   needs_review -> approved       (decision roles)
     *   needs_review -> rejected       (decision roles)
     *   rejected     -> needs_review   (decision roles, reopen for re-review)
     * Conversion (approved -> converted) is a separate action.
     */
    public function transitionChangeRequest(Request $request, Project $project, ProjectChangeRequest $changeRequest)
    {
        if ($redirect = $this->ensureCanAccessChangeRequests($project)) {
            return $redirect;
        }
        abort_unless($changeRequest->project_id === $project->id, 404);

        $validated = $request->validate([
            'to'             => ['required', Rule::in(['needs_review', 'approved', 'rejected'])],
            'decision_notes' => ['nullable', 'string', 'max:4000'],
        ]);

        $from = $changeRequest->status;
        $to = $validated['to'];

        $allowed = [
            'needs_review' => ['draft', 'rejected'],
            'approved'     => ['needs_review'],
            'rejected'     => ['needs_review'],
        ];

        if (! in_array($from, $allowed[$to] ?? [], true)) {
            return $this->backToScope($project, 'Transisi status Change Request tidak valid.');
        }

        // Only PM/SA (decision roles) may approve/reject/reopen; submitting a
        // draft for review is allowed for any contributor (e.g. the creator).
        if (in_array($to, ['approved', 'rejected'], true) || $from === 'rejected') {
            abort_unless($this->crCanDecide(), 403);
        } else {
            abort_unless($this->crCanContribute($project), 403);
        }

        $changeRequest->status = $to;
        if (filled($validated['decision_notes'] ?? null)) {
            $changeRequest->decision_notes = $validated['decision_notes'];
        }

        if ($to === 'approved') {
            $changeRequest->approved_by_user_id = $request->user()?->id;
            $changeRequest->approved_at = now();
        } elseif ($to === 'needs_review') {
            // reopening clears the previous approval stamp
            $changeRequest->approved_by_user_id = null;
            $changeRequest->approved_at = null;
        }

        $changeRequest->save();

        $action = match ($to) {
            'needs_review' => 'change_request_review',
            'approved'     => 'change_request_approved',
            'rejected'     => 'change_request_rejected',
        };
        $verb = match ($to) {
            'needs_review' => 'mengirim ke review',
            'approved'     => 'menyetujui',
            'rejected'     => 'menolak',
        };

        AuditLogger::log(
            $action,
            'Project Master',
            ucfirst($verb) . ' Change Request <strong>' . e($changeRequest->title) . '</strong> pada proyek <strong>' . e($project->name) . '</strong>',
            $changeRequest,
            ['project_id' => $project->id, 'change_request_id' => $changeRequest->id, 'status' => $from],
            ['project_id' => $project->id, 'change_request_id' => $changeRequest->id, 'status' => $to],
        );

        return $this->backToScope($project, 'Change Request "' . $changeRequest->title . '" ' . $verb . '.');
    }

    /**
     * Convert an approved change request into a project task. Idempotent: a CR
     * already converted will not create a second task.
     */
    public function convertChangeRequest(Request $request, Project $project, ProjectChangeRequest $changeRequest)
    {
        if ($redirect = $this->ensureCanAccessChangeRequests($project)) {
            return $redirect;
        }
        abort_unless($changeRequest->project_id === $project->id, 404);
        abort_unless($this->crCanDecide(), 403);

        if ($changeRequest->status === 'converted' || $changeRequest->created_task_id) {
            return $this->backToScope($project, 'Change Request ini sudah dikonversi menjadi task.');
        }

        if ($changeRequest->status !== 'approved') {
            return $this->backToScope($project, 'Hanya Change Request yang sudah disetujui yang dapat dikonversi menjadi task.');
        }

        // Do not silently inject active scope into a finished project.
        if ($this->phaseKey($project->phase) === 'done') {
            return $this->backToScope($project, 'Project sudah selesai (Done). Aktifkan kembali fase project sebelum menambah scope dari Change Request.');
        }

        $createdTask = null;
        try {
            DB::transaction(function () use ($project, $changeRequest, &$createdTask) {
                // Use the chosen affected module, otherwise a dedicated bucket module.
                $module = $changeRequest->affected_module_id
                    ? $project->modules()->whereKey($changeRequest->affected_module_id)->first()
                    : null;

                if (! $module) {
                    $module = $project->modules()->firstOrCreate(
                        ['title' => 'Scope Tambahan'],
                        [
                            'description'    => 'Task tambahan hasil konversi Change Request (scope di luar WBS awal).',
                            'status'         => 'approved',
                            'estimate_hours' => 0,
                            'sort_order'     => (int) $project->modules()->max('sort_order') + 1,
                        ],
                    );
                }

                $descriptionParts = array_filter([
                    'Permintaan: ' . trim((string) ($changeRequest->description ?: $changeRequest->title)),
                    filled($changeRequest->impact_summary) ? 'Dampak: ' . trim((string) $changeRequest->impact_summary) : null,
                    filled($changeRequest->decision_notes) ? 'Catatan keputusan: ' . trim((string) $changeRequest->decision_notes) : null,
                ]);

                $createdTask = $project->tasks()->create([
                    'project_module_id' => $module->id,
                    'assigned_to'       => null,
                    'title'             => $changeRequest->title,
                    'description'       => implode("\n\n", $descriptionParts),
                    'status'            => 'planned',
                    'priority'          => $this->mapCrPriorityToTaskPriority($changeRequest->priority),
                    'estimate_hours'    => (int) round((float) ($changeRequest->estimated_hours ?? 0)),
                    'sort_order'        => (int) $project->tasks()->max('sort_order') + 1,
                ]);

                $changeRequest->forceFill([
                    'status'          => 'converted',
                    'converted_at'    => now(),
                    'created_task_id' => $createdTask->id,
                ])->save();
            });
        } catch (\Throwable $e) {
            report($e);

            return $this->backToScope($project, 'Gagal mengonversi Change Request: ' . $e->getMessage());
        }

        AuditLogger::log(
            'change_request_converted',
            'Project Master',
            'Mengonversi Change Request <strong>' . e($changeRequest->title) . '</strong> menjadi task pada proyek <strong>' . e($project->name) . '</strong>',
            $changeRequest,
            ['project_id' => $project->id, 'change_request_id' => $changeRequest->id, 'status' => 'approved'],
            ['project_id' => $project->id, 'change_request_id' => $changeRequest->id, 'status' => 'converted', 'created_task_id' => $createdTask?->id],
        );

        $note = $this->phaseKey($project->phase) === 'design'
            ? ' Task masuk sebagai backlog dan akan dapat dieksekusi setelah project masuk fase Development.'
            : '';

        return $this->backToScope($project, 'Change Request dikonversi menjadi task baru.' . $note);
    }

    /* ===================== PDF Exports ===================== */

    public function exportWbsPdf(Request $request, Project $project)
    {
        if ($redirect = $this->ensureOperationalCanAccessProject($project)) {
            return $redirect;
        }

        $project->load([
            'client:id,name',
            'modules' => fn ($q) => $q->orderBy('sort_order')->orderBy('id'),
            'tasks'   => fn ($q) => $q->with(['assignee:id,name'])->orderBy('sort_order')->orderBy('id'),
        ]);

        $tasksByModule = $project->tasks->groupBy('project_module_id');

        $modules = $project->modules->map(function (ProjectModule $module) use ($tasksByModule) {
            $tasks = ($tasksByModule[$module->id] ?? collect())->map(fn (ProjectTask $task) => [
                'title'          => $task->title,
                'status'         => $task->status,
                'priority'       => $task->priority,
                'assignee'       => $task->assignee?->name,
                'estimate_hours' => (int) $task->estimate_hours,
                'due_date'       => $task->due_date?->format('d M Y'),
                'description'    => $task->description,
            ])->all();

            return [
                'title'          => $module->title,
                'description'    => $module->description,
                'status'         => $module->status,
                'estimate_hours' => (int) $module->estimate_hours,
                'tasks'          => $tasks,
            ];
        })->all();

        // Include any orphaned tasks (no module) as a synthetic "Tanpa Modul" bucket
        $orphans = ($tasksByModule[null] ?? collect())->map(fn (ProjectTask $task) => [
            'title'          => $task->title,
            'status'         => $task->status,
            'priority'       => $task->priority,
            'assignee'       => $task->assignee?->name,
            'estimate_hours' => (int) $task->estimate_hours,
            'due_date'       => $task->due_date?->format('d M Y'),
            'description'    => $task->description,
        ])->all();
        if (! empty($orphans)) {
            $modules[] = [
                'title' => 'Tanpa Modul',
                'description' => 'Task yang belum terikat ke modul WBS manapun.',
                'status' => 'pending_design',
                'estimate_hours' => 0,
                'tasks' => $orphans,
            ];
        }

        $totalModules = count($modules);
        $totalTasks   = $project->tasks->count();
        $totalEstimateHours = (int) $project->modules->sum('estimate_hours') + (int) $project->tasks->sum('estimate_hours');

        $generatedAt = AppTime::now();
        $filename = 'wbs-' . $this->exportSlug($project) . '-' . $generatedAt->format('Ymd-His') . '.pdf';

        $pdf = Pdf::loadView('projects.exports.wbs-pdf', [
            'project'             => $project,
            'modules'             => $modules,
            'totalModules'        => $totalModules,
            'totalTasks'          => $totalTasks,
            'totalEstimateHours'  => $totalEstimateHours,
            'generatedAt'         => $generatedAt,
        ])->setPaper('a4', 'portrait');

        AuditLogger::log(
            'wbs_pdf_exported',
            'Project Master',
            'Mengekspor WBS PDF untuk proyek <strong>' . e($project->name) . '</strong>',
            $project,
            null,
            ['project_id' => $project->id, 'modules' => $totalModules, 'tasks' => $totalTasks],
        );

        return $pdf->download($filename);
    }

    public function exportTestCasesPdf(Request $request, Project $project)
    {
        if ($redirect = $this->ensureOperationalCanAccessProject($project)) {
            return $redirect;
        }

        $project->load([
            'client:id,name',
            'qcTests' => fn ($q) => $q->with(['module:id,title', 'creator:id,name'])
                ->orderByRaw("FIELD(status, 'failed', 'retest', 'pending', 'passed')")
                ->orderByDesc('updated_at')
                ->orderByDesc('id'),
        ]);

        $testCases = $project->qcTests->map(function (ProjectQcTest $qc) {
            return [
                'id'              => $qc->id,
                'code'            => 'QC-' . str_pad((string) $qc->id, 4, '0', STR_PAD_LEFT),
                'title'           => $qc->title,
                'scenario'        => $qc->scenario,
                'expected_result' => $qc->expected_result,
                'actual_result'   => $qc->actual_result,
                'notes'           => $qc->notes,
                'module'          => $qc->module?->title,
                'priority'        => $qc->priority ?: 'medium',
                'status'          => $qc->status ?: 'pending',
                'tested_at'       => AppTime::cast($qc->tested_at)?->format('d M Y H:i'),
            ];
        })->all();

        $byStatus = $project->qcTests->countBy('status');
        $summary = [
            'total'   => $project->qcTests->count(),
            'passed'  => (int) ($byStatus['passed'] ?? 0),
            'failed'  => (int) ($byStatus['failed'] ?? 0),
            'pending' => (int) ($byStatus['pending'] ?? 0),
            'retest'  => (int) ($byStatus['retest'] ?? 0),
        ];

        $generatedAt = AppTime::now();
        $filename = 'test-case-' . $this->exportSlug($project) . '-' . $generatedAt->format('Ymd-His') . '.pdf';

        $pdf = Pdf::loadView('projects.exports.test-cases-pdf', [
            'project'     => $project,
            'testCases'   => $testCases,
            'summary'     => $summary,
            'generatedAt' => $generatedAt,
        ])->setPaper('a4', 'portrait');

        AuditLogger::log(
            'test_case_pdf_exported',
            'Project Master',
            'Mengekspor Test Case PDF untuk proyek <strong>' . e($project->name) . '</strong>',
            $project,
            null,
            ['project_id' => $project->id, 'total' => $summary['total']],
        );

        return $pdf->download($filename);
    }

    private function validateProject(Request $request, ?Project $project = null): array
    {
        return $request->validate([
            'code'        => ['required', 'string', 'max:4', 'regex:/^[A-Za-z0-9]+$/', Rule::unique('projects', 'code')->ignore($project?->id)],
            'name'        => ['required', 'string', 'max:160'],
            'client_id'   => ['required', Rule::exists('clients', 'id')],
            'description' => ['nullable', 'string', 'max:2000'],
            'due_at'      => ['nullable', 'date'],
            'requires_design' => ['nullable', 'boolean'],
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

    private function validateDesignDeliverable(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:160'],
            'figma_url' => ['nullable', 'url', 'max:2048'],
            'pdf_file' => ['nullable', 'file', 'mimes:pdf', 'max:10240'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ], [
            'title.required' => 'Judul deliverable wajib diisi.',
            'figma_url.url' => 'Link Figma/mockup harus berupa URL valid.',
            'pdf_file.mimes' => 'File mockup harus berupa PDF.',
            'pdf_file.max' => 'File PDF maksimal 10MB.',
        ]);
    }

    private function ensureCanEditDesignDeliverables(Project $project, ProjectTask $task, ?ProjectTaskDesignDeliverable $deliverable = null): void
    {
        $this->ensureDesignDeliverableBelongsToTask($project, $task, $deliverable);

        $user = auth()->user();
        $role = $user?->roles?->first()?->name;

        abort_unless($user && $this->canEditDesignDeliverables($task, $role, $user->id), 403);
    }

    private function canEditDesignDeliverables(ProjectTask $task, ?string $role = null, ?int $userId = null): bool
    {
        $user = auth()->user();
        $role ??= $user?->roles?->first()?->name;
        $userId ??= $user?->id;

        return (bool) $task->is_design_deliverable
            && in_array($role, ['uiux_designer', 'ui_ux'], true)
            && (int) $task->assigned_to === (int) $userId;
    }

    private function ensureDesignDeliverableBelongsToTask(Project $project, ProjectTask $task, ?ProjectTaskDesignDeliverable $deliverable = null): void
    {
        abort_unless($task->project_id === $project->id, 404);
        abort_unless((bool) $task->is_design_deliverable, 404);

        if ($deliverable) {
            abort_unless($deliverable->project_task_id === $task->id, 404);
        }
    }

    private function taskHasValidDesignDeliverable(ProjectTask $task): bool
    {
        $task->loadMissing('designDeliverables');

        if (filled($task->deliverable_url) || filled($task->deliverable_file_path)) {
            return true;
        }

        return $task->designDeliverables->contains(
            fn (ProjectTaskDesignDeliverable $deliverable) => filled($deliverable->figma_url) || filled($deliverable->pdf_file_path),
        );
    }

    private function taskStatusTransitionBlockMessage(Project $project, ProjectTask $task, string $newStatus): ?string
    {
        if ($task->status === $newStatus) {
            return null;
        }

        $phase = $this->phaseKey($project->phase);

        if ($phase === 'planning' && $newStatus !== 'planned') {
            return 'Task execution aktif setelah project keluar dari fase Planning.';
        }

        if ($phase === 'design') {
            if ((bool) $task->is_design_deliverable) {
                return $this->canEditDesignDeliverables($task)
                    ? null
                    : 'Handover desain hanya dapat diperbarui oleh UI/UX assignee.';
            }

            if ($newStatus !== 'planned') {
                return 'Task development aktif setelah handover desain selesai.';
            }
        }

        return null;
    }

    private function taskStatusLockMessageForView(Project $project, ProjectTask $task): ?string
    {
        return match ($this->phaseKey($project->phase)) {
            'planning' => 'Task execution aktif setelah project keluar dari fase Planning.',
            'design' => (bool) $task->is_design_deliverable
                ? ($this->canEditDesignDeliverables($task) ? null : 'Handover desain hanya dapat diperbarui oleh UI/UX assignee.')
                : 'Task development aktif setelah handover desain selesai.',
            default => null,
        };
    }

    private function ensureDesignWbsDraft(array $modules): array
    {
        $designModuleIndex = null;

        foreach ($modules as $idx => $module) {
            $moduleTitle = (string) ($module['title'] ?? '');
            $moduleDescription = (string) ($module['description'] ?? '');
            if ($this->looksLikeDesignText($moduleTitle . ' ' . $moduleDescription)) {
                $designModuleIndex = $idx;
                $modules[$idx]['include'] = '1';
                $modules[$idx]['title'] = 'UI/UX Design';
                $modules[$idx]['tasks'] = $this->designHandoverTaskDraft();
                break;
            }
        }

        if ($designModuleIndex !== null) {
            return array_values(array_filter(
                $modules,
                fn (array $module, int $idx) => $idx === $designModuleIndex
                    || ! $this->looksLikeDesignText((string) ($module['title'] ?? '') . ' ' . (string) ($module['description'] ?? '')),
                ARRAY_FILTER_USE_BOTH,
            ));
        }

        array_unshift($modules, [
            'include' => '1',
            'title' => 'UI/UX Design',
            'description' => 'Modul desain untuk menyiapkan final mockup UI/UX sebelum development dimulai.',
            'status' => 'pending_design',
            'estimate_hours' => 12,
            'tasks' => $this->designHandoverTaskDraft(),
        ]);

        return $modules;
    }

    private function designHandoverTaskDraft(): array
    {
        return [[
            'include' => '1',
            'title' => 'Siapkan Mockup UI/UX & Handover Desain',
            'description' => 'Menyiapkan satu atau beberapa deliverable desain seperti link Figma dan PDF mockup sebagai acuan implementasi development.',
            'priority' => 'high',
            'estimate_hours' => 12,
        ]];
    }

    private function isDesignDeliverableDraft(string $moduleTitle, string $taskTitle, ?string $description = null): bool
    {
        $text = mb_strtolower($moduleTitle . ' ' . $taskTitle . ' ' . (string) $description);

        if ((str_contains($text, 'review') || str_contains($text, 'revisi')) && ! str_contains($text, 'handover')) {
            return false;
        }

        return str_contains($text, 'handover')
            || str_contains($text, 'mockup')
            || str_contains($text, 'figma');
    }

    private function looksLikeDesignText(string $value): bool
    {
        $text = mb_strtolower($value);

        return str_contains($text, 'ui/ux')
            || str_contains($text, 'ui ux')
            || str_contains($text, 'ui_ux')
            || str_contains($text, 'mockup')
            || str_contains($text, 'figma')
            || str_contains($text, 'handover desain')
            || str_contains($text, 'design')
            || str_contains($text, 'desain');
    }

    private function transitionProjectPhaseAfterWbs(Project $project): void
    {
        $target = $project->requires_design ? 'Design' : 'Development';
        $this->transitionProjectPhase($project, $target, 'WBS tersimpan');
    }

    private function transitionProjectPhaseAfterDesignDone(Project $project): void
    {
        $project->load('tasks.designDeliverables');
        if (! $project->requires_design || $this->phaseKey($project->phase) !== 'design') {
            return;
        }

        $designTasks = $project->tasks->where('is_design_deliverable', true);
        if ($designTasks->isEmpty()
            || $designTasks->contains(fn (ProjectTask $task) => ! $this->isDoneStatus($task->status) || ! $this->taskHasValidDesignDeliverable($task))) {
            return;
        }

        $this->transitionProjectPhase($project, 'Development', 'Handover desain selesai');
    }

    private function transitionProjectPhaseAfterDevelopmentDone(Project $project): void
    {
        if ($this->phaseKey($project->phase) !== 'development') {
            return;
        }

        $project->loadMissing('tasks.module');
        $developmentTasks = $project->tasks
            ->reject(fn (ProjectTask $task) => (bool) $task->is_design_deliverable)
            ->filter(fn (ProjectTask $task) => $this->isDevelopmentExecutionTask($task))
            ->values();

        if ($developmentTasks->isEmpty()
            || $developmentTasks->contains(fn (ProjectTask $task) => ! $this->isDoneStatus($task->status))) {
            return;
        }

        $this->transitionProjectPhase($project, 'QC', 'Semua task development selesai');
    }

    private function isDevelopmentExecutionTask(ProjectTask $task): bool
    {
        $text = mb_strtolower(implode(' ', array_filter([
            $task->module?->title,
            $task->title,
            $task->description,
        ])));

        if ($text === '') {
            return false;
        }

        foreach ([
            'implementasi',
            'implementation',
            'develop',
            'development',
            'frontend',
            'backend',
            'fullstack',
            'cms',
            'wordpress',
            'integrasi',
            'integration',
            'api',
            'database',
            'auth',
            'login',
            'dashboard',
            'deployment',
            'deploy',
            'bugfix',
            'stabilisasi',
            'stabilization',
            'fitur',
            'feature',
            'module',
            'modul',
        ] as $needle) {
            if (str_contains($text, $needle)) {
                foreach ([
                    'handover desain',
                    'mockup',
                    'figma',
                    'copywriting',
                    'konten',
                    'content',
                    'mom',
                    'notulensi',
                    'meeting',
                    'analisis kebutuhan',
                ] as $excludedNeedle) {
                    if (str_contains($text, $excludedNeedle)) {
                        return false;
                    }
                }

                return true;
            }
        }

        foreach ([
            'mom',
            'notulensi',
            'wbs',
            'test case',
            'qc',
            'quality',
            'handover desain',
            'mockup',
            'figma',
            'copywriting',
            'konten',
            'content',
            'meeting',
            'analisis kebutuhan',
        ] as $needle) {
            if (str_contains($text, $needle)) {
                return false;
            }
        }

        return false;
    }

    private function transitionProjectPhase(Project $project, string $targetPhase, string $reason): void
    {
        if ($project->phase === $targetPhase) {
            return;
        }

        $before = ['phase' => $project->phase];
        $project->forceFill(['phase' => $targetPhase])->save();

        AuditLogger::log(
            'project_phase_changed',
            'Project Master',
            'Mengubah fase proyek <strong>' . e($project->name) . '</strong> menjadi <strong>' . e($targetPhase) . '</strong>',
            $project,
            $before,
            ['phase' => $targetPhase, 'reason' => $reason],
        );
    }

    private function exportSlug(Project $project): string
    {
        $base = trim((string) ($project->code ?: $project->name ?: ('project-' . $project->id)));
        $slug = strtolower((string) preg_replace('/[^A-Za-z0-9]+/', '-', $base));
        $slug = trim($slug, '-');

        return $slug !== '' ? $slug : 'project-' . $project->id;
    }

    private function usesReferenceProjectData(Project $project): bool
    {
        if (! array_key_exists($project->code, self::PROJECT_META)) {
            return false;
        }

        if ($project->relationLoaded('modules')
            && $project->relationLoaded('tasks')
            && $project->relationLoaded('moms')
            && $project->relationLoaded('qcTests')) {
            return $project->modules->isEmpty()
                && $project->tasks->isEmpty()
                && $project->moms->isEmpty()
                && $project->qcTests->isEmpty();
        }

        return ! $project->modules()->exists()
            && ! $project->tasks()->exists()
            && ! $project->moms()->exists()
            && ! $project->qcTests()->exists();
    }

    /* ---- Dependencies & Blockers helpers ------------------------------- */

    private function blockerCanManage(): bool
    {
        return in_array($this->currentRoleName(), self::CR_DECISION_ROLES, true);
    }

    private function blockerCanContribute(Project $project): bool
    {
        return $this->blockerCanManage() || $this->isAssignedOperationalToProject($project);
    }

    private function isAssignedOperationalToProject(Project $project): bool
    {
        $role = $this->currentRoleName();
        if (! in_array($role, self::OPERATIONAL_ROLES, true)) {
            return false;
        }

        return TeamAssignment::query()
            ->where('user_id', auth()->id())
            ->where('project_id', $project->id)
            ->exists();
    }

    private function backToBlockers(Project $project, string $status = 'success')
    {
        return redirect()
            ->to(route('projects.show', $project) . '#blockers')
            ->with('status', $status);
    }

    private function validateBlocker(Request $request, Project $project): array
    {
        $assignedIds = $this->projectAssignedUsers($project)->pluck('id')->all();

        return $request->validate([
            'title' => ['required', 'string', 'max:160'],
            'description' => ['nullable', 'string', 'max:4000'],
            'source' => ['nullable', Rule::in(ProjectBlocker::SOURCES)],
            'severity' => ['required', Rule::in(ProjectBlocker::SEVERITIES)],
            'task_id' => ['nullable', Rule::exists('project_tasks', 'id')->where('project_id', $project->id)],
            'assigned_to_user_id' => ['nullable', Rule::in($assignedIds)],
            'due_date' => ['nullable', 'date'],
        ], [
            'title.required' => 'Judul blocker wajib diisi.',
            'severity.required' => 'Severity wajib dipilih.',
            'task_id.exists' => 'Task blocker tidak valid.',
            'assigned_to_user_id.in' => 'Owner harus anggota yang ditugaskan ke project ini.',
        ]);
    }

    private function validateBlockerUpdate(Request $request, Project $project, bool $canManage): array
    {
        if (! $canManage) {
            return $request->validate([
                'status' => ['required', Rule::in(['in_progress', 'resolved'])],
                'resolution_notes' => ['nullable', 'string', 'max:4000'],
            ]);
        }

        $validated = $this->validateBlocker($request, $project);
        $extra = $request->validate([
            'status' => ['required', Rule::in(ProjectBlocker::STATUSES)],
            'resolution_notes' => ['nullable', 'string', 'max:4000'],
        ]);

        return $validated + $extra;
    }

    private function projectBlockerRows(Project $project): array
    {
        $col = $project->relationLoaded('blockers')
            ? $project->blockers
            : $project->blockers()->with(['task:id,title,status', 'reporter:id,name', 'assignee:id,name'])->get();

        return $col->map(function (ProjectBlocker $blocker) {
            $due = $blocker->due_date ? AppTime::cast($blocker->due_date) : null;
            $overdue = $due && ! in_array($blocker->status, ['resolved', 'cancelled'], true) && $due->isBefore(AppTime::now()->startOfDay());

            return [
                'id' => $blocker->id,
                'title' => $blocker->title,
                'description' => $blocker->description,
                'source' => $blocker->source,
                'source_label' => $blocker->source ? (self::BLOCKER_SOURCE_LABELS[$blocker->source] ?? $blocker->source) : 'Tidak diklasifikasi',
                'severity' => $blocker->severity,
                'severity_label' => self::BLOCKER_SEVERITY_LABELS[$blocker->severity] ?? $blocker->severity,
                'status' => $blocker->status,
                'status_label' => self::BLOCKER_STATUS_LABELS[$blocker->status] ?? $blocker->status,
                'task_id' => $blocker->task_id,
                'task_title' => $blocker->task?->title,
                'task_status' => $blocker->task?->status,
                'reported_by' => $blocker->reporter?->name,
                'assigned_to_user_id' => $blocker->assigned_to_user_id,
                'assigned_to' => $blocker->assignee?->name,
                'due_date' => $blocker->due_date?->format('Y-m-d'),
                'due_label' => $due ? $this->formatDateId($due) : null,
                'overdue' => $overdue,
                'resolved_at' => $blocker->resolved_at ? AppTime::cast($blocker->resolved_at)?->format('d M Y H:i') : null,
                'resolution_notes' => $blocker->resolution_notes,
                'created_at' => AppTime::diff($blocker->created_at),
            ];
        })->all();
    }

    private function projectBlockerSummary(Project $project): array
    {
        $col = $project->relationLoaded('blockers') ? $project->blockers : $project->blockers()->get();
        $active = $col->whereIn('status', ['open', 'in_progress']);
        $highCritical = $active->whereIn('severity', ['high', 'critical']);
        $today = AppTime::now()->startOfDay();

        return [
            'open' => (int) $active->count(),
            'high_critical' => (int) $highCritical->count(),
            'overdue' => (int) $active->filter(fn (ProjectBlocker $blocker) => $blocker->due_date && AppTime::cast($blocker->due_date)->isBefore($today))->count(),
            'resolved' => (int) $col->where('status', 'resolved')->count(),
            'tasks_blocked' => (int) $active->whereNotNull('task_id')->pluck('task_id')->unique()->count(),
        ];
    }

    private function projectDependencyRows(Project $project): array
    {
        $col = $project->relationLoaded('taskDependencies')
            ? $project->taskDependencies
            : $project->taskDependencies()->with(['task:id,title,status', 'dependsOnTask:id,title,status', 'creator:id,name'])->get();

        return $col->map(function (ProjectTaskDependency $dependency) {
            $done = $this->isDoneStatus($dependency->dependsOnTask?->status);

            return [
                'id' => $dependency->id,
                'task_id' => $dependency->task_id,
                'task_title' => $dependency->task?->title ?? 'Task tidak ditemukan',
                'task_status' => $dependency->task?->status,
                'depends_on_task_id' => $dependency->depends_on_task_id,
                'depends_on_title' => $dependency->dependsOnTask?->title ?? 'Task dependency tidak ditemukan',
                'depends_on_status' => $dependency->dependsOnTask?->status,
                'depends_on_done' => $done,
                'type' => $dependency->type,
                'type_label' => self::DEPENDENCY_TYPE_LABELS[$dependency->type] ?? $dependency->type,
                'notes' => $dependency->notes,
                'created_by' => $dependency->creator?->name,
                'created_at' => AppTime::diff($dependency->created_at),
            ];
        })->all();
    }

    private function projectTaskOptions(Project $project): array
    {
        $tasks = $project->relationLoaded('tasks') ? $project->tasks : $project->tasks()->with('module:id,title')->orderBy('sort_order')->get();

        return $tasks->map(fn (ProjectTask $task) => [
            'id' => $task->id,
            'title' => $task->title,
            'module' => $task->module?->title ?? 'Tanpa Modul',
            'status' => $task->status,
        ])->values()->all();
    }

    private function safeBlockerAuditValues(ProjectBlocker $blocker): array
    {
        return [
            'project_id' => $blocker->project_id,
            'blocker_id' => $blocker->id,
            'task_id' => $blocker->task_id,
            'assigned_to_user_id' => $blocker->assigned_to_user_id,
            'title' => Str::limit($blocker->title, 120),
            'severity' => $blocker->severity,
            'status' => $blocker->status,
            'source' => $blocker->source,
        ];
    }

    private function safeDependencyAuditValues(ProjectTaskDependency $dependency): array
    {
        return [
            'project_id' => $dependency->project_id,
            'dependency_id' => $dependency->id,
            'task_id' => $dependency->task_id,
            'depends_on_task_id' => $dependency->depends_on_task_id,
            'type' => $dependency->type,
        ];
    }

    private function dependencyPathExists(Project $project, int $fromTaskId, int $targetTaskId): bool
    {
        $edges = $project->taskDependencies()
            ->get(['task_id', 'depends_on_task_id'])
            ->groupBy('task_id')
            ->map(fn ($rows) => $rows->pluck('depends_on_task_id')->map(fn ($id) => (int) $id)->all());
        $stack = [$fromTaskId];
        $seen = [];

        while ($stack !== []) {
            $current = array_pop($stack);
            if ($current === $targetTaskId) {
                return true;
            }
            if (isset($seen[$current])) {
                continue;
            }
            $seen[$current] = true;
            foreach ($edges[$current] ?? [] as $next) {
                if (! isset($seen[$next])) {
                    $stack[] = $next;
                }
            }
        }

        return false;
    }

    private function taskBlockerWarnings(ProjectTask $task, Project $project): array
    {
        $blockers = $project->relationLoaded('blockers')
            ? $project->blockers
            : $project->blockers()->get();
        $dependencies = $project->relationLoaded('taskDependencies')
            ? $project->taskDependencies
            : $project->taskDependencies()->with('dependsOnTask:id,status')->get();

        $activeBlockers = $blockers
            ->where('task_id', $task->id)
            ->whereIn('status', ['open', 'in_progress']);
        $highCritical = $activeBlockers->whereIn('severity', ['high', 'critical'])->count();
        $unfinishedDeps = $dependencies
            ->where('task_id', $task->id)
            ->filter(fn (ProjectTaskDependency $dependency) => ! $this->isDoneStatus($dependency->dependsOnTask?->status))
            ->count();

        return [
            'active_blocker_count' => (int) $activeBlockers->count(),
            'high_critical_blocker_count' => (int) $highCritical,
            'unfinished_dependency_count' => (int) $unfinishedDeps,
            'has_active_blocker' => $activeBlockers->isNotEmpty(),
            'has_high_critical_blocker' => $highCritical > 0,
            'has_unfinished_dependency' => $unfinishedDeps > 0,
        ];
    }

    /* ---- Requirement Inbox helpers ------------------------------------- */

    private function intakeCanContribute(Project $project): bool
    {
        if ($this->intakeCanDecide()) {
            return true;
        }

        $role = $this->currentRoleName();
        if (! in_array($role, self::OPERATIONAL_ROLES, true)) {
            return false;
        }

        return TeamAssignment::query()
            ->where('user_id', auth()->id())
            ->where('project_id', $project->id)
            ->exists();
    }

    private function intakeCanDecide(): bool
    {
        return in_array($this->currentRoleName(), self::CR_DECISION_ROLES, true);
    }

    private function backToIntake(Project $project, string $status = 'success')
    {
        return redirect()
            ->to(route('projects.show', $project) . '#intake')
            ->with('status', $status);
    }

    /**
     * @return array<string, mixed>
     */
    private function validateRequirementInbox(Request $request): array
    {
        return $request->validate([
            'source' => ['nullable', Rule::in(ProjectRequirementInboxItem::SOURCES)],
            'channel_label' => ['nullable', 'string', 'max:120'],
            'occurred_on' => ['nullable', 'date'],
            'raw_text' => ['required', 'string', 'max:20000'],
            'summary' => ['nullable', 'string', 'max:6000'],
            'suggested_type' => ['nullable', Rule::in(ProjectChangeRequest::TYPES)],
            'suggested_priority' => ['nullable', Rule::in(ProjectChangeRequest::PRIORITIES)],
            'notes' => ['nullable', 'string', 'max:6000'],
        ], [
            'raw_text.required' => 'Isi catatan requirement wajib diisi.',
            'raw_text.max' => 'Catatan requirement terlalu panjang.',
            'source.in' => 'Sumber requirement tidak valid.',
            'suggested_type.in' => 'Tipe klasifikasi tidak valid.',
            'suggested_priority.in' => 'Prioritas tidak valid.',
        ]);
    }

    private function projectRequirementInboxRows(Project $project): array
    {
        $col = $project->relationLoaded('requirementInboxItems')
            ? $project->requirementInboxItems
            : $project->requirementInboxItems()
                ->with(['capturedBy:id,name', 'reviewer:id,name', 'changeRequest:id,title', 'task:id,title', 'mom:id,meeting_date'])
                ->orderByDesc('created_at')
                ->orderByDesc('id')
                ->get();

        return $col->map(function (ProjectRequirementInboxItem $item) use ($project) {
            $convertedTarget = null;
            $convertedUrl = null;
            if ($item->converted_to === 'change_request' && $item->changeRequest) {
                $convertedTarget = 'CR: ' . $item->changeRequest->title;
                $convertedUrl = route('projects.show', $project) . '#scope';
            } elseif ($item->converted_to === 'task' && $item->task) {
                $convertedTarget = 'Task: ' . $item->task->title;
                $convertedUrl = route('projects.show', $project) . '#workspace';
            } elseif ($item->converted_to === 'mom' && $item->mom) {
                $convertedTarget = 'MoM: ' . optional($item->mom->meeting_date)->format('d M Y');
                $convertedUrl = route('projects.show', $project) . '#aiplanning';
            }

            $rawPreview = Str::limit(preg_replace('/\s+/', ' ', trim((string) $item->raw_text)), 220);
            $summaryPreview = Str::limit(preg_replace('/\s+/', ' ', trim((string) ($item->summary ?: $item->notes ?: ''))), 180);

            return [
                'id' => $item->id,
                'source' => $item->source,
                'source_label' => $item->source ? (self::INTAKE_SOURCE_LABELS[$item->source] ?? $item->source) : 'Belum diklasifikasi',
                'channel_label' => $item->channel_label,
                'occurred_on' => $item->occurred_on?->format('Y-m-d'),
                'occurred_label' => $item->occurred_on ? $this->formatDateLongId(AppTime::cast($item->occurred_on)) : null,
                'raw_text' => $item->raw_text,
                'raw_preview' => $rawPreview,
                'summary' => $item->summary,
                'summary_preview' => $summaryPreview,
                'suggested_type' => $item->suggested_type,
                'suggested_type_label' => $item->suggested_type ? (self::CR_TYPE_LABELS[$item->suggested_type] ?? $item->suggested_type) : null,
                'suggested_priority' => $item->suggested_priority,
                'suggested_priority_label' => $item->suggested_priority ? (self::CR_PRIORITY_LABELS[$item->suggested_priority] ?? $item->suggested_priority) : null,
                'status' => $item->status,
                'status_label' => self::INTAKE_STATUS_LABELS[$item->status] ?? $item->status,
                'notes' => $item->notes,
                'captured_by' => $item->capturedBy?->name,
                'captured_by_user_id' => $item->captured_by_user_id,
                'created_at' => AppTime::diff($item->created_at),
                'reviewed_by' => $item->reviewer?->name,
                'reviewed_at' => $item->reviewed_at ? AppTime::cast($item->reviewed_at)?->format('d M Y H:i') : null,
                'converted_to' => $item->converted_to,
                'converted_target' => $convertedTarget,
                'converted_url' => $convertedUrl,
                'converted_at' => $item->converted_at ? AppTime::cast($item->converted_at)?->format('d M Y H:i') : null,
            ];
        })->all();
    }

    private function projectRequirementInboxSummary(Project $project): array
    {
        $col = $project->relationLoaded('requirementInboxItems')
            ? $project->requirementInboxItems
            : $project->requirementInboxItems()->get(['status']);
        $by = $col->countBy('status');

        return [
            'total' => (int) $col->count(),
            'new' => (int) ($by['new'] ?? 0),
            'reviewed' => (int) ($by['reviewed'] ?? 0),
            'converted' => (int) ($by['converted'] ?? 0),
            'dismissed' => (int) ($by['dismissed'] ?? 0),
        ];
    }

    private function intakeAlreadyConverted(ProjectRequirementInboxItem $item): bool
    {
        return in_array($item->status, ['converted', 'dismissed'], true)
            || filled($item->converted_to)
            || filled($item->converted_change_request_id)
            || filled($item->converted_task_id)
            || filled($item->converted_mom_id);
    }

    private function intakeConversionTitle(ProjectRequirementInboxItem $item): string
    {
        $title = trim((string) ($item->summary ?: ''));
        if ($title === '') {
            $title = trim((string) Str::limit(preg_replace('/\s+/', ' ', $item->raw_text), 120, ''));
        }

        return $title !== '' ? $title : 'Requirement Intake #' . $item->id;
    }

    private function safeIntakeAuditSummary(ProjectRequirementInboxItem $item): string
    {
        $summary = trim((string) ($item->summary ?: $item->notes ?: ''));

        return $summary !== ''
            ? Str::limit(preg_replace('/\s+/', ' ', $summary), 120)
            : 'Requirement Inbox #' . $item->id;
    }

    /* ---- Change Request helpers ---------------------------------------- */

    private function currentRoleName(): ?string
    {
        return auth()->user()?->roles()->first()?->name;
    }

    /**
     * Project access gate for Change Requests. Operational users are limited
     * to their assigned projects (reusing the existing convention); CEO/PM and
     * admin-tier pass through. Returns a redirect to short-circuit, or null.
     */
    private function ensureCanAccessChangeRequests(Project $project)
    {
        return $this->ensureOperationalCanAccessProject($project);
    }

    /** PM/SA + admin-tier may make scope decisions (classify/approve/reject/convert). */
    private function crCanDecide(): bool
    {
        return in_array($this->currentRoleName(), self::CR_DECISION_ROLES, true);
    }

    /** Anyone with project access may file/view a change request (incl. operational notes). */
    private function crCanContribute(Project $project): bool
    {
        if ($this->crCanDecide()) {
            return true;
        }

        // operational contributors must be assigned to the project
        $role = $this->currentRoleName();
        if (! in_array($role, self::OPERATIONAL_ROLES, true)) {
            return false;
        }

        return TeamAssignment::query()
            ->where('user_id', auth()->id())
            ->where('project_id', $project->id)
            ->exists();
    }

    /**
     * @return array<string, mixed>
     */
    private function validateChangeRequest(Request $request, Project $project): array
    {
        return $request->validate([
            'title'                => ['required', 'string', 'max:180'],
            'description'          => ['nullable', 'string', 'max:6000'],
            'source'               => ['nullable', Rule::in(ProjectChangeRequest::SOURCES)],
            'type'                 => ['nullable', Rule::in(ProjectChangeRequest::TYPES)],
            'priority'             => ['nullable', Rule::in(ProjectChangeRequest::PRIORITIES)],
            'affected_module_id'   => ['nullable', Rule::exists('project_modules', 'id')->where('project_id', $project->id)],
            'estimated_hours'      => ['nullable', 'numeric', 'min:0', 'max:9999'],
            'timeline_impact_days' => ['nullable', 'integer', 'min:0', 'max:3650'],
            'impact_summary'       => ['nullable', 'string', 'max:4000'],
            'decision_notes'       => ['nullable', 'string', 'max:4000'],
        ], [
            'title.required'             => 'Judul change request wajib diisi.',
            'source.in'                  => 'Sumber change request tidak valid.',
            'type.in'                    => 'Tipe change request tidak valid.',
            'priority.in'                => 'Prioritas change request tidak valid.',
            'affected_module_id.exists'  => 'Modul terdampak tidak ditemukan pada proyek ini.',
            'estimated_hours.numeric'    => 'Estimasi jam harus berupa angka.',
            'timeline_impact_days.integer' => 'Dampak timeline harus berupa angka hari.',
        ]);
    }

    private function mapCrPriorityToTaskPriority(?string $priority): string
    {
        return match ($priority) {
            'urgent', 'high' => 'high',
            'low'            => 'low',
            default          => 'medium',
        };
    }

    private function backToScope(Project $project, string $status)
    {
        return redirect()
            ->to(route('projects.show', $project) . '#scope')
            ->with('status', $status);
    }

    private function projectChangeRequestSummary(Project $project): array
    {
        $col = $project->relationLoaded('changeRequests') ? $project->changeRequests : $project->changeRequests()->get(['status']);
        $by = $col->countBy('status');

        return [
            'total'        => (int) $col->count(),
            'draft'        => (int) ($by['draft'] ?? 0),
            'needs_review' => (int) ($by['needs_review'] ?? 0),
            'approved'     => (int) ($by['approved'] ?? 0),
            'rejected'     => (int) ($by['rejected'] ?? 0),
            'converted'    => (int) ($by['converted'] ?? 0),
        ];
    }

    private function projectChangeRequestRows(Project $project): array
    {
        $col = $project->relationLoaded('changeRequests')
            ? $project->changeRequests
            : $project->changeRequests()->with(['requester', 'approver', 'affectedModule', 'createdTask'])->get();

        return $col->map(function (ProjectChangeRequest $cr) {
            $estimated = $cr->estimated_hours !== null ? (float) $cr->estimated_hours : null;

            return [
                'id'             => $cr->id,
                'code'           => 'CR-' . str_pad((string) $cr->id, 4, '0', STR_PAD_LEFT),
                'title'          => $cr->title,
                'description'    => $cr->description,
                'source'         => $cr->source,
                'source_label'   => $cr->source ? (self::CR_SOURCE_LABELS[$cr->source] ?? $cr->source) : null,
                'type'           => $cr->type,
                'type_label'     => $cr->type ? (self::CR_TYPE_LABELS[$cr->type] ?? $cr->type) : null,
                'priority'       => $cr->priority,
                'priority_label' => $cr->priority ? (self::CR_PRIORITY_LABELS[$cr->priority] ?? $cr->priority) : null,
                'status'         => $cr->status,
                'status_label'   => self::CR_STATUS_LABELS[$cr->status] ?? $cr->status,
                'affected_module_id' => $cr->affected_module_id,
                'affected_module'    => $cr->affectedModule?->title,
                'estimated_hours'    => $estimated !== null ? rtrim(rtrim(number_format($estimated, 2, '.', ''), '0'), '.') : null,
                'timeline_impact_days' => $cr->timeline_impact_days,
                'impact_summary' => $cr->impact_summary,
                'decision_notes' => $cr->decision_notes,
                'requester'      => $cr->requester?->name,
                'requester_id'   => $cr->requested_by_user_id,
                'approver'       => $cr->approver?->name,
                'created_task_id'=> $cr->created_task_id,
                'created_task'   => $cr->createdTask?->title,
                'approved_at'    => $cr->approved_at ? AppTime::cast($cr->approved_at)?->format('d M Y H:i') : null,
                'converted_at'   => $cr->converted_at ? AppTime::cast($cr->converted_at)?->format('d M Y H:i') : null,
                'created_at'     => AppTime::diff($cr->created_at),
            ];
        })->all();
    }

    private function projectClientReviewRows(Project $project): array
    {
        $col = $project->relationLoaded('clientReviews')
            ? $project->clientReviews
            : $project->clientReviews()->with('creator:id,name')->orderByDesc('created_at')->get();

        return $col->map(function (ProjectClientReview $review) {
            $status = $review->isExpired() && ! in_array($review->status, ['approved', 'revision_requested', 'revoked'], true)
                ? 'expired'
                : $review->status;

            return [
                'id' => $review->id,
                'title' => $review->title,
                'description' => $review->description,
                'status' => $status,
                'status_label' => self::CLIENT_REVIEW_STATUS_LABELS[$status] ?? $status,
                'review_type' => $review->review_type,
                'review_type_label' => $review->review_type ? (self::CLIENT_REVIEW_TYPE_LABELS[$review->review_type] ?? $review->review_type) : 'General',
                'url' => route('client-reviews.show', $review->token),
                'client_name' => $review->client_name,
                'client_email' => $review->client_email,
                'client_feedback' => $review->client_feedback,
                'internal_notes' => $review->internal_notes,
                'opened_count' => (int) $review->opened_count,
                'last_opened_at' => $review->last_opened_at ? AppTime::diff($review->last_opened_at) : null,
                'expires_at' => $review->expires_at ? AppTime::cast($review->expires_at)?->format('d M Y H:i') : null,
                'approved_at' => $review->approved_at ? AppTime::cast($review->approved_at)?->format('d M Y H:i') : null,
                'revision_requested_at' => $review->revision_requested_at ? AppTime::cast($review->revision_requested_at)?->format('d M Y H:i') : null,
                'created_by' => $review->creator?->name,
                'created_at' => AppTime::diff($review->created_at),
                'includes' => collect([
                    'MoM' => $review->include_mom,
                    'Design' => $review->include_design_deliverables,
                    'Progress' => $review->include_progress,
                    'QC' => $review->include_qc_summary,
                    'Change Request' => $review->include_change_requests,
                ])->filter()->keys()->values()->all(),
            ];
        })->all();
    }

    private function canManageClientReviews(Project $project): bool
    {
        if ($this->ensureOperationalCanAccessProject($project)) {
            return false;
        }

        return in_array($this->currentRoleName(), self::CLIENT_REVIEW_MANAGER_ROLES, true);
    }

    private function ensureCanManageClientReviews(Project $project): void
    {
        if ($redirect = $this->ensureOperationalCanAccessProject($project)) {
            abort(403);
        }

        abort_unless(in_array($this->currentRoleName(), self::CLIENT_REVIEW_MANAGER_ROLES, true), 403);
    }

    private function canManageUatSignoff(Project $project): bool
    {
        if ($this->ensureOperationalCanAccessProject($project)) {
            return false;
        }

        return in_array($this->currentRoleName(), self::UAT_MANAGER_ROLES, true);
    }

    private function ensureCanManageUatSignoff(Project $project): void
    {
        if ($redirect = $this->ensureOperationalCanAccessProject($project)) {
            abort(403);
        }

        abort_unless(in_array($this->currentRoleName(), self::UAT_MANAGER_ROLES, true), 403);
    }

    private function canRunUat(Project $project): bool
    {
        return in_array($this->phaseKey((string) $project->phase), ['qc', 'done'], true);
    }

    private function backToSignoff(Project $project, string $status)
    {
        return redirect()
            ->to(route('projects.show', $project) . '#signoff')
            ->with('status', $status);
    }

    /* ---- Handover Pack helpers ---------------------------------------- */

    private function canManageHandover(Project $project): bool
    {
        if ($this->ensureOperationalCanAccessProject($project)) {
            return false;
        }

        return in_array($this->currentRoleName(), self::HANDOVER_MANAGER_ROLES, true);
    }

    private function ensureCanManageHandover(Project $project): void
    {
        if ($this->ensureOperationalCanAccessProject($project)) {
            abort(403);
        }

        abort_unless(in_array($this->currentRoleName(), self::HANDOVER_MANAGER_ROLES, true), 403);
    }

    private function backToHandover(Project $project, string $status)
    {
        return redirect()
            ->to(route('projects.show', $project) . '#handover')
            ->with('status', $status);
    }

    /**
     * Readiness indicators + finalize gate for the Handover Pack UI.
     */
    private function projectHandoverReadiness(Project $project): array
    {
        $project->loadMissing(['qcTests', 'uatItems', 'signoffs', 'clientReviews', 'tasks.designDeliverables']);

        $phaseKey = $this->phaseKey((string) $project->phase);
        $uatSummary = $this->projectUatSummary($project);
        $gate = $this->projectSignoffGate($project);

        $uatSigned = $project->signoffs->contains(fn (ProjectSignoff $s) => $s->type === 'uat' && $s->status === 'signed');
        $handoverSigned = $project->signoffs->contains(fn (ProjectSignoff $s) => $s->type === 'handover' && $s->status === 'signed');

        $designAvailable = $project->tasks->contains(
            fn (ProjectTask $task) => (bool) $task->is_design_deliverable
                && ($task->relationLoaded('designDeliverables') ? $task->designDeliverables->isNotEmpty() : $task->designDeliverables()->exists()),
        );

        $canGenerate = in_array($phaseKey, ['qc', 'done'], true);

        // missing checklist toward finalization (concise, human readable)
        $missing = [];
        if (! $uatSigned) {
            $missing[] = 'Sign-off UAT belum ada';
        }
        if (! $handoverSigned) {
            $missing[] = 'Sign-off Handover belum ada';
        }
        if ($uatSummary['total'] === 0) {
            $missing[] = 'UAT belum lengkap';
        }
        if ($uatSummary['blocking'] > 0) {
            $missing[] = 'Masih ada UAT gagal/tertahan/butuh revisi';
        } elseif ($uatSummary['open'] > 0) {
            $missing[] = 'Belum semua UAT lulus';
        }

        return [
            'phase_key'              => $phaseKey,
            'can_generate'          => $canGenerate,
            'can_finalize'          => $gate['can_complete'],
            'missing'               => array_values(array_unique($missing)),
            'qc_summary_available'  => $project->qcTests->isNotEmpty(),
            'uat_checklist_available' => $uatSummary['total'] > 0,
            'uat_signed'            => $uatSigned,
            'handover_signed'       => $handoverSigned,
            'project_done'          => $phaseKey === 'done',
            'design_available'      => $designAvailable,
            'client_reviews_available' => $project->clientReviews->isNotEmpty(),
        ];
    }

    private function projectHandoverPackRows(Project $project): array
    {
        $col = $project->relationLoaded('handoverPacks')
            ? $project->handoverPacks
            : $project->handoverPacks()->with(['generatedBy:id,name', 'finalizedBy:id,name'])->orderByDesc('version')->get();

        return $col->map(function (ProjectHandoverPack $pack) {
            return [
                'id'             => $pack->id,
                'title'          => $pack->title,
                'version'        => $pack->version,
                'status'         => $pack->status,
                'status_label'   => self::HANDOVER_STATUS_LABELS[$pack->status] ?? $pack->status,
                'summary'        => $pack->summary,
                'deployment_url' => $pack->deployment_url,
                'staging_url'    => $pack->staging_url,
                'repository_url' => $pack->repository_url,
                'admin_url'      => $pack->admin_url,
                'credential_status' => $pack->credential_handover_status,
                'credential_label'  => $pack->credential_handover_status
                    ? (self::HANDOVER_CREDENTIAL_LABELS[$pack->credential_handover_status] ?? $pack->credential_handover_status)
                    : null,
                'maintenance_notes' => $pack->maintenance_notes,
                'client_notes'   => $pack->client_notes,
                'internal_notes' => $pack->internal_notes,
                'has_pdf'        => filled($pack->pdf_path),
                'generated_by'   => $pack->generatedBy?->name,
                'finalized_by'   => $pack->finalizedBy?->name,
                'generated_at'   => $pack->generated_at ? AppTime::cast($pack->generated_at)?->format('d M Y H:i') : null,
                'finalized_at'   => $pack->finalized_at ? AppTime::cast($pack->finalized_at)?->format('d M Y H:i') : null,
                'created_at'     => AppTime::diff($pack->created_at),
            ];
        })->all();
    }

    private function handoverPackFilename(Project $project, ProjectHandoverPack $pack): string
    {
        return 'handover-' . $this->exportSlug($project) . '-v' . $pack->version . '.pdf';
    }

    /**
     * Build the DomPDF instance for a handover pack from safe project data.
     */
    private function renderHandoverPack(Project $project, ProjectHandoverPack $pack)
    {
        return Pdf::loadView('projects.exports.handover-pack-pdf', $this->handoverPackPdfData($project, $pack))
            ->setPaper('a4', 'portrait');
    }

    /**
     * Render + persist the PDF to the (non-public) local disk and record path.
     */
    private function storeHandoverPackPdf(Project $project, ProjectHandoverPack $pack): void
    {
        try {
            $pdf = $this->renderHandoverPack($project, $pack);
            $path = 'handover-packs/project-' . $project->id . '-pack-' . $pack->id . '-v' . $pack->version . '.pdf';
            Storage::disk('local')->put($path, $pdf->output());
            $pack->forceFill(['pdf_path' => $path])->save();
        } catch (\Throwable $e) {
            report($e);
        }
    }

    /**
     * Assemble safe, structured data for the handover pack PDF. Never includes
     * passwords/secrets; internal decision notes are intentionally excluded.
     *
     * @return array<string, mixed>
     */
    private function handoverPackPdfData(Project $project, ProjectHandoverPack $pack): array
    {
        $project->loadMissing([
            'client',
            'lead',
            'modules' => fn ($q) => $q->orderBy('sort_order')->orderBy('id'),
            'tasks.designDeliverables',
            'qcTests',
            'uatItems' => fn ($q) => $q->with('tester:id,name')->orderBy('sort_order')->orderBy('id'),
            'signoffs' => fn ($q) => $q->with('clientReview:id,title,status'),
            'changeRequests',
            'clientReviews',
        ]);

        // Team & responsibilities
        $team = TeamAssignment::query()
            ->where('project_id', $project->id)
            ->with(['user.roles:id,name'])
            ->get()
            ->map(function (TeamAssignment $a) {
                $resp = collect($a->responsibilities ?? [])
                    ->map(fn ($key) => self::QUICK_ASSIGN_RESPONSIBILITIES[$key] ?? $key)
                    ->values()->all();

                return [
                    'name'             => $a->user?->name ?? '—',
                    'role'             => $a->user?->roles?->first()?->name,
                    'title'            => $a->title,
                    'responsibilities' => $resp,
                ];
            })
            ->filter(fn ($r) => $r['name'] !== '—')
            ->values()->all();

        // Modules + per-module task counts
        $modules = $this->projectModuleRows($project);

        // Change requests (safe subset — no internal decision notes)
        $changeRequests = collect($this->projectChangeRequestRows($project))->map(fn ($cr) => [
            'code'           => $cr['code'],
            'title'          => $cr['title'],
            'type_label'     => $cr['type_label'],
            'priority_label' => $cr['priority_label'],
            'status_label'   => $cr['status_label'],
            'timeline_impact_days' => $cr['timeline_impact_days'],
            'created_task'   => $cr['created_task'],
        ])->all();

        // Design deliverables (reference only — no embedded PDFs)
        $deliverables = [];
        foreach ($project->tasks->where('is_design_deliverable', true) as $task) {
            foreach ($task->designDeliverables as $d) {
                $deliverables[] = [
                    'task'    => $task->title,
                    'title'   => $d->title,
                    'figma'   => $d->figma_url,
                    'has_pdf' => filled($d->pdf_file_path),
                ];
            }
        }

        return [
            'project'        => $project,
            'pack'           => $pack,
            'generatedAt'    => AppTime::now(),
            'clientName'     => $project->client?->name,
            'progress'       => $this->projectProgress($project),
            'phaseLabel'     => $project->phase ?: '—',
            'team'           => $team,
            'modules'        => $modules,
            'changeRequests' => $changeRequests,
            'deliverables'   => $deliverables,
            'qcSummary'      => $this->projectQcSummary($project),
            'uatSummary'     => $this->projectUatSummary($project),
            'uatItems'       => $this->projectUatItemRows($project),
            'signoffs'       => $this->projectSignoffRows($project),
            'approvedReviews'=> $this->approvedClientReviewOptions($project),
            'credentialLabel'=> $pack->credential_handover_status
                ? (self::HANDOVER_CREDENTIAL_LABELS[$pack->credential_handover_status] ?? $pack->credential_handover_status)
                : 'Tidak ditentukan',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function validateUatItem(Request $request, Project $project): array
    {
        return $request->validate([
            'title' => [
                'required',
                'string',
                'max:180',
                Rule::unique('project_uat_items', 'title')->where('project_id', $project->id),
            ],
            'description' => ['nullable', 'string', 'max:4000'],
            'category' => ['nullable', Rule::in(ProjectUatItem::CATEGORIES)],
            'priority' => ['nullable', Rule::in(ProjectUatItem::PRIORITIES)],
            'evidence_url' => ['nullable', 'url', 'max:2048'],
            'notes' => ['nullable', 'string', 'max:4000'],
        ], [
            'title.required' => 'Judul item UAT wajib diisi.',
            'title.unique' => 'Item UAT dengan judul tersebut sudah ada pada proyek ini.',
            'category.in' => 'Kategori UAT tidak valid.',
            'priority.in' => 'Prioritas UAT tidak valid.',
            'evidence_url.url' => 'Evidence URL harus berupa URL valid.',
        ]);
    }

    private function inferUatCategory(string $text): string
    {
        $normalized = mb_strtolower($text);

        return match (true) {
            str_contains($normalized, 'auth') || str_contains($normalized, 'login') || str_contains($normalized, 'password') || str_contains($normalized, 'security') || str_contains($normalized, 'akses') => 'security',
            str_contains($normalized, 'api') || str_contains($normalized, 'integrasi') || str_contains($normalized, 'integration') || str_contains($normalized, 'webhook') || str_contains($normalized, 'payment') => 'integration',
            str_contains($normalized, 'mockup') || str_contains($normalized, 'design') || str_contains($normalized, 'desain') || str_contains($normalized, 'ui/ux') || str_contains($normalized, 'figma') => 'design',
            str_contains($normalized, 'content') || str_contains($normalized, 'konten') || str_contains($normalized, 'copywriting') || str_contains($normalized, 'artikel') => 'content',
            str_contains($normalized, 'deploy') || str_contains($normalized, 'hosting') || str_contains($normalized, 'domain') || str_contains($normalized, 'server') => 'deployment',
            str_contains($normalized, 'performa') || str_contains($normalized, 'performance') || str_contains($normalized, 'loading') || str_contains($normalized, 'speed') => 'performance',
            default => 'functional',
        };
    }

    private function projectUatItemRows(Project $project): array
    {
        $col = $project->relationLoaded('uatItems')
            ? $project->uatItems
            : $project->uatItems()->with('tester:id,name')->orderBy('sort_order')->orderBy('id')->get();

        return $col->map(function (ProjectUatItem $item) {
            return [
                'id' => $item->id,
                'code' => 'UAT-' . str_pad((string) $item->id, 4, '0', STR_PAD_LEFT),
                'title' => $item->title,
                'description' => $item->description,
                'category' => $item->category ?: 'functional',
                'category_label' => self::UAT_CATEGORY_LABELS[$item->category ?: 'functional'] ?? ($item->category ?: 'Functional'),
                'priority' => $item->priority ?: 'medium',
                'priority_label' => self::UAT_PRIORITY_LABELS[$item->priority ?: 'medium'] ?? ($item->priority ?: 'Medium'),
                'status' => $item->status ?: 'pending',
                'status_label' => self::UAT_STATUS_LABELS[$item->status ?: 'pending'] ?? ($item->status ?: 'Pending'),
                'notes' => $item->notes,
                'evidence_url' => $item->evidence_url,
                'tester' => $item->tester?->name,
                'tested_at' => $item->tested_at ? AppTime::cast($item->tested_at)?->format('d M Y H:i') : null,
            ];
        })->all();
    }

    private function projectUatSummary(Project $project): array
    {
        $col = $project->relationLoaded('uatItems') ? $project->uatItems : $project->uatItems()->get(['status', 'priority']);
        $by = $col->countBy('status');
        $blockingStatuses = ['failed', 'blocked', 'revision_needed'];

        return [
            'total' => (int) $col->count(),
            'pending' => (int) ($by['pending'] ?? 0),
            'passed' => (int) ($by['passed'] ?? 0),
            'failed' => (int) ($by['failed'] ?? 0),
            'blocked' => (int) ($by['blocked'] ?? 0),
            'revision_needed' => (int) ($by['revision_needed'] ?? 0),
            'blocking' => (int) $col->whereIn('status', $blockingStatuses)->count(),
            'open' => (int) $col->where('status', '!=', 'passed')->count(),
            'high_open' => (int) $col
                ->whereIn('priority', ['high', 'critical'])
                ->whereIn('status', ['pending', 'failed', 'blocked', 'revision_needed'])
                ->count(),
            'all_passed' => $col->isNotEmpty() && $col->every(fn (ProjectUatItem $item) => $item->status === 'passed'),
        ];
    }

    private function projectSignoffRows(Project $project): array
    {
        $col = $project->relationLoaded('signoffs')
            ? $project->signoffs
            : $project->signoffs()->with(['creator:id,name', 'approver:id,name', 'clientReview:id,title,status'])->orderBy('type')->orderByDesc('id')->get();

        return $col->map(function (ProjectSignoff $signoff) {
            return [
                'id' => $signoff->id,
                'type' => $signoff->type,
                'type_label' => self::SIGNOFF_TYPE_LABELS[$signoff->type] ?? $signoff->type,
                'status' => $signoff->status,
                'status_label' => self::SIGNOFF_STATUS_LABELS[$signoff->status] ?? $signoff->status,
                'signed_by_name' => $signoff->signed_by_name,
                'signed_by_email' => $signoff->signed_by_email,
                'signed_by_role' => $signoff->signed_by_role,
                'signed_at' => $signoff->signed_at ? AppTime::cast($signoff->signed_at)?->format('d M Y H:i') : null,
                'notes' => $signoff->notes,
                'handover_summary' => $signoff->handover_summary,
                'client_review_title' => $signoff->clientReview?->title,
                'created_by' => $signoff->creator?->name,
            ];
        })->all();
    }

    private function approvedClientReviewOptions(Project $project): array
    {
        $col = $project->relationLoaded('clientReviews')
            ? $project->clientReviews
            : $project->clientReviews()->where('status', 'approved')->orderByDesc('approved_at')->get();

        return $col
            ->where('status', 'approved')
            ->map(fn (ProjectClientReview $review) => [
                'id' => $review->id,
                'title' => $review->title,
                'approved_at' => $review->approved_at ? AppTime::cast($review->approved_at)?->format('d M Y H:i') : null,
            ])
            ->values()
            ->all();
    }

    private function projectSignoffGate(Project $project): array
    {
        $project->loadMissing('uatItems', 'signoffs');

        $summary = $this->projectUatSummary($project);
        $canRun = $this->canRunUat($project);
        $uatSigned = $project->signoffs->contains(fn (ProjectSignoff $signoff) => $signoff->type === 'uat' && $signoff->status === 'signed');
        $handoverSigned = $project->signoffs->contains(fn (ProjectSignoff $signoff) => $signoff->type === 'handover' && $signoff->status === 'signed');

        $missing = [];
        if (! $canRun) {
            $missing[] = 'project belum masuk fase QC';
        }
        if ($summary['total'] === 0) {
            $missing[] = 'checklist UAT belum tersedia';
        }
        if ($summary['blocking'] > 0) {
            $missing[] = 'masih ada UAT gagal/tertahan/revisi';
        }
        if ($summary['open'] > 0) {
            $missing[] = 'belum semua UAT lulus';
        }

        $canSignUat = $canRun && $summary['total'] > 0 && $summary['blocking'] === 0 && $summary['open'] === 0;
        $canSignHandover = $uatSigned;

        if (! $uatSigned) {
            $missing[] = 'sign-off UAT belum dicatat';
        }
        if (! $handoverSigned) {
            $missing[] = 'handover belum dicatat';
        }

        $canComplete = $canSignUat && $uatSigned && $handoverSigned;
        $statusLabel = match (true) {
            ! $canRun => 'QC belum siap',
            $summary['total'] === 0 || $summary['blocking'] > 0 || $summary['open'] > 0 => 'UAT berjalan',
            ! $uatSigned => 'Menunggu sign-off UAT',
            ! $handoverSigned => 'Menunggu handover',
            default => 'Siap Done',
        };

        return [
            'can_run_uat' => $canRun,
            'can_sign_uat' => $canSignUat,
            'can_sign_handover' => $canSignHandover,
            'can_complete' => $canComplete,
            'missing' => array_values(array_unique($missing)),
            'status_label' => $statusLabel,
        ];
    }

    private function generateClientReviewToken(): string
    {
        do {
            $token = Str::random(48);
        } while (ProjectClientReview::where('token', $token)->exists());

        return $token;
    }

    private function ensureCanEditProjectDetail(): void
    {
        abort_unless(in_array(auth()->user()?->roles?->first()?->name, self::OPERATIONAL_ROLES, true), 403);
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
            ['count' => (int) ($counts['approved'] ?? 0),       'label' => 'Aktif',           'bg' => '#ECFDF5', 'border' => '#A7F3D0', 'value' => '#047857', 'caption' => '#059669'],
            ['count' => (int) ($counts['waiting_dev'] ?? 0),    'label' => 'Menunggu Dev',    'bg' => '#EFF6FF', 'border' => '#BFDBFE', 'value' => '#1D4ED8', 'caption' => '#2563EB'],
            ['count' => (int) ($counts['pending_design'] ?? 0), 'label' => 'Menunggu Design', 'bg' => '#FFFBEB', 'border' => '#FDE68A', 'value' => '#B45309', 'caption' => '#D97706'],
            ['count' => (int) ($counts['revision'] ?? 0),       'label' => 'Perlu Revisi',    'bg' => '#FFF1F2', 'border' => '#FECDD3', 'value' => '#BE123C', 'caption' => '#E11D48'],
        ];
    }

    private function projectMetrics(Project $project): array
    {
        $moduleTotal = $project->modules->count();
        $progressTasks = $project->tasks
            ->reject(fn (ProjectTask $task) => $this->isExcludedProgressStatus($task->status))
            ->values();
        $taskTotal = $progressTasks->count();
        $taskDone = $progressTasks->filter(fn (ProjectTask $task) => $this->isDoneStatus($task->status))->count();
        $taskOpen = max(0, $taskTotal - $taskDone);
        $momTotal = $project->relationLoaded('moms') ? $project->moms->count() : $project->moms()->count();
        $qcSummary = $this->projectQcSummary($project);
        $qcTotal = $qcSummary['total'];
        $qcPassRate = $this->percentage($qcSummary['passed'], $qcTotal);

        return [
            ['code' => 'MOD',  'value' => (string) $moduleTotal,                'label' => 'Modul Terdefinisi',  'color' => '#3B82F6', 'progress' => $moduleTotal > 0 ? 100 : 0,                       'sub' => $moduleTotal > 0 ? $moduleTotal . ' modul aktif/terdefinisi' : 'Belum ada modul'],
            ['code' => 'TASK', 'value' => $taskDone . '/' . $taskTotal,         'label' => 'Task Selesai',       'color' => '#7C3AED', 'progress' => $this->percentage($taskDone, $taskTotal),         'sub' => $taskDone . ' Selesai • ' . $taskOpen . ' Open'],
            ['code' => 'MOM',  'value' => $momTotal . ' MoM',                   'label' => 'MoM Tersimpan',      'color' => '#10B981', 'progress' => $momTotal > 0 ? 100 : 0,                          'sub' => $momTotal > 0 ? 'Catatan rapat tersimpan' : 'Belum ada MoM'],
            ['code' => 'QC',   'value' => $qcTotal > 0 ? $qcPassRate . '%' : '0%', 'label' => 'Tingkat Lulus Test', 'color' => '#F59E0B', 'progress' => $qcTotal > 0 ? $qcPassRate : 0, 'sub' => $qcTotal > 0 ? ($qcSummary['passed'] . ' lulus • ' . $qcSummary['failed'] . ' gagal • ' . $qcSummary['pending'] . ' pending') : 'Belum ada test case'],
        ];
    }

    private function projectProgress(Project $project): int
    {
        $tasks = $project->relationLoaded('tasks')
            ? $project->tasks
            : $project->tasks()->get(['status', 'estimate_hours']);

        $tasks = $tasks->reject(fn (ProjectTask $task) => $this->isExcludedProgressStatus($task->status))->values();
        if ($tasks->isEmpty()) {
            return 0;
        }

        $totalHours = (int) $tasks->sum(fn (ProjectTask $task) => max(0, (int) $task->estimate_hours));
        if ($totalHours > 0) {
            $doneHours = (int) $tasks
                ->filter(fn (ProjectTask $task) => $this->isDoneStatus($task->status))
                ->sum(fn (ProjectTask $task) => max(0, (int) $task->estimate_hours));

            return $this->percentage($doneHours, $totalHours);
        }

        return $this->percentage(
            $tasks->filter(fn (ProjectTask $task) => $this->isDoneStatus($task->status))->count(),
            $tasks->count(),
        );
    }

    private function isDoneStatus(?string $status): bool
    {
        return in_array(mb_strtolower(trim((string) $status)), ['done', 'completed', 'complete', 'selesai'], true);
    }

    private function isExcludedProgressStatus(?string $status): bool
    {
        return in_array(mb_strtolower(trim((string) $status)), ['archived', 'archive', 'cancelled', 'canceled', 'dibatalkan'], true);
    }

    private function canPrepareQc(Project $project): bool
    {
        return in_array($this->phaseKey($project->phase), ['development', 'qc', 'done'], true);
    }

    private function canExecuteQc(Project $project): bool
    {
        return in_array($this->phaseKey($project->phase), ['qc', 'done'], true);
    }

    private function projectTabs(Project $project): array
    {
        $moduleTotal = $project->relationLoaded('modules') ? $project->modules->count() : $project->modules()->count();
        $taskTotal = $project->relationLoaded('tasks') ? $project->tasks->count() : $project->tasks()->count();
        $momTotal = $project->relationLoaded('moms') ? $project->moms->count() : $project->moms()->count();
        $intakeTotal = $project->relationLoaded('requirementInboxItems') ? $project->requirementInboxItems->count() : $project->requirementInboxItems()->count();
        $blockerTotal = ($project->relationLoaded('blockers') ? $project->blockers->count() : $project->blockers()->count())
            + ($project->relationLoaded('taskDependencies') ? $project->taskDependencies->count() : $project->taskDependencies()->count());
        $qcTotal = $project->relationLoaded('qcTests') ? $project->qcTests->count() : $project->qcTests()->count();
        $uatTotal = $project->relationLoaded('uatItems') ? $project->uatItems->count() : $project->uatItems()->count();
        $crTotal = $project->relationLoaded('changeRequests') ? $project->changeRequests->count() : $project->changeRequests()->count();
        $reviewTotal = $project->relationLoaded('clientReviews') ? $project->clientReviews->count() : $project->clientReviews()->count();
        $packTotal = $project->relationLoaded('handoverPacks') ? $project->handoverPacks->count() : $project->handoverPacks()->count();

        return [
            ['id' => 'overview',   'label' => 'Overview',         'count' => $moduleTotal],
            ['id' => 'workspace',  'label' => 'Kanban Workspace', 'count' => $taskTotal],
            ['id' => 'aiplanning', 'label' => 'AI Planning',      'count' => $momTotal + $moduleTotal],
            ['id' => 'intake',     'label' => 'Requirement Inbox', 'count' => $intakeTotal],
            ['id' => 'blockers',   'label' => 'Dependencies & Blockers', 'count' => $blockerTotal],
            ['id' => 'qc',         'label' => 'Quality Control',  'count' => $qcTotal],
            ['id' => 'signoff',    'label' => 'UAT & Sign-off',   'count' => $uatTotal],
            ['id' => 'scope',      'label' => 'Scope Control',    'count' => $crTotal],
            ['id' => 'clientportal', 'label' => 'Client Portal',   'count' => $reviewTotal],
            ['id' => 'handover',   'label' => 'Handover Pack',    'count' => $packTotal],
        ];
    }

    private function projectQcSummary(Project $project): array
    {
        $col = $project->relationLoaded('qcTests') ? $project->qcTests : $project->qcTests()->get(['status']);
        $by = $col->countBy('status');

        return [
            'total'   => (int) $col->count(),
            'pending' => (int) ($by['pending'] ?? 0),
            'passed'  => (int) ($by['passed']  ?? 0),
            'failed'  => (int) ($by['failed']  ?? 0),
            'retest'  => (int) ($by['retest']  ?? 0),
        ];
    }

    private function projectQcTestRows(Project $project): array
    {
        $col = $project->relationLoaded('qcTests') ? $project->qcTests : $project->qcTests()->with(['module', 'task', 'creator'])->get();

        return $col->map(function (ProjectQcTest $qc) {
            return [
                'id'              => $qc->id,
                'code'            => 'QC-' . str_pad((string) $qc->id, 4, '0', STR_PAD_LEFT),
                'title'           => $qc->title,
                'scenario'        => $qc->scenario,
                'module'          => $qc->module?->title ?? '—',
                'module_id'       => $qc->project_module_id,
                'task'            => $qc->task?->title,
                'status'          => $qc->status ?: 'pending',
                'priority'        => $qc->priority ?: 'medium',
                'expected_result' => $qc->expected_result,
                'actual_result'   => $qc->actual_result,
                'notes'           => $qc->notes,
                'tested_at'       => AppTime::cast($qc->tested_at)?->format('d M Y H:i'),
                'creator'         => $qc->creator?->name,
                'updated_at'      => AppTime::diff($qc->updated_at),
            ];
        })->all();
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
                'created_at'   => AppTime::diff($mom->created_at),
            ];
        })->all();
    }

    private function projectActivityRows(Project $project): array
    {
        $moduleIds = $project->relationLoaded('modules') ? $project->modules->pluck('id')->all() : $project->modules()->pluck('id')->all();
        $taskIds = $project->relationLoaded('tasks') ? $project->tasks->pluck('id')->all() : $project->tasks()->pluck('id')->all();
        $momIds = $project->relationLoaded('moms') ? $project->moms->pluck('id')->all() : $project->moms()->pluck('id')->all();
        $qcIds = $project->relationLoaded('qcTests') ? $project->qcTests->pluck('id')->all() : $project->qcTests()->pluck('id')->all();

        $projectType = $project->getMorphClass();
        $moduleType = (new ProjectModule())->getMorphClass();
        $taskType = (new ProjectTask())->getMorphClass();
        $momType = (new ProjectMom())->getMorphClass();
        $qcType = (new ProjectQcTest())->getMorphClass();

        $logs = AuditLog::query()
            ->with('user:id,name')
            ->where('module', 'Project Master')
            ->where(function ($query) use ($project, $projectType, $moduleType, $moduleIds, $taskType, $taskIds, $momType, $momIds, $qcType, $qcIds) {
                $query->where(function ($q) use ($project, $projectType) {
                    $q->where('auditable_type', $projectType)
                        ->where('auditable_id', $project->id);
                });

                if (! empty($moduleIds)) {
                    $query->orWhere(function ($q) use ($moduleType, $moduleIds) {
                        $q->where('auditable_type', $moduleType)
                            ->whereIn('auditable_id', $moduleIds);
                    });
                }

                if (! empty($taskIds)) {
                    $query->orWhere(function ($q) use ($taskType, $taskIds) {
                        $q->where('auditable_type', $taskType)
                            ->whereIn('auditable_id', $taskIds);
                    });
                }

                if (! empty($momIds)) {
                    $query->orWhere(function ($q) use ($momType, $momIds) {
                        $q->where('auditable_type', $momType)
                            ->whereIn('auditable_id', $momIds);
                    });
                }

                if (! empty($qcIds)) {
                    $query->orWhere(function ($q) use ($qcType, $qcIds) {
                        $q->where('auditable_type', $qcType)
                            ->whereIn('auditable_id', $qcIds);
                    });
                }

                $query->orWhere('new_values->project_id', $project->id)
                    ->orWhere('old_values->project_id', $project->id);
            })
            ->latest('created_at')
            ->limit(8)
            ->get();

        return $logs->map(function (AuditLog $log) {
            $tag = AuditController::tagForLog($log->module, $log->action, $log->auditable_type, $log->description);

            return [
                'dot' => $this->activityDot($log->action),
                'time' => AppTime::diff($log->created_at, 'Baru saja'),
                'title' => $tag,
                'text' => trim(strip_tags((string) $log->description)) ?: 'Aktivitas project tercatat.',
            ];
        })->all();
    }

    private function activityDot(string $action): string
    {
        return match (true) {
            str_starts_with($action, 'qc_') => '#F59E0B',
            str_starts_with($action, 'blocker_') => '#E11D48',
            str_starts_with($action, 'dependency_') => '#D97706',
            str_starts_with($action, 'mom_') => '#10B981',
            str_starts_with($action, 'task_') => '#7C3AED',
            $action === 'ai_mom_fixed' => '#10B981',
            $action === 'ai_wbs_generated' => '#C084FC',
            str_starts_with($action, 'wbs_module_') => '#3B82F6',
            default => '#7C3AED',
        };
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
                ->map(fn (ProjectTask $task) => $this->projectTaskRow($task, $project))
                ->values()
                ->all();

            return $column + ['tasks' => $tasks];
        })->all();
    }

    private function projectTaskRow(ProjectTask $task, ?Project $project = null): array
    {
            $project ??= $task->project;
            $statusLockMessage = $project ? $this->taskStatusLockMessageForView($project, $task) : null;
            $warnings = $project ? $this->taskBlockerWarnings($task, $project) : [
                'active_blocker_count' => 0,
                'high_critical_blocker_count' => 0,
                'unfinished_dependency_count' => 0,
                'has_active_blocker' => false,
                'has_high_critical_blocker' => false,
                'has_unfinished_dependency' => false,
            ];

            return [
                'id' => $task->id,
                'module_id' => $task->project_module_id,
                'module' => $task->module?->title ?? 'Tanpa Modul',
                'priority' => self::TASK_PRIORITY_LABELS[$task->priority] ?? $task->priority,
                'priority_key' => $task->priority,
                'title' => $task->title,
                'description' => $task->description,
                'assigned_to' => $task->assigned_to,
                'assignee' => $task->assignee?->name ?? 'Belum Ditugaskan',
                'status' => $task->status,
                'due_date' => $task->due_date?->format('Y-m-d'),
                'due' => $task->due_date ? $this->formatDateId($task->due_date) : null,
                'hours' => (int) $task->estimate_hours,
                'is_design_deliverable' => (bool) $task->is_design_deliverable,
                'can_edit_design_deliverables' => $this->canEditDesignDeliverables($task),
                'status_locked' => filled($statusLockMessage),
                'status_lock_message' => $statusLockMessage,
                'blocker_warnings' => $warnings,
                'design_deliverables' => $task->designDeliverables->map(function (ProjectTaskDesignDeliverable $deliverable) use ($task) {
                    $hasPdf = filled($deliverable->pdf_file_path) && Storage::disk('public')->exists($deliverable->pdf_file_path);

                    return [
                        'id' => $deliverable->id,
                        'title' => $deliverable->title,
                        'figma_url' => $deliverable->figma_url,
                        'notes' => $deliverable->notes,
                        'has_pdf' => filled($deliverable->pdf_file_path),
                        'pdf_available' => $hasPdf,
                        'pdf_preview_url' => $hasPdf ? route('projects.tasks.design-deliverables.preview', [$task->project_id, $task->id, $deliverable->id]) : null,
                        'pdf_download_url' => $hasPdf ? route('projects.tasks.design-deliverables.download', [$task->project_id, $task->id, $deliverable->id]) : null,
                        'submitted_at' => $deliverable->submitted_at ? AppTime::cast($deliverable->submitted_at)?->format('d M Y H:i') : null,
                        'creator' => $deliverable->creator?->name,
                    ];
                })->values()->all(),
            ];
    }

    private function projectAssignedUsers(Project $project)
    {
        $ids = \App\Models\TeamAssignment::query()
            ->where('project_id', $project->id)
            ->pluck('user_id')
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            return collect();
        }

        return User::query()
            ->with('roles')
            ->whereIn('id', $ids)
            ->whereNull('archived_at')
            ->whereHas('roles', fn ($query) => $query->whereIn('name', self::OPERATIONAL_ROLES))
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    private function projectDesignAssigneeId(Project $project): ?int
    {
        $ids = TeamAssignment::query()
            ->where('project_id', $project->id)
            ->pluck('user_id')
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            return null;
        }

        return User::query()
            ->whereIn('id', $ids)
            ->whereNull('archived_at')
            ->whereHas('roles', fn ($query) => $query->whereIn('name', ['uiux_designer', 'ui_ux']))
            ->orderBy('name')
            ->value('id');
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

    private function canQuickAssignTeam(): bool
    {
        $role = auth()->user()?->roles?->first()?->name;

        return in_array($role, self::QUICK_ASSIGN_ROLES, true);
    }

    private function quickAssignUserRows(Project $project): array
    {
        $existing = TeamAssignment::where('project_id', $project->id)
            ->get()
            ->keyBy('user_id');

        $preferredEmailOrder = [
            'ahmad.arlisyah@avatech.test',
            'ferry.achmad@avatech.test',
            'irwan.kurniawan@avatech.test',
            'genta@avatech.test',
            'yuda.prayoga@avatech.test',
        ];

        return User::query()
            ->with('roles')
            ->whereNull('archived_at')
            ->whereHas('roles', fn ($query) => $query->whereIn('name', self::OPERATIONAL_ROLES))
            ->get()
            ->sortBy(function (User $user) use ($preferredEmailOrder) {
                $index = array_search($user->email, $preferredEmailOrder, true);

                return $index === false ? 99 . $user->name : str_pad((string) $index, 2, '0', STR_PAD_LEFT);
            })
            ->values()
            ->map(function (User $user) use ($existing, $project) {
                $assignment = $existing->get($user->id);
                $defaults = $this->quickAssignDefaultsForUser($user);
                $responsibilities = $this->normalizeQuickAssignResponsibilities($assignment?->responsibilities ?? [], $user, $assignment);

                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'initials' => $this->initials($user->name),
                    'role' => $this->quickAssignRoleLabel($user),
                    'checked' => (bool) $assignment,
                    'title' => $assignment?->title ?: $defaults['title'],
                    'type' => $assignment?->type ?: $defaults['type'],
                    'status' => $assignment?->status ?: $defaults['status'],
                    'estimated_hours' => $assignment?->estimated_hours ?? $defaults['estimated_hours'],
                    'due_date' => $assignment?->due_date?->format('Y-m-d') ?: $project->due_at?->format('Y-m-d'),
                    'notes' => $assignment?->notes ?: $defaults['notes'],
                    'responsibilities' => $responsibilities,
                    'responsibility_labels' => $this->quickAssignResponsibilityLabels($responsibilities),
                    'responsibility_options' => self::QUICK_ASSIGN_RESPONSIBILITIES,
                    'presets' => $this->quickAssignPresetsForUser($user),
                ];
            })
            ->all();
    }

    private function quickAssignDefaultsForUser(User|int $user): array
    {
        if (is_int($user)) {
            $user = User::with('roles')->find($user);
        }

        $email = (string) ($user?->email ?? '');
        $role = (string) ($user?->roles?->first()?->name ?? '');

        if ($email === 'ahmad.arlisyah@avatech.test') {
            return $this->quickAssignPresetsForUser($user)['saqa_mom_qc'];
        }

        if ($email === 'yuda.prayoga@avatech.test' || in_array($role, ['uiux_designer', 'ui_ux'], true)) {
            return $this->quickAssignPresetsForUser($user)['uiux_design'];
        }

        return $this->quickAssignPresetsForUser($user)['fullstack_dev'];
    }

    private function normalizeQuickAssignResponsibilities(array $responsibilities, ?User $user = null, ?TeamAssignment $assignment = null): array
    {
        $keys = collect($responsibilities)
            ->filter(fn ($key) => is_string($key) && array_key_exists($key, self::QUICK_ASSIGN_RESPONSIBILITIES))
            ->values();

        if ($keys->isNotEmpty()) {
            return $keys->unique()->values()->all();
        }

        if ($assignment?->type === 'review' || str_contains(mb_strtolower((string) $assignment?->title), 'qc')) {
            return ['saqa_mom_qc'];
        }

        if ($assignment?->type === 'support') {
            $title = mb_strtolower((string) $assignment->title . ' ' . (string) $assignment->notes);
            if (str_contains($title, 'wordpress')) {
                return ['wordpress_support'];
            }
            if (str_contains($title, 'copy')) {
                return ['copywriting_support'];
            }
        }

        $role = (string) ($user?->roles?->first()?->name ?? '');
        $email = (string) ($user?->email ?? '');

        if ($email === 'ahmad.arlisyah@avatech.test' || $role === 'sa_qa') {
            return ['saqa_mom_qc'];
        }

        if ($email === 'yuda.prayoga@avatech.test' || in_array($role, ['uiux_designer', 'ui_ux'], true)) {
            return ['uiux_design'];
        }

        return ['fullstack_dev'];
    }

    private function quickAssignResponsibilityLabels(array $responsibilities): array
    {
        return collect($responsibilities)
            ->map(fn ($key) => self::QUICK_ASSIGN_RESPONSIBILITIES[$key] ?? null)
            ->filter()
            ->values()
            ->all();
    }

    private function quickAssignDefaultsForResponsibilities(array $responsibilities, ?User $user): array
    {
        $presets = $this->quickAssignPresetsForUser($user);
        $primary = $responsibilities[0] ?? array_key_first($presets);
        $base = $presets[$primary] ?? reset($presets);
        $labels = $this->quickAssignResponsibilityLabels($responsibilities);

        if (count($labels) > 1) {
            $base['title'] = implode(', ', $labels);
            $base['notes'] = collect($responsibilities)
                ->map(fn ($key) => $presets[$key]['notes'] ?? null)
                ->filter()
                ->implode(' ');
            $base['type'] = in_array('saqa_mom_qc', $responsibilities, true) ? 'review' : ($base['type'] ?? 'task');
        }

        return $base;
    }

    private function quickAssignPresetsForUser(?User $user): array
    {
        return [
            'saqa_mom_qc' => [
                'label' => self::QUICK_ASSIGN_RESPONSIBILITIES['saqa_mom_qc'],
                'title' => 'Analisis kebutuhan, MoM, dan validasi QC',
                'type' => 'review',
                'status' => 'in_progress',
                'estimated_hours' => 12,
                'notes' => 'Bertanggung jawab pada analisis kebutuhan, dokumentasi MoM, validasi output AI, penyusunan test case, dan pengujian fungsional.',
            ],
            'uiux_design' => [
                'label' => self::QUICK_ASSIGN_RESPONSIBILITIES['uiux_design'],
                'title' => 'Perancangan dan penyempurnaan UI/UX',
                'type' => 'task',
                'status' => 'in_progress',
                'estimated_hours' => 12,
                'notes' => 'Bertanggung jawab pada mockup, desain UI/UX, konsistensi tampilan, dan handover desain.',
            ],
            'fullstack_dev' => [
                'label' => self::QUICK_ASSIGN_RESPONSIBILITIES['fullstack_dev'],
                'title' => 'Implementasi fitur dan integrasi teknis',
                'type' => 'task',
                'status' => 'planned',
                'estimated_hours' => 14,
                'notes' => 'Bertanggung jawab pada implementasi fitur, pengembangan frontend/backend, integrasi teknis, bugfix, dan stabilisasi sistem.',
            ],
            'wordpress_support' => [
                'label' => self::QUICK_ASSIGN_RESPONSIBILITIES['wordpress_support'],
                'title' => 'Dukungan WordPress dan konten website',
                'type' => 'support',
                'status' => 'planned',
                'estimated_hours' => 8,
                'notes' => 'Mendukung konfigurasi WordPress, penyesuaian konten, validasi halaman, dan koordinasi kebutuhan ringan website klien.',
            ],
            'copywriting_support' => [
                'label' => self::QUICK_ASSIGN_RESPONSIBILITIES['copywriting_support'],
                'title' => 'Dukungan copywriting dan validasi konten',
                'type' => 'support',
                'status' => 'planned',
                'estimated_hours' => 6,
                'notes' => 'Mendukung penyusunan microcopy, konten presentasi, wording UI, dan validasi bahasa agar sesuai kebutuhan klien.',
            ],
        ];
    }

    private function quickAssignRoleLabel(User $user): string
    {
        return match ($user->roles->first()?->name) {
            'sa_qa' => 'SA / QA',
            'uiux_designer', 'ui_ux' => 'UI/UX Designer',
            default => 'Fullstack Developer',
        };
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
        $avatars = $this->insights->projectAvatars($project);          // deduped, up to 3
        $memberCount = $this->insights->projectAssignmentCount($project); // distinct user_id

        if (! empty($avatars)) {
            $team = $avatars;
        } else {
            /* No real team assignments yet: fall back to static meta initials
             * or the project lead. Don't carry the legacy `team_more` from
             * static meta because the static initials list was inconsistent
             * with that count (produced "2 avatars + +1" for 3-member rows). */
            $staticTeam = $meta['team'] ?? ($project->lead ? [$this->initials($project->lead->name)] : []);
            $team = collect($staticTeam)
                ->take(3)
                ->map(fn ($initials) => ['initials' => $initials, 'name' => $initials, 'color' => '#7C3AED'])
                ->values()
                ->all();
            $memberCount = count($staticTeam);
        }

        $teamMore = max(0, $memberCount - count($team));
        $teamMoreNames = $teamMore > 0 ? $this->insights->projectHiddenMemberNames($project, count($team)) : [];

        $progressTasks = $project->relationLoaded('tasks')
            ? $project->tasks->reject(fn (ProjectTask $task) => $this->isExcludedProgressStatus($task->status))->values()
            : collect();
        $tasksDone = $project->relationLoaded('tasks')
            ? $progressTasks->filter(fn (ProjectTask $task) => $this->isDoneStatus($task->status))->count()
            : (int) ($meta['tasks_done'] ?? 0);
        $tasksTotal = $project->relationLoaded('tasks') ? $progressTasks->count() : (int) ($meta['tasks_total'] ?? 0);
        $momCount = $project->relationLoaded('moms') ? $project->moms->count() : (int) ($meta['mom'] ?? 0);

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
            'progress'     => $this->projectProgress($project),
            'status'       => $project->status,
            'status_label' => $this->statusLabel($project->status),
            'archived'     => (bool) $project->archived_at,
            'requires_design' => (bool) $project->requires_design,
            'team'             => $team,
            'team_more'        => $teamMore,
            'team_more_names'  => $teamMoreNames,
            'tasks_done'   => (int) $tasksDone,
            'tasks_total'  => (int) $tasksTotal,
            'mom'          => (int) $momCount,
            'ai_flag'      => (bool) $project->ai_wbs_generated,
            'smart_badges' => $this->insights->projectBadges($project),
        ];
    }

    /**
     * Normalize a human phase label to a canonical internal phase key.
     *
     * Canonical keys: planning | design | development | qc | done.
     * Punctuation/spacing/casing are ignored, so "QC", "Quality Control",
     * "quality_control", "quality-control" and "QA/QC" all resolve to "qc".
     * Gathering folds into "planning" (both are pre-execution discovery, and
     * the projects list + phase locks group them together).
     */
    private function phaseKey(string $phase): string
    {
        // strip every non-letter so spaces, "_", "-" and "/" don't matter
        $normalized = preg_replace('/[^a-z]/', '', mb_strtolower(trim($phase)));

        return match ($normalized) {
            'design'                                        => 'design',
            'development', 'dev'                            => 'development',
            'qc', 'qa', 'qaqc', 'qcqa', 'qualitycontrol'    => 'qc',
            'done', 'completed', 'complete', 'selesai'      => 'done',
            default                                         => 'planning', // gathering, planning, discovery, unknown
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
