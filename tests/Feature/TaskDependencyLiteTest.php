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
            ->assertSee('Dependencies')
            ->assertSee('Timeline')
            ->assertSee('Kanban Workspace')
            ->assertSee('data-gp-chip="intake"', false)
            ->assertSee('data-gp-chip="mom"', false)
            ->assertSee('data-gp-chip="wbs"', false)
            ->assertSee('data-gp-chip="timeline"', false)
            ->assertSee('data-gp-chip="dependencies"', false)
            ->assertSee('data-gp-section="intake"', false)
            ->assertSee('data-gp-section="mom"', false)
            ->assertSee('data-gp-section="wbs"', false)
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
            ->assertSee("'quality-control': { tab: 'testing-evidence' }", false);
    }

    public function test_authenticated_user_can_access_dependency_section(): void
    {
        [$user, $project] = $this->projectContext();

        $this->actingAs($user)
            ->get(route('projects.show', $project))
            ->assertOk()
            ->assertSee('Dependencies')
            ->assertSee('Dependency membantu PM melihat task yang harus menunggu task pendahulu selesai. Sistem tidak mengubah due date otomatis.');
    }

    public function test_authenticated_allowed_user_can_access_timeline_section(): void
    {
        [$user, $project, $a] = $this->projectContextWithTasks(1);

        $this->actingAs($user)
            ->get(route('projects.show', $project) . '?tab=timeline')
            ->assertOk()
            ->assertSee('Timeline menampilkan ringkasan jadwal task berdasarkan due date manual, status task, dan dependency. Sistem tidak mengubah jadwal otomatis.')
            ->assertSee($a->title);
    }

    public function test_ceo_pm_can_view_timeline(): void
    {
        [$user, $project] = $this->projectContext('ceo_pm');

        $this->actingAs($user)
            ->get(route('projects.show', $project) . '?tab=timeline')
            ->assertOk()
            ->assertSee('Timeline');
    }

    public function test_blocked_tasks_appear_in_timeline(): void
    {
        [$user, $project, $a, $b] = $this->projectContextWithTasks(2);
        $this->createDependency($project, $a, $b, $user);

        $this->actingAs($user)
            ->get(route('projects.show', $project) . '?tab=timeline')
            ->assertOk()
            ->assertSee('Task yang Menunggu Task Pendahulu')
            ->assertSee($b->title)
            ->assertSee($a->title)
            ->assertSee('Blocked');
    }

    public function test_ceo_pm_can_view_but_cannot_manage_dependencies(): void
    {
        [$user, $project, $a, $b] = $this->projectContextWithTasks(2, 'ceo_pm');

        $this->actingAs($user)
            ->get(route('projects.show', $project) . '?tab=dependencies')
            ->assertOk()
            ->assertSee('Anda memiliki akses lihat saja pada dependency proyek ini.')
            ->assertDontSee('Tambah Dependency</button>', false);

        $this->actingAs($user)
            ->post(route('projects.task-dependencies.store', $project), [
                'predecessor_task_id' => $a->id,
                'successor_task_id' => $b->id,
            ])
            ->assertForbidden();
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

    public function test_cannot_create_self_dependency(): void
    {
        [$user, $project, $task] = $this->projectContextWithTasks(1);

        $this->actingAs($user)
            ->from(route('projects.show', $project) . '#dependencies')
            ->post(route('projects.task-dependencies.store', $project), [
                'predecessor_task_id' => $task->id,
                'successor_task_id' => $task->id,
            ])
            ->assertSessionHasErrors('successor_task_id');
    }

    public function test_cannot_create_duplicate_dependency(): void
    {
        [$user, $project, $a, $b] = $this->projectContextWithTasks(2);

        $this->createDependency($project, $a, $b, $user);

        $this->actingAs($user)
            ->post(route('projects.task-dependencies.store', $project), [
                'predecessor_task_id' => $a->id,
                'successor_task_id' => $b->id,
            ])
            ->assertSessionHasErrors('successor_task_id');
    }

    public function test_cannot_create_transitive_circular_dependency(): void
    {
        [$user, $project, $a, $b, $c] = $this->projectContextWithTasks(3);

        $this->createDependency($project, $a, $b, $user);
        $this->createDependency($project, $b, $c, $user);

        $this->actingAs($user)
            ->post(route('projects.task-dependencies.store', $project), [
                'predecessor_task_id' => $c->id,
                'successor_task_id' => $a->id,
            ])
            ->assertSessionHasErrors('successor_task_id');
    }

    public function test_can_create_valid_dependency(): void
    {
        [$user, $project, $a, $b] = $this->projectContextWithTasks(2);

        $this->actingAs($user)
            ->post(route('projects.task-dependencies.store', $project), [
                'predecessor_task_id' => $a->id,
                'successor_task_id' => $b->id,
                'notes' => 'Frontend menunggu UI selesai.',
            ])
            ->assertRedirect(route('projects.show', $project) . '?tab=dependencies#dependencies');

        $this->assertDatabaseHas('project_task_dependencies', [
            'project_id' => $project->id,
            'predecessor_task_id' => $a->id,
            'successor_task_id' => $b->id,
            'dependency_type' => 'finish_to_start',
        ]);
    }

    public function test_can_delete_dependency_if_authorized(): void
    {
        [$user, $project, $a, $b] = $this->projectContextWithTasks(2);
        $dependency = $this->createDependency($project, $a, $b, $user);

        $this->actingAs($user)
            ->delete(route('projects.task-dependencies.destroy', [$project, $dependency]))
            ->assertRedirect(route('projects.show', $project) . '?tab=dependencies#dependencies');

        $this->assertDatabaseMissing('project_task_dependencies', ['id' => $dependency->id]);
    }

    private function createDependency(Project $project, ProjectTask $predecessor, ProjectTask $successor, User $user): ProjectTaskDependency
    {
        return ProjectTaskDependency::create([
            'project_id' => $project->id,
            'predecessor_task_id' => $predecessor->id,
            'successor_task_id' => $successor->id,
            'dependency_type' => 'finish_to_start',
            'created_by' => $user->id,
            'task_id' => $successor->id,
            'depends_on_task_id' => $predecessor->id,
            'type' => 'finish_to_start',
            'created_by_user_id' => $user->id,
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
