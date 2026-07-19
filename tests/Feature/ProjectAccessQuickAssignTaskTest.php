<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Project;
use App\Models\ProjectTask;
use App\Models\TeamAssignment;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class ProjectAccessQuickAssignTaskTest extends TestCase
{
    public function test_assigned_operational_user_can_list_and_open_only_assigned_project(): void
    {
        $user = $this->userWithRoles(['sa_qa']);
        $assigned = $this->project('Assigned Project');
        $unassigned = $this->project('Unassigned Project');
        $this->assign($user, $assigned);

        $this->actingAs($user)->get(route('projects.index'))
            ->assertOk()
            ->assertSee($assigned->name)
            ->assertDontSee($unassigned->name);
        $this->actingAs($user)->get(route('projects.show', $assigned))->assertOk();
    }

    public function test_unassigned_operational_user_is_denied_project_detail(): void
    {
        $user = $this->userWithRoles(['fullstack_dev']);
        $project = $this->project();

        $this->actingAs($user)->get(route('projects.show', $project))
            ->assertRedirect(route('projects.index'))
            ->assertSessionHas('status', 'Proyek tersebut tidak ditugaskan kepada Anda.');
    }

    public function test_no_role_and_unknown_role_are_denied_project_list_and_detail(): void
    {
        $project = $this->project();

        foreach ([User::factory()->create(), $this->userWithRoles(['unknown_role'])] as $user) {
            $this->actingAs($user)->get(route('projects.index'))->assertForbidden();
            $this->actingAs($user)->get(route('projects.show', $project))->assertForbidden();
        }
    }

    public function test_multi_role_operational_access_is_independent_of_role_order(): void
    {
        $project = $this->project('Multi Role Project');

        foreach ([['unknown_role', 'sa_qa'], ['sa_qa', 'unknown_role']] as $roles) {
            $user = $this->userWithRoles($roles);
            $this->assign($user, $project);

            $this->actingAs($user)->get(route('projects.index'))->assertOk()->assertSee($project->name);
            $this->actingAs($user)->get(route('projects.show', $project))->assertOk();
        }
    }

    public function test_ceo_has_managerial_project_access_but_project_detail_mutations_are_read_only(): void
    {
        $ceo = $this->userWithRoles(['ceo_pm']);
        $project = $this->project();

        $this->actingAs($ceo)->get(route('projects.index'))->assertOk();
        $this->actingAs($ceo)->get(route('projects.show', $project))->assertOk();
        $this->actingAs($ceo)->post(route('projects.tasks.store', $project), $this->taskPayload())
            ->assertForbidden();
    }

    public function test_quick_assign_creates_then_updates_the_same_record_with_multiple_responsibilities(): void
    {
        $ceo = $this->userWithRoles(['ceo_pm']);
        $member = $this->userWithRoles(['fullstack_dev']);
        $project = $this->project();

        $this->actingAs($ceo)->post(route('projects.quick-assign', $project), [
            'assignments' => [$this->assignmentPayload($member, ['fullstack_dev', 'wordpress_support'])],
        ])->assertRedirect(route('projects.show', $project) . '#overview');

        $assignment = TeamAssignment::whereBelongsTo($project)->whereBelongsTo($member)->sole();
        $this->assertSame(['fullstack_dev', 'wordpress_support'], $assignment->responsibilities);

        $this->actingAs($ceo)->post(route('projects.quick-assign', $project), [
            'assignments' => [$this->assignmentPayload($member, ['copywriting_support'], 24)],
        ])->assertRedirect(route('projects.show', $project) . '#overview');

        $this->assertSame(1, TeamAssignment::whereBelongsTo($project)->whereBelongsTo($member)->count());
        $this->assertDatabaseHas('team_assignments', [
            'id' => $assignment->id,
            'estimated_hours' => 24,
        ]);
        $this->assertSame(['copywriting_support'], $assignment->fresh()->responsibilities);
    }

    public function test_quick_assign_rejects_archived_member(): void
    {
        $ceo = $this->userWithRoles(['ceo_pm']);
        $member = $this->userWithRoles(['sa_qa']);
        $member->forceFill(['archived_at' => now()])->save();
        $project = $this->project();

        $this->actingAs($ceo)->post(route('projects.quick-assign', $project), [
            'assignments' => [$this->assignmentPayload($member, ['saqa_mom_qc'])],
        ])->assertSessionHasErrors('assignments.0.user_id');

        $this->assertDatabaseMissing('team_assignments', ['project_id' => $project->id, 'user_id' => $member->id]);
    }

    public function test_quick_assign_uses_deterministic_server_defaults_not_hidden_input_values(): void
    {
        $ceo = $this->userWithRoles(['ceo_pm']);
        $member = $this->userWithRoles(['sa_qa']);
        $project = $this->project();
        $payload = $this->assignmentPayload($member, ['saqa_mom_qc']);
        $payload += ['title' => 'Tampered', 'type' => 'support', 'status' => 'done', 'due_date' => '1999-01-01'];

        $this->actingAs($ceo)->post(route('projects.quick-assign', $project), ['assignments' => [$payload]])
            ->assertRedirect(route('projects.show', $project) . '#overview');

        $assignment = TeamAssignment::whereBelongsTo($project)->whereBelongsTo($member)->sole();
        $this->assertSame('Analisis kebutuhan, MoM, dan validasi QC', $assignment->title);
        $this->assertSame('task', $assignment->type);
        $this->assertSame('planned', $assignment->status);
        $this->assertSame($project->due_at->toDateString(), $assignment->due_date->toDateString());
    }

    public function test_task_assignee_is_optional_accepts_project_member_and_rejects_non_member(): void
    {
        $operator = $this->userWithRoles(['sa_qa']);
        $member = $this->userWithRoles(['fullstack_dev']);
        $nonMember = $this->userWithRoles(['fullstack_dev']);
        $project = $this->project();
        $this->assign($operator, $project);
        $this->assign($member, $project);

        $this->actingAs($operator)->post(route('projects.tasks.store', $project), $this->taskPayload('Optional'))
            ->assertRedirect(route('projects.show', $project) . '#workspace');
        $this->assertNull(ProjectTask::where('project_id', $project->id)->where('title', 'Optional')->sole()->assigned_to);

        $this->actingAs($operator)->post(route('projects.tasks.store', $project), $this->taskPayload('Member', $member->id))
            ->assertRedirect(route('projects.show', $project) . '#workspace');
        $this->assertSame($member->id, ProjectTask::where('project_id', $project->id)->where('title', 'Member')->sole()->assigned_to);

        $this->actingAs($operator)->post(route('projects.tasks.store', $project), $this->taskPayload('Non Member', $nonMember->id))
            ->assertSessionHasErrors('assigned_to');
        $this->assertDatabaseMissing('project_tasks', ['project_id' => $project->id, 'title' => 'Non Member']);
    }

    public function test_task_assignee_can_be_cleared_to_null(): void
    {
        $operator = $this->userWithRoles(['sa_qa']);
        $member = $this->userWithRoles(['fullstack_dev']);
        $project = $this->project();
        $this->assign($operator, $project);
        $this->assign($member, $project);
        $task = ProjectTask::create($this->taskPayload('Clear Assignee', $member->id) + [
            'project_id' => $project->id,
            'sort_order' => 1,
        ]);

        $this->actingAs($operator)->put(route('projects.tasks.update', [$project, $task]), $this->taskPayload('Clear Assignee'))
            ->assertRedirect(route('projects.show', $project) . '#workspace');

        $this->assertNull($task->fresh()->assigned_to);
    }

    private function userWithRoles(array $roleNames): User
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $user = User::factory()->create();

        foreach ($roleNames as $roleName) {
            $user->assignRole(Role::findOrCreate($roleName, 'web'));
        }

        return $user;
    }

    private function project(string $name = 'Focused Test Project'): Project
    {
        $client = Client::create([
            'name' => 'Focused Test Client ' . uniqid(),
            'code' => strtoupper(substr(uniqid(), -4)),
            'tier' => 'standard',
        ]);

        return Project::create([
            'code' => strtoupper(substr(uniqid(), -4)),
            'name' => $name . ' ' . uniqid(),
            'client_id' => $client->id,
            'phase' => 'Development',
            'due_at' => now()->addDays(14)->toDateString(),
            'progress' => 0,
            'status' => 'on-track',
        ]);
    }

    private function assign(User $user, Project $project): TeamAssignment
    {
        return TeamAssignment::create([
            'user_id' => $user->id,
            'project_id' => $project->id,
            'title' => 'Focused Test Assignment',
            'type' => 'task',
            'responsibilities' => ['fullstack_dev'],
            'status' => 'planned',
        ]);
    }

    private function assignmentPayload(User $user, array $responsibilities, int $hours = 8): array
    {
        return [
            'include' => '1',
            'user_id' => $user->id,
            'responsibilities' => $responsibilities,
            'estimated_hours' => $hours,
            'notes' => 'Focused test',
        ];
    }

    private function taskPayload(string $title = 'Focused Task', ?int $assignee = null): array
    {
        return array_filter([
            'title' => $title,
            'assigned_to' => $assignee,
            'status' => 'planned',
            'priority' => 'medium',
            'estimate_hours' => 4,
        ], fn ($value) => $value !== null);
    }
}
