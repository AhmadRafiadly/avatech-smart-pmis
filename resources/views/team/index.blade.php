@php
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

    // Team Management lists delivery resources only; CEO/PM keeps page access but is not a workload card.
    $deliveryMembers = collect($members)->reject(fn ($m) => $m['role_key'] === 'ceo')->values()->all();

    $filters = [
        ['id' => 'all',       'label' => 'Semua'],
        ['id' => 'sa-qa',     'label' => 'SA/QA'],
        ['id' => 'fullstack', 'label' => 'Fullstack'],
        ['id' => 'uiux',      'label' => 'UI/UX'],
        ['id' => 'risk',      'label' => 'Burnout Risk'],
    ];

    $col = collect($deliveryMembers);

    $filterCounts = [
        'all'       => count($deliveryMembers),
        'sa-qa'     => $col->where('role_key', 'sa-qa')->count(),
        'fullstack' => $col->where('role_key', 'fullstack')->count(),
        'uiux'      => $col->where('role_key', 'uiux')->count(),
        'risk'      => $col->where('load', '>=', 85)->count(),
    ];

    $stats = [
        ['label' => 'Total Anggota', 'value' => count($deliveryMembers),                                              'suffix' => '',      'color' => '#7C3AED'],
        ['label' => 'Kapasitas',     'value' => $col->sum('capacity_hours'),                                          'suffix' => 'h/mgg', 'color' => '#3B82F6'],
        ['label' => 'Avg Load',      'value' => round($col->avg('load')),                                             'suffix' => '%',     'color' => '#10B981'],
        ['label' => 'Proyek Aktif',  'value' => 7,                                                                     'suffix' => '',      'color' => '#A855F7'],
        ['label' => 'Burnout Risk',  'value' => $col->where('load', '>=', 85)->count(),                              'suffix' => '',      'color' => '#EF4444'],
    ];

    $rolePill = [
        'ceo'       => 'bg-fuchsia-50 text-fuchsia-700',
        'sa-qa'     => 'bg-violet-50 text-violet-700',
        'fullstack' => 'bg-cyan-50 text-cyan-700',
        'uiux'      => 'bg-pink-50 text-pink-700',
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
                Pantau kapasitas, alokasi, dan kemampuan tim Avatech. AI Workload Balancer mendeteksi risiko burnout otomatis.
            </p>
        </div>
        <div class="flex items-center gap-2 flex-wrap">
            <button type="button" data-export-team class="inline-flex items-center gap-2 h-12 px-4 rounded-xl border border-violet-100 bg-white text-[13.5px] font-semibold text-slate-600 hover:border-violet-300 hover:text-violet-700 transition cursor-pointer">
                <x-heroicon-o-arrow-down-tray class="w-4 h-4" />
                Export
            </button>
            <button type="button" data-create-trigger="team" class="inline-flex items-center gap-2 h-12 px-5 rounded-xl bg-gradient-to-r from-[#7C3AED] via-[#A855F7] to-[#C084FC] text-white font-semibold text-[14px] shadow-[0_4px_14px_rgba(124,58,237,0.35)] hover:shadow-[0_6px_20px_rgba(124,58,237,0.45)] hover:-translate-y-0.5 transition-all cursor-pointer">
                <x-heroicon-o-user-plus class="w-5 h-5" />
                Undang Anggota
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

    <section class="relative overflow-hidden rounded-2xl p-6 mb-8 text-white bg-gradient-to-r from-violet-600 via-fuchsia-600 to-pink-600 shadow-[0_8px_24px_rgba(124,58,237,0.18)]">
        <div class="absolute -top-10 -right-10 w-48 h-48 rounded-full bg-white/10 pointer-events-none"></div>
        <div class="absolute top-12 right-32 w-24 h-24 rounded-full bg-white/5 pointer-events-none"></div>
        <div class="relative flex items-start gap-5 flex-wrap">
            <div class="w-12 h-12 rounded-xl bg-white/20 backdrop-blur flex items-center justify-center flex-shrink-0">
                <x-heroicon-o-sparkles class="w-6 h-6" />
            </div>
            <div class="flex-1 min-w-[280px]">
                <div class="flex items-center gap-2 mb-1.5 flex-wrap">
                    <span class="text-[10px] font-bold tracking-[0.18em] uppercase bg-white/20 px-2 py-1 rounded-md">AI WORKLOAD BALANCER</span>
                    <span class="text-[11px] text-white/80">Diperbarui 5 menit lalu</span>
                </div>
                <h3 class="text-[19px] font-bold leading-tight mb-1.5">Yuda &amp; Irwan mendekati overload minggu ini</h3>
                <p class="text-[13.5px] text-white/85 leading-relaxed">
                    Beban kerja <strong>Yuda Prayoga</strong> mencapai 90% kapasitas (Beta Portal + Gamma API).
                    Sarankan re-assign task <em>"UI Review Beta Portal"</em> ke <strong>Ferry Achmad</strong> (load 42%) untuk meredakan tekanan.
                </p>
            </div>
            <div class="flex flex-col gap-2 flex-shrink-0">
                <button type="button" data-toast="Penerapan saran AI Workload segera tersedia." class="inline-flex items-center gap-2 h-10 px-4 rounded-xl bg-white text-violet-700 font-bold text-[13px] hover:bg-violet-50 transition cursor-pointer">
                    <x-heroicon-o-sparkles class="w-4 h-4" />
                    Terapkan Saran
                </button>
                <button type="button" data-toast="Detail rekomendasi AI segera tersedia." class="inline-flex items-center gap-2 h-10 px-4 rounded-xl bg-white/15 backdrop-blur text-white font-semibold text-[12.5px] hover:bg-white/25 transition border border-white/20 cursor-pointer">
                    Tinjau detail
                </button>
            </div>
        </div>
    </section>

    <section class="bg-white rounded-2xl border border-violet-100 shadow-[0_2px_8px_rgba(124,58,237,0.08)] p-5 mb-6 flex flex-wrap items-center gap-4">
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
                        <span class="inline-flex items-center text-[10.5px] font-bold tracking-wide uppercase px-2 py-1 rounded-md mt-2 {{ $rolePill[$m['role_key']] }}">{{ $m['role'] }}</span>
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
                        <div class="text-[18px] font-bold tabular-nums" style="color: {{ $perfFill($m['perf']) }};">{{ $m['perf'] }}</div>
                        <div class="text-[10.5px] text-slate-400 font-semibold uppercase tracking-wider mt-0.5">Skor</div>
                    </div>
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
                        <th class="px-4 py-4">Skor</th>
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
                                        <div class="text-[12px] text-slate-500 truncate">{{ $m['email'] }}</div>
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
                            <td class="px-4 py-4 text-[13px] font-bold tabular-nums" style="color: {{ $perfFill($m['perf']) }};">{{ $m['perf'] }}</td>
                            <td class="px-7 py-4 text-right">
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
                                                <a href="https://mail.google.com/mail/?view=cm&fs=1&to={{ urlencode($m['email']) }}" target="_blank" rel="noopener" class="text-[13.5px] text-[#1E1B4B] truncate hover:text-violet-700 transition">{{ $m['email'] }}</a>
                                            @else
                                                <span class="text-[13.5px] text-slate-400 italic" title="Email anggota tidak tersedia">Email belum tersedia</span>
                                            @endif
                                        </div>
                                        <div class="flex items-center gap-3">
                                            <x-heroicon-o-phone class="w-4 h-4 text-violet-600 flex-shrink-0" />
                                            @if (! empty($m['phone']))
                                                <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $m['phone']) }}" target="_blank" rel="noopener" class="text-[13.5px] text-[#1E1B4B] hover:text-emerald-600 transition">{{ $m['phone'] }}</a>
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
                                    <h4 class="text-[11px] font-bold tracking-wider uppercase text-slate-400 mb-3">Skor Performa</h4>
                                    <div class="flex items-baseline gap-2 mb-2">
                                        <span class="text-[40px] font-bold leading-none tabular-nums" style="color: {{ $perfFill($m['perf']) }};">{{ $m['perf'] }}</span>
                                        <span class="text-[14px] font-semibold text-slate-400">/ 100</span>
                                    </div>
                                    <div class="h-2 rounded-full bg-[#F3E8FF] overflow-hidden">
                                        <div class="h-full rounded-full" style="width: {{ $m['perf'] }}%; background: {{ $perfFill($m['perf']) }};"></div>
                                    </div>
                                    <div class="text-[11.5px] text-slate-500 mt-2.5 leading-relaxed">
                                        Dihitung dari konsistensi WBS, tingkat lulus QC, dan respon AI Smart Reminders.
                                    </div>
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
                                    <div class="text-[11.5px] text-slate-500 mt-2.5">Termasuk peran lead di <span class="font-semibold text-[#1E1B4B] tabular-nums">{{ $m['projects_lead'] }}</span> proyek</div>
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

                        {{-- Role & Akses --}}
                        <div data-tm-panel="access" class="hidden">
                            <div class="rounded-2xl border border-violet-100 p-5 mb-5">
                                <h4 class="text-[11px] font-bold tracking-wider uppercase text-slate-400 mb-3">Level Akses Sistem</h4>
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
                            <div class="rounded-2xl border border-amber-200 bg-amber-50/40 p-5 flex items-start gap-3">
                                <x-heroicon-o-shield-exclamation class="w-5 h-5 text-amber-700 mt-0.5 flex-shrink-0" />
                                <div>
                                    <div class="text-[13.5px] font-bold text-amber-900">Konfirmasi CEO/PM dibutuhkan</div>
                                    <p class="text-[12.5px] text-amber-700 mt-1">Perubahan level akses anggota memerlukan persetujuan CEO untuk menjaga audit trail tetap aman.</p>
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
                            @if (! empty($m['phone']))
                                <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $m['phone']) }}" target="_blank" rel="noopener" class="inline-flex items-center gap-2 h-10 px-4 rounded-xl border border-violet-200 bg-white text-[13px] font-semibold text-slate-600 hover:border-emerald-400 hover:text-emerald-700 transition cursor-pointer">
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
                        const rows = [['ID','Name','Email','Role','Level','Tenure','Load %','Load Hours','Capacity Hours','Active Projects','Open Tasks','Performance','Presence','Skills']];
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
    @php
        $projectOptions = collect($members)
            ->pluck('allocations')
            ->flatten(1)
            ->pluck('name')
            ->filter()
            ->unique()
            ->sort()
            ->values()
            ->all();
    @endphp
    <div data-assign-modal class="hidden fixed inset-0 z-50 items-center justify-center p-4 sm:p-6" role="dialog" aria-modal="true" aria-labelledby="assign-modal-title">
        <div data-assign-overlay class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm"></div>
        <div data-assign-panel class="relative bg-white rounded-3xl shadow-[0_24px_64px_rgba(124,58,237,0.18)] w-full max-w-lg max-h-[90vh] flex flex-col overflow-hidden border border-violet-100">
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
                    <h4 class="text-[11px] font-bold tracking-wider uppercase text-slate-400 mb-2">Penugasan Tersimpan (Demo Lokal)</h4>
                    <div data-assign-saved class="space-y-2"></div>
                </section>
                <section class="rounded-2xl border border-dashed border-violet-200 bg-violet-50/30 p-4">
                    <h4 class="text-[11px] font-bold tracking-wider uppercase text-violet-700 mb-3">Tambah Penugasan Baru</h4>
                    <div class="space-y-3">
                        <div>
                            <label class="block text-[11px] font-bold tracking-wider uppercase text-slate-500 mb-1.5">Proyek</label>
                            <select data-assign-project class="w-full h-10 rounded-lg border border-violet-100 px-3 text-[13px] focus:outline-none focus:ring-2 focus:ring-violet-300">
                                <option value="">— Pilih proyek —</option>
                                @foreach ($projectOptions as $pn)
                                    <option value="{{ $pn }}">{{ $pn }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-[11px] font-bold tracking-wider uppercase text-slate-500 mb-1.5">Peran</label>
                                <input data-assign-role type="text" placeholder="mis. UI Lead" class="w-full h-10 rounded-lg border border-violet-100 px-3 text-[13px] focus:outline-none focus:ring-2 focus:ring-violet-300" />
                            </div>
                            <div>
                                <label class="block text-[11px] font-bold tracking-wider uppercase text-slate-500 mb-1.5">Load %</label>
                                <input data-assign-load type="number" min="0" max="100" step="5" placeholder="20" class="w-full h-10 rounded-lg border border-violet-100 px-3 text-[13px] focus:outline-none focus:ring-2 focus:ring-violet-300" />
                            </div>
                        </div>
                    </div>
                </section>
            </div>
            <div class="px-6 py-3 border-t border-violet-100 bg-violet-50/30 flex items-center justify-end gap-2 flex-shrink-0">
                <button type="button" data-assign-close class="h-9 px-4 rounded-lg text-[12.5px] font-semibold text-slate-500 hover:text-slate-700 transition cursor-pointer">Batal</button>
                <button type="button" data-assign-save class="inline-flex items-center gap-2 h-9 px-4 rounded-lg bg-gradient-to-r from-[#7C3AED] via-[#A855F7] to-[#C084FC] text-white font-semibold text-[12.5px] shadow-[0_2px_8px_rgba(124,58,237,0.2)] hover:scale-[1.02] transition cursor-pointer">
                    <x-heroicon-o-bookmark-square class="w-4 h-4" />
                    Simpan
                </button>
            </div>
        </div>
    </div>

    <script>
        (function () {
            const wire = () => {
                const modal = document.querySelector('[data-assign-modal]');
                if (! modal) return;
                const overlay  = modal.querySelector('[data-assign-overlay]');
                const panel    = modal.querySelector('[data-assign-panel]');
                const nameEl   = modal.querySelector('[data-assign-member-name]');
                const curEl    = modal.querySelector('[data-assign-current]');
                const savedEl  = modal.querySelector('[data-assign-saved]');
                const projSel  = modal.querySelector('[data-assign-project]');
                const roleInp  = modal.querySelector('[data-assign-role]');
                const loadInp  = modal.querySelector('[data-assign-load]');
                const saveBtn  = modal.querySelector('[data-assign-save]');
                let currentId  = null;

                const esc = (s) => String(s ?? '').replace(/[&<>"]/g, (c) => ({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c]));
                const lsKey = (id) => 'avt-team-assignment:' + id;
                const loadSaved = (id) => {
                    try { return JSON.parse(localStorage.getItem(lsKey(id)) || '[]'); }
                    catch (e) { return []; }
                };
                const persistSaved = (id, list) => {
                    try { localStorage.setItem(lsKey(id), JSON.stringify(list)); } catch (e) {}
                };

                const renderCurrent = (allocs) => {
                    if (! curEl) return;
                    if (! allocs || allocs.length === 0) {
                        curEl.innerHTML = '<p class="text-[12.5px] text-slate-400 italic">Belum ada penugasan tercatat.</p>';
                        return;
                    }
                    curEl.innerHTML = allocs.map(a =>
                        '<div class="flex items-center justify-between gap-3 p-3 rounded-xl border border-violet-100 bg-white">'
                        + '  <div class="flex items-center gap-3 min-w-0">'
                        + '    <span class="w-8 h-8 rounded-lg text-white text-[11px] font-bold flex items-center justify-center flex-shrink-0" style="background:' + esc(a.color || '#7C3AED') + ';">' + esc(a.code || '··') + '</span>'
                        + '    <div class="min-w-0">'
                        + '      <div class="text-[13px] font-semibold text-[#1E1B4B] truncate">' + esc(a.name || '—') + '</div>'
                        + '      <div class="text-[11.5px] text-slate-500">' + esc(a.role || '—') + ' · ' + esc(a.hours || 0) + 'h/minggu</div>'
                        + '    </div>'
                        + '  </div>'
                        + '  <span class="text-[12px] font-bold tabular-nums text-violet-700">' + esc(a.pct || 0) + '%</span>'
                        + '</div>'
                    ).join('');
                };

                const renderSaved = (id) => {
                    if (! savedEl) return;
                    const list = loadSaved(id);
                    if (list.length === 0) {
                        savedEl.innerHTML = '<p class="text-[12.5px] text-slate-400 italic">Belum ada penugasan tambahan tersimpan.</p>';
                        return;
                    }
                    savedEl.innerHTML = list.map((row, idx) =>
                        '<div class="flex items-center justify-between gap-3 p-3 rounded-xl border border-violet-200 bg-violet-50/40">'
                        + '  <div class="min-w-0">'
                        + '    <div class="text-[13px] font-semibold text-[#1E1B4B] truncate">' + esc(row.project) + '</div>'
                        + '    <div class="text-[11.5px] text-slate-500">' + esc(row.role || '—') + ' · ' + esc(row.load) + '%</div>'
                        + '  </div>'
                        + '  <button type="button" data-assign-remove="' + idx + '" class="text-[11.5px] font-semibold text-rose-600 hover:text-rose-700 transition cursor-pointer">Hapus</button>'
                        + '</div>'
                    ).join('');
                };

                const openAssign = (id, fallbackName) => {
                    currentId = id;
                    const member = (window.__teamCsvMap && window.__teamCsvMap[id]) || null;
                    const name   = (member && member.name) || fallbackName || 'Anggota';
                    if (nameEl) nameEl.textContent = name;
                    renderCurrent(member ? (member.allocations || []) : []);
                    renderSaved(id);
                    if (projSel) projSel.value = '';
                    if (roleInp) roleInp.value = '';
                    if (loadInp) loadInp.value = '';
                    modal.classList.remove('hidden');
                    modal.classList.add('flex');
                    document.body.style.overflow = 'hidden';
                };

                const closeAssign = () => {
                    modal.classList.add('hidden');
                    modal.classList.remove('flex');
                    document.body.style.overflow = '';
                    currentId = null;
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
                        const rm = e.target.closest('[data-assign-remove]');
                        if (rm && currentId !== null) {
                            const idx = parseInt(rm.dataset.assignRemove, 10);
                            const list = loadSaved(currentId);
                            if (! isNaN(idx) && idx >= 0 && idx < list.length) {
                                const removed = list.splice(idx, 1)[0];
                                persistSaved(currentId, list);
                                renderSaved(currentId);
                                if (window.toast) window.toast('Penugasan "' + (removed?.project || '') + '" dihapus.');
                            }
                            return;
                        }
                        if (e.target.closest('[data-assign-save]')) {
                            if (currentId === null) return;
                            const project = (projSel?.value || '').trim();
                            const role    = (roleInp?.value || '').trim();
                            const loadStr = (loadInp?.value || '').trim();
                            if (! project) { window.toast && window.toast('Pilih proyek dulu.'); return; }
                            const load = Math.max(0, Math.min(100, parseInt(loadStr || '0', 10) || 0));
                            const list = loadSaved(currentId);
                            list.unshift({ project, role, load, at: Date.now() });
                            persistSaved(currentId, list);
                            renderSaved(currentId);
                            if (projSel) projSel.value = '';
                            if (roleInp) roleInp.value = '';
                            if (loadInp) loadInp.value = '';
                            if (window.toast) window.toast('Penugasan ke "' + project + '" disimpan (demo lokal).');
                        }
                    });
                }
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

    {{-- ===== Invite Team Member Modal ===== --}}
    <div data-create-modal="team" class="hidden fixed inset-0 z-50 items-center justify-center p-4 sm:p-6" role="dialog" aria-modal="true">
        <div data-create-overlay class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm"></div>
        <div data-create-panel class="relative bg-white rounded-3xl shadow-[0_24px_64px_rgba(124,58,237,0.18)] w-full max-w-md flex flex-col overflow-hidden border border-violet-100">
            <div class="px-6 py-4 border-b border-violet-100 flex items-center justify-between">
                <h3 class="text-[16px] font-bold text-[#1E1B4B]">Undang Anggota</h3>
                <button type="button" data-create-close aria-label="Tutup" class="w-9 h-9 rounded-full hover:bg-violet-50 flex items-center justify-center text-slate-500 hover:text-rose-500 transition cursor-pointer">
                    <x-heroicon-o-x-mark class="w-5 h-5" />
                </button>
            </div>
            <div class="px-6 py-5 space-y-3">
                <div>
                    <label class="block text-[11px] font-bold tracking-wider uppercase text-slate-500 mb-1.5">Nama Lengkap</label>
                    <input data-ct-name class="w-full h-10 rounded-lg border border-violet-100 px-3 text-[13px] focus:outline-none focus:ring-2 focus:ring-violet-300" placeholder="Nama anggota..." />
                </div>
                <div>
                    <label class="block text-[11px] font-bold tracking-wider uppercase text-slate-500 mb-1.5">Email</label>
                    <input data-ct-email type="email" class="w-full h-10 rounded-lg border border-violet-100 px-3 text-[13px] focus:outline-none focus:ring-2 focus:ring-violet-300" placeholder="nama@avatech.test" />
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-[11px] font-bold tracking-wider uppercase text-slate-500 mb-1.5">Role</label>
                        <select data-ct-role class="w-full h-10 rounded-lg border border-violet-100 px-3 text-[13px] focus:outline-none focus:ring-2 focus:ring-violet-300">
                            <option value="sa-qa">SA/QA</option>
                            <option value="fullstack" selected>Fullstack Dev</option>
                            <option value="uiux">UI/UX Designer</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold tracking-wider uppercase text-slate-500 mb-1.5">Level</label>
                        <select data-ct-level class="w-full h-10 rounded-lg border border-violet-100 px-3 text-[13px] focus:outline-none focus:ring-2 focus:ring-violet-300">
                            <option>Junior</option>
                            <option selected>Mid</option>
                            <option>Senior</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="px-6 py-3 border-t border-violet-100 bg-violet-50/30 flex items-center justify-end gap-2">
                <button type="button" data-create-close class="h-9 px-4 rounded-lg text-[12.5px] font-semibold text-slate-500 hover:text-slate-700 transition cursor-pointer">Batal</button>
                <button type="button" data-create-save class="inline-flex items-center gap-2 h-9 px-4 rounded-lg bg-gradient-to-r from-[#7C3AED] via-[#A855F7] to-[#C084FC] text-white font-semibold text-[12.5px] shadow-[0_2px_8px_rgba(124,58,237,0.2)] hover:scale-[1.02] transition cursor-pointer">
                    <x-heroicon-o-paper-airplane class="w-4 h-4" />
                    Kirim Undangan
                </button>
            </div>
        </div>
    </div>

    <script>
        (function () {
            const wire = () => {
                const modal   = document.querySelector('[data-create-modal="team"]');
                const trigger = document.querySelector('[data-create-trigger="team"]');
                if (! modal || ! trigger) return;
                const overlay = modal.querySelector('[data-create-overlay]');
                const panel   = modal.querySelector('[data-create-panel]');
                const grid    = document.querySelector('[data-view-panel="grid"]');
                const LS_KEY  = 'avt-team-added';
                const roleLabel = { 'sa-qa':'SA/QA', fullstack:'Fullstack Dev', uiux:'UI/UX Designer' };
                const rolePill  = { 'sa-qa':'bg-violet-50 text-violet-700', fullstack:'bg-cyan-50 text-cyan-700', uiux:'bg-pink-50 text-pink-700' };
                const esc = (s) => String(s).replace(/[&<>"]/g, (c) => ({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c]));
                const gmailUrl = (email) => 'https://mail.google.com/mail/?view=cm&fs=1&to=' + encodeURIComponent(email || '');

                const tmModalPanel = document.querySelector('[data-tm-modal] [data-tm-panel]');

                const injectDynamicMemberContent = (m) => {
                    if (! tmModalPanel) return;
                    if (tmModalPanel.querySelector('[data-member-content="' + m.id + '"]')) return;
                    const initials = (m.name || '').split(/\s+/).slice(0, 2).map(w => (w[0] || '').toUpperCase()).join('') || '?';
                    const rLabel = roleLabel[m.role_key] || m.role_key || '—';
                    const rPill  = rolePill[m.role_key] || 'bg-slate-100 text-slate-600';
                    const emailHref = m.email ? gmailUrl(m.email) : '';
                    const phoneDigits = (m.phone || '').replace(/[^0-9]/g, '');
                    const waHref = phoneDigits ? ('https://wa.me/' + phoneDigits) : '';

                    /* Slim builders to avoid duplicated innerHTML */
                    const tabBtn = (id, label, active) =>
                        '<button type="button" data-tm-tab="' + id + '" class="tm-tab h-10 px-5 rounded-xl text-[13px] font-semibold transition cursor-pointer' + (active ? ' is-active' : ' text-slate-500 hover:bg-white/70 hover:text-violet-700') + '">' + label + '</button>';
                    const emptyState = (text) =>
                        '<div class="text-[13px] text-slate-400 text-center py-10 rounded-2xl border border-violet-100 border-dashed">' + text + '</div>';
                    const contactRow = (svgPath, valueText, href, missingMsg, missingLabel) => {
                        const icon = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="w-4 h-4 text-violet-600 flex-shrink-0">' + svgPath + '</svg>';
                        const body = href
                            ? '<a href="' + esc(href) + '" target="_blank" rel="noopener" class="text-[13.5px] text-[#1E1B4B] truncate hover:text-violet-700 transition">' + esc(valueText) + '</a>'
                            : '<span class="text-[13.5px] text-slate-400 italic" title="' + esc(missingMsg) + '">' + esc(missingLabel) + '</span>';
                        return '<div class="flex items-center gap-3">' + icon + body + '</div>';
                    };
                    const SVG_MAIL = '<path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/>';
                    const SVG_PHONE = '<path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07 19.5 19.5 0 01-6-6 19.79 19.79 0 01-3.07-8.67A2 2 0 014.11 2h3a2 2 0 012 1.72c.13.96.37 1.9.72 2.81a2 2 0 01-.45 2.11L8.09 9.91a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45c.91.35 1.85.59 2.81.72A2 2 0 0122 16.92z"/>';
                    const SVG_BRIEF = '<rect x="2" y="7" width="20" height="14" rx="2"/><path d="M8 7V5a2 2 0 012-2h4a2 2 0 012 2v2"/>';

                    const kirimPesanBtn = waHref
                        ? '<a href="' + esc(waHref) + '" target="_blank" rel="noopener" class="inline-flex items-center gap-2 h-10 px-4 rounded-xl border border-violet-200 bg-white text-[13px] font-semibold text-slate-600 hover:border-emerald-400 hover:text-emerald-700 transition cursor-pointer">Kirim Pesan</a>'
                        : '<button type="button" disabled aria-disabled="true" title="Nomor WhatsApp anggota tidak tersedia" class="inline-flex items-center gap-2 h-10 px-4 rounded-xl border border-violet-100 bg-slate-50 text-[13px] font-semibold text-slate-400 cursor-not-allowed">Kirim Pesan</button>';

                    const block = document.createElement('div');
                    block.setAttribute('data-member-content', m.id);
                    block.className = 'hidden flex-col flex-1 min-h-0';
                    block.innerHTML =
                        '<div class="relative px-7 sm:px-8 pt-8 pb-6 bg-gradient-to-br from-violet-100 via-fuchsia-50 to-white">'
                        + '  <button type="button" data-modal-close aria-label="Tutup" class="absolute top-5 right-5 w-9 h-9 rounded-xl hover:bg-white/70 text-slate-500 hover:text-violet-700 flex items-center justify-center transition cursor-pointer">'
                        + '    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="w-5 h-5"><path d="M6 18L18 6M6 6l12 12"/></svg>'
                        + '  </button>'
                        + '  <div class="flex items-start gap-5">'
                        + '    <div class="w-20 h-20 rounded-2xl flex items-center justify-center text-white font-bold text-[24px]" style="background:#7C3AED">' + esc(initials) + '</div>'
                        + '    <div class="flex-1 min-w-0 pt-1">'
                        + '      <div class="flex items-center gap-2 flex-wrap mb-2">'
                        + '        <span class="inline-flex items-center text-[10.5px] font-bold tracking-wide uppercase px-2 py-1 rounded-md ' + rPill + '">' + esc(rLabel) + '</span>'
                        + '        <span class="inline-flex items-center text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 rounded-md bg-fuchsia-100 text-fuchsia-700">DEMO</span>'
                        + '      </div>'
                        + '      <h2 class="text-[26px] font-bold text-[#1E1B4B] leading-tight">' + esc(m.name) + '</h2>'
                        + '      <div class="text-[13px] text-slate-500 mt-1 truncate">' + esc(m.email || '—') + '</div>'
                        + '    </div>'
                        + '  </div>'
                        + '  <div class="mt-6 flex items-center gap-2 flex-wrap">'
                        +    tabBtn('profile', 'Profil & Skill', true)
                        +    tabBtn('load',    'Beban Kerja',    false)
                        +    tabBtn('activity','Aktivitas',      false)
                        +    tabBtn('access',  'Role & Akses',   false)
                        + '  </div>'
                        + '</div>'
                        + '<div class="flex-1 overflow-y-auto px-7 sm:px-8 py-6">'
                        + '  <div data-tm-panel="profile">'
                        + '    <div class="rounded-2xl border border-violet-100 p-5 mb-5">'
                        + '      <h4 class="text-[11px] font-bold tracking-wider uppercase text-slate-400 mb-3">Info Kontak</h4>'
                        + '      <div class="space-y-3">'
                        +          contactRow(SVG_MAIL,  m.email || '—', emailHref, 'Email anggota tidak tersedia', 'Email belum tersedia')
                        +          contactRow(SVG_PHONE, m.phone || '—', waHref,    'Nomor WhatsApp anggota tidak tersedia', 'Nomor belum tersedia')
                        + '        <div class="flex items-center gap-3">'
                        + '          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="w-4 h-4 text-violet-600 flex-shrink-0">' + SVG_BRIEF + '</svg>'
                        + '          <span class="text-[13.5px] text-[#1E1B4B]">' + esc(m.level || '—') + '</span>'
                        + '        </div>'
                        + '      </div>'
                        + '    </div>'
                        + '    <div class="rounded-2xl border border-violet-100 p-5">'
                        + '      <h4 class="text-[11px] font-bold tracking-wider uppercase text-slate-400 mb-3">Performa &amp; Bio</h4>'
                        + '      <p class="text-[13.5px] text-slate-400 italic">Skor performa, skill, dan bio akan tercatat setelah anggota menerima undangan dan mengerjakan task.</p>'
                        + '    </div>'
                        + '  </div>'
                        + '  <div data-tm-panel="load" class="hidden">'     + emptyState('Belum ada beban kerja tercatat untuk anggota baru.') + '</div>'
                        + '  <div data-tm-panel="activity" class="hidden">' + emptyState('Belum ada aktivitas tercatat.') + '</div>'
                        + '  <div data-tm-panel="access" class="hidden">'   + emptyState('Permissions akan diatur setelah anggota menerima undangan.') + '</div>'
                        + '</div>'
                        + '<div class="px-7 sm:px-8 py-5 border-t border-violet-100 bg-violet-50/30 flex items-center justify-between gap-3 flex-wrap">'
                        + '  <div class="text-[12.5px] text-slate-500 inline-flex items-center gap-2">'
                        + '    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="w-4 h-4 text-violet-500"><circle cx="12" cy="12" r="9"/><polyline points="12,7 12,12 15,15"/></svg>'
                        + '    Aktivitas terakhir: <span class="font-semibold text-[#1E1B4B]">baru saja</span>'
                        + '  </div>'
                        + '  <div class="flex items-center gap-2">'
                        +      kirimPesanBtn
                        + '    <button type="button" data-assign-trigger data-assign-id="' + esc(m.id) + '" data-assign-name="' + esc(m.name) + '" class="inline-flex items-center gap-2 h-10 px-4 rounded-xl bg-gradient-to-r from-[#7C3AED] via-[#A855F7] to-[#C084FC] text-white font-semibold text-[13px] shadow-[0_2px_8px_rgba(124,58,237,0.2)] hover:scale-[1.02] transition cursor-pointer">Atur Penugasan</button>'
                        + '  </div>'
                        + '</div>';
                    tmModalPanel.appendChild(block);
                };

                const renderCard = (m) => {
                    if (! grid) return;
                    const a = document.createElement('article');
                    a.setAttribute('data-role', m.role_key);
                    a.setAttribute('data-load', '0');
                    a.setAttribute('data-search-item', '');
                    a.setAttribute('data-sort-load', '0');
                    a.setAttribute('data-sort-name', m.name);
                    a.setAttribute('data-sort-projects', '0');
                    a.setAttribute('data-member-id', m.id);
                    a.setAttribute('data-modal-trigger', '');
                    a.className = 'group bg-white rounded-2xl border border-violet-100 shadow-[0_2px_8px_rgba(124,58,237,0.08)] hover:shadow-[0_8px_24px_rgba(124,58,237,0.12)] transition p-6 flex flex-col relative overflow-hidden cursor-pointer';
                    a.title = m.name + ' — anggota baru (demo)';
                    const initials = (m.name || '').split(/\s+/).slice(0, 2).map(w => (w[0] || '').toUpperCase()).join('') || '?';
                    a.innerHTML = ''
                        + '<span class="absolute top-0 left-6 right-6 h-[3px] rounded-b-full bg-emerald-500"></span>'
                        + '<div class="flex items-start gap-4 mb-5">'
                        + '  <div class="relative flex-shrink-0">'
                        + '    <div class="w-14 h-14 rounded-2xl flex items-center justify-center text-white font-bold text-[16px]" style="background:#7C3AED">' + esc(initials) + '</div>'
                        + '  </div>'
                        + '  <div class="flex-1 min-w-0">'
                        + '    <h3 class="text-[16px] font-bold text-[#1E1B4B] leading-tight group-hover:text-violet-700 transition truncate">' + esc(m.name) + '</h3>'
                        + '    <div class="text-[12.5px] text-slate-500 mt-0.5 truncate">' + esc(m.email) + '</div>'
                        + '    <span class="inline-flex items-center text-[10.5px] font-bold tracking-wide uppercase px-2 py-1 rounded-md mt-2 ' + (rolePill[m.role_key] || 'bg-slate-100 text-slate-600') + '">' + (roleLabel[m.role_key] || m.role_key) + '</span>'
                        + '  </div>'
                        + '</div>'
                        + '<div class="mb-4 text-[12px] text-slate-500 italic">Undangan terkirim (demo) — beban kerja akan terlihat setelah anggota menerima.</div>'
                        + '<div class="mt-auto pt-4 border-t border-violet-50 grid grid-cols-3 gap-3 text-center">'
                        + '  <div><div class="text-[18px] font-bold text-[#1E1B4B] tabular-nums">0</div><div class="text-[10.5px] text-slate-400 font-semibold uppercase tracking-wider mt-0.5">Proyek</div></div>'
                        + '  <div class="border-x border-violet-50"><div class="text-[18px] font-bold text-[#1E1B4B] tabular-nums">0</div><div class="text-[10.5px] text-slate-400 font-semibold uppercase tracking-wider mt-0.5">Task</div></div>'
                        + '  <div><div class="text-[18px] font-bold text-violet-500 tabular-nums">—</div><div class="text-[10.5px] text-slate-400 font-semibold uppercase tracking-wider mt-0.5">Skor</div></div>'
                        + '</div>';
                    grid.insertBefore(a, grid.firstChild);
                    injectDynamicMemberContent(m);
                };

                try {
                    const saved = JSON.parse(localStorage.getItem(LS_KEY) || '[]');
                    saved.forEach(renderCard);
                } catch (e) {}

                const open  = () => { modal.classList.remove('hidden'); modal.classList.add('flex'); document.body.style.overflow = 'hidden'; modal.querySelector('[data-ct-name]')?.focus(); };
                const close = () => { modal.classList.add('hidden'); modal.classList.remove('flex'); document.body.style.overflow = ''; };
                trigger.addEventListener('click', open);
                panel?.addEventListener('click', (e) => e.stopPropagation());
                overlay?.addEventListener('click', close);
                modal.querySelectorAll('[data-create-close]').forEach(b => b.addEventListener('click', close));
                document.addEventListener('keydown', (e) => { if (e.key === 'Escape' && ! modal.classList.contains('hidden')) close(); });

                modal.querySelector('[data-create-save]')?.addEventListener('click', () => {
                    const m = {
                        id: Date.now(),
                        name:  (modal.querySelector('[data-ct-name]').value || '').trim(),
                        email: (modal.querySelector('[data-ct-email]').value || '').trim(),
                        role_key: modal.querySelector('[data-ct-role]').value,
                        level:    modal.querySelector('[data-ct-level]').value,
                    };
                    if (! m.name || ! m.email) {
                        window.toast && window.toast('Lengkapi nama dan email.');
                        return;
                    }
                    renderCard(m);
                    try {
                        const cur = JSON.parse(localStorage.getItem(LS_KEY) || '[]');
                        cur.push(m);
                        localStorage.setItem(LS_KEY, JSON.stringify(cur));
                    } catch (e) {}
                    modal.querySelectorAll('input').forEach(i => i.value = '');
                    close();
                    window.toast && window.toast('Undangan terkirim ke ' + m.email + ' (demo).');
                });
            };
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', wire);
            } else {
                wire();
            }
        })();
    </script>
</x-layouts.authenticated>
