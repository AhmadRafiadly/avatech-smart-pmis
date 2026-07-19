<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Client;
use App\Models\Project;
use App\Models\ProjectQcTest;
use App\Models\ProjectTask;
use App\Models\TeamAssignment;
use App\Models\User;
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
            'title' => 'Siapkan Mockup UI/UX & Handover Desain',
            'is_design_deliverable' => true,
        ]);
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
            'title' => 'Siapkan Mockup UI/UX & Handover Desain',
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
