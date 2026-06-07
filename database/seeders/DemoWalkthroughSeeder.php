<?php

namespace Database\Seeders;

use App\Models\AiRequestLog;
use App\Models\AuditLog;
use App\Models\Client;
use App\Models\Project;
use App\Models\ProjectMom;
use App\Models\ProjectModule;
use App\Models\ProjectQcTest;
use App\Models\ProjectTask;
use App\Models\TeamAssignment;
use App\Models\User;
use App\Support\AppTime;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class DemoWalkthroughSeeder extends Seeder
{
    public function run(): void
    {
        $client = Client::where('name', 'PT Maju Jaya')->first() ?? Client::query()->first();
        $lead = User::where('email', 'ahmad.arlisyah@avatech.test')->first() ?? User::query()->first();

        if (! $client || ! $lead) {
            $this->command?->warn('DemoWalkthroughSeeder skipped: AC / Alpha CRM needs at least one client and one user.');

            return;
        }

        $project = Project::updateOrCreate(
            ['code' => 'AC'],
            [
                'color' => '#7C3AED',
                'name' => 'Alpha CRM',
                'description' => 'Sales pipeline & customer dashboard internal untuk tim sales B2B PT Maju Jaya.',
                'client_id' => $client->id,
                'lead_user_id' => $lead->id,
                'phase' => 'Development',
                'due_at' => Carbon::parse('2026-06-30'),
                'progress' => 72,
                'status' => 'on-track',
                'ai_wbs_generated' => true,
                'is_featured' => true,
                'archived_at' => null,
            ],
        );

        $users = $this->usersByEmail();
        $modules = $this->seedMainModules($project);
        $tasks = $this->seedMainTasks($project, $modules, $users);
        $this->seedMainMoms($project, $lead);
        $this->seedMainQc($project, $modules, $tasks, $lead);
        $this->seedTeamAssignments($project, Project::whereIn('code', ['BP', 'GA'])->get()->keyBy('code'), $users);
        $this->seedSupportingProjects(Project::whereIn('code', ['BP', 'GA'])->get()->keyBy('code'), $users);
        $this->seedAuditTimeline($project, $lead, $modules, $tasks);
        $this->seedAiMonitorHistory($project, $client, $lead);
    }

    private function usersByEmail(): array
    {
        return User::whereIn('email', [
            'ahmad.arlisyah@avatech.test',
            'irwan.kurniawan@avatech.test',
            'ferry.achmad@avatech.test',
            'genta@avatech.test',
            'yuda.prayoga@avatech.test',
            'joshua.raphael@avatech.test',
        ])->get()->keyBy('email')->all();
    }

    /**
     * @return array<string, ProjectModule>
     */
    private function seedMainModules(Project $project): array
    {
        $rows = [
            [
                'title' => 'CRM Lead & Contact Management',
                'description' => 'Kelola lead, kontak PIC, dan histori komunikasi sales.',
                'status' => 'approved',
                'estimate_hours' => 36,
                'sort_order' => 1,
            ],
            [
                'title' => 'Sales Pipeline & Follow-up Automation',
                'description' => 'Pipeline deal, reminder follow-up, dan tracking next action.',
                'status' => 'waiting_dev',
                'estimate_hours' => 44,
                'sort_order' => 2,
            ],
            [
                'title' => 'Reporting, Role Access, and Audit Readiness',
                'description' => 'Dashboard ringkas, pembatasan akses role, dan catatan aktivitas.',
                'status' => 'revision',
                'estimate_hours' => 32,
                'sort_order' => 3,
            ],
        ];

        $modules = [];
        foreach ($rows as $row) {
            $modules[$row['title']] = ProjectModule::updateOrCreate(
                ['project_id' => $project->id, 'title' => $row['title']],
                $row,
            );
        }

        return $modules;
    }

    /**
     * @param array<string, ProjectModule> $modules
     * @param array<string, User> $users
     * @return array<string, ProjectTask>
     */
    private function seedMainTasks(Project $project, array $modules, array $users): array
    {
        $rows = [
            ['module' => 'CRM Lead & Contact Management', 'assignee' => 'irwan.kurniawan@avatech.test', 'title' => 'Finalisasi skema data lead dan kontak', 'status' => 'done', 'priority' => 'high', 'due_date' => '2026-06-08', 'estimate_hours' => 8, 'sort_order' => 1],
            ['module' => 'CRM Lead & Contact Management', 'assignee' => 'yuda.prayoga@avatech.test', 'title' => 'Review UI form input lead B2B', 'status' => 'review', 'priority' => 'medium', 'due_date' => '2026-06-10', 'estimate_hours' => 6, 'sort_order' => 2],
            ['module' => 'CRM Lead & Contact Management', 'assignee' => 'ferry.achmad@avatech.test', 'title' => 'Implementasi pencarian kontak dan filter stage', 'status' => 'in_progress', 'priority' => 'high', 'due_date' => '2026-06-14', 'estimate_hours' => 14, 'sort_order' => 3],
            ['module' => 'Sales Pipeline & Follow-up Automation', 'assignee' => 'genta@avatech.test', 'title' => 'Bangun board pipeline sales per tahap deal', 'status' => 'in_progress', 'priority' => 'high', 'due_date' => '2026-06-16', 'estimate_hours' => 16, 'sort_order' => 1],
            ['module' => 'Sales Pipeline & Follow-up Automation', 'assignee' => 'ahmad.arlisyah@avatech.test', 'title' => 'Validasi rule reminder follow-up klien', 'status' => 'planned', 'priority' => 'medium', 'due_date' => '2026-06-18', 'estimate_hours' => 5, 'sort_order' => 2],
            ['module' => 'Sales Pipeline & Follow-up Automation', 'assignee' => 'irwan.kurniawan@avatech.test', 'title' => 'Integrasi activity timeline dengan update deal', 'status' => 'planned', 'priority' => 'medium', 'due_date' => '2026-06-20', 'estimate_hours' => 10, 'sort_order' => 3],
            ['module' => 'Reporting, Role Access, and Audit Readiness', 'assignee' => 'yuda.prayoga@avatech.test', 'title' => 'Desain ringkasan dashboard sales manager', 'status' => 'done', 'priority' => 'medium', 'due_date' => '2026-06-09', 'estimate_hours' => 7, 'sort_order' => 1],
            ['module' => 'Reporting, Role Access, and Audit Readiness', 'assignee' => 'ahmad.arlisyah@avatech.test', 'title' => 'Susun skenario UAT role sales dan manager', 'status' => 'review', 'priority' => 'high', 'due_date' => '2026-06-19', 'estimate_hours' => 8, 'sort_order' => 2],
            ['module' => 'Reporting, Role Access, and Audit Readiness', 'assignee' => 'ferry.achmad@avatech.test', 'title' => 'Tambahkan audit trail untuk perubahan status deal', 'status' => 'planned', 'priority' => 'high', 'due_date' => '2026-06-23', 'estimate_hours' => 12, 'sort_order' => 3],
            ['module' => 'Reporting, Role Access, and Audit Readiness', 'assignee' => 'genta@avatech.test', 'title' => 'Optimasi query laporan pipeline mingguan', 'status' => 'planned', 'priority' => 'low', 'due_date' => '2026-06-25', 'estimate_hours' => 6, 'sort_order' => 4],
        ];

        $tasks = [];
        foreach ($rows as $row) {
            $module = $modules[$row['module']] ?? null;
            $user = $users[$row['assignee']] ?? null;
            $tasks[$row['title']] = ProjectTask::updateOrCreate(
                ['project_id' => $project->id, 'title' => $row['title']],
                [
                    'project_module_id' => $module?->id,
                    'assigned_to' => $user?->id,
                    'description' => 'Demo task untuk walkthrough WBS, Kanban, dan workload Alpha CRM.',
                    'status' => $row['status'],
                    'priority' => $row['priority'],
                    'due_date' => Carbon::parse($row['due_date']),
                    'estimate_hours' => $row['estimate_hours'],
                    'sort_order' => $row['sort_order'],
                ],
            );
        }

        return $tasks;
    }

    private function seedMainMoms(Project $project, User $lead): void
    {
        $rows = [
            [
                'meeting_date' => '2026-06-03',
                'notes' => "Meeting Alpha CRM dengan PT Maju Jaya.\n- Sales butuh satu tempat untuk tracking lead, PIC, dan next follow-up.\n- Manager minta dashboard ringkas per stage dan aging deal.\n- Perlu role sales dan manager dipisah.\n- Risiko: data lama masih tersebar di spreadsheet.",
                'summary' => null,
                'status' => 'draft',
            ],
            [
                'meeting_date' => '2026-06-05',
                'notes' => "Review lanjutan Alpha CRM.\n- Scope MVP dikunci ke lead, pipeline, dashboard, dan audit perubahan deal.\n- UAT fokus pada input lead, perpindahan stage, dan laporan mingguan.",
                'summary' => "Ringkasan MoM:\n- MVP Alpha CRM mencakup lead management, pipeline sales, dashboard manager, dan audit aktivitas.\n- Tim Avatech menyiapkan WBS dari hasil meeting dan memprioritaskan flow input lead sampai laporan mingguan.\n\nAction Items:\n- SA/QA menyiapkan skenario UAT role sales dan manager.\n- Developer menyelesaikan board pipeline dan filter kontak.\n- UI/UX menyelesaikan review form lead B2B.",
                'status' => 'ai_fixed',
            ],
        ];

        foreach ($rows as $row) {
            ProjectMom::updateOrCreate(
                ['project_id' => $project->id, 'meeting_date' => Carbon::parse($row['meeting_date'])],
                [
                    'created_by' => $lead->id,
                    'notes' => $row['notes'],
                    'summary' => $row['summary'],
                    'status' => $row['status'],
                ],
            );
        }
    }

    /**
     * @param array<string, ProjectModule> $modules
     * @param array<string, ProjectTask> $tasks
     */
    private function seedMainQc(Project $project, array $modules, array $tasks, User $lead): void
    {
        $rows = [
            ['module' => 'CRM Lead & Contact Management', 'task' => 'Implementasi pencarian kontak dan filter stage', 'title' => 'Lead dapat dibuat dengan PIC utama', 'scenario' => 'Sales mengisi nama perusahaan, PIC, email, sumber lead, dan stage awal.', 'expected' => 'Lead tersimpan dan muncul di daftar dengan stage awal yang sesuai.', 'actual' => 'Sesuai ekspektasi pada data demo.', 'status' => 'passed', 'priority' => 'high', 'tested_at' => '2026-06-05 10:15:00'],
            ['module' => 'CRM Lead & Contact Management', 'task' => 'Review UI form input lead B2B', 'title' => 'Validasi email PIC menolak format salah', 'scenario' => 'User mengisi email PIC tanpa domain valid lalu menyimpan form.', 'expected' => 'Sistem menampilkan pesan validasi dan data tidak tersimpan.', 'actual' => null, 'status' => 'pending', 'priority' => 'medium', 'tested_at' => null],
            ['module' => 'Sales Pipeline & Follow-up Automation', 'task' => 'Bangun board pipeline sales per tahap deal', 'title' => 'Deal berpindah stage dari Qualified ke Proposal', 'scenario' => 'Sales memindahkan kartu deal ke stage Proposal pada board pipeline.', 'expected' => 'Stage deal berubah dan activity timeline mencatat perubahan.', 'actual' => 'Stage berubah, tetapi activity timeline belum tampil pada refresh pertama.', 'status' => 'failed', 'priority' => 'high', 'tested_at' => '2026-06-05 14:35:00'],
            ['module' => 'Sales Pipeline & Follow-up Automation', 'task' => 'Validasi rule reminder follow-up klien', 'title' => 'Reminder follow-up muncul untuk deal aging lebih dari 7 hari', 'scenario' => 'Manager membuka dashboard saat terdapat deal tanpa follow-up lebih dari 7 hari.', 'expected' => 'Deal ditandai perlu follow-up dan muncul di ringkasan perhatian.', 'actual' => 'Menunggu retest setelah penyesuaian rule aging.', 'status' => 'retest', 'priority' => 'high', 'tested_at' => null],
            ['module' => 'Reporting, Role Access, and Audit Readiness', 'task' => 'Susun skenario UAT role sales dan manager', 'title' => 'Role sales tidak dapat melihat laporan semua tim', 'scenario' => 'User role sales membuka dashboard manager lintas tim.', 'expected' => 'Akses ditolak atau data dibatasi ke scope user tersebut.', 'actual' => null, 'status' => 'pending', 'priority' => 'medium', 'tested_at' => null],
            ['module' => 'Reporting, Role Access, and Audit Readiness', 'task' => 'Tambahkan audit trail untuk perubahan status deal', 'title' => 'Perubahan stage deal tercatat di audit trail', 'scenario' => 'Sales mengubah stage deal lalu manager membuka audit trail.', 'expected' => 'Audit trail memuat aktor, waktu, dan stage baru.', 'actual' => null, 'status' => 'pending', 'priority' => 'medium', 'tested_at' => null],
        ];

        foreach ($rows as $row) {
            $qc = ProjectQcTest::updateOrCreate(
                ['project_id' => $project->id, 'title' => $row['title']],
                [
                    'project_module_id' => ($modules[$row['module']] ?? null)?->id,
                    'project_task_id' => ($tasks[$row['task']] ?? null)?->id,
                    'created_by' => $lead->id,
                    'scenario' => $row['scenario'],
                    'expected_result' => $row['expected'],
                    'actual_result' => $row['actual'],
                    'status' => $row['status'],
                    'priority' => $row['priority'],
                    'notes' => 'Demo QC compact untuk PDF export dan Quality Control walkthrough.',
                ],
            );

            $qc->forceFill([
                'tested_at' => $row['tested_at'] ? Carbon::parse($row['tested_at']) : null,
            ])->save();
        }
    }

    /**
     * @param array<string, Project> $supportingProjects
     * @param array<string, User> $users
     */
    private function seedTeamAssignments(Project $mainProject, $supportingProjects, array $users): void
    {
        $rows = [
            ['project' => $mainProject, 'user' => 'ahmad.arlisyah@avatech.test', 'title' => 'SA/QA lead Alpha CRM UAT', 'type' => 'review', 'status' => 'in_progress', 'hours' => 12, 'due' => '2026-06-19'],
            ['project' => $mainProject, 'user' => 'irwan.kurniawan@avatech.test', 'title' => 'Backend pipeline dan filter kontak', 'type' => 'task', 'status' => 'in_progress', 'hours' => 16, 'due' => '2026-06-18'],
            ['project' => $mainProject, 'user' => 'ferry.achmad@avatech.test', 'title' => 'Audit trail perubahan deal', 'type' => 'task', 'status' => 'planned', 'hours' => 10, 'due' => '2026-06-23'],
            ['project' => $mainProject, 'user' => 'yuda.prayoga@avatech.test', 'title' => 'Review UI lead form dan dashboard', 'type' => 'review', 'status' => 'done', 'hours' => 8, 'due' => '2026-06-10'],
            ['project' => $supportingProjects->get('BP'), 'user' => 'yuda.prayoga@avatech.test', 'title' => 'Prototype merchant portal onboarding', 'type' => 'task', 'status' => 'in_progress', 'hours' => 9, 'due' => '2026-06-21'],
            ['project' => $supportingProjects->get('GA'), 'user' => 'irwan.kurniawan@avatech.test', 'title' => 'Review API gateway regression issue', 'type' => 'support', 'status' => 'planned', 'hours' => 6, 'due' => '2026-06-17'],
        ];

        foreach ($rows as $row) {
            $project = $row['project'];
            $user = $users[$row['user']] ?? null;
            if (! $project || ! $user) {
                continue;
            }

            TeamAssignment::updateOrCreate(
                ['project_id' => $project->id, 'user_id' => $user->id, 'title' => $row['title']],
                [
                    'type' => $row['type'],
                    'status' => $row['status'],
                    'estimated_hours' => $row['hours'],
                    'due_date' => Carbon::parse($row['due']),
                    'notes' => 'Demo assignment untuk Team Management dan Executive Team Load.',
                ],
            );
        }
    }

    /**
     * @param array<string, Project> $projects
     * @param array<string, User> $users
     */
    private function seedSupportingProjects($projects, array $users): void
    {
        $bp = $projects->get('BP');
        if ($bp) {
            $bp->forceFill(['ai_wbs_generated' => false])->save();

            ProjectTask::updateOrCreate(
                ['project_id' => $bp->id, 'title' => 'Review UX merchant onboarding'],
                [
                    'project_module_id' => null,
                    'assigned_to' => ($users['yuda.prayoga@avatech.test'] ?? null)?->id,
                    'description' => 'Light demo task untuk konteks dashboard dan Team Load.',
                    'status' => 'in_progress',
                    'priority' => 'medium',
                    'due_date' => Carbon::parse('2026-06-21'),
                    'estimate_hours' => 9,
                    'sort_order' => 1,
                ],
            );
        }

        $ga = $projects->get('GA');
        if ($ga) {
            $ga->forceFill(['ai_wbs_generated' => false])->save();

            ProjectQcTest::updateOrCreate(
                ['project_id' => $ga->id, 'title' => 'Gateway menolak token partner kedaluwarsa'],
                [
                    'project_module_id' => null,
                    'project_task_id' => null,
                    'created_by' => ($users['ahmad.arlisyah@avatech.test'] ?? null)?->id,
                    'scenario' => 'Partner memanggil endpoint gateway dengan token yang sudah expired.',
                    'expected_result' => 'Gateway mengembalikan 401 dan tidak meneruskan request ke service internal.',
                    'actual_result' => 'Perlu retest setelah perbaikan response mapper.',
                    'status' => 'retest',
                    'priority' => 'high',
                    'tested_at' => null,
                    'notes' => 'Light demo QC untuk konteks risiko Executive Monitor.',
                ],
            );
        }
    }

    /**
     * @param array<string, ProjectModule> $modules
     * @param array<string, ProjectTask> $tasks
     */
    private function seedAuditTimeline(Project $project, User $actor, array $modules, array $tasks): void
    {
        $module = $modules['Sales Pipeline & Follow-up Automation'] ?? null;
        $task = $tasks['Bangun board pipeline sales per tahap deal'] ?? null;
        $mom = ProjectMom::where('project_id', $project->id)->where('status', 'ai_fixed')->first();
        $qc = ProjectQcTest::where('project_id', $project->id)->where('status', 'failed')->first();
        $now = AppTime::now();

        $rows = [
            ['action' => 'assignment_created', 'module' => 'Team Management', 'description' => 'Menambah penugasan <strong>SA/QA lead Alpha CRM UAT</strong> untuk demo walkthrough', 'auditable' => $project, 'new' => ['project_id' => $project->id], 'created_at' => $now->copy()->subHours(8)],
            ['action' => 'mom_created', 'module' => 'Project Master', 'description' => 'Menambah MoM demo pada proyek <strong>Alpha CRM</strong>', 'auditable' => $mom, 'new' => ['project_id' => $project->id], 'created_at' => $now->copy()->subHours(7)],
            ['action' => 'ai_mom_fixed', 'module' => 'Project Master', 'description' => 'AI merapikan MoM demo untuk proyek <strong>Alpha CRM</strong>', 'auditable' => $mom, 'new' => ['status' => 'ai_fixed'], 'created_at' => $now->copy()->subHours(6)],
            ['action' => 'ai_wbs_generated', 'module' => 'Project Master', 'description' => 'Generate WBS AI compact untuk proyek <strong>Alpha CRM</strong>', 'auditable' => $module, 'new' => ['project_id' => $project->id, 'modules' => 3], 'created_at' => $now->copy()->subHours(5)],
            ['action' => 'task_status_changed', 'module' => 'Project Master', 'description' => 'Mengubah status task <strong>Bangun board pipeline sales per tahap deal</strong> menjadi <strong>Doing</strong>', 'auditable' => $task, 'old' => ['status' => 'planned'], 'new' => ['status' => 'in_progress'], 'created_at' => $now->copy()->subHours(4)],
            ['action' => 'ai_test_cases_generated', 'module' => 'Project Master', 'description' => 'Generate test case AI compact untuk proyek <strong>Alpha CRM</strong>', 'auditable' => $qc, 'new' => ['project_id' => $project->id, 'total' => 6], 'created_at' => $now->copy()->subHours(3)],
            ['action' => 'qc_status_updated', 'module' => 'Project Master', 'description' => 'Mengubah status QC <strong>Deal berpindah stage dari Qualified ke Proposal</strong> menjadi <strong>Gagal</strong>', 'auditable' => $qc, 'old' => ['status' => 'pending'], 'new' => ['status' => 'failed'], 'created_at' => $now->copy()->subHours(2)],
            ['action' => 'wbs_pdf_exported', 'module' => 'Project Master', 'description' => 'Mengekspor WBS PDF untuk proyek <strong>Alpha CRM</strong>', 'auditable' => $project, 'new' => ['project_id' => $project->id, 'modules' => 3, 'tasks' => 10], 'created_at' => $now->copy()->subHour()],
            ['action' => 'test_case_pdf_exported', 'module' => 'Project Master', 'description' => 'Mengekspor Test Case PDF untuk proyek <strong>Alpha CRM</strong>', 'auditable' => $project, 'new' => ['project_id' => $project->id, 'total' => 6], 'created_at' => $now->copy()->subMinutes(35)],
        ];

        foreach ($rows as $row) {
            AuditLog::updateOrCreate(
                ['action' => $row['action'], 'module' => $row['module'], 'description' => $row['description']],
                [
                    'user_id' => $actor->id,
                    'auditable_type' => $row['auditable']?->getMorphClass(),
                    'auditable_id' => $row['auditable']?->getKey(),
                    'old_values' => $row['old'] ?? null,
                    'new_values' => $row['new'] ?? null,
                    'ip_address' => '127.0.0.1',
                    'user_agent' => 'Smart-PMIS Demo Seeder',
                    'created_at' => $row['created_at'],
                ],
            );
        }
    }

    private function seedAiMonitorHistory(Project $project, Client $client, User $actor): void
    {
        $now = AppTime::now();
        $rows = [
            ['feature' => 'mom_fixer', 'provider' => 'gemini', 'model' => 'gemini-1.5-flash', 'status' => 'success', 'fallback_path' => ['gemini'], 'prompt_tokens' => 980, 'completion_tokens' => 360, 'latency_ms' => 1420, 'created_at' => $now->copy()->subHours(6)],
            ['feature' => 'wbs_generator', 'provider' => 'gemini', 'model' => 'gemini-1.5-flash', 'status' => 'success', 'fallback_path' => ['gemini'], 'prompt_tokens' => 1320, 'completion_tokens' => 710, 'latency_ms' => 2380, 'created_at' => $now->copy()->subHours(5)],
            ['feature' => 'test_case_generator', 'provider' => 'groq', 'model' => 'llama-3.1-70b-versatile', 'status' => 'success', 'fallback_path' => ['gemini_failed', 'groq'], 'prompt_tokens' => 1180, 'completion_tokens' => 520, 'latency_ms' => 1960, 'created_at' => $now->copy()->subHours(3)],
            ['feature' => 'mom_fixer', 'provider' => 'openrouter', 'model' => 'meta-llama/llama-3.1-8b-instruct', 'status' => 'failed', 'fallback_path' => ['gemini_failed', 'groq_failed', 'openrouter_failed'], 'prompt_tokens' => 760, 'completion_tokens' => 0, 'latency_ms' => 3200, 'error_message' => 'Demo metadata: provider fallback exhausted.', 'created_at' => $now->copy()->subHours(2)],
            ['feature' => 'test_case_generator', 'provider' => 'gemini', 'model' => 'gemini-1.5-flash', 'status' => 'success', 'fallback_path' => ['gemini'], 'prompt_tokens' => 1090, 'completion_tokens' => 480, 'latency_ms' => 1710, 'created_at' => $now->copy()->subMinutes(50)],
        ];

        foreach ($rows as $row) {
            $log = AiRequestLog::firstOrNew([
                'project_id' => $project->id,
                'feature' => $row['feature'],
                'provider' => $row['provider'],
                'model' => $row['model'],
                'status' => $row['status'],
            ]);

            $log->fill([
                'user_id' => $actor->id,
                'client_id' => $client->id,
                'fallback_path' => $row['fallback_path'],
                'prompt_tokens' => $row['prompt_tokens'],
                'completion_tokens' => $row['completion_tokens'],
                'total_tokens' => $row['prompt_tokens'] + $row['completion_tokens'],
                'latency_ms' => $row['latency_ms'],
                'error_message' => $row['error_message'] ?? null,
            ]);

            $log->forceFill([
                'created_at' => $row['created_at'],
                'updated_at' => $row['created_at'],
            ])->save();
        }
    }
}
