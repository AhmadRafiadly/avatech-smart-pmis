<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Project;
use App\Models\ProjectTask;
use App\Models\ProjectTaskDependency;
use App\Models\TeamAssignment;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class TaskDependencyLiteTest extends TestCase
{
    public function test_project_detail_shows_grouped_workflow_tabs_and_sections(): void
    {
        [$user, $project] = $this->projectContext();

        $this->actingAs($user)
            ->get(route('projects.show', $project) . '?tab=gathering-planning')
            ->assertOk()
            ->assertSee('Overview')
            ->assertSee('Gathering &amp; Planning', false)
            ->assertSee('Development Monitoring')
            ->assertSee('Testing &amp; Evidence', false)
            ->assertSee('Requirement Intake')
            ->assertSee('Kanban Workspace')
            ->assertSee('data-gp-chip="intake"', false)
            ->assertSee('data-gp-chip="mom"', false)
            ->assertSee('data-gp-chip="wbs"', false)
            ->assertDontSee('data-gp-chip="timeline"', false)
            ->assertDontSee('data-gp-chip="dependencies"', false)
            ->assertSee('data-gp-section="intake"', false)
            ->assertSee('data-gp-section="mom"', false)
            ->assertSee('data-gp-section="wbs"', false)
            ->assertDontSee('data-gp-section="timeline"', false)
            ->assertDontSee('data-gp-section="dependencies"', false)
            ->assertSee('Testing &amp; Evidence', false);
    }

    public function test_project_detail_keeps_old_hash_aliases_in_tab_mapper(): void
    {
        [$user, $project] = $this->projectContext();

        $this->actingAs($user)
            ->get(route('projects.show', $project) . '?tab=requirement-intake')
            ->assertOk()
            ->assertSee("'requirement-intake': { tab: 'gathering-planning', section: 'intake' }", false)
            ->assertSee("aiplanning: { tab: 'gathering-planning', section: 'wbs' }", false)
            ->assertSee("kanban: { tab: 'development-monitoring' }", false)
            ->assertSee("'quality-control': { tab: 'testing-evidence' }", false)
            ->assertDontSee("timeline: { tab: 'gathering-planning', section: 'timeline' }", false)
            ->assertDontSee("dependencies: { tab: 'gathering-planning', section: 'dependencies' }", false);
    }

    public function test_project_detail_does_not_show_timeline_or_dependency_sections(): void
    {
        [$user, $project, $a, $b] = $this->projectContextWithTasks(2);
        $this->createDependency($project, $a, $b, $user);

        $this->actingAs($user)
            ->get(route('projects.show', $project) . '?tab=gathering-planning')
            ->assertOk()
            ->assertDontSee('Timeline Jadwal Opsional')
            ->assertDontSee('Gantt-lite')
            ->assertDontSee('Daftar Dependency')
            ->assertDontSee('Tambah Dependency')
            ->assertDontSee('Task yang Menunggu Task Pendahulu');
    }

    public function test_authorized_user_can_store_task_start_date(): void
    {
        [$user, $project] = $this->projectContext();

        $this->actingAs($user)
            ->post(route('projects.tasks.store', $project), [
                'title' => 'Task Dengan Start Date',
                'status' => 'planned',
                'priority' => 'medium',
                'start_date' => '2026-07-01',
                'due_date' => '2026-07-05',
                'estimate_hours' => 8,
            ])
            ->assertRedirect(route('projects.show', $project) . '#workspace');

        $this->assertDatabaseHas('project_tasks', [
            'project_id' => $project->id,
            'title' => 'Task Dengan Start Date',
            'start_date' => '2026-07-01',
            'due_date' => '2026-07-05',
        ]);
    }

    public function test_task_start_date_after_due_date_is_rejected(): void
    {
        [$user, $project] = $this->projectContext();

        $this->actingAs($user)
            ->post(route('projects.tasks.store', $project), [
                'title' => 'Task Jadwal Tidak Valid',
                'status' => 'planned',
                'priority' => 'medium',
                'start_date' => '2026-07-10',
                'due_date' => '2026-07-05',
            ])
            ->assertSessionHasErrors('start_date');
    }

    public function test_ceo_pm_project_detail_stays_read_only_for_tasks(): void
    {
        [$user, $project] = $this->projectContextWithTasks(1, 'ceo_pm');

        $this->actingAs($user)
            ->get(route('projects.show', $project) . '?tab=development-monitoring')
            ->assertOk()
            ->assertSee('Kanban Workspace')
            ->assertDontSee('Tambah Task</button>', false);
    }

    public function test_dependency_create_and_delete_routes_are_disabled(): void
    {
        [$user, $project, $a, $b] = $this->projectContextWithTasks(2);
        $dependency = $this->createDependency($project, $a, $b, $user);

        $this->actingAs($user)
            ->post("/projects/{$project->id}/task-dependencies", [
                'predecessor_task_id' => $a->id,
                'successor_task_id' => $b->id,
            ])
            ->assertNotFound();

        $this->actingAs($user)
            ->delete("/projects/{$project->id}/task-dependencies/{$dependency->id}")
            ->assertNotFound();
    }

    public function test_ceo_pm_cannot_manage_requirement_intake_from_project_detail(): void
    {
        [$user, $project] = $this->projectContext('ceo_pm');

        $this->actingAs($user)
            ->post(route('projects.requirement-intake.store', $project), [
                'title' => 'Read Only Intake Test',
                'source_type' => 'prd',
                'priority' => 'should',
                'summary' => 'CEO PM should not create intake from read-only Project Detail.',
            ])
            ->assertForbidden();
    }

    private function createDependency(Project $project, ProjectTask $predecessor, ProjectTask $successor, User $user): ProjectTaskDependency
    {
        return ProjectTaskDependency::create([
            'project_id' => $project->id,
            'predecessor_task_id' => $predecessor->id,
            'successor_task_id' => $successor->id,
            'dependency_type' => 'finish_to_start',
            'created_by' => $user->id,
        ]);
    }

    private function projectContextWithTasks(int $count, string $roleName = 'sa_qa'): array
    {
        [$user, $project] = $this->projectContext($roleName);
        $tasks = collect(range(1, $count))->map(fn (int $idx) => ProjectTask::create([
            'project_id' => $project->id,
            'title' => 'Dependency Test Task ' . $idx . ' ' . uniqid(),
            'status' => 'planned',
            'priority' => 'medium',
            'start_date' => now()->addDays($idx)->toDateString(),
            'due_date' => now()->addDays($idx + 2)->toDateString(),
            'estimate_hours' => 4,
            'sort_order' => $idx,
        ]))->all();

        return array_merge([$user, $project], $tasks);
    }

    private function projectContext(string $roleName = 'sa_qa'): array
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $role = Role::findOrCreate($roleName, 'web');
        $user = User::factory()->create();
        $user->assignRole($role);

        $client = Client::create([
            'name' => 'Dependency Test Client ' . uniqid(),
            'code' => strtoupper(substr(uniqid(), -4)),
            'tier' => 'standard',
        ]);
        $project = Project::create([
            'code' => strtoupper(substr(uniqid(), -4)),
            'name' => 'Dependency Test Project ' . uniqid(),
            'client_id' => $client->id,
            'lead_user_id' => $user->id,
            'phase' => 'Development',
            'due_at' => now()->addDays(14)->toDateString(),
            'progress' => 0,
            'status' => 'on-track',
        ]);

        if (in_array($roleName, ['sa_qa', 'uiux_designer', 'ui_ux', 'fullstack_dev'], true)) {
            TeamAssignment::create([
                'user_id' => $user->id,
                'project_id' => $project->id,
                'title' => 'Dependency Test Assignment',
                'type' => 'task',
                'responsibilities' => ['saqa_mom_qc'],
                'status' => 'active',
                'estimated_hours' => 8,
            ]);
        }

        return [$user, $project];
    }
}
