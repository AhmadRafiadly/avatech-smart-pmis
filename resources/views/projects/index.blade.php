@php
    $projects ??= [];
    $clients ??= collect();
    $errors ??= new \Illuminate\Support\ViewErrorBag;
    $archiveScope ??= 'active';

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
        ['label' => 'Selesai',   'value' => $col->where('phase_key', 'done')->count(),     'color' => '#94A3B8'],
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

    $archiveFilters = [
        'active'   => 'Active',
        'archived' => 'Archived',
        'all'      => 'All',
    ];
    $editProjectId = old('_form') === 'edit' ? old('_project_id') : null;
    $editAction = $editProjectId ? route('projects.update', $editProjectId) : '#';

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
                Kelola semua proyek Avatech dari satu tempat. Smart-PMIS membantu membaca kesiapan WBS, assignment, dan QC.
            </p>
        </div>
        <button
            type="button"
            data-create-trigger="project"
            class="inline-flex items-center gap-2 h-12 px-5 rounded-xl bg-gradient-to-r from-[#7C3AED] via-[#A855F7] to-[#C084FC] text-white font-semibold text-[14px] shadow-[0_4px_14px_rgba(124,58,237,0.35)] hover:shadow-[0_6px_20px_rgba(124,58,237,0.45)] hover:-translate-y-0.5 transition-all cursor-pointer"
        >
            <x-heroicon-o-plus class="w-5 h-5" />
            Buat Proyek Baru
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
            @foreach ($archiveFilters as $scope => $label)
                <a
                    href="{{ route('projects.index', ['archive' => $scope]) }}"
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
    <p class="-mt-3 mb-6 text-[12px] text-slate-400">
        Due date merupakan target saat ini dan dapat disesuaikan jika ada revisi atau perubahan scope. Progress dihitung dari task selesai/estimasi jam.
    </p>

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
                    @foreach (($p['smart_badges'] ?? []) as $badge)
                        <span class="text-[11px] font-semibold px-2.5 py-1 rounded-md bg-fuchsia-50 text-fuchsia-700 inline-flex items-center gap-1">
                            <x-dynamic-component :component="'heroicon-o-' . $badge['icon']" class="w-3 h-3" />
                            {{ $badge['label'] }}
                        </span>
                    @endforeach
                    @if ($p['archived'])
                        <span class="text-[11px] font-semibold px-2.5 py-1 rounded-md bg-slate-100 text-slate-600 inline-flex items-center gap-1">
                            <x-heroicon-o-archive-box class="w-3 h-3" />
                            Archived
                        </span>
                    @endif
                </div>

                <p class="text-[13px] text-slate-500 leading-relaxed mb-5 line-clamp-2">{{ $p['desc'] }}</p>

                <div class="mt-auto">
                    <div class="flex items-center justify-between mb-1.5">
                        <span class="text-[11px] font-semibold uppercase tracking-wider text-slate-500">Progress</span>
                        <span class="text-[13px] font-bold text-[#1E1B4B] tabular-nums" title="Progress dihitung dari task selesai/estimasi jam.">{{ $p['progress'] }}%</span>
                    </div>
                    <div class="h-2 rounded-full bg-[#F3E8FF] overflow-hidden">
                        <div class="h-full rounded-full {{ $statusDot[$p['status']] }}" style="width: {{ $p['progress'] }}%"></div>
                    </div>
                    <p class="mt-1 text-[10.5px] text-slate-400">Progress dihitung dari task selesai/estimasi jam.</p>

                    <div class="mt-4 flex items-center gap-2" onclick="event.stopPropagation()">
                        <button
                            type="button"
                            data-edit-project
                            data-action="{{ route('projects.update', $p['id']) }}"
                            data-project-id="{{ $p['id'] }}"
                            data-code="{{ $p['code'] }}"
                            data-name="{{ $p['name'] }}"
                            data-client-id="{{ $p['client_id'] }}"
                            data-description="{{ $p['desc'] }}"
                            data-due-at="{{ $p['due_at'] }}"
                            data-requires-design="{{ $p['requires_design'] ? '1' : '0' }}"
                            class="inline-flex items-center gap-1.5 h-8 px-3 rounded-lg border border-violet-100 bg-white text-[12px] font-semibold text-slate-600 hover:border-violet-300 hover:text-violet-700 transition cursor-pointer"
                        >
                            <x-heroicon-o-pencil-square class="w-3.5 h-3.5" />
                            Edit
                        </button>
                        @if (! $p['archived'])
                            <form method="POST" action="{{ route('projects.archive', $p['id']) }}" onsubmit="return confirm('Arsipkan proyek ini?');">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="inline-flex items-center gap-1.5 h-8 px-3 rounded-lg border border-amber-100 bg-amber-50 text-[12px] font-semibold text-amber-700 hover:bg-amber-100 transition cursor-pointer">
                                    <x-heroicon-o-archive-box class="w-3.5 h-3.5" />
                                    Archive
                                </button>
                            </form>
                        @else
                            <form method="POST" action="{{ route('projects.restore', $p['id']) }}">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="inline-flex items-center gap-1.5 h-8 px-3 rounded-lg border border-emerald-100 bg-emerald-50 text-[12px] font-semibold text-emerald-700 hover:bg-emerald-100 transition cursor-pointer">
                                    <x-heroicon-o-arrow-path class="w-3.5 h-3.5" />
                                    Restore
                                </button>
                            </form>
                        @endif
                    </div>

                    <div class="mt-4 pt-4 border-t border-violet-50 flex items-center justify-between gap-3">
                        <div class="flex -space-x-2">
                            @foreach ($p['team'] as $m)
                                <div class="w-8 h-8 rounded-full text-white border-2 border-white flex items-center justify-center text-[10.5px] font-bold" title="{{ $m['name'] }}" style="background: {{ $m['color'] }};">{{ $m['initials'] }}</div>
                            @endforeach
                            @if ($p['team_more'] > 0)
                                @php $hiddenNames = $p['team_more_names'] ?? []; @endphp
                                <div
                                    class="w-8 h-8 rounded-full bg-slate-100 text-slate-500 border-2 border-white flex items-center justify-center text-[10px] font-bold cursor-help"
                                    @if (! empty($hiddenNames)) title="Anggota lainnya: {{ implode(', ', $hiddenNames) }}" @endif
                                >+{{ $p['team_more'] }}</div>
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
                        <th class="px-4 py-4 w-[220px]">Progress <span class="normal-case tracking-normal font-medium text-slate-300">(task-based)</span></th>
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
                                        <div class="w-7 h-7 rounded-full text-white border-2 border-white flex items-center justify-center text-[10px] font-bold" title="{{ $m['name'] }}" style="background: {{ $m['color'] }};">{{ $m['initials'] }}</div>
                                    @endforeach
                                    @if ($p['team_more'] > 0)
                                        @php $hiddenNamesList = $p['team_more_names'] ?? []; @endphp
                                        <div
                                            class="w-7 h-7 rounded-full bg-slate-100 text-slate-500 border-2 border-white flex items-center justify-center text-[10px] font-bold cursor-help"
                                            @if (! empty($hiddenNamesList)) title="Anggota lainnya: {{ implode(', ', $hiddenNamesList) }}" @endif
                                        >+{{ $p['team_more'] }}</div>
                                    @endif
                                </div>
                            </td>
                            <td class="px-4 py-4 w-[220px]">
                                <div class="flex items-center gap-2">
                                    <div class="flex-1 h-2 rounded-full bg-[#F3E8FF] overflow-hidden">
                                        <div class="h-full rounded-full {{ $statusDot[$p['status']] }}" style="width: {{ $p['progress'] }}%"></div>
                                    </div>
                                    <span class="text-[12px] font-bold text-[#1E1B4B] w-9 text-right tabular-nums" title="Progress dihitung dari task selesai/estimasi jam.">{{ $p['progress'] }}%</span>
                                </div>
                            </td>
                            <td class="px-4 py-4">
                                <span class="inline-flex items-center gap-1.5 text-[11px] font-semibold rounded-full px-2.5 py-1 {{ $statusPill[$p['status']] }}">
                                    <span class="w-1.5 h-1.5 rounded-full {{ $statusDot[$p['status']] }}"></span>
                                    {{ $p['status_label'] }}
                                </span>
                            </td>
                            <td class="px-7 py-4 text-right">
                                <div class="inline-flex items-center gap-2" onclick="event.stopPropagation()">
                                    <button
                                        type="button"
                                        data-edit-project
                                        data-action="{{ route('projects.update', $p['id']) }}"
                                        data-project-id="{{ $p['id'] }}"
                                        data-code="{{ $p['code'] }}"
                                        data-name="{{ $p['name'] }}"
                                        data-client-id="{{ $p['client_id'] }}"
                                        data-description="{{ $p['desc'] }}"
                                        data-due-at="{{ $p['due_at'] }}"
                                        data-requires-design="{{ $p['requires_design'] ? '1' : '0' }}"
                                        class="inline-flex items-center justify-center w-8 h-8 rounded-lg border border-violet-100 bg-white text-slate-500 hover:border-violet-300 hover:text-violet-700 transition cursor-pointer"
                                        aria-label="Edit proyek {{ $p['name'] }}"
                                    >
                                        <x-heroicon-o-pencil-square class="w-4 h-4" />
                                    </button>
                                    @if (! $p['archived'])
                                        <form method="POST" action="{{ route('projects.archive', $p['id']) }}" onsubmit="return confirm('Arsipkan proyek ini?');">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="inline-flex items-center justify-center w-8 h-8 rounded-lg border border-amber-100 bg-amber-50 text-amber-700 hover:bg-amber-100 transition cursor-pointer" aria-label="Arsipkan proyek {{ $p['name'] }}">
                                                <x-heroicon-o-archive-box class="w-4 h-4" />
                                            </button>
                                        </form>
                                    @else
                                        <form method="POST" action="{{ route('projects.restore', $p['id']) }}">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="inline-flex items-center justify-center w-8 h-8 rounded-lg border border-emerald-100 bg-emerald-50 text-emerald-700 hover:bg-emerald-100 transition cursor-pointer" aria-label="Pulihkan proyek {{ $p['name'] }}">
                                                <x-heroicon-o-arrow-path class="w-4 h-4" />
                                            </button>
                                        </form>
                                    @endif
                                </div>
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

    {{-- ===== Edit Project Modal ===== --}}
    <div data-edit-modal="project" data-edit-has-errors="{{ old('_form') === 'edit' && $errors->any() ? '1' : '0' }}" class="hidden fixed inset-0 z-50 items-center justify-center p-4 sm:p-6" role="dialog" aria-modal="true">
        <div data-edit-overlay class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm"></div>
        <form method="POST" action="{{ $editAction }}" data-edit-panel class="relative bg-white rounded-3xl shadow-[0_24px_64px_rgba(124,58,237,0.18)] w-full max-w-md max-h-[90vh] flex flex-col overflow-hidden border border-violet-100">
            @csrf
            @method('PUT')
            <input type="hidden" name="_form" value="edit">
            <input type="hidden" name="_project_id" value="{{ old('_project_id') }}">
            <div class="px-6 py-4 border-b border-violet-100 flex items-center justify-between">
                <h3 class="text-[16px] font-bold text-[#1E1B4B]">Edit Proyek</h3>
                <button type="button" data-edit-close aria-label="Tutup" class="w-9 h-9 rounded-full hover:bg-violet-50 flex items-center justify-center text-slate-500 hover:text-rose-500 transition cursor-pointer">
                    <x-heroicon-o-x-mark class="w-5 h-5" />
                </button>
            </div>
            <div class="px-6 py-5 space-y-3 overflow-y-auto">
                <div>
                    <label class="block text-[11px] font-bold tracking-wider uppercase text-slate-500 mb-1.5">Kode</label>
                    <input name="code" value="{{ old('_form') === 'edit' ? old('code') : '' }}" maxlength="4" class="w-full h-10 rounded-lg border border-violet-100 px-3 text-[13px] uppercase focus:outline-none focus:ring-2 focus:ring-violet-300" placeholder="AC" />
                    @if (old('_form') === 'edit')
                        @error('code') <p class="mt-1.5 text-[12px] font-semibold text-rose-600">{{ $message }}</p> @enderror
                    @endif
                </div>
                <div>
                    <label class="block text-[11px] font-bold tracking-wider uppercase text-slate-500 mb-1.5">Nama Proyek</label>
                    <input name="name" value="{{ old('_form') === 'edit' ? old('name') : '' }}" class="w-full h-10 rounded-lg border border-violet-100 px-3 text-[13px] focus:outline-none focus:ring-2 focus:ring-violet-300" placeholder="Nama proyek..." />
                    @if (old('_form') === 'edit')
                        @error('name') <p class="mt-1.5 text-[12px] font-semibold text-rose-600">{{ $message }}</p> @enderror
                    @endif
                </div>
                <div>
                    <label class="block text-[11px] font-bold tracking-wider uppercase text-slate-500 mb-1.5">Klien</label>
                    <select name="client_id" class="w-full h-10 rounded-lg border border-violet-100 px-3 text-[13px] bg-white focus:outline-none focus:ring-2 focus:ring-violet-300 cursor-pointer">
                        <option value="">Pilih klien...</option>
                        @foreach ($clients as $client)
                            <option value="{{ $client->id }}" @selected(old('_form') === 'edit' && (string) old('client_id') === (string) $client->id)>{{ $client->name }}</option>
                        @endforeach
                    </select>
                    @if (old('_form') === 'edit')
                        @error('client_id') <p class="mt-1.5 text-[12px] font-semibold text-rose-600">{{ $message }}</p> @enderror
                    @endif
                </div>
                <div>
                    <label class="block text-[11px] font-bold tracking-wider uppercase text-slate-500 mb-1.5">Template Proyek</label>
                    <select data-project-template class="w-full h-10 rounded-lg border border-violet-100 px-3 text-[13px] bg-white focus:outline-none focus:ring-2 focus:ring-violet-300 cursor-pointer">
                        <option value="custom">Custom</option>
                        <option value="web">Web Development</option>
                        <option value="wordpress">WordPress Website</option>
                        <option value="mobile">Mobile App</option>
                        <option value="internal">Internal System</option>
                        <option value="qa">QA/UAT Project</option>
                    </select>
                    <p class="mt-1 text-[11px] text-slate-400">Opsional, hanya membantu mengisi deskripsi awal.</p>
                </div>
                <div>
                    <label class="block text-[11px] font-bold tracking-wider uppercase text-slate-500 mb-1.5">Deskripsi</label>
                    <textarea name="description" rows="3" class="w-full rounded-lg border border-violet-100 px-3 py-2 text-[13px] focus:outline-none focus:ring-2 focus:ring-violet-300 resize-y" placeholder="Ringkasan singkat proyek...">{{ old('_form') === 'edit' ? old('description') : '' }}</textarea>
                    @if (old('_form') === 'edit')
                        @error('description') <p class="mt-1.5 text-[12px] font-semibold text-rose-600">{{ $message }}</p> @enderror
                    @endif
                </div>
                <label class="flex items-start gap-3 rounded-xl border border-violet-100 bg-violet-50/40 px-3 py-3">
                    <input type="hidden" name="requires_design" value="0">
                    <input name="requires_design" type="checkbox" value="1" data-requires-design-checkbox @checked(old('_form') === 'edit' && old('requires_design')) class="mt-0.5 w-4 h-4 rounded border-violet-300 text-violet-600 focus:ring-violet-300 cursor-pointer">
                    <span>
                        <span class="block text-[12.5px] font-bold text-[#1E1B4B]">Membutuhkan mockup UI/UX?</span>
                        <span class="block mt-1 text-[11.5px] text-slate-500 leading-relaxed">Jika aktif, sistem akan menyiapkan task desain terlebih dahulu sebelum masuk ke Development.</span>
                    </span>
                </label>
                <div>
                    <label class="block text-[11px] font-bold tracking-wider uppercase text-slate-500 mb-1.5">Due Date</label>
                    <input name="due_at" type="date" value="{{ old('_form') === 'edit' ? old('due_at') : '' }}" class="w-full h-10 rounded-lg border border-violet-100 px-3 text-[13px] focus:outline-none focus:ring-2 focus:ring-violet-300" />
                    <p class="mt-1 text-[11px] text-slate-400">Target saat ini; bisa disesuaikan jika ada revisi atau perubahan scope.</p>
                    @if (old('_form') === 'edit')
                        @error('due_at') <p class="mt-1.5 text-[12px] font-semibold text-rose-600">{{ $message }}</p> @enderror
                    @endif
                </div>
            </div>
            <div class="px-6 py-3 border-t border-violet-100 bg-violet-50/30 flex items-center justify-end gap-2">
                <button type="button" data-edit-close class="h-9 px-4 rounded-lg text-[12.5px] font-semibold text-slate-500 hover:text-slate-700 transition cursor-pointer">Batal</button>
                <button type="submit" class="inline-flex items-center gap-2 h-9 px-4 rounded-lg bg-gradient-to-r from-[#7C3AED] via-[#A855F7] to-[#C084FC] text-white font-semibold text-[12.5px] shadow-[0_2px_8px_rgba(124,58,237,0.2)] hover:scale-[1.02] transition cursor-pointer">
                    <x-heroicon-o-bookmark-square class="w-4 h-4" />
                    Simpan
                </button>
            </div>
        </form>
    </div>

    {{-- ===== Create Project Modal ===== --}}
    <div data-create-modal="project" data-create-has-errors="{{ old('_form') === 'create' && $errors->any() ? '1' : '0' }}" class="hidden fixed inset-0 z-50 items-center justify-center p-4 sm:p-6" role="dialog" aria-modal="true">
        <div data-create-overlay class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm"></div>
        <form method="POST" action="{{ route('projects.store') }}" data-create-panel class="relative bg-white rounded-3xl shadow-[0_24px_64px_rgba(124,58,237,0.18)] w-full max-w-md max-h-[90vh] flex flex-col overflow-hidden border border-violet-100">
            @csrf
            <input type="hidden" name="_form" value="create">
            <div class="px-6 py-4 border-b border-violet-100 flex items-center justify-between">
                <h3 class="text-[16px] font-bold text-[#1E1B4B]">Buat Proyek Baru</h3>
                <button type="button" data-create-close aria-label="Tutup" class="w-9 h-9 rounded-full hover:bg-violet-50 flex items-center justify-center text-slate-500 hover:text-rose-500 transition cursor-pointer">
                    <x-heroicon-o-x-mark class="w-5 h-5" />
                </button>
            </div>
            <div class="px-6 py-5 space-y-3 overflow-y-auto">
                <div class="flex items-start gap-2.5 p-3 rounded-lg bg-violet-50/70 border border-violet-100 text-[12px] text-slate-600">
                    <x-heroicon-o-sparkles class="w-4 h-4 text-violet-600 flex-shrink-0 mt-0.5" />
                    <span>Project baru dimulai dari <strong class="text-[#1E1B4B]">Gathering & Planning</strong> dengan status <strong class="text-[#1E1B4B]">On Track</strong>.</span>
                </div>
                <div>
                    <label class="block text-[11px] font-bold tracking-wider uppercase text-slate-500 mb-1.5">Kode</label>
                    <input name="code" value="{{ old('code') }}" maxlength="4" class="w-full h-10 rounded-lg border border-violet-100 px-3 text-[13px] uppercase focus:outline-none focus:ring-2 focus:ring-violet-300" placeholder="AC" />
                    @error('code')
                        <p class="mt-1.5 text-[12px] font-semibold text-rose-600">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="block text-[11px] font-bold tracking-wider uppercase text-slate-500 mb-1.5">Nama Proyek</label>
                    <input name="name" value="{{ old('name') }}" class="w-full h-10 rounded-lg border border-violet-100 px-3 text-[13px] focus:outline-none focus:ring-2 focus:ring-violet-300" placeholder="Nama proyek..." />
                    @error('name')
                        <p class="mt-1.5 text-[12px] font-semibold text-rose-600">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="block text-[11px] font-bold tracking-wider uppercase text-slate-500 mb-1.5">Klien</label>
                    <select name="client_id" class="w-full h-10 rounded-lg border border-violet-100 px-3 text-[13px] bg-white focus:outline-none focus:ring-2 focus:ring-violet-300 cursor-pointer">
                        <option value="">Pilih klien...</option>
                        @foreach ($clients as $client)
                            <option value="{{ $client->id }}" @selected((string) old('client_id') === (string) $client->id)>{{ $client->name }}</option>
                        @endforeach
                    </select>
                    @error('client_id')
                        <p class="mt-1.5 text-[12px] font-semibold text-rose-600">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="block text-[11px] font-bold tracking-wider uppercase text-slate-500 mb-1.5">Template Proyek</label>
                    <select data-project-template class="w-full h-10 rounded-lg border border-violet-100 px-3 text-[13px] bg-white focus:outline-none focus:ring-2 focus:ring-violet-300 cursor-pointer">
                        <option value="custom">Custom</option>
                        <option value="web">Web Development</option>
                        <option value="wordpress">WordPress Website</option>
                        <option value="mobile">Mobile App</option>
                        <option value="internal">Internal System</option>
                        <option value="qa">QA/UAT Project</option>
                    </select>
                    <p class="mt-1 text-[11px] text-slate-400">Opsional, hanya membantu mengisi deskripsi awal.</p>
                </div>
                <div>
                    <label class="block text-[11px] font-bold tracking-wider uppercase text-slate-500 mb-1.5">Deskripsi</label>
                    <textarea name="description" rows="3" class="w-full rounded-lg border border-violet-100 px-3 py-2 text-[13px] focus:outline-none focus:ring-2 focus:ring-violet-300 resize-y" placeholder="Ringkasan singkat proyek...">{{ old('description') }}</textarea>
                    @error('description')
                        <p class="mt-1.5 text-[12px] font-semibold text-rose-600">{{ $message }}</p>
                    @enderror
                </div>
                <label class="flex items-start gap-3 rounded-xl border border-violet-100 bg-violet-50/40 px-3 py-3">
                    <input type="hidden" name="requires_design" value="0">
                    <input name="requires_design" type="checkbox" value="1" data-requires-design-checkbox @checked(old('requires_design')) class="mt-0.5 w-4 h-4 rounded border-violet-300 text-violet-600 focus:ring-violet-300 cursor-pointer">
                    <span>
                        <span class="block text-[12.5px] font-bold text-[#1E1B4B]">Membutuhkan mockup UI/UX?</span>
                        <span class="block mt-1 text-[11.5px] text-slate-500 leading-relaxed">Jika aktif, sistem akan menyiapkan task desain terlebih dahulu sebelum masuk ke Development.</span>
                    </span>
                </label>
                <div>
                    <label class="block text-[11px] font-bold tracking-wider uppercase text-slate-500 mb-1.5">Due Date</label>
                    <input name="due_at" type="date" value="{{ old('due_at') }}" class="w-full h-10 rounded-lg border border-violet-100 px-3 text-[13px] focus:outline-none focus:ring-2 focus:ring-violet-300" />
                    <p class="mt-1 text-[11px] text-slate-400">Target saat ini; bisa disesuaikan jika ada revisi atau perubahan scope.</p>
                    @error('due_at')
                        <p class="mt-1.5 text-[12px] font-semibold text-rose-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>
            <div class="px-6 py-3 border-t border-violet-100 bg-violet-50/30 flex items-center justify-end gap-2">
                <button type="button" data-create-close class="h-9 px-4 rounded-lg text-[12.5px] font-semibold text-slate-500 hover:text-slate-700 transition cursor-pointer">Batal</button>
                <button type="submit" data-create-save class="inline-flex items-center gap-2 h-9 px-4 rounded-lg bg-gradient-to-r from-[#7C3AED] via-[#A855F7] to-[#C084FC] text-white font-semibold text-[12.5px] shadow-[0_2px_8px_rgba(124,58,237,0.2)] hover:scale-[1.02] transition cursor-pointer">
                    <x-heroicon-o-bookmark-square class="w-4 h-4" />
                    Simpan
                </button>
            </div>
        </form>
    </div>

    <script>
        (function () {
            const wire = () => {
                const modal   = document.querySelector('[data-create-modal="project"]');
                const trigger = document.querySelector('[data-create-trigger="project"]');
                if (! modal || ! trigger) return;
                const overlay = modal.querySelector('[data-create-overlay]');
                const panel   = modal.querySelector('[data-create-panel]');

                const open = () => {
                    modal.classList.remove('hidden');
                    modal.classList.add('flex');
                    document.body.style.overflow = 'hidden';
                    modal.querySelector('[name="name"]')?.focus();
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

                if (modal.dataset.createHasErrors === '1') open();
                @if (session('status'))
                    window.toast && window.toast(@json(session('status')));
                @endif

                const editModal = document.querySelector('[data-edit-modal="project"]');
                const editForm = editModal?.querySelector('form');
                const editOverlay = editModal?.querySelector('[data-edit-overlay]');
                const editOpen = () => {
                    editModal.classList.remove('hidden');
                    editModal.classList.add('flex');
                    document.body.style.overflow = 'hidden';
                    editModal.querySelector('[name="name"]')?.focus();
                };
                const editClose = () => {
                    editModal.classList.add('hidden');
                    editModal.classList.remove('flex');
                    document.body.style.overflow = '';
                };

                document.querySelectorAll('[data-edit-project]').forEach(btn => {
                    btn.addEventListener('click', (event) => {
                        event.stopPropagation();
                        if (! editModal || ! editForm) return;
                        editForm.action = btn.dataset.action || '#';
                        editForm.querySelector('[name="_project_id"]').value = btn.dataset.projectId || '';
                        editForm.querySelector('[name="code"]').value = btn.dataset.code || '';
                        editForm.querySelector('[name="name"]').value = btn.dataset.name || '';
                        editForm.querySelector('[name="client_id"]').value = btn.dataset.clientId || '';
                        editForm.querySelector('[name="description"]').value = btn.dataset.description || '';
                        editForm.querySelector('[name="due_at"]').value = btn.dataset.dueAt || '';
                        const requiresDesign = editForm.querySelector('[data-requires-design-checkbox]');
                        if (requiresDesign) requiresDesign.checked = btn.dataset.requiresDesign === '1';
                        editForm.querySelector('[data-project-template]').value = 'custom';
                        editOpen();
                    });
                });
                editModal?.querySelector('[data-edit-panel]')?.addEventListener('click', (event) => event.stopPropagation());
                editOverlay?.addEventListener('click', editClose);
                editModal?.querySelectorAll('[data-edit-close]').forEach(btn => btn.addEventListener('click', editClose));
                document.addEventListener('keydown', (event) => {
                    if (event.key === 'Escape' && editModal && ! editModal.classList.contains('hidden')) editClose();
                });
                if (editModal?.dataset.editHasErrors === '1') editOpen();

                const templateDescriptions = {
                    web: 'Web development untuk kebutuhan bisnis klien, mencakup perencanaan halaman, implementasi frontend/backend, integrasi teknis, revisi desain/development, testing, dan handover.',
                    wordpress: 'WordPress website untuk company profile atau landing page, mencakup input konten, penyesuaian desain, revisi klien, konfigurasi CMS, testing, dan handover pengelolaan.',
                    mobile: 'Mobile app untuk kebutuhan pengguna klien, mencakup desain alur, implementasi fitur, integrasi API, revisi UI/UX, testing perangkat, dan persiapan rilis.',
                    internal: 'Internal system untuk operasional klien, mencakup dashboard, role access, workflow, reporting, testing, revisi scope, dan handover penggunaan.',
                    qa: 'QA/UAT project untuk validasi sistem, mencakup penyusunan test case, bug validation, regression testing, dokumentasi temuan, dan laporan hasil pengujian.'
                };
                const templateRequiresDesign = {
                    web: true,
                    wordpress: true,
                    mobile: true,
                    internal: true,
                    qa: false,
                    custom: false,
                };

                document.querySelectorAll('[data-project-template]').forEach(select => {
                    select.addEventListener('change', () => {
                        const form = select.closest('form');
                        const description = form?.querySelector('[name="description"]');
                        const requiresDesign = form?.querySelector('[data-requires-design-checkbox]');
                        const nextValue = templateDescriptions[select.value] || '';
                        if (requiresDesign) requiresDesign.checked = !! templateRequiresDesign[select.value];
                        if (! description || nextValue === '') return;
                        if (description.value.trim() !== '' && ! window.confirm('Ganti deskripsi dengan template proyek ini?')) {
                            select.value = 'custom';
                            if (requiresDesign) requiresDesign.checked = false;
                            return;
                        }
                        description.value = nextValue;
                        description.focus();
                    });
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
