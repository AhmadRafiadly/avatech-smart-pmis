<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Project;
use App\Models\ProjectRequirementInboxItem;
use App\Models\TestingEvidence;
use App\Models\ProjectTask;
use App\Models\TeamAssignment;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class SidangFinalHardeningTest extends TestCase
{
    public function test_root_redirects_to_login(): void
    {
        $this->get('/')->assertRedirect('/login');
    }

    public function test_visible_views_use_factual_current_wording_without_realtime_claims(): void
    {
        $views = collect(new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(resource_path('views'))))
            ->filter(fn (\SplFileInfo $file): bool => $file->isFile() && str_ends_with($file->getFilename(), '.blade.php'))
            ->map(fn (\SplFileInfo $file): string => file_get_contents($file->getPathname()))
            ->implode("\n");

        $this->assertStringContainsString('Ikhtisar terkini kinerja bisnis dan kesehatan proyek perusahaan.', $views);
        $this->assertDoesNotMatchRegularExpression('/\b(?:realtime|real-time|live sync)\b/i', $views);
    }

    public function test_ceo_pm_can_access_dashboard_and_executive_area_when_routes_exist(): void
    {
        $user = $this->userWithRole('ceo_pm');

        if (Route::has('dashboard.index')) {
            $response = $this->actingAs($user)->get(route('dashboard.index'));

            if (Route::has('executive.index')) {
                $response->assertRedirect(route('executive.index'));
            } else {
                $response->assertOk();
            }
        }

        if (Route::has('executive.index')) {
            $this->actingAs($user)->get(route('executive.index'))->assertOk();
        }
    }

    public function test_ceo_pm_can_access_project_master_when_route_exists(): void
    {
        if (! Route::has('projects.index')) {
            $this->markTestSkipped('projects.index route is not registered.');
        }

        $this->actingAs($this->userWithRole('ceo_pm'))
            ->get(route('projects.index'))
            ->assertOk();
    }

    public function test_project_detail_can_be_accessed_by_allowed_user(): void
    {
        if (! Route::has('projects.show')) {
            $this->markTestSkipped('projects.show route is not registered.');
        }

        $user = $this->userWithRole('ceo_pm');
        $project = $this->projectFor($user);

        $this->actingAs($user)
            ->get(route('projects.show', $project))
            ->assertOk();
    }

    public function test_requirement_intake_route_exists_and_is_protected_by_auth(): void
    {
        if (! Route::has('projects.requirement-intake.store')) {
            $this->markTestSkipped('projects.requirement-intake.store route is not registered.');
        }

        $project = $this->projectFor($this->userWithRole('ceo_pm'));

        $this->from('/login')
            ->post(route('projects.requirement-intake.store', $project), ['_token' => csrf_token()])
            ->assertRedirect('/login');
    }

    public function test_ai_monitor_is_protected_and_accessible_by_ceo_pm_and_admin(): void
    {
        if (! Route::has('ai-monitor.index')) {
            $this->markTestSkipped('ai-monitor.index route is not registered.');
        }

        $this->get(route('ai-monitor.index'))->assertRedirect('/login');
        $this->actingAs($this->userWithRole('ceo_pm'))->get(route('ai-monitor.index'))->assertOk();
        $this->actingAs($this->userWithRole('admin'))->get(route('ai-monitor.index'))->assertOk();
    }

    public function test_qa_evidence_is_protected_and_accessible_from_settings_support(): void
    {
        if (! Route::has('testing-evidence.index') || ! Route::has('settings.index')) {
            $this->markTestSkipped('QA Evidence or Settings route is not registered.');
        }

        $user = $this->userWithRole('ceo_pm');

        $this->get(route('testing-evidence.index'))->assertRedirect('/login');
        $this->actingAs($user)->get(route('testing-evidence.index'))->assertOk();
        $this->actingAs($user)->get(route('settings.index'))->assertOk()->assertSee('QA Evidence');
    }

    public function test_metric_reference_is_protected_and_accessible_from_settings_support(): void
    {
        if (! Route::has('metric-reference.index') || ! Route::has('settings.index')) {
            $this->markTestSkipped('Metric Reference or Settings route is not registered.');
        }

        $user = $this->userWithRole('ceo_pm');

        $this->get(route('metric-reference.index'))->assertRedirect('/login');
        $this->actingAs($user)->get(route('metric-reference.index'))->assertOk();
        $this->actingAs($user)->get(route('settings.index'))->assertOk()->assertSee('Metric Reference');
    }

    public function test_project_lead_must_be_an_active_member_of_the_same_project_and_can_be_cleared(): void
    {
        $manager = $this->userWithRole('ceo_pm');
        $lead = $this->userWithRole('fullstack_dev');
        $project = $this->projectFor($manager);
        TeamAssignment::create(['user_id' => $lead->id, 'project_id' => $project->id, 'title' => 'Developer', 'type' => 'project', 'status' => 'in_progress']);
        $payload = ['code' => $project->code, 'name' => $project->name, 'client_id' => $project->client_id, 'lead_user_id' => $lead->id, 'requires_design' => 0];

        $this->actingAs($manager)->put(route('projects.update', $project), $payload)->assertSessionHasNoErrors();
        $this->assertDatabaseHas('projects', ['id' => $project->id, 'lead_user_id' => $lead->id]);

        TeamAssignment::where('user_id', $lead->id)->update(['status' => 'completed']);
        $this->actingAs($manager)->put(route('projects.update', $project), $payload)->assertSessionHasErrors('lead_user_id');
        $this->actingAs($manager)->put(route('projects.update', $project), array_merge($payload, ['lead_user_id' => null]))->assertSessionHasNoErrors();
        $this->assertDatabaseHas('projects', ['id' => $project->id, 'lead_user_id' => null]);
    }

    public function test_operational_dashboard_excludes_inactive_membership_and_archived_project_tasks(): void
    {
        $user = $this->userWithRole('fullstack_dev');
        $active = $this->projectFor($user);
        $archived = $this->projectFor($user);
        $archived->update(['archived_at' => now()]);
        TeamAssignment::create(['user_id' => $user->id, 'project_id' => $active->id, 'title' => 'Developer', 'type' => 'project', 'status' => 'completed']);
        TeamAssignment::create(['user_id' => $user->id, 'project_id' => $archived->id, 'title' => 'Developer', 'type' => 'project', 'status' => 'in_progress']);
        ProjectTask::create(['project_id' => $active->id, 'title' => 'Inactive membership task', 'status' => 'planned', 'priority' => 'medium', 'assigned_to' => $user->id]);
        ProjectTask::create(['project_id' => $archived->id, 'title' => 'Archived project task', 'status' => 'planned', 'priority' => 'medium', 'assigned_to' => $user->id]);

        $this->actingAs($user)->get(route('dashboard.index'))
            ->assertOk()
            ->assertDontSee('Inactive membership task')
            ->assertDontSee('Archived project task');
    }

    public function test_archived_and_no_role_users_fail_closed_on_critical_pages(): void
    {
        $archived = $this->userWithRole('ceo_pm');
        $archived->update(['archived_at' => now()]);
        $noRole = User::factory()->create();

        $this->actingAs($archived)->get(route('ai-monitor.index'))->assertRedirect(route('login'));
        $this->assertGuest();
        $this->actingAs($noRole)->get(route('audit.index'))->assertForbidden();
        $this->actingAs($noRole)->get(route('dashboard.index'))->assertForbidden();
    }

    public function test_testing_evidence_rejects_cross_count_and_stores_valid_upload_privately_with_audit(): void
    {
        Storage::fake('local');
        $user = $this->userWithRole('sa_qa');
        $payload = ['category' => 'Black-Box Testing', 'title' => 'Regression', 'total_scenarios' => 3, 'passed_scenarios' => 1, 'failed_scenarios' => 1, 'result_status' => 'Review', 'tested_at' => now()->toDateString()];

        $this->actingAs($user)->post(route('testing-evidence.store'), $payload)->assertSessionHasErrors('total_scenarios');
        $this->actingAs($user)->post(route('testing-evidence.store'), array_merge($payload, ['total_scenarios' => 2, 'file' => UploadedFile::fake()->create('evidence.pdf', 10, 'application/pdf')]))->assertSessionHasNoErrors();

        $evidence = TestingEvidence::where('title', 'Regression')->firstOrFail();
        Storage::disk('local')->assertExists($evidence->evidence_file_path);
        $this->assertDatabaseHas('audit_logs', ['action' => 'testing_evidence_created', 'auditable_id' => $evidence->id]);
    }

    public function test_testing_evidence_and_private_files_are_role_and_project_guarded_with_legacy_fallback(): void
    {
        Storage::fake('local');
        Storage::fake('public');
        $manager = $this->userWithRole('ceo_pm');
        $outsider = $this->userWithRole('fullstack_dev');
        $owner = $this->userWithRole('sa_qa');
        $project = $this->projectFor($owner);
        TeamAssignment::create(['user_id' => $owner->id, 'project_id' => $project->id, 'title' => 'QA', 'type' => 'project', 'status' => 'in_progress']);
        $item = ProjectRequirementInboxItem::create(['project_id' => $project->id, 'created_by' => $owner->id, 'title' => 'Legacy PRD', 'source_type' => 'prd', 'priority' => 'must', 'status' => 'draft', 'summary' => 'Legacy', 'file_path' => 'project-requirements/'.$project->id.'/legacy.pdf']);
        Storage::disk('public')->put($item->file_path, 'legacy');

        $this->actingAs($outsider)->get(route('testing-evidence.index'))->assertForbidden();
        $this->actingAs($outsider)->get(route('projects.requirement-intake.preview', [$project, $item]))->assertRedirect(route('projects.index'));
        $this->actingAs($owner)->get(route('projects.requirement-intake.preview', [$project, $item]))->assertOk();
        $this->actingAs($manager)->get(route('projects.requirement-intake.download', [$project, $item]))->assertOk();
    }

    private function userWithRole(string $roleName): User
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $role = Role::findOrCreate($roleName, 'web');
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }

    private function projectFor(User $user): Project
    {
        $client = Client::create([
            'name' => 'Regression Test Client ' . uniqid(),
            'code' => strtoupper(substr(uniqid(), -4)),
            'tier' => 'standard',
        ]);

        return Project::create([
            'code' => strtoupper(substr(uniqid(), -4)),
            'name' => 'Regression Test Project ' . uniqid(),
            'client_id' => $client->id,
            'lead_user_id' => $user->id,
            'phase' => 'Discovery',
            'due_at' => now()->addDays(14)->toDateString(),
            'progress' => 0,
            'status' => 'on-track',
        ]);
    }
}
