@php
    $events ??= [];
    $actorOptions ??= [];
    $todayCount ??= 0;
    $activeChip ??= 'all';
    $selectedActor ??= 'all';
    $selectedRange ??= 'all';
    $isOperationalView ??= false;

    $filters = [
        ['id' => 'all',      'label' => 'Semua'],
        ['id' => 'proyek',   'label' => 'Proyek'],
        ['id' => 'klien',    'label' => 'Klien'],
        ['id' => 'tim',      'label' => 'Tim'],
        ['id' => 'settings', 'label' => 'Settings'],
        ['id' => 'login',    'label' => 'Login'],
    ];

    $col = collect($events);

    $filterCounts = [
        'all'      => $col->count(),
        'proyek'   => $col->where('filter', 'proyek')->count(),
        'klien'    => $col->where('filter', 'klien')->count(),
        'tim'      => $col->where('filter', 'tim')->count(),
        'settings' => $col->where('filter', 'settings')->count(),
        'login'    => $col->where('filter', 'login')->count(),
    ];

    /* Operational view: hide chips that are always zero (Klien / Settings /
       Login are unreachable from their UI). Keep "Semua" plus whatever has
       at least one log so the chip row reads as actionable. CEO/PM keep all. */
    if ($isOperationalView) {
        $filters = array_values(array_filter(
            $filters,
            fn ($f) => $f['id'] === 'all' || ($filterCounts[$f['id']] ?? 0) > 0,
        ));
    }

    $dataChanged = $filterCounts['proyek'] + $filterCounts['klien'] + $filterCounts['tim'];
    $visibleCount = $activeChip === 'all' ? $filterCounts['all'] : ($filterCounts[$activeChip] ?? $filterCounts['all']);

    $stats = [
        ['label' => 'Total Entri',  'value' => $visibleCount,            'note' => $activeChip === 'all' ? $todayCount . ' aktivitas hari ini' : 'sesuai chip aktif', 'color' => '#7C3AED'],
        ['label' => 'Proyek',       'value' => $filterCounts['proyek'],  'note' => 'create/update/archive',              'color' => '#A855F7'],
        ['label' => 'Klien',        'value' => $filterCounts['klien'],   'note' => 'create/update/archive',              'color' => '#10B981'],
        ['label' => 'Tim',          'value' => $filterCounts['tim'],     'note' => 'anggota + penugasan',                'color' => '#EC4899'],
        ['label' => 'Settings',     'value' => $filterCounts['settings'],'note' => 'preferensi & integrasi',             'color' => '#F97316'],
    ];

    $tagPaletteByFilter = [
        'proyek'   => ['bg' => '#EDE9FE', 'color' => '#5B21B6'],
        'klien'    => ['bg' => '#DCFCE7', 'color' => '#166534'],
        'tim'      => ['bg' => '#FAE8FF', 'color' => '#86198F'],
        'settings' => ['bg' => '#FED7AA', 'color' => '#9A3412'],
        'login'    => ['bg' => '#F1F5F9', 'color' => '#334155'],
        'all'      => ['bg' => '#F3E8FF', 'color' => '#6B21A8'],
    ];

    $actorColorPalette = ['#9333EA', '#8B5CF6', '#EC4899', '#10B981', '#3B82F6', '#F97316', '#7C3AED', '#0EA5E9', '#F59E0B'];
    $actorColorMap = [];
    foreach ($col->pluck('actor')->unique()->values() as $idx => $actorName) {
        $actorColorMap[$actorName] = $actorColorPalette[$idx % count($actorColorPalette)];
    }

    /* Preserve original DB order when grouping by date label. */
    $groups = $col->groupBy('date');
@endphp

<x-layouts.authenticated :title="$title">

    @php $isOperationalView ??= false; @endphp
    <section class="flex items-end justify-between mb-8 gap-6 flex-wrap">
        <div>
            <h1 class="text-[44px] leading-[1.05] font-bold tracking-tight text-[#1E1B4B]">
                @if ($isOperationalView)
                    Activity
                    <span class="bg-clip-text text-transparent bg-gradient-to-r from-[#7C3AED] to-[#A855F7]">Log</span>
                @else
                    Audit
                    <span class="bg-clip-text text-transparent bg-gradient-to-r from-[#7C3AED] to-[#A855F7]">Trail</span>
                @endif
            </h1>
            <p class="mt-3 text-[15px] text-slate-500 max-w-2xl">
                @if ($isOperationalView)
                    Riwayat aktivitas Anda di Smart-PMIS.
                @else
                    Riwayat lengkap semua aktivitas di Smart-PMIS &mdash; siapa melakukan apa, kapan, dan dari modul mana.
                @endif
            </p>
        </div>
        @unless ($isOperationalView)
            <div class="flex items-center gap-2 flex-wrap">
                <button type="button" data-export-audit-csv class="inline-flex items-center gap-2 h-11 px-4 rounded-xl border border-violet-100 bg-white text-[13px] font-semibold text-slate-600 hover:border-violet-300 hover:text-violet-700 transition cursor-pointer">
                    <x-heroicon-o-arrow-down-tray class="w-4 h-4" />
                    Export CSV
                </button>
                <button type="button" data-export-audit-report class="inline-flex items-center gap-2 h-12 px-5 rounded-xl bg-gradient-to-r from-[#7C3AED] via-[#A855F7] to-[#C084FC] text-white font-semibold text-[14px] shadow-[0_4px_14px_rgba(124,58,237,0.35)] hover:shadow-[0_6px_20px_rgba(124,58,237,0.45)] hover:-translate-y-0.5 transition-all cursor-pointer">
                    <x-heroicon-o-document-text class="w-5 h-5" />
                    Laporan Audit
                </button>
            </div>
        @endunless
    </section>

    <section class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-8">
        @foreach ($stats as $s)
            <div class="bg-white rounded-2xl border border-violet-100 shadow-[0_2px_8px_rgba(124,58,237,0.08)] p-5" @if ($s['label'] === 'Total Entri') data-visible-total-card @endif>
                <div class="flex items-center gap-2 mb-2">
                    <span class="w-2 h-2 rounded-full" style="background: {{ $s['color'] }};"></span>
                    <span class="text-[11px] font-bold tracking-wider uppercase text-slate-500">{{ $s['label'] }}</span>
                </div>
                <div class="text-[28px] font-bold text-[#1E1B4B] tabular-nums" @if ($s['label'] === 'Total Entri') data-visible-total-value @endif>{{ $s['value'] }}</div>
                <div class="text-[11.5px] text-slate-400 mt-0.5" @if ($s['label'] === 'Total Entri') data-visible-total-note @endif>{{ $s['note'] }}</div>
            </div>
        @endforeach
    </section>

    <section class="bg-white rounded-2xl border border-violet-100 shadow-[0_2px_8px_rgba(124,58,237,0.08)] p-5 mb-6 flex flex-wrap items-center gap-4">
        <div class="flex items-center gap-2 flex-wrap">
            @foreach ($filters as $idx => $f)
                @php $isActive = $f['id'] === $activeChip; @endphp
                <button
                    type="button"
                    data-filter="{{ $f['id'] }}"
                    @class([
                        'js-filter-chip text-[12.5px] font-semibold px-3.5 py-1.5 rounded-full transition border inline-flex items-center gap-1.5 cursor-pointer',
                        'bg-[#1E1B4B] text-white border-[#1E1B4B] shadow-sm' => $isActive,
                        'bg-white text-slate-600 border-violet-100 hover:border-violet-300 hover:text-violet-700' => ! $isActive,
                    ])
                >
                    <span>{{ $f['label'] }}</span>
                    <span
                        data-count-badge
                        @class([
                            'text-[10px] font-bold px-1.5 py-0.5 rounded-full',
                            'bg-white/15 text-white' => $isActive,
                            'bg-violet-100 text-violet-700' => ! $isActive,
                        ])
                    >{{ $filterCounts[$f['id']] }}</span>
                </button>
            @endforeach
        </div>

        <div class="ml-auto flex items-center gap-3 flex-wrap">
            @unless ($isOperationalView)
                <div class="relative">
                    <select data-audit-actor class="appearance-none h-10 pl-4 pr-9 rounded-xl border border-violet-100 bg-white text-[13px] text-slate-600 font-medium focus:outline-none focus:ring-2 focus:ring-violet-300 cursor-pointer">
                        <option value="all" @selected($selectedActor === 'all')>Semua Pengguna</option>
                        @foreach ($actorOptions as $actorName)
                            <option value="{{ $actorName }}" @selected($selectedActor === $actorName)>{{ $actorName }}</option>
                        @endforeach
                    </select>
                    <x-heroicon-o-chevron-down class="w-4 h-4 text-slate-400 absolute right-3 top-1/2 -translate-y-1/2 pointer-events-none" />
                </div>
            @endunless
            <div class="relative">
                <select data-audit-range class="appearance-none h-10 pl-4 pr-9 rounded-xl border border-violet-100 bg-white text-[13px] text-slate-600 font-medium focus:outline-none focus:ring-2 focus:ring-violet-300 cursor-pointer">
                    <option value="7" @selected($selectedRange === '7')>7 hari terakhir</option>
                    <option value="30" @selected($selectedRange === '30')>30 hari terakhir</option>
                    <option value="90" @selected($selectedRange === '90')>90 hari terakhir</option>
                    <option value="all" @selected($selectedRange === 'all')>Semua waktu</option>
                </select>
                <x-heroicon-o-chevron-down class="w-4 h-4 text-slate-400 absolute right-3 top-1/2 -translate-y-1/2 pointer-events-none" />
            </div>
        </div>
    </section>

    <section class="space-y-8" data-audit-list>
        @forelse ($groups as $date => $items)
            @php $groupDays = (int) ($items->first()['days'] ?? 99); @endphp
            <div data-group data-group-days="{{ $groupDays }}">
                <div class="flex items-center gap-3 mb-3">
                    <div class="text-[11px] font-bold tracking-[0.15em] uppercase text-violet-500">{{ $date }}</div>
                    <div class="h-px flex-1 bg-violet-100"></div>
                    <div class="text-[11.5px] text-slate-400 tabular-nums" data-group-count>{{ count($items) }} entri</div>
                </div>

                <div class="bg-white rounded-2xl border border-violet-100 shadow-[0_2px_8px_rgba(124,58,237,0.08)] overflow-hidden">
                    @foreach ($items as $a)
                        @php $palette = $tagPaletteByFilter[$a['filter']] ?? $tagPaletteByFilter['all']; @endphp
                        <div
                            data-entry="{{ $a['filter'] }}"
                            data-actor="{{ $a['actor'] }}"
                            data-days="{{ $a['days'] ?? 99 }}"
                            class="flex items-start gap-4 px-6 py-4 border-b border-violet-50 last:border-0 hover:bg-violet-50/40 transition"
                        >
                            <div class="w-10 h-10 rounded-full flex items-center justify-center font-bold text-[11.5px] text-white flex-shrink-0" style="background: {{ $actorColorMap[$a['actor']] ?? '#7C3AED' }};">
                                {{ $a['initials'] }}
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2 flex-wrap mb-1.5">
                                    <span class="inline-flex items-center text-[10.5px] font-bold tracking-wide uppercase px-2.5 py-1 rounded-md" style="background: {{ $palette['bg'] }}; color: {{ $palette['color'] }};">
                                        {{ $a['tag'] }}
                                    </span>
                                    <span class="text-[11.5px] text-slate-400">&middot;</span>
                                    <span class="text-[11.5px] text-slate-500">{{ $a['module'] }}</span>
                                </div>
                                <p class="text-[13.5px] text-[#1E1B4B] leading-snug">{!! $a['text'] !!}</p>
                                <div class="text-[12px] text-slate-500 mt-1">{{ $a['actor'] }}</div>
                            </div>
                            <div class="text-[11.5px] text-slate-400 flex-shrink-0 pt-1 tabular-nums">{{ $a['time'] }}</div>
                        </div>
                    @endforeach
                </div>
            </div>
        @empty
            <div class="bg-white rounded-2xl border border-dashed border-violet-200 p-12 text-center">
                <div class="w-14 h-14 mx-auto rounded-2xl bg-violet-50 text-violet-400 flex items-center justify-center mb-3">
                    <x-heroicon-o-inbox class="w-6 h-6" />
                </div>
                <h3 class="text-[15px] font-semibold text-[#1E1B4B]">Belum ada aktivitas</h3>
                <p class="text-[13px] text-slate-500 mt-1">Aksi pada Proyek, Klien, Tim, atau Settings akan tampil di sini.</p>
            </div>
        @endforelse

        <div data-empty class="hidden bg-white rounded-2xl border border-dashed border-violet-200 p-12 text-center">
            <div class="w-14 h-14 mx-auto rounded-2xl bg-violet-50 text-violet-400 flex items-center justify-center mb-3">
                <x-heroicon-o-inbox class="w-6 h-6" />
            </div>
            <h3 class="text-[15px] font-semibold text-[#1E1B4B]">Tidak ada aktivitas</h3>
            <p class="text-[13px] text-slate-500 mt-1">Coba ubah filter atau rentang waktu.</p>
        </div>
    </section>

    <script>
        window.__auditRoutes = {
            export: @json(route('audit.export')),
            report: @json(route('audit.report')),
        };
    </script>

    <script>
        (function () {
            const wire = () => {
                const chips = document.querySelectorAll('.js-filter-chip');
                const groups = document.querySelectorAll('[data-group]');
                const empty = document.querySelector('[data-empty]');

                const actorSel  = document.querySelector('[data-audit-actor]');
                const rangeSel  = document.querySelector('[data-audit-range]');
                const totalValue = document.querySelector('[data-visible-total-value]');
                const totalNote = document.querySelector('[data-visible-total-note]');
                let activeChip  = @json($activeChip);

                const applyFilter = () => {
                    chips.forEach(c => {
                        const active = c.dataset.filter === activeChip;
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

                    const wantActor = actorSel ? actorSel.value : 'all';
                    const rangeRaw  = rangeSel ? rangeSel.value : 'all';
                    const maxDays   = rangeRaw === 'all' ? Infinity : parseInt(rangeRaw, 10);

                    let totalVisible = 0;

                    groups.forEach(group => {
                        const entries = group.querySelectorAll('[data-entry]');
                        let visibleInGroup = 0;

                        entries.forEach(entry => {
                            const chipOk   = activeChip === 'all' || entry.dataset.entry === activeChip;
                            const actorOk  = wantActor === 'all' || wantActor === '' || entry.dataset.actor === wantActor;
                            const days     = parseFloat(entry.dataset.days || '99');
                            const rangeOk  = days <= maxDays;
                            const show = chipOk && actorOk && rangeOk;
                            entry.classList.toggle('hidden', !show);
                            if (show) visibleInGroup++;
                        });

                        group.classList.toggle('hidden', visibleInGroup === 0);

                        const countEl = group.querySelector('[data-group-count]');
                        if (countEl) countEl.textContent = visibleInGroup + ' entri';

                        totalVisible += visibleInGroup;
                    });

                    if (empty) empty.classList.toggle('hidden', totalVisible > 0);
                    if (totalValue) totalValue.textContent = totalVisible;
                    if (totalNote) totalNote.textContent = activeChip === 'all' ? 'semua chip aktif' : 'sesuai chip aktif';
                };

                const syncQueryString = () => {
                    const params = new URLSearchParams(window.location.search);
                    if (activeChip && activeChip !== 'all') params.set('chip', activeChip);
                    else params.delete('chip');

                    const actor = actorSel ? actorSel.value : 'all';
                    if (actor && actor !== 'all') params.set('actor', actor);
                    else params.delete('actor');

                    const range = rangeSel ? rangeSel.value : 'all';
                    if (range && range !== 'all') params.set('range', range);
                    else params.delete('range');

                    const qs = params.toString();
                    const next = window.location.pathname + (qs ? '?' + qs : '');
                    window.history.replaceState({}, '', next);
                };

                chips.forEach(c => {
                    c.addEventListener('click', () => {
                        activeChip = c.dataset.filter;
                        applyFilter();
                        syncQueryString();
                    });
                });
                actorSel?.addEventListener('change', () => {
                    applyFilter();
                    syncQueryString();
                });
                rangeSel?.addEventListener('change', () => {
                    applyFilter();
                    syncQueryString();
                });
                applyFilter();

                /* === Exports — real server routes, honoring current filters === */
                const buildExportUrl = (base) => {
                    const params = new URLSearchParams();
                    if (activeChip && activeChip !== 'all') params.set('chip', activeChip);
                    const a = actorSel ? actorSel.value : 'all';
                    if (a && a !== 'all' && a !== '') params.set('actor', a);
                    const r = rangeSel ? rangeSel.value : 'all';
                    if (r && r !== 'all') params.set('range', r);
                    const qs = params.toString();
                    return qs ? base + '?' + qs : base;
                };

                document.querySelector('[data-export-audit-csv]')?.addEventListener('click', () => {
                    const url = buildExportUrl(window.__auditRoutes?.export || '');
                    if (! url) return;
                    window.location.href = url;
                });

                document.querySelector('[data-export-audit-report]')?.addEventListener('click', () => {
                    const url = buildExportUrl(window.__auditRoutes?.report || '');
                    if (! url) return;
                    window.open(url, '_blank', 'noopener');
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
