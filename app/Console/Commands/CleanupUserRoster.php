<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class CleanupUserRoster extends Command
{
    private const OFFICIAL_EMAILS = [
        'joshua.raphael@avatech.test',
        'ahmad.arlisyah@avatech.test',
        'ferry.achmad@avatech.test',
        'irwan.kurniawan@avatech.test',
        'genta@avatech.test',
        'yuda.prayoga@avatech.test',
    ];

    private const OFFICIAL_ACCOUNTS = [
        'joshua.raphael@avatech.test' => ['name' => 'Joshua Raphael', 'role' => 'ceo_pm'],
        'ahmad.arlisyah@avatech.test' => ['name' => 'Ahmad Rafiadly Arlisyah', 'role' => 'sa_qa'],
        'ferry.achmad@avatech.test' => ['name' => 'Ferry Achmad', 'role' => 'fullstack_dev'],
        'irwan.kurniawan@avatech.test' => ['name' => 'Irwan Kurniawan', 'role' => 'fullstack_dev'],
        'genta@avatech.test' => ['name' => 'Genta', 'role' => 'fullstack_dev'],
        'yuda.prayoga@avatech.test' => ['name' => 'Yuda Prayoga', 'role' => 'uiux_designer'],
    ];

    private const KNOWN_ROLES = ['ceo_pm', 'sa_qa', 'uiux_designer', 'ui_ux', 'fullstack_dev', 'admin', 'super_admin', 'developer'];

    private const OPERATIONAL_ROLES = ['sa_qa', 'uiux_designer', 'ui_ux', 'fullstack_dev'];

    private const DETACHABLE = [
        'projects.lead_user_id',
        'project_blockers.assigned_to_user_id',
        'project_tasks.assigned_to',
        'team_assignments.user_id',
        'team_workloads.user_id',
    ];

    private const OWNED = [
        'user_notification_preferences.user_id',
        'user_integration_states.user_id',
        'user_recovery_codes.user_id',
        'account_deletion_requests.user_id',
        'user_security_preferences.user_id',
    ];

    private const PIVOTS = [
        'sessions.user_id',
        'password_reset_tokens.email',
        'model_has_roles.model_id',
        'model_has_permissions.model_id',
    ];

    private const HISTORICAL = [
        'audit_logs.user_id',
        'ai_request_logs.user_id',
        'project_moms.created_by',
        'project_qc_tests.created_by',
        'project_requirement_inbox_items.created_by',
        'project_task_design_deliverables.created_by',
        'project_task_dependencies.created_by',
        'project_task_dependencies.created_by_user_id',
        'project_blockers.reported_by_user_id',
        'project_change_requests.approved_by_user_id',
        'project_change_requests.requested_by_user_id',
        'project_client_reviews.created_by_user_id',
        'project_handover_packs.finalized_by_user_id',
        'project_handover_packs.generated_by_user_id',
        'project_signoffs.approved_by_user_id',
        'project_signoffs.created_by_user_id',
        'project_uat_items.tested_by_user_id',
    ];

    protected $signature = 'maintenance:cleanup-user-roster
        {--dry-run : Inventory and write a review manifest}
        {--execute : Apply the exact reviewed manifest}
        {--manifest= : Reviewed manifest path}
        {--confirmation-token= : Token printed by dry-run}
        {--database-confirmation= : Exact local database name}
        {--preserve-email=* : Official email repeated once per address}';

    protected $description = 'Inventory and conservatively clean the user roster';

    public function handle(): int
    {
        if ($this->option('dry-run') && $this->option('execute')) {
            $this->error('--dry-run and --execute are mutually exclusive.');

            return self::FAILURE;
        }

        return $this->option('execute') ? $this->executeCleanup() : $this->dryRun();
    }

    public static function assertSafeDatabase(bool $execute, ?string $confirmation = null): void
    {
        $connection = config('database.default');
        $database = DB::connection()->getDatabaseName();
        $testing = app()->environment('testing') && $connection === 'mysql' && $database === 'avatech_smart_pmis_testing';
        $local = app()->environment('local') && $connection === 'mysql' && $database === 'avatech_smart_pmis';
        if (! $testing && ! $local) {
            throw new RuntimeException('Requires local/avatech_smart_pmis or testing/avatech_smart_pmis_testing.');
        }
        if ($execute && app()->environment('local') && $confirmation !== 'avatech_smart_pmis') {
            throw new RuntimeException('Local execution requires --database-confirmation=avatech_smart_pmis.');
        }
    }

    public static function assertOfficialPreserves(array $emails): void
    {
        $normalized = array_map('strtolower', $emails);
        sort($normalized);
        $official = self::OFFICIAL_EMAILS;
        sort($official);
        if ($normalized !== $official) {
            throw new RuntimeException('Execution requires each of the six official emails exactly once via --preserve-email.');
        }
    }

    private function dryRun(): int
    {
        self::assertSafeDatabase(false);
        $manifest = $this->inventory();
        $manifest['checksum'] = $this->checksum($manifest);
        $manifest['confirmation_token'] = $this->token($manifest['checksum']);
        $path = storage_path('app/cleanup-user-roster-'.$manifest['checksum'].'.json');
        File::put($path, $this->encode($manifest));
        $this->line('Manifest: '.$path);
        $this->line('Checksum: '.$manifest['checksum']);
        $this->line('Confirmation token: '.$manifest['confirmation_token']);
        $this->line('Users: '.$manifest['stats']['total'].'; classifications: '.json_encode($manifest['stats']['classifications']));
        $this->warn('Dry run only. Nothing changed. Review every classification, warning, reference, and detachment.');

        return self::SUCCESS;
    }

    private function executeCleanup(): int
    {
        self::assertSafeDatabase(true, $this->option('database-confirmation'));
        self::assertOfficialPreserves($this->option('preserve-email') ?: []);
        $path = (string) $this->option('manifest');
        $token = (string) $this->option('confirmation-token');
        if ($path === '' || $token === '' || ! File::isFile($path)) {
            $this->error('Execution requires --manifest and --confirmation-token from a reviewed dry run.');

            return self::FAILURE;
        }

        $manifest = json_decode(File::get($path), true, flags: JSON_THROW_ON_ERROR);
        $unsigned = $manifest;
        unset($unsigned['checksum'], $unsigned['confirmation_token']);
        $current = $this->inventory();
        $checksum = (string) ($manifest['checksum'] ?? '');
        $valid = $checksum !== ''
            && hash_equals($checksum, $this->checksum($unsigned))
            && hash_equals((string) ($manifest['confirmation_token'] ?? ''), $this->token($checksum))
            && hash_equals((string) ($manifest['confirmation_token'] ?? ''), $token)
            && $unsigned === $current
            && ($manifest['database'] ?? null) === DB::connection()->getDatabaseName()
            && ! self::hasExecutionBlockers($manifest);
        if (! $valid) {
            $this->error('Manifest is stale, altered, for another database, or has execution warnings. Run a new dry run.');

            return self::FAILURE;
        }

        if (($manifest['format_version'] ?? null) !== 4) {
            $this->error('Only V4 manifests can be executed. Run a new dry run.');

            return self::FAILURE;
        }
        $deleteIds = array_map('intval', $manifest['delete_user_ids']);
        $archiveIds = array_map('intval', $manifest['archive_user_ids']);
        $actionIds = array_merge($deleteIds, $archiveIds);
        if (array_intersect($actionIds, $manifest['official_user_ids']) !== [] || DB::table('users')->whereIn('id', $actionIds)->whereIn(DB::raw('LOWER(email)'), self::OFFICIAL_EMAILS)->exists()) {
            $this->error('Official user entered the archive or deletion set.');

            return self::FAILURE;
        }

        $this->applyManifest($manifest);

        $this->info('Archived '.count($archiveIds).' and deleted '.count($deleteIds).' exact manifest user(s) in one transaction; no physical files changed.');

        return self::SUCCESS;
    }

    public static function hasExecutionBlockers(array $manifest): bool
    {
        $integerList = fn ($value) => is_array($value) && array_is_list($value) && count($value) === count(array_unique($value, SORT_REGULAR)) && collect($value)->every(fn ($id) => is_int($id));
        $warnings = $manifest['warnings'] ?? null;
        $blockedIds = $manifest['blocked_user_ids'] ?? null;
        $verification = $manifest['official_account_verification'] ?? null;
        $officialEmails = $manifest['official_emails'] ?? null;
        $officialIds = $manifest['official_user_ids'] ?? null;
        $deleteIds = $manifest['delete_user_ids'] ?? null;
        $archiveIds = $manifest['archive_user_ids'] ?? null;
        $users = $manifest['users'] ?? null;
        $references = $manifest['reference_schema'] ?? null;
        $aggregate = $manifest['reference_aggregate'] ?? null;
        $expected = $manifest['expected'] ?? null;
        $current = $manifest['current'] ?? null;
        if (($manifest['format_version'] ?? null) !== 4 || $warnings !== [] || $blockedIds !== []
            || ! is_array($verification) || count($verification) !== 6 || ! collect($verification)->every(fn ($row) => is_array($row) && ($row['valid'] ?? null) === true)
            || ! is_array($officialEmails) || $officialEmails !== self::OFFICIAL_EMAILS || ! $integerList($officialIds) || count($officialIds) !== 6
            || ! $integerList($deleteIds) || ! $integerList($archiveIds) || array_intersect($deleteIds, $archiveIds) !== []
            || ! is_array($users) || ! array_is_list($users) || ! is_array($references) || ! array_is_list($references) || ! is_array($aggregate)
            || ! is_array($expected) || ! is_array($current) || ($expected['active_count'] ?? null) !== 6
            || ($expected['proposed_final_team_count'] ?? null) !== 5 || ($expected['team_count_after'] ?? null) !== 5
            || ($expected['physical_count'] ?? null) !== ($current['physical_count'] ?? null) - count($deleteIds)
            || ($manifest['sentinels'] ?? null) !== ['project_count' => 22, 'client_count' => 19, 'required_project_ids' => [651, 652], 'valid' => true]
            || ! in_array($manifest['feasibility'] ?? null, ['SAFE_TO_REACH_EXACTLY_6', 'SAFE_TO_SHOW_6_ACTIVE_BUT_NOT_DELETE_ALL_HISTORY'], true)) {
            return true;
        }
        $manifestIds = [];
        $officialUserEmails = [];
        foreach ($users as $user) {
            if (! is_array($user) || ! is_int($user['id'] ?? null) || ! is_bool($user['official'] ?? null) || ! is_string($user['email'] ?? null) || isset($manifestIds[$user['id']])) {
                return true;
            }
            $manifestIds[$user['id']] = true;
            $actions = (int) in_array($user['id'], $deleteIds, true) + (int) in_array($user['id'], $archiveIds, true);
            $officialByEmail = in_array(strtolower($user['email']), self::OFFICIAL_EMAILS, true);
            if ($user['official'] !== $officialByEmail || ($user['official'] && $actions !== 0) || (! $user['official'] && $actions !== 1)) {
                return true;
            }
            if ($user['official']) {
                $officialUserEmails[$user['id']] = strtolower($user['email']);
            }
        }
        $mappedOfficialEmails = array_values(array_map(fn ($id) => $officialUserEmails[$id] ?? null, $officialIds));
        $expectedOfficialEmails = self::OFFICIAL_EMAILS;
        sort($mappedOfficialEmails);
        sort($expectedOfficialEmails);
        if (array_diff(array_merge($deleteIds, $archiveIds, $officialIds), array_keys($manifestIds)) !== []
            || array_intersect(array_merge($deleteIds, $archiveIds), $officialIds) !== []
            || $mappedOfficialEmails !== $expectedOfficialEmails) {
            return true;
        }
        foreach ($references as $reference) {
            if (! is_array($reference) || ! is_string($reference['table'] ?? null) || ! is_string($reference['column'] ?? null)) {
                return true;
            }
            $key = $reference['table'].'.'.$reference['column'];
            if (($reference['accounted'] ?? null) !== true && (! is_array($aggregate[$key] ?? null) || ($aggregate[$key]['count_nonofficial'] ?? 0) !== 0)) {
                return true;
            }
        }

        return false;
    }

    protected function applyManifest(array $manifest): void
    {
        DB::transaction(function () use ($manifest): void {
            foreach ($manifest['detachments'] as $detachment) {
                $this->applyExactPlan($detachment);
            }
            foreach (array_merge($manifest['role_pivots_to_delete'], $manifest['authentication_records_to_delete']) as $plan) {
                $this->applyExactPlan($plan);
            }
            $archiveIds = array_map('intval', $manifest['archive_user_ids']);
            $archived = $archiveIds === [] ? 0 : DB::table('users')->whereIn('id', $archiveIds)->whereNull('archived_at')->update(['archived_at' => now(), 'updated_at' => now(), 'remember_token' => null]);
            $expectedArchives = collect($manifest['users'])->whereIn('id', $archiveIds)->whereNull('archived_at')->count();
            if ($archived !== $expectedArchives) {
                throw new RuntimeException('Exact user archive count mismatch.');
            }
            $deleteIds = array_map('intval', $manifest['delete_user_ids']);
            $deleted = $deleteIds === [] ? 0 : DB::table('users')->whereIn('id', $deleteIds)->delete();
            if ($deleted !== count($deleteIds)) {
                throw new RuntimeException('Exact user deletion count mismatch.');
            }
            $this->assertPostconditions($manifest);
        });
    }

    protected function assertPostconditions(array $manifest): void
    {
        $deleteIds = $manifest['delete_user_ids'];
        $archiveIds = $manifest['archive_user_ids'];
        $actionIds = array_merge($deleteIds, $archiveIds);
        $officialIds = $manifest['official_user_ids'];
        $officialEmails = $manifest['official_emails'];
        $officialUsers = collect($manifest['users'])->whereIn('id', $officialIds)->keyBy('id');
        $officialPairsExist = collect($officialIds)->every(function ($id) use ($officialUsers): bool {
            $email = strtolower((string) ($officialUsers->get($id)['email'] ?? ''));

            return $email !== '' && DB::table('users')->where('id', $id)->whereRaw('LOWER(email) = ?', [$email])->whereNull('archived_at')->exists();
        });
        $invalid = DB::table('users')->count() !== $manifest['expected']['physical_count']
            || DB::table('users')->whereNull('archived_at')->count() !== 6
            || count($this->teamUserIds()) !== 5
            || count($officialEmails) !== 6 || ! $officialPairsExist
            || ($deleteIds !== [] && DB::table('users')->whereIn('id', $deleteIds)->exists())
            || ($archiveIds !== [] && DB::table('users')->whereIn('id', $archiveIds)->where(fn ($query) => $query->whereNull('archived_at')->orWhereNotNull('remember_token'))->exists())
            || (Schema::hasTable('sessions') && $actionIds !== [] && DB::table('sessions')->whereIn('user_id', $actionIds)->exists())
            || (Schema::hasTable('password_reset_tokens') && $actionIds !== [] && DB::table('password_reset_tokens')->whereIn(DB::raw('LOWER(email)'), collect($manifest['users'])->whereIn('id', $actionIds)->pluck('email')->map('strtolower')->all())->exists())
            || DB::table('projects')->count() !== 22
            || DB::table('clients')->count() !== 19
            || DB::table('projects')->whereIn('id', [651, 652])->count() !== 2;
        if ($invalid) {
            throw new RuntimeException('Cleanup postconditions failed.');
        }
    }

    private function applyExactPlan(array $plan): void
    {
        $query = DB::table($plan['table']);
        if ($plan['record_ids'] !== []) {
            $query->whereIn('id', $plan['record_ids'])->where($plan['column'], $plan['old_user_id']);
        } else {
            foreach ($plan['exact_key'] as $column => $value) {
                $query->where($column, $value);
            }
        }
        $affected = $plan['action'] === 'delete' ? $query->delete() : $query->update([$plan['column'] => $plan['planned_value']]);
        if ($affected !== $plan['expected_count']) {
            throw new RuntimeException('Stale exact plan for '.$plan['table'].'.'.$plan['column'].'.');
        }
    }

    private function inventory(): array
    {
        $columns = $this->columns();
        $foreignKeys = $this->foreignKeys();
        $references = $this->referenceDefinitions($columns, $foreignKeys);
        $protected = $this->protectedUserIds();
        $users = [];
        foreach (DB::table('users')->orderBy('id')->get() as $user) {
            $roles = $this->roles((int) $user->id);
            $refs = $this->references((int) $user->id, (string) $user->email, $references);
            $disposable = $this->disposableEmail((string) $user->email);
            [$classification, $detachments, $reasons] = $this->classify((int) $user->id, strtolower((string) $user->email), $roles, $disposable, $refs, $protected);
            $users[] = [
                'id' => (int) $user->id,
                'name' => $user->name,
                'email' => strtolower((string) $user->email),
                'official' => in_array(strtolower((string) $user->email), self::OFFICIAL_EMAILS, true),
                'archived_at' => $user->archived_at,
                'roles' => $roles,
                'classification' => $classification,
                'disposable_evidence' => $disposable,
                'reasons' => $reasons,
                'references' => $refs,
                'detachments' => $detachments,
            ];
        }

        $collection = collect($users);
        [$officialVerification, $officialWarnings] = $this->officialVerification($collection);
        $aggregate = [];
        foreach ($references as $definition) {
            $key = $definition['table'].'.'.$definition['column'];
            $aggregate[$key] = ['nullable' => $definition['nullable'], 'delete_rule' => $definition['delete_rule'], 'count_nonofficial' => $collection->where('official', false)->sum(fn ($user) => collect($user['references'])->firstWhere('key', $key)['count'] ?? 0)];
        }
        ksort($aggregate);
        $warnings = $officialWarnings;
        if ($collection->count() !== 369 && app()->environment('local')) {
            $warnings[] = 'Expected local roster count 369; found '.$collection->count().'.';
        }
        foreach ($references as $definition) {
            if (! $definition['accounted'] && ($aggregate[$definition['table'].'.'.$definition['column']]['count_nonofficial'] ?? 0) > 0) {
                $warnings[] = 'Unknown user-like relation has nonofficial references: '.$definition['table'].'.'.$definition['column'].'.';
            }
        }
        $deleteIds = $collection->whereIn('classification', ['SAFE_DELETE', 'SAFE_DETACH_THEN_DELETE'])->pluck('id')->all();
        $archiveIds = $collection->where('official', false)->where('classification', 'ARCHIVE_ONLY')->pluck('id')->all();
        $blockedIds = $collection->where('official', false)->where('classification', 'BLOCKED_MANUAL_REVIEW')->pluck('id')->all();
        $teamBefore = $this->teamUserIds();
        $nonofficial = $collection->where('official', false);
        $activeDeleteIds = $nonofficial->whereNull('archived_at')->whereIn('classification', ['SAFE_DELETE', 'SAFE_DETACH_THEN_DELETE'])->pluck('id')->all();
        $activeArchiveIds = $nonofficial->whereNull('archived_at')->where('classification', 'ARCHIVE_ONLY')->pluck('id')->all();
        $expectedPhysical = $collection->count() - count($deleteIds);
        $expectedActive = $collection->whereNull('archived_at')->count() - count($activeDeleteIds) - count($activeArchiveIds);
        $expectedTeam = count(array_diff($teamBefore, array_merge($deleteIds, $archiveIds)));
        $feasibility = $warnings === [] && $blockedIds === []
            ? ($archiveIds === [] && $expectedPhysical === 6 ? 'SAFE_TO_REACH_EXACTLY_6' : ($expectedActive === 6 ? 'SAFE_TO_SHOW_6_ACTIVE_BUT_NOT_DELETE_ALL_HISTORY' : 'NOT_SAFE_WITHOUT_SCHEMA_OR_HISTORY_CHANGES'))
            : 'NOT_SAFE_WITHOUT_SCHEMA_OR_HISTORY_CHANGES';

        $officialIds = $collection->where('official', true)->pluck('id')->all();
        $actionPlans = $collection->whereIn('id', array_merge($deleteIds, $archiveIds))->flatMap(fn ($user) => $user['detachments']);
        $detachments = $actionPlans->reject(fn ($plan) => in_array($plan['table'], ['sessions', 'password_reset_tokens', 'model_has_roles', 'model_has_permissions'], true))->values()->all();
        $rolePivots = $actionPlans->whereIn('table', ['model_has_roles', 'model_has_permissions'])->values()->all();
        $authenticationRecords = $actionPlans->whereIn('table', ['sessions', 'password_reset_tokens'])->values()->all();

        return [
            'format_version' => 4,
            'database' => DB::connection()->getDatabaseName(),
            'sentinels' => [
                'project_count' => DB::table('projects')->count(),
                'client_count' => DB::table('clients')->count(),
                'required_project_ids' => [651, 652],
                'valid' => DB::table('projects')->count() === 22 && DB::table('clients')->count() === 19 && DB::table('projects')->whereIn('id', [651, 652])->count() === 2,
            ],
            'official_emails' => self::OFFICIAL_EMAILS,
            'official_user_ids' => $officialIds,
            'protected_user_ids' => $protected,
            'delete_user_ids' => $deleteIds,
            'archive_user_ids' => $archiveIds,
            'detachments' => $detachments,
            'role_pivots_to_delete' => $rolePivots,
            'authentication_records_to_delete' => $authenticationRecords,
            'preserved_user_ids' => array_values(array_unique(array_merge($officialIds, $archiveIds))),
            'blocked_user_ids' => $blockedIds,
            'stats' => [
                'total' => $collection->count(),
                'official' => $collection->where('official', true)->count(),
                'active' => $collection->whereNull('archived_at')->count(),
                'archived' => $collection->whereNotNull('archived_at')->count(),
                'domains' => $collection->countBy(fn ($user) => substr(strrchr($user['email'], '@') ?: '', 1))->sortKeys()->all(),
                'roles' => $collection->flatMap(fn ($user) => array_column($user['roles'], 'name'))->countBy()->sortKeys()->all(),
                'duplicate_names' => $collection->groupBy(fn ($user) => strtolower((string) $user['name']))->filter(fn ($group) => $group->count() > 1)->map->pluck('id')->all(),
                'duplicate_emails' => $collection->groupBy('email')->filter(fn ($group) => $group->count() > 1)->map->pluck('id')->all(),
                'no_role' => $collection->filter(fn ($user) => $user['roles'] === [])->pluck('id')->all(),
                'unknown_role' => $collection->filter(fn ($user) => collect($user['roles'])->contains(fn ($role) => ! in_array($role['name'], self::KNOWN_ROLES, true)))->pluck('id')->all(),
                'multi_role' => $collection->filter(fn ($user) => count($user['roles']) > 1)->pluck('id')->all(),
                'patterns' => [
                    '@avatech.demo' => $collection->filter(fn ($user) => str_ends_with($user['email'], '@avatech.demo'))->pluck('id')->all(),
                    'example' => $collection->filter(fn ($user) => preg_match('/@example\.(com|net|org)$/', $user['email']))->pluck('id')->all(),
                    'faker' => $collection->filter(fn ($user) => preg_match('/@[^@]*faker[^@]*$/i', $user['email']))->pluck('id')->all(),
                    'test' => $collection->filter(fn ($user) => preg_match('/@[^@]*test[^@]*$/i', $user['email']))->pluck('id')->all(),
                ],
                'classifications' => $collection->where('official', false)->countBy('classification')->sortKeys()->all(),
            ],
            'official_account_verification' => $officialVerification,
            'reference_schema' => $references,
            'reference_aggregate' => $aggregate,
            'users' => $users,
            'current' => [
                'physical_count' => $collection->count(),
                'active_count' => $collection->whereNull('archived_at')->count(),
                'team_count' => count($teamBefore),
            ],
            'expected' => [
                'physical_count' => $expectedPhysical,
                'active_count' => $expectedActive,
                'proposed_final_physical_count' => $expectedPhysical,
                'proposed_final_active_count' => $expectedActive,
                'proposed_final_team_count' => $expectedTeam,
                'delete_user_ids' => $deleteIds,
                'archive_candidate_ids' => $archiveIds,
                'blocked_candidate_ids' => $blockedIds,
                'delete_count' => count($deleteIds),
                'team_count_before' => count($teamBefore),
                'team_count_after' => $expectedTeam,
            ],
            'feasibility' => $feasibility,
            'warnings' => $warnings,
        ];
    }

    private function classify(int $id, string $email, array $roles, ?string $evidence, array $refs, array $protected): array
    {
        if (in_array($email, self::OFFICIAL_EMAILS, true)) {
            return ['BLOCKED_MANUAL_REVIEW', [], ['official account']];
        }
        $meaningful = array_values(array_filter($refs, fn ($ref) => $ref['meaningful'] && $ref['count'] > 0));
        if (in_array($id, $protected, true)) {
            return ['ARCHIVE_ONLY', $this->safePivotDetachments($refs), ['linked to exact project test 1/test 2']];
        }
        if (collect($meaningful)->contains(fn ($ref) => $ref['historical'])) {
            return ['ARCHIVE_ONLY', $this->safePivotDetachments($refs), ['historical, audit, creator, or AI reference exists']];
        }
        if ($evidence === null) {
            return ['BLOCKED_MANUAL_REVIEW', [], ['no exact disposable-email evidence']];
        }
        if ($meaningful === []) {
            return ['SAFE_DELETE', $this->safePivotDetachments($refs), ['disposable email and zero meaningful references']];
        }
        if (collect($meaningful)->every(fn ($ref) => $ref['detachable'])) {
            return ['SAFE_DETACH_THEN_DELETE', $this->detachments($refs), ['only explicitly safe nullable membership, lead, PIC, workload, or pivots']];
        }

        return ['BLOCKED_MANUAL_REVIEW', [], ['unaccounted or non-detachable user reference exists']];
    }

    private function detachments(array $refs): array
    {
        return collect($refs)->filter(fn ($ref) => $ref['count'] > 0 && ($ref['detachable'] || $ref['safe_pivot']))->map(fn ($ref) => [
            'table' => $ref['table'],
            'record_ids' => $ref['record_ids'],
            'column' => $ref['column'],
            'old_user_id' => $ref['lookup_value'],
            'planned_value' => $ref['nullable'] ? null : 'row deleted',
            'action' => $ref['nullable'] ? 'set_null' : 'delete',
            'reason' => $ref['safe_pivot'] ? 'exact account-owned or authentication row' : 'explicitly detachable relation',
            'resources' => $ref['resources'],
            'projects' => $ref['projects'],
            'exact_key' => $ref['exact_key'],
            'expected_count' => $ref['count'],
        ])->values()->all();
    }

    private function safePivotDetachments(array $refs): array
    {
        return $this->detachments(array_values(array_filter($refs, fn ($ref) => $ref['safe_pivot'])));
    }

    private function references(int $userId, string $email, array $definitions): array
    {
        $result = [];
        foreach ($definitions as $definition) {
            $value = $definition['table'] === 'password_reset_tokens' ? $email : $userId;
            if (! $definition['available']) {
                $result[] = $definition + ['key' => $definition['table'].'.'.$definition['column'], 'count' => 0, 'record_ids' => [], 'lookup_value' => $value, 'exact_key' => [], 'resources' => [], 'projects' => []];
                continue;
            }
            $query = DB::table($definition['table'])->where($definition['column'], $value);
            if ($definition['morph_type']) {
                $query->whereIn($definition['morph_type'], [User::class, 'App\\Models\\User', 'User', 'user']);
            }
            $rows = $query->get();
            $ids = $definition['has_id'] ? $rows->pluck('id')->map(fn ($id) => (int) $id)->all() : [];
            $exactKey = $definition['has_id'] || $rows->isEmpty() ? [] : [$definition['column'] => $value];
            if ($definition['morph_type']) {
                $exactKey[$definition['morph_type']] = $rows->first()?->{$definition['morph_type']};
            }
            $projects = $rows->pluck('project_id')->filter()->map(fn ($id) => (int) $id)->unique()->values()->all();
            $result[] = $definition + ['key' => $definition['table'].'.'.$definition['column'], 'count' => $rows->count(), 'record_ids' => $ids, 'lookup_value' => $value, 'exact_key' => $exactKey, 'resources' => $ids, 'projects' => $projects];
        }

        return $result;
    }

    private function referenceDefinitions($columns, $foreignKeys): array
    {
        $definitions = [];
        foreach ($columns as $table => $tableColumns) {
            $names = $tableColumns->pluck('COLUMN_NAME')->all();
            foreach ($tableColumns as $column) {
                $name = $column->COLUMN_NAME;
                $fk = $foreignKeys->get($table.'.'.$name);
                $key = $table.'.'.$name;
                $userLike = $fk || in_array($key, array_merge(self::DETACHABLE, self::OWNED, self::PIVOTS, self::HISTORICAL), true) || ($table === 'audit_logs' && $name === 'auditable_id');
                if (! $userLike || $table === 'users') {
                    continue;
                }
                $key = $table.'.'.$name;
                $morphType = null;
                if (str_ends_with($name, '_id') && in_array(substr($name, 0, -3).'_type', $names, true)) {
                    $morphType = substr($name, 0, -3).'_type';
                }
                $safePivot = in_array($key, array_merge(self::OWNED, self::PIVOTS), true);
                $historical = in_array($key, self::HISTORICAL, true) || ($table === 'audit_logs' && $name === 'auditable_id' && $morphType !== null);
                $definitions[$key] = [
                    'table' => $table,
                    'column' => $name,
                    'has_id' => in_array('id', $names, true),
                    'nullable' => $column->IS_NULLABLE === 'YES',
                    'foreign_key' => $fk?->CONSTRAINT_NAME,
                    'delete_rule' => $fk?->DELETE_RULE,
                    'morph_type' => $morphType,
                    'explicit' => in_array($key, ['sessions.user_id', 'password_reset_tokens.email', 'model_has_roles.model_id', 'model_has_permissions.model_id', 'audit_logs.user_id', 'project_task_dependencies.created_by_user_id'], true),
                    'accounted' => in_array($key, array_merge(self::DETACHABLE, self::OWNED, self::PIVOTS, self::HISTORICAL), true) || $historical,
                    'historical' => $historical,
                    'safe_pivot' => $safePivot,
                    'detachable' => in_array($key, self::DETACHABLE, true),
                    'meaningful' => ! in_array($key, ['sessions.user_id'], true) && ! $safePivot,
                ];
            }
        }
        foreach (array_merge(self::DETACHABLE, self::HISTORICAL) as $key) {
            if (isset($definitions[$key])) {
                $definitions[$key]['available'] = true;
                continue;
            }
            [$table, $column] = explode('.', $key, 2);
            $definitions[$key] = ['table' => $table, 'column' => $column, 'has_id' => false, 'nullable' => false, 'foreign_key' => null, 'delete_rule' => null, 'morph_type' => null, 'explicit' => true, 'accounted' => true, 'historical' => in_array($key, self::HISTORICAL, true), 'safe_pivot' => false, 'detachable' => in_array($key, self::DETACHABLE, true), 'meaningful' => true, 'available' => false];
        }
        foreach ($definitions as &$definition) {
            $definition['available'] ??= true;
        }
        unset($definition);
        if (isset($columns['password_reset_tokens'])) {
            $definitions['password_reset_tokens.email'] = ['table' => 'password_reset_tokens', 'column' => 'email', 'has_id' => false, 'nullable' => false, 'foreign_key' => null, 'delete_rule' => null, 'morph_type' => null, 'explicit' => true, 'accounted' => true, 'historical' => false, 'safe_pivot' => true, 'detachable' => false, 'meaningful' => false, 'available' => true];
        }
        ksort($definitions);

        return array_values($definitions);
    }

    private function columns()
    {
        return DB::table('information_schema.COLUMNS')->where('TABLE_SCHEMA', DB::connection()->getDatabaseName())->orderBy('TABLE_NAME')->orderBy('ORDINAL_POSITION')->get()->groupBy('TABLE_NAME');
    }

    private function foreignKeys()
    {
        return DB::table('information_schema.KEY_COLUMN_USAGE as k')->leftJoin('information_schema.REFERENTIAL_CONSTRAINTS as r', function ($join) {
            $join->on('r.CONSTRAINT_SCHEMA', '=', 'k.CONSTRAINT_SCHEMA')->on('r.CONSTRAINT_NAME', '=', 'k.CONSTRAINT_NAME')->on('r.TABLE_NAME', '=', 'k.TABLE_NAME');
        })->where('k.TABLE_SCHEMA', DB::connection()->getDatabaseName())->where('k.REFERENCED_TABLE_NAME', 'users')->select('k.TABLE_NAME', 'k.COLUMN_NAME', 'k.CONSTRAINT_NAME', 'r.DELETE_RULE')->get()->keyBy(fn ($fk) => $fk->TABLE_NAME.'.'.$fk->COLUMN_NAME);
    }

    private function protectedUserIds(): array
    {
        $ids = DB::table('users')->whereIn(DB::raw('LOWER(email)'), self::OFFICIAL_EMAILS)->pluck('id')->map(fn ($id) => (int) $id)->all();
        if (Schema::hasTable('projects')) {
            $projectIds = DB::table('projects')->whereIn('name', ['test 1', 'test 2'])->pluck('id');
            foreach (['projects' => 'lead_user_id', 'project_tasks' => 'assigned_to', 'team_assignments' => 'user_id'] as $table => $column) {
                if (Schema::hasTable($table) && Schema::hasColumn($table, $column)) {
                    $query = DB::table($table)->whereNotNull($column);
                    $table === 'projects' ? $query->whereIn('id', $projectIds) : $query->whereIn('project_id', $projectIds);
                    $ids = array_merge($ids, $query->pluck($column)->map(fn ($id) => (int) $id)->all());
                }
            }
        }
        $ids = array_values(array_unique($ids));
        sort($ids);

        return $ids;
    }

    private function officialVerification($users): array
    {
        $verification = [];
        $warnings = [];
        foreach (self::OFFICIAL_ACCOUNTS as $email => $expected) {
            $matches = $users->where('email', $email)->values();
            $account = $matches->first();
            $actualRoles = $account ? array_column($account['roles'], 'name') : [];
            $valid = $matches->count() === 1 && $account['name'] === $expected['name'] && $account['archived_at'] === null && in_array($expected['role'], $actualRoles, true);
            $verification[] = [
                'expected_name' => $expected['name'],
                'expected_email' => $email,
                'expected_role' => $expected['role'],
                'matched_ids' => $matches->pluck('id')->all(),
                'actual_name' => $account['name'] ?? null,
                'actual_email' => $account['email'] ?? null,
                'actual_roles' => $actualRoles,
                'archived_at' => $account['archived_at'] ?? null,
                'valid' => $valid,
            ];
            if (! $valid) {
                $warnings[] = 'Official account missing, duplicate, archived, wrong name, or missing expected role: '.$email.'.';
            }
        }

        return [$verification, $warnings];
    }

    private function teamUserIds(): array
    {
        return DB::table('users as u')->whereNull('u.archived_at')
            ->whereNotExists(fn ($query) => $query->selectRaw('1')->from('model_has_roles as m')->join('roles as r', 'r.id', '=', 'm.role_id')->whereColumn('m.model_id', 'u.id')->whereIn('m.model_type', [User::class, 'App\\Models\\User'])->where('r.name', 'ceo_pm'))
            ->whereExists(fn ($query) => $query->selectRaw('1')->from('model_has_roles as m')->join('roles as r', 'r.id', '=', 'm.role_id')->whereColumn('m.model_id', 'u.id')->whereIn('m.model_type', [User::class, 'App\\Models\\User'])->whereIn('r.name', self::OPERATIONAL_ROLES))
            ->orderBy('u.id')->pluck('u.id')->map(fn ($id) => (int) $id)->all();
    }

    private function roles(int $userId): array
    {
        if (! Schema::hasTable('model_has_roles')) {
            return [];
        }

        return DB::table('model_has_roles as m')->leftJoin('roles as r', 'r.id', '=', 'm.role_id')->where('m.model_id', $userId)->whereIn('m.model_type', [User::class, 'App\\Models\\User'])->orderBy('m.role_id')->get(['m.role_id', 'r.name'])->map(fn ($role) => ['id' => (int) $role->role_id, 'name' => $role->name])->all();
    }

    private function disposableEmail(string $email): ?string
    {
        $email = strtolower($email);
        if (str_ends_with($email, '@avatech.demo')) {
            return 'exact @avatech.demo domain';
        }
        if (preg_match('/@(example\.(com|net|org)|[^@]*(faker|test)[^@]*)$/i', $email)) {
            return 'example/Faker/test email pattern';
        }

        return null;
    }

    private function checksum(array $manifest): string
    {
        return hash('sha256', $this->encode($manifest));
    }

    private function token(string $checksum): string
    {
        return strtoupper(substr($checksum, 0, 8).'-'.substr($checksum, 8, 8));
    }

    private function encode(array $value): string
    {
        return json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    }
}
