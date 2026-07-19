@php
    $errors ??= new \Illuminate\Support\ViewErrorBag;
    $projects ??= collect();
    $archiveScope ??= 'active';
    $totalActiveProjects ??= 0;
    if (! isset($members)) {
    $members = [
        [
            'id' => 1, 'name' => 'Joshua Raphael', 'initials' => 'JR', 'email' => 'joshua.raphael@avatech.test',
            'role_key' => 'ceo', 'role' => 'CEO/PM', 'level' => 'Founder', 'tenure' => '3 tahun',
            'avatar_color' => '#9333EA', 'load' => 35, 'load_hours' => 14, 'capacity_hours' => 40,
            'projects_active' => 8, 'tasks_open' => 4, 'perf' => 92, 'presence' => 'online',
            'skills' => ['Strategy', 'Client Relations', 'Product Vision', 'WhatsApp Sekretaris'],
            'phone' => '+62 812-1100-0001', 'location' => 'Jakarta · GMT+7', 'join_date' => '12 Mar 2023', 'last_active' => '5 menit lalu',
            'projects_lead' => 0, 'tasks_done' => 12,
            'bio' => 'Founder Avatech Nusantara. Fokus di hubungan klien strategic dan arah produk; gunakan Smart-PMIS dalam mode read-only untuk monitoring eksekutif.',
            'allocations' => [
                ['code' => 'AC', 'color' => '#7C3AED', 'name' => 'Alpha CRM',         'role' => 'Sponsor', 'pct' => 15, 'hours' => 6],
                ['code' => 'BP', 'color' => '#A855F7', 'name' => 'Beta Portal',       'role' => 'Sponsor', 'pct' => 10, 'hours' => 4],
                ['code' => 'GA', 'color' => '#C084FC', 'name' => 'Gamma API Gateway', 'role' => 'Sponsor', 'pct' => 10, 'hours' => 4],
            ],
            'activities' => [
                ['icon' => 'arrow-right-on-rectangle', 'bg' => '#EDE9FE', 'color' => '#7C3AED', 'text' => 'Login ke Executive Monitor',                              'time' => '5 menit lalu'],
                ['icon' => 'chat-bubble-left-right',   'bg' => '#FCE7F3', 'color' => '#DB2777', 'text' => 'Setujui draft WhatsApp untuk <strong>PT Maju Jaya</strong>', 'time' => '2 jam lalu'],
                ['icon' => 'eye',                       'bg' => '#DBEAFE', 'color' => '#1E40AF', 'text' => 'Tinjau laporan health Beta Portal',                       'time' => 'kemarin'],
            ],
            'permissions' => [
                ['name' => 'Executive Monitor', 'desc' => 'Lihat semua proyek, klien, dan tim',  'level' => 'Penuh'],
                ['name' => 'Approve WBS / TC',  'desc' => 'Tinjau & konfirmasi keluaran AI',     'level' => 'Penuh'],
                ['name' => 'Audit Trail',       'desc' => 'Akses penuh ke riwayat sistem',        'level' => 'Penuh'],
                ['name' => 'Modify Data',       'desc' => 'Membuat/mengubah data proyek & task', 'level' => 'Tidak Ada'],
            ],
        ],
        [
            'id' => 2, 'name' => 'Ahmad Rafiadly Arlisyah', 'initials' => 'AR', 'email' => 'ahmad.arlisyah@avatech.test',
            'role_key' => 'sa-qa', 'role' => 'SA/QA', 'level' => 'Senior', 'tenure' => '2 tahun',
            'avatar_color' => '#8B5CF6', 'load' => 75, 'load_hours' => 30, 'capacity_hours' => 40,
            'projects_active' => 5, 'tasks_open' => 11, 'perf' => 95, 'presence' => 'online',
            'skills' => ['Black-Box QC', 'WBS Drafting', 'MoM Curation', 'AI Prompt Tuning', 'SQL'],
            'phone' => '+62 812-1100-0002', 'location' => 'Jakarta · GMT+7', 'join_date' => '04 Apr 2024', 'last_active' => 'sekarang',
            'projects_lead' => 3, 'tasks_done' => 24,
            'bio' => 'SA/QA utama Avatech. Penanggung jawab proses AI Planning dan Quality Control sehari-hari, sekaligus jembatan eksekutif dengan tim eksekusi.',
            'allocations' => [
                ['code' => 'AC', 'color' => '#7C3AED', 'name' => 'Alpha CRM',         'role' => 'Lead SA', 'pct' => 30, 'hours' => 12],
                ['code' => 'BP', 'color' => '#A855F7', 'name' => 'Beta Portal',       'role' => 'QA Lead', 'pct' => 20, 'hours' => 8],
                ['code' => 'GA', 'color' => '#C084FC', 'name' => 'Gamma API Gateway', 'role' => 'QA',      'pct' => 18, 'hours' => 7],
                ['code' => 'EX', 'color' => '#9333EA', 'name' => 'Epsilon Exchange',  'role' => 'SA',      'pct' => 10, 'hours' => 4],
            ],
            'activities' => [
                ['icon' => 'check-circle',         'bg' => '#DCFCE7', 'color' => '#16A34A', 'text' => 'Setujui WBS <strong>Alpha CRM</strong> (28 task)',          'time' => '5 menit lalu'],
                ['icon' => 'sparkles',             'bg' => '#EDE9FE', 'color' => '#7C3AED', 'text' => 'Generate 12 Test Case untuk modul Authentication',         'time' => '2 jam lalu'],
                ['icon' => 'document-text',        'bg' => '#EDE9FE', 'color' => '#7C3AED', 'text' => 'Upload MoM Kickoff Delta Logistics',                       'time' => '5 jam lalu'],
                ['icon' => 'sparkles',             'bg' => '#FCE7F3', 'color' => '#DB2777', 'text' => 'Rapikan MoM 06 May 2026 via AI MoM Fixer',                 'time' => 'kemarin'],
            ],
            'permissions' => [
                ['name' => 'Project Master',  'desc' => 'Buat & kelola proyek',         'level' => 'Penuh'],
                ['name' => 'AI Planning',     'desc' => 'Generate WBS & MoM Fixer',     'level' => 'Penuh'],
                ['name' => 'Quality Control', 'desc' => 'Eksekusi & retest test case',  'level' => 'Penuh'],
                ['name' => 'Audit Trail',     'desc' => 'Lihat riwayat sistem',          'level' => 'Terbatas'],
            ],
        ],
        [
            'id' => 3, 'name' => 'Yuda Prayoga', 'initials' => 'YP', 'email' => 'yuda.prayoga@avatech.test',
            'role_key' => 'uiux', 'role' => 'UI/UX Designer', 'level' => 'Mid', 'tenure' => '1.5 tahun',
            'avatar_color' => '#EC4899', 'load' => 90, 'load_hours' => 36, 'capacity_hours' => 40,
            'projects_active' => 6, 'tasks_open' => 14, 'perf' => 88, 'presence' => 'online',
            'skills' => ['Figma', 'Design System', 'Prototyping', 'User Research', 'Illustration'],
            'phone' => '+62 812-1100-0003', 'location' => 'Bandung · GMT+7', 'join_date' => '20 Nov 2024', 'last_active' => '12 menit lalu',
            'projects_lead' => 2, 'tasks_done' => 19,
            'bio' => 'Designer aktif di Beta Portal dan Gamma API Gateway. Sedang mendekati overload — kandidat untuk re-balance task minggu ini.',
            'allocations' => [
                ['code' => 'BP', 'color' => '#A855F7', 'name' => 'Beta Portal',       'role' => 'Design Lead', 'pct' => 35, 'hours' => 14],
                ['code' => 'GA', 'color' => '#C084FC', 'name' => 'Gamma API Gateway', 'role' => 'UI Pair',     'pct' => 25, 'hours' => 10],
                ['code' => 'ZN', 'color' => '#7C3AED', 'name' => 'Zeta Mobile App',   'role' => 'UI/UX',       'pct' => 20, 'hours' => 8],
                ['code' => 'AC', 'color' => '#7C3AED', 'name' => 'Alpha CRM',         'role' => 'Support',     'pct' => 10, 'hours' => 4],
            ],
            'activities' => [
                ['icon' => 'arrows-right-left', 'bg' => '#DBEAFE', 'color' => '#1E40AF', 'text' => "Pindah task <strong>'UI Review Beta Portal'</strong> dari Todo ke Doing", 'time' => '3 hari lalu'],
                ['icon' => 'photo',             'bg' => '#FCE7F3', 'color' => '#DB2777', 'text' => 'Upload mockup baru untuk Dashboard Beta Portal (3 frame)',              'time' => '6 hari lalu'],
                ['icon' => 'paint-brush',       'bg' => '#EDE9FE', 'color' => '#7C3AED', 'text' => 'Update design system Avatech v2.1',                                     'time' => '1 minggu lalu'],
            ],
            'permissions' => [
                ['name' => 'Project Workspace', 'desc' => 'Akses Kanban dan task editing', 'level' => 'Penuh'],
                ['name' => 'AI Planning',       'desc' => 'Lihat hasil WBS',                'level' => 'Terbatas'],
                ['name' => 'Quality Control',   'desc' => 'Update status task',            'level' => 'Terbatas'],
                ['name' => 'Project Master',    'desc' => 'Buat / arsipkan proyek',        'level' => 'Tidak Ada'],
            ],
        ],
        [
            'id' => 4, 'name' => 'Irwan Kurniawan', 'initials' => 'IK', 'email' => 'irwan.kurniawan@avatech.test',
            'role_key' => 'fullstack', 'role' => 'Fullstack Dev', 'level' => 'Senior', 'tenure' => '2.5 tahun',
            'avatar_color' => '#10B981', 'load' => 78, 'load_hours' => 31, 'capacity_hours' => 40,
            'projects_active' => 5, 'tasks_open' => 12, 'perf' => 91, 'presence' => 'away',
            'skills' => ['Node.js', 'Express', 'PostgreSQL', 'Redis', 'Docker', 'CI/CD', 'GraphQL'],
            'phone' => '+62 812-1100-0004', 'location' => 'Jakarta · GMT+7', 'join_date' => '08 Sep 2023', 'last_active' => '20 menit lalu',
            'projects_lead' => 2, 'tasks_done' => 28,
            'bio' => 'Senior fullstack Avatech. Punya jam terbang panjang di Gamma API Gateway, kandidat utama untuk arsitektur layanan AI internal.',
            'allocations' => [
                ['code' => 'GA', 'color' => '#C084FC', 'name' => 'Gamma API Gateway', 'role' => 'Backend Lead', 'pct' => 40, 'hours' => 16],
                ['code' => 'AC', 'color' => '#7C3AED', 'name' => 'Alpha CRM',         'role' => 'Backend',      'pct' => 20, 'hours' => 8],
                ['code' => 'KP', 'color' => '#9333EA', 'name' => 'Kappa POS',         'role' => 'Reviewer',     'pct' => 14, 'hours' => 6],
                ['code' => 'DL', 'color' => '#8B5CF6', 'name' => 'Delta Logistics',   'role' => 'Backend',      'pct' => 10, 'hours' => 4],
            ],
            'activities' => [
                ['icon' => 'arrows-right-left', 'bg' => '#DBEAFE', 'color' => '#1E40AF', 'text' => "Pindah task <strong>'Setup CI/CD Pipeline'</strong> ke Review", 'time' => '1 minggu lalu'],
                ['icon' => 'code-bracket',      'bg' => '#DCFCE7', 'color' => '#166534', 'text' => 'Merge PR auth-middleware ke <strong>Gamma API</strong>',       'time' => '1 minggu lalu'],
            ],
            'permissions' => [
                ['name' => 'Project Workspace', 'desc' => 'Akses Kanban dan task editing', 'level' => 'Penuh'],
                ['name' => 'Audit Trail',       'desc' => 'Lihat riwayat sistem',           'level' => 'Terbatas'],
                ['name' => 'Quality Control',   'desc' => 'Update status task',            'level' => 'Terbatas'],
                ['name' => 'Project Master',    'desc' => 'Buat / arsipkan proyek',        'level' => 'Tidak Ada'],
            ],
        ],
        [
            'id' => 5, 'name' => 'Ferry Achmad', 'initials' => 'FA', 'email' => 'ferry.achmad@avatech.test',
            'role_key' => 'fullstack', 'role' => 'Fullstack Dev', 'level' => 'Mid', 'tenure' => '1 tahun',
            'avatar_color' => '#3B82F6', 'load' => 42, 'load_hours' => 17, 'capacity_hours' => 40,
            'projects_active' => 3, 'tasks_open' => 7, 'perf' => 87, 'presence' => 'online',
            'skills' => ['React', 'TypeScript', 'Vue', 'REST APIs', 'Vite'],
            'phone' => '+62 812-1100-0005', 'location' => 'Jakarta · GMT+7', 'join_date' => '15 Mei 2025', 'last_active' => '1 jam lalu',
            'projects_lead' => 1, 'tasks_done' => 14,
            'bio' => 'Fullstack mid dengan kapasitas tersisa cukup besar — kandidat ideal untuk menerima re-assign dari anggota yang overload.',
            'allocations' => [
                ['code' => 'DL', 'color' => '#8B5CF6', 'name' => 'Delta Logistics', 'role' => 'Frontend Lead', 'pct' => 30, 'hours' => 12],
                ['code' => 'BP', 'color' => '#A855F7', 'name' => 'Beta Portal',     'role' => 'Frontend Pair', 'pct' => 12, 'hours' => 5],
            ],
            'activities' => [
                ['icon' => 'code-bracket', 'bg' => '#DCFCE7', 'color' => '#166534', 'text' => 'Commit <strong>auth-flow-v2</strong> ke Alpha CRM', 'time' => '2 jam lalu'],
                ['icon' => 'sparkles',     'bg' => '#EDE9FE', 'color' => '#7C3AED', 'text' => 'Refactor module Delta Logistics ke pattern baru',  'time' => 'kemarin'],
            ],
            'permissions' => [
                ['name' => 'Project Workspace', 'desc' => 'Akses Kanban dan task editing', 'level' => 'Penuh'],
                ['name' => 'AI Planning',       'desc' => 'Lihat hasil WBS',                'level' => 'Terbatas'],
                ['name' => 'Quality Control',   'desc' => 'Update status task',            'level' => 'Terbatas'],
                ['name' => 'Project Master',    'desc' => 'Buat / arsipkan proyek',        'level' => 'Tidak Ada'],
            ],
        ],
        [
            'id' => 6, 'name' => 'Genta', 'initials' => 'GT', 'email' => 'genta@avatech.test',
            'role_key' => 'fullstack', 'role' => 'Fullstack Dev', 'level' => 'Mid', 'tenure' => '8 bulan',
            'avatar_color' => '#F97316', 'load' => 55, 'load_hours' => 22, 'capacity_hours' => 40,
            'projects_active' => 2, 'tasks_open' => 9, 'perf' => 84, 'presence' => 'away',
            'skills' => ['Flutter', 'React Native', 'Firebase', 'Kotlin', 'Swift'],
            'phone' => '+62 812-1100-0006', 'location' => 'Yogyakarta · GMT+7', 'join_date' => '10 Sep 2025', 'last_active' => '3 jam lalu',
            'projects_lead' => 1, 'tasks_done' => 16,
            'bio' => 'Fullstack fokus di mobile build untuk Zeta Mobile App dan Delta Logistics. Aktif di QC mobile build untuk Kappa POS.',
            'allocations' => [
                ['code' => 'ZN', 'color' => '#7C3AED', 'name' => 'Zeta Mobile App', 'role' => 'Mobile Lead', 'pct' => 40, 'hours' => 16],
                ['code' => 'DL', 'color' => '#8B5CF6', 'name' => 'Delta Logistics', 'role' => 'Mobile',      'pct' => 20, 'hours' => 8],
                ['code' => 'KP', 'color' => '#9333EA', 'name' => 'Kappa POS',       'role' => 'QC Mobile',   'pct' => 5,  'hours' => 2],
            ],
            'activities' => [
                ['icon' => 'device-phone-mobile', 'bg' => '#FFEDD5', 'color' => '#9A3412', 'text' => 'Rilis build dev Zeta Mobile v0.4.2 ke TestFlight', 'time' => '3 jam lalu'],
                ['icon' => 'bug-ant',             'bg' => '#FEE2E2', 'color' => '#991B1B', 'text' => 'Fix crash login pada Android 14',                  'time' => 'kemarin'],
            ],
            'permissions' => [
                ['name' => 'Project Workspace', 'desc' => 'Akses Kanban dan task editing', 'level' => 'Penuh'],
                ['name' => 'Quality Control',   'desc' => 'Update status task',            'level' => 'Terbatas'],
                ['name' => 'AI Planning',       'desc' => 'Lihat hasil WBS',                'level' => 'Terbatas'],
                ['name' => 'Project Master',    'desc' => 'Buat / arsipkan proyek',        'level' => 'Tidak Ada'],
            ],
        ],
    ];
    }

    // Team Management lists delivery resources only; CEO/PM keeps page access but is not a workload card.
    $deliveryMembers = collect($members)->reject(fn ($m) => $m['role_key'] === 'ceo')->values()->all();

    $filters = [
        ['id' => 'all',       'label' => 'Semua'],
        ['id' => 'sa_qa',     'label' => 'SA/QA'],
        ['id' => 'fullstack_dev', 'label' => 'Fullstack'],
        ['id' => 'ui_ux',      'label' => 'UI/UX'],
        ['id' => 'risk',      'label' => 'Burnout Risk'],
    ];

    $col = collect($deliveryMembers);

    $filterCounts = [
        'all'       => count($deliveryMembers),
        'sa_qa'     => $col->where('role_key', 'sa_qa')->count(),
        'fullstack_dev' => $col->where('role_key', 'fullstack_dev')->count(),
        'ui_ux'      => $col->where('role_key', 'ui_ux')->count(),
        'risk'      => $col->where('load', '>=', 85)->count(),
    ];

    $archiveFilters = [
        'active' => 'Active',
        'archived' => 'Archived',
        'all' => 'All',
    ];
    $editMemberId = old('_form') === 'edit' ? old('_member_id') : null;
    $editAction = $editMemberId ? route('team.members.update', $editMemberId) : '#';

    $stats = [
        ['label' => 'Total Anggota', 'value' => count($deliveryMembers),                                              'suffix' => '',      'color' => '#7C3AED'],
        ['label' => 'Kapasitas',     'value' => $col->sum('capacity_hours'),                                          'suffix' => 'h/mgg', 'color' => '#3B82F6'],
        ['label' => 'Avg Load',      'value' => $col->count() ? (int) round($col->avg('load')) : 0,                  'suffix' => '%',     'color' => '#10B981'],
        ['label' => 'Proyek Aktif',  'value' => $totalActiveProjects,                                                  'suffix' => '',      'color' => '#A855F7'],
        ['label' => 'Burnout Risk',  'value' => $col->where('load', '>=', 85)->count(),                              'suffix' => '',      'color' => '#EF4444'],
    ];

    $overloadedMembers = $col->where('load', '>=', 85)->sortByDesc('load')->values();

    $rolePill = [
        'ceo'       => 'bg-fuchsia-50 text-fuchsia-700',
        'sa_qa'     => 'bg-violet-50 text-violet-700',
        'fullstack_dev' => 'bg-cyan-50 text-cyan-700',
        'ui_ux'      => 'bg-pink-50 text-pink-700',
    ];

    $presenceClass = [
        'online'  => 'bg-emerald-500',
        'away'    => 'bg-amber-500',
        'offline' => 'bg-slate-400',
    ];

    $presenceRing = [
        'online'  => 'shadow-[0_0_0_3px_#fff,0_0_0_4px_#6EE7B7]',
        'away'    => 'shadow-[0_0_0_3px_#fff,0_0_0_4px_#FCD34D]',
        'offline' => 'shadow-[0_0_0_3px_#fff,0_0_0_4px_#CBD5E1]',
    ];

    $accessLevelPill = [
        'Penuh'     => 'bg-emerald-100 text-emerald-700',
        'Terbatas'  => 'bg-amber-100 text-amber-700',
        'Tidak Ada' => 'bg-slate-100 text-slate-500',
    ];

    $loadFill = function ($load) {
        if ($load >= 85) return '#EF4444';
        if ($load >= 65) return '#F59E0B';
        return '#10B981';
    };

    $perfFill = function ($perf) {
        if ($perf >= 90) return '#10B981';
        if ($perf >= 80) return '#7C3AED';
        return '#F59E0B';
    };
@endphp

<x-layouts.authenticated :title="$title">

    <style>
        .tm-tab.is-active {
            background: linear-gradient(135deg, #7C3AED 0%, #C084FC 100%);
            color: #fff;
            box-shadow: 0 8px 20px rgba(124,58,237,0.25);
        }
    </style>

    <section class="flex items-end justify-between mb-8 gap-6 flex-wrap">
        <div>
            <h1 class="text-[44px] leading-[1.05] font-bold tracking-tight text-[#1E1B4B]">
                Team
                <span class="bg-clip-text text-transparent bg-gradient-to-r from-[#7C3AED] to-[#A855F7]">Management</span>
            </h1>
            <p class="mt-3 text-[15px] text-slate-500 max-w-2xl">
                Pantau kapasitas, alokasi, dan kemampuan tim Avatech. Sistem Workload mendeteksi risiko burnout otomatis.
            </p>
            <p class="mt-2 text-[12px] text-slate-400 max-w-2xl">
                Team Load menunjukkan estimasi alokasi jam/minggu untuk membantu pembagian beban kerja, bukan batas kerja mutlak.
            </p>
        </div>
        <div class="flex items-center gap-2 flex-wrap">
            <button type="button" data-export-team class="inline-flex items-center gap-2 h-12 px-4 rounded-xl border border-violet-100 bg-white text-[13.5px] font-semibold text-slate-600 hover:border-violet-300 hover:text-violet-700 transition cursor-pointer">
                <x-heroicon-o-arrow-down-tray class="w-4 h-4" />
                Export
            </button>
            <button type="button" data-create-trigger="team" class="inline-flex items-center gap-2 h-12 px-5 rounded-xl bg-gradient-to-r from-[#7C3AED] via-[#A855F7] to-[#C084FC] text-white font-semibold text-[14px] shadow-[0_4px_14px_rgba(124,58,237,0.35)] hover:shadow-[0_6px_20px_rgba(124,58,237,0.45)] hover:-translate-y-0.5 transition-all cursor-pointer">
                <x-heroicon-o-user-plus class="w-5 h-5" />
                Tambah Anggota
            </button>
        </div>
    </section>

    <section class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-8">
        @foreach ($stats as $s)
            <div class="bg-white rounded-2xl border border-violet-100 shadow-[0_2px_8px_rgba(124,58,237,0.08)] p-5">
                <div class="flex items-center gap-2 mb-2">
                    <span class="w-2 h-2 rounded-full" style="background: {{ $s['color'] }};"></span>
                    <span class="text-[11px] font-bold tracking-wider uppercase text-slate-500">{{ $s['label'] }}</span>
                </div>
                <div class="flex items-baseline gap-1">
                    <span class="text-[28px] font-bold text-[#1E1B4B] tabular-nums">{{ $s['value'] }}</span>
                    @if ($s['suffix'])
                        <span class="text-[14px] font-semibold text-slate-400">{{ $s['suffix'] }}</span>
                    @endif
                </div>
            </div>
        @endforeach
    </section>

    @if ($overloadedMembers->isNotEmpty())
        @php
            $primaryOverloaded = $overloadedMembers->first();
            $extraOverloaded = $overloadedMembers->slice(1, 1)->first();
        @endphp
        <section class="relative overflow-hidden rounded-2xl p-6 mb-8 text-white bg-gradient-to-r from-violet-600 via-fuchsia-600 to-pink-600 shadow-[0_8px_24px_rgba(124,58,237,0.18)]">
            <div class="absolute -top-10 -right-10 w-48 h-48 rounded-full bg-white/10 pointer-events-none"></div>
            <div class="absolute top-12 right-32 w-24 h-24 rounded-full bg-white/5 pointer-events-none"></div>
            <div class="relative flex items-start gap-5 flex-wrap">
                <div class="w-12 h-12 rounded-xl bg-white/20 backdrop-blur flex items-center justify-center flex-shrink-0">
                    <x-heroicon-o-bolt class="w-6 h-6" />
                </div>
                <div class="flex-1 min-w-[280px]">
                    <div class="flex items-center gap-2 mb-1.5 flex-wrap">
                        <span class="text-[10px] font-bold tracking-[0.18em] uppercase bg-white/20 px-2 py-1 rounded-md">WORKLOAD ALERT</span>
                        <span class="text-[11px] text-white/80">{{ $overloadedMembers->count() }} anggota &ge; 85% kapasitas</span>
                    </div>
                    <h3 class="text-[19px] font-bold leading-tight mb-1.5">
                        {{ $primaryOverloaded['name'] }}{{ $extraOverloaded ? ' & ' . $extraOverloaded['name'] : '' }} mendekati overload minggu ini
                    </h3>
                    <p class="text-[13.5px] text-white/85 leading-relaxed">
                        Beban kerja <strong>{{ $primaryOverloaded['name'] }}</strong> mencapai {{ $primaryOverloaded['load'] }}% kapasitas
                        ({{ $primaryOverloaded['load_hours'] }}h dari {{ $primaryOverloaded['capacity_hours'] }}h).
                        Pertimbangkan re-assign penugasan ke anggota dengan beban lebih rendah lewat tombol <span class="font-semibold">Atur Penugasan</span>.
                    </p>
                </div>
            </div>
        </section>
    @endif

    <section class="bg-white rounded-2xl border border-violet-100 shadow-[0_2px_8px_rgba(124,58,237,0.08)] p-5 mb-6 flex flex-wrap items-center gap-4">
        <div class="flex items-center gap-2 flex-wrap">
            @foreach ($archiveFilters as $scope => $label)
                <a
                    href="{{ route('team.index', ['archive' => $scope]) }}"
                    @class([
                        'text-[12.5px] font-semibold px-3.5 py-1.5 rounded-full transition border inline-flex items-center gap-1.5',
                        'bg-[#1E1B4B] text-white border-[#1E1B4B] shadow-sm' => $archiveScope === $scope,
                        'bg-white text-slate-600 border-violet-100 hover:border-violet-300 hover:text-violet-700' => $archiveScope !== $scope,
                    ])
                >
                    {{ $label }}
                </a>
            @endforeach
        </div>
        <span class="h-6 w-px bg-violet-100"></span>
        <div class="flex items-center gap-2 flex-wrap">
            @foreach ($filters as $idx => $f)
                @php $isFirst = $idx === 0; @endphp
                <button
                    type="button"
                    data-filter="{{ $f['id'] }}"
                    @class([
                        'js-filter-chip text-[12.5px] font-semibold px-3.5 py-1.5 rounded-full transition border inline-flex items-center gap-1.5 cursor-pointer',
                        'bg-[#1E1B4B] text-white border-[#1E1B4B] shadow-sm' => $isFirst,
                        'bg-white text-slate-600 border-violet-100 hover:border-violet-300 hover:text-violet-700' => ! $isFirst,
                    ])
                >
                    <span>{{ $f['label'] }}</span>
                    <span
                        data-count-badge
                        @class([
                            'text-[10px] font-bold px-1.5 py-0.5 rounded-full',
                            'bg-white/15 text-white' => $isFirst,
                            'bg-violet-100 text-violet-700' => ! $isFirst,
                        ])
                    >{{ $filterCounts[$f['id']] }}</span>
                </button>
            @endforeach
        </div>

        <div class="ml-auto flex items-center gap-3">
            <div class="relative">
                <select data-sort-select class="appearance-none h-10 pl-4 pr-9 rounded-xl border border-violet-100 bg-white text-[13px] text-slate-600 font-medium focus:outline-none focus:ring-2 focus:ring-violet-300 cursor-pointer">
                    <option value="load-desc">Load tertinggi</option>
                    <option value="load-asc">Load terendah</option>
                    <option value="name">Nama A-Z</option>
                    <option value="projects">Proyek aktif</option>
                </select>
                <x-heroicon-o-chevron-down class="w-4 h-4 text-slate-400 absolute right-3 top-1/2 -translate-y-1/2 pointer-events-none" />
            </div>

            <div class="p-1 bg-violet-50 rounded-xl flex items-center">
                <button type="button" data-view="grid" class="js-view-btn w-9 h-8 rounded-lg flex items-center justify-center transition bg-white text-violet-700 shadow-sm cursor-pointer">
                    <x-heroicon-o-squares-2x2 class="w-4 h-4" />
                </button>
                <button type="button" data-view="list" class="js-view-btn w-9 h-8 rounded-lg flex items-center justify-center transition text-slate-500 hover:text-slate-700 cursor-pointer">
                    <x-heroicon-o-list-bullet class="w-4 h-4" />
                </button>
            </div>
        </div>
    </section>

    <section data-view-panel="grid" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
        @foreach ($deliveryMembers as $m)
            <article
                data-role="{{ $m['role_key'] }}"
                data-load="{{ $m['load'] }}"
                data-member-id="{{ $m['id'] }}"
                data-modal-trigger
                data-search-item
                data-sort-load="{{ $m['load'] }}"
                data-sort-name="{{ $m['name'] }}"
                data-sort-projects="{{ $m['projects_active'] }}"
                data-archived="{{ $m['archived'] ? 'true' : 'false' }}"
                data-name="{{ $m['name'] }}"
                data-email="{{ $m['email'] }}"
                data-role-value="{{ $m['raw_role'] }}"
                data-phone="{{ $m['raw_phone'] }}"
                data-level="{{ $m['raw_level'] }}"
                data-skills="{{ $m['raw_skills'] }}"
                data-avatar-color="{{ $m['raw_avatar_color'] }}"
                data-update-url="{{ route('team.members.update', $m['id']) }}"
                class="group bg-white rounded-2xl border border-violet-100 shadow-[0_2px_8px_rgba(124,58,237,0.08)] hover:shadow-[0_8px_24px_rgba(124,58,237,0.12)] transition p-6 flex flex-col relative overflow-hidden cursor-pointer"
            >
                <span class="absolute top-0 left-6 right-6 h-[3px] rounded-b-full" style="background: {{ $loadFill($m['load']) }};"></span>

                <div class="flex items-start gap-4 mb-5">
                    <div class="relative flex-shrink-0">
                        <div class="w-14 h-14 rounded-2xl flex items-center justify-center text-white font-bold text-[16px] shadow-[0_2px_8px_rgba(124,58,237,0.08)]" style="background: {{ $m['avatar_color'] }};">
                            {{ $m['initials'] }}
                        </div>
                        <span class="absolute -bottom-0.5 -right-0.5 w-3 h-3 rounded-full {{ $presenceClass[$m['presence']] }} {{ $presenceRing[$m['presence']] }}"></span>
                    </div>
                    <div class="flex-1 min-w-0">
                        <h3 class="text-[16px] font-bold text-[#1E1B4B] leading-tight group-hover:text-violet-700 transition truncate">{{ $m['name'] }}</h3>
                        <div class="text-[12.5px] text-slate-500 mt-0.5 truncate">{{ $m['email'] }}</div>
                        <div class="flex items-center gap-1.5 flex-wrap mt-2">
                            <span class="inline-flex items-center text-[10.5px] font-bold tracking-wide uppercase px-2 py-1 rounded-md {{ $rolePill[$m['role_key']] }}">{{ $m['role'] }}</span>
                            @if ($m['archived'])
                                <span class="inline-flex items-center gap-1 text-[10px] font-semibold rounded-md px-2 py-1 bg-slate-100 text-slate-600">
                                    <x-heroicon-o-archive-box class="w-3 h-3" />
                                    Archived
                                </span>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="mb-4">
                    <div class="flex items-center justify-between mb-1.5">
                        <span class="text-[11px] font-semibold uppercase tracking-wider text-slate-500 inline-flex items-center gap-1.5">
                            <x-heroicon-o-bolt class="w-3 h-3" />
                            Beban Kerja
                        </span>
                        <span class="text-[13px] font-bold tabular-nums" style="color: {{ $loadFill($m['load']) }};">{{ $m['load'] }}%</span>
                    </div>
                    <div class="h-2 rounded-full bg-[#F3E8FF] overflow-hidden">
                        <div class="h-full rounded-full" style="width: {{ $m['load'] }}%; background: {{ $loadFill($m['load']) }};"></div>
                    </div>
                    <div class="text-[11.5px] text-slate-400 mt-1.5 tabular-nums">{{ $m['load_hours'] }}h / {{ $m['capacity_hours'] }}h minggu ini</div>
                    <div class="text-[10.5px] text-slate-400 mt-1 leading-tight">Estimasi alokasi mingguan, bukan batas kerja mutlak.</div>
                </div>

                <div class="flex flex-wrap gap-1.5 mb-5">
                    @foreach (array_slice($m['skills'], 0, 3) as $skill)
                        <span class="text-[10.5px] font-semibold px-2 py-1 rounded-md bg-violet-50 text-violet-700 border border-violet-100">{{ $skill }}</span>
                    @endforeach
                    @if (count($m['skills']) > 3)
                        <span class="text-[10.5px] font-semibold px-2 py-1 rounded-md bg-slate-100 text-slate-500">+{{ count($m['skills']) - 3 }}</span>
                    @endif
                </div>

                <div class="mt-auto pt-4 border-t border-violet-50 grid grid-cols-3 gap-3 text-center">
                    <div>
                        <div class="text-[18px] font-bold text-[#1E1B4B] tabular-nums">{{ $m['projects_active'] }}</div>
                        <div class="text-[10.5px] text-slate-400 font-semibold uppercase tracking-wider mt-0.5">Proyek</div>
                    </div>
                    <div class="border-x border-violet-50">
                        <div class="text-[18px] font-bold text-[#1E1B4B] tabular-nums">{{ $m['tasks_open'] }}</div>
                        <div class="text-[10.5px] text-slate-400 font-semibold uppercase tracking-wider mt-0.5">Task</div>
                    </div>
                    <div>
                        @if ($m['perf'] !== null)
                            <div class="text-[18px] font-bold tabular-nums" style="color: {{ $perfFill($m['perf']) }};">{{ $m['perf'] }}%</div>
                        @else
                            <div class="text-[12px] font-bold text-slate-400 leading-tight" title="Belum ada task yang ditugaskan">Belum ada data</div>
                        @endif
                        <div class="text-[10.5px] text-slate-400 font-semibold uppercase tracking-wider mt-0.5">Skor Task</div>
                    </div>
                </div>
                <div class="mt-4 pt-4 border-t border-violet-50 flex items-center justify-end gap-2">
                    <button type="button" data-edit-member data-no-modal class="w-9 h-9 inline-flex items-center justify-center rounded-lg border border-violet-100 text-slate-500 hover:border-violet-300 hover:text-violet-700 transition cursor-pointer" aria-label="Edit anggota">
                        <x-heroicon-o-pencil-square class="w-4 h-4" />
                    </button>
                    @if (! $m['archived'])
                        <form method="POST" action="{{ route('team.members.archive', $m['id']) }}" data-no-modal onsubmit="return confirm('Arsipkan anggota ini? Data dan penugasan tetap aman.');">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="w-9 h-9 inline-flex items-center justify-center rounded-lg border border-amber-100 text-amber-600 hover:bg-amber-50 transition cursor-pointer" aria-label="Archive anggota">
                                <x-heroicon-o-archive-box class="w-4 h-4" />
                            </button>
                        </form>
                    @else
                        <form method="POST" action="{{ route('team.members.restore', $m['id']) }}" data-no-modal>
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="w-9 h-9 inline-flex items-center justify-center rounded-lg border border-emerald-100 text-emerald-600 hover:bg-emerald-50 transition cursor-pointer" aria-label="Restore anggota">
                                <x-heroicon-o-arrow-path class="w-4 h-4" />
                            </button>
                        </form>
                    @endif
                </div>
            </article>
        @endforeach

        <div data-empty class="hidden md:col-span-2 xl:col-span-3 bg-white rounded-2xl border border-dashed border-violet-200 p-12 text-center">
            <div class="w-14 h-14 mx-auto rounded-2xl bg-violet-50 text-violet-400 flex items-center justify-center mb-3">
                <x-heroicon-o-users class="w-6 h-6" />
            </div>
            <h3 class="text-[15px] font-semibold text-[#1E1B4B]">Tidak ada anggota</h3>
            <p class="text-[13px] text-slate-500 mt-1">Coba ubah filter atau undang anggota baru.</p>
        </div>
    </section>

    <section data-view-panel="list" class="hidden bg-white rounded-2xl border border-violet-100 shadow-[0_2px_8px_rgba(124,58,237,0.08)] overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead>
                    <tr class="text-[11px] font-bold tracking-[0.1em] uppercase text-slate-400 border-b border-violet-50">
                        <th class="px-7 py-4">Anggota</th>
                        <th class="px-4 py-4">Role</th>
                        <th class="px-4 py-4 w-[200px]">Beban Kerja</th>
                        <th class="px-4 py-4">Proyek</th>
                        <th class="px-4 py-4">Task</th>
                        <th class="px-4 py-4">Skor Task</th>
                        <th class="px-7 py-4 text-right"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($deliveryMembers as $m)
                        <tr
                            data-role="{{ $m['role_key'] }}"
                            data-load="{{ $m['load'] }}"
                            data-member-id="{{ $m['id'] }}"
                            data-modal-trigger
                            data-search-item
                            data-sort-load="{{ $m['load'] }}"
                            data-sort-name="{{ $m['name'] }}"
                            data-sort-projects="{{ $m['projects_active'] }}"
                            data-archived="{{ $m['archived'] ? 'true' : 'false' }}"
                            data-name="{{ $m['name'] }}"
                            data-email="{{ $m['email'] }}"
                            data-role-value="{{ $m['raw_role'] }}"
                            data-phone="{{ $m['raw_phone'] }}"
                            data-level="{{ $m['raw_level'] }}"
                            data-skills="{{ $m['raw_skills'] }}"
                            data-avatar-color="{{ $m['raw_avatar_color'] }}"
                            data-update-url="{{ route('team.members.update', $m['id']) }}"
                            class="hover:bg-[#FAF5FF] border-b border-violet-50/60 last:border-0 transition cursor-pointer"
                        >
                            <td class="px-7 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="relative flex-shrink-0">
                                        <div class="w-9 h-9 rounded-lg flex items-center justify-center text-white font-semibold text-[12px]" style="background: {{ $m['avatar_color'] }};">
                                            {{ $m['initials'] }}
                                        </div>
                                        <span class="absolute -bottom-0.5 -right-0.5 w-2.5 h-2.5 rounded-full {{ $presenceClass[$m['presence']] }} {{ $presenceRing[$m['presence']] }}"></span>
                                    </div>
                                    <div class="min-w-0">
                                        <div class="text-[13.5px] font-semibold text-[#1E1B4B]">{{ $m['name'] }}</div>
                                        <div class="text-[12px] text-slate-500 truncate flex items-center gap-1.5">
                                            <span>{{ $m['email'] }}</span>
                                            @if ($m['archived'])
                                                <span class="inline-flex items-center text-[10px] font-semibold rounded-full px-2 py-0.5 bg-slate-100 text-slate-600">Archived</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-4">
                                <span class="inline-flex items-center text-[10.5px] font-bold tracking-wide uppercase px-2 py-1 rounded-md {{ $rolePill[$m['role_key']] }}">{{ $m['role'] }}</span>
                            </td>
                            <td class="px-4 py-4 w-[200px]">
                                <div class="flex items-center gap-2">
                                    <div class="flex-1 h-2 rounded-full bg-[#F3E8FF] overflow-hidden">
                                        <div class="h-full rounded-full" style="width: {{ $m['load'] }}%; background: {{ $loadFill($m['load']) }};"></div>
                                    </div>
                                    <span class="text-[12px] font-bold w-9 text-right tabular-nums" style="color: {{ $loadFill($m['load']) }};">{{ $m['load'] }}%</span>
                                </div>
                            </td>
                            <td class="px-4 py-4 text-[13px] font-semibold text-[#1E1B4B] tabular-nums">{{ $m['projects_active'] }}</td>
                            <td class="px-4 py-4 text-[13px] font-semibold text-[#1E1B4B] tabular-nums">{{ $m['tasks_open'] }}</td>
                            <td class="px-4 py-4 text-[13px] font-bold tabular-nums">
                                @if ($m['perf'] !== null)
                                    <span style="color: {{ $perfFill($m['perf']) }};">{{ $m['perf'] }}%</span>
                                @else
                                    <span class="text-slate-400" title="Belum ada task yang ditugaskan">Belum ada data</span>
                                @endif
                            </td>
                            <td class="px-7 py-4 text-right">
                                <button type="button" data-edit-member data-no-modal class="inline-flex items-center text-violet-600 mr-3 cursor-pointer" aria-label="Edit anggota">
                                    <x-heroicon-o-pencil-square class="w-4 h-4" />
                                </button>
                                @if (! $m['archived'])
                                    <form method="POST" action="{{ route('team.members.archive', $m['id']) }}" data-no-modal class="inline-flex mr-3" onsubmit="return confirm('Arsipkan anggota ini? Data dan penugasan tetap aman.');">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="inline-flex items-center text-amber-600 cursor-pointer" aria-label="Archive anggota">
                                            <x-heroicon-o-archive-box class="w-4 h-4" />
                                        </button>
                                    </form>
                                @else
                                    <form method="POST" action="{{ route('team.members.restore', $m['id']) }}" data-no-modal class="inline-flex mr-3">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="inline-flex items-center text-emerald-600 cursor-pointer" aria-label="Restore anggota">
                                            <x-heroicon-o-arrow-path class="w-4 h-4" />
                                        </button>
                                    </form>
                                @endif
                                <x-heroicon-o-chevron-right class="w-4 h-4 text-slate-400 inline-block" />
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>

    {{-- Static UI placeholder values: skills, perf score, presence, hours, allocations, activities, and permissions are display-only mockup data. --}}

    {{-- =============== MEMBER DETAIL MODAL =============== --}}
    <div data-tm-modal class="hidden fixed inset-0 z-50 items-center justify-center p-4 sm:p-6" role="dialog" aria-modal="true">
        <div data-tm-overlay class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm"></div>

        <div data-tm-panel class="relative bg-white rounded-3xl shadow-[0_24px_64px_rgba(124,58,237,0.18)] w-full max-w-[860px] max-h-[90vh] flex flex-col overflow-hidden border border-violet-100">
            @foreach ($deliveryMembers as $m)
                <div data-member-content="{{ $m['id'] }}" class="hidden flex-col flex-1 min-h-0">

                    {{-- Hero --}}
                    <div class="relative px-7 sm:px-8 pt-8 pb-6 bg-gradient-to-br from-violet-100 via-fuchsia-50 to-white">
                        <button type="button" data-modal-close class="absolute top-5 right-5 w-9 h-9 rounded-xl hover:bg-white/70 text-slate-500 hover:text-violet-700 flex items-center justify-center transition cursor-pointer" aria-label="Tutup">
                            <x-heroicon-o-x-mark class="w-5 h-5" />
                        </button>

                        <div class="flex items-start gap-5">
                            <div class="relative flex-shrink-0">
                                <div class="w-20 h-20 rounded-2xl flex items-center justify-center text-white font-bold text-[24px] shadow-[0_4px_14px_rgba(124,58,237,0.18)]" style="background: {{ $m['avatar_color'] }};">
                                    {{ $m['initials'] }}
                                </div>
                                <span class="absolute -bottom-1 -right-1 w-4 h-4 rounded-full {{ $presenceClass[$m['presence']] }} {{ $presenceRing[$m['presence']] }}"></span>
                            </div>
                            <div class="flex-1 min-w-0 pt-1">
                                <div class="flex items-center gap-2 flex-wrap mb-2">
                                    <span class="inline-flex items-center text-[10.5px] font-bold tracking-wide uppercase px-2 py-1 rounded-md {{ $rolePill[$m['role_key']] }}">{{ $m['role'] }}</span>
                                    @if ($m['archived'])
                                        <span class="inline-flex items-center gap-1 text-[10.5px] font-semibold rounded-md px-2 py-1 bg-slate-100 text-slate-600">
                                            <x-heroicon-o-archive-box class="w-3 h-3" />
                                            Archived
                                        </span>
                                    @endif
                                    <span class="text-[11.5px] text-slate-500 inline-flex items-center gap-1">
                                        <x-heroicon-o-map-pin class="w-3 h-3" />
                                        {{ $m['location'] }}
                                    </span>
                                    <span class="text-[11.5px] text-slate-500 inline-flex items-center gap-1">
                                        <x-heroicon-o-calendar class="w-3 h-3" />
                                        Bergabung {{ $m['join_date'] }}
                                    </span>
                                </div>
                                <h2 class="text-[26px] font-bold text-[#1E1B4B] leading-tight">{{ $m['name'] }}</h2>
                                <div class="text-[13px] text-slate-500 mt-1 truncate">{{ $m['email'] }}</div>
                            </div>
                        </div>

                        {{-- Tabs --}}
                        <div class="mt-6 flex items-center gap-2 flex-wrap">
                            @php $tmTabs = [['id' => 'profile', 'label' => 'Profil & Skill'], ['id' => 'load', 'label' => 'Beban Kerja'], ['id' => 'activity', 'label' => 'Aktivitas'], ['id' => 'access', 'label' => 'Role & Akses']]; @endphp
                            @foreach ($tmTabs as $idx => $t)
                                <button
                                    type="button"
                                    data-tm-tab="{{ $t['id'] }}"
                                    @class([
                                        'tm-tab h-10 px-5 rounded-xl text-[13px] font-semibold transition cursor-pointer',
                                        'is-active' => $idx === 0,
                                        'text-slate-500 hover:bg-white/70 hover:text-violet-700' => $idx !== 0,
                                    ])
                                >
                                    {{ $t['label'] }}
                                </button>
                            @endforeach
                        </div>
                    </div>

                    {{-- Body --}}
                    <div class="flex-1 overflow-y-auto px-7 sm:px-8 py-6">

                        {{-- Profil & Skill --}}
                        <div data-tm-panel="profile">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-6">
                                <div class="rounded-2xl border border-violet-100 p-5">
                                    <h4 class="text-[11px] font-bold tracking-wider uppercase text-slate-400 mb-3">Info Kontak</h4>
                                    <div class="space-y-3">
                                        <div class="flex items-center gap-3">
                                            <x-heroicon-o-envelope class="w-4 h-4 text-violet-600 flex-shrink-0" />
                                            @if (! empty($m['email']))
                                                <a href="{{ $m['email_link'] }}" target="_blank" rel="noopener" class="text-[13.5px] text-[#1E1B4B] truncate hover:text-violet-700 transition">{{ $m['email'] }}</a>
                                            @else
                                                <span class="text-[13.5px] text-slate-400 italic" title="Email anggota tidak tersedia">Email belum tersedia</span>
                                            @endif
                                        </div>
                                        <div class="flex items-center gap-3">
                                            <x-heroicon-o-phone class="w-4 h-4 text-violet-600 flex-shrink-0" />
                                            @if (! empty($m['phone']))
                                                <a href="{{ $m['wa_link'] }}" target="_blank" rel="noopener" class="text-[13.5px] text-[#1E1B4B] hover:text-emerald-600 transition">{{ $m['phone'] }}</a>
                                            @else
                                                <span class="text-[13.5px] text-slate-400 italic" title="Nomor WhatsApp anggota tidak tersedia">Nomor belum tersedia</span>
                                            @endif
                                        </div>
                                        <div class="flex items-center gap-3">
                                            <x-heroicon-o-briefcase class="w-4 h-4 text-violet-600 flex-shrink-0" />
                                            <span class="text-[13.5px] text-[#1E1B4B]">{{ $m['level'] }} &middot; {{ $m['tenure'] }}</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="rounded-2xl border border-violet-100 p-5">
                                    <h4 class="text-[11px] font-bold tracking-wider uppercase text-slate-400 mb-3">Skor Task</h4>
                                    @if ($m['perf'] !== null)
                                        <div class="flex items-baseline gap-2 mb-2">
                                            <span class="text-[40px] font-bold leading-none tabular-nums" style="color: {{ $perfFill($m['perf']) }};">{{ $m['perf'] }}</span>
                                            <span class="text-[14px] font-semibold text-slate-400">%</span>
                                        </div>
                                        <div class="h-2 rounded-full bg-[#F3E8FF] overflow-hidden">
                                            <div class="h-full rounded-full" style="width: {{ $m['perf'] }}%; background: {{ $perfFill($m['perf']) }};"></div>
                                        </div>
                                        <div class="text-[11.5px] text-slate-500 mt-2.5 leading-relaxed">
                                            {{ $m['perf_label'] }}. Skor ini terpisah dari beban kerja mingguan.
                                        </div>
                                    @else
                                        <div class="flex items-baseline gap-2 mb-2">
                                            <span class="text-[18px] font-bold leading-tight text-slate-400">Belum ada data</span>
                                        </div>
                                        <div class="h-2 rounded-full bg-slate-100 overflow-hidden"></div>
                                        <div class="text-[11.5px] text-slate-500 mt-2.5 leading-relaxed">
                                            Skor task akan muncul saat anggota memiliki task yang ditugaskan.
                                        </div>
                                    @endif
                                </div>
                            </div>

                            <div class="rounded-2xl border border-violet-100 p-5 mb-6">
                                <h4 class="text-[11px] font-bold tracking-wider uppercase text-slate-400 mb-3">Kemampuan &amp; Stack</h4>
                                <div class="flex flex-wrap gap-2">
                                    @foreach ($m['skills'] as $skill)
                                        <span class="text-[12px] font-semibold px-3 py-1.5 rounded-lg bg-violet-50 text-violet-700 border border-violet-100">{{ $skill }}</span>
                                    @endforeach
                                </div>
                            </div>

                            <div class="rounded-2xl border border-violet-100 p-5">
                                <h4 class="text-[11px] font-bold tracking-wider uppercase text-slate-400 mb-3">Bio Singkat</h4>
                                <p class="text-[13.5px] text-slate-600 leading-relaxed">{{ $m['bio'] }}</p>
                            </div>
                        </div>

                        {{-- Beban Kerja --}}
                        <div data-tm-panel="load" class="hidden">
                            <p class="mb-4 text-[12px] text-slate-400 leading-relaxed">
                                Team Load menunjukkan estimasi alokasi jam/minggu untuk membantu pembagian beban kerja, bukan batas kerja mutlak.
                            </p>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-5">
                                <div class="rounded-2xl border border-violet-100 p-5">
                                    <div class="text-[11px] font-bold tracking-wider uppercase text-slate-400 mb-2">Kapasitas Minggu Ini</div>
                                    <div class="flex items-baseline gap-1">
                                        <span class="text-[32px] font-bold text-[#1E1B4B] tabular-nums">{{ $m['load_hours'] }}</span>
                                        <span class="text-[16px] font-semibold text-slate-400 tabular-nums">/ {{ $m['capacity_hours'] }}h</span>
                                    </div>
                                    <div class="mt-3 h-2 rounded-full bg-[#F3E8FF] overflow-hidden">
                                        <div class="h-full rounded-full" style="width: {{ $m['load'] }}%; background: {{ $loadFill($m['load']) }};"></div>
                                    </div>
                                </div>
                                <div class="rounded-2xl border border-violet-100 p-5">
                                    <div class="text-[11px] font-bold tracking-wider uppercase text-slate-400 mb-2">Proyek Aktif</div>
                                    <div class="text-[32px] font-bold text-[#1E1B4B] tabular-nums">{{ $m['projects_active'] }}</div>
                                    <div class="text-[11.5px] text-slate-500 mt-2.5">
                                        @if ($m['projects_active'] > 0)
                                            Dari <span class="font-semibold text-[#1E1B4B] tabular-nums">{{ $m['projects_active'] }}</span> proyek aktif
                                        @else
                                            Belum ada proyek aktif
                                        @endif
                                    </div>
                                </div>
                                <div class="rounded-2xl border border-violet-100 p-5">
                                    <div class="text-[11px] font-bold tracking-wider uppercase text-slate-400 mb-2">Task Terbuka</div>
                                    <div class="text-[32px] font-bold text-[#1E1B4B] tabular-nums">{{ $m['tasks_open'] }}</div>
                                    <div class="text-[11.5px] text-slate-500 mt-2.5"><span class="font-semibold text-emerald-600 tabular-nums">{{ $m['tasks_done'] }}</span> selesai bulan ini</div>
                                </div>
                            </div>

                            <div class="rounded-2xl border border-violet-100 overflow-hidden">
                                <div class="px-5 py-4 border-b border-violet-50 flex items-center justify-between">
                                    <h4 class="text-[11px] font-bold tracking-wider uppercase text-violet-600">Alokasi per Proyek</h4>
                                    <span class="text-[12px] text-slate-400">{{ count($m['allocations']) }} proyek</span>
                                </div>
                                <div class="divide-y divide-violet-50">
                                    @forelse ($m['allocations'] as $a)
                                        <div class="px-5 py-4 flex items-center gap-4">
                                            <div class="w-10 h-10 rounded-lg flex items-center justify-center text-white font-bold text-[11px] flex-shrink-0" style="background: {{ $a['color'] }};">{{ $a['code'] }}</div>
                                            <div class="flex-1 min-w-0">
                                                <div class="text-[13.5px] font-semibold text-[#1E1B4B] truncate">{{ $a['name'] }}</div>
                                                <div class="text-[11.5px] text-slate-500 mt-0.5">{{ $a['role'] }}</div>
                                            </div>
                                            <div class="w-[180px] hidden sm:block">
                                                <div class="flex items-center gap-2">
                                                    <div class="flex-1 h-2 rounded-full bg-[#F3E8FF] overflow-hidden">
                                                        <div class="h-full rounded-full bg-violet-500" style="width: {{ $a['pct'] }}%;"></div>
                                                    </div>
                                                    <span class="text-[12px] font-bold text-[#1E1B4B] w-9 text-right tabular-nums">{{ $a['pct'] }}%</span>
                                                </div>
                                            </div>
                                            <div class="text-[12px] font-semibold text-slate-500 w-16 text-right tabular-nums">{{ $a['hours'] }}h</div>
                                        </div>
                                    @empty
                                        <div class="px-5 py-6 text-[12.5px] text-slate-400 text-center">Belum ada alokasi.</div>
                                    @endforelse
                                </div>
                            </div>
                        </div>

                        {{-- Aktivitas --}}
                        <div data-tm-panel="activity" class="hidden">
                            <div class="space-y-4">
                                @foreach ($m['activities'] as $a)
                                    <div class="flex gap-4">
                                        <div class="w-9 h-9 rounded-lg flex items-center justify-center flex-shrink-0" style="background: {{ $a['bg'] }}; color: {{ $a['color'] }};">
                                            <x-dynamic-component :component="'heroicon-o-' . $a['icon']" class="w-4 h-4" />
                                        </div>
                                        <div class="flex-1 min-w-0 pt-0.5">
                                            <p class="text-[13.5px] text-[#1E1B4B] leading-snug">{!! $a['text'] !!}</p>
                                            <div class="text-[11.5px] text-slate-400 mt-1">{{ $a['time'] }}</div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        {{-- Role & Akses (read-only — derived from role) --}}
                        <div data-tm-panel="access" class="hidden">
                            <div class="rounded-2xl border border-violet-100 p-5 mb-5">
                                <div class="flex items-center justify-between gap-3 mb-3">
                                    <h4 class="text-[11px] font-bold tracking-wider uppercase text-slate-400">Level Akses Sistem</h4>
                                    <span class="inline-flex items-center gap-1 text-[10.5px] font-semibold rounded-md px-2 py-1 bg-slate-100 text-slate-600">
                                        <x-heroicon-o-lock-closed class="w-3 h-3" />
                                        Read-only
                                    </span>
                                </div>
                                <div class="space-y-2.5">
                                    @foreach ($m['permissions'] as $p)
                                        <div class="flex items-center justify-between gap-4 py-2 border-b border-violet-50 last:border-0">
                                            <div class="min-w-0">
                                                <div class="text-[13.5px] font-semibold text-[#1E1B4B] truncate">{{ $p['name'] }}</div>
                                                <div class="text-[11.5px] text-slate-500">{{ $p['desc'] }}</div>
                                            </div>
                                            <span class="inline-flex items-center text-[10.5px] font-bold tracking-wide uppercase px-2.5 py-1 rounded-md flex-shrink-0 {{ $accessLevelPill[$p['level']] ?? 'bg-slate-100 text-slate-500' }}">{{ $p['level'] }}</span>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                            <div class="rounded-2xl border border-violet-100 bg-violet-50/40 p-5 flex items-start gap-3">
                                <x-heroicon-o-information-circle class="w-5 h-5 text-violet-600 mt-0.5 flex-shrink-0" />
                                <div>
                                    <div class="text-[13.5px] font-bold text-[#1E1B4B]">Akses mengikuti role</div>
                                    <p class="text-[12.5px] text-slate-600 mt-1">Daftar akses di atas otomatis mengikuti role anggota. Untuk mengubah akses, ubah role lewat tombol <span class="font-semibold text-violet-700">Edit Anggota</span>.</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Footer (per-member: last_active + action buttons) --}}
                    <div class="px-7 sm:px-8 py-5 border-t border-violet-100 bg-violet-50/30 flex items-center justify-between gap-3 flex-wrap">
                        <div class="text-[12.5px] text-slate-500 inline-flex items-center gap-2">
                            <x-heroicon-o-clock class="w-4 h-4 text-violet-500" />
                            Aktivitas terakhir: <span class="font-semibold text-[#1E1B4B]">{{ $m['last_active'] }}</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <button type="button" data-edit-member data-member-id="{{ $m['id'] }}" class="inline-flex items-center gap-2 h-10 px-4 rounded-xl border border-violet-200 bg-white text-[13px] font-semibold text-slate-600 hover:border-violet-400 hover:text-violet-700 transition cursor-pointer">
                                <x-heroicon-o-pencil-square class="w-4 h-4" />
                                Edit
                            </button>
                            @if (! empty($m['phone']))
                                <a href="{{ $m['wa_link'] }}" target="_blank" rel="noopener" class="inline-flex items-center gap-2 h-10 px-4 rounded-xl border border-violet-200 bg-white text-[13px] font-semibold text-slate-600 hover:border-emerald-400 hover:text-emerald-700 transition cursor-pointer">
                                    <x-heroicon-o-chat-bubble-left-right class="w-4 h-4" />
                                    Kirim Pesan
                                </a>
                            @else
                                <button type="button" disabled aria-disabled="true" title="Nomor WhatsApp anggota tidak tersedia" class="inline-flex items-center gap-2 h-10 px-4 rounded-xl border border-violet-100 bg-slate-50 text-[13px] font-semibold text-slate-400 cursor-not-allowed">
                                    <x-heroicon-o-chat-bubble-left-right class="w-4 h-4" />
                                    Kirim Pesan
                                </button>
                            @endif
                            <button type="button" data-assign-trigger data-assign-id="{{ $m['id'] }}" data-assign-name="{{ $m['name'] }}" class="inline-flex items-center gap-2 h-10 px-4 rounded-xl bg-gradient-to-r from-[#7C3AED] via-[#A855F7] to-[#C084FC] text-white font-semibold text-[13px] shadow-[0_2px_8px_rgba(124,58,237,0.2)] hover:scale-[1.02] transition cursor-pointer">
                                <x-heroicon-o-user class="w-4 h-4" />
                                Atur Penugasan
                            </button>
                            @if (! $m['archived'])
                                <form method="POST" action="{{ route('team.members.archive', $m['id']) }}" onsubmit="return confirm('Arsipkan anggota ini? Data dan penugasan tetap aman.');">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="inline-flex items-center gap-2 h-10 px-4 rounded-xl border border-amber-100 text-amber-600 hover:bg-amber-50 text-[13px] font-semibold transition cursor-pointer">
                                        <x-heroicon-o-archive-box class="w-4 h-4" />
                                        Archive
                                    </button>
                                </form>
                            @else
                                <form method="POST" action="{{ route('team.members.restore', $m['id']) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="inline-flex items-center gap-2 h-10 px-4 rounded-xl border border-emerald-100 text-emerald-600 hover:bg-emerald-50 text-[13px] font-semibold transition cursor-pointer">
                                        <x-heroicon-o-arrow-path class="w-4 h-4" />
                                        Restore
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <script>
        window.__teamCsvMap = @json(collect($deliveryMembers)->keyBy('id'));
    </script>

    <script>
        (function () {
            const wire = () => {
                const chips = document.querySelectorAll('.js-filter-chip');
                const empty = document.querySelector('[data-empty]');

                const matches = (el, id) => {
                    if (id === 'all') return true;
                    if (id === 'risk') return parseInt(el.dataset.load || '0', 10) >= 85;
                    return el.dataset.role === id;
                };

                const applyFilter = (id) => {
                    const items = document.querySelectorAll('[data-role]'); // live query so user-added cards are included
                    chips.forEach(c => {
                        const active = c.dataset.filter === id;
                        c.classList.toggle('bg-[#1E1B4B]', active);
                        c.classList.toggle('text-white', active);
                        c.classList.toggle('border-[#1E1B4B]', active);
                        c.classList.toggle('shadow-sm', active);
                        c.classList.toggle('bg-white', !active);
                        c.classList.toggle('text-slate-600', !active);
                        c.classList.toggle('border-violet-100', !active);
                        c.classList.toggle('hover:border-violet-300', !active);
                        c.classList.toggle('hover:text-violet-700', !active);

                        const badge = c.querySelector('[data-count-badge]');
                        if (badge) {
                            badge.classList.toggle('bg-white/15', active);
                            badge.classList.toggle('text-white', active);
                            badge.classList.toggle('bg-violet-100', !active);
                            badge.classList.toggle('text-violet-700', !active);
                        }
                    });

                    let visible = 0;
                    items.forEach(el => {
                        const show = matches(el, id);
                        el.classList.toggle('hidden', !show);
                        if (show) visible++;
                    });

                    if (empty) empty.classList.toggle('hidden', visible > 0);
                };

                chips.forEach(c => {
                    c.addEventListener('click', () => applyFilter(c.dataset.filter));
                });

                const viewBtns = document.querySelectorAll('.js-view-btn');
                const viewPanels = document.querySelectorAll('[data-view-panel]');

                viewBtns.forEach(btn => {
                    btn.addEventListener('click', () => {
                        const v = btn.dataset.view;
                        viewBtns.forEach(b => {
                            const active = b.dataset.view === v;
                            b.classList.toggle('bg-white', active);
                            b.classList.toggle('text-violet-700', active);
                            b.classList.toggle('shadow-sm', active);
                            b.classList.toggle('text-slate-500', !active);
                            b.classList.toggle('hover:text-slate-700', !active);
                        });
                        viewPanels.forEach(p => {
                            p.classList.toggle('hidden', p.dataset.viewPanel !== v);
                        });
                    });
                });

                /* Sort */
                const sortSel = document.querySelector('[data-sort-select]');
                if (sortSel) {
                    const cfg = {
                        'load-desc': { key: 'sortLoad',     dir: -1, num: true  },
                        'load-asc':  { key: 'sortLoad',     dir:  1, num: true  },
                        'name':      { key: 'sortName',     dir:  1, num: false },
                        'projects':  { key: 'sortProjects', dir: -1, num: true  },
                    };
                    const sortContainer = (selector) => {
                        const container = document.querySelector(selector);
                        if (! container) return;
                        const c = cfg[sortSel.value];
                        if (! c) return;
                        const all = Array.from(container.children);
                        const sortable = all.filter(el => el.matches('[data-sort-load]'));
                        const rest     = all.filter(el => ! el.matches('[data-sort-load]'));
                        sortable.sort((a, b) => {
                            if (c.num) {
                                return c.dir * (parseFloat(a.dataset[c.key] || 0) - parseFloat(b.dataset[c.key] || 0));
                            }
                            return c.dir * (a.dataset[c.key] || '').localeCompare(b.dataset[c.key] || '');
                        });
                        [...sortable, ...rest].forEach(el => container.appendChild(el));
                    };
                    sortSel.addEventListener('change', () => {
                        sortContainer('[data-view-panel="grid"]');
                        sortContainer('[data-view-panel="list"] tbody');
                    });
                }

                /* === Export CSV === */
                const exportBtn = document.querySelector('[data-export-team]');
                if (exportBtn) {
                    exportBtn.addEventListener('click', () => {
                        const csvCell = (v) => '"' + String(v ?? '').replace(/"/g, '""') + '"';
                        const rows = [['ID','Name','Email','Role','Level','Tenure','Load %','Load Hours','Capacity Hours','Active Projects','Open Tasks','Task Score %','Presence','Skills']];
                        document.querySelectorAll('[data-view-panel="grid"] [data-member-id]').forEach(card => {
                            const id = card.dataset.memberId;
                            const m = window.__teamCsvMap?.[id];
                            if (! m) return;
                            rows.push([m.id, m.name, m.email, m.role, m.level, m.tenure, m.load, m.load_hours, m.capacity_hours, m.projects_active, m.tasks_open, m.perf, m.presence, (m.skills || []).join('; ')]);
                        });
                        const csv = rows.map(r => r.map(csvCell).join(',')).join('\r\n');
                        window.downloadFile && window.downloadFile('team-' + new Date().toISOString().slice(0,10) + '.csv', csv, 'text/csv;charset=utf-8');
                        window.toast && window.toast('CSV tim diunduh (' + (rows.length - 1) + ' anggota).');
                    });
                }

                /* === Member detail modal === */
                const modal    = document.querySelector('[data-tm-modal]');
                const overlay  = modal?.querySelector('[data-tm-overlay]');
                const panel    = modal?.querySelector('[data-tm-panel]');

                const resetTabs = (contentEl) => {
                    contentEl.querySelectorAll('[data-tm-tab]').forEach(t => {
                        const active = t.dataset.tmTab === 'profile';
                        t.classList.toggle('is-active', active);
                        t.classList.toggle('text-slate-500', !active);
                        t.classList.toggle('hover:bg-white/70', !active);
                        t.classList.toggle('hover:text-violet-700', !active);
                    });
                    contentEl.querySelectorAll('[data-tm-panel]').forEach(p => {
                        p.classList.toggle('hidden', p.dataset.tmPanel !== 'profile');
                    });
                };

                const openMemberModal = (id) => {
                    if (! modal) return;
                    /* live query — dynamic blocks may be injected by renderCard */
                    const contents = modal.querySelectorAll('[data-member-content]');
                    let matched = null;
                    contents.forEach(el => {
                        const isThis = el.dataset.memberContent === String(id);
                        el.classList.toggle('hidden', ! isThis);
                        el.classList.toggle('flex', isThis);
                        if (isThis) matched = el;
                    });
                    if (! matched) return;
                    resetTabs(matched);
                    modal.classList.remove('hidden');
                    modal.classList.add('flex');
                    document.body.style.overflow = 'hidden';
                };

                const closeMemberModal = () => {
                    if (! modal) return;
                    modal.classList.add('hidden');
                    modal.classList.remove('flex');
                    document.body.style.overflow = '';
                };

                const switchTab = (contentEl, tabId) => {
                    contentEl.querySelectorAll('[data-tm-tab]').forEach(t => {
                        const active = t.dataset.tmTab === tabId;
                        t.classList.toggle('is-active', active);
                        t.classList.toggle('text-slate-500', !active);
                        t.classList.toggle('hover:bg-white/70', !active);
                        t.classList.toggle('hover:text-violet-700', !active);
                    });
                    contentEl.querySelectorAll('[data-tm-panel]').forEach(p => {
                        p.classList.toggle('hidden', p.dataset.tmPanel !== tabId);
                    });
                };

                /* Delegated trigger click — catches both seeded and dynamic cards */
                document.addEventListener('click', (e) => {
                    if (e.target.closest('[data-no-modal]')) return;
                    const trigger = e.target.closest('[data-modal-trigger][data-member-id]');
                    if (! trigger) return;
                    openMemberModal(trigger.dataset.memberId);
                });

                /* Delegated tab + close — attached to panel (not modal) because the panel
                   stops click propagation below to prevent overlay-close, which also
                   blocks any modal-level delegation. */
                if (panel) {
                    panel.addEventListener('click', (e) => {
                        const closeBtn = e.target.closest('[data-modal-close]');
                        if (closeBtn) { closeMemberModal(); return; }
                        const tab = e.target.closest('[data-tm-tab]');
                        if (tab) {
                            const contentEl = tab.closest('[data-member-content]');
                            if (contentEl) switchTab(contentEl, tab.dataset.tmTab);
                        }
                    });
                }

                /* === Auto-open member modal from ?open=member:{id} === */
                try {
                    const params = new URLSearchParams(window.location.search);
                    const op = params.get('open');
                    if (op && op.startsWith('member:')) {
                        const id = op.split(':')[1];
                        if (id) openMemberModal(id);
                    }
                } catch (e) {}

                if (panel) panel.addEventListener('click', (e) => e.stopPropagation());
                if (overlay) overlay.addEventListener('click', closeMemberModal);
                /* close buttons handled by delegated modal click handler above */
                document.addEventListener('keydown', (e) => {
                    if (e.key === 'Escape' && modal && ! modal.classList.contains('hidden')) closeMemberModal();
                });
            };

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', wire);
            } else {
                wire();
            }
        })();
    </script>

    {{-- ===== Atur Penugasan Modal (shared across seeded + dynamic members) ===== --}}
    <div data-assign-modal class="hidden fixed inset-0 z-50 items-center justify-center p-4 sm:p-6" role="dialog" aria-modal="true" aria-labelledby="assign-modal-title">
        <div data-assign-overlay class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm"></div>
        <form method="POST" action="#" data-assign-panel data-assign-form class="relative bg-white rounded-3xl shadow-[0_24px_64px_rgba(124,58,237,0.18)] w-full max-w-lg max-h-[90vh] flex flex-col overflow-hidden border border-violet-100">
            @csrf
            <input type="hidden" name="_method" value="PUT" data-assign-method disabled>
            <div class="px-6 py-4 border-b border-violet-100 flex items-center justify-between gap-3">
                <div class="min-w-0">
                    <h3 id="assign-modal-title" class="text-[16px] font-bold text-[#1E1B4B] leading-tight">Atur Penugasan</h3>
                    <p class="text-[12px] text-slate-500 mt-0.5">Untuk <span data-assign-member-name class="font-semibold text-violet-700">—</span></p>
                </div>
                <button type="button" data-assign-close aria-label="Tutup" class="w-9 h-9 rounded-full hover:bg-violet-50 flex items-center justify-center text-slate-500 hover:text-rose-500 transition cursor-pointer flex-shrink-0">
                    <x-heroicon-o-x-mark class="w-5 h-5" />
                </button>
            </div>
            <div class="px-6 py-5 overflow-y-auto space-y-5">
                <section>
                    <h4 class="text-[11px] font-bold tracking-wider uppercase text-slate-400 mb-2">Penugasan Saat Ini</h4>
                    <div data-assign-current class="space-y-2"></div>
                </section>
                <section>
                    <h4 class="text-[11px] font-bold tracking-wider uppercase text-slate-400 mb-2">Riwayat Selesai</h4>
                    <div data-assign-completed class="space-y-2"></div>
                </section>
                <section class="rounded-2xl border border-dashed border-violet-200 bg-violet-50/30 p-4">
                    <h4 data-assign-form-title class="text-[11px] font-bold tracking-wider uppercase text-violet-700 mb-3">Tambah Penugasan Baru</h4>
                    <div class="space-y-3">
                        <div>
                            <label class="block text-[11px] font-bold tracking-wider uppercase text-slate-500 mb-1.5">Proyek</label>
                            <select name="project_id" data-assign-project class="w-full h-10 rounded-lg border border-violet-100 px-3 text-[13px] focus:outline-none focus:ring-2 focus:ring-violet-300">
                                <option value="">— Pilih proyek —</option>
                                @foreach ($projects as $project)
                                    <option value="{{ $project->id }}">{{ $project->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold tracking-wider uppercase text-slate-500 mb-1.5">Ringkasan / Peran</label>
                            <input name="title" data-assign-title type="text" placeholder="mis. Review flow onboarding" class="w-full h-10 rounded-lg border border-violet-100 px-3 text-[13px] focus:outline-none focus:ring-2 focus:ring-violet-300" />
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-[11px] font-bold tracking-wider uppercase text-slate-500 mb-1.5">Tipe</label>
                                <select name="type" data-assign-type class="w-full h-10 rounded-lg border border-violet-100 px-3 text-[13px] focus:outline-none focus:ring-2 focus:ring-violet-300">
                                    <option value="task">Task</option>
                                    <option value="review">Review</option>
                                    <option value="support">Support</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-[11px] font-bold tracking-wider uppercase text-slate-500 mb-1.5">Status</label>
                                <select name="status" data-assign-status class="w-full h-10 rounded-lg border border-violet-100 px-3 text-[13px] focus:outline-none focus:ring-2 focus:ring-violet-300">
                                    <option value="planned">Planned</option>
                                    <option value="in_progress">In Progress</option>
                                    <option value="done">Done</option>
                                </select>
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-[11px] font-bold tracking-wider uppercase text-slate-500 mb-1.5">Due Date</label>
                                <input name="due_date" data-assign-due type="date" class="w-full h-10 rounded-lg border border-violet-100 px-3 text-[13px] focus:outline-none focus:ring-2 focus:ring-violet-300" />
                            </div>
                            <div>
                                <label class="block text-[11px] font-bold tracking-wider uppercase text-slate-500 mb-1.5">Estimasi Jam / Minggu</label>
                                <input name="estimated_hours" data-assign-hours type="number" min="0" max="200" step="1" placeholder="0" class="w-full h-10 rounded-lg border border-violet-100 px-3 text-[13px] focus:outline-none focus:ring-2 focus:ring-violet-300" />
                            </div>
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold tracking-wider uppercase text-slate-500 mb-1.5">Catatan</label>
                            <textarea name="notes" data-assign-notes rows="3" class="w-full rounded-lg border border-violet-100 px-3 py-2 text-[13px] focus:outline-none focus:ring-2 focus:ring-violet-300" placeholder="Catatan singkat untuk konteks penugasan"></textarea>
                        </div>
                    </div>
                </section>
            </div>
            <div class="px-6 py-3 border-t border-violet-100 bg-violet-50/30 flex items-center justify-end gap-2 flex-shrink-0">
                <button type="button" data-assign-reset class="hidden h-9 px-4 rounded-lg border border-violet-200 bg-white text-[12.5px] font-semibold text-violet-700 hover:border-violet-400 transition cursor-pointer">Tambah Baru</button>
                <button type="button" data-assign-close class="h-9 px-4 rounded-lg text-[12.5px] font-semibold text-slate-500 hover:text-slate-700 transition cursor-pointer">Batal</button>
                <button type="submit" class="inline-flex items-center gap-2 h-9 px-4 rounded-lg bg-gradient-to-r from-[#7C3AED] via-[#A855F7] to-[#C084FC] text-white font-semibold text-[12.5px] shadow-[0_2px_8px_rgba(124,58,237,0.2)] hover:scale-[1.02] transition cursor-pointer">
                    <x-heroicon-o-bookmark-square class="w-4 h-4" />
                    <span data-assign-submit-label>Simpan</span>
                </button>
            </div>
        </form>
        <form method="POST" action="#" data-assign-delete-form class="hidden">
            @csrf
            @method('DELETE')
        </form>
    </div>

    <script>
        (function () {
            const wire = () => {
                const modal = document.querySelector('[data-assign-modal]');
                if (! modal) return;
                const overlay  = modal.querySelector('[data-assign-overlay]');
                const panel    = modal.querySelector('[data-assign-panel]');
                const form     = modal.querySelector('[data-assign-form]');
                const nameEl   = modal.querySelector('[data-assign-member-name]');
                const curEl    = modal.querySelector('[data-assign-current]');
                const doneEl   = modal.querySelector('[data-assign-completed]');
                const projSel  = modal.querySelector('[data-assign-project]');
                const titleInp = modal.querySelector('[data-assign-title]');
                const typeSel = modal.querySelector('[data-assign-type]');
                const statusSel = modal.querySelector('[data-assign-status]');
                const dueInp = modal.querySelector('[data-assign-due]');
                const hoursInp = modal.querySelector('[data-assign-hours]');
                const notesInp = modal.querySelector('[data-assign-notes]');
                const methodInp = modal.querySelector('[data-assign-method]');
                const formTitle = modal.querySelector('[data-assign-form-title]');
                const resetBtn = modal.querySelector('[data-assign-reset]');
                const submitLabel = modal.querySelector('[data-assign-submit-label]');
                const deleteForm = modal.querySelector('[data-assign-delete-form]');
                let currentId  = null;
                let currentMember = null;

                const esc = (s) => String(s ?? '').replace(/[&<>"]/g, (c) => ({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c]));
                const renderResponsibilityChips = (a) => {
                    const labels = Array.isArray(a?.responsibility_labels) ? a.responsibility_labels : [];
                    if (! labels.length) return '';
                    return '<div class="mt-1 flex flex-wrap gap-1">'
                        + labels.map(label => '<span class="inline-flex rounded-full border border-violet-100 bg-violet-50 px-2 py-0.5 text-[10px] font-semibold text-violet-700">' + esc(label) + '</span>').join('')
                        + '</div>';
                };

                const allAssignments = () => [
                    ...((currentMember && currentMember.allocations) || []),
                    ...((currentMember && currentMember.completed_allocations) || []),
                ];
                const assignmentById = (id) => allAssignments().find(a => String(a.id || '') === String(id || ''));

                const resetAssignmentForm = () => {
                    if (form && currentMember?.assignment_url) form.action = currentMember.assignment_url;
                    if (methodInp) methodInp.disabled = true;
                    if (formTitle) formTitle.textContent = 'Tambah Penugasan Baru';
                    if (submitLabel) submitLabel.textContent = 'Simpan';
                    resetBtn?.classList.add('hidden');
                    if (projSel) projSel.value = '';
                    if (titleInp) titleInp.value = '';
                    if (typeSel) typeSel.value = 'task';
                    if (statusSel) statusSel.value = 'planned';
                    if (dueInp) dueInp.value = '';
                    if (hoursInp) hoursInp.value = '';
                    if (notesInp) notesInp.value = '';
                };

                const editAssignment = (assignment) => {
                    if (! assignment || ! form || ! assignment.update_url) return;
                    form.action = assignment.update_url;
                    if (methodInp) {
                        methodInp.disabled = false;
                        methodInp.value = 'PUT';
                    }
                    if (formTitle) formTitle.textContent = 'Edit Penugasan';
                    if (submitLabel) submitLabel.textContent = 'Simpan Perubahan';
                    resetBtn?.classList.remove('hidden');
                    if (projSel) projSel.value = assignment.project_id || '';
                    if (titleInp) titleInp.value = assignment.title || assignment.role || '';
                    if (typeSel) typeSel.value = assignment.type || 'task';
                    if (statusSel) statusSel.value = assignment.status || 'planned';
                    if (dueInp) dueInp.value = assignment.due_date || '';
                    if (hoursInp) hoursInp.value = assignment.hours ?? '';
                    if (notesInp) notesInp.value = assignment.notes || '';
                    titleInp?.focus();
                };

                const renderCurrent = (allocs) => {
                    if (! curEl) return;
                    if (! allocs || allocs.length === 0) {
                        curEl.innerHTML = '<p class="text-[12.5px] text-slate-400 italic">Belum ada penugasan tercatat.</p>';
                        return;
                    }
                    curEl.innerHTML = allocs.map(a =>
                        '<div class="flex items-center justify-between gap-3 p-3 rounded-xl border border-violet-100 bg-white">'
                        + '  <div class="flex items-center gap-3 min-w-0 flex-1">'
                        + '    <span class="w-8 h-8 rounded-lg text-white text-[11px] font-bold flex items-center justify-center flex-shrink-0" style="background:' + esc(a.color || '#7C3AED') + ';">' + esc(a.code || '··') + '</span>'
                        + '    <div class="min-w-0">'
                        + '      <div class="text-[13px] font-semibold text-[#1E1B4B] truncate">' + esc(a.name || '—') + '</div>'
                        + '      <div class="text-[11.5px] text-slate-500">' + esc((a.responsibility_labels || []).join(' · ') || a.role || '—') + ' · ' + esc(a.hours || 0) + 'h/minggu</div>'
                        +        renderResponsibilityChips(a)
                        + '    </div>'
                        + '  </div>'
                        + '  <div class="flex items-center gap-2 flex-shrink-0">'
                        + '    <span class="text-[12px] font-bold tabular-nums text-violet-700">' + esc(a.pct || 0) + '%</span>'
                        + (a.update_url ? '    <button type="button" data-assign-edit="' + esc(a.id) + '" class="h-8 px-3 rounded-lg border border-violet-100 text-[12px] font-semibold text-violet-700 hover:border-violet-300 transition cursor-pointer">Edit</button>' : '')
                        + (a.delete_url ? '    <button type="button" data-assign-delete="' + esc(a.id) + '" class="h-8 px-3 rounded-lg border border-rose-100 text-[12px] font-semibold text-rose-600 hover:bg-rose-50 transition cursor-pointer">Hapus</button>' : '')
                        + '  </div>'
                        + '</div>'
                    ).join('');
                };

                const renderCompleted = (allocs) => {
                    if (! doneEl) return;
                    if (! allocs || allocs.length === 0) {
                        doneEl.innerHTML = '<p class="text-[12.5px] text-slate-400 italic">Belum ada penugasan selesai.</p>';
                        return;
                    }
                    doneEl.innerHTML = allocs.map(a =>
                        '<div class="flex items-center justify-between gap-3 p-3 rounded-xl border border-slate-100 bg-slate-50/80">'
                        + '  <div class="flex items-center gap-3 min-w-0 flex-1">'
                        + '    <span class="w-8 h-8 rounded-lg text-white text-[11px] font-bold flex items-center justify-center flex-shrink-0" style="background:' + esc(a.color || '#7C3AED') + ';">' + esc(a.code || '..') + '</span>'
                        + '    <div class="min-w-0">'
                        + '      <div class="text-[13px] font-semibold text-[#1E1B4B] truncate">' + esc(a.name || '-') + '</div>'
                        + '      <div class="text-[11.5px] text-slate-500">' + esc(a.role || '-') + ' - ' + esc(a.hours || 0) + 'h/minggu</div>'
                        +        renderResponsibilityChips(a)
                        + '    </div>'
                        + '  </div>'
                        + '  <div class="flex items-center gap-2 flex-shrink-0">'
                        + '    <span class="px-2 py-0.5 rounded-md bg-emerald-50 text-emerald-700 text-[10.5px] font-bold uppercase">' + esc(a.status_label || 'Selesai') + '</span>'
                        + (a.update_url ? '    <button type="button" data-assign-edit="' + esc(a.id) + '" class="h-8 px-3 rounded-lg border border-violet-100 bg-white text-[12px] font-semibold text-violet-700 hover:border-violet-300 transition cursor-pointer">Edit</button>' : '')
                        + (a.delete_url ? '    <button type="button" data-assign-delete="' + esc(a.id) + '" class="h-8 px-3 rounded-lg border border-rose-100 bg-white text-[12px] font-semibold text-rose-600 hover:bg-rose-50 transition cursor-pointer">Hapus</button>' : '')
                        + '  </div>'
                        + '</div>'
                    ).join('');
                };

                const openAssign = (id, fallbackName) => {
                    currentId = id;
                    currentMember = (window.__teamCsvMap && window.__teamCsvMap[id]) || null;
                    const name   = (currentMember && currentMember.name) || fallbackName || 'Anggota';
                    if (nameEl) nameEl.textContent = name;
                    renderCurrent(currentMember ? (currentMember.allocations || []) : []);
                    renderCompleted(currentMember ? (currentMember.completed_allocations || []) : []);
                    resetAssignmentForm();
                    modal.classList.remove('hidden');
                    modal.classList.add('flex');
                    document.body.style.overflow = 'hidden';
                };

                const closeAssign = () => {
                    modal.classList.add('hidden');
                    modal.classList.remove('flex');
                    document.body.style.overflow = '';
                    currentId = null;
                    currentMember = null;
                };

                /* Open trigger — delegated in CAPTURE phase so that the member modal's
                   panel.stopPropagation() (used to prevent overlay-close) doesn't block us. */
                document.addEventListener('click', (e) => {
                    const trig = e.target.closest('[data-assign-trigger][data-assign-id]');
                    if (! trig) return;
                    e.preventDefault();
                    e.stopPropagation();
                    openAssign(trig.dataset.assignId, trig.dataset.assignName);
                }, true);

                /* Panel-level delegation (mirror pattern used by member modal) */
                if (panel) {
                    panel.addEventListener('click', (e) => {
                        if (e.target.closest('[data-assign-close]')) { closeAssign(); return; }
                        const editBtn = e.target.closest('[data-assign-edit]');
                        if (editBtn) {
                            editAssignment(assignmentById(editBtn.dataset.assignEdit));
                            return;
                        }
                        const deleteBtn = e.target.closest('[data-assign-delete]');
                        if (deleteBtn) {
                            const assignment = assignmentById(deleteBtn.dataset.assignDelete);
                            if (! assignment?.delete_url || ! deleteForm) return;
                            if (! confirm('Hapus penugasan ini? Beban kerja anggota akan dihitung ulang.')) return;
                            deleteForm.action = assignment.delete_url;
                            deleteForm.submit();
                        }
                    });
                }
                resetBtn?.addEventListener('click', resetAssignmentForm);
                if (overlay) overlay.addEventListener('click', closeAssign);
                document.addEventListener('keydown', (e) => {
                    if (e.key === 'Escape' && ! modal.classList.contains('hidden')) closeAssign();
                });
            };

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', wire);
            } else {
                wire();
            }
        })();
    </script>

    {{-- ===== Edit Team Member Modal ===== --}}
    <div data-edit-modal="team" data-edit-has-errors="{{ old('_form') === 'edit' && $errors->any() ? '1' : '0' }}" class="hidden fixed inset-0 z-[70] items-center justify-center p-4 sm:p-6" role="dialog" aria-modal="true">
        <div data-edit-overlay class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm"></div>
        <form method="POST" action="{{ $editAction }}" data-edit-panel class="relative z-10 bg-white rounded-3xl shadow-[0_24px_64px_rgba(124,58,237,0.18)] w-full max-w-md flex flex-col overflow-hidden border border-violet-100">
            @csrf
            @method('PUT')
            <input type="hidden" name="_form" value="edit">
            <input type="hidden" name="_member_id" value="{{ old('_member_id') }}">
            <input type="hidden" name="_archive_scope" value="{{ old('_archive_scope', $archiveScope) }}">
            <input type="hidden" name="avatar_color" value="{{ old('_form') === 'edit' ? old('avatar_color') : '' }}">
            <div class="px-6 py-4 border-b border-violet-100 flex items-center justify-between">
                <h3 class="text-[16px] font-bold text-[#1E1B4B]">Edit Anggota</h3>
                <button type="button" data-edit-close aria-label="Tutup" class="w-9 h-9 rounded-full hover:bg-violet-50 flex items-center justify-center text-slate-500 hover:text-rose-500 transition cursor-pointer">
                    <x-heroicon-o-x-mark class="w-5 h-5" />
                </button>
            </div>
            <div class="px-6 py-5 space-y-3 max-h-[72vh] overflow-y-auto">
                <div>
                    <label class="block text-[11px] font-bold tracking-wider uppercase text-slate-500 mb-1.5">Nama Lengkap</label>
                    <input name="name" value="{{ old('_form') === 'edit' ? old('name') : '' }}" class="w-full h-10 rounded-lg border border-violet-100 px-3 text-[13px] focus:outline-none focus:ring-2 focus:ring-violet-300" />
                    @if (old('_form') === 'edit') @error('name') <p class="mt-1 text-[11.5px] font-semibold text-rose-600">{{ $message }}</p> @enderror @endif
                </div>
                <div>
                    <label class="block text-[11px] font-bold tracking-wider uppercase text-slate-500 mb-1.5">Email</label>
                    <input name="email" value="{{ old('_form') === 'edit' ? old('email') : '' }}" type="email" class="w-full h-10 rounded-lg border border-violet-100 px-3 text-[13px] focus:outline-none focus:ring-2 focus:ring-violet-300" />
                    @if (old('_form') === 'edit') @error('email') <p class="mt-1 text-[11.5px] font-semibold text-rose-600">{{ $message }}</p> @enderror @endif
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-[11px] font-bold tracking-wider uppercase text-slate-500 mb-1.5">Role</label>
                        <select name="role" class="w-full h-10 rounded-lg border border-violet-100 px-3 text-[13px] focus:outline-none focus:ring-2 focus:ring-violet-300">
                            <option value="sa_qa">SA/QA</option>
                            <option value="fullstack_dev">Fullstack Dev</option>
                            <option value="ui_ux">UI/UX Designer</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold tracking-wider uppercase text-slate-500 mb-1.5">Level</label>
                        <select name="level" class="w-full h-10 rounded-lg border border-violet-100 px-3 text-[13px] focus:outline-none focus:ring-2 focus:ring-violet-300">
                            <option>Junior</option>
                            <option>Mid</option>
                            <option>Senior</option>
                        </select>
                    </div>
                </div>
                <div>
                    <label class="block text-[11px] font-bold tracking-wider uppercase text-slate-500 mb-1.5">WhatsApp</label>
                    <input name="phone" value="{{ old('_form') === 'edit' ? old('phone') : '' }}" type="tel" class="w-full h-10 rounded-lg border border-violet-100 px-3 text-[13px] focus:outline-none focus:ring-2 focus:ring-violet-300" />
                </div>
                <div>
                    <label class="block text-[11px] font-bold tracking-wider uppercase text-slate-500 mb-1.5">Skills</label>
                    <input name="skills" value="{{ old('_form') === 'edit' ? old('skills') : '' }}" class="w-full h-10 rounded-lg border border-violet-100 px-3 text-[13px] focus:outline-none focus:ring-2 focus:ring-violet-300" />
                </div>
            </div>
            <div class="px-6 py-3 border-t border-violet-100 bg-violet-50/30 flex items-center justify-end gap-2">
                <button type="button" data-edit-close class="h-9 px-4 rounded-lg text-[12.5px] font-semibold text-slate-500 hover:text-slate-700 transition cursor-pointer">Batal</button>
                <button type="submit" class="inline-flex items-center gap-2 h-9 px-4 rounded-lg bg-gradient-to-r from-[#7C3AED] via-[#A855F7] to-[#C084FC] text-white font-semibold text-[12.5px] shadow-[0_2px_8px_rgba(124,58,237,0.2)] hover:scale-[1.02] transition cursor-pointer">
                    <x-heroicon-o-bookmark-square class="w-4 h-4" />
                    Simpan Perubahan
                </button>
            </div>
        </form>
    </div>

    {{-- ===== Invite Team Member Modal ===== --}}
    <div data-create-modal="team" data-create-has-errors="{{ old('_form') === 'create' && $errors->any() ? '1' : '0' }}" class="hidden fixed inset-0 z-[70] items-center justify-center p-4 sm:p-6" role="dialog" aria-modal="true">
        <div data-create-overlay class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm"></div>
        <form method="POST" action="{{ route('team.members.store') }}" data-create-panel class="relative z-10 bg-white rounded-3xl shadow-[0_24px_64px_rgba(124,58,237,0.18)] w-full max-w-md flex flex-col overflow-hidden border border-violet-100">
            @csrf
            <input type="hidden" name="_form" value="create">
            <div class="px-6 py-4 border-b border-violet-100 flex items-center justify-between">
                <h3 class="text-[16px] font-bold text-[#1E1B4B]">Tambah Anggota</h3>
                <button type="button" data-create-close aria-label="Tutup" class="w-9 h-9 rounded-full hover:bg-violet-50 flex items-center justify-center text-slate-500 hover:text-rose-500 transition cursor-pointer">
                    <x-heroicon-o-x-mark class="w-5 h-5" />
                </button>
            </div>
            <div class="px-6 py-5 space-y-3">
                <div class="rounded-lg border border-violet-100 bg-violet-50/60 px-3 py-2.5 flex items-start gap-2">
                    <x-heroicon-o-key class="w-4 h-4 text-violet-600 mt-0.5 flex-shrink-0" />
                    <p class="text-[12px] text-violet-800 leading-snug">Akun akan dibuat dengan password awal: <span class="font-bold">password</span></p>
                </div>
                <div>
                    <label class="block text-[11px] font-bold tracking-wider uppercase text-slate-500 mb-1.5">Nama Lengkap</label>
                    <input name="name" value="{{ old('_form') === 'create' ? old('name') : '' }}" data-ct-name class="w-full h-10 rounded-lg border border-violet-100 px-3 text-[13px] focus:outline-none focus:ring-2 focus:ring-violet-300" placeholder="Nama anggota..." />
                    @if (old('_form') === 'create') @error('name') <p class="mt-1 text-[11.5px] font-semibold text-rose-600">{{ $message }}</p> @enderror @endif
                </div>
                <div>
                    <label class="block text-[11px] font-bold tracking-wider uppercase text-slate-500 mb-1.5">Email</label>
                    <input name="email" value="{{ old('_form') === 'create' ? old('email') : '' }}" data-ct-email type="email" class="w-full h-10 rounded-lg border border-violet-100 px-3 text-[13px] focus:outline-none focus:ring-2 focus:ring-violet-300" placeholder="nama@avatech.test" />
                    @if (old('_form') === 'create') @error('email') <p class="mt-1 text-[11.5px] font-semibold text-rose-600">{{ $message }}</p> @enderror @endif
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-[11px] font-bold tracking-wider uppercase text-slate-500 mb-1.5">Role</label>
                        <select name="role" data-ct-role class="w-full h-10 rounded-lg border border-violet-100 px-3 text-[13px] focus:outline-none focus:ring-2 focus:ring-violet-300">
                            <option value="sa_qa" @selected(old('role') === 'sa_qa')>SA/QA</option>
                            <option value="fullstack_dev" @selected(old('role', 'fullstack_dev') === 'fullstack_dev')>Fullstack Dev</option>
                            <option value="ui_ux" @selected(old('role') === 'ui_ux')>UI/UX Designer</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold tracking-wider uppercase text-slate-500 mb-1.5">Level</label>
                        <select name="level" data-ct-level class="w-full h-10 rounded-lg border border-violet-100 px-3 text-[13px] focus:outline-none focus:ring-2 focus:ring-violet-300">
                            <option @selected(old('level') === 'Junior')>Junior</option>
                            <option @selected(old('level', 'Mid') === 'Mid')>Mid</option>
                            <option @selected(old('level') === 'Senior')>Senior</option>
                        </select>
                    </div>
                </div>
                <div>
                    <label class="block text-[11px] font-bold tracking-wider uppercase text-slate-500 mb-1.5">WhatsApp</label>
                    <input name="phone" value="{{ old('_form') === 'create' ? old('phone') : '' }}" type="tel" class="w-full h-10 rounded-lg border border-violet-100 px-3 text-[13px] focus:outline-none focus:ring-2 focus:ring-violet-300" placeholder="+62 812 3456 7890" />
                    @if (old('_form') === 'create') @error('phone') <p class="mt-1 text-[11.5px] font-semibold text-rose-600">{{ $message }}</p> @enderror @endif
                </div>
                <div>
                    <label class="block text-[11px] font-bold tracking-wider uppercase text-slate-500 mb-1.5">Skills</label>
                    <input name="skills" value="{{ old('_form') === 'create' ? old('skills') : '' }}" class="w-full h-10 rounded-lg border border-violet-100 px-3 text-[13px] focus:outline-none focus:ring-2 focus:ring-violet-300" placeholder="Laravel, QA, Figma" />
                    @if (old('_form') === 'create') @error('skills') <p class="mt-1 text-[11.5px] font-semibold text-rose-600">{{ $message }}</p> @enderror @endif
                </div>
            </div>
            <div class="px-6 py-3 border-t border-violet-100 bg-violet-50/30 flex items-center justify-end gap-2">
                <button type="button" data-create-close class="h-9 px-4 rounded-lg text-[12.5px] font-semibold text-slate-500 hover:text-slate-700 transition cursor-pointer">Batal</button>
                <button type="submit" class="inline-flex items-center gap-2 h-9 px-4 rounded-lg bg-gradient-to-r from-[#7C3AED] via-[#A855F7] to-[#C084FC] text-white font-semibold text-[12.5px] shadow-[0_2px_8px_rgba(124,58,237,0.2)] hover:scale-[1.02] transition cursor-pointer">
                    <x-heroicon-o-user-plus class="w-4 h-4" />
                    Tambah Anggota
                </button>
            </div>
        </form>
    </div>

    <script>
        (function () {
            const wire = () => {
                const findMemberSource = (id, fallback) => {
                    if (id) {
                        const match = Array.from(document.querySelectorAll('[data-member-id][data-update-url]'))
                            .find(el => el.dataset.memberId === String(id));
                        if (match) return match;
                    }

                    return fallback?.closest?.('[data-member-id][data-update-url]') || null;
                };

                const openCreateModal = () => {
                    const modal = document.querySelector('[data-create-modal="team"]');
                    if (! modal) return;
                    modal.classList.remove('hidden');
                    modal.classList.add('flex');
                    document.body.style.overflow = 'hidden';
                    modal.querySelector('[data-ct-name]')?.focus();
                };

                const openEditModal = (source) => {
                    const editModal = document.querySelector('[data-edit-modal="team"]');
                    const editForm = editModal?.querySelector('[data-edit-panel]');
                    if (! editModal || ! editForm || ! source) return;

                    editForm.action = source.dataset.updateUrl || editForm.action;
                    editForm.querySelector('[name="_member_id"]').value = source.dataset.memberId || '';
                    editForm.querySelector('[name="_archive_scope"]').value = @json($archiveScope);
                    editForm.querySelector('[name="name"]').value = source.dataset.name || '';
                    editForm.querySelector('[name="email"]').value = source.dataset.email || '';
                    editForm.querySelector('[name="role"]').value = source.dataset.roleValue || 'fullstack_dev';
                    editForm.querySelector('[name="phone"]').value = source.dataset.phone || '';
                    editForm.querySelector('[name="level"]').value = source.dataset.level || 'Mid';
                    editForm.querySelector('[name="skills"]').value = source.dataset.skills || '';
                    editForm.querySelector('[name="avatar_color"]').value = source.dataset.avatarColor || '';

                    editModal.classList.remove('hidden');
                    editModal.classList.add('flex');
                    document.body.style.overflow = 'hidden';
                    editForm.querySelector('input[name="name"]')?.focus();
                };

                document.addEventListener('click', (e) => {
                    const createTrigger = e.target.closest('[data-create-trigger="team"]');
                    if (createTrigger) {
                        e.preventDefault();
                        e.stopPropagation();
                        openCreateModal();
                        return;
                    }

                    const editTrigger = e.target.closest('[data-edit-member]');
                    if (editTrigger) {
                        e.preventDefault();
                        e.stopPropagation();
                        const source = findMemberSource(editTrigger.dataset.memberId, editTrigger);
                        openEditModal(source);
                    }
                }, true);

                const editModal = document.querySelector('[data-edit-modal="team"]');
                const editForm = editModal?.querySelector('[data-edit-panel]');
                const editOverlay = editModal?.querySelector('[data-edit-overlay]');
                const hasVisibleModal = () => Array.from(document.querySelectorAll('[data-edit-modal="team"], [data-create-modal="team"], [data-tm-modal], [data-assign-modal]'))
                    .some(item => ! item.classList.contains('hidden'));
                const syncScrollLock = () => {
                    document.body.style.overflow = hasVisibleModal() ? 'hidden' : '';
                };
                const openEdit = (source) => {
                    if (! editModal || ! editForm || ! source) return;
                    editForm.action = source.dataset.updateUrl || editForm.action;
                    editForm.querySelector('[name="_member_id"]').value = source.dataset.memberId || '';
                    editForm.querySelector('[name="_archive_scope"]').value = @json($archiveScope);
                    editForm.querySelector('[name="name"]').value = source.dataset.name || '';
                    editForm.querySelector('[name="email"]').value = source.dataset.email || '';
                    editForm.querySelector('[name="role"]').value = source.dataset.roleValue || 'fullstack_dev';
                    editForm.querySelector('[name="phone"]').value = source.dataset.phone || '';
                    editForm.querySelector('[name="level"]').value = source.dataset.level || 'Mid';
                    editForm.querySelector('[name="skills"]').value = source.dataset.skills || '';
                    editForm.querySelector('[name="avatar_color"]').value = source.dataset.avatarColor || '';
                    editModal.classList.remove('hidden');
                    editModal.classList.add('flex');
                    document.body.style.overflow = 'hidden';
                    editForm.querySelector('input[name="name"]')?.focus();
                };
                const closeEdit = () => {
                    if (! editModal) return;
                    editModal.classList.add('hidden');
                    editModal.classList.remove('flex');
                    syncScrollLock();
                };
                document.querySelectorAll('[data-edit-member]').forEach(btn => {
                    btn.addEventListener('click', (e) => {
                        e.preventDefault();
                        e.stopPropagation();
                        const id = btn.dataset.memberId || btn.closest('[data-member-id]')?.dataset.memberId;
                        const source = id
                            ? Array.from(document.querySelectorAll('[data-view-panel="grid"] [data-member-id]')).find(el => el.dataset.memberId === String(id))
                            : btn.closest('[data-member-id]');
                        openEdit(source);
                    });
                });
                editOverlay?.addEventListener('click', closeEdit);
                editModal?.querySelectorAll('[data-edit-close]').forEach(btn => btn.addEventListener('click', closeEdit));
                editForm?.addEventListener('click', (e) => e.stopPropagation());
                if (editModal?.dataset.editHasErrors === '1') {
                    editModal.classList.remove('hidden');
                    editModal.classList.add('flex');
                    document.body.style.overflow = 'hidden';
                }

                const modal   = document.querySelector('[data-create-modal="team"]');
                const trigger = document.querySelector('[data-create-trigger="team"]');
                if (! modal || ! trigger) return;
                const overlay = modal.querySelector('[data-create-overlay]');
                const panel   = modal.querySelector('[data-create-panel]');
                const open  = () => { modal.classList.remove('hidden'); modal.classList.add('flex'); document.body.style.overflow = 'hidden'; modal.querySelector('[data-ct-name]')?.focus(); };
                const close = () => { modal.classList.add('hidden'); modal.classList.remove('flex'); syncScrollLock(); };
                trigger.addEventListener('click', open);
                panel?.addEventListener('click', (e) => e.stopPropagation());
                overlay?.addEventListener('click', close);
                modal.querySelectorAll('[data-create-close]').forEach(b => b.addEventListener('click', close));
                document.addEventListener('keydown', (e) => {
                    if (e.key !== 'Escape') return;

                    if (editModal && ! editModal.classList.contains('hidden')) {
                        e.preventDefault();
                        e.stopPropagation();
                        closeEdit();
                        return;
                    }

                    if (! modal.classList.contains('hidden')) {
                        e.preventDefault();
                        e.stopPropagation();
                        close();
                    }
                }, true);
                if (modal.dataset.createHasErrors === '1') open();
                @if (session('status'))
                    window.toast && window.toast(@json(session('status')));
                @endif

            };
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', wire);
            } else {
                wire();
            }
        })();
    </script>
</x-layouts.authenticated>
