<?php

namespace Tests\Feature;

use App\Console\Commands\CleanupTestArtifacts;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\TestCase;

class CleanupTestArtifactsTest extends TestCase
{
    private array $manifests = [];

    protected function tearDown(): void
    {
        foreach ($this->manifests as $path) {
            File::delete($path);
        }

        parent::tearDown();
    }

    public function test_dry_run_records_exact_context_without_treating_ordinary_preserved_projects_as_conflicts(): void
    {
        $clientId = $this->client('Ordinary Client Dry Run', 'ODR');
        DB::table('projects')->insert(['name' => 'test 1', 'code' => 'T1X', 'client_id' => $clientId, 'created_at' => now(), 'updated_at' => now()]);
        DB::table('projects')->insert(['name' => 'test 2', 'code' => 'T2X', 'client_id' => $clientId, 'created_at' => now(), 'updated_at' => now()]);
        $candidateId = $this->project('Focused Test Project Dry Run', 'FTDR', $clientId);
        $this->client('TEST-Broad Match Must Stay', 'TBMS');

        [$path, $manifest] = $this->dryRun();

        $this->assertSame('avatech_smart_pmis_testing', $manifest['database']);
        $this->assertSame(['test 1', 'test 2'], $manifest['preserve_projects']);
        $this->assertSame([$candidateId], array_column($manifest['candidates']['projects'], 'id'));
        $this->assertSame([], $manifest['preserved_conflicts']);
        $this->assertSame([], $manifest['manual_review']);
        $this->assertArrayHasKey('candidate_users', $manifest);
        $this->assertArrayHasKey('preserved_users', $manifest);
        $this->assertArrayHasKey('uncertain_users', $manifest);
        $this->assertArrayHasKey('user_shared_warnings', $manifest);
        $this->assertArrayHasKey('project_relation_inventory', $manifest);
        $this->assertArrayHasKey('post_commit_file_cleanup_plan', $manifest);
        $this->assertDatabaseHas('clients', ['name' => 'TEST-Broad Match Must Stay']);
        $this->assertFileExists($path);
    }

    public function test_production_execution_is_rejected(): void
    {
        app()->detectEnvironment(fn () => 'production');

        try {
            $this->expectException(RuntimeException::class);
            CleanupTestArtifacts::assertSafeExecutionDatabase('avatech_smart_pmis');
        } finally {
            app()->detectEnvironment(fn () => 'testing');
        }
    }

    public function test_unexpected_environment_and_wrong_database_are_rejected(): void
    {
        app()->detectEnvironment(fn () => 'staging');

        try {
            CleanupTestArtifacts::assertSafeExecutionDatabase();
            $this->fail('Unexpected environment was accepted.');
        } catch (RuntimeException) {
            app()->detectEnvironment(fn () => 'testing');
        }

        config(['database.connections.mysql.database' => 'wrong_database']);
        $this->expectException(RuntimeException::class);
        CleanupTestArtifacts::assertSafeExecutionDatabase();
    }

    public function test_local_missing_and_mismatched_database_confirmation_are_rejected(): void
    {
        app()->detectEnvironment(fn () => 'local');
        config(['database.connections.mysql.database' => 'avatech_smart_pmis']);

        try {
            foreach ([null, 'wrong'] as $confirmation) {
                try {
                    CleanupTestArtifacts::assertSafeExecutionDatabase($confirmation);
                    $this->fail('Missing or mismatched confirmation was accepted.');
                } catch (RuntimeException) {
                }
            }
            $this->assertTrue(true);
        } finally {
            app()->detectEnvironment(fn () => 'testing');
            config(['database.connections.mysql.database' => 'avatech_smart_pmis_testing']);
        }
    }

    public function test_local_execute_requires_both_preserves_explicitly(): void
    {
        app()->detectEnvironment(fn () => 'local');

        try {
            foreach ([[], ['test 1'], ['test 2']] as $preserves) {
                try {
                    CleanupTestArtifacts::assertExplicitLocalPreserves($preserves);
                    $this->fail('Implicit or incomplete local preserves were accepted.');
                } catch (RuntimeException) {
                }
            }
            CleanupTestArtifacts::assertExplicitLocalPreserves(['test 2', 'test 1']);
            $this->assertTrue(true);
        } finally {
            app()->detectEnvironment(fn () => 'testing');
        }
    }

    public function test_testing_execute_rejects_missing_manifest_or_token(): void
    {
        $this->artisan('maintenance:cleanup-test-artifacts', ['--execute' => true])
            ->expectsOutputToContain('requires --manifest and --confirmation-token')
            ->assertFailed();

        [$path] = $this->dryRun();
        $this->artisan('maintenance:cleanup-test-artifacts', ['--execute' => true, '--manifest' => $path])
            ->expectsOutputToContain('requires --manifest and --confirmation-token')
            ->assertFailed();
    }

    public function test_execute_rejects_stale_inventory(): void
    {
        [$path, $manifest] = $this->dryRun();
        $clientId = $this->client('Ordinary Client Stale', 'OCS');
        $this->project('Focused Test Project Added Later', 'FTAL', $clientId);

        $this->execute($path, $manifest)->expectsOutputToContain('candidate inventory changed')->assertFailed();
    }

    public function test_execute_rejects_preserved_candidate_conflict(): void
    {
        $clientId = $this->client('Ordinary Client Preserve', 'OCP');
        $this->project('Assigned Project Preserve Me', 'APPM', $clientId);
        [$path, $manifest] = $this->dryRun(['Assigned Project Preserve Me']);

        $this->assertNotEmpty($manifest['preserved_conflicts']);
        $this->execute($path, $manifest, ['Assigned Project Preserve Me'])->assertFailed();
    }

    public function test_execute_rejects_shared_client_and_user_warnings(): void
    {
        $clientId = $this->client('Focused Test Client Shared', 'FTCS');
        $candidateId = $this->project('Focused Test Project Shared', 'FTPS', $clientId);
        $this->project('Ordinary Project Shared', 'OPS', $clientId);
        $userId = DB::table('users')->insertGetId(['name' => 'Shared Cleanup User', 'email' => 'shared-cleanup@example.test', 'password' => 'unused', 'created_at' => now(), 'updated_at' => now()]);
        DB::table('team_assignments')->insert(['user_id' => $userId, 'project_id' => $candidateId, 'title' => 'Focused Test Assignment Shared', 'created_at' => now(), 'updated_at' => now()]);
        DB::table('team_assignments')->insert(['user_id' => $userId, 'project_id' => $candidateId, 'title' => 'Ordinary Assignment Shared', 'created_at' => now(), 'updated_at' => now()]);
        [$path, $manifest] = $this->dryRun();

        $this->assertCount(2, $manifest['shared_warnings']);
        $this->execute($path, $manifest)->assertFailed();
    }

    public function test_seeded_user_is_explicitly_preserved_and_candidate_project_files_are_plan_only(): void
    {
        $clientId = $this->client('Ordinary Client Manifest', 'OCM');
        $projectId = $this->project('Focused Test Project Manifest', 'FTPM', $clientId);
        $seededUserId = DB::table('users')->insertGetId(['name' => 'Joshua Raphael', 'email' => 'joshua.raphael@avatech.test', 'password' => 'unused', 'created_at' => now(), 'updated_at' => now()]);
        DB::table('projects')->where('id', $projectId)->update(['lead_user_id' => $seededUserId]);
        DB::table('project_tasks')->insert(['project_id' => $projectId, 'assigned_to' => $seededUserId, 'title' => 'Manifest File Task', 'deliverable_file_path' => 'project-design-deliverables/missing.pdf', 'created_at' => now(), 'updated_at' => now()]);

        [, $manifest] = $this->dryRun();

        $this->assertContains($seededUserId, array_column($manifest['preserved_users'], 'id'));
        $this->assertNotContains($seededUserId, array_column($manifest['candidate_users'], 'id'));
        $this->assertSame('project-design-deliverables/missing.pdf', $manifest['files'][0]['relative_path']);
        $this->assertSame($manifest['files'], $manifest['post_commit_file_cleanup_plan']);
        $this->assertFalse($manifest['files'][0]['exists']);
    }

    public function test_global_testing_evidence_is_preserved_manual_inventory_and_physical_file_is_untouched(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('evidence/global.pdf', 'evidence');
        $evidenceId = DB::table('testing_evidences')->insertGetId(['category' => 'UAT', 'title' => 'Global Evidence', 'result_status' => 'passed', 'tested_at' => now()->toDateString(), 'evidence_file_path' => 'evidence/global.pdf', 'created_at' => now(), 'updated_at' => now()]);
        $clientId = $this->client('Ordinary Client Global Evidence', 'OCGE');
        $this->project('Focused Test Project Global Evidence', 'FTGE', $clientId);

        [, $manifest] = $this->dryRun();
        $relation = collect($manifest['project_relation_inventory'])->firstWhere('table', 'testing_evidences');
        $file = collect($manifest['files'])->firstWhere('record_id', $evidenceId);

        $this->assertSame('manual_global_preserved', $relation['classification']);
        $this->assertGreaterThanOrEqual(1, $relation['count']);
        $this->assertSame([], $relation['candidate_record_ids']);
        $this->assertSame('manual_global_preserved', $file['classification']);
        $this->assertSame('public', $file['disk']);
        $this->assertTrue($file['legacy_public']);
        Storage::disk('public')->assertExists('evidence/global.pdf');
    }

    public function test_candidate_and_preserved_project_users_are_classified_without_deleting_shared_unknown_role(): void
    {
        $clientId = $this->client('Ordinary Client Users', 'OCU');
        $candidateProjectId = $this->project('Focused Test Project Users', 'FTPU', $clientId);
        $preservedProjectId = $this->project('test 1', 'T1U', $clientId);
        $candidateUserId = DB::table('users')->insertGetId(['name' => 'Candidate User', 'email' => 'candidate-only@example.test', 'password' => 'unused', 'created_at' => now(), 'updated_at' => now()]);
        $mixedUserId = DB::table('users')->insertGetId(['name' => 'Mixed User', 'email' => 'mixed-user@example.test', 'password' => 'unused', 'created_at' => now(), 'updated_at' => now()]);
        DB::table('projects')->where('id', $candidateProjectId)->update(['lead_user_id' => $candidateUserId]);
        DB::table('projects')->where('id', $preservedProjectId)->update(['lead_user_id' => $mixedUserId]);
        DB::table('project_tasks')->insert(['project_id' => $candidateProjectId, 'assigned_to' => $mixedUserId, 'title' => 'Mixed PIC', 'created_at' => now(), 'updated_at' => now()]);
        $roleId = DB::table('roles')->insertGetId(['name' => 'unknown_role', 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()]);
        DB::table('model_has_roles')->insert(['role_id' => $roleId, 'model_type' => 'App\\Models\\User', 'model_id' => $mixedUserId]);

        [, $manifest] = $this->dryRun();

        $this->assertContains($candidateUserId, array_column($manifest['candidate_users'], 'id'));
        $this->assertContains($mixedUserId, array_column($manifest['preserved_users'], 'id'));
        $this->assertNotContains($mixedUserId, array_column($manifest['candidate_users'], 'id'));
        $role = collect($manifest['role_pivot_inventory'])->firstWhere('id', $roleId);
        $this->assertSame('unknown_role', $role['name']);
        $this->assertFalse($role['candidate']);
    }

    public function test_valid_testing_execution_deletes_exact_manifest_ids_reports_before_and_after_and_rolls_back(): void
    {
        $clientId = $this->client('Focused Test Client Execute', 'FTCE');
        $candidateId = $this->project('Focused Test Project Execute', 'FTPE', $clientId);
        $ordinaryClientId = $this->client('Ordinary Client Remains', 'OCR');
        $ordinaryId = $this->project('Ordinary Project Remains', 'OPR', $ordinaryClientId);
        [$path, $manifest] = $this->dryRun();

        $this->execute($path, $manifest)
            ->expectsOutputToContain('Before: projects=1, clients=1, assignments=0, users=0')
            ->expectsOutputToContain('After: projects=0, clients=0, assignments=0, users=0')
            ->assertSuccessful();

        $this->assertDatabaseMissing('projects', ['id' => $candidateId]);
        $this->assertDatabaseMissing('clients', ['id' => $clientId]);
        $this->assertDatabaseHas('projects', ['id' => $ordinaryId]);
        $this->assertDatabaseHas('clients', ['id' => $ordinaryClientId]);
        $this->assertFalse(File::exists($path));
        $this->assertSame('avatech_smart_pmis_testing', DB::connection()->getDatabaseName());
        $this->assertTrue(DB::connection()->transactionLevel() > 0);
    }

    private function dryRun(array $preserves = []): array
    {
        $before = File::glob(storage_path('app/cleanup-test-artifacts-*.json'));
        $this->artisan('maintenance:cleanup-test-artifacts', ['--dry-run' => true, '--preserve-project' => $preserves])->assertSuccessful();
        $paths = array_values(array_diff(File::glob(storage_path('app/cleanup-test-artifacts-*.json')), $before));
        $path = $paths[0];
        $this->manifests[] = $path;

        return [$path, json_decode(File::get($path), true, flags: JSON_THROW_ON_ERROR)];
    }

    private function execute(string $path, array $manifest, array $preserves = [])
    {
        return $this->artisan('maintenance:cleanup-test-artifacts', [
            '--execute' => true,
            '--manifest' => $path,
            '--confirmation-token' => $manifest['confirmation_token'],
            '--preserve-project' => $preserves,
        ]);
    }

    private function client(string $name, string $code): int
    {
        return DB::table('clients')->insertGetId(['name' => $name, 'code' => $code, 'tier' => 'standard', 'created_at' => now(), 'updated_at' => now()]);
    }

    private function project(string $name, string $code, int $clientId): int
    {
        return DB::table('projects')->insertGetId(['name' => $name, 'code' => $code, 'client_id' => $clientId, 'created_at' => now(), 'updated_at' => now()]);
    }
}
