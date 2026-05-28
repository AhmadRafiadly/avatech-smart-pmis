@php
    $role    = auth()->user()?->roles?->first()?->name;
    $isCeo   = $role === 'ceo_pm';
    $canEdit = ! $isCeo;
    $useReferenceProjectData = (bool) ($useReferenceProjectData ?? false);

    $tabs = [
        ['id' => 'overview',   'label' => 'Overview',         'count' => 0],
        ['id' => 'workspace',  'label' => 'Kanban Workspace', 'count' => 14],
        ['id' => 'aiplanning', 'label' => 'AI Planning',      'count' => 1],
        ['id' => 'qc',         'label' => 'Quality Control',  'count' => 10],
    ];

    $steps = [
        ['label' => 'Gathering',   'state' => 'done',   'icon' => 'check',         'tag' => 'Completed'],
        ['label' => 'Planning',    'state' => 'done',   'icon' => 'check',         'tag' => 'Completed'],
        ['label' => 'Design',      'state' => 'active', 'icon' => 'paint-brush',   'tag' => 'In Progress'],
        ['label' => 'Development', 'state' => 'todo',   'icon' => 'code-bracket',  'tag' => 'Pending'],
        ['label' => 'QC',          'state' => 'todo',   'icon' => 'bug-ant',       'tag' => 'Pending'],
    ];

    $metrics = [
        ['code' => 'MOD',  'value' => '0/6',  'label' => 'Modul Disetujui',    'color' => '#3B82F6', 'progress' => 0,   'sub' => '100% Terdefinisi'],
        ['code' => 'TASK', 'value' => '0/14', 'label' => 'Task Selesai',       'color' => '#7C3AED', 'progress' => 0,   'sub' => '0 Selesai • 14 Proses'],
        ['code' => 'MOM',  'value' => '1/1',  'label' => 'MoM AI Rapi',        'color' => '#10B981', 'progress' => 100, 'sub' => 'Semua disetujui'],
        ['code' => 'QC',   'value' => '0%',   'label' => 'Tingkat Lulus Test', 'color' => '#F59E0B', 'progress' => 0,   'sub' => 'Pass Rate'],
    ];

    $statusCards = [
        ['count' => 0, 'label' => 'Disetujui',       'bg' => '#ECFDF5', 'border' => '#A7F3D0', 'value' => '#047857', 'caption' => '#059669'],
        ['count' => 0, 'label' => 'Menunggu Dev',    'bg' => '#EFF6FF', 'border' => '#BFDBFE', 'value' => '#1D4ED8', 'caption' => '#2563EB'],
        ['count' => 6, 'label' => 'Menunggu Design', 'bg' => '#FFFBEB', 'border' => '#FDE68A', 'value' => '#B45309', 'caption' => '#D97706'],
        ['count' => 0, 'label' => 'Perlu Revisi',    'bg' => '#FFF1F2', 'border' => '#FECDD3', 'value' => '#BE123C', 'caption' => '#E11D48'],
    ];

    $modStatusStyles = [
        'Menunggu Desain' => ['bg' => '#FEF3C7', 'color' => '#92400E'],
        'Perlu Revisi'    => ['bg' => '#DBEAFE', 'color' => '#1E40AF'],
        'Menunggu Dev'    => ['bg' => '#EDE9FE', 'color' => '#5B21B6'],
        'Disetujui'       => ['bg' => '#D1FAE5', 'color' => '#065F46'],
    ];

    $modules = [
        ['name' => 'Modul Autentikasi dan Akses Pengguna', 'tasks_done' => 0, 'tasks_total' => 2, 'hours' => 32, 'status' => 'Menunggu Desain'],
        ['name' => 'Project Hub dan Kanban Operasional',   'tasks_done' => 0, 'tasks_total' => 3, 'hours' => 48, 'status' => 'Menunggu Desain'],
        ['name' => 'Quality Control Black-Box Testing',    'tasks_done' => 0, 'tasks_total' => 2, 'hours' => 24, 'status' => 'Menunggu Desain'],
        ['name' => 'Dashboard & Pelaporan Eksekutif',      'tasks_done' => 0, 'tasks_total' => 2, 'hours' => 32, 'status' => 'Menunggu Desain'],
        ['name' => 'AI Smart Reminders Engine',            'tasks_done' => 0, 'tasks_total' => 3, 'hours' => 48, 'status' => 'Menunggu Desain'],
        ['name' => 'CRM Lite & WhatsApp Integration',      'tasks_done' => 0, 'tasks_total' => 2, 'hours' => 24, 'status' => 'Menunggu Desain'],
    ];

    $activities = [
        ['dot' => '#EF4444', 'time' => 'Hari ini, 10:42', 'title' => 'Testcase Gagal',  'text' => 'Checkout error pada edge case kupon diskon bertumpuk.'],
        ['dot' => '#10B981', 'time' => 'Hari ini, 09:15', 'title' => 'Testcase Lulus',  'text' => 'Modul Product Catalog lolos UAT internal.'],
        ['dot' => '#C084FC', 'time' => 'Kemarin, 16:30', 'title' => 'MoM Dibuat',       'text' => 'Sync mingguan dengan klien membahas revisi UI Cart.'],
        ['dot' => '#F59E0B', 'time' => 'Kemarin, 14:00', 'title' => 'MoM Diperbaiki',   'text' => 'Penambahan poin integrasi logistik JNE pada dokumen.'],
        ['dot' => '#7C3AED', 'time' => '4 hari lalu',     'title' => 'WBS Dibuat',      'text' => 'AI menghasilkan 6 modul dari MoM 06 Mei 2026.'],
    ];

    $kanban = [
        ['id' => 'todo',    'label' => 'Todo',    'color' => '#475569', 'bg' => '#F1F5F9', 'tasks' => [
            ['module' => 'Modul Autentikasi dan Ak...',     'priority' => 'High',   'title' => 'Validasi proteksi akses resource',  'assignee' => 'Belum Ditugaskan'],
            ['module' => 'Project Hub dan Kanban O...',     'priority' => 'Medium', 'title' => 'Tampilkan ringkasan proyek terpadu','assignee' => 'Belum Ditugaskan'],
            ['module' => 'Project Hub dan Kanban O...',     'priority' => 'High',   'title' => 'Pastikan CEO hanya read-only',      'assignee' => 'Belum Ditugaskan'],
            ['module' => 'Quality Control Black-Bo...',     'priority' => 'High',   'title' => 'Siapkan daftar test case per modul','assignee' => 'Belum Ditugaskan'],
            ['module' => 'Quality Control Black-Bo...',     'priority' => 'Medium', 'title' => 'Catat hasil eksekusi Pass atau Fail','assignee' => 'Belum Ditugaskan'],
            ['module' => 'Modul Autentikasi dan Ak...',     'priority' => 'High',   'title' => 'Implementasi login berbasis role',  'assignee' => 'Belum Ditugaskan'],
        ]],
        ['id' => 'doing',   'label' => 'Doing',   'color' => '#2563EB', 'bg' => '#DBEAFE', 'tasks' => [
            ['module' => 'Modul Autentikasi dan Ak...', 'priority' => 'High',   'title' => 'Implementasi login berbasis role',  'assignee' => 'Belum Ditugaskan'],
            ['module' => 'Project Hub dan Kanban O...', 'priority' => 'Medium', 'title' => 'Integrasikan task ke board Kanban', 'assignee' => 'Belum Ditugaskan'],
        ]],
        ['id' => 'testing', 'label' => 'Testing', 'color' => '#D97706', 'bg' => '#FEF3C7', 'tasks' => []],
        ['id' => 'done',    'label' => 'Done',    'color' => '#059669', 'bg' => '#D1FAE5', 'tasks' => []],
    ];

    $moms = [
        ['date' => '12 May 2026', 'tag' => 'AI RAPI', 'title' => 'Kickoff Meeting: Project Charter', 'body' => 'Diskusi awal mengenai scope, flow registrasi user, dan penentuan timeline fase discovery.'],
    ];

    $testCases = [
        ['id' => 'TC-0001', 'scenario' => 'Login dengan kredensial valid',                       'module' => 'Modul Autentikasi',   'status' => 'lulus'],
        ['id' => 'TC-0002', 'scenario' => 'Login dengan password salah',                         'module' => 'Modul Autentikasi',   'status' => 'lulus'],
        ['id' => 'TC-0003', 'scenario' => 'Logout dari sistem',                                  'module' => 'Modul Autentikasi',   'status' => 'lulus'],
        ['id' => 'TC-0004', 'scenario' => 'Tampilkan grafik penjualan hari ini',                 'module' => 'Dashboard & Laporan', 'status' => 'lulus'],
        ['id' => 'TC-0005', 'scenario' => 'Filter grafik berdasarkan tanggal',                   'module' => 'Dashboard & Laporan', 'status' => 'lulus'],
        ['id' => 'TC-0006', 'scenario' => 'Total penjualan hari ini akurat',                     'module' => 'Dashboard & Laporan', 'status' => 'lulus'],
        ['id' => 'TC-0011', 'scenario' => 'Validasi akses utama modul Dashboard & Laporan',      'module' => 'Dashboard & Laporan', 'status' => 'lulus'],
        ['id' => 'TC-0012', 'scenario' => 'Validasi pembatasan akses modul Dashboard & Laporan', 'module' => 'Dashboard & Laporan', 'status' => 'gagal'],
        ['id' => 'TC-0013', 'scenario' => 'Validasi audit trail modul Dashboard & Laporan',      'module' => 'Dashboard & Laporan', 'status' => 'pending'],
        ['id' => 'TC-0014', 'scenario' => 'Validasi kestabilan data setelah refresh',            'module' => 'Dashboard & Laporan', 'status' => 'pending'],
    ];

    $workspaceTaskTotal = 14;
    $workspaceTaskDone = 0;
    $qcSummary = ['lulus' => 7, 'gagal' => 1, 'pending' => 2];

    if (! $useReferenceProjectData) {
        $tabs = $dbTabs ?? [
            ['id' => 'overview',   'label' => 'Overview',         'count' => 0],
            ['id' => 'workspace',  'label' => 'Kanban Workspace', 'count' => 0],
            ['id' => 'aiplanning', 'label' => 'AI Planning',      'count' => 0],
            ['id' => 'qc',         'label' => 'Quality Control',  'count' => 0],
        ];

        $phaseLabels = ['Gathering', 'Planning', 'Design', 'Development', 'QC'];
        $phaseIcons = [
            'Gathering' => 'clipboard',
            'Planning' => 'clipboard',
            'Design' => 'paint-brush',
            'Development' => 'code-bracket',
            'QC' => 'bug-ant',
        ];
        $phaseIndex = array_search($project->phase, $phaseLabels, true);
        if ($phaseIndex === false) {
            $phaseIndex = 1;
        }

        $steps = collect($phaseLabels)->map(function ($label, $index) use ($phaseIndex, $phaseIcons) {
            $state = $index < $phaseIndex ? 'done' : ($index === $phaseIndex ? 'active' : 'todo');

            return [
                'label' => $label,
                'state' => $state,
                'icon' => $phaseIcons[$label],
                'tag' => $state === 'done' ? 'Completed' : ($state === 'active' ? 'In Progress' : 'Pending'),
            ];
        })->all();

        $metrics = $dbMetrics ?? [
            ['code' => 'MOD',  'value' => '0/0', 'label' => 'Modul Disetujui',    'color' => '#3B82F6', 'progress' => 0, 'sub' => 'Belum ada modul'],
            ['code' => 'TASK', 'value' => '0/0', 'label' => 'Task Selesai',       'color' => '#7C3AED', 'progress' => 0, 'sub' => 'Belum ada task'],
            ['code' => 'MOM',  'value' => '0/0', 'label' => 'MoM AI Rapi',        'color' => '#10B981', 'progress' => 0, 'sub' => 'Belum ada MoM'],
            ['code' => 'QC',   'value' => '0%',  'label' => 'Tingkat Lulus Test', 'color' => '#F59E0B', 'progress' => 0, 'sub' => 'Pass Rate'],
        ];

        $statusCards = $dbStatusCards ?? [
            ['count' => 0, 'label' => 'Disetujui',       'bg' => '#ECFDF5', 'border' => '#A7F3D0', 'value' => '#047857', 'caption' => '#059669'],
            ['count' => 0, 'label' => 'Menunggu Dev',    'bg' => '#EFF6FF', 'border' => '#BFDBFE', 'value' => '#1D4ED8', 'caption' => '#2563EB'],
            ['count' => 0, 'label' => 'Menunggu Design', 'bg' => '#FFFBEB', 'border' => '#FDE68A', 'value' => '#B45309', 'caption' => '#D97706'],
            ['count' => 0, 'label' => 'Perlu Revisi',    'bg' => '#FFF1F2', 'border' => '#FECDD3', 'value' => '#BE123C', 'caption' => '#E11D48'],
        ];

        $modules = $dbModules ?? [];
        $activities = $dbActivities ?? [];
        $kanban = $dbKanban ?? [
            ['id' => 'todo',    'label' => 'Todo',    'color' => '#475569', 'bg' => '#F1F5F9', 'tasks' => []],
            ['id' => 'doing',   'label' => 'Doing',   'color' => '#2563EB', 'bg' => '#DBEAFE', 'tasks' => []],
            ['id' => 'testing', 'label' => 'Testing', 'color' => '#D97706', 'bg' => '#FEF3C7', 'tasks' => []],
            ['id' => 'done',    'label' => 'Done',    'color' => '#059669', 'bg' => '#D1FAE5', 'tasks' => []],
        ];
        $moms = $dbMoms ?? [];
        $testCases = $dbQcTests ?? [];
        $workspaceTaskTotal = $dbTaskTotal ?? 0;
        $workspaceTaskDone = $dbTaskDone ?? 0;
        /* Map DB summary (passed/failed/pending/retest) to the legacy
         * lulus/gagal/pending keys the QC panel header was originally built for,
         * keeping retest as its own counter. */
        $qcSummary = [
            'lulus'   => $dbQcSummary['passed']  ?? 0,
            'gagal'   => $dbQcSummary['failed']  ?? 0,
            'pending' => $dbQcSummary['pending'] ?? 0,
            'retest'  => $dbQcSummary['retest']  ?? 0,
            'total'   => $dbQcSummary['total']   ?? 0,
        ];
    }

    $tcStatusPill = [
        'lulus'   => 'bg-emerald-50 text-emerald-700 border border-emerald-100',
        'gagal'   => 'bg-rose-50 text-rose-700 border border-rose-100',
        'pending' => 'bg-amber-50 text-amber-700 border border-amber-100',
        'passed'  => 'bg-emerald-50 text-emerald-700 border border-emerald-100',
        'failed'  => 'bg-rose-50 text-rose-700 border border-rose-100',
        'retest'  => 'bg-violet-50 text-violet-700 border border-violet-100',
    ];

    $tcStatusLabel = [
        'lulus'   => 'Lulus',
        'gagal'   => 'Gagal',
        'pending' => 'Pending',
        'passed'  => 'Lulus',
        'failed'  => 'Gagal',
        'retest'  => 'Retest',
    ];

    $tcPriorityPill = [
        'low'    => 'bg-slate-100 text-slate-600 border border-slate-200',
        'medium' => 'bg-amber-50 text-amber-700 border border-amber-100',
        'high'   => 'bg-rose-50 text-rose-700 border border-rose-100',
    ];
@endphp

<x-layouts.authenticated :title="$title">

    <style>
        /* === Stitch-style stepper === */
        .pd-step-circle      { width: 56px; height: 56px; border-radius: 9999px; display: flex; align-items: center; justify-content: center; position: relative; z-index: 1; }
        .pd-step-done        { background: #10B981; color: #fff; box-shadow: 0 10px 24px -8px rgba(16,185,129,0.45); }
        .pd-step-active      { background: linear-gradient(135deg, #7C3AED 0%, #C084FC 100%); color: #fff; box-shadow: 0 0 0 4px #fff, 0 12px 28px -8px rgba(124,58,237,0.45); }
        .pd-step-active .pd-step-icon { animation: pd-pulse 1.6s ease-in-out infinite; }
        .pd-step-todo        { background: #F1F5F9; color: #94A3B8; border: 2px solid #E2E8F0; }
        .pd-step-label-done  { color: #1E1B4B; }
        .pd-step-tag-done    { color: #059669; }
        .pd-step-label-act   { color: #7C3AED; }
        .pd-step-tag-act     { color: #7C3AED; }
        .pd-step-label-todo  { color: #94A3B8; }
        .pd-step-tag-todo    { color: #94A3B8; }
        .pd-step-line        { position: absolute; top: 28px; height: 2px; z-index: 0; }
        .pd-line-done        { background: #10B981; }
        .pd-line-mid         { background: linear-gradient(90deg, #10B981 0%, #C084FC 100%); }
        .pd-line-todo        { background: #E2E8F0; }
        @keyframes pd-pulse {
            0%, 100% { transform: scale(1); opacity: 1; }
            50%      { transform: scale(1.08); opacity: 0.85; }
        }

        /* === Kanban column accents === */
        .pd-kbn-col-todo    { border-top: 3px solid #94A3B8; }
        .pd-kbn-col-doing   { border-top: 3px solid #3B82F6; }
        .pd-kbn-col-testing { border-top: 3px solid #F59E0B; }
        .pd-kbn-col-done    { border-top: 3px solid #10B981; }

        /* === Pill tabs === */
        .pd-tab.is-active {
            background: linear-gradient(135deg, #7C3AED 0%, #C084FC 100%);
            color: #fff;
            box-shadow: 0 8px 20px rgba(124,58,237,0.25);
        }
        .pd-tab.is-active .pd-tab-badge { background: rgba(255,255,255,0.25); color: #fff; }

        /* === Stitch card border (AI Planning / QC) === */
        .pd-stitch-card { border: 1.5px solid #E9D5FF; box-shadow: 0 2px 8px rgba(124,58,237,0.08); }
        .pd-section-bar { width: 6px; height: 32px; background: linear-gradient(180deg, #7C3AED 0%, #C084FC 100%); border-radius: 9999px; }
        .pd-detail-open-label { display: none; }
        details[open] .pd-detail-open-label { display: inline; }
        details[open] .pd-detail-closed-label { display: none; }
    </style>

    {{-- =============== HERO =============== --}}
    <section class="relative overflow-hidden rounded-2xl border border-violet-100 shadow-[0_2px_8px_rgba(124,58,237,0.08)] bg-white/70 backdrop-blur-[12px] p-8 mb-8">
        <span class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-[#7C3AED] to-[#EC4899]"></span>
        <div class="flex items-center gap-3 flex-wrap mb-5">
            <span class="inline-flex items-center gap-1.5 text-[10px] font-bold tracking-wider uppercase rounded-full px-2.5 py-0.5 bg-emerald-50/60 text-emerald-600 border border-emerald-100/60">
                <span class="w-1 h-1 rounded-full bg-emerald-500"></span>
                {{ $statusUi['label'] }}
            </span>
            @if ($project->archived_at)
                <span class="inline-flex items-center gap-1.5 text-[10px] font-bold tracking-wider uppercase rounded-full px-2.5 py-0.5 bg-slate-100 text-slate-600 border border-slate-200">
                    <x-heroicon-o-archive-box class="w-3 h-3" />
                    Archived
                </span>
            @endif
            <span class="h-3 w-px bg-slate-200"></span>
            <span class="text-[12px] text-slate-500 inline-flex items-center gap-1.5">
                <x-heroicon-o-user class="w-3.5 h-3.5 opacity-60" />
                Dibuat oleh: <span class="font-semibold text-slate-700">{{ $createdBy }}</span>
            </span>
            <span class="text-[12px] text-slate-500 inline-flex items-center gap-1.5">
                <x-heroicon-o-calendar class="w-3.5 h-3.5 opacity-60" />
                Dibuat: <span class="text-slate-700">{{ $createdAt }}</span>
            </span>
            @if ($isCeo)
                <span class="ml-auto inline-flex items-center gap-1.5 text-[10.5px] font-bold tracking-wider uppercase px-2.5 py-1 rounded-full bg-violet-100 text-violet-700">
                    <x-heroicon-o-eye class="w-3 h-3" />
                    Hanya Lihat
                </span>
            @endif
        </div>
        <div class="flex items-center gap-4 flex-wrap">
            <div class="w-14 h-14 rounded-2xl flex items-center justify-center text-white font-bold text-[16px] flex-shrink-0" style="background: {{ $project->color }};">
                {{ $project->code }}
            </div>
            <div class="min-w-0">
                <h1 class="text-[40px] leading-tight font-extrabold tracking-tight text-[#1E1B4B]">{{ $project->name }}</h1>
                <div class="text-[13px] text-slate-500 mt-1 inline-flex items-center gap-3 flex-wrap">
                    <span class="inline-flex items-center gap-1.5">
                        <x-heroicon-o-building-office class="w-3.5 h-3.5" />
                        {{ $project->client?->name ?? '—' }}
                    </span>
                    <span class="w-1 h-1 rounded-full bg-slate-300"></span>
                    <span class="inline-flex items-center gap-1.5">
                        <x-heroicon-o-tag class="w-3.5 h-3.5" />
                        {{ $project->phase }}
                    </span>
                    <span class="w-1 h-1 rounded-full bg-slate-300"></span>
                    <span class="inline-flex items-center gap-1.5">
                        <x-heroicon-o-calendar-days class="w-3.5 h-3.5" />
                        Due {{ $dueFormatted }}
                    </span>
                </div>
            </div>
        </div>
        <p class="mt-4 text-[14px] text-slate-500 max-w-3xl leading-relaxed">{{ $desc }}</p>

        <div class="mt-5 flex flex-wrap items-center gap-2">
            <a href="{{ route('projects.export.wbs', $project) }}" title="Unduh WBS sebagai PDF" data-loading-link data-loading-label="Menyiapkan PDF..."
               class="inline-flex items-center gap-2 h-10 px-4 rounded-xl border border-violet-200 bg-white text-[13px] font-semibold text-slate-600 hover:border-violet-400 hover:text-violet-700 transition cursor-pointer no-underline">
                <x-heroicon-o-document-text class="w-4 h-4" />
                <span data-loading-text>Export WBS (PDF)</span>
            </a>
            <a href="{{ route('projects.export.test-cases', $project) }}" title="Unduh Test Case QC sebagai PDF" data-loading-link data-loading-label="Menyiapkan PDF..."
               class="inline-flex items-center gap-2 h-10 px-4 rounded-xl border border-violet-200 bg-white text-[13px] font-semibold text-slate-600 hover:border-violet-400 hover:text-violet-700 transition cursor-pointer no-underline">
                <x-heroicon-o-beaker class="w-4 h-4" />
                <span data-loading-text>Export Test Case (PDF)</span>
            </a>
            @if ($project->archived_at)
                <form method="POST" action="{{ route('projects.restore', $project) }}">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="inline-flex items-center gap-2 h-10 px-4 rounded-xl border border-emerald-100 bg-emerald-50 text-[13px] font-semibold text-emerald-700 hover:bg-emerald-100 transition cursor-pointer">
                        <x-heroicon-o-arrow-path class="w-4 h-4" />
                        Restore Project
                    </button>
                </form>
            @endif
        </div>
    </section>

    {{-- =============== STITCH-STYLE STEPPER =============== --}}
    <section class="bg-white rounded-2xl border border-violet-100 shadow-[0_2px_8px_rgba(124,58,237,0.08)] p-10 mb-8 relative overflow-hidden">
        <div class="relative flex items-start justify-between">
            {{-- Connector lines (positioned behind circles) --}}
            @php $segmentCount = count($steps) - 1; @endphp
            @for ($i = 0; $i < $segmentCount; $i++)
                @php
                    $left  = (($i + 0.5) / count($steps)) * 100;
                    $right = 100 - ((($i + 1.5) / count($steps)) * 100);
                    $prev  = $steps[$i]['state'];
                    $next  = $steps[$i + 1]['state'];
                    if ($prev === 'done' && $next === 'done')          $cls = 'pd-line-done';
                    elseif ($prev === 'done' && $next === 'active')    $cls = 'pd-line-mid';
                    elseif ($prev === 'active' && $next === 'todo')    $cls = 'pd-line-mid';
                    else                                                $cls = 'pd-line-todo';
                @endphp
                <div class="pd-step-line {{ $cls }}" style="left: {{ $left }}%; right: {{ $right }}%;"></div>
            @endfor

            @foreach ($steps as $s)
                <div class="flex flex-col items-center gap-4 relative z-10" style="width: {{ 100 / count($steps) }}%;">
                    <div @class([
                        'pd-step-circle',
                        'pd-step-done'   => $s['state'] === 'done',
                        'pd-step-active' => $s['state'] === 'active',
                        'pd-step-todo'   => $s['state'] === 'todo',
                    ])>
                        <span class="pd-step-icon">
                            @if ($s['state'] === 'done')
                                <x-heroicon-s-check class="w-7 h-7" />
                            @elseif ($s['icon'] === 'paint-brush')
                                <x-heroicon-o-paint-brush class="w-6 h-6" />
                            @elseif ($s['icon'] === 'code-bracket')
                                <x-heroicon-o-code-bracket class="w-6 h-6" />
                            @elseif ($s['icon'] === 'bug-ant')
                                <x-heroicon-o-bug-ant class="w-6 h-6" />
                            @else
                                <x-heroicon-o-clipboard-document class="w-6 h-6" />
                            @endif
                        </span>
                    </div>
                    <div class="text-center">
                        <div @class([
                            'text-[14px] font-bold',
                            'pd-step-label-done' => $s['state'] === 'done',
                            'pd-step-label-act'  => $s['state'] === 'active',
                            'pd-step-label-todo' => $s['state'] === 'todo',
                        ])>{{ $s['label'] }}</div>
                        <div @class([
                            'text-[10px] font-bold uppercase tracking-wider mt-1',
                            'pd-step-tag-done' => $s['state'] === 'done',
                            'pd-step-tag-act'  => $s['state'] === 'active',
                            'pd-step-tag-todo' => $s['state'] === 'todo',
                        ])>{{ $s['tag'] }}</div>
                    </div>
                </div>
            @endforeach
        </div>
    </section>

    {{-- =============== METRIC CARDS =============== --}}
    <section class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
        @foreach ($metrics as $m)
            <article class="bg-white rounded-2xl border border-violet-100 shadow-[0_2px_8px_rgba(124,58,237,0.08)] overflow-hidden">
                <div class="h-[3px] w-full" style="background: {{ $m['color'] }};"></div>
                <div class="p-5">
                    <div class="text-[36px] font-extrabold text-[#1E1B4B] leading-none mb-1">{{ $m['value'] }}</div>
                    <div class="text-[10px] font-bold tracking-wider uppercase text-slate-400 mb-3">{{ $m['label'] }}</div>
                    <div class="w-full rounded-full h-2 mb-2" style="background: {{ $m['color'] }}1A;">
                        <div class="h-full rounded-full" style="width: {{ $m['progress'] }}%; background: {{ $m['color'] }};"></div>
                    </div>
                    <div class="text-[11px] text-slate-400">{{ $m['sub'] }}</div>
                </div>
            </article>
        @endforeach
    </section>

    {{-- =============== TABS =============== --}}
    <section class="bg-white rounded-2xl border border-violet-100 shadow-[0_2px_8px_rgba(124,58,237,0.08)] p-2 mb-6">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-2">
            @foreach ($tabs as $idx => $t)
                <button
                    type="button"
                    data-tab="{{ $t['id'] }}"
                    @class([
                        'pd-tab h-12 rounded-xl text-[14px] font-semibold transition inline-flex items-center justify-center gap-2 cursor-pointer',
                        'is-active' => $idx === 0,
                        'text-slate-500 hover:text-slate-700 hover:bg-violet-50/60' => $idx !== 0,
                    ])
                >
                    <span>{{ $t['label'] }}</span>
                    <span @class([
                        'pd-tab-badge text-[10.5px] font-bold rounded-full px-1.5 py-0.5',
                        'bg-violet-100 text-violet-700' => $idx !== 0,
                    ])>{{ $t['count'] }}</span>
                </button>
            @endforeach
        </div>
    </section>

    {{-- =============== OVERVIEW =============== --}}
    <div id="overview" data-panel="overview" class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-white rounded-2xl border border-violet-100 shadow-[0_2px_8px_rgba(124,58,237,0.08)] p-6">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-[16px] font-bold text-[#1E1B4B]">Pipeline WBS</h3>
                    <span class="text-[13px] font-semibold text-slate-400">{{ count($modules) }} total modul</span>
                </div>

                @if ($canEdit && ! $useReferenceProjectData)
                    <form method="POST" action="{{ route('projects.modules.store', $project) }}" class="mb-6 rounded-xl border border-violet-100 bg-violet-50/30 p-4">
                        @csrf
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-[10.5px] font-bold tracking-wider uppercase text-slate-500 mb-2">Judul Modul</label>
                                <input name="title" value="{{ old('title') }}" class="w-full h-10 rounded-xl border border-violet-100 px-3 text-[13px] focus:outline-none focus:ring-2 focus:ring-violet-300" placeholder="Contoh: Modul Autentikasi" />
                                @error('title')<p class="mt-1 text-[11.5px] text-rose-600">{{ $message }}</p>@enderror
                            </div>
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-[10.5px] font-bold tracking-wider uppercase text-slate-500 mb-2">Status</label>
                                    <select name="status" class="w-full h-10 rounded-xl border border-violet-100 px-3 text-[13px] bg-white focus:outline-none focus:ring-2 focus:ring-violet-300">
                                        @foreach ($moduleStatusOptions as $value => $label)
                                            <option value="{{ $value }}" @selected(old('status', 'pending_design') === $value)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                    @error('status')<p class="mt-1 text-[11.5px] text-rose-600">{{ $message }}</p>@enderror
                                </div>
                                <div>
                                    <label class="block text-[10.5px] font-bold tracking-wider uppercase text-slate-500 mb-2">Estimasi Jam</label>
                                    <input name="estimate_hours" type="number" min="0" max="999" value="{{ old('estimate_hours', 0) }}" class="w-full h-10 rounded-xl border border-violet-100 px-3 text-[13px] focus:outline-none focus:ring-2 focus:ring-violet-300" />
                                    @error('estimate_hours')<p class="mt-1 text-[11.5px] text-rose-600">{{ $message }}</p>@enderror
                                </div>
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-[10.5px] font-bold tracking-wider uppercase text-slate-500 mb-2">Deskripsi</label>
                                <textarea name="description" rows="2" class="w-full rounded-xl border border-violet-100 px-3 py-2 text-[13px] focus:outline-none focus:ring-2 focus:ring-violet-300 resize-y" placeholder="Opsional">{{ old('description') }}</textarea>
                                @error('description')<p class="mt-1 text-[11.5px] text-rose-600">{{ $message }}</p>@enderror
                            </div>
                        </div>
                        <div class="mt-3 flex justify-end">
                            <button type="submit" class="inline-flex items-center gap-2 h-10 px-4 rounded-xl bg-[#1E1B4B] text-white font-semibold text-[13px] hover:bg-violet-900 transition cursor-pointer">
                                <x-heroicon-o-plus class="w-4 h-4" />
                                Tambah Modul
                            </button>
                        </div>
                    </form>
                @endif

                @php
                    $segColors = ['#10B981', '#3B82F6', '#F59E0B', '#F43F5E'];
                    $totalCount = collect($statusCards)->sum('count');
                    $segments = [];
                    $cum = 0.0;
                    foreach ($statusCards as $i => $sc) {
                        if ($totalCount > 0 && $sc['count'] > 0) {
                            $pct = ($sc['count'] / $totalCount) * 100;
                            $segments[] = [
                                'pct'    => $pct,
                                'gap'    => 100 - $pct,
                                'offset' => -$cum,
                                'color'  => $segColors[$i] ?? '#7C3AED',
                            ];
                            $cum += $pct;
                        }
                    }
                @endphp

                <div class="flex flex-col md:flex-row items-center md:items-start gap-8 mb-8">
                    <div class="relative w-36 h-36 shrink-0">
                        <svg viewBox="0 0 36 36" class="absolute inset-0 w-full h-full -rotate-90" aria-hidden="true">
                            <circle cx="18" cy="18" r="15.91549430918954" fill="none" stroke="#F3E8FF" stroke-width="3.5"></circle>
                            @foreach ($segments as $seg)
                                <circle
                                    cx="18" cy="18" r="15.91549430918954" fill="none"
                                    stroke="{{ $seg['color'] }}"
                                    stroke-width="3.5"
                                    stroke-dasharray="{{ number_format($seg['pct'], 4, '.', '') }} {{ number_format($seg['gap'], 4, '.', '') }}"
                                    stroke-dashoffset="{{ number_format($seg['offset'], 4, '.', '') }}"
                                    stroke-linecap="butt"
                                ></circle>
                            @endforeach
                        </svg>
                        <div class="absolute inset-0 flex flex-col items-center justify-center pointer-events-none">
                            <span class="text-[32px] font-extrabold text-[#1E1B4B] leading-none">{{ count($modules) }}</span>
                            <span class="text-[10px] font-bold tracking-wider uppercase text-slate-400 mt-1">Modul</span>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4 flex-1 w-full">
                        @foreach ($statusCards as $sc)
                            <div class="p-4 rounded-xl flex flex-col items-start gap-1" style="background: {{ $sc['bg'] }}; border: 1px solid {{ $sc['border'] }};">
                                <span class="text-[24px] font-extrabold leading-none" style="color: {{ $sc['value'] }};">{{ $sc['count'] }}</span>
                                <span class="text-[11px] font-bold uppercase tracking-tight" style="color: {{ $sc['caption'] }};">{{ $sc['label'] }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="space-y-3">
                    @forelse ($modules as $mod)
                        @php $st = $modStatusStyles[$mod['status']] ?? ['bg' => '#F3F4F6', 'color' => '#374151']; @endphp
                        <div class="p-4 rounded-xl border border-violet-50 hover:bg-[#FAF5FF] transition">
                            <div class="flex items-center justify-between gap-4">
                                <div class="min-w-0">
                                    <h4 class="text-[14px] font-bold text-[#1E1B4B] truncate">{{ $mod['name'] }}</h4>
                                    <div class="text-[12px] text-slate-500 mt-1 inline-flex items-center gap-3 flex-wrap">
                                        <span>Task: <span class="font-semibold text-violet-600">{{ $mod['tasks_done'] }}/{{ $mod['tasks_total'] }}</span></span>
                                        <span class="text-violet-600 font-semibold px-1.5 py-0.5 bg-violet-50 rounded">Estimation: {{ $mod['hours'] }}h</span>
                                    </div>
                                    @if (! empty($mod['description']))
                                        <p class="mt-2 text-[12px] text-slate-500 leading-relaxed">{{ $mod['description'] }}</p>
                                    @endif
                                </div>
                                <span class="text-[10px] font-bold tracking-wider uppercase px-2.5 py-1 rounded-full shrink-0 border" style="background: {{ $st['bg'] }}; color: {{ $st['color'] }}; border-color: {{ $st['color'] }}20;">
                                    {{ $mod['status'] }}
                                </span>
                            </div>

                            @if ($canEdit && ! $useReferenceProjectData && ! empty($mod['id']))
                                <details class="mt-3 rounded-lg border border-violet-100 bg-white/70 px-3 py-2">
                                    <summary class="cursor-pointer select-none text-[12px] font-bold text-violet-700">Edit Modul</summary>
                                    <form method="POST" action="{{ route('projects.modules.update', [$project, $mod['id']]) }}" class="mt-3 grid grid-cols-1 md:grid-cols-2 gap-3">
                                        @csrf
                                        @method('PUT')
                                        <div>
                                            <label class="block text-[10px] font-bold tracking-wider uppercase text-slate-400 mb-1.5">Judul</label>
                                            <input name="title" value="{{ old('module_title_'.$mod['id'], $mod['name']) }}" class="w-full h-9 rounded-lg border border-violet-100 px-3 text-[12.5px] focus:outline-none focus:ring-2 focus:ring-violet-300" />
                                        </div>
                                        <div class="grid grid-cols-2 gap-2">
                                            <div>
                                                <label class="block text-[10px] font-bold tracking-wider uppercase text-slate-400 mb-1.5">Status</label>
                                                <select name="status" class="w-full h-9 rounded-lg border border-violet-100 bg-white px-3 text-[12.5px] focus:outline-none focus:ring-2 focus:ring-violet-300">
                                                    @foreach ($moduleStatusOptions as $value => $label)
                                                        <option value="{{ $value }}" @selected(($mod['status_key'] ?? '') === $value)>{{ $label }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div>
                                                <label class="block text-[10px] font-bold tracking-wider uppercase text-slate-400 mb-1.5">Jam</label>
                                                <input name="estimate_hours" type="number" min="0" max="999" value="{{ $mod['hours'] }}" class="w-full h-9 rounded-lg border border-violet-100 px-3 text-[12.5px] focus:outline-none focus:ring-2 focus:ring-violet-300" />
                                            </div>
                                        </div>
                                        <div class="md:col-span-2">
                                            <label class="block text-[10px] font-bold tracking-wider uppercase text-slate-400 mb-1.5">Deskripsi</label>
                                            <textarea name="description" rows="2" class="w-full rounded-lg border border-violet-100 px-3 py-2 text-[12.5px] focus:outline-none focus:ring-2 focus:ring-violet-300 resize-y">{{ $mod['description'] ?? '' }}</textarea>
                                        </div>
                                        <div class="md:col-span-2 flex justify-end gap-2">
                                            <button type="submit" class="inline-flex items-center gap-1.5 h-8 px-3 rounded-lg bg-violet-600 text-white text-[12px] font-semibold hover:bg-violet-700 transition cursor-pointer">Simpan</button>
                                        </div>
                                    </form>
                                    @if ((int) ($mod['tasks_total'] ?? 0) > 0)
                                        <div class="mt-2 flex flex-col items-start gap-1">
                                            <button type="button" disabled class="inline-flex items-center gap-1.5 h-8 px-3 rounded-lg border border-slate-100 bg-slate-50 text-slate-400 text-[12px] font-semibold cursor-not-allowed">
                                                Hapus
                                            </button>
                                            <p class="text-[11px] text-slate-400">Hapus task terlebih dahulu sebelum menghapus modul.</p>
                                        </div>
                                    @else
                                        <form method="POST" action="{{ route('projects.modules.destroy', [$project, $mod['id']]) }}" class="mt-2" onsubmit="return confirm('Hapus modul WBS ini?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="inline-flex items-center gap-1.5 h-8 px-3 rounded-lg border border-rose-100 bg-rose-50 text-rose-700 text-[12px] font-semibold hover:bg-rose-100 transition cursor-pointer">
                                                Hapus
                                            </button>
                                        </form>
                                    @endif
                                </details>
                            @endif
                        </div>
                    @empty
                        <div class="rounded-xl border border-dashed border-violet-200 bg-violet-50/30 px-4 py-8 text-center text-[13px] font-medium text-slate-400">
                            Belum ada WBS. Generate draf dari MoM atau tambahkan modul manual.
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        <div>
            <div class="bg-white rounded-2xl border border-violet-100 shadow-[0_2px_8px_rgba(124,58,237,0.08)] p-6 sticky top-[88px]">
                <h3 class="text-[16px] font-bold text-[#1E1B4B] mb-6">Aktivitas Terbaru</h3>
                <div class="relative pl-6 max-h-[640px] overflow-y-auto">
                    <div class="absolute left-[7px] top-1 bottom-1 w-[2px] bg-violet-100"></div>
                    <div class="space-y-6">
                        @forelse ($activities as $a)
                            <div class="relative">
                                <div class="absolute -left-6 top-1 w-4 h-4 rounded-full border-4 border-white shadow" style="background: {{ $a['dot'] }};"></div>
                                <div class="text-[11px] text-slate-400 mb-1">{{ $a['time'] }}</div>
                                <div class="text-[13.5px] font-bold text-[#1E1B4B]">{{ $a['title'] }}</div>
                                <div class="text-[12.5px] text-slate-500 mt-1 leading-relaxed">{{ $a['text'] }}</div>
                            </div>
                        @empty
                            <div class="relative">
                                <div class="absolute -left-6 top-1 w-4 h-4 rounded-full border-4 border-white shadow bg-slate-300"></div>
                                <div class="text-[13px] font-semibold text-slate-500">Belum ada aktivitas untuk project ini.</div>
                                <div class="text-[12.5px] text-slate-400 mt-1 leading-relaxed">Aktivitas WBS, task, MoM, dan QC akan muncul setelah ada perubahan DB.</div>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- =============== WORKSPACE (kept as-is) =============== --}}
    <div id="workspace" data-panel="workspace" class="hidden bg-white rounded-2xl border border-violet-100 shadow-[0_2px_8px_rgba(124,58,237,0.08)] p-6">
        <div class="flex items-center justify-between mb-5 flex-wrap gap-4">
            <h3 class="inline-flex items-center gap-2 text-[12px] font-bold tracking-[0.12em] uppercase text-violet-600">
                <span class="w-1.5 h-1.5 rounded-full bg-violet-500"></span> Kanban Proyek
            </h3>
            <div class="text-[13px] text-slate-400">{{ $workspaceTaskTotal }} task &middot; <span class="font-semibold text-slate-500">{{ $workspaceTaskDone }} selesai</span></div>
        </div>

        <div class="flex items-center justify-between mb-5 flex-wrap gap-3">
            <div class="inline-flex items-center gap-3">
                <span class="text-[10.5px] font-bold tracking-wider uppercase text-slate-400">Filter Anggota</span>
                <div class="relative">
                    <select data-kanban-filter class="appearance-none h-9 pl-3 pr-9 rounded-lg border border-violet-100 bg-white text-[13px] text-slate-600 cursor-pointer">
                        <option value="">Semua</option>
                        @php
                            $kanbanAssignees = collect($assigneeOptions ?? [])->pluck('name')->all();
                            $seededAssignees = collect($kanban)->pluck('tasks')->flatten(1)->pluck('assignee')->filter()->unique()->values()->all();
                            $extraAssignees  = array_values(array_diff($seededAssignees, $kanbanAssignees, ['Belum Ditugaskan']));
                            $kanbanFilterOptions = array_merge($kanbanAssignees, $extraAssignees);
                            if (in_array('Belum Ditugaskan', $seededAssignees, true)) {
                                $kanbanFilterOptions[] = 'Belum Ditugaskan';
                            }
                        @endphp
                        @foreach ($kanbanFilterOptions as $name)
                            <option value="{{ $name }}">{{ $name }}</option>
                        @endforeach
                    </select>
                    <x-heroicon-o-chevron-down class="w-4 h-4 text-slate-400 absolute right-2 top-1/2 -translate-y-1/2 pointer-events-none" />
                </div>
            </div>
            @if (! $canEdit)
                <span class="text-[11px] font-bold tracking-wider uppercase px-3 py-1.5 rounded-full bg-violet-100 text-violet-700 inline-flex items-center gap-1.5">
                    <x-heroicon-o-eye class="w-3.5 h-3.5" />
                    Hanya Lihat
                </span>
            @elseif (! $useReferenceProjectData)
                <span class="text-[11px] font-bold tracking-wider uppercase px-3 py-1.5 rounded-full bg-emerald-50 text-emerald-700 inline-flex items-center gap-1.5">
                    <x-heroicon-o-circle-stack class="w-3.5 h-3.5" />
                    Tersimpan
                </span>
            @endif
        </div>

        @if ($canEdit && ! $useReferenceProjectData)
            <form method="POST" action="{{ route('projects.tasks.store', $project) }}" class="mb-6 rounded-xl border border-violet-100 bg-violet-50/30 p-4">
                @csrf
                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-3">
                    <div class="md:col-span-2">
                        <label class="block text-[10.5px] font-bold tracking-wider uppercase text-slate-500 mb-2">Judul Task</label>
                        <input name="title" value="{{ old('title') }}" class="w-full h-10 rounded-xl border border-violet-100 px-3 text-[13px] focus:outline-none focus:ring-2 focus:ring-violet-300" placeholder="Contoh: Implementasi halaman login" />
                        @error('title')<p class="mt-1 text-[11.5px] text-rose-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-[10.5px] font-bold tracking-wider uppercase text-slate-500 mb-2">Modul</label>
                        <select name="project_module_id" class="w-full h-10 rounded-xl border border-violet-100 px-3 text-[13px] bg-white focus:outline-none focus:ring-2 focus:ring-violet-300">
                            <option value="">Tanpa Modul</option>
                            @foreach ($modules as $mod)
                                <option value="{{ $mod['id'] }}" @selected((string) old('project_module_id') === (string) $mod['id'])>{{ $mod['name'] }}</option>
                            @endforeach
                        </select>
                        @error('project_module_id')<p class="mt-1 text-[11.5px] text-rose-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-[10.5px] font-bold tracking-wider uppercase text-slate-500 mb-2">Assignee</label>
                        <select name="assigned_to" class="w-full h-10 rounded-xl border border-violet-100 px-3 text-[13px] bg-white focus:outline-none focus:ring-2 focus:ring-violet-300">
                            <option value="">Belum Ditugaskan</option>
                            @foreach ($assigneeOptions as $member)
                                <option value="{{ $member->id }}" @selected((string) old('assigned_to') === (string) $member->id)>{{ $member->name }}</option>
                            @endforeach
                        </select>
                        <p class="mt-1 text-[11px] text-slate-400">Hanya anggota yang ditugaskan ke project ini yang muncul.</p>
                        @error('assigned_to')<p class="mt-1 text-[11.5px] text-rose-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-[10.5px] font-bold tracking-wider uppercase text-slate-500 mb-2">Status</label>
                        <select name="status" class="w-full h-10 rounded-xl border border-violet-100 px-3 text-[13px] bg-white focus:outline-none focus:ring-2 focus:ring-violet-300">
                            @foreach ($taskStatusOptions as $value => $label)
                                <option value="{{ $value }}" @selected(old('status', 'planned') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('status')<p class="mt-1 text-[11.5px] text-rose-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-[10.5px] font-bold tracking-wider uppercase text-slate-500 mb-2">Prioritas</label>
                        <select name="priority" class="w-full h-10 rounded-xl border border-violet-100 px-3 text-[13px] bg-white focus:outline-none focus:ring-2 focus:ring-violet-300">
                            @foreach ($taskPriorityOptions as $value => $label)
                                <option value="{{ $value }}" @selected(old('priority', 'medium') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('priority')<p class="mt-1 text-[11.5px] text-rose-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-[10.5px] font-bold tracking-wider uppercase text-slate-500 mb-2">Due Date</label>
                        <input name="due_date" type="date" value="{{ old('due_date') }}" class="w-full h-10 rounded-xl border border-violet-100 px-3 text-[13px] focus:outline-none focus:ring-2 focus:ring-violet-300" />
                        @error('due_date')<p class="mt-1 text-[11.5px] text-rose-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-[10.5px] font-bold tracking-wider uppercase text-slate-500 mb-2">Estimasi Jam</label>
                        <input name="estimate_hours" type="number" min="0" max="999" value="{{ old('estimate_hours', 0) }}" class="w-full h-10 rounded-xl border border-violet-100 px-3 text-[13px] focus:outline-none focus:ring-2 focus:ring-violet-300" />
                        @error('estimate_hours')<p class="mt-1 text-[11.5px] text-rose-600">{{ $message }}</p>@enderror
                    </div>
                    <div class="md:col-span-2 xl:col-span-4">
                        <label class="block text-[10.5px] font-bold tracking-wider uppercase text-slate-500 mb-2">Deskripsi</label>
                        <textarea name="description" rows="2" class="w-full rounded-xl border border-violet-100 px-3 py-2 text-[13px] focus:outline-none focus:ring-2 focus:ring-violet-300 resize-y" placeholder="Opsional">{{ old('description') }}</textarea>
                        @error('description')<p class="mt-1 text-[11.5px] text-rose-600">{{ $message }}</p>@enderror
                    </div>
                </div>
                <div class="mt-3 flex justify-end">
                    <button type="submit" class="inline-flex items-center gap-2 h-10 px-4 rounded-xl bg-[#1E1B4B] text-white font-semibold text-[13px] hover:bg-violet-900 transition cursor-pointer">
                        <x-heroicon-o-plus class="w-4 h-4" />
                        Tambah Task
                    </button>
                </div>
            </form>
        @endif

        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
            @foreach ($kanban as $col)
                <div data-kanban-column class="bg-white rounded-2xl border border-violet-100 p-4 pd-kbn-col-{{ $col['id'] }}">
                    <div class="flex items-center justify-between mb-4">
                        <h4 class="text-[13.5px] font-bold" style="color: {{ $col['color'] }};">{{ $col['label'] }}</h4>
                        <span data-col-count class="text-[11px] font-bold rounded-full px-2 py-0.5" style="background: {{ $col['bg'] }}; color: {{ $col['color'] }};">
                            {{ count($col['tasks']) }}
                        </span>
                    </div>
                    <div class="space-y-3 min-h-[100px] max-h-[640px] overflow-y-auto pr-1">
                        @forelse ($col['tasks'] as $task)
                            @php $accent = $task['priority'] === 'High' ? '#EF4444' : '#F59E0B'; @endphp
                            <div data-kanban-task data-assignee="{{ $task['assignee'] }}" class="bg-white rounded-lg border border-violet-50 shadow-sm hover:shadow-[0_2px_8px_rgba(124,58,237,0.08)] transition p-3" style="border-left: 4px solid {{ $accent }};">
                                <div class="flex items-start justify-between gap-2 mb-2">
                                    <span class="text-[9.5px] font-bold tracking-wider uppercase text-slate-400 truncate">{{ $task['module'] }}</span>
                                    <span @class([
                                        'text-[10px] font-bold rounded-md px-1.5 py-0.5 shrink-0',
                                        'bg-rose-100 text-rose-700' => $task['priority'] === 'High',
                                        'bg-amber-100 text-amber-700' => $task['priority'] !== 'High',
                                    ])>{{ $task['priority'] }}</span>
                                </div>
                                <p class="text-[13px] font-semibold text-[#1E1B4B] leading-snug mb-2">{{ $task['title'] }}</p>
                                <div class="flex items-center gap-2 flex-wrap">
                                    <span class="inline-flex items-center text-[10.5px] font-semibold rounded-full px-2 py-0.5 bg-slate-100 text-slate-500">{{ $task['assignee'] }}</span>
                                    @if (! empty($task['hours']))
                                        <span class="inline-flex items-center text-[10.5px] font-semibold rounded-full px-2 py-0.5 bg-violet-50 text-violet-600">{{ $task['hours'] }}h</span>
                                    @endif
                                    @if (! empty($task['due']))
                                        <span class="inline-flex items-center text-[10.5px] font-semibold rounded-full px-2 py-0.5 bg-amber-50 text-amber-700">{{ $task['due'] }}</span>
                                    @endif
                                </div>
                                @if ($canEdit && ! $useReferenceProjectData && ! empty($task['id']))
                                    <form method="POST" action="{{ route('projects.tasks.status', [$project, $task['id']]) }}" class="mt-3 flex items-center gap-2">
                                        @csrf
                                        @method('PATCH')
                                        <select name="status" class="min-w-0 flex-1 h-8 rounded-lg border border-violet-100 bg-white px-2 text-[12px] text-slate-600 focus:outline-none focus:ring-2 focus:ring-violet-300">
                                            @foreach ($taskStatusOptions as $value => $label)
                                                <option value="{{ $value }}" @selected($task['status'] === $value)>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                        <button type="submit" class="h-8 px-3 rounded-lg bg-violet-600 text-white text-[12px] font-semibold hover:bg-violet-700 transition cursor-pointer">Simpan</button>
                                    </form>
                                    <details class="mt-2 rounded-lg border border-violet-100 bg-violet-50/30 px-3 py-2">
                                        <summary class="cursor-pointer select-none text-[12px] font-bold text-violet-700">Edit Task</summary>
                                        <form method="POST" action="{{ route('projects.tasks.update', [$project, $task['id']]) }}" class="mt-3 space-y-2">
                                            @csrf
                                            @method('PUT')
                                            <div>
                                                <label class="block text-[10px] font-bold tracking-wider uppercase text-slate-400 mb-1">Judul</label>
                                                <input name="title" value="{{ $task['title'] }}" class="w-full h-9 rounded-lg border border-violet-100 bg-white px-3 text-[12px] focus:outline-none focus:ring-2 focus:ring-violet-300" />
                                            </div>
                                            <div>
                                                <label class="block text-[10px] font-bold tracking-wider uppercase text-slate-400 mb-1">Deskripsi</label>
                                                <textarea name="description" rows="2" class="w-full rounded-lg border border-violet-100 bg-white px-3 py-2 text-[12px] focus:outline-none focus:ring-2 focus:ring-violet-300 resize-y">{{ $task['description'] ?? '' }}</textarea>
                                            </div>
                                            <div class="grid grid-cols-2 gap-2">
                                                <div>
                                                    <label class="block text-[10px] font-bold tracking-wider uppercase text-slate-400 mb-1">Modul</label>
                                                    <select name="project_module_id" class="w-full h-9 rounded-lg border border-violet-100 bg-white px-2 text-[12px] focus:outline-none focus:ring-2 focus:ring-violet-300">
                                                        <option value="">Tanpa Modul</option>
                                                        @foreach ($modules as $mod)
                                                            <option value="{{ $mod['id'] }}" @selected((string) ($task['module_id'] ?? '') === (string) $mod['id'])>{{ $mod['name'] }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <div>
                                                    <label class="block text-[10px] font-bold tracking-wider uppercase text-slate-400 mb-1">Assignee</label>
                                                    <select name="assigned_to" class="w-full h-9 rounded-lg border border-violet-100 bg-white px-2 text-[12px] focus:outline-none focus:ring-2 focus:ring-violet-300">
                                                        <option value="">Belum Ditugaskan</option>
                                                        @foreach ($assigneeOptions as $member)
                                                            <option value="{{ $member->id }}" @selected((string) ($task['assigned_to'] ?? '') === (string) $member->id)>{{ $member->name }}</option>
                                                        @endforeach
                                                    </select>
                                                    <p class="mt-1 text-[10.5px] text-slate-400 leading-tight">Hanya anggota yang ditugaskan ke project ini yang muncul.</p>
                                                </div>
                                                <div>
                                                    <label class="block text-[10px] font-bold tracking-wider uppercase text-slate-400 mb-1">Status</label>
                                                    <select name="status" class="w-full h-9 rounded-lg border border-violet-100 bg-white px-2 text-[12px] focus:outline-none focus:ring-2 focus:ring-violet-300">
                                                        @foreach ($taskStatusOptions as $value => $label)
                                                            <option value="{{ $value }}" @selected($task['status'] === $value)>{{ $label }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <div>
                                                    <label class="block text-[10px] font-bold tracking-wider uppercase text-slate-400 mb-1">Prioritas</label>
                                                    <select name="priority" class="w-full h-9 rounded-lg border border-violet-100 bg-white px-2 text-[12px] focus:outline-none focus:ring-2 focus:ring-violet-300">
                                                        @foreach ($taskPriorityOptions as $value => $label)
                                                            <option value="{{ $value }}" @selected(($task['priority_key'] ?? '') === $value)>{{ $label }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <div>
                                                    <label class="block text-[10px] font-bold tracking-wider uppercase text-slate-400 mb-1">Due</label>
                                                    <input name="due_date" type="date" value="{{ $task['due_date'] ?? '' }}" class="w-full h-9 rounded-lg border border-violet-100 bg-white px-2 text-[12px] focus:outline-none focus:ring-2 focus:ring-violet-300" />
                                                </div>
                                                <div>
                                                    <label class="block text-[10px] font-bold tracking-wider uppercase text-slate-400 mb-1">Jam</label>
                                                    <input name="estimate_hours" type="number" min="0" max="999" value="{{ $task['hours'] ?? 0 }}" class="w-full h-9 rounded-lg border border-violet-100 bg-white px-2 text-[12px] focus:outline-none focus:ring-2 focus:ring-violet-300" />
                                                </div>
                                            </div>
                                            <div class="flex justify-end">
                                                <button type="submit" class="h-8 px-3 rounded-lg bg-violet-600 text-white text-[12px] font-semibold hover:bg-violet-700 transition cursor-pointer">Simpan</button>
                                            </div>
                                        </form>
                                        <form method="POST" action="{{ route('projects.tasks.destroy', [$project, $task['id']]) }}" class="mt-2" onsubmit="return confirm('Hapus task ini?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="h-8 px-3 rounded-lg border border-rose-100 bg-rose-50 text-rose-700 text-[12px] font-semibold hover:bg-rose-100 transition cursor-pointer">Hapus</button>
                                        </form>
                                    </details>
                                @endif
                            </div>
                        @empty
                            <div data-col-empty class="text-[12.5px] text-slate-400 text-center py-8">Belum ada task di workspace.</div>
                        @endforelse
                        <div data-col-filtered-empty class="hidden text-[12.5px] text-slate-400 text-center py-8 italic">Tidak ada task untuk anggota ini</div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- =============== AI PLANNING =============== --}}
    <div id="aiplanning" data-panel="aiplanning" class="hidden grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- LEFT: Daftar MoM --}}
        <div class="bg-white rounded-2xl pd-stitch-card flex flex-col">
            <div class="p-7 flex-1 space-y-5">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <span class="pd-section-bar"></span>
                        <h3 class="text-[16px] font-extrabold uppercase tracking-tight text-[#1E1B4B]">Daftar MoM</h3>
                    </div>
                    <span class="px-2.5 py-1 rounded-full bg-violet-100 text-violet-700 text-[10px] font-bold">{{ count($moms) }} TOTAL</span>
                </div>

                @if ($canEdit && ! $useReferenceProjectData)
                    <form method="POST" action="{{ route('projects.moms.store', $project) }}" class="space-y-3 pt-1">
                        @csrf
                        <div>
                            <label class="block text-[10.5px] font-bold tracking-wider uppercase text-slate-500 mb-2">Tanggal Rapat</label>
                            <input type="date" name="meeting_date" required value="{{ old('meeting_date') }}" class="w-full h-11 rounded-xl border border-violet-100 px-4 text-[13.5px] focus:outline-none focus:ring-2 focus:ring-violet-300" />
                            @error('meeting_date') <p class="mt-1 text-[11.5px] font-semibold text-rose-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-[10.5px] font-bold tracking-wider uppercase text-slate-500 mb-2">Notulensi Mentah</label>
                            <textarea name="notes" rows="4" required placeholder="Ketik atau tempelkan seluruh catatan rapat di sini..." class="w-full rounded-xl border border-violet-100 px-4 py-3 text-[13.5px] focus:outline-none focus:ring-2 focus:ring-violet-300 resize-y">{{ old('notes') }}</textarea>
                            @error('notes') <p class="mt-1 text-[11.5px] font-semibold text-rose-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-[10.5px] font-bold tracking-wider uppercase text-slate-500 mb-2">Ringkasan <span class="text-slate-400 normal-case tracking-normal">(opsional)</span></label>
                            <textarea name="summary" rows="2" placeholder="Ringkasan singkat dari MoM, action items, dsb. (opsional)" class="w-full rounded-xl border border-violet-100 px-4 py-3 text-[13.5px] focus:outline-none focus:ring-2 focus:ring-violet-300 resize-y">{{ old('summary') }}</textarea>
                            @error('summary') <p class="mt-1 text-[11.5px] font-semibold text-rose-600">{{ $message }}</p> @enderror
                        </div>
                        <div class="flex justify-end">
                            <button type="submit" class="inline-flex items-center gap-2 h-10 px-5 rounded-xl bg-[#1E1B4B] text-white font-semibold text-[13px] hover:bg-violet-900 transition cursor-pointer">
                                <x-heroicon-o-bookmark-square class="w-4 h-4" />
                                Simpan MoM
                            </button>
                        </div>
                    </form>
                @endif

                <div data-mom-list class="space-y-4 pt-1">
                    @forelse ($moms as $mom)
                        @php
                            $momIsDb     = is_array($mom) && array_key_exists('meeting_date', $mom);
                            $momDateText = $momIsDb ? ($mom['date_label'] ?? '-') : ($mom['date'] ?? '-');
                            $momStatusLabels = [
                                'ai_fixed' => 'Dirapikan AI',
                                'manual_updated' => 'Diperbarui',
                                'draft' => 'Draft',
                            ];
                            $momStatusKey = $momIsDb ? (string) ($mom['status'] ?? 'draft') : '';
                            $momTag      = $momIsDb ? ($momStatusLabels[$momStatusKey] ?? 'Tersimpan') : ($mom['tag'] ?? 'Draft');
                            $momTitle    = $momIsDb
                                ? 'MoM ' . $momDateText . ($mom['creator'] ? ' · ' . $mom['creator'] : '')
                                : ($mom['title'] ?? 'MoM');
                            $momBody     = $momIsDb ? ($mom['notes'] ?? '') : ($mom['body'] ?? '');
                            $momSummary  = $momIsDb ? ($mom['summary'] ?? null) : null;
                            $momRawPreview = \Illuminate\Support\Str::limit(trim(preg_replace('/\s+/', ' ', (string) $momBody)), 250, '...');
                            $momSummaryPreview = \Illuminate\Support\Str::limit(trim(preg_replace('/\s+/', ' ', (string) $momSummary)), 250, '...');
                        @endphp
                        <div class="p-5 rounded-xl border border-violet-100 bg-violet-50/20 hover:border-violet-300 transition group">
                            <div class="flex justify-between items-start mb-2 gap-3">
                                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">{{ $momDateText }}</span>
                                <div class="inline-flex items-center gap-1.5 flex-shrink-0">
                                    @if ($loop->first)
                                        <span class="px-2 py-0.5 bg-emerald-50 text-emerald-700 text-[9px] font-extrabold rounded-full">Terbaru</span>
                                    @endif
                                    <span class="px-2 py-0.5 bg-violet-50 text-violet-700 text-[9px] font-extrabold rounded-full inline-flex items-center gap-1">
                                        <x-heroicon-o-bookmark-square class="w-3 h-3" />
                                        {{ $momTag }}
                                    </span>
                                </div>
                            </div>
                            <h4 class="font-bold text-[#1E1B4B] text-[14px] group-hover:text-violet-700 transition mb-1">{{ $momTitle }}</h4>
                            @if ($momSummary)
                                <div class="mt-3 rounded-lg border border-emerald-100 bg-emerald-50/50 px-3 py-2">
                                    <div class="text-[10px] font-bold tracking-wider uppercase text-emerald-700 mb-1">MoM Proper / Ringkasan AI</div>
                                    <p class="text-[12px] text-slate-600 leading-relaxed font-medium">{{ $momSummaryPreview }}</p>
                                </div>
                            @endif
                            <div class="mt-3">
                                <div class="text-[10px] font-bold tracking-wider uppercase text-slate-400 mb-1">Preview Notulensi Mentah</div>
                                <p class="text-[12px] text-slate-500 leading-relaxed">{{ $momRawPreview ?: 'Tidak ada catatan.' }}</p>
                            </div>

                            <details class="group mt-4 rounded-lg border border-violet-100 bg-white/70 px-3 py-2">
                                <summary class="cursor-pointer select-none text-[12px] font-bold text-violet-700">
                                    <span class="pd-detail-closed-label">Lihat detail</span>
                                    <span class="pd-detail-open-label">Sembunyikan</span>
                                </summary>
                                <div class="mt-3 space-y-4">
                                    <div>
                                        <div class="text-[10px] font-bold tracking-wider uppercase text-slate-400 mb-1.5">Raw MoM / Notulensi Mentah</div>
                                        <div class="rounded-lg border border-slate-100 bg-slate-50 px-3 py-2 text-[12px] text-slate-600 leading-relaxed whitespace-pre-line">{{ $momBody }}</div>
                                    </div>
                                    <div>
                                        <div class="text-[10px] font-bold tracking-wider uppercase text-emerald-700 mb-1.5">Proper MoM / Ringkasan AI</div>
                                        @if ($momSummary)
                                            <div class="rounded-lg border border-emerald-100 bg-emerald-50/50 px-3 py-2 text-[12px] text-slate-700 leading-relaxed whitespace-pre-line">{{ $momSummary }}</div>
                                        @else
                                            <div class="rounded-lg border border-dashed border-violet-100 bg-violet-50/30 px-3 py-3 text-center text-[12px] font-medium text-slate-400">
                                                Belum ada Ringkasan AI untuk MoM ini.
                                            </div>
                                        @endif
                                    </div>
                                    @if ($canEdit && ! $useReferenceProjectData && $momIsDb && ! empty($mom['id']))
                                        <form method="POST" action="{{ route('projects.moms.summary', [$project, $mom['id']]) }}" class="space-y-2">
                                            @csrf
                                            @method('PATCH')
                                            <label class="block text-[10px] font-bold tracking-wider uppercase text-slate-400">Edit Ringkasan</label>
                                            <textarea name="summary" rows="5" placeholder="Ringkasan MoM yang sudah dirapikan..." class="w-full rounded-xl border border-violet-100 bg-white px-4 py-3 text-[12.5px] focus:outline-none focus:ring-2 focus:ring-violet-300 resize-y">{{ old('summary', $momSummary) }}</textarea>
                                            @error('summary') <p class="text-[11.5px] font-semibold text-rose-600">{{ $message }}</p> @enderror
                                            <div class="flex justify-end">
                                                <button type="submit" class="inline-flex items-center gap-2 h-9 px-4 rounded-lg border border-violet-200 bg-white text-[12px] font-semibold text-violet-700 hover:border-violet-400 transition cursor-pointer">
                                                    <x-heroicon-o-check class="w-4 h-4" />
                                                    Simpan Ringkasan
                                                </button>
                                            </div>
                                        </form>
                                    @endif
                                </div>
                            </details>
                        </div>
                    @empty
                        <div data-mom-empty class="rounded-xl border border-dashed border-violet-200 bg-violet-50/30 px-4 py-8 text-center text-[13px] font-medium text-slate-400">
                            Belum ada MoM. Tambahkan notulensi untuk memulai perencanaan.
                        </div>
                    @endforelse
                </div>
            </div>

            <div class="p-7 pt-0">
                @php
                    $latestMom = $moms[0] ?? null;
                    $latestMomRawNotes = is_array($latestMom) ? trim((string) ($latestMom['notes'] ?? ($latestMom['body'] ?? ''))) : '';
                    $latestMomSummary = is_array($latestMom) ? trim((string) ($latestMom['summary'] ?? '')) : '';

                    if ($latestMomRawNotes === '') {
                        $momFixerState = [
                            'label' => 'Tambahkan MoM terlebih dahulu',
                            'tooltip' => 'AI MoM Fixer butuh setidaknya 1 MoM tersimpan dengan notulensi mentah.',
                            'tone' => 'muted',
                        ];
                    } elseif (! ($aiReady ?? false)) {
                        $momFixerState = [
                            'label' => 'AI belum dikonfigurasi',
                            'tooltip' => 'Belum ada provider AI yang dikonfigurasi.',
                            'tone' => 'muted',
                        ];
                    } else {
                        $momFixerState = [
                            'label' => $latestMomSummary !== '' ? 'Rapikan Ulang MoM' : 'Rapikan MoM dengan AI',
                            'tooltip' => 'Rapikan MoM terbaru menjadi ringkasan formal dengan provider AI aktif.',
                            'tone' => 'ready',
                        ];
                    }
                @endphp
                <div class="p-5 rounded-2xl border border-dashed border-violet-300 bg-violet-50/40 flex flex-col gap-3">
                    <p class="text-[12px] text-slate-500 leading-relaxed text-center font-medium">
                        Otomatis rapikan MoM mentah Anda menggunakan AI agar siap diproses.
                    </p>
                    @if (($momFixerState['tone'] ?? 'muted') === 'ready' && $canEdit && ! $useReferenceProjectData)
                        <form method="POST" action="{{ route('projects.ai-mom.fix', $project) }}" class="w-full" data-loading-form data-loading-label="Merapikan MoM...">
                            @csrf
                            <button
                                type="submit"
                                title="{{ $momFixerState['tooltip'] }}"
                                class="w-full py-3 rounded-xl font-bold text-[13px] inline-flex items-center justify-center gap-2 transition bg-gradient-to-r from-violet-500 to-pink-500 text-white shadow-lg shadow-violet-500/20 hover:scale-[1.02] cursor-pointer disabled:opacity-70 disabled:cursor-wait"
                            >
                                <x-heroicon-o-sparkles class="w-4 h-4" />
                                <span data-loading-text>{{ $momFixerState['label'] }}</span>
                            </button>
                        </form>
                    @else
                        <button
                            type="button"
                            disabled
                            aria-disabled="true"
                            title="{{ $momFixerState['tooltip'] }}"
                            class="w-full py-3 rounded-xl font-bold text-[13px] inline-flex items-center justify-center gap-2 transition bg-slate-100 text-slate-400 cursor-not-allowed"
                        >
                            <x-heroicon-o-sparkles class="w-4 h-4" />
                            {{ $momFixerState['label'] }}
                        </button>
                    @endif
                    <p class="text-[11px] text-violet-600 italic text-center leading-relaxed">
                        Notulensi mentah tetap disimpan apa adanya; AI hanya memperbarui ringkasan.
                    </p>
                </div>
            </div>
        </div>

        {{-- RIGHT: WBS Terbentuk --}}
        <div class="bg-white rounded-2xl pd-stitch-card flex flex-col">
            <div class="p-7 flex-1 space-y-5">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <span class="pd-section-bar"></span>
                        <h3 class="text-[16px] font-extrabold uppercase tracking-tight text-[#1E1B4B]">WBS Terbentuk</h3>
                    </div>
                    <span class="px-2.5 py-1 rounded-full bg-[#EDE9FE] text-violet-700 text-[10px] font-bold">{{ count($modules) }} MODUL</span>
                </div>

                <div class="space-y-3">
                    @forelse ($modules as $mod)
                        @php $st = $modStatusStyles[$mod['status']] ?? ['bg' => '#F3F4F6', 'color' => '#374151']; @endphp
                        <div class="p-5 rounded-xl border border-violet-100 bg-violet-50/20 space-y-3">
                            <h4 class="text-[14px] font-bold text-[#1E1B4B] truncate">{{ $mod['name'] }}</h4>
                            @if (! empty($mod['description']))
                                <p class="text-[12px] text-slate-500 leading-relaxed">{{ $mod['description'] }}</p>
                            @endif
                            <div class="flex items-end justify-between gap-3">
                                <div class="flex items-center gap-2 text-[11px] text-slate-500 font-medium">
                                    <span>{{ $mod['tasks_total'] }} tasks</span>
                                    <span class="px-2 py-0.5 bg-[#EDE9FE] text-violet-700 rounded-full font-bold">{{ $mod['hours'] }}h</span>
                                </div>
                                <span class="px-2.5 py-1 text-[10px] font-bold rounded-full uppercase tracking-wider" style="background: {{ $st['bg'] }}; color: {{ $st['color'] }};">
                                    {{ $mod['status'] }}
                                </span>
                            </div>
                            @if ($canEdit && ! $useReferenceProjectData && ! empty($mod['id']))
                                <details class="rounded-lg border border-violet-100 bg-white/80 px-3 py-2">
                                    <summary class="cursor-pointer select-none text-[12px] font-bold text-violet-700">Edit / Hapus Modul</summary>
                                    <form method="POST" action="{{ route('projects.modules.update', [$project, $mod['id']]) }}" class="mt-3 grid grid-cols-1 md:grid-cols-2 gap-3">
                                        @csrf
                                        @method('PUT')
                                        <div>
                                            <label class="block text-[10px] font-bold tracking-wider uppercase text-slate-400 mb-1.5">Judul</label>
                                            <input name="title" value="{{ $mod['name'] }}" class="w-full h-9 rounded-lg border border-violet-100 px-3 text-[12.5px] focus:outline-none focus:ring-2 focus:ring-violet-300" />
                                        </div>
                                        <div class="grid grid-cols-2 gap-2">
                                            <div>
                                                <label class="block text-[10px] font-bold tracking-wider uppercase text-slate-400 mb-1.5">Status</label>
                                                <select name="status" class="w-full h-9 rounded-lg border border-violet-100 bg-white px-3 text-[12.5px] focus:outline-none focus:ring-2 focus:ring-violet-300">
                                                    @foreach ($moduleStatusOptions as $value => $label)
                                                        <option value="{{ $value }}" @selected(($mod['status_key'] ?? '') === $value)>{{ $label }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div>
                                                <label class="block text-[10px] font-bold tracking-wider uppercase text-slate-400 mb-1.5">Jam</label>
                                                <input name="estimate_hours" type="number" min="0" max="999" value="{{ $mod['hours'] }}" class="w-full h-9 rounded-lg border border-violet-100 px-3 text-[12.5px] focus:outline-none focus:ring-2 focus:ring-violet-300" />
                                            </div>
                                        </div>
                                        <div class="md:col-span-2">
                                            <label class="block text-[10px] font-bold tracking-wider uppercase text-slate-400 mb-1.5">Deskripsi</label>
                                            <textarea name="description" rows="2" class="w-full rounded-lg border border-violet-100 px-3 py-2 text-[12.5px] focus:outline-none focus:ring-2 focus:ring-violet-300 resize-y">{{ $mod['description'] ?? '' }}</textarea>
                                        </div>
                                        <div class="md:col-span-2 flex justify-end">
                                            <button type="submit" class="inline-flex items-center gap-1.5 h-8 px-3 rounded-lg bg-violet-600 text-white text-[12px] font-semibold hover:bg-violet-700 transition cursor-pointer">Simpan</button>
                                        </div>
                                    </form>
                                    @if ((int) ($mod['tasks_total'] ?? 0) > 0)
                                        <div class="mt-2 flex flex-col items-start gap-1">
                                            <button type="button" disabled class="inline-flex items-center gap-1.5 h-8 px-3 rounded-lg border border-slate-100 bg-slate-50 text-slate-400 text-[12px] font-semibold cursor-not-allowed">
                                                Hapus
                                            </button>
                                            <p class="text-[11px] text-slate-400">Hapus task terlebih dahulu sebelum menghapus modul.</p>
                                        </div>
                                    @else
                                        <form method="POST" action="{{ route('projects.modules.destroy', [$project, $mod['id']]) }}" class="mt-2" onsubmit="return confirm('Hapus modul WBS ini?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="inline-flex items-center gap-1.5 h-8 px-3 rounded-lg border border-rose-100 bg-rose-50 text-rose-700 text-[12px] font-semibold hover:bg-rose-100 transition cursor-pointer">
                                                Hapus
                                            </button>
                                        </form>
                                    @endif
                                </details>
                            @endif
                        </div>
                    @empty
                        <div class="rounded-xl border border-dashed border-violet-200 bg-violet-50/30 px-4 py-8 text-center text-[13px] font-medium text-slate-400">
                            Belum ada WBS. Generate draf dari MoM atau tambahkan modul manual.
                        </div>
                    @endforelse
                </div>
            </div>

            <div class="p-7 pt-0">
                @php
                    $aiReady    ??= false;
                    $aiProvider ??= 'AI';
                    $momCount    = count($moms);
                    $wbsLatestMom = $moms[0] ?? null;
                    $projectHasWbs = count($modules) > 0 || ($workspaceTaskTotal ?? 0) > 0;
                    $wbsSourceLabel = is_array($wbsLatestMom) && trim((string) ($wbsLatestMom['summary'] ?? '')) !== ''
                        ? 'Proper MoM terbaru'
                        : 'Raw MoM terbaru';
                    /* Three-state gate. Ready state now wires a real POST to
                     * projects.ai-wbs.generate; the muted states stay
                     * non-actionable with a clear reason. */
                    if ($momCount === 0) {
                        $wbsBtnState = [
                            'label'   => 'Tambahkan MoM terlebih dahulu',
                            'tooltip' => 'Generator butuh setidaknya 1 MoM tersimpan sebagai sumber.',
                            'tone'    => 'muted',
                        ];
                    } elseif (! $aiReady) {
                        $wbsBtnState = [
                            'label'   => 'AI belum dikonfigurasi',
                            'tooltip' => 'Belum ada provider AI yang dikonfigurasi.',
                            'tone'    => 'muted',
                        ];
                    } else {
                        $wbsBtnState = [
                            'label'   => $projectHasWbs ? 'Tambah Draft WBS dari MoM' : 'Generate WBS dari MoM',
                            'tooltip' => 'Buat draft modul + task dari MoM terbaru dengan provider AI aktif.',
                            'tone'    => 'ready',
                        ];
                    }
                @endphp
                <div class="p-5 rounded-2xl border border-dashed border-violet-300 bg-violet-50/40 flex flex-col gap-3">
                    <p class="text-[12px] text-slate-500 leading-relaxed text-center font-medium">
                        Generate draft WBS dari MoM tersimpan. Hasil tetap bisa diedit manual.
                    </p>

                    @if ($wbsBtnState['tone'] === 'ready' && $canEdit && ! $useReferenceProjectData)
                        <form method="POST" action="{{ route('projects.ai-wbs.generate', $project) }}" class="w-full" data-loading-form data-loading-label="Menyusun WBS...">
                            @csrf
                            <button
                                type="submit"
                                title="{{ $wbsBtnState['tooltip'] }}"
                                class="w-full py-3 rounded-xl font-bold text-[13px] inline-flex items-center justify-center gap-2 transition bg-gradient-to-r from-violet-500 to-pink-500 text-white shadow-lg shadow-violet-500/20 hover:scale-[1.02] cursor-pointer disabled:opacity-70 disabled:cursor-wait"
                            >
                                <x-heroicon-o-sparkles class="w-4 h-4" />
                                <span data-loading-text>{{ $wbsBtnState['label'] }}</span>
                            </button>
                        </form>
                        <p class="text-[11px] text-violet-600 italic text-center leading-relaxed">
                            Sumber: {{ $wbsSourceLabel }}. Modul/task dengan judul yang sudah ada akan dilewati.
                        </p>
                        @if ($projectHasWbs)
                            <p class="text-[11px] text-amber-700 bg-amber-50 border border-amber-100 rounded-lg px-3 py-2 text-center leading-relaxed">
                                Project ini sudah memiliki WBS. Generate ulang akan menambahkan draft baru tanpa menghapus data lama.
                            </p>
                        @endif
                    @else
                        <button
                            type="button"
                            disabled
                            aria-disabled="true"
                            title="{{ $wbsBtnState['tooltip'] }}"
                            class="w-full py-3 rounded-xl font-bold text-[13px] inline-flex items-center justify-center gap-2 transition cursor-not-allowed bg-slate-100 text-slate-400"
                        >
                            <x-heroicon-o-sparkles class="w-4 h-4" />
                            {{ $wbsBtnState['label'] }}
                        </button>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- =============== QUALITY CONTROL =============== --}}
    <div id="qc" data-panel="qc" class="hidden bg-white rounded-2xl pd-stitch-card overflow-hidden">
        <div class="p-6 md:p-7 flex flex-col md:flex-row md:items-center justify-between gap-4 border-b border-violet-100/60">
            <div class="flex items-center gap-3">
                <span class="pd-section-bar"></span>
                <h3 class="text-[16px] font-extrabold uppercase tracking-tight text-[#1E1B4B]">Test Case Black-Box</h3>
            </div>
            <div class="text-[13px] text-slate-500 font-medium">
                {{ ($qcSummary['total'] ?? count($testCases)) }} case &middot;
                <span class="text-emerald-600 font-semibold">{{ $qcSummary['lulus'] }} lulus</span> ·
                <span class="text-rose-600 font-semibold">{{ $qcSummary['gagal'] }} gagal</span> ·
                <span class="text-amber-600 font-semibold">{{ $qcSummary['pending'] }} pending</span>
                @if (! empty($qcSummary['retest']))
                    · <span class="text-violet-600 font-semibold">{{ $qcSummary['retest'] }} retest</span>
                @endif
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-violet-50/40">
                        <th class="px-7 py-4 text-[10px] font-bold tracking-wider uppercase text-slate-400 w-[110px]">ID</th>
                        <th class="px-4 py-4 text-[10px] font-bold tracking-wider uppercase text-slate-400">Skenario</th>
                        <th class="px-4 py-4 text-[10px] font-bold tracking-wider uppercase text-slate-400">Modul</th>
                        <th class="px-4 py-4 text-[10px] font-bold tracking-wider uppercase text-slate-400 w-[110px]">Prioritas</th>
                        <th class="px-4 py-4 text-[10px] font-bold tracking-wider uppercase text-slate-400 w-[120px]">Status</th>
                        @if ($canEdit && ! $useReferenceProjectData)
                            <th class="px-7 py-4 text-[10px] font-bold tracking-wider uppercase text-slate-400 text-right w-[260px]">Aksi</th>
                        @endif
                    </tr>
                </thead>
                <tbody class="divide-y divide-violet-100/40">
                    @forelse ($testCases as $tc)
                        @php
                            $tcStatus  = $tc['status'] ?? 'pending';
                            $tcCode    = $tc['code'] ?? ('TC-' . ($tc['id'] ?? '----'));
                            $tcModule  = $tc['module'] ?? '—';
                            $tcPriority = $tc['priority'] ?? 'medium';
                            $tcTitle   = $tc['title'] ?? null;
                            $tcScenario = $tc['scenario'] ?? '';
                        @endphp
                        <tr data-tc-row data-tc-id="{{ $tc['id'] ?? '' }}" data-tc-initial-status="{{ $tcStatus }}" class="hover:bg-[#FAF5FF] transition align-top">
                            <td class="px-7 py-4 text-[12px] font-bold text-violet-600/70">{{ $tcCode }}</td>
                            <td class="px-4 py-4 text-[13.5px] font-medium text-[#1E1B4B]">
                                @if ($tcTitle)
                                    <div class="font-semibold leading-snug">{{ $tcTitle }}</div>
                                    @if ($tcScenario && $tcScenario !== $tcTitle)
                                        <div class="text-[12px] text-slate-500 mt-1 leading-snug">{{ $tcScenario }}</div>
                                    @endif
                                @else
                                    {{ $tcScenario }}
                                @endif

                                @if (! empty($tc['expected_result']))
                                    <div class="mt-2 text-[11.5px] leading-snug">
                                        <span class="font-bold tracking-wider uppercase text-violet-700 mr-1">Expected</span>
                                        <span class="text-slate-600">{{ $tc['expected_result'] }}</span>
                                    </div>
                                @endif
                                @if (! empty($tc['actual_result']))
                                    <div class="mt-1 text-[11.5px] leading-snug">
                                        <span class="font-bold tracking-wider uppercase text-emerald-700 mr-1">Actual</span>
                                        <span class="text-slate-600">{{ $tc['actual_result'] }}</span>
                                    </div>
                                @endif
                                @if (! empty($tc['notes']))
                                    <div class="mt-1 text-[11.5px] leading-snug text-slate-500 italic">{{ $tc['notes'] }}</div>
                                @endif
                            </td>
                            <td class="px-4 py-4 text-[12px] text-slate-500 font-medium">{{ $tcModule }}</td>
                            <td class="px-4 py-4">
                                <span class="inline-flex items-center text-[10px] font-bold tracking-wide uppercase rounded-md px-2 py-1 {{ $tcPriorityPill[$tcPriority] ?? $tcPriorityPill['medium'] }}">
                                    {{ ucfirst($tcPriority) }}
                                </span>
                            </td>
                            <td class="px-4 py-4">
                                <span data-tc-pill class="inline-flex items-center text-[10px] font-bold tracking-wide uppercase rounded-full px-3 py-1 {{ $tcStatusPill[$tcStatus] ?? $tcStatusPill['pending'] }}">
                                    {{ $tcStatusLabel[$tcStatus] ?? ucfirst($tcStatus) }}
                                </span>
                                @if (! empty($tc['tested_at']))
                                    <div class="text-[10.5px] text-slate-400 mt-1">{{ $tc['tested_at'] }}</div>
                                @endif
                            </td>
                            @if ($canEdit && ! $useReferenceProjectData && ! empty($tc['id']))
                                <td data-tc-actions class="px-7 py-4 text-right">
                                    @php $qcAction = route('projects.qc.update', [$project, $tc['id']]); @endphp
                                    @if (in_array($tcStatus, ['passed', 'lulus'], true))
                                        <form method="POST" action="{{ $qcAction }}" class="inline">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="status" value="retest">
                                            <button type="submit" class="inline-flex items-center gap-1.5 h-8 px-3 rounded-lg border border-violet-200 bg-white text-[12px] font-semibold text-slate-600 hover:border-violet-400 hover:text-violet-700 transition cursor-pointer">
                                                <x-heroicon-o-arrow-path class="w-3.5 h-3.5" />
                                                Retest
                                            </button>
                                        </form>
                                    @elseif (in_array($tcStatus, ['failed', 'gagal'], true))
                                        <form method="POST" action="{{ $qcAction }}" class="inline">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="status" value="retest">
                                            <button type="submit" class="inline-flex items-center gap-1.5 h-8 px-3 rounded-lg border border-violet-200 bg-white text-[12px] font-semibold text-slate-600 hover:border-violet-400 hover:text-violet-700 transition cursor-pointer">
                                                <x-heroicon-o-arrow-path class="w-3.5 h-3.5" />
                                                Retest
                                            </button>
                                        </form>
                                    @else
                                        <div class="inline-flex gap-2">
                                            <form method="POST" action="{{ $qcAction }}" class="inline">
                                                @csrf
                                                @method('PATCH')
                                                <input type="hidden" name="status" value="passed">
                                                <button type="submit" class="h-8 px-3 rounded-lg bg-emerald-500 hover:bg-emerald-600 text-white text-[12px] font-bold transition cursor-pointer">Lulus</button>
                                            </form>
                                            <form method="POST" action="{{ $qcAction }}" class="inline">
                                                @csrf
                                                @method('PATCH')
                                                <input type="hidden" name="status" value="failed">
                                                <button type="submit" class="h-8 px-3 rounded-lg bg-rose-500 hover:bg-rose-600 text-white text-[12px] font-bold transition cursor-pointer">Gagal</button>
                                            </form>
                                        </div>
                                    @endif
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $canEdit && ! $useReferenceProjectData ? 6 : 5 }}" class="px-7 py-10 text-center text-[13px] font-medium text-slate-400">
                                Belum ada test case. Generate dari WBS/task atau tambahkan manual.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($canEdit && ! $useReferenceProjectData)
            <div class="px-7 py-5 border-t border-violet-100/60 bg-violet-50/20">
                <form method="POST" action="{{ route('projects.qc.store', $project) }}" class="grid grid-cols-1 md:grid-cols-12 gap-3">
                    @csrf
                    <div class="md:col-span-3">
                        <label class="block text-[10.5px] font-bold tracking-wider uppercase text-slate-500 mb-1.5">Judul</label>
                        <input type="text" name="title" required value="{{ old('title') }}" placeholder="mis. Login user valid" class="w-full h-10 rounded-lg border border-violet-100 px-3 text-[13px] focus:outline-none focus:ring-2 focus:ring-violet-300" />
                        @error('title') <p class="mt-1 text-[11.5px] font-semibold text-rose-600">{{ $message }}</p> @enderror
                    </div>
                    <div class="md:col-span-9">
                        <label class="block text-[10.5px] font-bold tracking-wider uppercase text-slate-500 mb-1.5">Skenario</label>
                        <input type="text" name="scenario" required value="{{ old('scenario') }}" placeholder="Skenario uji yang dilakukan..." class="w-full h-10 rounded-lg border border-violet-100 px-3 text-[13px] focus:outline-none focus:ring-2 focus:ring-violet-300" />
                        @error('scenario') <p class="mt-1 text-[11.5px] font-semibold text-rose-600">{{ $message }}</p> @enderror
                    </div>
                    <div class="md:col-span-7">
                        <label class="block text-[10.5px] font-bold tracking-wider uppercase text-slate-500 mb-1.5">Expected Result <span class="text-slate-400 normal-case tracking-normal">(opsional)</span></label>
                        <textarea name="expected_result" rows="2" placeholder="Hasil yang diharapkan dari skenario..." class="w-full rounded-lg border border-violet-100 px-3 py-2 text-[13px] focus:outline-none focus:ring-2 focus:ring-violet-300 resize-y">{{ old('expected_result') }}</textarea>
                        @error('expected_result') <p class="mt-1 text-[11.5px] font-semibold text-rose-600">{{ $message }}</p> @enderror
                    </div>
                    <div class="md:col-span-3">
                        <label class="block text-[10.5px] font-bold tracking-wider uppercase text-slate-500 mb-1.5">Modul (opsional)</label>
                        <select name="project_module_id" class="w-full h-10 rounded-lg border border-violet-100 px-3 text-[13px] focus:outline-none focus:ring-2 focus:ring-violet-300">
                            <option value="">— pilih modul —</option>
                            @foreach ($modules as $mod)
                                @php $modId = $mod['id'] ?? null; @endphp
                                @if ($modId)
                                    <option value="{{ $modId }}" @selected(old('project_module_id') == $modId)>{{ $mod['name'] }}</option>
                                @endif
                            @endforeach
                        </select>
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-[10.5px] font-bold tracking-wider uppercase text-slate-500 mb-1.5">Prioritas</label>
                        <select name="priority" class="w-full h-10 rounded-lg border border-violet-100 px-3 text-[13px] focus:outline-none focus:ring-2 focus:ring-violet-300">
                            @foreach ($qcPriorityOptions ?? ['low' => 'Low', 'medium' => 'Medium', 'high' => 'High'] as $key => $label)
                                <option value="{{ $key }}" @selected(old('priority', 'medium') === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="md:col-span-12 flex justify-end">
                        <button type="submit" class="inline-flex items-center gap-2 h-10 px-4 rounded-xl bg-[#1E1B4B] text-white font-semibold text-[13px] hover:bg-violet-900 transition cursor-pointer">
                            <x-heroicon-o-plus class="w-4 h-4" />
                            Tambah Test Case
                        </button>
                    </div>
                </form>
            </div>
        @endif

        <div class="px-7 py-5 border-t border-violet-100/60 bg-violet-50/30 flex items-center justify-between flex-wrap gap-3">
            <p class="text-[11.5px] text-slate-400 italic">Status pass/fail dapat dikembalikan ke pending lewat tombol Retest.</p>
            @if ($canEdit && ! $useReferenceProjectData)
                @php
                    $qcAiSourceReady = count($modules) > 0 || ($workspaceTaskTotal ?? 0) > 0;

                    if (! $qcAiSourceReady) {
                        $qcAiState = [
                            'label' => 'Tambahkan WBS/task terlebih dahulu',
                            'tooltip' => 'Generator butuh setidaknya 1 WBS module atau task sebagai sumber.',
                            'tone' => 'muted',
                        ];
                    } elseif (! $aiReady) {
                        $qcAiState = [
                            'label' => 'AI belum dikonfigurasi',
                            'tooltip' => 'Belum ada provider AI yang dikonfigurasi.',
                            'tone' => 'muted',
                        ];
                    } else {
                        $qcAiState = [
                            'label' => 'Generate Test Case',
                            'tooltip' => 'Buat draft black-box test case dari WBS module dan task dengan provider AI aktif.',
                            'tone' => 'ready',
                        ];
                    }
                @endphp

                <div class="flex flex-col items-end gap-1.5">
                    @if ($qcAiState['tone'] === 'ready')
                        <form method="POST" action="{{ route('projects.ai-test-cases.generate', $project) }}" data-loading-form data-loading-label="Membuat Test Case...">
                            @csrf
                            <button
                                type="submit"
                                title="{{ $qcAiState['tooltip'] }}"
                                class="inline-flex items-center gap-2 h-10 px-4 rounded-xl bg-[#1E1B4B] text-white font-semibold text-[13px] hover:bg-violet-900 transition cursor-pointer disabled:opacity-70 disabled:cursor-wait"
                            >
                                <x-heroicon-o-sparkles class="w-4 h-4" />
                                <span data-loading-text>{{ $qcAiState['label'] }}</span>
                            </button>
                        </form>
                    @else
                        <button
                            type="button"
                            disabled
                            aria-disabled="true"
                            title="{{ $qcAiState['tooltip'] }}"
                            class="inline-flex items-center gap-2 h-10 px-4 rounded-xl bg-slate-100 text-slate-400 font-semibold text-[13px] cursor-not-allowed"
                        >
                            <x-heroicon-o-sparkles class="w-4 h-4" />
                            {{ $qcAiState['label'] }}
                        </button>
                    @endif
                    <span class="text-[11px] text-slate-400 italic">Hasil AI tetap bisa direview dan diedit manual.</span>
                </div>
            @endif
        </div>
    </div>

    <script>
        (function () {
            const wire = () => {
                const spinner = '<span data-loading-spinner class="inline-block w-3.5 h-3.5 rounded-full border-2 border-current border-t-transparent animate-spin"></span>';
                const setLoading = (button, label) => {
                    if (! button || button.dataset.loading === '1') return false;
                    const text = button.querySelector('[data-loading-text]');
                    button.dataset.loading = '1';
                    button.disabled = true;
                    button.setAttribute('aria-disabled', 'true');
                    button.classList.add('opacity-70', 'cursor-wait');
                    if (text) text.textContent = label;
                    button.insertAdjacentHTML('afterbegin', spinner);
                    return true;
                };

                document.querySelectorAll('[data-loading-form]').forEach(form => {
                    form.addEventListener('submit', (event) => {
                        const button = form.querySelector('button[type="submit"]');
                        if (button?.dataset.loading === '1') {
                            event.preventDefault();
                            return;
                        }
                        setLoading(button, form.dataset.loadingLabel || 'Memproses...');
                    });
                });

                document.querySelectorAll('[data-loading-link]').forEach(link => {
                    link.addEventListener('click', (event) => {
                        if (link.dataset.loading === '1') {
                            event.preventDefault();
                            return;
                        }
                        link.dataset.loading = '1';
                        link.setAttribute('aria-disabled', 'true');
                        link.classList.add('opacity-70', 'cursor-wait', 'pointer-events-none');
                        const text = link.querySelector('[data-loading-text]');
                        if (text) text.textContent = link.dataset.loadingLabel || 'Menyiapkan...';
                        link.insertAdjacentHTML('afterbegin', spinner);
                    });
                });

                const tabs       = document.querySelectorAll('.pd-tab');
                const sideLinks  = document.querySelectorAll('[data-pd-nav]');
                const panels     = document.querySelectorAll('[data-panel]');

                const activate = (id) => {
                    tabs.forEach(x => {
                        const active = x.dataset.tab === id;
                        x.classList.toggle('is-active', active);
                        x.classList.toggle('text-slate-500', !active);
                        x.classList.toggle('hover:text-slate-700', !active);
                        x.classList.toggle('hover:bg-violet-50/60', !active);

                        const badge = x.querySelector('.pd-tab-badge');
                        if (badge) {
                            badge.classList.toggle('bg-violet-100', !active);
                            badge.classList.toggle('text-violet-700', !active);
                        }
                    });

                    sideLinks.forEach(x => {
                        const active = x.dataset.pdNav === id;
                        x.classList.toggle('is-active', active);
                        x.classList.toggle('bg-[#F5F3FF]', active);
                        x.classList.toggle('text-primary', active);
                        x.classList.toggle('font-bold', active);
                        x.classList.toggle('text-muted-foreground', !active);
                        x.classList.toggle('hover:text-primary', !active);
                        x.classList.toggle('hover:bg-violet-50/60', !active);
                        x.classList.toggle('font-medium', !active);
                    });

                    panels.forEach(p => {
                        p.classList.toggle('hidden', p.dataset.panel !== id);
                    });
                };

                /* Activate tab from URL hash (e.g. /projects/3#aiplanning from notifications/global search) */
                const TAB_IDS = ['overview', 'workspace', 'aiplanning', 'qc'];
                const setHash = (id) => {
                    if (TAB_IDS.includes(id)) history.replaceState(null, '', '#' + id);
                };

                tabs.forEach(t => t.addEventListener('click', () => {
                    activate(t.dataset.tab);
                    setHash(t.dataset.tab);
                }));
                sideLinks.forEach(t => t.addEventListener('click', (e) => {
                    e.preventDefault();
                    activate(t.dataset.pdNav);
                    setHash(t.dataset.pdNav);
                }));

                const hashToTab = () => {
                    const h = (window.location.hash || '').replace(/^#/, '').trim();
                    if (h && TAB_IDS.includes(h)) activate(h);
                };
                hashToTab();
                window.addEventListener('hashchange', hashToTab);

                /* === Kanban Filter Anggota === */
                const kbnSelect = document.querySelector('[data-kanban-filter]');
                if (kbnSelect) {
                    const applyKbn = (assignee) => {
                        document.querySelectorAll('[data-kanban-column]').forEach(col => {
                            let visible = 0;
                            let total = 0;
                            col.querySelectorAll('[data-kanban-task]').forEach(t => {
                                total++;
                                const match = ! assignee || t.dataset.assignee === assignee;
                                t.classList.toggle('hidden', ! match);
                                if (match) visible++;
                            });
                            const countEl = col.querySelector('[data-col-count]');
                            if (countEl) countEl.textContent = visible;
                            const initialEmpty = col.querySelector('[data-col-empty]');
                            const filteredEmpty = col.querySelector('[data-col-filtered-empty]');
                            if (filteredEmpty) {
                                const showFilteredEmpty = !! assignee && total > 0 && visible === 0;
                                filteredEmpty.classList.toggle('hidden', ! showFilteredEmpty);
                            }
                            if (initialEmpty) {
                                initialEmpty.classList.toggle('hidden', !! assignee && total > 0);
                            }
                        });
                    };
                    kbnSelect.addEventListener('change', () => applyKbn(kbnSelect.value));
                }

                /* Project Detail writes now submit to Laravel routes for WBS,
                 * Kanban, MoM, and QC actions. */
            };

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', wire);
            } else {
                wire();
            }
        })();
    </script>
</x-layouts.authenticated>
