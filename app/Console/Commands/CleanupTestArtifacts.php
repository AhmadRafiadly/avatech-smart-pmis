<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class CleanupTestArtifacts extends Command
{
    private const PROJECT_PREFIXES = ['Dependency Test Project ', 'Regression Test Project ', 'Focused Test Project ', 'Focused Flow Project ', 'Assigned Project ', 'Unassigned Project ', 'Multi Role Project '];

    private const CLIENT_PREFIXES = ['Dependency Test Client ', 'Regression Test Client ', 'Focused Test Client ', 'Focused Flow Client '];

    private const ASSIGNMENT_PREFIXES = ['Dependency Test Assignment', 'Focused Test Assignment', 'Focused Flow Assignment'];

    private const STABLE_CODES = ['AC', 'BP', 'GA', 'DL', 'EX', 'ZN', 'OT', 'KP'];

    private const INTENTIONAL_EMAILS = [
        'joshua.raphael@avatech.test', 'ahmad.arlisyah@avatech.test', 'ferry.achmad@avatech.test',
        'irwan.kurniawan@avatech.test', 'genta@avatech.test', 'yuda.prayoga@avatech.test',
        'joshua@avatech.demo', 'adly@avatech.demo', 'yuda@avatech.demo', 'ferry@avatech.demo',
        'irwan@avatech.demo', 'genta@avatech.demo', 'achmad@avatech.demo',
    ];

    private const FILE_COLUMNS = [
        'project_tasks' => ['deliverable_file_path'],
        'project_task_design_deliverables' => ['pdf_file_path'],
        'project_requirement_inbox_items' => ['file_path'],
    ];

    protected $signature = 'maintenance:cleanup-test-artifacts
        {--dry-run : Inventory candidates without deleting}
        {--execute : Delete candidates from the testing database}
        {--manifest= : Reviewed dry-run manifest path}
        {--confirmation-token= : Confirmation token printed by dry-run}
        {--database-confirmation= : Exact development database name confirmation}
        {--preserve-project=* : Exact project name to preserve}';

    protected $description = 'Inventory or remove only explicitly prefixed test artifacts';

    public function handle(): int
    {
        if ($this->option('dry-run') && $this->option('execute')) {
            $this->error('--dry-run and --execute are mutually exclusive.');

            return self::FAILURE;
        }

        return $this->option('execute') ? $this->executeCleanup() : $this->dryRun();
    }

    public static function assertSafeExecutionDatabase(?string $confirmation = null): void
    {
        $connection = config('database.default');
        $database = config("database.connections.{$connection}.database");
        $testing = app()->environment('testing') && $connection === 'mysql' && $database === 'avatech_smart_pmis_testing';
        $local = app()->environment('local') && $connection === 'mysql' && $database === 'avatech_smart_pmis' && $confirmation === 'avatech_smart_pmis';
        if (! $testing && ! $local) {
            throw new RuntimeException('Execution requires testing/avatech_smart_pmis_testing or local/avatech_smart_pmis with --database-confirmation=avatech_smart_pmis.');
        }
    }

    public static function assertExplicitLocalPreserves(array $preserves): void
    {
        if (app()->environment('local') && (! in_array('test 1', $preserves, true) || ! in_array('test 2', $preserves, true))) {
            throw new RuntimeException('Local execution requires explicit --preserve-project="test 1" and --preserve-project="test 2".');
        }
    }

    private function assertSafeDryRunDatabase(): void
    {
        $connection = config('database.default');
        $database = DB::connection()->getDatabaseName();
        if ($connection !== 'mysql' || ! ((app()->environment('testing') && $database === 'avatech_smart_pmis_testing') || (app()->environment('local') && $database === 'avatech_smart_pmis'))) {
            throw new RuntimeException('Dry run requires testing/avatech_smart_pmis_testing or local/avatech_smart_pmis.');
        }
    }

    private function dryRun(): int
    {
        $this->assertSafeDryRunDatabase();
        $manifest = $this->inventory();
        $checksum = $this->checksum($manifest);
        $manifest['checksum'] = $checksum;
        $manifest['confirmation_token'] = $this->confirmationToken($checksum);
        $path = storage_path('app/cleanup-test-artifacts-'.$checksum.'.json');
        File::put($path, $this->encode($manifest));
        $this->line('Manifest: '.$path);
        $this->line('Checksum: '.$checksum);
        $this->line('Confirmation token: '.$manifest['confirmation_token']);
        $this->line('Candidates: projects='.$manifest['counts']['projects'].', clients='.$manifest['counts']['clients'].', assignments='.$manifest['counts']['assignments'].', users='.$manifest['counts']['users']);
        $this->warn('Dry run only. Nothing was deleted. Review uncertain_users, user_shared_warnings, relation_inventory, and preserved_conflicts.');

        return self::SUCCESS;
    }

    private function executeCleanup(): int
    {
        self::assertSafeExecutionDatabase($this->option('database-confirmation'));
        self::assertExplicitLocalPreserves($this->option('preserve-project') ?: []);
        $path = (string) $this->option('manifest');
        $token = (string) $this->option('confirmation-token');
        if ($path === '' || $token === '' || ! File::isFile($path)) {
            $this->error('Execution requires --manifest and --confirmation-token from a reviewed dry run.');

            return self::FAILURE;
        }

        $manifest = json_decode(File::get($path), true, flags: JSON_THROW_ON_ERROR);
        $checksum = (string) ($manifest['checksum'] ?? '');
        $storedToken = (string) ($manifest['confirmation_token'] ?? '');
        $unsigned = $manifest;
        unset($unsigned['checksum'], $unsigned['confirmation_token']);
        $current = $this->inventory();
        $candidateUserWarnings = array_values(array_filter($manifest['user_shared_warnings'] ?? [], fn ($warning) => in_array($warning['user_id'] ?? null, array_column($manifest['candidate_users'] ?? [], 'id'), true)));
        $valid = $checksum !== ''
            && hash_equals($checksum, $this->checksum($unsigned))
            && hash_equals($storedToken, $this->confirmationToken($checksum))
            && hash_equals($storedToken, $token)
            && ($manifest['database'] ?? null) === DB::connection()->getDatabaseName()
            && $unsigned === $current
            && ($manifest['shared_warnings'] ?? null) === []
            && $candidateUserWarnings === []
            && ($manifest['preserved_conflicts'] ?? null) === []
            && ($manifest['unaccounted_relations'] ?? null) === []
            && $this->hasOnlyAllowlistedCandidates($manifest['candidates'] ?? []);
        if (! $valid) {
            $this->error('Manifest, checksum, token, database, preservation list, candidate inventory changed, or execution blockers exist. Run a new dry run.');

            return self::FAILURE;
        }

        $projectIds = array_column($manifest['candidates']['projects'], 'id');
        $clientIds = array_column($manifest['candidates']['clients'], 'id');
        $assignmentIds = array_column($manifest['candidates']['assignments'], 'id');
        $userIds = array_column($manifest['candidate_users'], 'id');
        $auditIds = array_values(array_unique(array_merge(...array_map(fn ($relation) => $relation['candidate_record_ids'] ?? [], array_filter($manifest['project_relation_inventory'], fn ($relation) => $relation['table'] === 'audit_logs')))));
        DB::transaction(function () use ($projectIds, $clientIds, $assignmentIds, $userIds, $auditIds): void {
            if ($auditIds !== []) {
                DB::table('audit_logs')->whereIn('id', $auditIds)->delete();
            }
            if ($assignmentIds !== []) {
                DB::table('team_assignments')->whereIn('id', $assignmentIds)->delete();
            }
            if ($projectIds !== []) {
                DB::table('projects')->whereIn('id', $projectIds)->delete();
            }
            if ($userIds !== []) {
                DB::table('users')->whereIn('id', $userIds)->delete();
            }
            if ($clientIds !== []) {
                DB::table('clients')->whereIn('id', $clientIds)->delete();
            }
        });

        $after = $this->inventory()['counts'];
        File::delete($path);
        $this->info('Manifest candidates deleted in one transaction; physical files were not deleted; manifest consumed.');
        $this->line('Before: projects='.$manifest['counts']['projects'].', clients='.$manifest['counts']['clients'].', assignments='.$manifest['counts']['assignments'].', users='.$manifest['counts']['users']);
        $this->line('After: projects='.$after['projects'].', clients='.$after['clients'].', assignments='.$after['assignments'].', users='.$after['users']);
        $this->line('Post-commit file cleanup plan: '.count($manifest['post_commit_file_cleanup_plan']).' file(s), manual action only.');

        return self::SUCCESS;
    }

    private function inventory(): array
    {
        $preservedNames = array_values(array_unique(array_merge(['test 1', 'test 2'], $this->option('preserve-project') ?: [])));
        sort($preservedNames, SORT_STRING);
        $matched = $this->matchingRows('projects', 'name', self::PROJECT_PREFIXES, ['id', 'name', 'code', 'client_id']);
        $projectIds = [];
        $projects = [];
        $preservedConflicts = [];
        foreach ($matched as $project) {
            $reasons = [];
            if (in_array($project->name, $preservedNames, true)) {
                $reasons[] = 'preserved exact project name';
            }
            if (in_array($project->code, self::STABLE_CODES, true)) {
                $reasons[] = 'stable demo project code';
            }
            if ($reasons !== []) {
                $preservedConflicts[] = ['id' => $project->id, 'name' => $project->name, 'code' => $project->code, 'reasons' => $reasons];

                continue;
            }
            $projectIds[] = $project->id;
            $projects[] = ['id' => $project->id, 'name' => $project->name, 'code' => $project->code, 'client_id' => $project->client_id, 'reason' => 'name starts with '.$this->matchingPrefix($project->name, self::PROJECT_PREFIXES), 'classification' => 'project'];
        }

        [$relations, $unaccountedRelations] = $this->projectRelationInventory($projectIds);
        $relationCounts = [];
        foreach ($relations as $relation) {
            $relationCounts[$relation['table'].'.'.$relation['relation']] = $relation['count'];
        }
        foreach ($projects as &$project) {
            $project['child_counts'] = $this->countsForProject($relations, $project['id']);
        }
        unset($project);

        $clients = [];
        foreach ($this->matchingRows('clients', 'name', self::CLIENT_PREFIXES, ['id', 'name']) as $client) {
            $shared = DB::table('projects')->where('client_id', $client->id)->when($projectIds !== [], fn ($q) => $q->whereNotIn('id', $projectIds))->count();
            $clients[] = ['id' => $client->id, 'name' => $client->name, 'reason' => 'name starts with '.$this->matchingPrefix($client->name, self::CLIENT_PREFIXES), 'classification' => 'client', 'child_counts' => ['projects' => DB::table('projects')->where('client_id', $client->id)->count()], 'shared_warning' => $shared ? "Referenced by {$shared} non-candidate project(s); manual review required" : null];
        }

        $assignments = [];
        foreach ($this->matchingRows('team_assignments', 'title', self::ASSIGNMENT_PREFIXES, ['id', 'title', 'project_id', 'user_id']) as $row) {
            $outside = DB::table('team_assignments')->where('user_id', $row->user_id)->where('id', '!=', $row->id)->count();
            $assignments[] = ['id' => $row->id, 'name' => $row->title, 'project_id' => $row->project_id, 'user_id' => $row->user_id, 'reason' => 'title starts with '.$this->matchingPrefix($row->title, self::ASSIGNMENT_PREFIXES), 'classification' => 'assignment', 'child_counts' => [], 'shared_warning' => $outside ? "User has {$outside} non-candidate assignment(s); user is preserved" : null];
        }

        [$candidateUsers, $preservedUsers, $uncertainUsers, $userWarnings] = $this->userInventory($projectIds, $relations);
        $roles = $this->roleInventory($candidateUsers);
        $files = $this->fileInventory($projectIds, $relations);
        $warnings = array_values(array_filter(array_merge(
            array_map(fn ($row) => $row['shared_warning'] ? "client {$row['id']}: {$row['shared_warning']}" : null, $clients),
            array_map(fn ($row) => $row['shared_warning'] ? "assignment {$row['id']}: {$row['shared_warning']}" : null, $assignments)
        )));

        return [
            'format_version' => 3,
            'database' => DB::connection()->getDatabaseName(),
            'preserve_projects' => $preservedNames,
            'prefixes' => ['projects' => self::PROJECT_PREFIXES, 'clients' => self::CLIENT_PREFIXES, 'assignments' => self::ASSIGNMENT_PREFIXES],
            'stable_demo_codes' => self::STABLE_CODES,
            'intentional_user_email_allowlist' => self::INTENTIONAL_EMAILS,
            'counts' => ['projects' => count($projects), 'clients' => count($clients), 'assignments' => count($assignments), 'users' => count($candidateUsers)],
            'candidates' => ['projects' => $projects, 'clients' => $clients, 'assignments' => $assignments],
            'candidate_users' => $candidateUsers,
            'preserved_users' => $preservedUsers,
            'uncertain_users' => $uncertainUsers,
            'user_shared_warnings' => $userWarnings,
            'role_pivot_inventory' => $roles,
            'project_relation_inventory' => $relations,
            'project_relation_aggregate_counts' => $relationCounts,
            'unaccounted_relations' => $unaccountedRelations,
            'files' => $files,
            'post_commit_file_cleanup_plan' => $files,
            'shared_warnings' => $warnings,
            'preserved_conflicts' => $preservedConflicts,
            'manual_review' => array_values(array_merge(array_map(fn ($c) => "Preserved project {$c['id']} ({$c['name']}): ".implode(', ', $c['reasons']), $preservedConflicts), $warnings)),
        ];
    }

    private function projectRelationInventory(array $projectIds): array
    {
        $database = DB::connection()->getDatabaseName();
        $columns = DB::table('information_schema.COLUMNS')->where('TABLE_SCHEMA', $database)->get()->groupBy('TABLE_NAME');
        $projectTables = $columns->filter(fn ($tableColumns) => $tableColumns->contains('COLUMN_NAME', 'project_id'))->keys()->sort()->values();
        $foreignKeys = DB::table('information_schema.KEY_COLUMN_USAGE as k')->leftJoin('information_schema.REFERENTIAL_CONSTRAINTS as r', function ($join) {
            $join->on('r.CONSTRAINT_SCHEMA', '=', 'k.CONSTRAINT_SCHEMA')->on('r.CONSTRAINT_NAME', '=', 'k.CONSTRAINT_NAME')->on('r.TABLE_NAME', '=', 'k.TABLE_NAME');
        })->where('k.TABLE_SCHEMA', $database)->where('k.COLUMN_NAME', 'project_id')->select('k.TABLE_NAME', 'k.CONSTRAINT_NAME', 'r.DELETE_RULE')->get()->keyBy('TABLE_NAME');
        $result = [];
        $unaccounted = [];
        foreach ($projectTables as $table) {
            $fk = $foreignKeys->get($table);
            $grouped = $projectIds === [] ? collect() : DB::table($table)->select('project_id', DB::raw('COUNT(*) as aggregate'))->whereIn('project_id', $projectIds)->groupBy('project_id')->pluck('aggregate', 'project_id');
            $risk = ! $fk || ! in_array($fk->DELETE_RULE, ['CASCADE', 'SET NULL'], true);
            $entry = ['table' => $table, 'model' => $this->modelForTable($table), 'relation' => 'project_id', 'relation_type' => 'foreign_key', 'classification' => 'candidate_project_relation', 'foreign_key' => $fk?->CONSTRAINT_NAME, 'on_delete' => $fk?->DELETE_RULE, 'cascade_verified' => $fk?->DELETE_RULE === 'CASCADE', 'orphan_risk' => $risk, 'file_fields' => self::FILE_COLUMNS[$table] ?? [], 'count' => (int) $grouped->sum(), 'per_project' => $this->normalizeCounts($grouped)];
            $result[] = $entry;
            if ($risk && $entry['count'] > 0) {
                $unaccounted[] = ['table' => $table, 'relation' => 'project_id', 'reason' => 'candidate records have no verified cascade or set-null handling', 'count' => $entry['count']];
            }
        }
        if (Schema::hasTable('project_task_design_deliverables')) {
            $grouped = $projectIds === [] ? collect() : DB::table('project_task_design_deliverables as d')->join('project_tasks as t', 't.id', '=', 'd.project_task_id')->whereIn('t.project_id', $projectIds)->select('t.project_id', DB::raw('COUNT(*) as aggregate'))->groupBy('t.project_id')->pluck('aggregate', 'project_id');
            $result[] = ['table' => 'project_task_design_deliverables', 'model' => 'App\\Models\\ProjectTaskDesignDeliverable', 'relation' => 'project_task_id -> project_tasks.project_id', 'relation_type' => 'indirect_foreign_key', 'classification' => 'candidate_project_relation', 'foreign_key' => 'project_task_design_deliverables_project_task_id_foreign', 'on_delete' => 'CASCADE', 'cascade_verified' => true, 'orphan_risk' => false, 'file_fields' => ['pdf_file_path'], 'count' => (int) $grouped->sum(), 'per_project' => $this->normalizeCounts($grouped)];
        }
        if (Schema::hasTable('testing_evidences')) {
            $count = DB::table('testing_evidences')->count();
            $result[] = ['table' => 'testing_evidences', 'model' => 'App\\Models\\TestingEvidence', 'relation' => null, 'relation_type' => 'global_unlinked', 'classification' => $count ? 'manual_global_preserved' : 'global_empty', 'foreign_key' => null, 'on_delete' => null, 'cascade_verified' => false, 'orphan_risk' => false, 'file_fields' => ['evidence_file_path'], 'count' => $count, 'per_project' => [], 'candidate_record_ids' => []];
        }
        [$morphRelations, $morphUnaccounted] = $this->morphRelationInventory($columns, $projectIds);

        return [array_merge($result, $morphRelations), array_merge($unaccounted, $morphUnaccounted)];
    }

    private function morphRelationInventory($columns, array $projectIds): array
    {
        $known = [
            'App\\Models\\Project' => ['projects', $projectIds],
            'App\\Models\\ProjectModule' => ['project_modules', $this->idsForProjects('project_modules', $projectIds)],
            'App\\Models\\ProjectTask' => ['project_tasks', $this->idsForProjects('project_tasks', $projectIds)],
            'App\\Models\\ProjectMom' => ['project_moms', $this->idsForProjects('project_moms', $projectIds)],
            'App\\Models\\ProjectQcTest' => ['project_qc_tests', $this->idsForProjects('project_qc_tests', $projectIds)],
            'App\\Models\\ProjectRequirementInboxItem' => ['project_requirement_inbox_items', $this->idsForProjects('project_requirement_inbox_items', $projectIds)],
            'App\\Models\\ProjectTaskDesignDeliverable' => ['project_task_design_deliverables', $this->designDeliverableIds($projectIds)],
            'App\\Models\\TeamAssignment' => ['team_assignments', $this->idsForProjects('team_assignments', $projectIds)],
        ];
        $result = [];
        $unaccounted = [];
        foreach ($columns as $table => $tableColumns) {
            $names = $tableColumns->pluck('COLUMN_NAME')->all();
            foreach ($names as $typeColumn) {
                if (! str_ends_with($typeColumn, '_type')) {
                    continue;
                }
                $base = substr($typeColumn, 0, -5);
                $idColumn = $base.'_id';
                if (! in_array($idColumn, $names, true)) {
                    continue;
                }
                $candidateIds = [];
                $perProject = [];
                foreach ($known as $class => [$resourceTable, $resourceIds]) {
                    if ($resourceIds === []) {
                        continue;
                    }
                    $aliases = [$class, class_basename($class), strtolower(class_basename($class))];
                    $query = DB::table($table)->whereIn($typeColumn, $aliases)->whereIn($idColumn, $resourceIds);
                    if (in_array('id', $names, true)) {
                        foreach ($query->pluck('id') as $recordId) {
                            $candidateIds[(int) $recordId] = true;
                        }
                    } elseif ($query->exists()) {
                        $candidateIds['composite:'.$typeColumn.':'.$idColumn] = true;
                    }
                }
                $candidateIds = array_keys($candidateIds);
                $entry = ['table' => $table, 'model' => $this->modelForTable($table), 'relation' => $typeColumn.'/'.$idColumn, 'relation_type' => 'morph', 'classification' => $candidateIds === [] ? 'discovered_no_candidate_records' : 'candidate_morph_relation', 'foreign_key' => null, 'on_delete' => null, 'cascade_verified' => false, 'orphan_risk' => $candidateIds !== [], 'file_fields' => self::FILE_COLUMNS[$table] ?? [], 'count' => count($candidateIds), 'per_project' => $perProject, 'candidate_record_ids' => $candidateIds];
                $result[] = $entry;
                if ($candidateIds !== [] && $table !== 'audit_logs') {
                    $unaccounted[] = ['table' => $table, 'relation' => $entry['relation'], 'reason' => 'candidate morph records lack explicit deletion handling', 'count' => count($candidateIds), 'record_ids' => $candidateIds];
                }
            }
        }

        return [$result, $unaccounted];
    }

    private function idsForProjects(string $table, array $projectIds): array
    {
        return Schema::hasTable($table) && $projectIds !== [] ? DB::table($table)->whereIn('project_id', $projectIds)->pluck('id')->map(fn ($id) => (int) $id)->all() : [];
    }

    private function designDeliverableIds(array $projectIds): array
    {
        return Schema::hasTable('project_task_design_deliverables') && $projectIds !== [] ? DB::table('project_task_design_deliverables')->whereIn('project_task_id', DB::table('project_tasks')->whereIn('project_id', $projectIds)->select('id'))->pluck('id')->map(fn ($id) => (int) $id)->all() : [];
    }

    private function userInventory(array $projectIds, array $relations): array
    {
        $database = DB::connection()->getDatabaseName();
        $refs = DB::table('information_schema.KEY_COLUMN_USAGE')->where('TABLE_SCHEMA', $database)->where('REFERENCED_TABLE_NAME', 'users')->select('TABLE_NAME', 'COLUMN_NAME')->orderBy('TABLE_NAME')->orderBy('COLUMN_NAME')->get();
        $linked = [];
        foreach ($refs as $ref) {
            if (! Schema::hasTable($ref->TABLE_NAME)) {
                continue;
            }
            $ids = $this->candidateLinkedUserIds($ref->TABLE_NAME, $ref->COLUMN_NAME, $projectIds);
            foreach ($ids as $id) {
                $linked[(int) $id] = true;
            }
        }
        $users = $linked === [] ? collect() : DB::table('users')->whereIn('id', array_keys($linked))->select('id', 'name', 'email')->orderBy('id')->get();
        $candidate = $preserved = $uncertain = $warnings = [];
        foreach ($users as $user) {
            $references = [];
            $outside = false;
            foreach ($refs as $ref) {
                if (! Schema::hasTable($ref->TABLE_NAME)) {
                    continue;
                }
                $allIds = DB::table($ref->TABLE_NAME)->where($ref->COLUMN_NAME, $user->id)->pluck('id')->map(fn ($id) => (int) $id)->all();
                if ($allIds === []) {
                    continue;
                }
                $candidateIds = $this->candidateReferenceIds($ref->TABLE_NAME, $ref->COLUMN_NAME, $user->id, $projectIds);
                $otherIds = array_values(array_diff($allIds, $candidateIds));
                $references[] = ['table' => $ref->TABLE_NAME, 'column' => $ref->COLUMN_NAME, 'record_ids' => $allIds, 'candidate_record_ids' => $candidateIds, 'preserved_record_ids' => $otherIds];
                if ($otherIds !== []) {
                    $outside = true;
                }
            }
            $roles = $this->userRoles($user->id);
            $base = ['id' => (int) $user->id, 'name' => $user->name, 'email' => $user->email, 'relationships' => $references, 'roles' => $roles, 'origin_evidence' => in_array(strtolower($user->email), self::INTENTIONAL_EMAILS, true) ? 'static allowlist from UserSeeder/AvatechDemoSeeder' : 'linked to candidate project/artifact'];
            if (in_array(strtolower($user->email), self::INTENTIONAL_EMAILS, true)) {
                $base['reason'] = 'intentional seeded/demo identity';
                $preserved[] = $base;
            } elseif ($outside) {
                $base['reason'] = 'has preserved or non-candidate references';
                $preserved[] = $base;
                $warnings[] = ['user_id' => (int) $user->id, 'reason' => $base['reason'], 'references' => $references];
            } elseif ($references === []) {
                $base['reason'] = 'candidate linkage could not be proven';
                $uncertain[] = $base;
            } else {
                $base['reason'] = 'linked to candidate artifacts with zero preserved/non-candidate/demo references';
                $candidate[] = $base;
            }
        }

        return [$candidate, $preserved, $uncertain, $warnings];
    }

    private function candidateLinkedUserIds(string $table, string $column, array $projectIds): array
    {
        if ($projectIds === []) {
            return [];
        }
        $query = DB::table($table)->whereNotNull($column);
        if ($table === 'projects') {
            $query->whereIn('id', $projectIds);
        } elseif (Schema::hasColumn($table, 'project_id')) {
            $query->whereIn('project_id', $projectIds);
        } elseif ($table === 'project_task_design_deliverables') {
            $query->whereIn('project_task_id', DB::table('project_tasks')->whereIn('project_id', $projectIds)->select('id'));
        } else {
            return [];
        }

        return $query->distinct()->pluck($column)->all();
    }

    private function candidateReferenceIds(string $table, string $column, int $userId, array $projectIds): array
    {
        $query = DB::table($table)->where($column, $userId);
        if ($table === 'projects') {
            $query->whereIn('id', $projectIds);
        } elseif (Schema::hasColumn($table, 'project_id')) {
            $query->whereIn('project_id', $projectIds);
        } elseif ($table === 'project_task_design_deliverables') {
            $query->whereIn('project_task_id', DB::table('project_tasks')->whereIn('project_id', $projectIds)->select('id'));
        } else {
            return [];
        }

        return $query->pluck('id')->map(fn ($id) => (int) $id)->all();
    }

    private function roleInventory(array $candidateUsers): array
    {
        if (! Schema::hasTable('roles')) {
            return [];
        }
        $candidateIds = array_column($candidateUsers, 'id');

        return DB::table('roles as r')->leftJoin('model_has_roles as m', 'm.role_id', '=', 'r.id')->select('r.id', 'r.name', 'r.guard_name', DB::raw('COUNT(m.model_id) as usages'))->groupBy('r.id', 'r.name', 'r.guard_name')->orderBy('r.id')->get()->map(function ($role) use ($candidateIds) {
            $userIds = DB::table('model_has_roles')->where('role_id', $role->id)->whereIn('model_type', [User::class, 'App\\Models\\User'])->pluck('model_id')->map(fn ($id) => (int) $id)->all();

            return ['id' => (int) $role->id, 'name' => $role->name ?: 'unknown_role', 'guard' => $role->guard_name, 'usages' => (int) $role->usages, 'user_ids' => $userIds, 'candidate_test_user_only' => $userIds !== [] && array_diff($userIds, $candidateIds) === [], 'candidate' => false, 'reason' => 'roles require manual review and are never automatically deleted'];
        })->all();
    }

    private function userRoles(int $userId): array
    {
        if (! Schema::hasTable('model_has_roles')) {
            return [];
        }

        return DB::table('model_has_roles as m')->leftJoin('roles as r', 'r.id', '=', 'm.role_id')->where('m.model_id', $userId)->whereIn('m.model_type', [User::class, 'App\\Models\\User'])->select('m.role_id', 'r.name', 'r.guard_name')->orderBy('m.role_id')->get()->map(fn ($r) => ['id' => (int) $r->role_id, 'name' => $r->name ?: 'unknown_role', 'guard' => $r->guard_name])->all();
    }

    private function fileInventory(array $projectIds, array $relations): array
    {
        $files = [];
        foreach ($relations as $relation) {
            foreach ($relation['file_fields'] as $column) {
                $table = $relation['table'];
                $query = DB::table($table)->whereNotNull($column)->where($column, '!=', '');
                if ($relation['relation_type'] === 'global_unlinked') {
                } elseif (Schema::hasColumn($table, 'project_id')) {
                    $query->whereIn('project_id', $projectIds);
                } elseif ($table === 'project_task_design_deliverables') {
                    $query->whereIn('project_task_id', DB::table('project_tasks')->whereIn('project_id', $projectIds)->select('id'));
                } else {
                    continue;
                }
                foreach ($query->get() as $row) {
                    $projectId = $row->project_id ?? ($table === 'project_task_design_deliverables' ? DB::table('project_tasks')->where('id', $row->project_task_id)->value('project_id') : null);
                    $path = ltrim(str_replace('\\', '/', (string) $row->{$column}), '/');
                    if ($path === '' || str_contains($path, '..') || preg_match('/^[A-Za-z]:/', $path)) {
                        continue;
                    }
                    $localExists = Storage::disk('local')->exists($path);
                    $publicExists = Storage::disk('public')->exists($path);
                    $disk = $localExists ? 'local' : ($publicExists ? 'public' : 'unknown');
                    $exists = $localExists || $publicExists;
                    $samePathUses = DB::table($table)->where($column, $row->{$column})->count();
                    $files[] = ['table' => $table, 'record_id' => (int) $row->id, 'resource_id' => (int) ($row->project_task_id ?? $row->id), 'project_id' => $projectId ? (int) $projectId : null, 'classification' => $relation['relation_type'] === 'global_unlinked' ? 'manual_global_preserved' : 'candidate_cleanup_plan', 'field' => $column, 'disk' => $disk, 'relative_path' => $path, 'exists' => $exists, 'private' => $localExists, 'legacy_public' => $publicExists, 'exclusive' => $samePathUses === 1];
                }
            }
        }

        return $files;
    }

    private function countsForProject(array $relations, int $projectId): array
    {
        $counts = [];
        foreach ($relations as $relation) {
            $counts[$relation['table'].'.'.$relation['relation']] = $relation['per_project'][(string) $projectId] ?? 0;
        }
        ksort($counts);

        return $counts;
    }

    private function normalizeCounts($counts): array
    {
        $result = [];
        foreach ($counts as $id => $count) {
            $result[(string) $id] = (int) $count;
        }
        ksort($result, SORT_NUMERIC);

        return $result;
    }

    private function modelForTable(string $table): ?string
    {
        $name = str_replace(' ', '', ucwords(str_replace('_', ' ', rtrim($table, 's'))));
        $class = 'App\\Models\\'.$name;

        return class_exists($class) ? $class : null;
    }

    private function hasOnlyAllowlistedCandidates(array $candidates): bool
    {
        if (array_keys($candidates) !== ['projects', 'clients', 'assignments']) {
            return false;
        }
        foreach ([['projects', self::PROJECT_PREFIXES], ['clients', self::CLIENT_PREFIXES], ['assignments', self::ASSIGNMENT_PREFIXES]] as [$type, $prefixes]) {
            foreach ($candidates[$type] as $candidate) {
                if (! isset($candidate['id'], $candidate['name']) || ! is_int($candidate['id'])) {
                    return false;
                }
                try {
                    $this->matchingPrefix($candidate['name'], $prefixes);
                } catch (RuntimeException) {
                    return false;
                }
            }
        }

        return true;
    }

    private function matchingRows(string $table, string $column, array $prefixes, array $columns): array
    {
        return DB::table($table)->select($columns)->where(function ($query) use ($column, $prefixes): void {
            foreach ($prefixes as $prefix) {
                $query->orWhere($column, 'like', $this->escapeLike($prefix).'%');
            }
        })->orderBy('id')->get()->all();
    }

    private function matchingPrefix(string $value, array $prefixes): string
    {
        foreach ($prefixes as $prefix) {
            if (str_starts_with($value, $prefix)) {
                return $prefix;
            }
        }
        throw new RuntimeException('Candidate has no configured prefix.');
    }

    private function escapeLike(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value);
    }

    private function checksum(array $manifest): string
    {
        return hash('sha256', $this->encode($manifest));
    }

    private function confirmationToken(string $checksum): string
    {
        return strtoupper(substr($checksum, 0, 8).'-'.substr($checksum, 8, 8));
    }

    private function encode(array $value): string
    {
        return json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    }
}
