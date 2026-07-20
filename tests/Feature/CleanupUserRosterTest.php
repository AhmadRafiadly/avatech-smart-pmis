<?php

namespace Tests\Feature;

use App\Console\Commands\CleanupUserRoster;
use App\Models\User;
use Filament\Panel;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use RuntimeException;
use Tests\TestCase;

class CleanupUserRosterTest extends TestCase
{
    private array $manifests = [];

    protected function tearDown(): void
    {
        foreach ($this->manifests as $path) {
            File::delete($path);
        }
        parent::tearDown();
    }

    public function test_production_and_wrong_database_execution_are_rejected(): void
    {
        app()->detectEnvironment(fn () => 'production');
        try {
            CleanupUserRoster::assertSafeDatabase(true, 'avatech_smart_pmis');
            $this->fail('Production accepted.');
        } catch (RuntimeException) {
            app()->detectEnvironment(fn () => 'testing');
        }
        app()->detectEnvironment(fn () => 'staging');
        try {
            $this->expectException(RuntimeException::class);
            CleanupUserRoster::assertSafeDatabase(true);
        } finally {
            app()->detectEnvironment(fn () => 'testing');
        }
    }

    public function test_exact_six_official_preserves_are_required(): void
    {
        $emails = ['joshua.raphael@avatech.test', 'ahmad.arlisyah@avatech.test', 'ferry.achmad@avatech.test', 'irwan.kurniawan@avatech.test', 'genta@avatech.test', 'yuda.prayoga@avatech.test'];
        CleanupUserRoster::assertOfficialPreserves($emails);
        $this->expectException(RuntimeException::class);
        CleanupUserRoster::assertOfficialPreserves(array_slice($emails, 1));
    }

    public function test_dry_run_inventories_every_user_and_classifies_conservatively(): void
    {
        $safe = $this->user('Disposable', 'disposable@example.com');
        $ordinary = $this->user('Ordinary', 'person@company.co.id');
        $historical = $this->user('Historical', 'historical@example.test');
        DB::table('audit_logs')->insert(['user_id' => $historical, 'action' => 'created', 'module' => 'users', 'auditable_type' => 'User', 'auditable_id' => $historical, 'created_at' => now()]);

        [, $manifest] = $this->dryRun();

        $this->assertSame(DB::table('users')->count(), $manifest['stats']['total']);
        $this->assertSame('SAFE_DELETE', collect($manifest['users'])->firstWhere('id', $safe)['classification']);
        $this->assertSame('BLOCKED_MANUAL_REVIEW', collect($manifest['users'])->firstWhere('id', $ordinary)['classification']);
        $this->assertNotContains($ordinary, $manifest['expected']['delete_user_ids']);
        $this->assertDatabaseHas('users', ['id' => $ordinary]);
        $this->assertSame('ARCHIVE_ONLY', collect($manifest['users'])->firstWhere('id', $historical)['classification']);
        $this->assertContains($manifest['feasibility'], ['SAFE_TO_REACH_EXACTLY_6', 'SAFE_TO_SHOW_6_ACTIVE_BUT_NOT_DELETE_ALL_HISTORY', 'NOT_SAFE_WITHOUT_SCHEMA_OR_HISTORY_CHANGES']);
        $this->assertArrayHasKey('sessions.user_id', $manifest['reference_aggregate']);
        $this->assertArrayHasKey('password_reset_tokens.email', $manifest['reference_aggregate']);
        $this->assertArrayHasKey('model_has_roles.model_id', $manifest['reference_aggregate']);
        $this->assertArrayHasKey('audit_logs.user_id', $manifest['reference_aggregate']);
    }

    public function test_test_project_linked_user_is_always_protected(): void
    {
        $user = $this->user('Protected Test User', 'protected@example.test');
        $client = DB::table('clients')->insertGetId(['name' => 'Roster Client', 'code' => 'RCL', 'tier' => 'standard', 'created_at' => now(), 'updated_at' => now()]);
        DB::table('projects')->insert(['name' => 'test 1', 'code' => 'RT1', 'client_id' => $client, 'lead_user_id' => $user, 'created_at' => now(), 'updated_at' => now()]);

        [, $manifest] = $this->dryRun();

        $entry = collect($manifest['users'])->firstWhere('id', $user);
        $this->assertSame('ARCHIVE_ONLY', $entry['classification']);
        $this->assertContains($user, $manifest['protected_user_ids']);
    }

    public function test_team_count_uses_exact_operational_active_query_and_excludes_ceo(): void
    {
        foreach ($this->officialEmails() as $email) {
            DB::table('users')->where('email', $email)->delete();
        }
        $official = [
            ['Joshua Raphael', 'joshua.raphael@avatech.test', 'ceo_pm'],
            ['Ahmad Rafiadly Arlisyah', 'ahmad.arlisyah@avatech.test', 'sa_qa'],
            ['Ferry Achmad', 'ferry.achmad@avatech.test', 'fullstack_dev'],
            ['Irwan Kurniawan', 'irwan.kurniawan@avatech.test', 'fullstack_dev'],
            ['Genta', 'genta@avatech.test', 'fullstack_dev'],
            ['Yuda Prayoga', 'yuda.prayoga@avatech.test', 'uiux_designer'],
        ];
        foreach ($official as [$name, $email, $role]) {
            $this->assignRole($this->user($name, $email), $role);
        }

        [, $manifest] = $this->dryRun();

        $this->assertSame(5, $manifest['expected']['team_count_before']);
        $this->assertCount(6, $manifest['official_account_verification']);
        $this->assertTrue(collect($manifest['official_account_verification'])->every('valid'));
    }

    public function test_safe_detach_plans_assignment_task_pic_and_project_lead(): void
    {
        $assignmentUser = $this->user('Assignment Fake', 'assignment@example.test');
        $taskUser = $this->user('Task Fake', 'task@example.test');
        $leadUser = $this->user('Lead Fake', 'lead@example.test');
        $client = DB::table('clients')->insertGetId(['name' => 'Detach Client', 'code' => 'DTC', 'tier' => 'standard', 'created_at' => now(), 'updated_at' => now()]);
        $project = DB::table('projects')->insertGetId(['name' => 'Detach Project', 'code' => 'DTP', 'client_id' => $client, 'lead_user_id' => $leadUser, 'created_at' => now(), 'updated_at' => now()]);
        DB::table('team_assignments')->insert(['user_id' => $assignmentUser, 'project_id' => $project, 'title' => 'Fake Assignment', 'created_at' => now(), 'updated_at' => now()]);
        DB::table('project_tasks')->insert(['project_id' => $project, 'assigned_to' => $taskUser, 'title' => 'Fake PIC', 'created_at' => now(), 'updated_at' => now()]);

        [, $manifest] = $this->dryRun();

        foreach ([[$assignmentUser, 'team_assignments.user_id', 'delete'], [$taskUser, 'project_tasks.assigned_to', 'set_null'], [$leadUser, 'projects.lead_user_id', 'set_null']] as [$id, $key, $action]) {
            $user = collect($manifest['users'])->firstWhere('id', $id);
            $this->assertSame('SAFE_DETACH_THEN_DELETE', $user['classification']);
            $plan = collect($user['detachments'])->first(fn ($item) => $key === $item['table'].'.'.$item['column']);
            $this->assertTrue($plan['action'] === $action);
        }
    }

    public function test_unknown_multiple_roles_and_official_accounts_are_blocked(): void
    {
        $unknown = $this->user('Unknown Role', 'unknown@example.test');
        $multiple = $this->user('Multiple Role', 'multiple@example.test');
        $this->assignRole($unknown, 'unexpected_role');
        $this->assignRole($multiple, 'sa_qa');
        $this->assignRole($multiple, 'developer');

        [, $manifest] = $this->dryRun();

        $this->assertContains($unknown, $manifest['stats']['unknown_role']);
        $this->assertContains($multiple, $manifest['stats']['multi_role']);
        $this->assertSame('SAFE_DELETE', collect($manifest['users'])->firstWhere('id', $unknown)['classification']);
        $this->assertSame('SAFE_DELETE', collect($manifest['users'])->firstWhere('id', $multiple)['classification']);
        $official = collect($manifest['users'])->where('official', true);
        $this->assertTrue($official->every(fn ($user) => $user['classification'] === 'BLOCKED_MANUAL_REVIEW'));
        $this->assertSame([], array_intersect($official->pluck('id')->all(), $manifest['expected']['delete_user_ids']));
        $this->assertTrue(collect($manifest['users'])->every(fn ($user) => in_array($user['classification'], ['SAFE_DELETE', 'SAFE_DETACH_THEN_DELETE', 'ARCHIVE_ONLY', 'BLOCKED_MANUAL_REVIEW'], true)));
    }

    public function test_faker_pattern_is_safe_without_meaningful_references(): void
    {
        $faker = $this->user('Faker User', 'person@faker.invalid');

        [, $manifest] = $this->dryRun();

        $this->assertContains($faker, $manifest['stats']['patterns']['faker']);
        $this->assertSame('SAFE_DELETE', collect($manifest['users'])->firstWhere('id', $faker)['classification']);
    }

    public function test_execute_rejects_missing_stale_altered_and_wrong_token_manifests(): void
    {
        $this->artisan('maintenance:cleanup-user-roster', ['--execute' => true, '--preserve-email' => $this->officialEmails()])->assertFailed();
        [$path, $manifest] = $this->dryRun();
        $this->artisan('maintenance:cleanup-user-roster', ['--execute' => true, '--manifest' => $path, '--confirmation-token' => 'WRONG-TOKEN', '--preserve-email' => $this->officialEmails()])->assertFailed();
        $altered = $manifest;
        $altered['stats']['total']++;
        File::put($path, json_encode($altered, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
        $this->artisan('maintenance:cleanup-user-roster', ['--execute' => true, '--manifest' => $path, '--confirmation-token' => $manifest['confirmation_token'], '--preserve-email' => $this->officialEmails()])->assertFailed();
        File::put($path, json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
        $this->user('Late User', 'late@example.com');
        $this->artisan('maintenance:cleanup-user-roster', ['--execute' => true, '--manifest' => $path, '--confirmation-token' => $manifest['confirmation_token'], '--preserve-email' => $this->officialEmails()])
            ->expectsOutputToContain('stale')
            ->assertFailed();
    }

    public function test_local_environment_with_testing_database_is_rejected(): void
    {
        app()->detectEnvironment(fn () => 'local');
        try {
            $this->expectException(RuntimeException::class);
            CleanupUserRoster::assertSafeDatabase(true, 'avatech_smart_pmis');
        } finally {
            app()->detectEnvironment(fn () => 'testing');
        }
    }

    public function test_execution_blocker_validation_rejects_unaccounted_nonzero_reference(): void
    {
        $manifest = [
            'warnings' => [],
            'users' => [],
            'official_account_verification' => [['valid' => true]],
            'reference_schema' => [['table' => 'unknown', 'column' => 'user_id', 'accounted' => false]],
            'reference_aggregate' => ['unknown.user_id' => ['count_nonofficial' => 1]],
        ];

        $this->assertTrue(CleanupUserRoster::hasExecutionBlockers($manifest));
    }

    public function test_apply_manifest_rolls_back_first_detachment_when_second_fails(): void
    {
        $user = $this->user('Rollback User', 'rollback@example.test');
        $client = DB::table('clients')->insertGetId(['name' => 'Rollback Client', 'code' => 'RBK', 'tier' => 'standard', 'created_at' => now(), 'updated_at' => now()]);
        $project = DB::table('projects')->insertGetId(['name' => 'Rollback Project', 'code' => 'RBP', 'client_id' => $client, 'created_at' => now(), 'updated_at' => now()]);
        $assignment = DB::table('team_assignments')->insertGetId(['user_id' => $user, 'project_id' => $project, 'title' => 'Rollback', 'created_at' => now(), 'updated_at' => now()]);
        $plans = collect([['id' => $user, 'detachments' => [
            ['table' => 'team_assignments', 'record_ids' => [$assignment], 'column' => 'user_id', 'old_user_id' => $user, 'planned_value' => 'row deleted', 'action' => 'delete', 'exact_key' => [], 'expected_count' => 1],
            ['table' => 'team_assignments', 'record_ids' => [$assignment + 999999], 'column' => 'user_id', 'old_user_id' => $user, 'planned_value' => 'row deleted', 'action' => 'delete', 'exact_key' => [], 'expected_count' => 1],
        ]]]);
        $command = new class extends CleanupUserRoster {
            public function apply(array $manifest): void
            {
                $this->applyManifest($manifest);
            }
        };

        try {
            $command->apply(['users' => $plans->all(), 'detachments' => $plans->flatMap(fn ($user) => $user['detachments'])->all(), 'role_pivots_to_delete' => [], 'authentication_records_to_delete' => [], 'archive_user_ids' => [], 'delete_user_ids' => [$user]]);
            $this->fail('Stale second detachment accepted.');
        } catch (RuntimeException) {
            $this->assertDatabaseHas('team_assignments', ['id' => $assignment, 'user_id' => $user]);
            $this->assertDatabaseHas('users', ['id' => $user]);
        }
    }

    public function test_testing_execution_deletes_only_exact_safe_manifest_ids(): void
    {
        foreach ([
            ['Joshua Raphael', 'joshua.raphael@avatech.test', 'ceo_pm'],
            ['Ahmad Rafiadly Arlisyah', 'ahmad.arlisyah@avatech.test', 'sa_qa'],
            ['Ferry Achmad', 'ferry.achmad@avatech.test', 'fullstack_dev'],
            ['Irwan Kurniawan', 'irwan.kurniawan@avatech.test', 'fullstack_dev'],
            ['Genta', 'genta@avatech.test', 'fullstack_dev'],
            ['Yuda Prayoga', 'yuda.prayoga@avatech.test', 'uiux_designer'],
        ] as [$name, $email, $role]) {
            $id = DB::table('users')->where('email', $email)->value('id') ?: $this->user($name, $email);
            DB::table('users')->where('id', $id)->update(['name' => $name, 'archived_at' => null]);
            if (! DB::table('model_has_roles')->where('model_id', $id)->where('model_type', 'App\\Models\\User')->exists()) {
                $this->assignRole($id, $role);
            }
        }
        $safe = $this->user('Safe Delete', 'safe@example.org');
        $this->ensureExecutionSentinels();
        [$path, $manifest] = $this->dryRun();

        $this->artisan('maintenance:cleanup-user-roster', ['--execute' => true, '--manifest' => $path, '--confirmation-token' => $manifest['confirmation_token'], '--preserve-email' => $this->officialEmails()])->assertSuccessful();

        $this->assertContains($safe, $manifest['expected']['delete_user_ids']);
        $this->assertDatabaseMissing('users', ['id' => $safe]);
    }

    public function test_v2_reference_schema_accounts_for_all_legacy_fields(): void
    {
        [, $manifest] = $this->dryRun();
        $expected = [
            'project_blockers.assigned_to_user_id' => [true, false],
            'project_blockers.reported_by_user_id' => [false, true],
            'project_change_requests.approved_by_user_id' => [false, true],
            'project_change_requests.requested_by_user_id' => [false, true],
            'project_client_reviews.created_by_user_id' => [false, true],
            'project_handover_packs.finalized_by_user_id' => [false, true],
            'project_handover_packs.generated_by_user_id' => [false, true],
            'project_signoffs.approved_by_user_id' => [false, true],
            'project_signoffs.created_by_user_id' => [false, true],
            'project_uat_items.tested_by_user_id' => [false, true],
        ];

        foreach ($expected as $key => [$detachable, $historical]) {
            $reference = collect($manifest['reference_schema'])->first(fn ($item) => $key === $item['table'].'.'.$item['column']);
            $this->assertNotNull($reference, $key);
            $this->assertTrue($reference['accounted'], $key);
            $this->assertSame($detachable, $reference['detachable'], $key);
            $this->assertSame($historical, $reference['historical'], $key);
        }
    }

    public function test_archived_users_cannot_use_normal_or_panel_authentication(): void
    {
        $password = 'archived-password';
        $id = $this->user('Archived Login', 'archived-login@company.test');
        DB::table('users')->where('id', $id)->update(['password' => Hash::make($password), 'archived_at' => now()]);

        $this->post(route('login.store'), ['email' => 'archived-login@company.test', 'password' => $password])->assertSessionHasErrors('email');
        $this->assertGuest();
        $this->assignRole($id, 'admin');
        $this->assertFalse(User::findOrFail($id)->canAccessPanel(Panel::make()));
    }

    public function test_archived_remembered_and_existing_sessions_are_rejected_but_logout_remains_accessible(): void
    {
        $id = $this->user('Archived Session', 'archived-session@company.test');
        DB::table('users')->where('id', $id)->update(['archived_at' => now(), 'remember_token' => 'remember-secret']);
        $user = User::findOrFail($id);

        $recaller = $id.'|remember-secret|'.Auth::guard()->hashPasswordForCookie($user->getAuthPassword());
        $this->withCookie(Auth::getRecallerName(), $recaller)->get(route('dashboard.index'))->assertRedirect(route('login'));
        $this->assertGuest();
        $this->actingAs($user)->get(route('dashboard.index'))->assertRedirect(route('login'));
        $this->assertGuest();
        $this->actingAs($user)->post(route('logout'))->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_active_official_user_is_unaffected(): void
    {
        $this->ensureOfficialAccounts();
        $user = User::where('email', 'ahmad.arlisyah@avatech.test')->firstOrFail();

        $this->actingAs($user)->get(route('dashboard.index'))->assertOk();
    }

    public function test_execution_archives_exact_history_and_deletes_exact_safe_user(): void
    {
        $this->ensureOfficialAccounts();
        $this->ensureExecutionSentinels();
        $historical = $this->user('Archive Exact', 'archive-exact@example.test');
        DB::table('users')->where('id', $historical)->update(['remember_token' => 'archive-token']);
        $safe = $this->user('Delete Exact', 'delete-exact@example.test');
        $this->assignRole($historical, 'developer');
        $audit = DB::table('audit_logs')->insertGetId(['user_id' => $historical, 'action' => 'created', 'module' => 'users', 'auditable_type' => 'User', 'auditable_id' => $historical, 'created_at' => now()]);
        [$path, $manifest] = $this->dryRun();

        $this->assertSame([$historical], array_values(array_intersect($manifest['archive_user_ids'], [$historical, $safe])));
        $this->assertSame([], array_intersect($manifest['official_user_ids'], array_merge($manifest['archive_user_ids'], $manifest['delete_user_ids'])));
        $this->artisan('maintenance:cleanup-user-roster', ['--execute' => true, '--manifest' => $path, '--confirmation-token' => $manifest['confirmation_token'], '--preserve-email' => $this->officialEmails()])->assertSuccessful();

        $this->assertDatabaseHas('users', ['id' => $historical]);
        $this->assertNotNull(DB::table('users')->where('id', $historical)->value('archived_at'));
        $this->assertNull(DB::table('users')->where('id', $historical)->value('remember_token'));
        $this->assertDatabaseHas('audit_logs', ['id' => $audit, 'user_id' => $historical]);
        $this->assertDatabaseMissing('model_has_roles', ['model_id' => $historical, 'model_type' => User::class]);
        $this->assertDatabaseMissing('users', ['id' => $safe]);
        foreach ($this->officialEmails() as $email) {
            $this->assertDatabaseHas('users', ['email' => $email, 'archived_at' => null]);
        }
    }

    public function test_apply_manifest_rolls_back_archive_when_exact_deletion_count_fails(): void
    {
        $archive = $this->user('Archive Rollback', 'archive-rollback@example.test');
        DB::table('users')->where('id', $archive)->update(['remember_token' => 'rollback-token']);
        $delete = $this->user('Delete Rollback', 'delete-rollback@example.test');
        $command = new class extends CleanupUserRoster {
            public function apply(array $manifest): void
            {
                $this->applyManifest($manifest);
            }
        };

        try {
            $command->apply(['users' => [['id' => $archive, 'archived_at' => null]], 'detachments' => [], 'role_pivots_to_delete' => [], 'authentication_records_to_delete' => [], 'archive_user_ids' => [$archive], 'delete_user_ids' => [$delete, 999999999]]);
            $this->fail('Invalid exact deletion count accepted.');
        } catch (RuntimeException) {
            $this->assertDatabaseHas('users', ['id' => $archive, 'archived_at' => null, 'remember_token' => 'rollback-token']);
            $this->assertDatabaseHas('users', ['id' => $delete]);
        }
    }

    public function test_v3_and_malformed_manifests_are_execution_blockers_without_database_access(): void
    {
        $this->assertTrue(CleanupUserRoster::hasExecutionBlockers(['format_version' => 3]));
        $this->assertTrue(CleanupUserRoster::hasExecutionBlockers([]));
        $this->assertTrue(CleanupUserRoster::hasExecutionBlockers([
            'format_version' => 4,
            'warnings' => 'invalid',
            'blocked_user_ids' => null,
            'official_account_verification' => 'invalid',
            'official_user_ids' => ['1'],
            'delete_user_ids' => null,
            'archive_user_ids' => 'invalid',
            'users' => null,
            'reference_schema' => null,
            'reference_aggregate' => null,
        ]));
    }

    public function test_each_v4_execution_invariant_blocks_independently(): void
    {
        $base = $this->validManifest();
        $this->assertFalse(CleanupUserRoster::hasExecutionBlockers($base));
        $mutations = [
            'blocked IDs' => fn ($m) => array_replace($m, ['blocked_user_ids' => [7]]),
            'active count' => fn ($m) => array_replace_recursive($m, ['expected' => ['active_count' => 7]]),
            'proposed team count' => fn ($m) => array_replace_recursive($m, ['expected' => ['proposed_final_team_count' => 4]]),
            'final team count' => fn ($m) => array_replace_recursive($m, ['expected' => ['team_count_after' => 4]]),
            'overlap' => fn ($m) => array_replace($m, ['archive_user_ids' => [7, 8]]),
            'duplicate delete' => fn ($m) => array_replace($m, ['delete_user_ids' => [7, 7]]),
            'duplicate archive' => fn ($m) => array_replace($m, ['archive_user_ids' => [8, 8]]),
            'incomplete nonofficial coverage' => fn ($m) => array_replace($m, ['archive_user_ids' => []]),
            'official action ID' => fn ($m) => array_replace($m, ['delete_user_ids' => [1, 7]]),
            'official action email' => function ($m) { $m['users'][6]['email'] = $this->officialEmails()[0]; return $m; },
            'unknown action ID' => fn ($m) => array_replace($m, ['delete_user_ids' => [7, 99]]),
            'physical arithmetic' => fn ($m) => array_replace_recursive($m, ['expected' => ['physical_count' => 999]]),
        ];
        foreach ($mutations as $name => $mutation) {
            $this->assertTrue(CleanupUserRoster::hasExecutionBlockers($mutation($base)), $name);
        }
    }

    public function test_each_postcondition_detects_final_database_drift(): void
    {
        $this->preparePostconditionState();
        $command = $this->exposedCommand();
        $base = $this->postconditionManifest();
        $cases = [
            'active' => fn () => DB::table('users')->where('id', $base['official_user_ids'][0])->update(['archived_at' => now()]),
            'team' => fn () => DB::table('model_has_roles')->where('model_id', $base['official_user_ids'][1])->delete(),
            'physical' => fn () => $this->user('Physical Drift', 'physical-drift@company.invalid'),
            'session' => fn () => DB::table('sessions')->insert(['id' => 'cleanup-leftover', 'user_id' => $base['archive_user_ids'][0], 'payload' => '', 'last_activity' => 1]),
            'reset' => fn () => DB::table('password_reset_tokens')->insert(['email' => collect($base['users'])->firstWhere('id', $base['archive_user_ids'][0])['email'], 'token' => 'leftover']),
            'official' => fn () => DB::table('users')->where('id', $base['official_user_ids'][0])->delete(),
            'project count' => fn () => DB::table('projects')->insert(['code' => 'DRFT', 'name' => 'Project Count Drift', 'created_at' => now(), 'updated_at' => now()]),
            'client count' => fn () => DB::table('clients')->insert(['name' => 'Client Drift', 'code' => 'CDRIFT', 'created_at' => now(), 'updated_at' => now()]),
            'required project IDs' => function () { DB::table('projects')->where('id', 652)->delete(); DB::table('projects')->insert(['id' => 650, 'code' => 'R650', 'name' => 'Replacement Sentinel', 'created_at' => now(), 'updated_at' => now()]); },
        ];
        foreach ($cases as $name => $change) {
            DB::beginTransaction();
            try {
                $change();
                $this->expectRuntimeException(fn () => $command->postconditions($base), $name);
            } finally {
                DB::rollBack();
            }
        }
    }

    public function test_failed_final_postcondition_rolls_back_all_mutations(): void
    {
        $this->preparePostconditionState();
        $archive = $this->user('Archive Rollback Final', 'archive-final@example.test');
        DB::table('users')->where('id', $archive)->update(['remember_token' => 'restore-token']);
        $delete = $this->user('Delete Rollback Final', 'delete-final@example.test');
        $assignment = DB::table('team_assignments')->insertGetId(['user_id' => $delete, 'project_id' => 651, 'title' => 'Restore Plan', 'created_at' => now(), 'updated_at' => now()]);
        $manifest = $this->postconditionManifest();
        $manifest['users'][] = ['id' => $archive, 'email' => 'archive-final@example.test', 'archived_at' => null];
        $manifest['users'][] = ['id' => $delete, 'email' => 'delete-final@example.test', 'archived_at' => null];
        $manifest['archive_user_ids'] = [$archive];
        $manifest['delete_user_ids'] = [$delete];
        $manifest['expected']['physical_count'] = DB::table('users')->count() - 2;
        $manifest['detachments'] = [['table' => 'team_assignments', 'record_ids' => [$assignment], 'column' => 'user_id', 'old_user_id' => $delete, 'planned_value' => 'row deleted', 'action' => 'delete', 'exact_key' => [], 'expected_count' => 1]];
        $this->expectRuntimeException(fn () => $this->exposedCommand()->apply($manifest), 'final postcondition');
        $this->assertDatabaseHas('users', ['id' => $archive, 'archived_at' => null, 'remember_token' => 'restore-token']);
        $this->assertDatabaseHas('users', ['id' => $delete]);
        $this->assertDatabaseHas('team_assignments', ['id' => $assignment, 'user_id' => $delete]);
    }

    public function test_projection_reaches_six_active_and_five_team_members(): void
    {
        $this->ensureOfficialAccounts();
        DB::table('users')->whereNotIn('email', $this->officialEmails())->update(['archived_at' => now()]);
        $historical = $this->user('Projected Archive', 'projected-archive@example.test');
        $this->assignRole($historical, 'fullstack_dev');
        DB::table('audit_logs')->insert(['user_id' => $historical, 'action' => 'created', 'module' => 'users', 'auditable_type' => 'User', 'auditable_id' => $historical, 'created_at' => now()]);

        [, $manifest] = $this->dryRun();

        $this->assertContains($historical, $manifest['archive_user_ids']);
        $this->assertSame(6, $manifest['expected']['active_count']);
        $this->assertSame(5, $manifest['expected']['team_count_after']);
    }

    private function dryRun(): array
    {
        $before = File::glob(storage_path('app/cleanup-user-roster-*.json'));
        $this->artisan('maintenance:cleanup-user-roster', ['--dry-run' => true])->assertSuccessful();
        $path = array_values(array_diff(File::glob(storage_path('app/cleanup-user-roster-*.json')), $before))[0];
        $this->manifests[] = $path;

        return [$path, json_decode(File::get($path), true, flags: JSON_THROW_ON_ERROR)];
    }

    private function user(string $name, string $email): int
    {
        return DB::table('users')->insertGetId(['name' => $name, 'email' => $email, 'password' => 'unused', 'created_at' => now(), 'updated_at' => now()]);
    }

    private function assignRole(int $userId, string $name): void
    {
        $roleId = DB::table('roles')->where('name', $name)->where('guard_name', 'web')->value('id')
            ?: DB::table('roles')->insertGetId(['name' => $name, 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()]);
        DB::table('model_has_roles')->insert(['role_id' => $roleId, 'model_type' => 'App\\Models\\User', 'model_id' => $userId]);
    }

    private function ensureOfficialAccounts(): void
    {
        foreach ([
            ['Joshua Raphael', 'joshua.raphael@avatech.test', 'ceo_pm'],
            ['Ahmad Rafiadly Arlisyah', 'ahmad.arlisyah@avatech.test', 'sa_qa'],
            ['Ferry Achmad', 'ferry.achmad@avatech.test', 'fullstack_dev'],
            ['Irwan Kurniawan', 'irwan.kurniawan@avatech.test', 'fullstack_dev'],
            ['Genta', 'genta@avatech.test', 'fullstack_dev'],
            ['Yuda Prayoga', 'yuda.prayoga@avatech.test', 'uiux_designer'],
        ] as [$name, $email, $role]) {
            $id = DB::table('users')->where('email', $email)->value('id') ?: $this->user($name, $email);
            DB::table('users')->where('id', $id)->update(['name' => $name, 'archived_at' => null]);
            DB::table('model_has_roles')->where('model_id', $id)->where('model_type', User::class)->delete();
            $this->assignRole($id, $role);
        }
    }

    private function validManifest(): array
    {
        $users = collect($this->officialEmails())->values()->map(fn ($email, $index) => ['id' => $index + 1, 'email' => $email, 'official' => true])->all();
        $users[] = ['id' => 7, 'email' => 'delete@example.test', 'official' => false];
        $users[] = ['id' => 8, 'email' => 'archive@example.test', 'official' => false];

        return [
            'format_version' => 4,
            'warnings' => [],
            'blocked_user_ids' => [],
            'official_account_verification' => array_fill(0, 6, ['valid' => true]),
            'official_emails' => $this->officialEmails(),
            'official_user_ids' => [1, 2, 3, 4, 5, 6],
            'delete_user_ids' => [7],
            'archive_user_ids' => [8],
            'users' => $users,
            'reference_schema' => [],
            'reference_aggregate' => [],
            'current' => ['physical_count' => 8],
            'expected' => ['physical_count' => 7, 'active_count' => 6, 'proposed_final_team_count' => 5, 'team_count_after' => 5],
            'sentinels' => ['project_count' => 22, 'client_count' => 19, 'required_project_ids' => [651, 652], 'valid' => true],
            'feasibility' => 'SAFE_TO_SHOW_6_ACTIVE_BUT_NOT_DELETE_ALL_HISTORY',
        ];
    }

    private function preparePostconditionState(): void
    {
        $this->ensureOfficialAccounts();
        DB::table('users')->whereNotIn(DB::raw('LOWER(email)'), $this->officialEmails())->update(['archived_at' => now(), 'remember_token' => null]);
        $this->ensureExecutionSentinels();
    }

    private function postconditionManifest(): array
    {
        $users = DB::table('users')->whereIn(DB::raw('LOWER(email)'), $this->officialEmails())->orderBy('id')->get()->map(fn ($user) => ['id' => (int) $user->id, 'email' => strtolower($user->email), 'archived_at' => $user->archived_at])->all();

        return [
            'users' => array_merge($users, [['id' => 999999999, 'email' => 'action@example.test', 'archived_at' => now()]]),
            'official_user_ids' => array_column($users, 'id'),
            'official_emails' => $this->officialEmails(),
            'delete_user_ids' => [],
            'archive_user_ids' => [999999999],
            'detachments' => [],
            'role_pivots_to_delete' => [],
            'authentication_records_to_delete' => [],
            'expected' => ['physical_count' => DB::table('users')->count()],
        ];
    }

    private function exposedCommand(): CleanupUserRoster
    {
        return new class extends CleanupUserRoster {
            public function apply(array $manifest): void
            {
                $this->applyManifest($manifest);
            }

            public function postconditions(array $manifest): void
            {
                $this->assertPostconditions($manifest);
            }
        };
    }

    private function expectRuntimeException(callable $callback, string $message): void
    {
        $thrown = false;
        try {
            $callback();
        } catch (RuntimeException) {
            $thrown = true;
        }
        $this->assertTrue($thrown, $message.' was accepted.');
    }

    private function ensureExecutionSentinels(): void
    {
        foreach ([651, 652] as $id) {
            if (! DB::table('projects')->where('id', $id)->exists()) {
                DB::table('projects')->insert(['id' => $id, 'code' => 'S'.substr((string) $id, -3), 'name' => 'Cleanup Sentinel '.$id, 'created_at' => now(), 'updated_at' => now()]);
            }
        }
        while (DB::table('clients')->count() < 19) {
            $number = DB::table('clients')->count();
            DB::table('clients')->insert(['name' => 'Cleanup Fixture '.$number, 'code' => 'CF'.str_pad((string) $number, 4, '0', STR_PAD_LEFT), 'created_at' => now(), 'updated_at' => now()]);
        }
        while (DB::table('projects')->count() < 22) {
            $number = DB::table('projects')->count();
            DB::table('projects')->insert(['code' => 'F'.str_pad((string) ($number % 1000), 3, '0', STR_PAD_LEFT), 'name' => 'Cleanup Fixture '.$number, 'created_at' => now(), 'updated_at' => now()]);
        }
        if (DB::table('clients')->count() !== 19 || DB::table('projects')->count() !== 22) {
            throw new RuntimeException('Testing baseline exceeds cleanup sentinel counts.');
        }
    }

    private function officialEmails(): array
    {
        return ['joshua.raphael@avatech.test', 'ahmad.arlisyah@avatech.test', 'ferry.achmad@avatech.test', 'irwan.kurniawan@avatech.test', 'genta@avatech.test', 'yuda.prayoga@avatech.test'];
    }
}
