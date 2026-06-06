<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Client;
use App\Models\Project;
use App\Models\ProjectQcTest;
use App\Models\ProjectTask;
use App\Models\TeamAssignment;
use App\Models\User;
use App\Support\AppTime;
use Illuminate\Support\Carbon;

class SmartInsightService
{
    private const OPERATIONAL_ROLES = ['sa_qa', 'uiux_designer', 'ui_ux', 'fullstack_dev'];
    private const CLOSED_TASK_STATUSES = ['done', 'completed'];
    private const CLOSED_ASSIGNMENT_STATUSES = ['done', 'completed', 'cancelled', 'canceled', 'archived'];

    /**
     * @return array<int, array<string, mixed>>
     */
    public function executiveInsights(int $limit = 3): array
    {
        $insights = collect()
            ->merge($this->dueProjectInsights())
            ->merge($this->projectPlanningInsights())
            ->merge($this->taskInsights())
            ->merge($this->qcInsights())
            ->merge($this->teamLoadInsights())
            ->merge($this->clientFollowUpInsights())
            ->sortByDesc(fn (array $item) => $this->severityRank($item['severity']))
            ->values()
            ->take($limit)
            ->all();

        return $insights ?: [[
            'severity' => 'success',
            'category' => 'Pengingat Cerdas',
            'title' => 'Semua indikator utama stabil',
            'description' => 'Belum ada proyek, QC, workload, atau klien aktif yang membutuhkan perhatian mendesak.',
            'action_label' => 'Lihat Insight',
            'action_url' => route('executive.insights'),
            'source' => 'Sistem Smart-PMIS',
            'time' => 'baru saja',
            'dismissable' => true,
            'icon' => 'check-circle',
        ]];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function projectBadges(Project $project): array
    {
        $badges = [];
        $moduleCount = $project->relationLoaded('modules') ? $project->modules->count() : $project->modules()->count();
        $taskCount = $project->relationLoaded('tasks') ? $project->tasks->count() : $project->tasks()->count();
        $unassignedTasks = $project->relationLoaded('tasks')
            ? $project->tasks->whereNull('assigned_to')->count()
            : $project->tasks()->whereNull('assigned_to')->count();
        $qcCount = $project->relationLoaded('qcTests') ? $project->qcTests->count() : $project->qcTests()->count();
        $momCount = $project->relationLoaded('moms') ? $project->moms->count() : $project->moms()->count();

        if ($momCount > 0 && $moduleCount === 0) {
            $badges[] = ['label' => 'AI WBS Ready', 'tone' => 'violet', 'icon' => 'sparkles'];
        } elseif ($moduleCount > 0 && $qcCount === 0) {
            $badges[] = ['label' => 'Needs QC', 'tone' => 'amber', 'icon' => 'beaker'];
        }

        if ($taskCount > 0 && $unassignedTasks > 0) {
            $badges[] = ['label' => 'Needs Assignment', 'tone' => 'rose', 'icon' => 'user-plus'];
        } elseif ($project->due_at && $project->due_at->isBetween(AppTime::now(), AppTime::now()->addDays(7)) && (int) $project->progress < 100) {
            $badges[] = ['label' => 'Due Soon', 'tone' => 'amber', 'icon' => 'clock'];
        } elseif ($project->status === 'on-track') {
            $badges[] = ['label' => 'On Track', 'tone' => 'emerald', 'icon' => 'check-circle'];
        }

        return array_slice($badges, 0, 2);
    }

    /**
     * @return array<int, array{initials: string, name: string, color: string}>
     */
    public function projectAvatars(Project $project, int $visible = 3): array
    {
        return TeamAssignment::query()
            ->with('user:id,name,avatar_color')
            ->where('project_id', $project->id)
            ->whereHas('user', fn ($query) => $query->whereNull('archived_at'))
            ->orderBy('id')
            ->get()
            ->pluck('user')
            ->filter()
            ->unique('id')
            ->take($visible)
            ->map(fn (User $user) => [
                'initials' => $this->initials($user->name),
                'name' => $user->name,
                'color' => $user->avatar_color ?: '#7C3AED',
            ])
            ->values()
            ->all();
    }

    public function projectAssignmentCount(Project $project): int
    {
        return TeamAssignment::query()
            ->where('project_id', $project->id)
            ->whereHas('user', fn ($query) => $query->whereNull('archived_at'))
            ->distinct('user_id')
            ->count('user_id');
    }

    /**
     * Names of project members beyond the first $skip avatars — used to
     * tooltip the "+N" overflow badge ("Anggota lainnya: A, B").
     *
     * @return array<int, string>
     */
    public function projectHiddenMemberNames(Project $project, int $skip = 3): array
    {
        return TeamAssignment::query()
            ->with('user:id,name')
            ->where('project_id', $project->id)
            ->whereHas('user', fn ($query) => $query->whereNull('archived_at'))
            ->orderBy('id')
            ->get()
            ->pluck('user')
            ->filter()
            ->unique('id')
            ->slice($skip)
            ->pluck('name')
            ->filter()
            ->values()
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function clientInsights(Client $client): array
    {
        $insights = [];
        $projects = $client->relationLoaded('projects') ? $client->projects : $client->projects()->get();
        $activeProjects = $projects->filter(fn (Project $project) => $project->archived_at === null && $project->phase !== 'Done');
        $health = (int) ($client->relationship_health ?? 50);
        $lastAudit = $this->clientLastAudit($client);

        if ($health < 65) {
            $insights[] = [
                'severity' => 'warning',
                'category' => 'Relasi Klien',
                'title' => 'Perlu perhatian',
                'description' => 'Relasi klien berada di ' . $health . '%. Siapkan follow-up singkat untuk validasi kebutuhan terbaru.',
            ];
        }

        if ($lastAudit && AppTime::cast($lastAudit)?->lt(AppTime::now()->subDays(14))) {
            $insights[] = [
                'severity' => 'warning',
                'category' => 'Last Touch',
                'title' => 'Follow-up disarankan',
                'description' => 'Belum ada aktivitas klien sejak ' . AppTime::diff($lastAudit) . '.',
            ];
        } elseif (! $lastAudit && AppTime::cast($client->created_at)?->lt(AppTime::now()->subDays(14))) {
            $insights[] = [
                'severity' => 'info',
                'category' => 'Last Touch',
                'title' => 'Belum ada touch terbaru',
                'description' => 'Belum ada audit follow-up klien yang tercatat setelah profil dibuat.',
            ];
        }

        if ($activeProjects->whereIn('status', ['attention', 'critical'])->isNotEmpty()) {
            $insights[] = [
                'severity' => 'critical',
                'category' => 'Project Risk',
                'title' => 'Ada proyek butuh perhatian',
                'description' => 'Salah satu proyek aktif klien sedang berstatus Needs Attention atau Critical.',
            ];
        }

        return array_slice($insights, 0, 3);
    }

    public function clientWhatsappDraft(Client $client): string
    {
        $project = $client->projects()->whereNull('archived_at')->latest('updated_at')->first();
        $pic = trim((string) $client->pic_name) !== '' ? $client->pic_name : 'Bapak/Ibu';
        $projectText = $project ? ' terkait project ' . $project->name : '';

        return trim("Halo {$pic}, izin follow up{$projectText}.\n\nKami ingin memastikan apakah ada update kebutuhan, kendala, atau prioritas baru yang perlu tim Avatech perhatikan minggu ini.\n\nJika berkenan, kami siap menyesuaikan langkah berikutnya berdasarkan arahan dari tim {$client->name}. Terima kasih.");
    }

    public function clientEmailDraft(Client $client): array
    {
        $project = $client->projects()->whereNull('archived_at')->latest('updated_at')->first();
        $projectText = $project ? ' terkait project ' . $project->name : '';

        return [
            'subject' => 'Follow-up ' . $client->name,
            'body' => trim("Halo " . ($client->pic_name ?: 'Bapak/Ibu') . ",\n\nSaya ingin melakukan follow-up{$projectText}. Apakah ada update kebutuhan, kendala, atau prioritas baru yang perlu kami tindak lanjuti dari sisi Avatech?\n\nKami siap menyesuaikan langkah berikutnya berdasarkan arahan dari tim {$client->name}.\n\nTerima kasih."),
        ];
    }

    private function dueProjectInsights(): array
    {
        return Project::query()
            ->whereNull('archived_at')
            ->whereNotNull('due_at')
            ->whereDate('due_at', '>=', AppTime::now()->toDateString())
            ->whereDate('due_at', '<=', AppTime::now()->addDays(7)->toDateString())
            ->where('progress', '<', 100)
            ->orderBy('due_at')
            ->limit(2)
            ->get()
            ->map(fn (Project $project) => $this->insight(
                'warning',
                'Mendekati Deadline',
                $project->name . ' due dalam ' . max(0, AppTime::now()->startOfDay()->diffInDays($project->due_at->startOfDay())) . ' hari',
                'Progress masih ' . (int) $project->progress . '%. Tinjau scope, WBS, dan task yang belum selesai.',
                'Tinjau Proyek',
                route('projects.show', $project) . '#workspace',
                'projects.due_at',
                $project->updated_at,
                'clock',
            ))
            ->all();
    }

    private function projectPlanningInsights(): array
    {
        $projects = Project::withCount(['moms', 'modules', 'tasks', 'qcTests'])
            ->whereNull('archived_at')
            ->latest('updated_at')
            ->limit(20)
            ->get();

        $insights = [];
        foreach ($projects as $project) {
            if ($project->moms_count > 0 && $project->modules_count === 0) {
                $insights[] = $this->insight('info', 'WBS Belum Lengkap', $project->name . ' punya MoM tetapi belum ada WBS', 'MoM sudah tersedia. Generate draft WBS lalu review secara manual.', 'Buka AI Planning', route('projects.show', $project) . '#aiplanning', 'project_moms/project_modules', $project->updated_at, 'sparkles');
            } elseif ($project->modules_count > 0 && $project->tasks_count === 0) {
                $insights[] = $this->insight('warning', 'Task Belum Dibreakdown', $project->name . ' punya WBS tanpa task', 'Tambahkan task implementasi agar tim bisa mengerjakan scope secara jelas.', 'Buka Workspace', route('projects.show', $project) . '#workspace', 'project_modules/project_tasks', $project->updated_at, 'squares-2x2');
            } elseif ($project->modules_count > 0 && $project->qc_tests_count === 0) {
                $insights[] = $this->insight('warning', 'QC Belum Ada', $project->name . ' punya WBS tetapi belum ada test case', 'Siapkan test case black-box untuk scope inti sebelum handover.', 'Lihat QC', route('projects.show', $project) . '#qc', 'project_qc_tests', $project->updated_at, 'beaker');
            }
        }

        return array_slice($insights, 0, 3);
    }

    private function taskInsights(): array
    {
        $insights = [];
        $overdue = ProjectTask::with('project')
            ->whereHas('project', fn ($query) => $query->whereNull('archived_at'))
            ->whereNotIn('status', self::CLOSED_TASK_STATUSES)
            ->whereDate('due_date', '<', AppTime::now()->toDateString())
            ->orderBy('due_date')
            ->first();
        if ($overdue?->project) {
            $insights[] = $this->insight('critical', 'Task Overdue', $overdue->project->name . ' memiliki task lewat deadline', '"' . $overdue->title . '" sudah melewati due date dan belum selesai.', 'Buka Workspace', route('projects.show', $overdue->project) . '#workspace', 'project_tasks.due_date', $overdue->updated_at, 'exclamation-triangle');
        }

        $unassigned = ProjectTask::with('project')
            ->whereHas('project', fn ($query) => $query->whereNull('archived_at'))
            ->whereNull('assigned_to')
            ->whereNotIn('status', self::CLOSED_TASK_STATUSES)
            ->latest('updated_at')
            ->first();
        if ($unassigned?->project) {
            $insights[] = $this->insight('warning', 'Task Belum Assigned', $unassigned->project->name . ' punya task tanpa assignee', 'Assign task ke anggota project agar ownership jelas. Tidak ada auto-assign.', 'Buka Workspace', route('projects.show', $unassigned->project) . '#workspace', 'project_tasks.assigned_to', $unassigned->updated_at, 'user-plus');
        }

        return $insights;
    }

    private function qcInsights(): array
    {
        $qc = ProjectQcTest::with('project')
            ->whereHas('project', fn ($query) => $query->whereNull('archived_at'))
            ->whereIn('status', ['failed', 'retest'])
            ->latest('updated_at')
            ->first();

        return $qc?->project ? [
            $this->insight('critical', 'QC Perlu Retest', $qc->project->name . ' punya test case ' . strtoupper($qc->status), '"' . $qc->title . '" perlu ditinjau oleh SA/QA sebelum dianggap siap.', 'Lihat QC', route('projects.show', $qc->project) . '#qc', 'project_qc_tests.status', $qc->updated_at, 'beaker'),
        ] : [];
    }

    private function teamLoadInsights(): array
    {
        $user = User::query()
            ->with('roles')
            ->whereNull('archived_at')
            ->whereHas('roles', fn ($query) => $query->whereIn('name', self::OPERATIONAL_ROLES))
            ->get()
            ->map(function (User $user) {
                $hours = (int) TeamAssignment::query()
                    ->where('user_id', $user->id)
                    ->whereNotIn('status', self::CLOSED_ASSIGNMENT_STATUSES)
                    ->sum('estimated_hours');

                return ['user' => $user, 'hours' => $hours, 'load' => (int) round($hours / 40 * 100)];
            })
            ->sortByDesc('load')
            ->first();

        if (! $user || $user['load'] < 85) {
            return [];
        }

        $severity = $user['load'] >= 95 ? 'critical' : 'warning';
        return [
            $this->insight($severity, 'Risiko Overload', $user['user']->name . ' berada di ' . $user['load'] . '% kapasitas', 'Pertimbangkan review assignment. Rebalance tetap manual melalui Team Management.', 'Buka Team Load', route('executive.index') . '#teamLoad', 'team_assignments.estimated_hours', AppTime::now(), 'users'),
        ];
    }

    private function clientFollowUpInsights(): array
    {
        return Client::query()
            ->whereNull('archived_at')
            ->orderBy('relationship_health')
            ->limit(2)
            ->get()
            ->filter(fn (Client $client) => (int) ($client->relationship_health ?? 50) < 70)
            ->map(fn (Client $client) => $this->insight('info', 'Perlu Follow-up', $client->name . ' butuh touch point ringan', 'Relasi klien ' . (int) ($client->relationship_health ?? 50) . '%. Siapkan draft WhatsApp sebelum kontak.', 'Draft WhatsApp', route('clients.index', ['open' => 'client:' . $client->id]), 'clients.relationship_health', $client->updated_at, 'chat-bubble-left-right'))
            ->values()
            ->all();
    }

    private function clientLastAudit(Client $client): ?Carbon
    {
        $type = $client->getMorphClass();

        $log = AuditLog::query()
            ->where('auditable_type', $type)
            ->where('auditable_id', $client->id)
            ->latest('created_at')
            ->first(['created_at']);

        return $log?->created_at;
    }

    private function insight(string $severity, string $category, string $title, string $description, string $actionLabel, string $actionUrl, string $source, ?Carbon $timestamp, string $icon): array
    {
        return [
            'severity' => $severity,
            'category' => $category,
            'title' => $title,
            'description' => $description,
            'action_label' => $actionLabel,
            'action_url' => $actionUrl,
            'source' => $this->sourceLabel($source),
            'time' => AppTime::diff($timestamp),
            'dismissable' => true,
            'icon' => $icon,
        ];
    }

    private function severityRank(string $severity): int
    {
        return match ($severity) {
            'critical' => 4,
            'warning' => 3,
            'info' => 2,
            'success' => 1,
            default => 0,
        };
    }

    private function sourceLabel(string $source): string
    {
        return match ($source) {
            'project_qc_tests', 'project_qc_tests.status' => 'Quality Control',
            'project_tasks.assigned_to' => 'Assignment Task',
            'project_tasks.due_date' => 'Deadline Task',
            'project_tasks.estimated_hours' => 'Estimasi Task',
            'project_modules', 'project_modules/project_tasks' => 'WBS Modul',
            'project_moms', 'project_moms/project_modules' => 'MoM Proyek',
            'audit_logs' => 'Aktivitas Sistem',
            'team_assignments.estimated_hours', 'team_load' => 'Beban Tim',
            'clients.relationship_health' => 'Relasi Klien',
            'clients.follow_up' => 'Follow-up Klien',
            'projects.due_at' => 'Deadline Proyek',
            default => str_contains($source, '.') || str_contains($source, '_')
                ? 'Sistem Smart-PMIS'
                : ($source !== '' ? $source : 'Sistem Smart-PMIS'),
        };
    }

    private function initials(string $name): string
    {
        $parts = array_values(array_filter(preg_split('/\s+/', trim($name)) ?: []));
        if ($parts === []) {
            return '?';
        }
        if (count($parts) === 1) {
            return mb_strtoupper(mb_substr($parts[0], 0, 2));
        }

        return mb_strtoupper(mb_substr($parts[0], 0, 1) . mb_substr($parts[1], 0, 1));
    }
}
