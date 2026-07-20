<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Client;
use App\Models\Project;
use App\Models\ProjectMom;
use App\Models\ProjectModule;
use App\Models\ProjectQcTest;
use App\Models\ProjectRequirementInboxItem;
use App\Models\ProjectTask;
use App\Models\ProjectTaskDesignDeliverable;
use App\Models\TeamAssignment;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class DesignAndQcFlowTest extends TestCase
{
    public function test_ai_wbs_without_design_moves_project_to_development(): void
    {
        [$user, $project] = $this->context(false);

        $this->actingAs($user)->post(route('projects.ai-wbs.apply', $project), $this->wbsPayload())
            ->assertRedirect(route('projects.show', $project).'#wbs');

        $this->assertSame('Development', $project->fresh()->phase);
        $this->assertDatabaseHas('project_tasks', ['project_id' => $project->id, 'title' => 'Implementasi Login']);
        $this->assertDatabaseMissing('project_modules', ['project_id' => $project->id, 'title' => 'UI/UX Design']);
    }

    public function test_ai_wbs_with_design_creates_handover_and_moves_project_to_design(): void
    {
        [$user, $project] = $this->context(true);

        $this->actingAs($user)->post(route('projects.ai-wbs.apply', $project), $this->wbsPayload());

        $this->assertSame('Design', $project->fresh()->phase);
        $this->assertDatabaseHas('project_modules', ['project_id' => $project->id, 'title' => 'UI/UX Design']);
        $this->assertDatabaseHas('project_tasks', [
            'project_id' => $project->id,
            'title' => 'Siapkan Mockup UI/UX',
            'description' => 'Menyiapkan link Figma, PDF mockup, atau deliverable desain lain sebagai acuan sebelum Development dimulai.',
            'is_design_deliverable' => true,
        ]);
        $this->assertSame(1, ProjectTask::where('project_id', $project->id)->where('is_design_deliverable', true)->count());
    }

    public function test_design_gate_classifier_is_strictly_title_based(): void
    {
        $method = new \ReflectionMethod(\App\Http\Controllers\ProjectController::class, 'isDesignDeliverableDraft');
        $controller = app(\App\Http\Controllers\ProjectController::class);

        $this->assertTrue($method->invoke($controller, 'Siapkan Mockup UI/UX'));
        foreach ([
            'Buat Halaman Utama berdasarkan mockup',
            'Buat Halaman Layanan & Portofolio',
            'Implementasi desain Figma',
            'Siapkan Dokumentasi Pengelolaan CMS',
            'Sesi Handover & Pelatihan Klien',
            'Review Mockup UI/UX',
            'Revisi Desain UI/UX',
        ] as $title) {
            $this->assertFalse($method->invoke($controller, $title));
        }
    }

    public function test_ai_wbs_normalizes_duplicate_design_related_tasks_to_one_gate(): void
    {
        [$user, $project] = $this->context(true);
        $payload = $this->wbsPayload();
        $payload['modules'][] = [
            'include' => '1',
            'title' => 'UI/UX Design',
            'status' => 'pending_design',
            'estimate_hours' => 20,
            'tasks' => [
                ['include' => '1', 'title' => 'Siapkan Mockup UI/UX', 'priority' => 'high', 'estimate_hours' => 12],
                ['include' => '1', 'title' => 'Implementasi desain Figma', 'description' => 'Mengikuti mockup dan handover.', 'priority' => 'medium', 'estimate_hours' => 8],
            ],
        ];

        $this->actingAs($user)->post(route('projects.ai-wbs.apply', $project), $payload)->assertSessionHasNoErrors();

        $this->assertSame(1, ProjectTask::where('project_id', $project->id)->where('is_design_deliverable', true)->count());
        $this->assertDatabaseHas('project_tasks', ['project_id' => $project->id, 'title' => 'Implementasi desain Figma', 'is_design_deliverable' => false]);
    }

    public function test_ordinary_task_update_ignores_design_module_and_description_mentions(): void
    {
        [$user, $project] = $this->context(true);
        $module = ProjectModule::create(['project_id' => $project->id, 'title' => 'UI/UX Design', 'status' => 'pending_design', 'sort_order' => 1]);
        $task = ProjectTask::create($this->taskAttributes($project));

        $this->actingAs($user)->put(route('projects.tasks.update', [$project, $task]), [
            'title' => 'Siapkan Dokumentasi Pengelolaan CMS',
            'project_module_id' => $module->id,
            'status' => 'planned',
            'priority' => 'medium',
            'estimate_hours' => 8,
            'description' => 'Dokumentasi mengacu pada mockup Figma dan sesi handover.',
        ])->assertSessionHasNoErrors();

        $this->assertFalse((bool) $task->fresh()->is_design_deliverable);
    }

    public function test_design_auto_assignment_prefers_exactly_one_contributor_and_excludes_archived_users(): void
    {
        [$user, $project] = $this->context(true);
        $globalDesigner = User::factory()->create();
        $globalDesigner->assignRole(Role::findOrCreate('uiux_designer', 'web'));
        $contributor = User::factory()->create();
        $contributor->assignRole(Role::findOrCreate('fullstack_dev', 'web'));
        TeamAssignment::create([
            'user_id' => $contributor->id,
            'project_id' => $project->id,
            'title' => 'Design contributor',
            'type' => 'task',
            'responsibilities' => ['uiux_design'],
            'status' => 'planned',
        ]);
        $archivedContributor = User::factory()->create(['archived_at' => now()]);
        $archivedContributor->assignRole(Role::findOrCreate('fullstack_dev', 'web'));
        TeamAssignment::create([
            'user_id' => $archivedContributor->id,
            'project_id' => $project->id,
            'title' => 'Archived design contributor',
            'type' => 'task',
            'responsibilities' => ['uiux_design'],
            'status' => 'planned',
        ]);

        $this->actingAs($user)->post(route('projects.ai-wbs.apply', $project), $this->wbsPayload());

        $this->assertDatabaseHas('project_tasks', [
            'project_id' => $project->id,
            'is_design_deliverable' => true,
            'assigned_to' => $contributor->id,
        ]);
    }

    public function test_design_auto_assignment_stays_unassigned_when_a_level_has_multiple_eligible_users(): void
    {
        [$user, $project] = $this->context(true);
        foreach (range(1, 2) as $index) {
            $designer = User::factory()->create();
            $designer->assignRole(Role::findOrCreate('uiux_designer', 'web'));
            TeamAssignment::create([
                'user_id' => $designer->id,
                'project_id' => $project->id,
                'title' => 'Designer '.$index,
                'type' => 'task',
                'responsibilities' => ['uiux_design'],
                'status' => 'planned',
            ]);
        }

        $this->actingAs($user)->post(route('projects.ai-wbs.apply', $project), $this->wbsPayload());

        $task = ProjectTask::where('project_id', $project->id)->where('is_design_deliverable', true)->firstOrFail();
        $this->assertNull($task->assigned_to);
    }

    public function test_non_uiux_global_role_with_uiux_design_responsibility_can_submit_deliverable(): void
    {
        [$user, $project] = $this->context(true, 'fullstack_dev', ['uiux_design']);
        $project->update(['phase' => 'Design']);
        $task = $this->designTask($project, $user);

        $this->actingAs($user)->post(route('projects.tasks.design-deliverables.store', [$project, $task]), [
            'title' => 'Final Mockup',
            'figma_url' => 'https://www.figma.com/file/test/final-mockup',
        ])->assertRedirect();

        $this->assertDatabaseHas('project_task_design_deliverables', [
            'project_task_id' => $task->id,
            'title' => 'Final Mockup',
            'created_by' => $user->id,
        ]);
    }

    public function test_design_gate_card_is_visually_distinct_and_ordinary_task_is_unchanged(): void
    {
        [$designer, $designProject] = $this->context(true, 'fullstack_dev', ['uiux_design']);
        $designTask = $this->designTask($designProject, $designer);
        ProjectTaskDesignDeliverable::create([
            'project_task_id' => $designTask->id,
            'title' => 'Master Figma',
            'figma_url' => 'https://www.figma.com/file/test/master',
            'submitted_at' => now(),
            'created_by' => $designer->id,
        ]);

        $designResponse = $this->actingAs($designer)->get(route('projects.show', $designProject));
        $designResponse->assertOk()
            ->assertSee('data-design-gate-card', false)
            ->assertSeeText('UI/UX DESIGN GATE')
            ->assertSeeText('Mockup desain wajib tersedia sebelum Development dimulai.')
            ->assertDontSeeText('Handover diperlukan')
            ->assertSeeText('Siap development')
            ->assertSeeText('Design Deliverables')
            ->assertSeeText('1 item')
            ->assertSee('data-design-handover-details', false)
            ->assertDontSee('data-design-handover-details open', false)
            ->assertSeeText('Master Figma')
            ->assertSeeText('Figma/mockup')
            ->assertSeeText('Edit deliverable')
            ->assertSeeText('Hapus Deliverable')
            ->assertSeeText('+ Tambah Deliverable');

        [$emptyDesigner, $emptyProject] = $this->context(true, 'fullstack_dev', ['uiux_design']);
        $this->designTask($emptyProject, $emptyDesigner);
        $this->actingAs($emptyDesigner)->get(route('projects.show', $emptyProject))
            ->assertOk()
            ->assertSeeText('Belum lengkap')
            ->assertSeeText('Design Deliverables')
            ->assertSeeText('0 item');

        [$developer, $ordinaryProject] = $this->context(false, 'fullstack_dev');
        ProjectTask::create($this->taskAttributes($ordinaryProject));
        $this->actingAs($developer)->get(route('projects.show', $ordinaryProject))
            ->assertOk()
            ->assertDontSee('data-design-gate-card', false)
            ->assertDontSee('data-design-handover-details', false)
            ->assertDontSeeText('UI/UX DESIGN GATE')
            ->assertDontSeeText('Mockup desain wajib tersedia sebelum Development dimulai.')
            ->assertDontSeeText('Belum lengkap')
            ->assertDontSeeText('Siap development');
    }

    public function test_separate_design_and_development_contributors_require_handover_context(): void
    {
        [$designer, $project] = $this->context(true, 'fullstack_dev', ['uiux_design']);
        $this->designTask($project, $designer);
        $developer = User::factory()->create();
        $developer->assignRole(Role::findOrCreate('fullstack_dev', 'web'));
        TeamAssignment::create([
            'user_id' => $developer->id,
            'project_id' => $project->id,
            'title' => 'Developer',
            'type' => 'project',
            'responsibilities' => ['fullstack_dev'],
            'status' => 'in_progress',
        ]);

        $this->actingAs($designer)->get(route('projects.show', $project))
            ->assertOk()
            ->assertSeeText('Handover diperlukan')
            ->assertSeeText('Mockup perlu diserahkan kepada tim Development sebelum implementasi dimulai.');
    }

    public function test_design_deliverable_pdf_preview_uses_internal_blob_renderer_and_preserves_guards(): void
    {
        Storage::fake('local');
        [$owner, $project] = $this->context(true, 'fullstack_dev', ['uiux_design']);
        $task = $this->designTask($project, $owner);
        $pdf = "%PDF-1.4\nmock";
        $path = 'project-design-deliverables/final-mockup.pdf';
        Storage::disk('local')->put($path, $pdf);
        $deliverable = ProjectTaskDesignDeliverable::create([
            'project_task_id' => $task->id,
            'title' => 'Final Mockup',
            'pdf_file_path' => $path,
            'submitted_at' => now(),
            'created_by' => $owner->id,
        ]);

        $preview = $this->actingAs($owner)->get(route('projects.tasks.design-deliverables.preview', [$project, $task, $deliverable]));
        $csp = $preview->headers->get('Content-Security-Policy');
        preg_match("/script-src 'nonce-([^']+)'/", $csp, $nonce);
        $preview->assertOk()
            ->assertHeader('Content-Type', 'text/html; charset=UTF-8')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertSee(base64_encode($pdf), false)
            ->assertSee('nonce="'.$nonce[1].'"', false)
            ->assertSee('new Uint8Array(binary.length)', false)
            ->assertSee("new Blob([bytes], { type: 'application/pdf' })", false)
            ->assertSee('URL.createObjectURL', false)
            ->assertSee('URL.revokeObjectURL', false)
            ->assertSee('frame.src = objectUrl', false)
            ->assertDontSee('src="data:application/pdf', false)
            ->assertDontSee('fetch(', false)
            ->assertDontSee('XMLHttpRequest', false)
            ->assertDontSee('docs.google.com', false)
            ->assertDontSee('view.officeapps.live.com', false)
            ->assertDontSee($path, false);
        $this->assertSame("default-src 'none'; script-src 'nonce-{$nonce[1]}'; style-src 'unsafe-inline'; frame-src blob:; base-uri 'none'; object-src 'none'; form-action 'none'; frame-ancestors 'self'", $csp);

        $download = $this->actingAs($owner)->get(route('projects.tasks.design-deliverables.download', [$project, $task, $deliverable]));
        $download->assertDownload('final-mockup.pdf')->assertHeader('Content-Type', 'application/pdf');
        $this->assertSame($pdf, file_get_contents($download->baseResponse->getFile()->getPathname()));

        [$outsider, $otherProject] = $this->context(true, 'fullstack_dev', ['uiux_design']);
        [$manager] = $this->context(true, 'ceo_pm');
        $otherTask = $this->designTask($otherProject, $outsider);
        $this->actingAs($outsider)->get(route('projects.tasks.design-deliverables.preview', [$project, $task, $deliverable]))->assertRedirect(route('projects.index'));
        $this->actingAs($manager)->get(route('projects.tasks.design-deliverables.preview', [$otherProject, $task, $deliverable]))->assertNotFound();
        $this->actingAs($manager)->get(route('projects.tasks.design-deliverables.preview', [$project, $otherTask, $deliverable]))->assertNotFound();
    }

    public function test_reviewed_ai_polished_mom_offers_editable_requirement_prefill_without_saving(): void
    {
        [$user, $project] = $this->context(false);
        ProjectMom::create([
            'project_id' => $project->id,
            'created_by' => $user->id,
            'meeting_date' => now()->toDateString(),
            'notes' => 'Raw meeting notes.',
            'summary' => 'Klien membutuhkan approval berjenjang.',
            'status' => 'ai_fixed',
        ]);

        $this->actingAs($user)->get(route('projects.show', $project))
            ->assertOk()
            ->assertSee('Buat Requirement dari MoM')
            ->assertSee('Klien membutuhkan approval berjenjang.');

        $this->assertSame(0, ProjectRequirementInboxItem::where('project_id', $project->id)->count());
    }

    public function test_design_deliverable_is_required_before_done(): void
    {
        [$user, $project] = $this->context(true, 'fullstack_dev', ['uiux_design']);
        $project->update(['phase' => 'Design']);
        $task = $this->designTask($project, $user);

        $this->actingAs($user)->patch(route('projects.tasks.status', [$project, $task]), ['status' => 'done'])
            ->assertSessionHasErrors('status');

        $this->assertSame('planned', $task->fresh()->status);
    }

    public function test_requires_design_cannot_change_after_ai_wbs_is_applied(): void
    {
        [$user, $project] = $this->context(false, 'ceo_pm');
        $project->update(['ai_wbs_generated' => true]);

        $this->actingAs($user)->put(route('projects.update', $project), [
            'code' => $project->code,
            'name' => $project->name,
            'client_id' => $project->client_id,
            'due_at' => $project->due_at?->format('Y-m-d'),
            'requires_design' => '1',
        ])->assertSessionHasErrors('requires_design');

        $this->assertFalse($project->fresh()->requires_design);
    }

    public function test_failed_qc_requires_actual_result_and_stores_evidence_and_audit_without_touching_tasks(): void
    {
        [$user, $project] = $this->context(false);
        $project->update(['phase' => 'QC']);
        $task = ProjectTask::create($this->taskAttributes($project));
        $qc = $this->qc($project, $user, $task);

        $this->actingAs($user)->patch(route('projects.qc.update', [$project, $qc]), ['status' => 'failed'])
            ->assertSessionHasErrors('actual_result');

        $taskCount = ProjectTask::where('project_id', $project->id)->count();
        $response = $this->actingAs($user)->patch(route('projects.qc.update', [$project, $qc]), [
            'status' => 'failed',
            'actual_result' => 'API returned 500 instead of validation response.',
            'notes' => 'Screenshot stored in external evidence repository.',
        ])->assertRedirect(route('projects.show', $project).'#qc');

        $response->assertSessionHas('status', fn (string $message) => ! str_contains($message, 'Pending'));
        $qc->refresh();
        $this->assertSame('failed', $qc->status);
        $this->assertSame('API returned 500 instead of validation response.', $qc->actual_result);
        $this->assertNotNull($qc->tested_at);
        $this->assertSame('done', $task->fresh()->status);
        $this->assertSame($taskCount, ProjectTask::where('project_id', $project->id)->count());
        $this->assertTrue(AuditLog::where('action', 'qc_status_updated')->where('auditable_id', $qc->id)->exists());
    }

    public function test_failed_to_retest_clears_tested_at_then_retest_to_passed_sets_fresh_verdict(): void
    {
        [$user, $project] = $this->context(false);
        $project->update(['phase' => 'QC']);
        $qc = $this->qc($project, $user);
        $qc->update(['status' => 'failed', 'actual_result' => 'Initial failure.', 'tested_at' => now()->subDay()]);

        $this->actingAs($user)->patch(route('projects.qc.update', [$project, $qc]), ['status' => 'retest']);
        $this->assertSame('retest', $qc->fresh()->status);
        $this->assertNull($qc->fresh()->tested_at);

        $this->actingAs($user)->patch(route('projects.qc.update', [$project, $qc]), [
            'status' => 'passed',
            'actual_result' => 'Validation response now matches expectation.',
        ]);

        $this->assertSame('passed', $qc->fresh()->status);
        $this->assertNotNull($qc->fresh()->tested_at);
    }

    private function context(bool $requiresDesign, string $roleName = 'sa_qa', array $responsibilities = ['saqa_mom_qc']): array
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $user = User::factory()->create();
        $user->assignRole(Role::findOrCreate($roleName, 'web'));
        $client = Client::create([
            'name' => 'Focused Flow Client '.uniqid(),
            'code' => strtoupper(substr(uniqid(), -4)),
            'tier' => 'standard',
        ]);
        $project = Project::create([
            'code' => strtoupper(substr(uniqid(), -4)),
            'name' => 'Focused Flow Project '.uniqid(),
            'client_id' => $client->id,
            'lead_user_id' => $user->id,
            'phase' => 'Planning',
            'due_at' => now()->addDays(14)->toDateString(),
            'progress' => 0,
            'status' => 'on-track',
            'requires_design' => $requiresDesign,
        ]);

        if ($roleName !== 'ceo_pm') {
            TeamAssignment::create([
                'user_id' => $user->id,
                'project_id' => $project->id,
                'title' => 'Focused Flow Assignment',
                'type' => 'task',
                'responsibilities' => $responsibilities,
                'status' => 'in_progress',
                'estimated_hours' => 8,
            ]);
        }

        return [$user, $project];
    }

    private function wbsPayload(): array
    {
        return ['modules' => [[
            'include' => '1',
            'title' => 'Authentication',
            'status' => 'pending_design',
            'estimate_hours' => 8,
            'tasks' => [[
                'include' => '1',
                'title' => 'Implementasi Login',
                'priority' => 'high',
                'estimate_hours' => 8,
            ]],
        ]]];
    }

    private function designTask(Project $project, User $user): ProjectTask
    {
        return ProjectTask::create(array_merge($this->taskAttributes($project), [
            'assigned_to' => $user->id,
            'title' => 'Siapkan Mockup UI/UX',
            'status' => 'planned',
            'is_design_deliverable' => true,
        ]));
    }

    private function taskAttributes(Project $project): array
    {
        return [
            'project_id' => $project->id,
            'title' => 'Implementasi Login '.uniqid(),
            'status' => 'done',
            'priority' => 'high',
            'estimate_hours' => 8,
            'sort_order' => 1,
        ];
    }

    private function qc(Project $project, User $user, ?ProjectTask $task = null): ProjectQcTest
    {
        return ProjectQcTest::create([
            'project_id' => $project->id,
            'project_task_id' => $task?->id,
            'created_by' => $user->id,
            'title' => 'Login validation '.uniqid(),
            'scenario' => 'Submit invalid credentials.',
            'expected_result' => 'Validation error is displayed.',
            'status' => 'pending',
            'priority' => 'high',
        ]);
    }
}
