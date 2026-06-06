@props(['title' => null])

@php
    $roleLabels = [
        'ceo_pm'        => 'CEO / PM',
        'sa_qa'         => 'SA / QA',
        'fullstack_dev' => 'Fullstack Developer',
        'uiux_designer' => 'UI/UX Designer',
    ];
    $user      = auth()->user();
    $role      = $user?->roles?->first()?->name;
    $roleLabel = $role ? ($roleLabels[$role] ?? $role) : null;
    $initials  = $user
        ? collect(preg_split('/\s+/', trim($user->name)))->take(2)->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->join('')
        : '';
    $pageTitle = $title ?? config('app.name', 'Smart-PMIS');

    $auditFilters = [
        ['id' => 'all',      'label' => 'Semua'],
        ['id' => 'proyek',   'label' => 'Proyek'],
        ['id' => 'klien',    'label' => 'Klien'],
        ['id' => 'tim',      'label' => 'Tim'],
        ['id' => 'settings', 'label' => 'Settings'],
        ['id' => 'login',    'label' => 'Login'],
    ];

    $auditCategoryPalette = [
        'proyek'   => ['pill' => 'bg-violet-100 text-violet-800',  'avatar' => 'bg-violet-100 text-violet-800'],
        'klien'    => ['pill' => 'bg-green-100 text-green-800',    'avatar' => 'bg-green-100 text-green-800'],
        'tim'      => ['pill' => 'bg-fuchsia-50 text-fuchsia-800', 'avatar' => 'bg-fuchsia-50 text-fuchsia-800'],
        'settings' => ['pill' => 'bg-orange-200 text-orange-800',  'avatar' => 'bg-orange-200 text-orange-800'],
        'login'    => ['pill' => 'bg-slate-100 text-slate-700',    'avatar' => 'bg-slate-100 text-slate-700'],
        'all'      => ['pill' => 'bg-violet-100 text-violet-800',  'avatar' => 'bg-violet-100 text-violet-800'],
    ];

    $auditEntries = [];
    $auditTodayCount = 0;
    $appTimeDiff = fn ($date, $fallback = 'baru saja') => \App\Support\AppTime::diff($date, $fallback);

    /*
     * Topbar audit/activity button is visible to every signed-in user, but the
     * scope of what they see differs:
     *  - admin-tier + CEO/PM → global "Audit Trail"
     *  - operational         → self-scoped "Activity Log"
     */
    $auditViewerAllowed   = (bool) $user;
    $isFullAuditViewer    = in_array($role, ['ceo_pm', 'admin', 'super_admin', 'developer'], true);
    $isOperationalViewer  = $auditViewerAllowed && ! $isFullAuditViewer;
    $auditButtonLabel     = $isOperationalViewer ? 'Activity Log' : 'Audit Trail';
    $auditModalTitle      = $isOperationalViewer ? 'Activity Log'  : 'Audit Trail';
    $auditModalSubtitle   = $isOperationalViewer ? 'Aktivitas terbaru dari akun Anda' : null;

    try {
        if ($auditViewerAllowed) {
            $recentLogsQuery = \App\Models\AuditLog::with('user')
                ->orderByDesc('created_at')
                ->orderByDesc('id');

            if ($isOperationalViewer) {
                $recentLogsQuery->where('user_id', $user->id);
            }

            $recentLogs = $recentLogsQuery->limit(20)->get();

            $todayQuery = \App\Models\AuditLog::where('created_at', '>=', \App\Support\AppTime::now()->startOfDay());
            if ($isOperationalViewer) {
                $todayQuery->where('user_id', $user->id);
            }
            $auditTodayCount = $todayQuery->count();
        } else {
            $recentLogs = collect();
        }

        foreach ($recentLogs as $log) {
            $filterKey = \App\Http\Controllers\AuditController::categoryForModule($log->module, $log->action);
            $tag = \App\Http\Controllers\AuditController::tagForLog($log->module, $log->action, $log->auditable_type, $log->description);
            $actorName = $log->user?->name ?? 'Sistem';
            $parts = array_values(array_filter(preg_split('/\s+/', trim($actorName)) ?: []));
            if ($parts === []) {
                $initialsLabel = '?';
            } elseif (count($parts) === 1) {
                $initialsLabel = mb_strtoupper(mb_substr($parts[0], 0, 2));
            } else {
                $initialsLabel = mb_strtoupper(mb_substr($parts[0], 0, 1) . mb_substr($parts[1], 0, 1));
            }
            $palette = $auditCategoryPalette[$filterKey] ?? $auditCategoryPalette['all'];

            /* Project context + safe deep link for clickable rows.
             * project_name is also the gate for showing the small context
             * line and for making the row clickable (when null we keep
             * the row static and link to /audit to avoid wrong /projects/{id}). */
            $entryProjectName = \App\Http\Controllers\AuditController::contextNameForLog($log);
            $entryDeepLink    = \App\Http\Controllers\AuditController::deepLinkForLog($log);
            $entryHasProjectLink = $entryProjectName !== null && (str_contains($entryDeepLink, '/projects/') || str_contains($entryDeepLink, '/clients'));

            $auditEntries[] = [
                'category'         => $filterKey,
                'category_label'   => $tag,
                'category_class'   => $palette['pill'],
                'initials'         => $initialsLabel,
                'avatar_class'     => $palette['avatar'],
                'text'             => $log->description ?: e($actorName) . ' melakukan ' . e($log->action),
                'time'             => $appTimeDiff($log->created_at),
                'user'             => $actorName,
                'module'           => $log->module,
                'project_name'     => $entryProjectName,
                'deep_link'        => $entryHasProjectLink ? $entryDeepLink : null,
            ];
        }
    } catch (\Throwable $e) {
        /* Audit table may not exist yet during initial deploy — fail soft. */
        $auditEntries = [];
        $auditTodayCount = 0;
    }

    $auditTotal = count($auditEntries);

    /* For operational viewers, hide chips that always read 0 — Klien / Settings /
       Login are unreachable from their UI. Keep "Semua" and only chips that have
       at least one entry in the current modal payload. CEO/PM keep all chips. */
    if ($isOperationalViewer) {
        $presentCategories = array_unique(array_column($auditEntries, 'category'));
        $auditFilters = array_values(array_filter(
            $auditFilters,
            fn ($f) => $f['id'] === 'all' || in_array($f['id'], $presentCategories, true),
        ));
    }

    /* ============== Notifications (DB-backed, role-aware) ==============
     * No dedicated notifications table — we derive notifications from
     * audit_logs. CEO/PM + admin-tier see the latest global events,
     * operational users only see events authored by themselves.
     * "Unread" = created within the last 24 hours. (Best-effort signal
     * without introducing a read-state table.) */
    $notifPaletteByModule = [
        'Project Master'   => ['cat_class' => 'bg-violet-50 text-violet-700',  'icon' => 'rectangle-stack',    'icon_class' => 'bg-violet-100 text-violet-700',  'label' => 'Proyek'],
        'Client Directory' => ['cat_class' => 'bg-emerald-50 text-emerald-700','icon' => 'building-office',    'icon_class' => 'bg-emerald-100 text-emerald-700', 'label' => 'Klien'],
        'Team Management'  => ['cat_class' => 'bg-pink-50 text-pink-700',      'icon' => 'users',              'icon_class' => 'bg-pink-100 text-pink-700',      'label' => 'Tim'],
        'Settings'         => ['cat_class' => 'bg-orange-50 text-orange-700',  'icon' => 'cog-6-tooth',        'icon_class' => 'bg-orange-100 text-orange-700',  'label' => 'Settings'],
        'Auth'             => ['cat_class' => 'bg-slate-100 text-slate-700',   'icon' => 'shield-check',       'icon_class' => 'bg-slate-100 text-slate-700',    'label' => 'Akun'],
    ];

    $notifications = [];
    try {
        if ($auditViewerAllowed) {
            $notifQuery = \App\Models\AuditLog::with('user')
                ->orderByDesc('created_at')
                ->orderByDesc('id');

            if ($isOperationalViewer) {
                $notifQuery->where('user_id', $user->id);
            }

            $notifLogs = $notifQuery->limit(8)->get();

            $unreadThreshold = \App\Support\AppTime::now()->subDay();

            foreach ($notifLogs as $log) {
                $palette = $notifPaletteByModule[$log->module] ?? [
                    'cat_class' => 'bg-slate-100 text-slate-700',
                    'icon' => 'bell',
                    'icon_class' => 'bg-slate-100 text-slate-700',
                    'label' => $log->module,
                ];
                $tag = \App\Http\Controllers\AuditController::tagForLog($log->module, $log->action, $log->auditable_type, $log->description);
                $actorName = $log->user?->name ?? 'Sistem';

                /* Default deep-link by module — safe for both roles.
                 * Project Master rows route through AuditController::deepLinkForLog
                 * which resolves the real parent project_id (from new_values or
                 * the auditable model) instead of treating auditable_id as the
                 * project id directly — that previously sent QC/MoM/task logs to
                 * the wrong /projects/{id} page. */
                $href = match (true) {
                    $log->module === 'Project Master' => \App\Http\Controllers\AuditController::deepLinkForLog($log),
                    $log->module === 'Client Directory' && $isFullAuditViewer => \App\Http\Controllers\AuditController::deepLinkForLog($log),
                    $log->module === 'Team Management' && $isOperationalViewer => route('dashboard.index'),
                    $log->module === 'Team Management' => route('team.index'),
                    $log->module === 'Settings' && $isFullAuditViewer => route('settings.index'),
                    default => route('audit.index'),
                };

                $notifications[] = [
                    'category'       => strtolower($palette['label']),
                    'category_label' => $tag,
                    'category_class' => $palette['cat_class'],
                    'icon'           => $palette['icon'],
                    'icon_class'     => $palette['icon_class'],
                    'title'          => $isOperationalViewer
                        ? (strip_tags((string) $log->description) ?: $tag)
                        : ($actorName . ' · ' . $tag),
                    'message'        => $isOperationalViewer
                        ? ($palette['label'] . ' · ' . $log->module)
                        : (strip_tags((string) $log->description) ?: $palette['label']),
                    'time'           => $appTimeDiff($log->created_at),
                    'unread'         => \App\Support\AppTime::cast($log->created_at)?->greaterThan($unreadThreshold) ?? false,
                    'href'           => $href,
                ];
            }
        }
    } catch (\Throwable $e) {
        $notifications = [];
    }

    $notifUnreadCount = collect($notifications)->where('unread', true)->count();

    /* Legacy hardcoded search index block — kept inside `if (false)` to neuter it. */
    if (false) {
    foreach (\App\Models\User::with('roles')->get() as $u) {
        $roleName = $u->roles->first()?->name;
        if (! $roleName || $roleName === 'ceo_pm') continue;
        $searchIndex[] = [
            'type'     => 'team',
            'label'    => $u->name,
            'sub'      => 'Team · ' . $roleName,
            'haystack' => Illuminate\Support\Str::lower($u->name . ' ' . $u->email . ' ' . $roleName),
            'href'     => url('/team') . '?open=member:' . $u->id,
        ];
    }

    foreach (\App\Models\Project::where('is_featured', true)->get() as $p) {
        $searchIndex[] = [
            'type'     => 'project',
            'label'    => $p->name,
            'sub'      => 'Project · ' . ($p->phase ?? '—'),
            'haystack' => Illuminate\Support\Str::lower($p->name . ' ' . $p->code . ' ' . ($p->phase ?? '') . ' ' . ($p->status ?? '')),
            'href'     => url('/projects/' . $p->id),
        ];
    }

    $clientsHc = [
        ['id' => 1, 'name' => 'PT Maju Jaya Indonesia',     'industry' => 'Manufaktur',     'pic' => 'Hendra Wijaya',  'email' => 'hendra@majujaya.co.id'],
        ['id' => 2, 'name' => 'CV Berkah Digital',           'industry' => 'Digital Agency', 'pic' => 'Sari Lestari',   'email' => 'sari@berkahdigital.id'],
        ['id' => 3, 'name' => 'PT Solusi Pintar Mandiri',    'industry' => 'Fintech',        'pic' => 'Adi Pratama',    'email' => 'adi@solusipintar.io'],
        ['id' => 4, 'name' => 'PT Trans Nusantara Logistik', 'industry' => 'Logistik',       'pic' => 'Reza Saputra',   'email' => 'reza@transnusantara.co.id'],
        ['id' => 5, 'name' => 'PT Global Prima Sentosa',     'industry' => 'Trading',        'pic' => 'Linda Hartono',  'email' => 'linda@globalprima.com'],
        ['id' => 6, 'name' => 'PT Toko Cerdas Retail',       'industry' => 'Retail',         'pic' => 'Budi Santoso',   'email' => 'budi@tokocerdas.id'],
        ['id' => 7, 'name' => 'PT Karya Sejahtera',          'industry' => 'Konstruksi',     'pic' => 'Doni Kurniawan', 'email' => 'doni@karyasejahtera.co.id'],
        ['id' => 8, 'name' => 'CV Nirwana Ventures',         'industry' => 'Startup Studio', 'pic' => 'Maya Putri',     'email' => 'maya@nirwanaventures.co'],
    ];
    foreach ($clientsHc as $c) {
        $searchIndex[] = [
            'type'     => 'client',
            'label'    => $c['name'],
            'sub'      => 'Client · ' . $c['industry'] . ' · PIC ' . $c['pic'],
            'haystack' => Illuminate\Support\Str::lower($c['name'] . ' ' . $c['industry'] . ' ' . $c['pic'] . ' ' . $c['email']),
            'href'     => url('/clients') . '?open=client:' . $c['id'],
        ];
    }

    $auditHc = [
        ['tag' => 'LOGIN',             'actor' => 'Joshua Raphael',  'module' => 'Auth',              'date' => 'Hari Ini', 'time' => '09:42'],
        ['tag' => 'RISK ALERT',        'actor' => 'AI Sekretaris',   'module' => 'Executive Monitor', 'date' => 'Hari Ini', 'time' => '09:15'],
        ['tag' => 'WBS GENERATED',     'actor' => 'AI Sekretaris',   'module' => 'Project Master',    'date' => 'Hari Ini', 'time' => '08:50'],
        ['tag' => 'LAPORAN EKSPOR',    'actor' => 'Joshua Raphael',  'module' => 'Executive Monitor', 'date' => 'Hari Ini', 'time' => '08:30'],
        ['tag' => 'KLIEN BARU',        'actor' => 'Adly',            'module' => 'Client Directory',  'date' => 'Kemarin',  'time' => '16:42'],
        ['tag' => 'WA OUTBOUND',       'actor' => 'AI Sekretaris',   'module' => 'AI Planning',       'date' => 'Kemarin',  'time' => '15:30'],
        ['tag' => 'PROYEK DIPERBARUI', 'actor' => 'Irwan Kurniawan', 'module' => 'Project Master',    'date' => 'Kemarin',  'time' => '14:10'],
        ['tag' => 'AKSES DITINJAU',    'actor' => 'Yuda Prayoga',    'module' => 'Team Management',   'date' => 'Kemarin',  'time' => '10:05'],
    ];
    foreach ($auditHc as $a) {
        $searchIndex[] = [
            'type'     => 'audit',
            'label'    => $a['tag'] . ' — ' . $a['actor'],
            'sub'      => 'Audit · ' . $a['module'] . ' · ' . $a['date'] . ' ' . $a['time'],
            'haystack' => Illuminate\Support\Str::lower($a['tag'] . ' ' . $a['actor'] . ' ' . $a['module']),
            'href'     => url('/audit'),
        ];
    }

    }

    /* ============== Search index (role-aware) ==============
     * CEO/PM + admin-tier  → global index (team, projects, clients, audit, settings).
     * Operational          → only their assigned projects, own tasks, own audit logs.
     * No unassigned project, no other team members, no clients,
     * no global audit, no settings entries leak into operational DOM. */
    $searchIndex      = [];
    $searchPlaceholder = $isOperationalViewer
        ? 'Cari proyek atau task Anda...'
        : 'Cari proyek, klien, anggota, audit, settings...';

    try {
        if ($isOperationalViewer) {
            $assignedProjectIds = \App\Models\TeamAssignment::query()
                ->where('user_id', $user->id)
                ->pluck('project_id')
                ->unique();

            foreach (\App\Models\Project::with('client')->whereIn('id', $assignedProjectIds)->whereNull('archived_at')->orderByDesc('updated_at')->get() as $p) {
                $searchIndex[] = [
                    'type'     => 'project',
                    'label'    => $p->name,
                    'sub'      => 'Project - ' . $p->code . ' - ' . ($p->client?->name ?? 'Tanpa klien'),
                    'haystack' => Illuminate\Support\Str::lower($p->name . ' ' . $p->code . ' ' . ($p->client?->name ?? '') . ' ' . ($p->phase ?? '') . ' ' . ($p->status ?? '')),
                    'href'     => route('projects.show', $p),
                ];
            }

            foreach (\App\Models\ProjectTask::with('project:id,name,code')->where('assigned_to', $user->id)->orderByDesc('updated_at')->limit(60)->get() as $t) {
                $searchIndex[] = [
                    'type'     => 'task',
                    'label'    => $t->title,
                    'sub'      => 'Task - ' . ($t->project?->code ?? '-') . ' - ' . ucfirst(str_replace('_', ' ', (string) $t->status)),
                    'haystack' => Illuminate\Support\Str::lower($t->title . ' ' . ($t->project?->name ?? '') . ' ' . ($t->project?->code ?? '') . ' ' . ($t->status ?? '') . ' ' . ($t->priority ?? '')),
                    'href'     => $t->project ? route('projects.show', $t->project) . '#workspace' : route('projects.index'),
                ];
            }

            foreach (\App\Models\AuditLog::where('user_id', $user->id)->orderByDesc('created_at')->orderByDesc('id')->limit(40)->get() as $a) {
                $auditTag = \App\Http\Controllers\AuditController::tagForLog($a->module, $a->action, $a->auditable_type, $a->description);
                $searchIndex[] = [
                    'type'     => 'activity',
                    'label'    => $auditTag,
                    'sub'      => 'Activity - ' . $a->module . ' - ' . $appTimeDiff($a->created_at),
                    'haystack' => Illuminate\Support\Str::lower($auditTag . ' ' . $a->module . ' ' . $a->action . ' ' . strip_tags((string) $a->description)),
                    'href'     => \App\Http\Controllers\AuditController::deepLinkForLog($a),
                ];
            }
        } elseif ($auditViewerAllowed) {
            // CEO/PM + admin-tier global index — same fields as before.
            foreach (\App\Models\User::with('roles')->whereNull('archived_at')->orderBy('name')->get() as $u) {
                $roleName = $u->roles->first()?->name;
                if (! $roleName || $roleName === 'ceo_pm') continue;
                $searchIndex[] = [
                    'type'     => 'team',
                    'label'    => $u->name,
                    'sub'      => 'Team - ' . ($u->position ?: $roleName),
                    'haystack' => Illuminate\Support\Str::lower($u->name . ' ' . $u->email . ' ' . $roleName . ' ' . ($u->position ?? '') . ' ' . ($u->department ?? '')),
                    'href'     => route('team.index') . '?open=member:' . $u->id,
                ];
            }

            foreach (\App\Models\Project::with('client')->whereNull('archived_at')->orderByDesc('updated_at')->limit(80)->get() as $p) {
                $searchIndex[] = [
                    'type'     => 'project',
                    'label'    => $p->name,
                    'sub'      => 'Project - ' . $p->code . ' - ' . ($p->client?->name ?? 'Tanpa klien'),
                    'haystack' => Illuminate\Support\Str::lower($p->name . ' ' . $p->code . ' ' . ($p->client?->name ?? '') . ' ' . ($p->phase ?? '') . ' ' . ($p->status ?? '') . ' ' . ($p->description ?? '')),
                    'href'     => route('projects.show', $p),
                ];
            }

            foreach (\App\Models\Client::whereNull('archived_at')->orderByDesc('updated_at')->limit(80)->get() as $c) {
                $searchIndex[] = [
                    'type'     => 'client',
                    'label'    => $c->name,
                    'sub'      => 'Client - ' . ($c->industry ?: 'Tanpa industri') . ' - PIC ' . ($c->pic_name ?: '-'),
                    'haystack' => Illuminate\Support\Str::lower($c->name . ' ' . $c->code . ' ' . ($c->industry ?? '') . ' ' . ($c->location ?? '') . ' ' . ($c->pic_name ?? '') . ' ' . ($c->email ?? '') . ' ' . ($c->phone ?? '')),
                    'href'     => route('clients.index') . '?open=client:' . $c->id,
                ];
            }

            foreach (\App\Models\AuditLog::with('user')->orderByDesc('created_at')->orderByDesc('id')->limit(60)->get() as $a) {
                $auditChip = \App\Http\Controllers\AuditController::categoryForModule($a->module, $a->action);
                $auditTag = \App\Http\Controllers\AuditController::tagForLog($a->module, $a->action, $a->auditable_type, $a->description);
                $auditActor = $a->user?->name ?? 'Sistem';
                $searchIndex[] = [
                    'type'     => 'audit',
                    'label'    => $auditTag . ' - ' . $auditActor,
                    'sub'      => 'Audit - ' . $a->module . ' - ' . $appTimeDiff($a->created_at),
                    'haystack' => Illuminate\Support\Str::lower($auditTag . ' ' . $auditActor . ' ' . $a->module . ' ' . $a->action . ' ' . strip_tags((string) $a->description)),
                    'href'     => \App\Http\Controllers\AuditController::deepLinkForLog($a) !== route('audit.index')
                        ? \App\Http\Controllers\AuditController::deepLinkForLog($a)
                        : ($auditChip === 'all' ? route('audit.index') : route('audit.index', ['chip' => $auditChip])),
                ];
            }

            foreach ([['general', 'Umum'], ['ai', 'Sekretaris AI'], ['notif', 'Notifikasi'], ['integrations', 'Integrasi'], ['security', 'Keamanan']] as [$tid, $tlabel]) {
                $searchIndex[] = [
                    'type'     => 'settings',
                    'label'    => 'Settings: ' . $tlabel,
                    'sub'      => 'Pengaturan',
                    'haystack' => Illuminate\Support\Str::lower('settings pengaturan ' . $tlabel . ' ' . $tid),
                    'href'     => url('/settings') . '#' . $tid,
                ];
            }
        }
    } catch (\Throwable $e) {
        $searchIndex = [];
    }
@endphp

<header class="h-14 flex items-center gap-4 px-6 bg-card/80 backdrop-blur-md border-b border-border/60 sticky top-0 z-30">
    <nav class="hidden md:flex items-center gap-2 text-sm text-muted-foreground flex-shrink-0">
        <a href="{{ route('executive.index') }}" class="hover:text-primary transition-colors">Home</a>
        <x-heroicon-o-chevron-right class="w-4 h-4 opacity-60" />
        @if (request()->routeIs('projects.show'))
            @php
                $crumbProject = request()->route('project');
                /* Operational users see this index as "Projects" (assigned-only);
                   CEO/PM see it as "Project Master" (full inventory). Mirror the
                   wording in the breadcrumb so it matches what they clicked into. */
                $crumbProjectsLabel = $isOperationalViewer ? 'Projects' : 'Project Master';
            @endphp
            <a href="{{ route('projects.index') }}" class="hover:text-primary transition-colors">{{ $crumbProjectsLabel }}</a>
            <x-heroicon-o-chevron-right class="w-4 h-4 opacity-60" />
            <span class="text-primary font-semibold truncate max-w-[280px]">{{ $crumbProject?->name ?? $pageTitle }}</span>
        @else
            <span class="text-primary font-semibold">{{ $pageTitle }}</span>
        @endif
    </nav>

    <div class="flex-1 flex justify-center">
        <div class="relative w-full max-w-md hidden lg:block">
            <x-heroicon-o-magnifying-glass class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-muted-foreground" />
            <input
                type="text"
                data-global-search
                placeholder="{{ $searchPlaceholder }}"
                class="w-full h-9 pl-9 pr-3 rounded-full bg-background border border-border text-sm placeholder:text-muted-foreground focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 transition"
                role="combobox"
                aria-expanded="false"
                aria-autocomplete="list"
                aria-controls="search-suggest-list"
            />
            <div data-search-dropdown class="hidden absolute left-0 right-0 top-full mt-2 z-40 bg-white rounded-2xl border border-violet-100 shadow-[0_24px_64px_rgba(124,58,237,0.18)] overflow-hidden">
                <div id="search-suggest-list" data-search-results role="listbox" class="max-h-[480px] overflow-y-auto"></div>
                <div data-search-empty class="hidden px-5 py-6 text-center text-[12.5px] text-slate-400">
                    Tidak ada hasil untuk "<span data-search-empty-q class="font-semibold text-slate-500"></span>". Coba kata kunci lain.
                </div>
            </div>
        </div>
    </div>

    @if ($user)
        {{-- Activity / Audit topbar button. Operational users see "Activity
             Log" (self-scoped); CEO/PM + admin-tier see "Audit Trail" (global). --}}
        <div class="flex items-center gap-2 flex-shrink-0">
            @if ($auditViewerAllowed)
                <button
                    type="button"
                    data-audit-trigger
                    title="{{ $auditButtonLabel }}"
                    aria-label="{{ $auditButtonLabel }}"
                    class="inline-flex items-center gap-2 h-9 px-3 rounded-full border border-violet-100 bg-white hover:bg-violet-50 hover:border-violet-300 text-slate-600 hover:text-violet-700 transition cursor-pointer"
                >
                    <x-heroicon-o-clock class="w-4 h-4 flex-shrink-0" />
                    <span class="hidden md:inline text-[12.5px] font-semibold">{{ $auditButtonLabel }}</span>
                </button>
            @endif
            <button
                type="button"
                data-notif-trigger
                title="Notifikasi"
                aria-label="Notifikasi"
                aria-haspopup="dialog"
                aria-expanded="false"
                class="relative p-2 rounded-full text-muted-foreground hover:bg-muted hover:text-foreground cursor-pointer transition-colors"
            >
                <x-heroicon-o-bell class="w-5 h-5" />
                <span
                    data-notif-badge
                    @class([
                        'absolute -top-0.5 -right-0.5 min-w-[18px] h-[18px] px-1 rounded-full bg-gradient-to-br from-[#7C3AED] to-[#A855F7] text-white text-[10px] font-bold leading-none flex items-center justify-center shadow-[0_2px_6px_rgba(124,58,237,0.45)] border-2 border-card pointer-events-none',
                        'hidden' => $notifUnreadCount === 0,
                    ])
                >{{ $notifUnreadCount > 9 ? '9+' : $notifUnreadCount }}</span>
            </button>

            <div class="hidden sm:flex flex-col items-end leading-tight ml-1">
                <span class="text-sm font-medium text-foreground">{{ $user->name }}</span>
                @if ($roleLabel)
                    <span class="text-[10px] font-medium uppercase tracking-wider text-muted-foreground">{{ $roleLabel }}</span>
                @endif
            </div>

            <div class="w-9 h-9 rounded-full bg-accent text-accent-foreground border-2 border-border hover:border-primary flex items-center justify-center text-sm font-semibold flex-shrink-0 transition-colors cursor-pointer">
                {{ $initials }}
            </div>

            {{-- Normal app logout (not the Filament panel logout) so CEO/PM
                 and operational roles—who are blocked from /admin—can sign out
                 without hitting the panel's 403. --}}
            <form method="POST" action="{{ route('logout') }}" class="inline ml-1">
                @csrf
                <button
                    type="submit"
                    class="w-9 h-9 rounded-full flex items-center justify-center text-muted-foreground hover:bg-muted hover:text-foreground cursor-pointer transition-colors"
                    title="Sign out"
                >
                    <x-heroicon-o-arrow-right-on-rectangle class="w-5 h-5" />
                </button>
            </form>
        </div>
    @endif
</header>

@if ($user)
    <style>
        .audit-pill {
            padding: 0.45rem 0.9rem;
            border-radius: 9999px;
            font-size: 11.5px;
            font-weight: 600;
            border: 1px solid #E9D5FF;
            background: #fff;
            color: #475569;
            transition: all 200ms ease;
            white-space: nowrap;
            cursor: pointer;
        }
        .audit-pill:hover {
            border-color: #A855F7;
            color: #7C3AED;
        }
        .audit-pill.is-active {
            background: #1E1B4B;
            color: #fff;
            border-color: #1E1B4B;
            box-shadow: 0 2px 6px rgba(30,27,75,0.15);
        }
        [data-audit-filter-bar]::-webkit-scrollbar { display: none; }
        [data-audit-filter-bar] { scrollbar-width: none; -ms-overflow-style: none; }
        [data-audit-feed]::-webkit-scrollbar       { width: 6px; }
        [data-audit-feed]::-webkit-scrollbar-thumb { background: rgba(124,58,237,0.25); border-radius: 9999px; }
        [data-audit-feed]::-webkit-scrollbar-track { background: transparent; }
        [data-audit-feed] { scrollbar-width: thin; scrollbar-color: rgba(124,58,237,0.25) transparent; }

        [data-notif-list]::-webkit-scrollbar       { width: 6px; }
        [data-notif-list]::-webkit-scrollbar-thumb { background: rgba(124,58,237,0.25); border-radius: 9999px; }
        [data-notif-list]::-webkit-scrollbar-track { background: transparent; }
        [data-notif-list] { scrollbar-width: thin; scrollbar-color: rgba(124,58,237,0.25) transparent; }

        .notif-item              { border-left: 3px solid transparent; transition: background 150ms ease; }
        .notif-item.is-unread    { border-left-color: #7C3AED; background: rgba(245,243,255,0.6); }
        .notif-item .notif-dot   { display: none; }
        .notif-item.is-unread .notif-dot { display: block; }

        [data-notif-dropdown] { animation: notifFade 150ms ease-out; }
        @keyframes notifFade {
            from { opacity: 0; transform: translateY(-4px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        [data-toast-host] {
            position: fixed; bottom: 1.5rem; right: 1.5rem; z-index: 60;
            display: flex; flex-direction: column; gap: 0.5rem;
            pointer-events: none;
        }
        .toast-item {
            background: #1E1B4B; color: #fff;
            font-size: 13px; font-weight: 500;
            padding: 0.625rem 1rem; border-radius: 0.75rem;
            box-shadow: 0 8px 24px rgba(124,58,237,0.25);
            pointer-events: auto;
            opacity: 0; transform: translateY(8px);
            transition: opacity 200ms ease, transform 200ms ease;
            max-width: 360px;
        }
        .toast-item.is-visible { opacity: 1; transform: translateY(0); }
    </style>

    {{-- =============== AUDIT TRAIL MODAL =============== --}}
    @if ($auditViewerAllowed)
    <div data-audit-modal class="hidden fixed inset-0 z-50 items-center justify-center p-4 sm:p-6" role="dialog" aria-modal="true" aria-labelledby="audit-modal-title">
        <div data-audit-overlay class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm"></div>

        <div data-audit-panel class="relative bg-white rounded-3xl shadow-[0_24px_64px_rgba(124,58,237,0.18)] w-full max-w-3xl max-h-[90vh] flex flex-col overflow-hidden border border-violet-100">

            {{-- Sticky header --}}
            <header class="px-6 py-4 border-b border-violet-100 bg-white/90 backdrop-blur-md flex justify-between items-start gap-4 flex-shrink-0">
                <div class="flex items-start gap-4 min-w-0">
                    <div class="w-12 h-12 rounded-2xl bg-violet-100 flex items-center justify-center flex-shrink-0">
                        <x-heroicon-o-clipboard-document-list class="w-6 h-6 text-violet-700" />
                    </div>
                    <div class="min-w-0">
                        <h2 id="audit-modal-title" class="text-[20px] font-bold text-[#1E1B4B] leading-tight">{{ $auditModalTitle }}</h2>
                        <p class="text-[12px] text-slate-500 mt-1">
                            @if ($auditModalSubtitle)
                                {{ $auditModalSubtitle }} &middot; <span data-audit-subtitle-count>{{ $auditTotal }}</span> entri
                            @else
                                <span data-audit-subtitle-count>{{ $auditTotal }}</span> entri ditampilkan &middot; {{ $auditTodayCount }} aktivitas hari ini
                            @endif
                        </p>
                    </div>
                </div>
                <button type="button" data-audit-close aria-label="Tutup" class="w-9 h-9 rounded-full hover:bg-violet-50 flex items-center justify-center text-slate-500 hover:text-rose-500 transition-colors flex-shrink-0 cursor-pointer">
                    <x-heroicon-o-x-mark class="w-5 h-5" />
                </button>
            </header>

            {{-- Sticky filter bar --}}
            <div data-audit-filter-bar class="py-3 border-b border-violet-100 bg-violet-50/40 overflow-x-auto flex-shrink-0">
                <div class="flex items-center gap-1.5 px-6">
                    @foreach ($auditFilters as $idx => $f)
                        <button
                            type="button"
                            data-audit-filter="{{ $f['id'] }}"
                            class="audit-pill {{ $idx === 0 ? 'is-active' : '' }}"
                        >{{ $f['label'] }}</button>
                    @endforeach
                </div>
            </div>

            {{-- Scrollable feed --}}
            <div data-audit-feed class="flex-1 overflow-y-auto px-6 py-2 bg-white">
                {{-- Timeline column wrapper. The connector lives INSIDE this scrolling content
                     so the line travels with the entries instead of staying pinned to the viewport.
                     Avatar geometry: feed has px-6, entries have `-mx-6 px-6`, so each avatar starts
                     at this wrapper's left edge. Avatar = w-10 (40px), so center is 20px → 1.25rem.
                     Entry uses py-4 (16px) + items-start, so first/last avatar centers sit 36px
                     (= 2.25rem = top-9 / bottom-9) from the wrapper's top/bottom. --}}
                <div class="relative">
                    @if ($auditTotal > 0)
                        <div
                            class="absolute top-9 bottom-9 w-px bg-violet-100 pointer-events-none hidden sm:block"
                            style="left: calc(1.25rem - 0.5px);"
                            aria-hidden="true"
                        ></div>
                    @endif
                    @forelse ($auditEntries as $i => $e)
                        @php
                            $entryTag = ! empty($e['deep_link']) ? 'a' : 'div';
                            $entryHref = $e['deep_link'] ?? null;
                        @endphp
                        <{{ $entryTag }}
                            data-audit-entry
                            data-audit-category="{{ $e['category'] }}"
                            @if ($entryHref) href="{{ $entryHref }}" @endif
                            class="relative flex items-start group hover:bg-violet-50/40 -mx-6 px-6 transition-colors py-4 no-underline"
                        >
                            <div class="w-10 h-10 rounded-full flex items-center justify-center text-[12px] font-bold flex-shrink-0 border-2 border-white z-10 relative {{ $e['avatar_class'] }}">{{ $e['initials'] }}</div>
                            <div class="ml-3 flex-1 min-w-0 pr-2">
                                <div class="flex items-center justify-between mb-1 gap-2">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold tracking-wider {{ $e['category_class'] }}">{{ $e['category_label'] }}</span>
                                    <span class="text-[11px] text-slate-400 flex-shrink-0">{{ $e['time'] }}</span>
                                </div>
                                <p class="text-[13.5px] text-[#1E1B4B] leading-snug break-words">{!! $e['text'] !!}</p>
                                @if (! empty($e['project_name']))
                                    <p class="text-[11px] text-violet-600 font-semibold mt-1 truncate">
                                        {{ $e['project_name'] }}
                                        <span class="text-slate-300 font-normal">·</span>
                                        <span class="text-slate-400 font-normal">{{ $e['module'] ?? '' }}</span>
                                    </p>
                                @endif
                                <p class="text-[11px] text-slate-400 mt-1">{{ $e['user'] }}</p>
                            </div>
                        </{{ $entryTag }}>
                        @if ($i < $auditTotal - 1)
                            <div class="border-b border-violet-50 ml-14"></div>
                        @endif
                    @empty
                        <div class="py-12 text-center">
                            <div class="w-12 h-12 mx-auto rounded-2xl bg-violet-50 text-violet-400 flex items-center justify-center mb-3">
                                <x-heroicon-o-inbox class="w-5 h-5" />
                            </div>
                            <p class="text-[13px] font-semibold text-[#1E1B4B]">Belum ada aktivitas</p>
                            <p class="text-[12px] text-slate-500 mt-1">Aksi pada Proyek, Klien, Tim, atau Settings akan muncul di sini.</p>
                        </div>
                    @endforelse
                </div>
            </div>

            {{-- Sticky footer --}}
            <footer class="px-6 py-3 border-t border-violet-100 bg-violet-50/30 flex justify-between items-center gap-3 flex-shrink-0">
                <span class="text-[12px] text-slate-500">
                    Menampilkan <span data-audit-footer-count>{{ $auditTotal }}</span> entri terbaru
                </span>
                <button type="button" data-audit-close class="px-5 h-9 rounded-xl bg-white border border-violet-200 text-[13px] font-semibold text-slate-600 hover:border-violet-400 hover:text-violet-700 transition cursor-pointer">
                    Tutup
                </button>
            </footer>
        </div>
    </div>
    @endif

    {{-- Toast host (shared across pages via topbar) --}}
    <div data-toast-host aria-live="polite" aria-atomic="true"></div>

    {{-- Global search index --}}
    <script>window.__searchIndex = @json($searchIndex);</script>

    {{-- =============== NOTIFICATION DROPDOWN =============== --}}
    <div
        data-notif-dropdown
        class="hidden fixed top-14 right-4 sm:right-6 z-50 w-[360px] max-w-[calc(100vw-2rem)]"
        role="dialog"
        aria-modal="false"
        aria-labelledby="notif-title"
    >
        <div class="bg-white rounded-2xl border border-violet-100 shadow-[0_24px_64px_rgba(124,58,237,0.18)] overflow-hidden flex flex-col">

            {{-- Header --}}
            <div class="px-5 py-4 border-b border-violet-100 flex items-center justify-between gap-3">
                <div class="flex items-center gap-3 min-w-0">
                    <div class="w-9 h-9 rounded-xl bg-violet-100 flex items-center justify-center flex-shrink-0">
                        <x-heroicon-o-bell class="w-5 h-5 text-violet-700" />
                    </div>
                    <div class="min-w-0">
                        <h3 id="notif-title" class="text-[15px] font-bold text-[#1E1B4B] leading-tight">Notifikasi</h3>
                        <p class="text-[11px] text-slate-500 mt-0.5">
                            <span
                                data-notif-unread-pill
                                @class([
                                    'inline-flex items-center px-1.5 py-0.5 rounded-full bg-violet-100 text-violet-700 text-[10px] font-bold mr-1',
                                    'hidden' => $notifUnreadCount === 0,
                                ])
                            ><span data-notif-unread-num>{{ $notifUnreadCount }}</span> baru</span>
                            <span>{{ count($notifications) }} total</span>
                        </p>
                    </div>
                </div>
            </div>

            {{-- List --}}
            <div data-notif-list class="max-h-[440px] overflow-y-auto">
                @forelse ($notifications as $n)
                    <a
                        href="{{ $n['href'] }}"
                        data-notif-item
                        data-notif-category="{{ $n['category'] }}"
                        @class(['notif-item flex items-start gap-3 px-5 py-3.5 hover:bg-violet-50/70 transition cursor-pointer no-underline', 'is-unread' => $n['unread']])
                    >
                        <div class="w-9 h-9 rounded-lg flex items-center justify-center flex-shrink-0 {{ $n['icon_class'] }}">
                            <x-dynamic-component :component="'heroicon-o-' . $n['icon']" class="w-4 h-4" />
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 mb-1">
                                <span class="text-[10px] font-bold tracking-wide uppercase rounded-md px-1.5 py-0.5 {{ $n['category_class'] }}">{{ $n['category_label'] }}</span>
                                <span class="text-[10.5px] text-slate-400 ml-auto flex-shrink-0">{{ $n['time'] }}</span>
                            </div>
                            <div class="text-[13px] font-semibold text-[#1E1B4B] leading-snug">{{ $n['title'] }}</div>
                            <p class="text-[12px] text-slate-500 leading-snug mt-0.5 line-clamp-2">{{ $n['message'] }}</p>
                        </div>
                        <span class="notif-dot w-2 h-2 rounded-full bg-violet-500 flex-shrink-0 mt-2"></span>
                    </a>
                @empty
                    <div class="px-5 py-10 text-center">
                        <div class="w-12 h-12 mx-auto rounded-2xl bg-violet-50 text-violet-400 flex items-center justify-center mb-3">
                            <x-heroicon-o-bell class="w-5 h-5" />
                        </div>
                        <p class="text-[13px] font-semibold text-[#1E1B4B]">Belum ada notifikasi</p>
                        <p class="text-[12px] text-slate-500 mt-1">Aksi pada Proyek, Klien, Tim, atau Settings akan muncul di sini.</p>
                    </div>
                @endforelse
            </div>

            {{-- Footer.
                 "Tandai semua dibaca" intentionally omitted: there is no
                 persistent read/unread state on the server yet, and a
                 client-only toggle would only fake the action away. --}}
            <div class="px-5 py-3 border-t border-violet-100 bg-violet-50/30 flex items-center justify-end gap-3">
                {{-- Notifications belong in the activity stream, not Settings.
                     Both roles get the same destination — operational users
                     see a self-scoped feed; CEO/PM see the global feed. --}}
                <a
                    href="{{ route('audit.index') }}"
                    class="text-[12px] font-semibold text-violet-700 hover:text-violet-900 transition inline-flex items-center gap-1"
                >
                    Lihat Semua
                    <x-heroicon-o-chevron-right class="w-3.5 h-3.5" />
                </a>
            </div>
        </div>
    </div>

    <script>
        (function () {
            const wire = () => {
                const modal    = document.querySelector('[data-audit-modal]');
                if (! modal) return;
                const overlay  = modal.querySelector('[data-audit-overlay]');
                const panel    = modal.querySelector('[data-audit-panel]');
                const trigger  = document.querySelector('[data-audit-trigger]');
                const closeBtns = modal.querySelectorAll('[data-audit-close]');
                const pills    = modal.querySelectorAll('[data-audit-filter]');
                const entries  = modal.querySelectorAll('[data-audit-entry]');
                const subCount = modal.querySelector('[data-audit-subtitle-count]');
                const footCount= modal.querySelector('[data-audit-footer-count]');

                const updateCounts = (n) => {
                    if (subCount)  subCount.textContent  = n;
                    if (footCount) footCount.textContent = n;
                };

                const applyFilter = (id) => {
                    pills.forEach(p => p.classList.toggle('is-active', p.dataset.auditFilter === id));
                    let visible = 0;
                    entries.forEach(el => {
                        const cat   = el.dataset.auditCategory;
                        const show  = id === 'all' || cat === id;
                        el.classList.toggle('hidden', ! show);
                        if (show) visible++;
                    });
                    updateCounts(visible);
                };

                const openModal = () => {
                    applyFilter('all');
                    modal.classList.remove('hidden');
                    modal.classList.add('flex');
                    document.body.style.overflow = 'hidden';
                };
                const closeModal = () => {
                    modal.classList.add('hidden');
                    modal.classList.remove('flex');
                    document.body.style.overflow = '';
                };

                trigger?.addEventListener('click', openModal);
                closeBtns.forEach(b => b.addEventListener('click', closeModal));
                overlay?.addEventListener('click', closeModal);
                panel?.addEventListener('click', (e) => e.stopPropagation());
                pills.forEach(p => p.addEventListener('click', () => applyFilter(p.dataset.auditFilter)));
                document.addEventListener('keydown', (e) => {
                    if (e.key === 'Escape' && ! modal.classList.contains('hidden')) closeModal();
                });

                /* === Notification dropdown === */
                const notifDropdown = document.querySelector('[data-notif-dropdown]');
                const notifTrigger  = document.querySelector('[data-notif-trigger]');
                if (notifDropdown && notifTrigger) {
                    const notifBadge   = notifTrigger.querySelector('[data-notif-badge]');
                    const notifPill    = notifDropdown.querySelector('[data-notif-unread-pill]');
                    const notifNum     = notifDropdown.querySelector('[data-notif-unread-num]');
                    const notifItems   = notifDropdown.querySelectorAll('[data-notif-item]');
                    const markAllBtn   = notifDropdown.querySelector('[data-notif-mark-all-read]');

                    const isNotifOpen  = () => ! notifDropdown.classList.contains('hidden');
                    const openNotif    = () => {
                        notifDropdown.classList.remove('hidden');
                        notifTrigger.setAttribute('aria-expanded', 'true');
                    };
                    const closeNotif   = () => {
                        notifDropdown.classList.add('hidden');
                        notifTrigger.setAttribute('aria-expanded', 'false');
                    };
                    const toggleNotif  = () => isNotifOpen() ? closeNotif() : openNotif();

                    notifTrigger.addEventListener('click', (e) => {
                        e.stopPropagation();
                        toggleNotif();
                    });
                    notifDropdown.addEventListener('click', (e) => e.stopPropagation());

                    document.addEventListener('click', (e) => {
                        if (isNotifOpen() && ! notifDropdown.contains(e.target) && ! notifTrigger.contains(e.target)) {
                            closeNotif();
                        }
                    });
                    document.addEventListener('keydown', (e) => {
                        if (e.key === 'Escape' && isNotifOpen()) closeNotif();
                    });

                    markAllBtn?.addEventListener('click', () => {
                        notifItems.forEach(it => it.classList.remove('is-unread'));
                        if (notifPill)  notifPill.classList.add('hidden');
                        if (notifNum)   notifNum.textContent = '0';
                        if (notifBadge) notifBadge.classList.add('hidden');
                    });

                    /* Individual mark-read on click before native navigation */
                    notifItems.forEach(item => {
                        item.addEventListener('click', () => {
                            if (! item.classList.contains('is-unread')) return;
                            item.classList.remove('is-unread');
                            const remaining = notifDropdown.querySelectorAll('[data-notif-item].is-unread').length;
                            if (notifNum) notifNum.textContent = String(remaining);
                            if (remaining === 0) {
                                notifPill?.classList.add('hidden');
                                notifBadge?.classList.add('hidden');
                            } else if (notifBadge) {
                                notifBadge.textContent = remaining > 9 ? '9+' : String(remaining);
                            }
                        });
                    });
                }

                /* === Global file download helper === */
                window.downloadFile = (filename, content, mime) => {
                    try {
                        const blob = new Blob([content], { type: mime || 'text/plain;charset=utf-8' });
                        const url  = URL.createObjectURL(blob);
                        const a    = document.createElement('a');
                        a.href = url;
                        a.download = filename;
                        document.body.appendChild(a);
                        a.click();
                        document.body.removeChild(a);
                        setTimeout(() => URL.revokeObjectURL(url), 200);
                    } catch (e) {
                        if (window.toast) window.toast('Unduhan gagal — coba lagi.');
                    }
                };

                /* === Global toast system === */
                window.toast = (msg) => {
                    const host = document.querySelector('[data-toast-host]');
                    if (! host || ! msg) return;
                    const el = document.createElement('div');
                    el.className = 'toast-item';
                    el.setAttribute('role', 'status');
                    el.textContent = msg;
                    host.appendChild(el);
                    requestAnimationFrame(() => el.classList.add('is-visible'));
                    setTimeout(() => {
                        el.classList.remove('is-visible');
                        setTimeout(() => el.remove(), 250);
                    }, 2400);
                };

                /* Delegated handlers: [data-toast] for safe client-side feedback, [data-confirm] for destructive guard */
                const runToastAction = (target, evt) => {
                    if (target.dataset.confirm) {
                        if (! confirm(target.dataset.confirm)) {
                            if (evt) evt.preventDefault();
                            return false;
                        }
                    }
                    if (target.tagName === 'A' && (target.getAttribute('href') === '#' || ! target.getAttribute('href'))) {
                        if (evt) evt.preventDefault();
                    }
                    const msg = target.dataset.toast || target.dataset.toastAfter;
                    if (msg) window.toast(msg);
                    return true;
                };
                document.addEventListener('click', (e) => {
                    const t = e.target.closest('[data-toast], [data-confirm]');
                    if (! t || t.tagName === 'SELECT') return;
                    runToastAction(t, e);
                });
                document.addEventListener('change', (e) => {
                    if (e.target.tagName === 'SELECT' && e.target.dataset.toast) {
                        runToastAction(e.target, e);
                    }
                });

                /* === Global topbar search (suggestion dropdown + page-filter fallback) === */
                const searchInput   = document.querySelector('[data-global-search]');
                const searchDD      = document.querySelector('[data-search-dropdown]');
                const searchResults = document.querySelector('[data-search-results]');
                const searchEmpty   = document.querySelector('[data-search-empty]');
                const searchEmptyQ  = document.querySelector('[data-search-empty-q]');
                if (searchInput) {
                    const TYPE_META = {
                        team:     { label: 'Team',     icon: 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z', pillCls: 'bg-violet-50 text-violet-700' },
                        project:  { label: 'Project',  icon: 'M4 7h16M4 12h16M4 17h10',                                              pillCls: 'bg-blue-50 text-blue-700' },
                        client:   { label: 'Client',   icon: 'M4 21V8l8-5 8 5v13M9 21V12h6v9',                                       pillCls: 'bg-emerald-50 text-emerald-700' },
                        audit:    { label: 'Audit',    icon: 'M12 8v4l3 3M21 12a9 9 0 11-18 0 9 9 0 0118 0z',                        pillCls: 'bg-amber-50 text-amber-700' },
                        settings: { label: 'SETTINGS', icon: 'M10.3 3.6a1.7 1.7 0 013.4 0l.2 1a8 8 0 012.5 1.5l1-.4a1.7 1.7 0 012.1 2.3l-.5.9a8 8 0 010 3l.5.9a1.7 1.7 0 01-2.1 2.3l-1-.4a8 8 0 01-2.5 1.5l-.2 1a1.7 1.7 0 01-3.4 0l-.2-1a8 8 0 01-2.5-1.5l-1 .4a1.7 1.7 0 01-2.1-2.3l.5-.9a8 8 0 010-3l-.5-.9a1.7 1.7 0 012.1-2.3l1 .4a8 8 0 012.5-1.5l.2-1z', pillCls: 'bg-slate-100 text-slate-600' },
                    };
                    const TYPE_ORDER = ['team', 'project', 'client', 'audit', 'settings'];
                    const PER_GROUP_LIMIT = 5;
                    const esc = (s) => String(s ?? '').replace(/[&<>"]/g, (c) => ({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c]));

                    const closeDropdown = () => {
                        if (! searchDD) return;
                        searchDD.classList.add('hidden');
                        searchInput.setAttribute('aria-expanded', 'false');
                    };
                    const openDropdown = () => {
                        if (! searchDD) return;
                        searchDD.classList.remove('hidden');
                        searchInput.setAttribute('aria-expanded', 'true');
                    };

                    const renderSuggestions = (query) => {
                        if (! searchResults || ! searchDD) return 0;
                        const data = window.__searchIndex || [];
                        const q = query.toLowerCase();
                        const grouped = {};
                        TYPE_ORDER.forEach(t => grouped[t] = []);
                        data.forEach(it => {
                            if (! it || ! it.haystack) return;
                            if (! it.haystack.includes(q)) return;
                            const bucket = grouped[it.type];
                            if (bucket && bucket.length < PER_GROUP_LIMIT) bucket.push(it);
                        });
                        let totalMatches = 0;
                        TYPE_ORDER.forEach(t => totalMatches += grouped[t].length);

                        if (totalMatches === 0) {
                            searchResults.innerHTML = '';
                            if (searchEmptyQ) searchEmptyQ.textContent = query;
                            searchEmpty?.classList.remove('hidden');
                            openDropdown();
                            return 0;
                        }

                        let html = '';
                        TYPE_ORDER.forEach(t => {
                            const items = grouped[t];
                            if (! items.length) return;
                            const meta = TYPE_META[t];
                            html += '<div class="px-4 pt-3 pb-1.5 flex items-center justify-between bg-violet-50/40 border-b border-violet-100">'
                                + '<span class="text-[10px] font-bold tracking-[0.18em] uppercase text-violet-700">' + meta.label + '</span>'
                                + '<span class="text-[10px] font-bold text-slate-400">' + items.length + '</span>'
                                + '</div>';
                            items.forEach(it => {
                                html += '<a href="' + esc(it.href) + '" role="option" class="search-suggest-row flex items-center gap-3 px-4 py-2.5 hover:bg-violet-50/70 transition no-underline">'
                                    + '<span class="w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0 ' + meta.pillCls + '">'
                                    + '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4"><path d="' + meta.icon + '"></path></svg>'
                                    + '</span>'
                                    + '<span class="flex-1 min-w-0">'
                                    + '<span class="block text-[13px] font-semibold text-[#1E1B4B] truncate">' + esc(it.label) + '</span>'
                                    + '<span class="block text-[11px] text-slate-500 truncate">' + esc(it.sub) + '</span>'
                                    + '</span>'
                                    + '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-3.5 h-3.5 text-slate-300 flex-shrink-0"><polyline points="9 18 15 12 9 6"></polyline></svg>'
                                    + '</a>';
                            });
                        });
                        searchResults.innerHTML = html;
                        searchEmpty?.classList.add('hidden');
                        openDropdown();
                        return totalMatches;
                    };

                    const applyPageFilter = (q) => {
                        const items = document.querySelectorAll('[data-search-item]');
                        if (! items.length) return;
                        let visible = 0;
                        items.forEach(el => {
                            const hay = (el.dataset.searchText || el.textContent || '').toLowerCase();
                            const match = ! q || hay.includes(q);
                            el.classList.toggle('hidden', ! match);
                            if (match) visible++;
                        });
                        const empty = document.querySelector('[data-empty]');
                        if (empty) empty.classList.toggle('hidden', visible > 0);
                    };

                    const onInput = () => {
                        const q = (searchInput.value || '').trim();
                        if (! q) {
                            applyPageFilter('');
                            closeDropdown();
                            return;
                        }
                        const matches = renderSuggestions(q);
                        if (matches > 0) {
                            applyPageFilter(''); // suggestions are primary; restore page items
                        } else {
                            applyPageFilter(q.toLowerCase()); // fallback to page filter when no global suggestions
                        }
                    };

                    searchInput.addEventListener('input', onInput);
                    searchInput.addEventListener('focus', () => {
                        const q = (searchInput.value || '').trim();
                        if (q) renderSuggestions(q);
                    });
                    searchInput.addEventListener('keydown', (e) => {
                        if (e.key === 'Escape') {
                            searchInput.value = '';
                            applyPageFilter('');
                            closeDropdown();
                            searchInput.blur();
                        }
                    });
                    document.addEventListener('click', (e) => {
                        if (! searchDD || searchDD.classList.contains('hidden')) return;
                        if (searchDD.contains(e.target) || searchInput.contains(e.target)) return;
                        closeDropdown();
                    });
                }
            };

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', wire);
            } else {
                wire();
            }
        })();
    </script>
@endif
