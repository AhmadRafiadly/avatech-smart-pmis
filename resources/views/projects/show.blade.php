@php
    $role    = auth()->user()?->roles?->first()?->name;
    $readOnlyDetailRoles = ['ceo_pm', 'admin', 'super_admin', 'developer'];
    $operationalEditRoles = ['sa_qa', 'uiux_designer', 'ui_ux', 'fullstack_dev'];
    $isReadOnlyProjectDetail = in_array($role, $readOnlyDetailRoles, true);
    $isCeo   = $isReadOnlyProjectDetail;
    $canEdit = in_array($role, $operationalEditRoles, true);
    $canQuickAssign = $isReadOnlyProjectDetail;
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
        ['code' => 'TASK', 'value' => '0/14', 'label' => 'Task Selesai',       'color' => '#7C3AED', 'progress' => 0,   'sub' => '0 Selesai / 14 Proses'],
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
            ['module' => 'Project Hub dan Kanban O...',     'priority' => 'High',   'title' => 'Validasi hak akses dashboard',      'assignee' => 'Belum Ditugaskan'],
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

    $hasTeamAssignments = ($assigneeOptions ?? collect())->count() > 0;
    $hasMom = count($moms) > 0;
    $hasAiMomSummary = collect($moms)->contains(fn ($mom) => trim((string) ($mom['summary'] ?? '')) !== '');
    $hasWbsOrTasks = count($modules) > 0 || (int) ($workspaceTaskTotal ?? 0) > 0;
    $hasQc = (int) ($qcSummary['total'] ?? count($testCases ?? [])) > 0;

    if (! $hasTeamAssignments) {
        $nextStep = [
            'label' => 'Assign tim proyek terlebih dahulu.',
            'desc' => 'Tambahkan SA/QA, satu fullstack, dan UI/UX bila dibutuhkan agar ownership project jelas.',
            'href' => $isReadOnlyProjectDetail ? route('team.index') : '#overview',
            'cta' => $isReadOnlyProjectDetail ? 'Buka Team Management' : 'Lihat Team',
        ];
    } elseif (! $hasMom) {
        $nextStep = [
            'label' => 'Tambahkan MoM/notulensi mentah pertama.',
            'desc' => 'Paste catatan meeting apa adanya, lalu gunakan AI MoM Fixer untuk merapikan.',
            'href' => '#aiplanning',
            'cta' => $isReadOnlyProjectDetail ? 'Lihat AI Planning' : 'Tambah MoM',
        ];
    } elseif (! $hasAiMomSummary) {
        $nextStep = [
            'label' => 'Rapikan MoM dengan AI MoM Fixer.',
            'desc' => 'Gunakan MoM terbaru sebagai sumber ringkasan formal sebelum membuat WBS.',
            'href' => '#aiplanning',
            'cta' => $isReadOnlyProjectDetail ? 'Lihat AI Planning' : 'Buka AI Planning',
        ];
    } elseif (! $hasWbsOrTasks) {
        $nextStep = [
            'label' => 'Buat draf WBS dari MoM.',
            'desc' => 'Generate draf WBS dari MoM terbaru atau tambahkan modul manual jika scope sudah jelas.',
            'href' => '#aiplanning',
            'cta' => $isReadOnlyProjectDetail ? 'Lihat AI Planning' : 'Buat WBS',
        ];
    } elseif (! $hasQc) {
        $nextStep = [
            'label' => 'Buat test case dari WBS/task.',
            'desc' => 'Setelah task tersimpan, siapkan test case agar scope bisa divalidasi.',
            'href' => '#qc',
            'cta' => $isReadOnlyProjectDetail ? 'Lihat QC' : 'Buka QC',
        ];
    } else {
        $nextStep = [
            'label' => 'Lanjutkan validasi QC dan export bukti PDF.',
            'desc' => 'Update hasil pengujian, lalu export WBS/Test Case sebagai bukti walkthrough.',
            'href' => '#qc',
            'cta' => $isReadOnlyProjectDetail ? 'Lihat QC' : 'Validasi QC',
        ];
    }

    $kanbanById = collect($kanban)->keyBy('id');
    $taskStatusCounts = [
        'planned' => count($kanbanById->get('todo')['tasks'] ?? []),
        'in_progress' => count($kanbanById->get('doing')['tasks'] ?? []),
        'review' => count($kanbanById->get('testing')['tasks'] ?? []),
        'done' => count($kanbanById->get('done')['tasks'] ?? []),
    ];

    $qcStatusCounts = [
        'pending' => (int) ($qcSummary['pending'] ?? 0),
        'passed' => (int) ($qcSummary['passed'] ?? $qcSummary['lulus'] ?? 0),
        'failed' => (int) ($qcSummary['failed'] ?? $qcSummary['gagal'] ?? 0),
        'retest' => (int) ($qcSummary['retest'] ?? 0),
    ];

    $latestMomSummary = '';
    if (! empty($moms)) {
        $latestMom = $moms[0] ?? null;
        $latestMomSummary = is_array($latestMom)
            ? trim((string) ($latestMom['summary'] ?? ''))
            : '';
    }

    $latestMomSummaryText = $latestMomSummary !== ''
        ? \Illuminate\Support\Str::limit(trim(preg_replace('/\s+/', ' ', $latestMomSummary)), 220, '...')
        : 'Belum ada ringkasan MoM terbaru.';

    $projectStatusLabel = $statusUi['label'] ?? \Illuminate\Support\Str::headline((string) ($project->status ?? 'on-track'));
    $projectUpdateText = implode("\n", [
        'Project: ' . trim((string) ($project->code ? $project->code . ' - ' : '') . $project->name),
        'Client: ' . ($project->client?->name ?? '-'),
        'Status: ' . $project->phase . ' / ' . $projectStatusLabel,
        'Progress: ' . (int) $project->progress . '% (estimasi manual PM)',
        'WBS: ' . count($modules) . ' modul, ' . (int) ($workspaceTaskTotal ?? array_sum($taskStatusCounts)) . ' task',
        'Task: ' . $taskStatusCounts['planned'] . ' planned, ' . $taskStatusCounts['in_progress'] . ' in progress, ' . $taskStatusCounts['review'] . ' review, ' . $taskStatusCounts['done'] . ' done',
        'QC: ' . $qcStatusCounts['pending'] . ' pending, ' . $qcStatusCounts['passed'] . ' passed, ' . $qcStatusCounts['failed'] . ' failed, ' . $qcStatusCounts['retest'] . ' retest',
        'Catatan terakhir: ' . $latestMomSummaryText,
        'Next step: ' . ($nextStep['label'] ?? '-'),
    ]);
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
                Lead Proyek: <span class="font-semibold text-slate-700">{{ $leadDisplay ?? 'Belum ditentukan' }}</span>
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
        <p class="mt-2 text-[12px] text-slate-400 max-w-3xl leading-relaxed">
            Due date merupakan target saat ini dan dapat disesuaikan jika ada revisi atau perubahan scope.
        </p>

        <div class="mt-5 flex flex-wrap items-center gap-2">
            <a href="{{ route('projects.export.wbs', $project) }}" title="Unduh WBS sebagai PDF" data-loading-link data-loading-label="Menyiapkan PDF..." data-loading-reset-after="3000" data-download-timestamp-param="download_ts"
               class="inline-flex items-center gap-2 h-10 px-4 rounded-xl border border-violet-200 bg-white text-[13px] font-semibold text-slate-600 hover:border-violet-400 hover:text-violet-700 transition cursor-pointer no-underline">
                <x-heroicon-o-document-text class="w-4 h-4" />
                <span data-loading-text>Export WBS (PDF)</span>
            </a>
            <a href="{{ route('projects.export.test-cases', $project) }}" title="Unduh Test Case QC sebagai PDF" data-loading-link data-loading-label="Menyiapkan PDF..." data-loading-reset-after="3000" data-download-timestamp-param="download_ts"
               class="inline-flex items-center gap-2 h-10 px-4 rounded-xl border border-violet-200 bg-white text-[13px] font-semibold text-slate-600 hover:border-violet-400 hover:text-violet-700 transition cursor-pointer no-underline">
                <x-heroicon-o-beaker class="w-4 h-4" />
                <span data-loading-text>Export Test Case (PDF)</span>
            </a>
            @if ($canQuickAssign)
                <button type="button" data-quick-assign-open class="inline-flex items-center gap-2 h-10 px-4 rounded-xl bg-gradient-to-r from-[#7C3AED] via-[#A855F7] to-[#C084FC] text-white text-[13px] font-semibold shadow-[0_2px_8px_rgba(124,58,237,0.2)] hover:scale-[1.02] transition cursor-pointer">
                    <x-heroicon-o-user-plus class="w-4 h-4" />
                    Quick Assign Team
                </button>
            @endif
            @if ($project->archived_at && ! $isReadOnlyProjectDetail)
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
        @if ($isReadOnlyProjectDetail)
            <div class="mt-4 inline-flex items-start gap-2 rounded-xl border border-violet-100 bg-violet-50/60 px-4 py-3 text-[12.5px] text-violet-700 max-w-3xl">
                <x-heroicon-o-eye class="w-4 h-4 mt-0.5 flex-shrink-0" />
                <span>Mode hanya lihat: CEO/PM dapat memantau detail proyek, sedangkan perubahan operasional dilakukan oleh user yang ditugaskan.</span>
            </div>
        @endif
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
                <div class="flex items-start justify-between gap-4 flex-wrap">
                    <div class="flex items-start gap-4 min-w-0">
                        <div class="w-11 h-11 rounded-xl bg-violet-50 text-violet-700 border border-violet-100 flex items-center justify-center flex-shrink-0">
                            <x-heroicon-o-arrow-trending-up class="w-5 h-5" />
                        </div>
                        <div class="min-w-0">
                            <div class="text-[10.5px] font-bold tracking-[0.14em] uppercase text-violet-600">Langkah Berikutnya</div>
                            <h3 class="mt-1 text-[17px] font-extrabold text-[#1E1B4B] leading-tight">{{ $nextStep['label'] }}</h3>
                            <p class="mt-1.5 text-[13px] text-slate-500 leading-relaxed max-w-2xl">{{ $nextStep['desc'] }}</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2 flex-wrap">
                        <button type="button" data-project-summary-open class="inline-flex items-center gap-2 h-10 px-4 rounded-xl border border-violet-200 bg-white text-[13px] font-semibold text-violet-700 hover:border-violet-400 transition cursor-pointer">
                            <x-heroicon-o-clipboard-document-list class="w-4 h-4" />
                            Salin Ringkasan Proyek
                        </button>
                        @if (($nextStep['cta'] ?? '') === 'Quick Assign Team' && $canQuickAssign)
                            <button type="button" data-quick-assign-open class="inline-flex items-center gap-2 h-10 px-4 rounded-xl bg-violet-600 text-white text-[13px] font-semibold hover:bg-violet-700 transition cursor-pointer">
                                <x-heroicon-o-user-plus class="w-4 h-4" />
                                {{ $nextStep['cta'] }}
                            </button>
                        @else
                            <a href="{{ $nextStep['href'] }}" @if (str_starts_with($nextStep['href'], '#')) data-pd-nav="{{ ltrim($nextStep['href'], '#') }}" @endif class="inline-flex items-center gap-2 h-10 px-4 rounded-xl border border-violet-200 bg-white text-[13px] font-semibold text-violet-700 hover:border-violet-400 transition no-underline">
                                {{ $nextStep['cta'] }}
                                <x-heroicon-o-arrow-right class="w-4 h-4" />
                            </a>
                        @endif
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-2xl border border-violet-100 shadow-[0_2px_8px_rgba(124,58,237,0.08)] p-6">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-[16px] font-bold text-[#1E1B4B]">Pipeline WBS</h3>
                    <span class="text-[13px] font-semibold text-slate-400">{{ count($modules) }} total modul</span>
                </div>

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
                            Belum ada WBS. Buat dari MoM terbaru dengan AI WBS Generator, atau tambahkan modul manual jika scope sudah jelas.
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
                <p class="mb-3 text-[12px] text-slate-500 leading-relaxed">
                    Revisi desain masuk ke task UI/design, revisi development ke task fitur/bugfix, dan revisi copywriting bisa dicatat sebagai notes atau support task.
                </p>
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
                        <p class="mt-1 text-[11px] text-slate-400">Target task saat ini, bisa disesuaikan saat scope berubah.</p>
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
                                                    <p class="mt-1 text-[10.5px] text-slate-400 leading-tight">Target task saat ini.</p>
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
                            <div data-col-empty class="text-[12.5px] text-slate-400 text-center py-8 leading-relaxed">Belum ada task di workspace. Task akan muncul setelah WBS/task disimpan.</div>
                        @endforelse
                        <div data-col-filtered-empty class="hidden text-[12.5px] text-slate-400 text-center py-8 italic">Tidak ada task untuk anggota ini</div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- =============== AI PLANNING =============== --}}
    <div id="aiplanning" data-panel="aiplanning" class="hidden grid grid-cols-1 lg:grid-cols-2 gap-6">

        {{-- ====== HITL PREVIEW: AI MoM Fixer (draf editable, simpan setelah konfirmasi) ====== --}}
        @if ($canEdit && ! $useReferenceProjectData && session('ai_mom_preview'))
            @php $momPrev = session('ai_mom_preview'); @endphp
            <div class="lg:col-span-2 rounded-2xl border-2 border-dashed border-violet-300 bg-violet-50/40 overflow-hidden">
                <div class="px-6 py-4 border-b border-violet-100 flex items-center gap-2.5">
                    <x-heroicon-o-sparkles class="w-5 h-5 text-violet-600" />
                    <div>
                        <h3 class="text-[14.5px] font-extrabold text-[#1E1B4B]">Draf Ringkasan MoM (AI)</h3>
                        <p class="text-[11.5px] text-slate-500">Tinjau dan sunting draf. Belum tersimpan — klik <strong>Simpan</strong> untuk menerapkan, atau <strong>Batal</strong> untuk membatalkan.</p>
                        <p class="text-[10.5px] text-slate-400 italic mt-0.5">Generate ulang akan mengganti draf yang belum disimpan.</p>
                    </div>
                </div>
                <form method="POST" action="{{ route('projects.ai-mom.apply', $project) }}" class="px-6 py-5 space-y-3">
                    @csrf
                    <input type="hidden" name="mom_id" value="{{ $momPrev['mom_id'] }}">
                    <label class="block text-[10.5px] font-bold tracking-wider uppercase text-slate-500">Ringkasan untuk MoM {{ $momPrev['meeting_date'] ?? '' }}</label>
                    <textarea name="summary" rows="10" class="w-full rounded-xl border border-violet-200 bg-white px-4 py-3 text-[13px] leading-relaxed focus:outline-none focus:ring-2 focus:ring-violet-300 resize-y">{{ old('summary', $momPrev['summary'] ?? '') }}</textarea>
                    @error('summary') <p class="text-[11.5px] font-semibold text-rose-600">{{ $message }}</p> @enderror
                    <div class="flex items-center justify-end gap-2">
                        <a href="{{ route('projects.show', $project) }}?cancel_ai_preview=1#aiplanning" class="h-9 px-4 inline-flex items-center rounded-lg border border-slate-200 bg-white text-[12.5px] font-semibold text-slate-600 hover:bg-slate-50 transition cursor-pointer no-underline">Batal</a>
                        <button type="submit" class="h-9 px-4 inline-flex items-center gap-2 rounded-lg bg-gradient-to-r from-violet-500 to-pink-500 text-white text-[12.5px] font-semibold shadow-[0_2px_8px_rgba(124,58,237,0.2)] hover:scale-[1.02] transition cursor-pointer">
                            <x-heroicon-o-check class="w-4 h-4" /> Simpan sebagai Ringkasan MoM
                        </button>
                    </div>
                </form>
            </div>
        @endif

        {{-- ====== HITL PREVIEW: AI WBS Generator (draf editable + pilih item) ====== --}}
        @if ($canEdit && ! $useReferenceProjectData && session('ai_wbs_preview'))
            @php $wbsPrev = session('ai_wbs_preview'); @endphp
            <div class="lg:col-span-2 rounded-2xl border-2 border-dashed border-violet-300 bg-violet-50/40 overflow-hidden">
                <div class="px-6 py-4 border-b border-violet-100 flex items-center gap-2.5">
                    <x-heroicon-o-sparkles class="w-5 h-5 text-violet-600" />
                    <div>
                        <h3 class="text-[14.5px] font-extrabold text-[#1E1B4B]">Draf WBS (AI)</h3>
                        <p class="text-[11.5px] text-slate-500">Tinjau, sunting, dan centang item yang ingin disimpan. Belum tersimpan sampai Anda klik <strong>Simpan</strong>.</p>
                        <p class="text-[10.5px] text-slate-400 italic mt-0.5">Generate ulang akan mengganti draf yang belum disimpan.</p>
                    </div>
                </div>
                <form method="POST" action="{{ route('projects.ai-wbs.apply', $project) }}" class="px-6 py-5 space-y-4">
                    @csrf
                    @foreach (($wbsPrev['modules'] ?? []) as $mi => $mod)
                        <div class="rounded-xl border border-violet-100 bg-white p-4 space-y-3" data-wbs-module-block="{{ $mi }}">
                            <div class="flex items-start gap-2">
                                <input type="checkbox" name="modules[{{ $mi }}][include]" value="1" checked data-wbs-module-include="{{ $mi }}" class="mt-1.5 w-4 h-4 rounded border-violet-300 text-violet-600 focus:ring-violet-300 cursor-pointer">
                                <div class="flex-1 grid grid-cols-1 md:grid-cols-12 gap-2">
                                    <div class="md:col-span-7">
                                        <label class="block text-[9.5px] font-bold tracking-wider uppercase text-slate-400 mb-1">Judul Modul</label>
                                        <input name="modules[{{ $mi }}][title]" value="{{ $mod['title'] ?? '' }}" class="w-full h-9 rounded-lg border border-violet-100 px-3 text-[12.5px] focus:outline-none focus:ring-2 focus:ring-violet-300" />
                                    </div>
                                    <div class="md:col-span-3">
                                        <label class="block text-[9.5px] font-bold tracking-wider uppercase text-slate-400 mb-1">Status</label>
                                        <select name="modules[{{ $mi }}][status]" class="w-full h-9 rounded-lg border border-violet-100 bg-white px-2 text-[12.5px] focus:outline-none focus:ring-2 focus:ring-violet-300">
                                            @foreach (($moduleStatusOptions ?? []) as $value => $label)
                                                <option value="{{ $value }}" @selected(($mod['status'] ?? 'pending_design') === $value)>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="md:col-span-2">
                                        <label class="block text-[9.5px] font-bold tracking-wider uppercase text-slate-400 mb-1">Jam</label>
                                        <input type="number" min="0" max="999" name="modules[{{ $mi }}][estimate_hours]" value="{{ (int) ($mod['estimate_hours'] ?? 0) }}" class="w-full h-9 rounded-lg border border-violet-100 px-2 text-[12.5px] focus:outline-none focus:ring-2 focus:ring-violet-300" />
                                    </div>
                                    <div class="md:col-span-12">
                                        <label class="block text-[9.5px] font-bold tracking-wider uppercase text-slate-400 mb-1">Deskripsi</label>
                                        <textarea name="modules[{{ $mi }}][description]" rows="2" class="w-full rounded-lg border border-violet-100 px-3 py-2 text-[12.5px] focus:outline-none focus:ring-2 focus:ring-violet-300 resize-y">{{ $mod['description'] ?? '' }}</textarea>
                                    </div>
                                </div>
                            </div>
                            @if (! empty($mod['tasks']))
                                <div class="pl-6 space-y-2">
                                    <p class="text-[9.5px] font-bold tracking-wider uppercase text-slate-400">Task</p>
                                    @foreach ($mod['tasks'] as $ti => $task)
                                        <div class="flex items-start gap-2 rounded-lg border border-violet-50 bg-violet-50/30 p-2.5">
                                            <input type="checkbox" name="modules[{{ $mi }}][tasks][{{ $ti }}][include]" value="1" checked data-wbs-task-include="{{ $mi }}" class="mt-1.5 w-4 h-4 rounded border-violet-300 text-violet-600 focus:ring-violet-300 cursor-pointer">
                                            <div class="flex-1 grid grid-cols-1 md:grid-cols-12 gap-2">
                                                <div class="md:col-span-7">
                                                    <input name="modules[{{ $mi }}][tasks][{{ $ti }}][title]" value="{{ $task['title'] ?? '' }}" placeholder="Judul task" class="w-full h-9 rounded-lg border border-violet-100 px-3 text-[12px] focus:outline-none focus:ring-2 focus:ring-violet-300" />
                                                </div>
                                                <div class="md:col-span-3">
                                                    <select name="modules[{{ $mi }}][tasks][{{ $ti }}][priority]" class="w-full h-9 rounded-lg border border-violet-100 bg-white px-2 text-[12px] focus:outline-none focus:ring-2 focus:ring-violet-300">
                                                        @foreach (($taskPriorityOptions ?? ['low' => 'Low', 'medium' => 'Medium', 'high' => 'High']) as $value => $label)
                                                            <option value="{{ $value }}" @selected(($task['priority'] ?? 'medium') === $value)>{{ $label }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <div class="md:col-span-2">
                                                    <input type="number" min="0" max="999" name="modules[{{ $mi }}][tasks][{{ $ti }}][estimate_hours]" value="{{ (int) ($task['estimate_hours'] ?? 0) }}" placeholder="Jam" class="w-full h-9 rounded-lg border border-violet-100 px-2 text-[12px] focus:outline-none focus:ring-2 focus:ring-violet-300" />
                                                </div>
                                                <div class="md:col-span-12">
                                                    <textarea name="modules[{{ $mi }}][tasks][{{ $ti }}][description]" rows="1" placeholder="Deskripsi task (opsional)" class="w-full rounded-lg border border-violet-100 px-3 py-2 text-[12px] focus:outline-none focus:ring-2 focus:ring-violet-300 resize-y">{{ $task['description'] ?? '' }}</textarea>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    @endforeach
                    <div class="flex items-center justify-end gap-2">
                        <a href="{{ route('projects.show', $project) }}?cancel_ai_preview=1#aiplanning" class="h-9 px-4 inline-flex items-center rounded-lg border border-slate-200 bg-white text-[12.5px] font-semibold text-slate-600 hover:bg-slate-50 transition cursor-pointer no-underline">Batal</a>
                        <button type="submit" class="h-9 px-4 inline-flex items-center gap-2 rounded-lg bg-gradient-to-r from-violet-500 to-pink-500 text-white text-[12.5px] font-semibold shadow-[0_2px_8px_rgba(124,58,237,0.2)] hover:scale-[1.02] transition cursor-pointer">
                            <x-heroicon-o-check class="w-4 h-4" /> Simpan Draf Terpilih
                        </button>
                    </div>
                </form>
            </div>
        @endif

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
                <p class="text-[12px] text-slate-400 leading-relaxed">
                    Dokumentasikan revisi klien di MoM, lalu ubah menjadi task baru atau update task yang sudah ada.
                </p>

                @if ($canEdit && ! $useReferenceProjectData)
                    <form method="POST" action="{{ route('projects.moms.store', $project) }}" class="space-y-3 pt-1">
                        @csrf
                        <div>
                            <label class="block text-[10.5px] font-bold tracking-wider uppercase text-slate-500 mb-2">Tanggal Rapat</label>
                            <input type="date" name="meeting_date" required value="{{ old('meeting_date') }}" class="w-full h-11 rounded-xl border border-violet-100 px-4 text-[13.5px] focus:outline-none focus:ring-2 focus:ring-violet-300" />
                            @error('meeting_date') <p class="mt-1 text-[11.5px] font-semibold text-rose-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <div class="flex items-center justify-between gap-2 mb-2">
                                <label class="block text-[10.5px] font-bold tracking-wider uppercase text-slate-500">Notulensi Mentah</label>
                                <button type="button" data-mom-template class="text-[11.5px] font-semibold text-violet-700 hover:text-violet-900 cursor-pointer">Gunakan Template MoM</button>
                            </div>
                            <textarea name="notes" data-mom-notes rows="5" required placeholder="Judul rapat:
Peserta:
Kebutuhan utama:
Masalah/kendala:
Keputusan:
Action item:
Catatan tambahan:" class="w-full rounded-xl border border-violet-100 px-4 py-3 text-[13.5px] focus:outline-none focus:ring-2 focus:ring-violet-300 resize-y">{{ old('notes') }}</textarea>
                            <p class="mt-1 text-[11px] text-slate-400">Paste catatan mentah dulu; AI MoM Fixer bisa merapikannya setelah disimpan.</p>
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
                            Belum ada MoM. Paste notulensi mentah pertama, lalu gunakan AI MoM Fixer untuk membuat ringkasan proper.
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
                    @elseif (! $canEdit)
                        <p class="w-full rounded-xl border border-violet-100 bg-white/70 px-4 py-3 text-center text-[12.5px] text-slate-500">
                            Mode hanya lihat: AI MoM Fixer dijalankan oleh user operasional yang ditugaskan.
                        </p>
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

                @if ($canEdit && ! $useReferenceProjectData)
                    <form method="POST" action="{{ route('projects.modules.store', $project) }}" class="rounded-xl border border-violet-100 bg-violet-50/30 p-4">
                        @csrf
                        <p class="mb-3 text-[12px] text-slate-500 leading-relaxed">
                            Revisi desain, development, atau copywriting bisa diterjemahkan menjadi modul/task baru sesuai scope.
                        </p>
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
                            Belum ada WBS. Buat draf dari MoM terbaru dengan AI WBS Generator, atau tambahkan modul manual jika scope sudah jelas.
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
                        WBS akan dibuat berdasarkan MoM yang dipilih. Hasil AI tetap berupa draf dan harus ditinjau sebelum disimpan.
                    </p>

                    @if ($wbsBtnState['tone'] === 'ready' && $canEdit && ! $useReferenceProjectData)
                        <form method="POST" action="{{ route('projects.ai-wbs.generate', $project) }}" class="w-full space-y-3" data-loading-form data-loading-label="Menyusun WBS...">
                            @csrf
                            <div class="text-left">
                                <label class="block text-[10.5px] font-bold tracking-wider uppercase text-slate-500 mb-1.5">Pilih MoM sumber WBS</label>
                                <select name="source_mom_id" class="w-full h-10 rounded-xl border border-violet-100 bg-white px-3 text-[12.5px] focus:outline-none focus:ring-2 focus:ring-violet-300">
                                    @foreach ($moms as $loopMom)
                                        @php
                                            $momId = is_array($loopMom) ? ($loopMom['id'] ?? null) : null;
                                            $momDate = is_array($loopMom) ? ($loopMom['date_label'] ?? '—') : '—';
                                            $momCreator = is_array($loopMom) ? ($loopMom['creator'] ?? null) : null;
                                            $momExcerptSrc = is_array($loopMom) ? trim((string) ($loopMom['summary'] ?? '')) : '';
                                            if ($momExcerptSrc === '') { $momExcerptSrc = is_array($loopMom) ? trim((string) ($loopMom['notes'] ?? '')) : ''; }
                                            $momExcerpt = \Illuminate\Support\Str::limit(preg_replace('/\s+/', ' ', $momExcerptSrc), 60, '…');
                                        @endphp
                                        @if ($momId)
                                            <option value="{{ $momId }}" @selected($loop->first)>
                                                {{ $momDate }}{{ $momCreator ? ' · ' . $momCreator : '' }}{{ $momExcerpt !== '' ? ' — ' . $momExcerpt : '' }}
                                            </option>
                                        @endif
                                    @endforeach
                                </select>
                            </div>
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
                            Default: MoM terbaru. Modul/task dengan judul yang sudah ada akan dilewati.
                        </p>
                        @if ($projectHasWbs)
                            <p class="text-[11px] text-amber-700 bg-amber-50 border border-amber-100 rounded-lg px-3 py-2 text-center leading-relaxed">
                                Project ini sudah memiliki WBS. Generate ulang akan menambahkan draft baru tanpa menghapus data lama.
                            </p>
                        @endif
                    @elseif (! $canEdit)
                        <p class="w-full rounded-xl border border-violet-100 bg-white/70 px-4 py-3 text-center text-[12.5px] text-slate-500">
                            Mode hanya lihat: AI WBS Generator dijalankan oleh user operasional yang ditugaskan.
                        </p>
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

        {{-- ====== HITL PREVIEW: AI Test Case Generator (draf editable + pilih item) ====== --}}
        @if ($canEdit && ! $useReferenceProjectData && session('ai_testcase_preview'))
            @php $tcPrev = session('ai_testcase_preview'); @endphp
            <div class="m-6 rounded-2xl border-2 border-dashed border-violet-300 bg-violet-50/40 overflow-hidden">
                <div class="px-6 py-4 border-b border-violet-100 flex items-center gap-2.5">
                    <x-heroicon-o-sparkles class="w-5 h-5 text-violet-600" />
                    <div>
                        <h3 class="text-[14.5px] font-extrabold text-[#1E1B4B]">Draf Test Case (AI)</h3>
                        <p class="text-[11.5px] text-slate-500">Tinjau, sunting, dan centang test case yang ingin disimpan. Belum tersimpan sampai Anda klik <strong>Simpan</strong>.</p>
                        <p class="text-[10.5px] text-slate-400 italic mt-0.5">Generate ulang akan mengganti draf yang belum disimpan.</p>
                    </div>
                </div>
                <form method="POST" action="{{ route('projects.ai-test-cases.apply', $project) }}" class="px-6 py-5 space-y-3">
                    @csrf
                    @foreach (($tcPrev['test_cases'] ?? []) as $ci => $case)
                        @php
                            $caseModuleTitle = mb_strtolower((string) ($case['module_title'] ?? ''));
                            $caseModuleId = null;
                            foreach ($modules as $mod) {
                                if (! empty($mod['id']) && mb_strtolower((string) $mod['name']) === $caseModuleTitle) { $caseModuleId = $mod['id']; break; }
                            }
                        @endphp
                        <div class="rounded-xl border border-violet-100 bg-white p-4">
                            <div class="flex items-start gap-2">
                                <input type="checkbox" name="test_cases[{{ $ci }}][include]" value="1" checked class="mt-1.5 w-4 h-4 rounded border-violet-300 text-violet-600 focus:ring-violet-300 cursor-pointer">
                                <div class="flex-1 grid grid-cols-1 md:grid-cols-12 gap-2">
                                    <div class="md:col-span-9">
                                        <label class="block text-[9.5px] font-bold tracking-wider uppercase text-slate-400 mb-1">Judul</label>
                                        <input name="test_cases[{{ $ci }}][title]" value="{{ $case['title'] ?? '' }}" class="w-full h-9 rounded-lg border border-violet-100 px-3 text-[12.5px] focus:outline-none focus:ring-2 focus:ring-violet-300" />
                                    </div>
                                    <div class="md:col-span-3">
                                        <label class="block text-[9.5px] font-bold tracking-wider uppercase text-slate-400 mb-1">Prioritas</label>
                                        <select name="test_cases[{{ $ci }}][priority]" class="w-full h-9 rounded-lg border border-violet-100 bg-white px-2 text-[12.5px] focus:outline-none focus:ring-2 focus:ring-violet-300">
                                            @foreach (($qcPriorityOptions ?? ['low' => 'Low', 'medium' => 'Medium', 'high' => 'High']) as $value => $label)
                                                <option value="{{ $value }}" @selected(($case['priority'] ?? 'medium') === $value)>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="md:col-span-6">
                                        <label class="block text-[9.5px] font-bold tracking-wider uppercase text-slate-400 mb-1">Skenario</label>
                                        <textarea name="test_cases[{{ $ci }}][scenario]" rows="2" class="w-full rounded-lg border border-violet-100 px-3 py-2 text-[12px] focus:outline-none focus:ring-2 focus:ring-violet-300 resize-y">{{ $case['scenario'] ?? '' }}</textarea>
                                    </div>
                                    <div class="md:col-span-6">
                                        <label class="block text-[9.5px] font-bold tracking-wider uppercase text-slate-400 mb-1">Expected Result</label>
                                        <textarea name="test_cases[{{ $ci }}][expected_result]" rows="2" class="w-full rounded-lg border border-violet-100 px-3 py-2 text-[12px] focus:outline-none focus:ring-2 focus:ring-violet-300 resize-y">{{ $case['expected_result'] ?? '' }}</textarea>
                                    </div>
                                    <div class="md:col-span-6">
                                        <label class="block text-[9.5px] font-bold tracking-wider uppercase text-slate-400 mb-1">Modul (opsional)</label>
                                        <select name="test_cases[{{ $ci }}][project_module_id]" class="w-full h-9 rounded-lg border border-violet-100 bg-white px-2 text-[12px] focus:outline-none focus:ring-2 focus:ring-violet-300">
                                            <option value="">— tanpa modul —</option>
                                            @foreach ($modules as $mod)
                                                @php $modId = $mod['id'] ?? null; @endphp
                                                @if ($modId)
                                                    <option value="{{ $modId }}" @selected((string) $caseModuleId === (string) $modId)>{{ $mod['name'] }}</option>
                                                @endif
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                    <div class="flex items-center justify-end gap-2">
                        <a href="{{ route('projects.show', $project) }}?cancel_ai_preview=1#qc" class="h-9 px-4 inline-flex items-center rounded-lg border border-slate-200 bg-white text-[12.5px] font-semibold text-slate-600 hover:bg-slate-50 transition cursor-pointer no-underline">Batal</a>
                        <button type="submit" class="h-9 px-4 inline-flex items-center gap-2 rounded-lg bg-gradient-to-r from-violet-500 to-pink-500 text-white text-[12.5px] font-semibold shadow-[0_2px_8px_rgba(124,58,237,0.2)] hover:scale-[1.02] transition cursor-pointer">
                            <x-heroicon-o-check class="w-4 h-4" /> Simpan Test Case Terpilih
                        </button>
                    </div>
                </form>
            </div>
        @endif

        <div class="overflow-x-auto">
            <p class="px-7 pt-5 pb-2 text-[12px] text-slate-400 leading-relaxed">
                QC membantu memvalidasi revisi; jika ada bug atau perubahan scope, update status test case atau buat retest.
            </p>
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
                                <td data-tc-actions class="px-7 py-4 text-right align-top">
                                    @php
                                        $qcAction       = route('projects.qc.update', [$project, $tc['id']]);
                                        $qcEditAction   = route('projects.qc.edit', [$project, $tc['id']]);
                                        $qcDeleteAction = route('projects.qc.destroy', [$project, $tc['id']]);
                                    @endphp
                                    <div class="flex flex-col items-end gap-2">
                                        {{-- Status actions (Lulus/Gagal/Retest) — dipertahankan --}}
                                        @if (in_array($tcStatus, ['passed', 'lulus', 'failed', 'gagal'], true))
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

                                        {{-- Manual Edit & Hapus (CRUD lengkap) — tombol selalu terlihat di kolom Aksi --}}
                                        <div class="inline-flex items-start gap-2">
                                            <button
                                                type="button"
                                                data-qc-edit-trigger
                                                data-qc-action="{{ $qcEditAction }}"
                                                data-qc-title="{{ $tcTitle ?? $tcScenario }}"
                                                data-qc-scenario="{{ $tcScenario }}"
                                                data-qc-expected="{{ $tc['expected_result'] ?? '' }}"
                                                data-qc-module="{{ $tc['module_id'] ?? '' }}"
                                                data-qc-priority="{{ $tcPriority }}"
                                                data-qc-code="{{ $tcCode }}"
                                                class="inline-flex items-center gap-1.5 h-8 px-3 rounded-lg border border-violet-200 bg-white text-[12px] font-semibold text-violet-700 hover:border-violet-400 hover:text-violet-800 transition cursor-pointer"
                                            >
                                                <x-heroicon-o-pencil-square class="w-3.5 h-3.5" />
                                                Edit
                                            </button>
                                            <form method="POST" action="{{ $qcDeleteAction }}" onsubmit="return confirm('Hapus test case ini? Tindakan ini tidak dapat dibatalkan.');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="inline-flex items-center gap-1.5 h-8 px-3 rounded-lg border border-rose-200 bg-rose-50 text-rose-700 text-[12px] font-semibold hover:bg-rose-100 transition cursor-pointer">
                                                    <x-heroicon-o-trash class="w-3.5 h-3.5" />
                                                    Hapus
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $canEdit && ! $useReferenceProjectData ? 6 : 5 }}" class="px-7 py-10 text-center text-[13px] font-medium text-slate-400">
                                Belum ada test case. Setelah WBS/task tersedia, gunakan AI Test Case Generator atau tambahkan test case manual.
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

                <div class="flex flex-col items-end gap-1.5 w-full sm:w-auto">
                    @if ($qcAiState['tone'] === 'ready')
                        @php $qcSourceModules = collect($modules)->filter(fn ($m) => ! empty($m['id']))->values(); @endphp
                        <form method="POST" action="{{ route('projects.ai-test-cases.generate', $project) }}" class="flex flex-col sm:flex-row sm:items-end gap-2 w-full sm:w-auto" data-loading-form data-loading-label="Membuat Test Case...">
                            @csrf
                            <div class="text-left">
                                <label class="block text-[10.5px] font-bold tracking-wider uppercase text-slate-500 mb-1.5">Pilih sumber test case</label>
                                <select name="source_module_id" class="w-full sm:w-64 h-10 rounded-xl border border-violet-100 bg-white px-3 text-[12.5px] focus:outline-none focus:ring-2 focus:ring-violet-300">
                                    <option value="">Semua modul/task</option>
                                    @foreach ($qcSourceModules as $srcMod)
                                        <option value="{{ $srcMod['id'] }}" @selected($loop->first)>{{ \Illuminate\Support\Str::limit($srcMod['name'] ?? 'Modul', 50, '…') }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <button
                                type="submit"
                                title="{{ $qcAiState['tooltip'] }}"
                                class="inline-flex items-center justify-center gap-2 h-10 px-4 rounded-xl bg-gradient-to-r from-violet-500 to-pink-500 text-white font-semibold text-[13px] shadow-lg shadow-violet-500/20 hover:scale-[1.02] transition cursor-pointer disabled:opacity-70 disabled:cursor-wait"
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
                    <span class="text-[11px] text-slate-400 italic">Test case akan dibuat berdasarkan modul atau task yang dipilih. Hasil AI tetap berupa draf dan harus ditinjau sebelum disimpan.</span>
                </div>
            @endif
        </div>
    </div>

    {{-- =============== PROJECT UPDATE SUMMARY MODAL =============== --}}
    <div data-project-summary-modal class="fixed inset-0 z-50 hidden items-center justify-center p-4" role="dialog" aria-modal="true" aria-labelledby="project-summary-title">
        <div data-project-summary-close class="absolute inset-0 bg-slate-900/40 backdrop-blur-[2px]"></div>
        <div class="relative w-full max-w-2xl rounded-2xl bg-white border border-violet-100 shadow-[0_20px_60px_-15px_rgba(124,58,237,0.35)] overflow-hidden">
            <div class="flex items-center justify-between gap-3 px-5 py-4 border-b border-violet-100/70 bg-violet-50/40">
                <div class="min-w-0">
                    <h3 id="project-summary-title" class="text-[15px] font-extrabold tracking-tight text-[#1E1B4B] leading-tight">Ringkasan Proyek</h3>
                    <p class="text-[11.5px] text-slate-500 mt-1">Review dan edit sebelum ditempel ke WhatsApp, email, atau catatan follow-up.</p>
                </div>
                <button type="button" data-project-summary-close class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-white transition cursor-pointer" aria-label="Tutup">
                    <x-heroicon-o-x-mark class="w-5 h-5" />
                </button>
            </div>
            <div class="px-5 py-4 space-y-3">
                <textarea data-project-summary-text rows="10" class="w-full rounded-xl border border-violet-100 bg-white px-4 py-3 text-[13px] leading-relaxed text-slate-700 focus:outline-none focus:ring-2 focus:ring-violet-300 resize-y">{{ $projectUpdateText }}</textarea>
                <p class="text-[11px] text-slate-400 leading-relaxed">Helper ini tidak mengirim WhatsApp/email otomatis dan tidak memanggil AI.</p>
                <div class="flex items-center justify-end gap-2">
                    <button type="button" data-project-summary-close class="h-9 px-4 rounded-lg border border-slate-200 bg-white text-[12.5px] font-semibold text-slate-600 hover:bg-slate-50 transition cursor-pointer">Tutup</button>
                    <button type="button" data-project-summary-copy class="inline-flex items-center gap-2 h-9 px-4 rounded-lg bg-violet-600 text-white text-[12.5px] font-semibold hover:bg-violet-700 transition cursor-pointer">
                        <x-heroicon-o-clipboard-document-check class="w-4 h-4" />
                        <span data-project-summary-copy-label>Copy</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- =============== QC EDIT MODAL (shared, satu untuk semua baris) =============== --}}
    @if ($canEdit && ! $useReferenceProjectData)
        <div id="qc-edit-modal" class="fixed inset-0 z-50 hidden items-center justify-center p-4" role="dialog" aria-modal="true" aria-labelledby="qc-edit-modal-title">
            <div data-qc-modal-overlay class="absolute inset-0 bg-slate-900/40 backdrop-blur-[2px]"></div>
            <div class="relative w-full max-w-md rounded-2xl bg-white border border-violet-100 shadow-[0_20px_60px_-15px_rgba(124,58,237,0.35)] overflow-hidden">
                <div class="flex items-center justify-between gap-3 px-5 py-4 border-b border-violet-100/70 bg-violet-50/40">
                    <div class="flex items-center gap-2.5 min-w-0">
                        <span class="pd-section-bar"></span>
                        <div class="min-w-0">
                            <h3 id="qc-edit-modal-title" class="text-[15px] font-extrabold tracking-tight text-[#1E1B4B] leading-tight">Edit Test Case</h3>
                            <p data-qc-modal-code class="text-[11px] font-semibold text-violet-500"></p>
                        </div>
                    </div>
                    <button type="button" data-qc-modal-close class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition cursor-pointer" aria-label="Tutup">
                        <x-heroicon-o-x-mark class="w-5 h-5" />
                    </button>
                </div>
                <form id="qc-edit-form" method="POST" action="" class="px-5 py-4 space-y-3">
                    @csrf
                    @method('PUT')
                    <div>
                        <label class="block text-[10px] font-bold tracking-wider uppercase text-slate-400 mb-1">Judul</label>
                        <input name="title" required class="w-full h-9 rounded-lg border border-violet-100 bg-white px-3 text-[12.5px] focus:outline-none focus:ring-2 focus:ring-violet-300" />
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold tracking-wider uppercase text-slate-400 mb-1">Skenario</label>
                        <textarea name="scenario" rows="2" required class="w-full rounded-lg border border-violet-100 bg-white px-3 py-2 text-[12.5px] focus:outline-none focus:ring-2 focus:ring-violet-300 resize-y"></textarea>
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold tracking-wider uppercase text-slate-400 mb-1">Expected Result <span class="normal-case tracking-normal text-slate-300">(opsional)</span></label>
                        <textarea name="expected_result" rows="2" class="w-full rounded-lg border border-violet-100 bg-white px-3 py-2 text-[12.5px] focus:outline-none focus:ring-2 focus:ring-violet-300 resize-y"></textarea>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-[10px] font-bold tracking-wider uppercase text-slate-400 mb-1">Modul</label>
                            <select name="project_module_id" class="w-full h-9 rounded-lg border border-violet-100 bg-white px-2 text-[12.5px] focus:outline-none focus:ring-2 focus:ring-violet-300">
                                <option value="">— pilih modul —</option>
                                @foreach ($modules as $mod)
                                    @php $modId = $mod['id'] ?? null; @endphp
                                    @if ($modId)
                                        <option value="{{ $modId }}">{{ $mod['name'] }}</option>
                                    @endif
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold tracking-wider uppercase text-slate-400 mb-1">Prioritas</label>
                            <select name="priority" class="w-full h-9 rounded-lg border border-violet-100 bg-white px-2 text-[12.5px] focus:outline-none focus:ring-2 focus:ring-violet-300">
                                @foreach ($qcPriorityOptions ?? ['low' => 'Low', 'medium' => 'Medium', 'high' => 'High'] as $key => $label)
                                    <option value="{{ $key }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <p class="text-[10.5px] text-slate-400 leading-tight">Status (Lulus/Gagal/Retest) dikelola lewat tombol di kolom Aksi.</p>
                    <div class="flex justify-end gap-2 pt-1">
                        <button type="button" data-qc-modal-close class="h-9 px-4 rounded-lg border border-slate-200 bg-white text-[12.5px] font-semibold text-slate-600 hover:bg-slate-50 transition cursor-pointer">Batal</button>
                        <button type="submit" class="h-9 px-4 rounded-lg bg-violet-600 text-white text-[12.5px] font-semibold hover:bg-violet-700 transition cursor-pointer">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    @if ($canQuickAssign)
        <div data-quick-assign-modal class="fixed inset-0 z-50 hidden items-center justify-center p-4" role="dialog" aria-modal="true" aria-labelledby="quick-assign-title">
            <div data-quick-assign-close class="absolute inset-0 bg-slate-900/40 backdrop-blur-[2px]"></div>
            <form method="POST" action="{{ route('projects.quick-assign', $project) }}" class="relative w-full max-w-5xl max-h-[90vh] rounded-3xl bg-white border border-violet-100 shadow-[0_24px_70px_rgba(124,58,237,0.24)] overflow-hidden flex flex-col">
                @csrf
                <div class="px-6 py-4 border-b border-violet-100 bg-violet-50/40 flex items-center justify-between gap-4">
                    <div class="min-w-0">
                        <h3 id="quick-assign-title" class="text-[17px] font-extrabold text-[#1E1B4B] leading-tight">Quick Assign Team</h3>
                        <p class="text-[12.5px] text-slate-500 mt-1">Pilih anggota operasional dan sesuaikan ringkasan bila perlu. Jika sudah ada, penugasan akan diperbarui.</p>
                    </div>
                    <button type="button" data-quick-assign-close class="w-9 h-9 rounded-full hover:bg-white flex items-center justify-center text-slate-500 hover:text-rose-500 transition cursor-pointer flex-shrink-0" aria-label="Tutup">
                        <x-heroicon-o-x-mark class="w-5 h-5" />
                    </button>
                </div>

                <div class="px-6 py-5 overflow-y-auto space-y-4">
                    @forelse ($quickAssignUsers ?? [] as $idx => $member)
                        <div data-quick-assign-row data-qa-user-id="{{ $member['id'] }}" class="rounded-2xl border border-violet-100 bg-white p-4 shadow-[0_2px_8px_rgba(124,58,237,0.05)]">
                            <div class="flex items-start gap-4">
                                <label class="pt-2 flex-shrink-0">
                                    <input type="checkbox" name="assignments[{{ $idx }}][include]" value="1" @checked($member['checked']) class="w-4 h-4 rounded border-violet-200 text-violet-600 focus:ring-violet-300 cursor-pointer">
                                    <input type="hidden" name="assignments[{{ $idx }}][user_id]" value="{{ $member['id'] }}">
                                </label>
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-start justify-between gap-3 flex-wrap">
                                        <div class="flex items-center gap-3 min-w-0">
                                            <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-[#7C3AED] to-[#C084FC] text-white font-bold text-[12px] flex items-center justify-center flex-shrink-0">{{ $member['initials'] }}</div>
                                            <div class="min-w-0">
                                                <div class="text-[14px] font-bold text-[#1E1B4B] truncate">{{ $member['name'] }}</div>
                                                <div class="text-[12px] text-slate-500">{{ $member['role'] }}</div>
                                            </div>
                                        </div>
                                        @if (count($member['presets']) > 1)
                                            <select data-qa-preset class="h-9 rounded-lg border border-violet-100 bg-violet-50/40 px-3 text-[12.5px] font-semibold text-violet-700 focus:outline-none focus:ring-2 focus:ring-violet-300 cursor-pointer">
                                                @foreach ($member['presets'] as $key => $preset)
                                                    <option value="{{ $key }}">{{ $preset['label'] }}</option>
                                                @endforeach
                                            </select>
                                        @endif
                                    </div>

                                    <div class="mt-4 grid grid-cols-1 lg:grid-cols-12 gap-3">
                                        <div class="lg:col-span-5">
                                            <label class="block text-[10.5px] font-bold tracking-wider uppercase text-slate-400 mb-1">Ringkasan</label>
                                            <input name="assignments[{{ $idx }}][title]" data-qa-field="title" value="{{ $member['title'] }}" class="w-full h-10 rounded-lg border border-violet-100 px-3 text-[13px] focus:outline-none focus:ring-2 focus:ring-violet-300">
                                        </div>
                                        <div class="lg:col-span-2">
                                            <label class="block text-[10.5px] font-bold tracking-wider uppercase text-slate-400 mb-1">Tipe</label>
                                            <select name="assignments[{{ $idx }}][type]" data-qa-field="type" class="w-full h-10 rounded-lg border border-violet-100 px-3 text-[13px] bg-white focus:outline-none focus:ring-2 focus:ring-violet-300">
                                                <option value="task" @selected($member['type'] === 'task')>Task</option>
                                                <option value="review" @selected($member['type'] === 'review')>Review</option>
                                                <option value="support" @selected($member['type'] === 'support')>Support</option>
                                            </select>
                                        </div>
                                        <div class="lg:col-span-2">
                                            <label class="block text-[10.5px] font-bold tracking-wider uppercase text-slate-400 mb-1">Status</label>
                                            <select name="assignments[{{ $idx }}][status]" data-qa-field="status" class="w-full h-10 rounded-lg border border-violet-100 px-3 text-[13px] bg-white focus:outline-none focus:ring-2 focus:ring-violet-300">
                                                <option value="planned" @selected($member['status'] === 'planned')>Planned</option>
                                                <option value="in_progress" @selected($member['status'] === 'in_progress')>In Progress</option>
                                                <option value="done" @selected($member['status'] === 'done')>Done</option>
                                            </select>
                                        </div>
                                        <div class="lg:col-span-1">
                                            <label class="block text-[10.5px] font-bold tracking-wider uppercase text-slate-400 mb-1">Jam</label>
                                            <input type="number" min="0" max="200" name="assignments[{{ $idx }}][estimated_hours]" data-qa-field="estimated_hours" value="{{ $member['estimated_hours'] }}" class="w-full h-10 rounded-lg border border-violet-100 px-3 text-[13px] focus:outline-none focus:ring-2 focus:ring-violet-300">
                                        </div>
                                        <div class="lg:col-span-2">
                                            <label class="block text-[10.5px] font-bold tracking-wider uppercase text-slate-400 mb-1">Due</label>
                                            <input type="date" name="assignments[{{ $idx }}][due_date]" value="{{ $member['due_date'] }}" class="w-full h-10 rounded-lg border border-violet-100 px-3 text-[13px] focus:outline-none focus:ring-2 focus:ring-violet-300">
                                        </div>
                                        <div class="lg:col-span-12">
                                            <label class="block text-[10.5px] font-bold tracking-wider uppercase text-slate-400 mb-1">Catatan</label>
                                            <textarea name="assignments[{{ $idx }}][notes]" data-qa-field="notes" rows="2" class="w-full rounded-lg border border-violet-100 px-3 py-2 text-[13px] focus:outline-none focus:ring-2 focus:ring-violet-300 resize-y">{{ $member['notes'] }}</textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="rounded-2xl border border-dashed border-violet-200 bg-violet-50/40 p-8 text-center text-[13px] text-slate-500">Belum ada anggota operasional aktif untuk di-assign.</div>
                    @endforelse
                </div>

                <div class="px-6 py-4 border-t border-violet-100 bg-violet-50/30 flex items-center justify-between gap-3 flex-wrap">
                    <p class="text-[11.5px] text-slate-500">Quick Assign tidak membuat duplikat untuk user dan project yang sama.</p>
                    <div class="flex items-center gap-2">
                        <button type="button" data-quick-assign-close class="h-10 px-4 rounded-lg border border-slate-200 bg-white text-[13px] font-semibold text-slate-600 hover:bg-slate-50 transition cursor-pointer">Batal</button>
                        <button type="submit" class="h-10 px-5 rounded-lg bg-violet-600 text-white text-[13px] font-semibold hover:bg-violet-700 transition cursor-pointer">Simpan Assignments</button>
                    </div>
                </div>
            </form>
        </div>
    @endif

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
                        if (link.dataset.downloadTimestampParam) {
                            const url = new URL(link.href, window.location.href);
                            url.searchParams.set(link.dataset.downloadTimestampParam, Date.now().toString());
                            link.href = url.toString();
                        }
                        link.dataset.loading = '1';
                        link.setAttribute('aria-disabled', 'true');
                        link.classList.add('opacity-70', 'cursor-wait', 'pointer-events-none');
                        const text = link.querySelector('[data-loading-text]');
                        if (text && ! link.dataset.loadingOriginalLabel) link.dataset.loadingOriginalLabel = text.textContent;
                        if (text) text.textContent = link.dataset.loadingLabel || 'Menyiapkan...';
                        link.insertAdjacentHTML('afterbegin', spinner);
                        const resetAfter = Number.parseInt(link.dataset.loadingResetAfter || '', 10);
                        if (resetAfter > 0) {
                            window.setTimeout(() => {
                                link.querySelector('[data-loading-spinner]')?.remove();
                                if (text && link.dataset.loadingOriginalLabel) text.textContent = link.dataset.loadingOriginalLabel;
                                link.classList.remove('opacity-70', 'cursor-wait', 'pointer-events-none');
                                link.removeAttribute('aria-disabled');
                                delete link.dataset.loading;
                            }, resetAfter);
                        }
                    });
                });

                const projectSummaryModal = document.querySelector('[data-project-summary-modal]');
                if (projectSummaryModal) {
                    const summaryText = projectSummaryModal.querySelector('[data-project-summary-text]');
                    const copyButton = projectSummaryModal.querySelector('[data-project-summary-copy]');
                    const copyLabel = projectSummaryModal.querySelector('[data-project-summary-copy-label]');
                    const openSummary = () => {
                        projectSummaryModal.classList.remove('hidden');
                        projectSummaryModal.classList.add('flex');
                        document.body.style.overflow = 'hidden';
                        window.setTimeout(() => summaryText?.focus(), 0);
                    };
                    const closeSummary = () => {
                        projectSummaryModal.classList.add('hidden');
                        projectSummaryModal.classList.remove('flex');
                        document.body.style.overflow = '';
                        if (copyLabel) copyLabel.textContent = 'Copy';
                    };

                    document.querySelectorAll('[data-project-summary-open]').forEach(btn => btn.addEventListener('click', openSummary));
                    projectSummaryModal.querySelectorAll('[data-project-summary-close]').forEach(btn => btn.addEventListener('click', closeSummary));
                    copyButton?.addEventListener('click', async () => {
                        if (! summaryText) return;
                        try {
                            await navigator.clipboard.writeText(summaryText.value);
                        } catch (error) {
                            summaryText.select();
                            document.execCommand('copy');
                        }
                        if (copyLabel) copyLabel.textContent = 'Copied';
                    });
                    document.addEventListener('keydown', (event) => {
                        if (event.key === 'Escape' && ! projectSummaryModal.classList.contains('hidden')) closeSummary();
                    });
                }

                const momTemplateButton = document.querySelector('[data-mom-template]');
                const momNotesInput = document.querySelector('[data-mom-notes]');
                if (momTemplateButton && momNotesInput) {
                    const momTemplate = [
                        'Judul rapat:',
                        'Peserta:',
                        'Kebutuhan utama:',
                        'Masalah/kendala:',
                        'Keputusan:',
                        'Action item:',
                        'Catatan tambahan:'
                    ].join('\n');

                    momTemplateButton.addEventListener('click', () => {
                        if (momNotesInput.value.trim() !== '' && ! window.confirm('Ganti isi notulensi dengan template MoM?')) {
                            return;
                        }

                        momNotesInput.value = momTemplate;
                        momNotesInput.focus();
                    });
                }

                const quickAssignModal = document.querySelector('[data-quick-assign-modal]');
                if (quickAssignModal) {
                    const quickAssignPresets = @json(collect($quickAssignUsers ?? [])->mapWithKeys(fn ($member) => [$member['id'] => $member['presets']])->all());
                    const openQuickAssign = () => {
                        quickAssignModal.classList.remove('hidden');
                        quickAssignModal.classList.add('flex');
                        document.body.style.overflow = 'hidden';
                    };
                    const closeQuickAssign = () => {
                        quickAssignModal.classList.add('hidden');
                        quickAssignModal.classList.remove('flex');
                        document.body.style.overflow = '';
                    };
                    document.querySelectorAll('[data-quick-assign-open]').forEach(btn => btn.addEventListener('click', openQuickAssign));
                    quickAssignModal.querySelectorAll('[data-quick-assign-close]').forEach(btn => btn.addEventListener('click', closeQuickAssign));
                    quickAssignModal.querySelectorAll('[data-qa-preset]').forEach(select => {
                        select.addEventListener('change', () => {
                            const row = select.closest('[data-quick-assign-row]');
                            const preset = quickAssignPresets[row?.dataset.qaUserId || '']?.[select.value];
                            if (! row || ! preset) return;
                            ['title', 'type', 'status', 'estimated_hours', 'notes'].forEach(field => {
                                const input = row.querySelector('[data-qa-field="' + field + '"]');
                                if (input && preset[field] !== undefined) input.value = preset[field];
                            });
                        });
                    });
                    document.addEventListener('keydown', (event) => {
                        if (event.key === 'Escape' && ! quickAssignModal.classList.contains('hidden')) closeQuickAssign();
                    });
                }

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

                /* ===== HITL WBS preview: sinkron centang modul -> task anaknya (UI clarity) =====
                 * Backend tetap melewati task bila modulnya tidak di-include; ini hanya kejelasan UI. */
                document.querySelectorAll('[data-wbs-module-include]').forEach(modCb => {
                    const block = modCb.closest('[data-wbs-module-block]');
                    if (! block) return;
                    const tasks = block.querySelectorAll('[data-wbs-task-include]');
                    const sync = () => {
                        const on = modCb.checked;
                        tasks.forEach(t => {
                            t.disabled = ! on;
                            if (! on) t.checked = false;
                            t.closest('.flex')?.classList.toggle('opacity-50', ! on);
                        });
                    };
                    modCb.addEventListener('change', sync);
                });

                /* ===== QC Edit modal (shared, satu dialog untuk semua baris) ===== */
                const qcModal = document.getElementById('qc-edit-modal');
                if (qcModal) {
                    const qcForm  = document.getElementById('qc-edit-form');
                    const setVal  = (name, value) => {
                        const el = qcForm.querySelector('[name="' + name + '"]');
                        if (el) el.value = value ?? '';
                    };
                    const openQcModal = (btn) => {
                        qcForm.setAttribute('action', btn.dataset.qcAction || '');
                        setVal('title', btn.dataset.qcTitle);
                        setVal('scenario', btn.dataset.qcScenario);
                        setVal('expected_result', btn.dataset.qcExpected);
                        setVal('project_module_id', btn.dataset.qcModule);
                        setVal('priority', btn.dataset.qcPriority || 'medium');
                        const code = qcModal.querySelector('[data-qc-modal-code]');
                        if (code) code.textContent = btn.dataset.qcCode || '';
                        qcModal.classList.remove('hidden');
                        qcModal.classList.add('flex');
                        document.body.classList.add('overflow-hidden');
                        const first = qcForm.querySelector('[name="title"]');
                        if (first) setTimeout(() => first.focus(), 30);
                    };
                    const closeQcModal = () => {
                        qcModal.classList.add('hidden');
                        qcModal.classList.remove('flex');
                        document.body.classList.remove('overflow-hidden');
                    };

                    document.querySelectorAll('[data-qc-edit-trigger]').forEach(btn => {
                        btn.addEventListener('click', () => openQcModal(btn));
                    });
                    qcModal.querySelectorAll('[data-qc-modal-close], [data-qc-modal-overlay]').forEach(el => {
                        el.addEventListener('click', closeQcModal);
                    });
                    document.addEventListener('keydown', (e) => {
                        if (e.key === 'Escape' && ! qcModal.classList.contains('hidden')) closeQcModal();
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
</x-layouts.authenticated>
