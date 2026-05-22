@php
    $projects = [
        ['id' => 1, 'code' => 'AC', 'color' => '#7C3AED', 'name' => 'Alpha CRM',          'client' => 'PT Maju Jaya',       'desc' => 'Sales pipeline & customer dashboard internal untuk tim sales B2B.',         'phase' => 'Development', 'phase_key' => 'dev',      'due' => '30 Jun', 'progress' => 72,  'status' => 'on-track',  'status_label' => 'On Track',        'team' => ['AR','IK','YP'], 'team_more' => 1, 'tasks_done' => 21, 'tasks_total' => 42, 'mom' => 6, 'ai_flag' => false],
        ['id' => 2, 'code' => 'BP', 'color' => '#A855F7', 'name' => 'Beta Portal',        'client' => 'CV Berkah Digital',  'desc' => 'Self-service portal untuk merchant onboarding & verifikasi dokumen.',        'phase' => 'Design',      'phase_key' => 'design',   'due' => '12 Jul', 'progress' => 45,  'status' => 'attention', 'status_label' => 'Needs Attention', 'team' => ['YP','AR'],      'team_more' => 0, 'tasks_done' => 8,  'tasks_total' => 28, 'mom' => 4, 'ai_flag' => true],
        ['id' => 3, 'code' => 'GA', 'color' => '#C084FC', 'name' => 'Gamma API Gateway',  'client' => 'PT Solusi Pintar',   'desc' => 'API gateway terpusat untuk integrasi sistem internal & partner.',            'phase' => 'QA',          'phase_key' => 'qa',       'due' => '18 Mei', 'progress' => 90,  'status' => 'critical',  'status_label' => 'Critical',        'team' => ['IK','AR','FA'], 'team_more' => 0, 'tasks_done' => 26, 'tasks_total' => 30, 'mom' => 8, 'ai_flag' => false],
        ['id' => 4, 'code' => 'DL', 'color' => '#8B5CF6', 'name' => 'Delta Logistics',    'client' => 'PT Trans Nusantara', 'desc' => 'Mobile + dashboard untuk monitoring armada & tracking pengiriman.',          'phase' => 'Development', 'phase_key' => 'dev',      'due' => '08 Ags', 'progress' => 38,  'status' => 'on-track',  'status_label' => 'On Track',        'team' => ['FA','IK','YP'], 'team_more' => 2, 'tasks_done' => 15, 'tasks_total' => 48, 'mom' => 3, 'ai_flag' => false],
        ['id' => 5, 'code' => 'EX', 'color' => '#9333EA', 'name' => 'Epsilon Exchange',   'client' => 'PT Global Prima',    'desc' => 'Internal tooling exchange data antar sistem legacy klien enterprise.',       'phase' => 'Planning',    'phase_key' => 'planning', 'due' => '22 Sep', 'progress' => 18,  'status' => 'attention', 'status_label' => 'Needs Attention', 'team' => ['AR','YP'],      'team_more' => 0, 'tasks_done' => 2,  'tasks_total' => 18, 'mom' => 2, 'ai_flag' => true],
        ['id' => 6, 'code' => 'ZN', 'color' => '#7C3AED', 'name' => 'Zeta Mobile App',    'client' => 'PT Maju Jaya',       'desc' => 'Customer-facing mobile app untuk loyalty program & point reward.',           'phase' => 'Development', 'phase_key' => 'dev',      'due' => '15 Jul', 'progress' => 64,  'status' => 'on-track',  'status_label' => 'On Track',        'team' => ['YP','FA','IK'], 'team_more' => 0, 'tasks_done' => 19, 'tasks_total' => 34, 'mom' => 5, 'ai_flag' => false],
        ['id' => 7, 'code' => 'OT', 'color' => '#6D28D9', 'name' => 'Omicron Onboarding', 'client' => 'CV Berkah Digital',  'desc' => 'Modul onboarding karyawan dengan e-signing & dokumen otomatis.',             'phase' => 'Done',        'phase_key' => 'done',     'due' => '02 Mei', 'progress' => 100, 'status' => 'done',      'status_label' => 'Selesai',         'team' => ['AR','YP'],      'team_more' => 0, 'tasks_done' => 24, 'tasks_total' => 24, 'mom' => 9, 'ai_flag' => false],
        ['id' => 8, 'code' => 'KP', 'color' => '#9333EA', 'name' => 'Kappa POS',          'client' => 'PT Toko Cerdas',     'desc' => 'Point-of-sale lightweight untuk SMB ritel dengan multi-cabang.',             'phase' => 'QA',          'phase_key' => 'qa',       'due' => '28 Jun', 'progress' => 82,  'status' => 'on-track',  'status_label' => 'On Track',        'team' => ['IK','AR'],      'team_more' => 1, 'tasks_done' => 30, 'tasks_total' => 38, 'mom' => 7, 'ai_flag' => false],
    ];

    $filters = [
        ['id' => 'all',      'label' => 'Semua'],
        ['id' => 'planning', 'label' => 'Planning'],
        ['id' => 'design',   'label' => 'Design'],
        ['id' => 'dev',      'label' => 'Development'],
        ['id' => 'qa',       'label' => 'QA'],
        ['id' => 'done',     'label' => 'Done'],
    ];

    $col = collect($projects);

    $filterCounts = [
        'all'      => count($projects),
        'planning' => $col->where('phase_key', 'planning')->count(),
        'design'   => $col->where('phase_key', 'design')->count(),
        'dev'      => $col->where('phase_key', 'dev')->count(),
        'qa'       => $col->where('phase_key', 'qa')->count(),
        'done'     => $col->where('phase_key', 'done')->count(),
    ];

    $stats = [
        ['label' => 'Total',     'value' => count($projects),                              'color' => '#7C3AED'],
        ['label' => 'On Track',  'value' => $col->where('status', 'on-track')->count(),    'color' => '#10B981'],
        ['label' => 'Attention', 'value' => $col->where('status', 'attention')->count(),   'color' => '#F59E0B'],
        ['label' => 'Critical',  'value' => $col->where('status', 'critical')->count(),    'color' => '#EF4444'],
        ['label' => 'Selesai',   'value' => $col->where('status', 'done')->count(),        'color' => '#94A3B8'],
    ];

    $statusPill = [
        'on-track'  => 'bg-emerald-50 text-emerald-700',
        'attention' => 'bg-amber-50 text-amber-700',
        'critical'  => 'bg-rose-50 text-rose-700',
        'done'      => 'bg-slate-100 text-slate-600',
    ];
    $statusDot = [
        'on-track'  => 'bg-emerald-500',
        'attention' => 'bg-amber-500',
        'critical'  => 'bg-rose-500',
        'done'      => 'bg-slate-400',
    ];

    $monthNum = ['Jan'=>1,'Feb'=>2,'Mar'=>3,'Apr'=>4,'Mei'=>5,'Jun'=>6,'Jul'=>7,'Ags'=>8,'Sep'=>9,'Okt'=>10,'Nov'=>11,'Des'=>12];
    $dueSort = function ($due) use ($monthNum) {
        $parts = preg_split('/\s+/', trim((string) $due));
        if (count($parts) < 2) return 99999;
        return ($monthNum[$parts[1]] ?? 99) * 100 + (int) $parts[0];
    };
@endphp

<x-layouts.authenticated :title="$title">

    <section class="flex items-end justify-between mb-8 gap-6 flex-wrap">
        <div>
            <h1 class="text-[44px] leading-[1.05] font-bold tracking-tight text-[#1E1B4B]">
                Project
                <span class="bg-clip-text text-transparent bg-gradient-to-r from-[#7C3AED] to-[#A855F7]">Master</span>
            </h1>
            <p class="mt-3 text-[15px] text-slate-500 max-w-2xl">
                Kelola semua proyek Avatech dari satu tempat. AI Sekretaris membantu generate WBS &amp; deteksi risiko otomatis.
            </p>
        </div>
        <button
            type="button"
            data-create-trigger="project"
            class="inline-flex items-center gap-2 h-12 px-5 rounded-xl bg-gradient-to-r from-[#7C3AED] via-[#A855F7] to-[#C084FC] text-white font-semibold text-[14px] shadow-[0_4px_14px_rgba(124,58,237,0.35)] hover:shadow-[0_6px_20px_rgba(124,58,237,0.45)] hover:-translate-y-0.5 transition-all cursor-pointer"
        >
            <x-heroicon-o-plus class="w-5 h-5" />
            New Project
        </button>
    </section>

    <section class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-8">
        @foreach ($stats as $s)
            <div class="bg-white rounded-2xl border border-violet-100 shadow-[0_2px_8px_rgba(124,58,237,0.08)] p-5">
                <div class="flex items-center gap-2 mb-2">
                    <span class="w-2 h-2 rounded-full" style="background: {{ $s['color'] }};"></span>
                    <span class="text-[11px] font-bold tracking-wider uppercase text-slate-500">{{ $s['label'] }}</span>
                </div>
                <div class="text-[28px] font-bold text-[#1E1B4B] tabular-nums">{{ $s['value'] }}</div>
            </div>
        @endforeach
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
                    <option value="recent">Terbaru dibuat</option>
                    <option value="progress">Progres tertinggi</option>
                    <option value="due">Mendekati deadline</option>
                    <option value="name">Nama A-Z</option>
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
        @foreach ($projects as $p)
            <article
                data-phase="{{ $p['phase_key'] }}"
                data-search-item
                data-sort-id="{{ $p['id'] }}"
                data-sort-progress="{{ $p['progress'] }}"
                data-sort-due="{{ $dueSort($p['due']) }}"
                data-sort-name="{{ $p['name'] }}"
                onclick="window.location='{{ route('projects.show', $p['id']) }}'"
                class="group bg-white rounded-2xl border border-violet-100 shadow-[0_2px_8px_rgba(124,58,237,0.08)] hover:shadow-[0_8px_24px_rgba(124,58,237,0.12)] transition p-6 flex flex-col relative overflow-hidden cursor-pointer"
            >
                <span class="absolute top-0 left-6 right-6 h-[3px] rounded-b-full" style="background: {{ $p['color'] }};"></span>

                <div class="flex items-start justify-between mb-4 gap-3">
                    <div class="flex items-center gap-3 min-w-0">
                        <div class="w-12 h-12 rounded-xl flex items-center justify-center text-white font-bold text-[13px] flex-shrink-0" style="background: {{ $p['color'] }};">
                            {{ $p['code'] }}
                        </div>
                        <div class="min-w-0">
                            <h3 class="text-[16px] font-bold text-[#1E1B4B] leading-tight group-hover:text-violet-700 transition truncate">{{ $p['name'] }}</h3>
                            <div class="text-[12.5px] text-slate-500 mt-0.5 truncate">{{ $p['client'] }}</div>
                        </div>
                    </div>
                    <span class="inline-flex items-center gap-1.5 text-[11px] font-semibold rounded-full px-2.5 py-1 flex-shrink-0 {{ $statusPill[$p['status']] }}">
                        <span class="w-1.5 h-1.5 rounded-full {{ $statusDot[$p['status']] }}"></span>
                        <span>{{ $p['status_label'] }}</span>
                    </span>
                </div>

                <div class="flex flex-wrap items-center gap-2 mb-4">
                    <span class="text-[11px] font-semibold px-2.5 py-1 rounded-md bg-violet-50 text-violet-700 inline-flex items-center gap-1">
                        <x-heroicon-o-tag class="w-3 h-3" />
                        {{ $p['phase'] }}
                    </span>
                    <span class="text-[11px] font-semibold px-2.5 py-1 rounded-md bg-slate-100 text-slate-600 inline-flex items-center gap-1">
                        <x-heroicon-o-calendar class="w-3 h-3" />
                        Due {{ $p['due'] }}
                    </span>
                    @if ($p['ai_flag'])
                        <span class="text-[11px] font-semibold px-2.5 py-1 rounded-md bg-fuchsia-50 text-fuchsia-700 inline-flex items-center gap-1">
                            <x-heroicon-o-sparkles class="w-3 h-3" />
                            AI WBS ready
                        </span>
                    @endif
                </div>

                <p class="text-[13px] text-slate-500 leading-relaxed mb-5 line-clamp-2">{{ $p['desc'] }}</p>

                <div class="mt-auto">
                    <div class="flex items-center justify-between mb-1.5">
                        <span class="text-[11px] font-semibold uppercase tracking-wider text-slate-500">Progress</span>
                        <span class="text-[13px] font-bold text-[#1E1B4B] tabular-nums">{{ $p['progress'] }}%</span>
                    </div>
                    <div class="h-2 rounded-full bg-[#F3E8FF] overflow-hidden">
                        <div class="h-full rounded-full {{ $statusDot[$p['status']] }}" style="width: {{ $p['progress'] }}%"></div>
                    </div>

                    <div class="mt-4 pt-4 border-t border-violet-50 flex items-center justify-between gap-3">
                        <div class="flex -space-x-2">
                            @foreach ($p['team'] as $m)
                                <div class="w-8 h-8 rounded-full bg-violet-100 text-violet-700 border-2 border-white flex items-center justify-center text-[10.5px] font-bold">{{ $m }}</div>
                            @endforeach
                            @if ($p['team_more'] > 0)
                                <div class="w-8 h-8 rounded-full bg-slate-100 text-slate-500 border-2 border-white flex items-center justify-center text-[10px] font-bold">+{{ $p['team_more'] }}</div>
                            @endif
                        </div>
                        <div class="text-[11.5px] text-slate-500 flex items-center gap-3 flex-shrink-0">
                            <span class="inline-flex items-center gap-1">
                                <x-heroicon-o-check class="w-3.5 h-3.5" />
                                <span class="tabular-nums">{{ $p['tasks_done'] }}/{{ $p['tasks_total'] }}</span>
                            </span>
                            <span class="inline-flex items-center gap-1">
                                <x-heroicon-o-chat-bubble-left class="w-3.5 h-3.5" />
                                <span class="tabular-nums">{{ $p['mom'] }}</span>
                            </span>
                        </div>
                    </div>
                </div>
            </article>
        @endforeach

        <div data-empty class="hidden md:col-span-2 xl:col-span-3 bg-white rounded-2xl border border-dashed border-violet-200 p-12 text-center">
            <div class="w-14 h-14 mx-auto rounded-2xl bg-violet-50 text-violet-400 flex items-center justify-center mb-3">
                <x-heroicon-o-folder-open class="w-6 h-6" />
            </div>
            <h3 class="text-[15px] font-semibold text-[#1E1B4B]">Tidak ada proyek</h3>
            <p class="text-[13px] text-slate-500 mt-1">Coba ubah filter atau buat proyek baru.</p>
        </div>
    </section>

    <section data-view-panel="list" class="hidden bg-white rounded-2xl border border-violet-100 shadow-[0_2px_8px_rgba(124,58,237,0.08)] overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead>
                    <tr class="text-[11px] font-bold tracking-[0.1em] uppercase text-slate-400 border-b border-violet-50">
                        <th class="px-7 py-4">Proyek</th>
                        <th class="px-4 py-4">Klien</th>
                        <th class="px-4 py-4">Phase</th>
                        <th class="px-4 py-4">Team</th>
                        <th class="px-4 py-4 w-[220px]">Progress</th>
                        <th class="px-4 py-4">Status</th>
                        <th class="px-7 py-4 text-right"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($projects as $p)
                        <tr
                            data-phase="{{ $p['phase_key'] }}"
                            data-search-item
                            data-sort-id="{{ $p['id'] }}"
                            data-sort-progress="{{ $p['progress'] }}"
                            data-sort-due="{{ $dueSort($p['due']) }}"
                            data-sort-name="{{ $p['name'] }}"
                            onclick="window.location='{{ route('projects.show', $p['id']) }}'"
                            class="hover:bg-[#FAF5FF] border-b border-violet-50/60 last:border-0 transition cursor-pointer"
                        >
                            <td class="px-7 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-lg flex items-center justify-center text-white font-semibold text-[11px] flex-shrink-0" style="background: {{ $p['color'] }};">
                                        {{ $p['code'] }}
                                    </div>
                                    <div class="min-w-0">
                                        <div class="text-[14px] font-semibold text-[#1E1B4B]">{{ $p['name'] }}</div>
                                        <div class="text-[12px] text-slate-500 truncate max-w-md">{{ $p['desc'] }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-4 text-[13.5px] text-slate-600">{{ $p['client'] }}</td>
                            <td class="px-4 py-4 text-[13px] text-slate-600">{{ $p['phase'] }}</td>
                            <td class="px-4 py-4">
                                <div class="flex -space-x-2">
                                    @foreach ($p['team'] as $m)
                                        <div class="w-7 h-7 rounded-full bg-violet-100 text-violet-700 border-2 border-white flex items-center justify-center text-[10px] font-bold">{{ $m }}</div>
                                    @endforeach
                                </div>
                            </td>
                            <td class="px-4 py-4 w-[220px]">
                                <div class="flex items-center gap-2">
                                    <div class="flex-1 h-2 rounded-full bg-[#F3E8FF] overflow-hidden">
                                        <div class="h-full rounded-full {{ $statusDot[$p['status']] }}" style="width: {{ $p['progress'] }}%"></div>
                                    </div>
                                    <span class="text-[12px] font-bold text-[#1E1B4B] w-9 text-right tabular-nums">{{ $p['progress'] }}%</span>
                                </div>
                            </td>
                            <td class="px-4 py-4">
                                <span class="inline-flex items-center gap-1.5 text-[11px] font-semibold rounded-full px-2.5 py-1 {{ $statusPill[$p['status']] }}">
                                    <span class="w-1.5 h-1.5 rounded-full {{ $statusDot[$p['status']] }}"></span>
                                    {{ $p['status_label'] }}
                                </span>
                            </td>
                            <td class="px-7 py-4 text-right">
                                <x-heroicon-o-chevron-right class="w-4 h-4 text-slate-400 inline-block" />
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>

    <script>
        (function () {
            const wire = () => {
                const chips = document.querySelectorAll('.js-filter-chip');
                const empty = document.querySelector('[data-empty]');

                const applyFilter = (id) => {
                    const items = document.querySelectorAll('[data-phase]'); // live query so user-added cards are included
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
                        const show = id === 'all' || el.dataset.phase === id;
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
                        recent:   { key: 'sortId',       dir: -1, num: true  },
                        progress: { key: 'sortProgress', dir: -1, num: true  },
                        due:      { key: 'sortDue',      dir:  1, num: true  },
                        name:     { key: 'sortName',     dir:  1, num: false },
                    };
                    const sortContainer = (selector) => {
                        const container = document.querySelector(selector);
                        if (! container) return;
                        const c = cfg[sortSel.value];
                        if (! c) return;
                        const all = Array.from(container.children);
                        const sortable = all.filter(el => el.matches('[data-sort-id]'));
                        const rest     = all.filter(el => ! el.matches('[data-sort-id]'));
                        sortable.sort((a, b) => {
                            if (c.num) {
                                return c.dir * (parseFloat(a.dataset[c.key] || 0) - parseFloat(b.dataset[c.key] || 0));
                            }
                            return c.dir * (a.dataset[c.key] || '').localeCompare(b.dataset[c.key] || '');
                        });
                        [...sortable, ...rest].forEach(el => container.appendChild(el));
                    };
                    const applySort = () => {
                        sortContainer('[data-view-panel="grid"]');
                        sortContainer('[data-view-panel="list"] tbody');
                    };
                    sortSel.addEventListener('change', applySort);
                }
            };

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', wire);
            } else {
                wire();
            }
        })();
    </script>

    {{-- ===== Create Project Modal ===== --}}
    <div data-create-modal="project" class="hidden fixed inset-0 z-50 items-center justify-center p-4 sm:p-6" role="dialog" aria-modal="true">
        <div data-create-overlay class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm"></div>
        <div data-create-panel class="relative bg-white rounded-3xl shadow-[0_24px_64px_rgba(124,58,237,0.18)] w-full max-w-md flex flex-col overflow-hidden border border-violet-100">
            <div class="px-6 py-4 border-b border-violet-100 flex items-center justify-between">
                <h3 class="text-[16px] font-bold text-[#1E1B4B]">Proyek Baru</h3>
                <button type="button" data-create-close aria-label="Tutup" class="w-9 h-9 rounded-full hover:bg-violet-50 flex items-center justify-center text-slate-500 hover:text-rose-500 transition cursor-pointer">
                    <x-heroicon-o-x-mark class="w-5 h-5" />
                </button>
            </div>
            <div class="px-6 py-5 space-y-3">
                <div class="flex items-start gap-2.5 p-3 rounded-lg bg-violet-50/70 border border-violet-100 text-[12px] text-slate-600">
                    <x-heroicon-o-sparkles class="w-4 h-4 text-violet-600 flex-shrink-0 mt-0.5" />
                    <span>Project baru otomatis dimulai dari <strong class="text-[#1E1B4B]">Planning</strong> dengan status <strong class="text-[#1E1B4B]">On Track</strong>.</span>
                </div>
                <div>
                    <label class="block text-[11px] font-bold tracking-wider uppercase text-slate-500 mb-1.5">Kode (2 huruf)</label>
                    <input data-cp-code maxlength="2" class="w-full h-10 rounded-lg border border-violet-100 px-3 text-[13px] uppercase focus:outline-none focus:ring-2 focus:ring-violet-300" placeholder="AC" />
                </div>
                <div>
                    <label class="block text-[11px] font-bold tracking-wider uppercase text-slate-500 mb-1.5">Nama Proyek</label>
                    <input data-cp-name class="w-full h-10 rounded-lg border border-violet-100 px-3 text-[13px] focus:outline-none focus:ring-2 focus:ring-violet-300" placeholder="Nama proyek..." />
                </div>
                <div>
                    <label class="block text-[11px] font-bold tracking-wider uppercase text-slate-500 mb-1.5">Klien</label>
                    <input data-cp-client class="w-full h-10 rounded-lg border border-violet-100 px-3 text-[13px] focus:outline-none focus:ring-2 focus:ring-violet-300" placeholder="Nama klien..." />
                </div>
                <div>
                    <label class="block text-[11px] font-bold tracking-wider uppercase text-slate-500 mb-1.5">Deskripsi</label>
                    <textarea data-cp-desc rows="3" class="w-full rounded-lg border border-violet-100 px-3 py-2 text-[13px] focus:outline-none focus:ring-2 focus:ring-violet-300 resize-y" placeholder="Ringkasan singkat proyek..."></textarea>
                </div>
                <div>
                    <label class="block text-[11px] font-bold tracking-wider uppercase text-slate-500 mb-1.5">Due (contoh: 30 Jun)</label>
                    <input data-cp-due class="w-full h-10 rounded-lg border border-violet-100 px-3 text-[13px] focus:outline-none focus:ring-2 focus:ring-violet-300" placeholder="30 Jun" />
                </div>
            </div>
            <div class="px-6 py-3 border-t border-violet-100 bg-violet-50/30 flex items-center justify-end gap-2">
                <button type="button" data-create-close class="h-9 px-4 rounded-lg text-[12.5px] font-semibold text-slate-500 hover:text-slate-700 transition cursor-pointer">Batal</button>
                <button type="button" data-create-save class="inline-flex items-center gap-2 h-9 px-4 rounded-lg bg-gradient-to-r from-[#7C3AED] via-[#A855F7] to-[#C084FC] text-white font-semibold text-[12.5px] shadow-[0_2px_8px_rgba(124,58,237,0.2)] hover:scale-[1.02] transition cursor-pointer">
                    <x-heroicon-o-bookmark-square class="w-4 h-4" />
                    Simpan
                </button>
            </div>
        </div>
    </div>

    <script>
        (function () {
            const wire = () => {
                const modal   = document.querySelector('[data-create-modal="project"]');
                const trigger = document.querySelector('[data-create-trigger="project"]');
                if (! modal || ! trigger) return;
                const overlay = modal.querySelector('[data-create-overlay]');
                const panel   = modal.querySelector('[data-create-panel]');
                const grid    = document.querySelector('[data-view-panel="grid"]');
                const tbody   = document.querySelector('[data-view-panel="list"] tbody');
                const LS_KEY  = 'avt-projects-added';

                const phaseLabel  = { planning:'Planning', design:'Design', dev:'Development', qa:'QA', done:'Done' };
                const statusLabel = { 'on-track':'On Track', attention:'Needs Attention', critical:'Critical', done:'Selesai' };
                const statusPill  = { 'on-track':'bg-emerald-50 text-emerald-700', attention:'bg-amber-50 text-amber-700', critical:'bg-rose-50 text-rose-700', done:'bg-slate-100 text-slate-600' };
                const statusDot   = { 'on-track':'bg-emerald-500', attention:'bg-amber-500', critical:'bg-rose-500', done:'bg-slate-400' };
                const monthNum    = { Jan:1,Feb:2,Mar:3,Apr:4,Mei:5,Jun:6,Jul:7,Ags:8,Sep:9,Okt:10,Nov:11,Des:12 };
                const dueSort     = (due) => {
                    const m = String(due || '').trim().split(/\s+/);
                    if (m.length < 2) return 99999;
                    return ((monthNum[m[1]] || 99) * 100) + (parseInt(m[0], 10) || 0);
                };
                const esc = (s) => String(s).replace(/[&<>"]/g, (c) => ({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c]));

                window.__demoProjects = window.__demoProjects || {};

                const renderCard = (p) => {
                    if (! grid) return;
                    window.__demoProjects[p.id] = p;
                    const a = document.createElement('article');
                    a.setAttribute('data-phase', p.phase_key);
                    a.setAttribute('data-search-item', '');
                    a.setAttribute('data-sort-id', p.id);
                    a.setAttribute('data-sort-progress', p.progress);
                    a.setAttribute('data-sort-due', dueSort(p.due));
                    a.setAttribute('data-sort-name', p.name);
                    a.setAttribute('data-demo-detail', p.id);
                    a.title = p.name + ' — detail demo (klik untuk membuka)';
                    a.className = 'group bg-white rounded-2xl border border-violet-100 shadow-[0_2px_8px_rgba(124,58,237,0.08)] hover:shadow-[0_8px_24px_rgba(124,58,237,0.12)] transition p-6 flex flex-col relative overflow-hidden cursor-pointer';
                    const descText = (p.desc && p.desc.trim()) ? p.desc : 'Proyek baru ditambahkan dari Project Master (demo, tersimpan client-side).';
                    a.innerHTML = ''
                        + '<span class="absolute top-0 left-6 right-6 h-[3px] rounded-b-full" style="background:#7C3AED"></span>'
                        + '<div class="flex items-start justify-between mb-4 gap-3">'
                        + '  <div class="flex items-center gap-3 min-w-0">'
                        + '    <div class="w-12 h-12 rounded-xl flex items-center justify-center text-white font-bold text-[13px] flex-shrink-0" style="background:#7C3AED">' + esc(p.code) + '</div>'
                        + '    <div class="min-w-0">'
                        + '      <h3 class="text-[16px] font-bold text-[#1E1B4B] leading-tight group-hover:text-violet-700 transition truncate">' + esc(p.name) + '</h3>'
                        + '      <div class="text-[12.5px] text-slate-500 mt-0.5 truncate">' + esc(p.client) + '</div>'
                        + '    </div>'
                        + '  </div>'
                        + '  <span class="inline-flex items-center gap-1.5 text-[11px] font-semibold rounded-full px-2.5 py-1 flex-shrink-0 ' + statusPill[p.status] + '">'
                        + '    <span class="w-1.5 h-1.5 rounded-full ' + statusDot[p.status] + '"></span>'
                        + '    <span>' + statusLabel[p.status] + '</span>'
                        + '  </span>'
                        + '</div>'
                        + '<div class="flex flex-wrap items-center gap-2 mb-4">'
                        + '  <span class="text-[11px] font-semibold px-2.5 py-1 rounded-md bg-violet-50 text-violet-700">' + phaseLabel[p.phase_key] + '</span>'
                        + '  <span class="text-[11px] font-semibold px-2.5 py-1 rounded-md bg-slate-100 text-slate-600">Due ' + esc(p.due) + '</span>'
                        + '  <span class="text-[10px] font-semibold px-2 py-0.5 rounded-md bg-fuchsia-100 text-fuchsia-700">DEMO</span>'
                        + '</div>'
                        + '<p class="text-[13px] text-slate-500 leading-relaxed mb-5 line-clamp-2">' + esc(descText) + '</p>'
                        + '<div class="mt-auto">'
                        + '  <div class="flex items-center justify-between mb-1.5"><span class="text-[11px] font-semibold uppercase tracking-wider text-slate-500">Progress</span><span class="text-[13px] font-bold text-[#1E1B4B] tabular-nums">' + p.progress + '%</span></div>'
                        + '  <div class="h-2 rounded-full bg-[#F3E8FF] overflow-hidden"><div class="h-full rounded-full ' + statusDot[p.status] + '" style="width:' + p.progress + '%"></div></div>'
                        + '</div>';
                    grid.insertBefore(a, grid.firstChild);
                };

                // Restore added projects
                try {
                    const saved = JSON.parse(localStorage.getItem(LS_KEY) || '[]');
                    saved.forEach(renderCard);
                } catch (e) {}

                const open = () => {
                    modal.classList.remove('hidden');
                    modal.classList.add('flex');
                    document.body.style.overflow = 'hidden';
                    modal.querySelector('[data-cp-name]')?.focus();
                };
                const close = () => {
                    modal.classList.add('hidden');
                    modal.classList.remove('flex');
                    document.body.style.overflow = '';
                };

                trigger.addEventListener('click', open);
                panel?.addEventListener('click', (e) => e.stopPropagation());
                overlay?.addEventListener('click', close);
                modal.querySelectorAll('[data-create-close]').forEach(b => b.addEventListener('click', close));
                document.addEventListener('keydown', (e) => {
                    if (e.key === 'Escape' && ! modal.classList.contains('hidden')) close();
                });

                modal.querySelector('[data-create-save]')?.addEventListener('click', () => {
                    const code   = (modal.querySelector('[data-cp-code]').value || '').trim().toUpperCase();
                    const name   = (modal.querySelector('[data-cp-name]').value || '').trim();
                    const client = (modal.querySelector('[data-cp-client]').value || '').trim();
                    const desc   = (modal.querySelector('[data-cp-desc]').value || '').trim();
                    const due    = (modal.querySelector('[data-cp-due]').value || '').trim();
                    if (! code || ! name || ! client) {
                        window.toast && window.toast('Lengkapi kode, nama, dan klien.');
                        return;
                    }
                    /* Auto-defaults — new projects always start at Planning / On Track / 0% */
                    const p = {
                        id: Date.now(),
                        code: code.slice(0, 2),
                        name, client, desc,
                        phase_key: 'planning',
                        status:    'on-track',
                        progress:  0,
                        due:       due || '—',
                        modules_total: 0,
                        tasks_done:    0,
                        tasks_total:   0,
                        mom_total:     0,
                        qc_pass_rate:  0,
                    };
                    renderCard(p);
                    try {
                        const cur = JSON.parse(localStorage.getItem(LS_KEY) || '[]');
                        cur.push(p);
                        localStorage.setItem(LS_KEY, JSON.stringify(cur));
                    } catch (e) {}
                    // Reset
                    modal.querySelectorAll('input, textarea').forEach(i => i.value = '');
                    close();
                    window.toast && window.toast('Proyek "' + name + '" ditambahkan (demo).');
                });
            };
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', wire);
            } else {
                wire();
            }
        })();
    </script>

    {{-- ===== Demo Project Detail Modal (for user-created projects) ===== --}}
    <div data-demo-detail-modal class="hidden fixed inset-0 z-50 items-center justify-center p-4 sm:p-6" role="dialog" aria-modal="true" aria-labelledby="demo-detail-title">
        <div data-demo-detail-overlay class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm"></div>
        <div data-demo-detail-panel class="relative bg-white rounded-3xl shadow-[0_24px_64px_rgba(124,58,237,0.18)] w-full max-w-2xl max-h-[90vh] flex flex-col overflow-hidden border border-violet-100">
            <span class="block h-1 w-full bg-gradient-to-r from-[#7C3AED] to-[#EC4899]"></span>

            <div class="px-7 pt-6 pb-5 flex items-start justify-between gap-4 border-b border-violet-100">
                <div class="flex items-start gap-4 min-w-0">
                    <div data-demo-code class="w-14 h-14 rounded-2xl flex items-center justify-center text-white font-bold text-[16px] flex-shrink-0" style="background:#7C3AED">—</div>
                    <div class="min-w-0">
                        <div class="flex items-center gap-2 mb-1 flex-wrap">
                            <span class="inline-flex items-center gap-1.5 text-[10px] font-bold uppercase tracking-wider rounded-full px-2.5 py-0.5 bg-emerald-50 text-emerald-700 border border-emerald-100">
                                <span class="w-1 h-1 rounded-full bg-emerald-500"></span> On Track
                            </span>
                            <span class="inline-flex items-center text-[10px] font-bold uppercase tracking-wider rounded-full px-2.5 py-0.5 bg-fuchsia-100 text-fuchsia-700">DEMO</span>
                        </div>
                        <h2 id="demo-detail-title" data-demo-name class="text-[22px] leading-tight font-extrabold text-[#1E1B4B] truncate">—</h2>
                        <div class="text-[12.5px] text-slate-500 mt-1 inline-flex items-center gap-2 flex-wrap">
                            <span class="inline-flex items-center gap-1"><x-heroicon-o-building-office class="w-3.5 h-3.5" /><span data-demo-client>—</span></span>
                            <span class="w-1 h-1 rounded-full bg-slate-300"></span>
                            <span class="inline-flex items-center gap-1"><x-heroicon-o-tag class="w-3.5 h-3.5" /><span>Planning</span></span>
                            <span class="w-1 h-1 rounded-full bg-slate-300"></span>
                            <span class="inline-flex items-center gap-1"><x-heroicon-o-calendar-days class="w-3.5 h-3.5" /><span>Due <span data-demo-due>—</span></span></span>
                        </div>
                    </div>
                </div>
                <button type="button" data-demo-detail-close aria-label="Tutup" class="w-9 h-9 rounded-full hover:bg-violet-50 flex items-center justify-center text-slate-500 hover:text-rose-500 transition cursor-pointer flex-shrink-0">
                    <x-heroicon-o-x-mark class="w-5 h-5" />
                </button>
            </div>

            <div class="flex-1 overflow-y-auto px-7 py-5 space-y-5">
                <div>
                    <h4 class="text-[10.5px] font-bold tracking-wider uppercase text-slate-400 mb-1.5">Deskripsi</h4>
                    <p data-demo-desc class="text-[13.5px] text-slate-600 leading-relaxed">—</p>
                </div>

                <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                    <div class="bg-white border border-violet-100 rounded-xl overflow-hidden">
                        <div class="h-[3px] bg-blue-500"></div>
                        <div class="p-3">
                            <div class="text-[10px] font-bold tracking-wider uppercase text-slate-400 mb-1">Modul</div>
                            <div class="text-[20px] font-bold text-[#1E1B4B] tabular-nums">0/0</div>
                        </div>
                    </div>
                    <div class="bg-white border border-violet-100 rounded-xl overflow-hidden">
                        <div class="h-[3px] bg-violet-500"></div>
                        <div class="p-3">
                            <div class="text-[10px] font-bold tracking-wider uppercase text-slate-400 mb-1">Task</div>
                            <div class="text-[20px] font-bold text-[#1E1B4B] tabular-nums">0/0</div>
                        </div>
                    </div>
                    <div class="bg-white border border-violet-100 rounded-xl overflow-hidden">
                        <div class="h-[3px] bg-emerald-500"></div>
                        <div class="p-3">
                            <div class="text-[10px] font-bold tracking-wider uppercase text-slate-400 mb-1">MoM</div>
                            <div class="text-[20px] font-bold text-[#1E1B4B] tabular-nums">0</div>
                        </div>
                    </div>
                    <div class="bg-white border border-violet-100 rounded-xl overflow-hidden">
                        <div class="h-[3px] bg-amber-500"></div>
                        <div class="p-3">
                            <div class="text-[10px] font-bold tracking-wider uppercase text-slate-400 mb-1">QC</div>
                            <div class="text-[20px] font-bold text-[#1E1B4B] tabular-nums">0%</div>
                        </div>
                    </div>
                </div>

                <div>
                    <div class="flex items-center justify-between mb-1.5">
                        <span class="text-[11px] font-semibold uppercase tracking-wider text-slate-500">Progress</span>
                        <span class="text-[13px] font-bold text-[#1E1B4B] tabular-nums">0%</span>
                    </div>
                    <div class="h-2 rounded-full bg-[#F3E8FF] overflow-hidden">
                        <div class="h-full rounded-full bg-emerald-500" style="width:0%"></div>
                    </div>
                </div>

                <div class="flex items-start gap-2.5 p-3 rounded-lg bg-violet-50/60 border border-violet-100 text-[12px] text-slate-600">
                    <x-heroicon-o-information-circle class="w-4 h-4 text-violet-600 flex-shrink-0 mt-0.5" />
                    <span>Project detail lengkap (WBS, Workspace, AI Planning, QC) tersedia setelah proyek ini disimpan ke server. Saat ini ditampilkan dari penyimpanan lokal (demo).</span>
                </div>
            </div>

            <div class="px-7 py-3 border-t border-violet-100 bg-violet-50/30 flex items-center justify-end gap-2">
                <button type="button" data-demo-detail-close class="h-9 px-5 rounded-xl bg-white border border-violet-200 text-[13px] font-semibold text-slate-600 hover:border-violet-400 hover:text-violet-700 transition cursor-pointer">Tutup</button>
            </div>
        </div>
    </div>

    <script>
        (function () {
            const wire = () => {
                const modal   = document.querySelector('[data-demo-detail-modal]');
                if (! modal) return;
                const overlay = modal.querySelector('[data-demo-detail-overlay]');
                const panel   = modal.querySelector('[data-demo-detail-panel]');

                const fields = {
                    code:   modal.querySelector('[data-demo-code]'),
                    name:   modal.querySelector('[data-demo-name]'),
                    client: modal.querySelector('[data-demo-client]'),
                    due:    modal.querySelector('[data-demo-due]'),
                    desc:   modal.querySelector('[data-demo-desc]'),
                };

                const lookup = (id) => {
                    if (window.__demoProjects && window.__demoProjects[id]) return window.__demoProjects[id];
                    try {
                        const arr = JSON.parse(localStorage.getItem('avt-projects-added') || '[]');
                        return arr.find(p => String(p.id) === String(id));
                    } catch (e) { return null; }
                };

                const open = (id) => {
                    const p = lookup(id);
                    if (! p) {
                        window.toast && window.toast('Data proyek demo tidak ditemukan.');
                        return;
                    }
                    if (fields.code)   fields.code.textContent   = p.code || '—';
                    if (fields.name)   fields.name.textContent   = p.name || '—';
                    if (fields.client) fields.client.textContent = p.client || '—';
                    if (fields.due)    fields.due.textContent    = p.due || '—';
                    if (fields.desc)   fields.desc.textContent   = (p.desc && p.desc.trim()) ? p.desc : 'Belum ada deskripsi untuk proyek ini.';
                    modal.classList.remove('hidden');
                    modal.classList.add('flex');
                    document.body.style.overflow = 'hidden';
                };
                const close = () => {
                    modal.classList.add('hidden');
                    modal.classList.remove('flex');
                    document.body.style.overflow = '';
                };

                document.addEventListener('click', (e) => {
                    const trigger = e.target.closest('[data-demo-detail]');
                    if (! trigger) return;
                    open(trigger.dataset.demoDetail);
                });
                panel?.addEventListener('click', (e) => e.stopPropagation());
                overlay?.addEventListener('click', close);
                modal.querySelectorAll('[data-demo-detail-close]').forEach(b => b.addEventListener('click', close));
                document.addEventListener('keydown', (e) => {
                    if (e.key === 'Escape' && ! modal.classList.contains('hidden')) close();
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
