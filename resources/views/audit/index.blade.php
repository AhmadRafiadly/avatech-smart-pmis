@php
    $events = [
        ['id' => 1,  'date' => 'Hari Ini', 'time' => '09:42', 'actor' => 'Joshua Raphael',         'initials' => 'JR', 'tag' => 'LOGIN',              'filter' => 'login',    'module' => 'Auth',              'text' => '<strong>Joshua Raphael</strong> login ke Executive Monitor'],
        ['id' => 2,  'date' => 'Hari Ini', 'time' => '09:15', 'actor' => 'AI Sekretaris',          'initials' => 'AI', 'tag' => 'RISK ALERT',         'filter' => 'risk',     'module' => 'Executive Monitor', 'text' => 'AI Sekretaris men-generate risk alert untuk <strong>Gamma API Gateway</strong> (QA backlog meningkat)'],
        ['id' => 3,  'date' => 'Hari Ini', 'time' => '08:50', 'actor' => 'AI Sekretaris',          'initials' => 'AI', 'tag' => 'WBS GENERATED',      'filter' => 'ai',       'module' => 'Project Master',    'text' => 'AI menghasilkan <strong>6 modul</strong> untuk proyek Alpha CRM'],
        ['id' => 4,  'date' => 'Hari Ini', 'time' => '08:30', 'actor' => 'Joshua Raphael',         'initials' => 'JR', 'tag' => 'LAPORAN EKSPOR',     'filter' => 'laporan',  'module' => 'Executive Monitor', 'text' => 'Ekspor laporan <strong>Project Health</strong> (CSV, 8 proyek aktif)'],

        ['id' => 5,  'date' => 'Kemarin',  'time' => '16:42', 'actor' => 'Adly',                    'initials' => 'AR', 'tag' => 'KLIEN BARU',         'filter' => 'klien',    'module' => 'Client Directory',  'text' => 'Adly menambahkan klien baru: <strong>PT Toko Cerdas Retail</strong>'],
        ['id' => 6,  'date' => 'Kemarin',  'time' => '15:30', 'actor' => 'AI Sekretaris',          'initials' => 'AI', 'tag' => 'WA OUTBOUND',        'filter' => 'ai',       'module' => 'AI Planning',       'text' => 'AI Sekretaris drafting WhatsApp follow-up ke <strong>PT Maju Jaya</strong> (tone formal)'],
        ['id' => 7,  'date' => 'Kemarin',  'time' => '14:10', 'actor' => 'Irwan Kurniawan',        'initials' => 'IK', 'tag' => 'PROYEK DIPERBARUI',  'filter' => 'proyek',   'module' => 'Project Master',    'text' => 'Irwan memperbarui status <strong>Gamma API Gateway</strong> menjadi Critical'],
        ['id' => 8,  'date' => 'Kemarin',  'time' => '11:20', 'actor' => 'Joshua Raphael',         'initials' => 'JR', 'tag' => 'SETTINGS DIBUKA',    'filter' => 'settings', 'module' => 'Settings',          'text' => 'Joshua membuka halaman <strong>Settings</strong>'],
        ['id' => 9,  'date' => 'Kemarin',  'time' => '10:05', 'actor' => 'Yuda Prayoga',           'initials' => 'YP', 'tag' => 'AKSES DITINJAU',     'filter' => 'akses',    'module' => 'Team Management',   'text' => 'Yuda meninjau role assignment untuk <strong>Ferry Achmad</strong>'],

        ['id' => 10, 'date' => '18 Mei',   'time' => '17:30', 'actor' => 'AI Sekretaris',          'initials' => 'AI', 'tag' => 'WBS GENERATED',      'filter' => 'ai',       'module' => 'Project Master',    'text' => 'AI menghasilkan <strong>5 modul</strong> untuk Beta Portal'],
        ['id' => 11, 'date' => '18 Mei',   'time' => '14:55', 'actor' => 'Ferry Achmad',           'initials' => 'FA', 'tag' => 'PROYEK DIPERBARUI',  'filter' => 'proyek',   'module' => 'Project Master',    'text' => 'Ferry memperbarui progress <strong>Delta Logistics</strong> ke 38%'],
        ['id' => 12, 'date' => '18 Mei',   'time' => '09:10', 'actor' => 'Adly',                    'initials' => 'AR', 'tag' => 'LOGIN',              'filter' => 'login',    'module' => 'Auth',              'text' => '<strong>Ahmad Rafiadly Arlisyah</strong> login ke Project Master'],

        ['id' => 13, 'date' => '17 Mei',   'time' => '16:00', 'actor' => 'Joshua Raphael',         'initials' => 'JR', 'tag' => 'AKSES DITINJAU',     'filter' => 'akses',    'module' => 'Team Management',   'text' => 'Joshua menyetujui perubahan akses <strong>Yuda Prayoga</strong> ke Project Master'],
        ['id' => 14, 'date' => '17 Mei',   'time' => '11:32', 'actor' => 'Adly',                    'initials' => 'AR', 'tag' => 'KLIEN BARU',         'filter' => 'klien',    'module' => 'Client Directory',  'text' => 'Adly menambahkan klien baru: <strong>CV Nirwana Ventures</strong>'],
        ['id' => 15, 'date' => '17 Mei',   'time' => '09:48', 'actor' => 'Genta',                   'initials' => 'GT', 'tag' => 'SETTINGS DIBUKA',    'filter' => 'settings', 'module' => 'Settings',          'text' => 'Genta membuka halaman <strong>Settings</strong>'],

        ['id' => 16, 'date' => '16 Mei',   'time' => '18:20', 'actor' => 'AI Sekretaris',          'initials' => 'AI', 'tag' => 'MOM FIXED',          'filter' => 'ai',       'module' => 'AI Planning',       'text' => 'AI MoM Fixer merapikan MoM rapat <strong>16 Mei 2026</strong> (typo & action items)'],
        ['id' => 17, 'date' => '16 Mei',   'time' => '17:55', 'actor' => 'Joshua Raphael',         'initials' => 'JR', 'tag' => 'LOGOUT',             'filter' => 'login',    'module' => 'Auth',              'text' => '<strong>Joshua Raphael</strong> logout dari Smart-PMIS'],
    ];

    $filters = [
        ['id' => 'all',      'label' => 'Semua'],
        ['id' => 'login',    'label' => 'Login'],
        ['id' => 'proyek',   'label' => 'Proyek'],
        ['id' => 'klien',    'label' => 'Klien'],
        ['id' => 'akses',    'label' => 'Akses'],
        ['id' => 'ai',       'label' => 'AI Activity'],
        ['id' => 'laporan',  'label' => 'Laporan'],
        ['id' => 'settings', 'label' => 'Settings'],
        ['id' => 'risk',     'label' => 'Risk'],
    ];

    $col = collect($events);

    $filterCounts = [
        'all'      => count($events),
        'login'    => $col->where('filter', 'login')->count(),
        'proyek'   => $col->where('filter', 'proyek')->count(),
        'klien'    => $col->where('filter', 'klien')->count(),
        'akses'    => $col->where('filter', 'akses')->count(),
        'ai'       => $col->where('filter', 'ai')->count(),
        'laporan'  => $col->where('filter', 'laporan')->count(),
        'settings' => $col->where('filter', 'settings')->count(),
        'risk'     => $col->where('filter', 'risk')->count(),
    ];

    $dataChanged = $filterCounts['proyek'] + $filterCounts['klien'] + $filterCounts['akses'];

    $stats = [
        ['label' => 'Total Entri',     'value' => count($events),         'note' => '30 hari terakhir',  'color' => '#7C3AED'],
        ['label' => 'AI Activity',     'value' => $filterCounts['ai'],    'note' => 'saran & generasi AI', 'color' => '#A855F7'],
        ['label' => 'User Logins',     'value' => $filterCounts['login'], 'note' => 'termasuk logout',   'color' => '#3B82F6'],
        ['label' => 'Data Changed',    'value' => $dataChanged,            'note' => 'Proyek + Klien + Akses', 'color' => '#10B981'],
        ['label' => 'Alert Triggered', 'value' => $filterCounts['risk'],  'note' => 'Risk Detection AI', 'color' => '#EF4444'],
    ];

    $daysForDate = [
        'Hari Ini' => 0,
        'Kemarin'  => 1,
        '18 Mei'   => 3,
        '17 Mei'   => 4,
        '16 Mei'   => 5,
    ];

    $tagStyle = [
        'LOGIN'             => ['bg' => '#F1F5F9', 'color' => '#334155'],
        'LOGOUT'            => ['bg' => '#F1F5F9', 'color' => '#334155'],
        'PROYEK DIPERBARUI' => ['bg' => '#EDE9FE', 'color' => '#5B21B6'],
        'KLIEN BARU'        => ['bg' => '#DCFCE7', 'color' => '#166534'],
        'AKSES DITINJAU'    => ['bg' => '#FAE8FF', 'color' => '#86198F'],
        'WBS GENERATED'     => ['bg' => '#FCE7F3', 'color' => '#9D174D'],
        'WA OUTBOUND'       => ['bg' => '#FCE7F3', 'color' => '#9D174D'],
        'MOM FIXED'         => ['bg' => '#FCE7F3', 'color' => '#9D174D'],
        'LAPORAN EKSPOR'    => ['bg' => '#DBEAFE', 'color' => '#1E40AF'],
        'SETTINGS DIBUKA'   => ['bg' => '#FED7AA', 'color' => '#9A3412'],
        'RISK ALERT'        => ['bg' => '#FEE2E2', 'color' => '#991B1B'],
    ];

    $actorColor = [
        'JR' => '#9333EA',
        'AR' => '#8B5CF6',
        'YP' => '#EC4899',
        'IK' => '#10B981',
        'FA' => '#3B82F6',
        'GT' => '#F97316',
        'AI' => '#7C3AED',
    ];

    $groups = $col->groupBy('date');
@endphp

<x-layouts.authenticated :title="$title">

    <section class="flex items-end justify-between mb-8 gap-6 flex-wrap">
        <div>
            <h1 class="text-[44px] leading-[1.05] font-bold tracking-tight text-[#1E1B4B]">
                Audit
                <span class="bg-clip-text text-transparent bg-gradient-to-r from-[#7C3AED] to-[#A855F7]">Trail</span>
            </h1>
            <p class="mt-3 text-[15px] text-slate-500 max-w-2xl">
                Riwayat lengkap semua aktivitas di Smart-PMIS &mdash; siapa melakukan apa, kapan, dan dari modul mana.
            </p>
        </div>
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
    </section>

    <section class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-8">
        @foreach ($stats as $s)
            <div class="bg-white rounded-2xl border border-violet-100 shadow-[0_2px_8px_rgba(124,58,237,0.08)] p-5">
                <div class="flex items-center gap-2 mb-2">
                    <span class="w-2 h-2 rounded-full" style="background: {{ $s['color'] }};"></span>
                    <span class="text-[11px] font-bold tracking-wider uppercase text-slate-500">{{ $s['label'] }}</span>
                </div>
                <div class="text-[28px] font-bold text-[#1E1B4B] tabular-nums">{{ $s['value'] }}</div>
                <div class="text-[11.5px] text-slate-400 mt-0.5">{{ $s['note'] }}</div>
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

        <div class="ml-auto flex items-center gap-3 flex-wrap">
            <div class="relative">
                <select data-audit-actor class="appearance-none h-10 pl-4 pr-9 rounded-xl border border-violet-100 bg-white text-[13px] text-slate-600 font-medium focus:outline-none focus:ring-2 focus:ring-violet-300 cursor-pointer">
                    <option value="all">Semua Anggota</option>
                    <option value="Joshua Raphael">Joshua Raphael</option>
                    <option value="Adly">Ahmad Rafiadly Arlisyah</option>
                    <option value="Yuda Prayoga">Yuda Prayoga</option>
                    <option value="Irwan Kurniawan">Irwan Kurniawan</option>
                    <option value="Ferry Achmad">Ferry Achmad</option>
                    <option value="Genta">Genta</option>
                    <option value="AI Sekretaris">AI Sekretaris</option>
                </select>
                <x-heroicon-o-chevron-down class="w-4 h-4 text-slate-400 absolute right-3 top-1/2 -translate-y-1/2 pointer-events-none" />
            </div>
            <div class="relative">
                <select data-audit-range class="appearance-none h-10 pl-4 pr-9 rounded-xl border border-violet-100 bg-white text-[13px] text-slate-600 font-medium focus:outline-none focus:ring-2 focus:ring-violet-300 cursor-pointer">
                    <option value="7">7 hari terakhir</option>
                    <option value="30" selected>30 hari terakhir</option>
                    <option value="90">90 hari terakhir</option>
                    <option value="all">Semua waktu</option>
                </select>
                <x-heroicon-o-chevron-down class="w-4 h-4 text-slate-400 absolute right-3 top-1/2 -translate-y-1/2 pointer-events-none" />
            </div>
        </div>
    </section>

    <section class="space-y-8" data-audit-list>
        @foreach ($groups as $date => $items)
            <div data-group data-group-days="{{ $daysForDate[$date] ?? 99 }}">
                <div class="flex items-center gap-3 mb-3">
                    <div class="text-[11px] font-bold tracking-[0.15em] uppercase text-violet-500">{{ $date }}</div>
                    <div class="h-px flex-1 bg-violet-100"></div>
                    <div class="text-[11.5px] text-slate-400 tabular-nums" data-group-count>{{ count($items) }} entri</div>
                </div>

                <div class="bg-white rounded-2xl border border-violet-100 shadow-[0_2px_8px_rgba(124,58,237,0.08)] overflow-hidden">
                    @foreach ($items as $a)
                        <div
                            data-entry="{{ $a['filter'] }}"
                            data-actor="{{ $a['actor'] }}"
                            data-days="{{ $daysForDate[$date] ?? 99 }}"
                            class="flex items-start gap-4 px-6 py-4 border-b border-violet-50 last:border-0 hover:bg-violet-50/40 transition"
                        >
                            <div class="w-10 h-10 rounded-full flex items-center justify-center font-bold text-[11.5px] text-white flex-shrink-0" style="background: {{ $actorColor[$a['initials']] ?? '#7C3AED' }};">
                                {{ $a['initials'] }}
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2 flex-wrap mb-1.5">
                                    <span class="inline-flex items-center text-[10.5px] font-bold tracking-wide uppercase px-2.5 py-1 rounded-md" style="background: {{ $tagStyle[$a['tag']]['bg'] }}; color: {{ $tagStyle[$a['tag']]['color'] }};">
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
        @endforeach

        <div data-empty class="hidden bg-white rounded-2xl border border-dashed border-violet-200 p-12 text-center">
            <div class="w-14 h-14 mx-auto rounded-2xl bg-violet-50 text-violet-400 flex items-center justify-center mb-3">
                <x-heroicon-o-inbox class="w-6 h-6" />
            </div>
            <h3 class="text-[15px] font-semibold text-[#1E1B4B]">Tidak ada aktivitas</h3>
            <p class="text-[13px] text-slate-500 mt-1">Coba ubah filter atau rentang waktu.</p>
        </div>
    </section>

    <script>
        window.__auditData = @json($events);
    </script>

    <script>
        (function () {
            const wire = () => {
                const chips = document.querySelectorAll('.js-filter-chip');
                const groups = document.querySelectorAll('[data-group]');
                const empty = document.querySelector('[data-empty]');

                const actorSel  = document.querySelector('[data-audit-actor]');
                const rangeSel  = document.querySelector('[data-audit-range]');
                let activeChip  = 'all';

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
                };

                chips.forEach(c => {
                    c.addEventListener('click', () => { activeChip = c.dataset.filter; applyFilter(); });
                });
                actorSel?.addEventListener('change', applyFilter);
                rangeSel?.addEventListener('change', applyFilter);
                applyFilter();

                /* === Exports === */
                const stripTags = (html) => String(html || '').replace(/<[^>]+>/g, '');
                document.querySelector('[data-export-audit-csv]')?.addEventListener('click', () => {
                    const csvCell = (v) => '"' + String(v ?? '').replace(/"/g, '""') + '"';
                    const rows = [['ID','Date','Time','Actor','Tag','Module','Text']];
                    (window.__auditData || []).forEach(e => {
                        rows.push([e.id, e.date, e.time, e.actor, e.tag, e.module, stripTags(e.text)]);
                    });
                    const csv = rows.map(r => r.map(csvCell).join(',')).join('\r\n');
                    window.downloadFile && window.downloadFile('audit-' + new Date().toISOString().slice(0,10) + '.csv', csv, 'text/csv;charset=utf-8');
                    window.toast && window.toast('CSV audit diunduh (' + (rows.length - 1) + ' entri).');
                });

                document.querySelector('[data-export-audit-report]')?.addEventListener('click', () => {
                    const lines = [
                        'AVATECH SMART-PMIS — LAPORAN AUDIT',
                        'Diekspor: ' + new Date().toLocaleString('id-ID'),
                        ''.padEnd(60, '='),
                        '',
                    ];
                    const data = window.__auditData || [];
                    const byDate = {};
                    data.forEach(e => { (byDate[e.date] = byDate[e.date] || []).push(e); });
                    Object.keys(byDate).forEach(date => {
                        lines.push('[' + date.toUpperCase() + ']  (' + byDate[date].length + ' entri)');
                        byDate[date].forEach(e => {
                            lines.push('  ' + e.time + '  ' + e.tag.padEnd(20) + '  ' + e.actor.padEnd(28) + '  ' + stripTags(e.text));
                        });
                        lines.push('');
                    });
                    lines.push(''.padEnd(60, '='));
                    lines.push('Total: ' + data.length + ' entri');
                    window.downloadFile && window.downloadFile('laporan-audit-' + new Date().toISOString().slice(0,10) + '.txt', lines.join('\n'), 'text/plain;charset=utf-8');
                    window.toast && window.toast('Laporan audit diunduh.');
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
