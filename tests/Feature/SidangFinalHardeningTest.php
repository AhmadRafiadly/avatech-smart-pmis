<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class SidangFinalHardeningTest extends TestCase
{
    public function test_root_redirects_to_login(): void
    {
        $this->get('/')->assertRedirect('/login');
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
