@php
    $clients ??= [];
    $errors ??= new \Illuminate\Support\ViewErrorBag;

    $filters = [
        ['id' => 'all',       'label' => 'Semua'],
        ['id' => 'Strategic', 'label' => 'Strategic'],
        ['id' => 'Growth',    'label' => 'Growth'],
        ['id' => 'Standard',  'label' => 'Standard'],
        ['id' => 'Prospect',  'label' => 'Prospect'],
        ['id' => 'attention', 'label' => 'Perlu Atensi'],
    ];

    $col = collect($clients);

    $filterCounts = [
        'all'       => count($clients),
        'Strategic' => $col->where('tier', 'Strategic')->count(),
        'Growth'    => $col->where('tier', 'Growth')->count(),
        'Standard'  => $col->where('tier', 'Standard')->count(),
        'Prospect'  => $col->where('tier', 'Prospect')->count(),
        'attention' => $col->where('attention', true)->count(),
    ];

    $stats = [
        ['label' => 'Total Klien',  'value' => count($clients),                                        'note' => '+2 kuartal ini',  'color' => '#7C3AED'],
        ['label' => 'Strategic',    'value' => $col->where('tier', 'Strategic')->count(),             'note' => 'kontrak >12 bln', 'color' => '#A855F7'],
        ['label' => 'Proyek Aktif', 'value' => $col->sum('active_projects'),                          'note' => 'tersebar 8 klien','color' => '#3B82F6'],
        ['label' => 'Avg Health',   'value' => round($col->avg('health')) . '%',                       'note' => 'baik',            'color' => '#10B981'],
        ['label' => 'Perlu Atensi', 'value' => $col->where('attention', true)->count(),               'note' => 'AI flagged',      'color' => '#F59E0B'],
    ];

    $tierGradient = [
        'Strategic' => 'background: linear-gradient(135deg, #7C3AED 0%, #A855F7 100%);',
        'Growth'    => 'background: linear-gradient(135deg, #2563EB 0%, #06B6D4 100%);',
        'Standard'  => 'background: linear-gradient(135deg, #64748B 0%, #94A3B8 100%);',
        'Prospect'  => 'background: linear-gradient(135deg, #F59E0B 0%, #F97316 100%);',
    ];

    $tierStripe = [
        'Strategic' => 'bg-violet-500',
        'Growth'    => 'bg-blue-500',
        'Standard'  => 'bg-slate-400',
        'Prospect'  => 'bg-amber-500',
    ];

    $tierPill = [
        'Strategic' => 'bg-violet-50 text-violet-700',
        'Growth'    => 'bg-blue-50 text-blue-700',
        'Standard'  => 'bg-slate-100 text-slate-600',
        'Prospect'  => 'bg-amber-50 text-amber-700',
    ];

    $tierPillSolid = [
        'Strategic' => 'bg-violet-100 text-violet-700',
        'Growth'    => 'bg-blue-100 text-blue-700',
        'Standard'  => 'bg-slate-200 text-slate-600',
        'Prospect'  => 'bg-amber-100 text-amber-700',
    ];

    $projectStatusPill = [
        'on-track'  => 'bg-emerald-50 text-emerald-700',
        'attention' => 'bg-amber-50 text-amber-700',
        'critical'  => 'bg-rose-50 text-rose-700',
        'done'      => 'bg-slate-100 text-slate-600',
    ];

    $healthFill = function ($h) {
        if ($h >= 75) return '#10B981';
        if ($h >= 50) return '#F59E0B';
        return '#EF4444';
    };

    $healthVerdict = function ($h) {
        if ($h >= 75) return ['label' => 'Excellent', 'class' => 'text-emerald-600'];
        if ($h >= 50) return ['label' => 'Watch',     'class' => 'text-amber-600'];
        return                ['label' => 'At Risk',   'class' => 'text-rose-600'];
    };

@endphp

<x-layouts.authenticated :title="$title">

    <section class="flex items-end justify-between mb-8 gap-6 flex-wrap">
        <div>
            <h1 class="text-[44px] leading-[1.05] font-bold tracking-tight text-[#1E1B4B]">
                Client
                <span class="bg-clip-text text-transparent bg-gradient-to-r from-[#7C3AED] to-[#A855F7]">Directory</span>
            </h1>
            <p class="mt-3 text-[15px] text-slate-500 max-w-2xl">
                Pusat data klien Avatech beserta proyek aktif, riwayat engagement, dan saluran komunikasi langsung via WhatsApp.
            </p>
        </div>
        <div class="flex items-center gap-2 flex-wrap">
            <button type="button" data-export-clients class="inline-flex items-center gap-2 h-11 px-4 rounded-xl border border-violet-100 bg-white text-[13px] font-semibold text-slate-600 hover:border-violet-300 hover:text-violet-700 transition cursor-pointer">
                <x-heroicon-o-arrow-down-tray class="w-4 h-4" />
                Export CSV
            </button>
            <button type="button" data-create-trigger="client" class="inline-flex items-center gap-2 h-12 px-5 rounded-xl bg-gradient-to-r from-[#7C3AED] via-[#A855F7] to-[#C084FC] text-white font-semibold text-[14px] shadow-[0_4px_14px_rgba(124,58,237,0.35)] hover:shadow-[0_6px_20px_rgba(124,58,237,0.45)] hover:-translate-y-0.5 transition-all cursor-pointer">
                <x-heroicon-o-plus class="w-5 h-5" />
                Tambah Klien
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

    <section class="relative overflow-hidden bg-gradient-to-br from-violet-50 via-white to-fuchsia-50 border border-violet-200 rounded-2xl p-6 mb-8">
        <div class="absolute inset-0 pointer-events-none" style="background: radial-gradient(circle at top right, rgba(168,85,247,0.18), transparent 60%);"></div>
        <div class="relative flex items-start gap-5 flex-wrap">
            <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-[#7C3AED] via-[#A855F7] to-[#C084FC] text-white flex items-center justify-center flex-shrink-0 shadow-[0_2px_8px_rgba(124,58,237,0.2)]">
                <x-heroicon-o-sparkles class="w-5 h-5" />
            </div>
            <div class="flex-1 min-w-[280px]">
                <div class="flex items-center gap-2 mb-1.5 flex-wrap">
                    <span class="text-[10.5px] font-bold tracking-[0.12em] uppercase text-violet-700">AI Sekretaris &middot; Outreach Saran</span>
                    <span class="w-1 h-1 rounded-full bg-violet-300"></span>
                    <span class="text-[11.5px] text-slate-500">baru saja</span>
                </div>
                <h3 class="text-[16.5px] font-bold text-[#1E1B4B] mb-1">3 klien butuh perhatian minggu ini</h3>
                <p class="text-[13px] text-slate-600 leading-relaxed">
                    <strong>PT Global Prima</strong> kontrak habis 7 hari &middot; <strong>PT Maju Jaya</strong> belum follow-up 30 hari &middot; <strong>CV Berkah Digital</strong> minta proposal revisi.
                    Saya bisa drafting WhatsApp dengan tone konteks-aware untuk masing-masing.
                </p>
                <div class="mt-3 flex gap-2 flex-wrap">
                    <button type="button" data-toast="AI Drafting WhatsApp untuk semua klien segera tersedia." class="inline-flex items-center gap-1.5 h-9 px-3.5 rounded-lg bg-white border border-violet-200 hover:border-violet-400 text-[12.5px] font-semibold text-violet-700 transition cursor-pointer">
                        <x-heroicon-o-chat-bubble-left-right class="w-3.5 h-3.5" />
                        Drafting WhatsApp Semua
                    </button>
                    <button type="button" data-cycle-attention class="inline-flex items-center gap-1.5 h-9 px-3.5 rounded-lg text-[12.5px] font-semibold text-slate-500 hover:bg-white/70 transition cursor-pointer">
                        Tampilkan satu per satu
                    </button>
                </div>
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
                    <option value="health">Health tertinggi</option>
                    <option value="recent">Aktivitas terbaru</option>
                    <option value="projects">Proyek terbanyak</option>
                    <option value="idle">Idle terlama</option>
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
        @foreach ($clients as $c)
            <article
                data-tier="{{ $c['tier'] }}"
                data-attention="{{ $c['attention'] ? 'true' : 'false' }}"
                data-client-id="{{ $c['id'] }}"
                data-modal-trigger
                data-search-item
                data-sort-health="{{ $c['health'] }}"
                data-sort-id="{{ $c['id'] }}"
                data-sort-projects="{{ $c['active_projects'] }}"
                data-sort-idle="{{ $c['last_touch_sort'] }}"
                data-sort-name="{{ $c['name'] }}"
                class="group bg-white rounded-2xl border border-violet-100 shadow-[0_2px_8px_rgba(124,58,237,0.08)] hover:shadow-[0_8px_24px_rgba(124,58,237,0.12)] transition p-6 flex flex-col relative overflow-hidden cursor-pointer"
            >
                <span class="absolute top-0 left-6 right-6 h-[3px] rounded-b-full {{ $tierStripe[$c['tier']] }}"></span>

                <div class="flex items-start justify-between mb-4 gap-3">
                    <div class="flex items-center gap-3 min-w-0">
                        <div class="w-12 h-12 rounded-xl flex items-center justify-center text-white font-bold text-[13px] flex-shrink-0" style="{{ $tierGradient[$c['tier']] }}">
                            {{ $c['code'] }}
                        </div>
                        <div class="min-w-0">
                            <h3 class="text-[16px] font-bold text-[#1E1B4B] leading-tight group-hover:text-violet-700 transition truncate">{{ $c['name'] }}</h3>
                            <div class="text-[12.5px] text-slate-500 mt-0.5 inline-flex items-center gap-1.5">
                                <x-heroicon-o-briefcase class="w-3 h-3" />
                                <span>{{ $c['industry'] }}</span>
                            </div>
                        </div>
                    </div>
                    <span class="inline-flex items-center gap-1.5 text-[11px] font-semibold rounded-full px-2.5 py-1 flex-shrink-0 {{ $tierPill[$c['tier']] }}">
                        <x-heroicon-o-star class="w-3 h-3" />
                        <span>{{ $c['tier'] }}</span>
                    </span>
                </div>

                <div class="flex items-center gap-2.5 mb-4 px-3 py-2.5 rounded-xl bg-violet-50/40 border border-violet-50">
                    <div class="w-8 h-8 rounded-full bg-violet-100 text-violet-700 flex items-center justify-center font-bold text-[11px] flex-shrink-0">{{ $c['pic_initials'] }}</div>
                    <div class="min-w-0 flex-1">
                        <div class="text-[12.5px] font-semibold text-[#1E1B4B] truncate">{{ $c['pic'] }}</div>
                        <div class="text-[11px] text-slate-500 truncate">{{ $c['pic_role'] }}</div>
                    </div>
                    <x-heroicon-o-phone class="w-3.5 h-3.5 text-slate-400 flex-shrink-0" />
                </div>

                <div class="grid grid-cols-3 gap-3 mb-4">
                    <div>
                        <div class="text-[10px] font-bold tracking-wider uppercase text-slate-400">Proyek Aktif</div>
                        <div class="text-[18px] font-bold text-[#1E1B4B] mt-0.5 tabular-nums">{{ $c['active_projects'] }}</div>
                    </div>
                    <div>
                        <div class="text-[10px] font-bold tracking-wider uppercase text-slate-400">Total Engagement</div>
                        <div class="text-[18px] font-bold text-[#1E1B4B] mt-0.5 tabular-nums">{{ $c['total_projects'] }}</div>
                    </div>
                    <div>
                        <div class="text-[10px] font-bold tracking-wider uppercase text-slate-400">Last Touch</div>
                        <div class="text-[14px] font-bold text-[#1E1B4B] mt-0.5">{{ $c['last_touch'] }}</div>
                    </div>
                </div>

                <div class="mt-auto">
                    <div class="flex items-center justify-between mb-1.5">
                        <span class="text-[11px] font-bold uppercase tracking-wider text-slate-500 inline-flex items-center gap-1.5">
                            <x-heroicon-o-heart class="w-3.5 h-3.5" />
                            Relationship Health
                        </span>
                        <span class="text-[13px] font-bold text-[#1E1B4B] tabular-nums">{{ $c['health'] }}%</span>
                    </div>
                    <div class="h-1.5 rounded-full bg-[#F3E8FF] overflow-hidden">
                        <div class="h-full rounded-full" style="width: {{ $c['health'] }}%; background: {{ $healthFill($c['health']) }};"></div>
                    </div>

                    <div class="mt-4 pt-4 border-t border-violet-50 flex items-center justify-between gap-2">
                        <a href="{{ $c['wa_link'] }}" target="_blank" rel="noopener" data-no-modal class="inline-flex items-center gap-1.5 h-9 px-3 rounded-lg bg-emerald-50 hover:bg-emerald-100 text-emerald-700 text-[12.5px] font-semibold transition cursor-pointer">
                            <x-heroicon-o-chat-bubble-oval-left class="w-4 h-4" />
                            WhatsApp
                        </a>
                        <a href="{{ $c['email_link'] }}" target="_blank" rel="noopener" data-no-modal class="inline-flex items-center gap-1.5 h-9 px-3 rounded-lg border border-violet-100 hover:border-violet-300 text-slate-600 hover:text-violet-700 text-[12.5px] font-semibold transition cursor-pointer">
                            <x-heroicon-o-envelope class="w-4 h-4" />
                            Email
                        </a>
                        <span class="ml-auto text-slate-300" aria-hidden="true">
                            <x-heroicon-o-arrow-top-right-on-square class="w-4 h-4" />
                        </span>
                    </div>
                </div>
            </article>
        @endforeach

        <div data-empty class="hidden md:col-span-2 xl:col-span-3 bg-white rounded-2xl border border-dashed border-violet-200 p-12 text-center">
            <div class="w-14 h-14 mx-auto rounded-2xl bg-violet-50 text-violet-400 flex items-center justify-center mb-3">
                <x-heroicon-o-user-group class="w-6 h-6" />
            </div>
            <h3 class="text-[15px] font-semibold text-[#1E1B4B]">Tidak ada klien</h3>
            <p class="text-[13px] text-slate-500 mt-1">Coba ubah filter atau tambah klien baru.</p>
        </div>
    </section>

    <section data-view-panel="list" class="hidden bg-white rounded-2xl border border-violet-100 shadow-[0_2px_8px_rgba(124,58,237,0.08)] overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead>
                    <tr class="text-[11px] font-bold tracking-[0.1em] uppercase text-slate-400 border-b border-violet-50">
                        <th class="px-7 py-4">Klien</th>
                        <th class="px-4 py-4">Industri</th>
                        <th class="px-4 py-4">PIC</th>
                        <th class="px-4 py-4">Tier</th>
                        <th class="px-4 py-4 w-[220px]">Health</th>
                        <th class="px-4 py-4">Proyek</th>
                        <th class="px-4 py-4">Last Touch</th>
                        <th class="px-7 py-4 text-right"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($clients as $c)
                        <tr
                            data-tier="{{ $c['tier'] }}"
                            data-attention="{{ $c['attention'] ? 'true' : 'false' }}"
                            data-client-id="{{ $c['id'] }}"
                            data-modal-trigger
                            data-search-item
                            data-sort-health="{{ $c['health'] }}"
                            data-sort-id="{{ $c['id'] }}"
                            data-sort-projects="{{ $c['active_projects'] }}"
                            data-sort-idle="{{ $c['last_touch_sort'] }}"
                            data-sort-name="{{ $c['name'] }}"
                            class="hover:bg-[#FAF5FF] border-b border-violet-50/60 last:border-0 transition cursor-pointer"
                        >
                            <td class="px-7 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-lg flex items-center justify-center text-white font-semibold text-[11px] flex-shrink-0" style="{{ $tierGradient[$c['tier']] }}">
                                        {{ $c['code'] }}
                                    </div>
                                    <div class="min-w-0">
                                        <div class="text-[14px] font-semibold text-[#1E1B4B]">{{ $c['name'] }}</div>
                                        <div class="text-[12px] text-slate-500">{{ $c['location'] }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-4 text-[13px] text-slate-600">{{ $c['industry'] }}</td>
                            <td class="px-4 py-4 text-[13px] text-slate-600">{{ $c['pic'] }}</td>
                            <td class="px-4 py-4">
                                <span class="inline-flex text-[11px] font-semibold rounded-full px-2.5 py-1 {{ $tierPill[$c['tier']] }}">{{ $c['tier'] }}</span>
                            </td>
                            <td class="px-4 py-4 w-[220px]">
                                <div class="flex items-center gap-2">
                                    <div class="flex-1 h-1.5 rounded-full bg-[#F3E8FF] overflow-hidden">
                                        <div class="h-full rounded-full" style="width: {{ $c['health'] }}%; background: {{ $healthFill($c['health']) }};"></div>
                                    </div>
                                    <span class="text-[12px] font-bold text-[#1E1B4B] w-9 text-right tabular-nums">{{ $c['health'] }}%</span>
                                </div>
                            </td>
                            <td class="px-4 py-4 text-[13px] text-slate-600">
                                <span class="font-semibold tabular-nums">{{ $c['active_projects'] }}</span> aktif
                            </td>
                            <td class="px-4 py-4 text-[13px] text-slate-600">{{ $c['last_touch'] }}</td>
                            <td class="px-7 py-4 text-right">
                                <a href="{{ $c['wa_link'] }}" target="_blank" rel="noopener" data-no-modal class="inline-flex items-center text-emerald-600 mr-3">
                                    <x-heroicon-o-chat-bubble-oval-left class="w-4 h-4" />
                                </a>
                                <x-heroicon-o-chevron-right class="w-4 h-4 text-slate-400 inline-block" />
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>

    {{-- =============== CLIENT DETAIL MODAL =============== --}}
    <div data-modal class="hidden fixed inset-0 z-50 items-center justify-center p-4 sm:p-6" role="dialog" aria-modal="true" aria-labelledby="cd-modal-title">
        <div data-modal-overlay class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm"></div>

        <div data-modal-panel class="relative bg-white rounded-3xl shadow-[0_24px_64px_rgba(124,58,237,0.18)] w-full max-w-[860px] max-h-[90vh] flex flex-col overflow-hidden border border-violet-100">
            @foreach ($clients as $c)
                @php $hv = $healthVerdict($c['health']); @endphp
                <div data-client-content="{{ $c['id'] }}" class="hidden flex-col flex-1 min-h-0">

                    {{-- Hero --}}
                    <div class="relative bg-gradient-to-br from-violet-50 via-white to-fuchsia-50 px-7 pt-7 pb-6 border-b border-violet-100">
                        <div class="absolute inset-0 pointer-events-none" style="background: radial-gradient(circle at top right, rgba(168,85,247,0.18), transparent 60%);"></div>
                        <div class="relative flex items-start justify-between gap-6">
                            <div class="flex items-start gap-4 min-w-0">
                                <div class="w-16 h-16 rounded-2xl flex items-center justify-center text-white font-bold text-[18px] flex-shrink-0" style="{{ $tierGradient[$c['tier']] }}">
                                    {{ $c['code'] }}
                                </div>
                                <div class="min-w-0">
                                    <div class="flex items-center gap-2 mb-1 flex-wrap">
                                        <span class="inline-flex text-[10.5px] font-bold tracking-[0.12em] uppercase rounded-full px-2.5 py-1 {{ $tierPillSolid[$c['tier']] }}">
                                            {{ $c['tier'] }} Account
                                        </span>
                                        <span class="text-[12px] text-slate-500">{{ $c['industry'] }}</span>
                                        <span class="w-1 h-1 rounded-full bg-slate-300"></span>
                                        <span class="text-[12px] text-slate-500">{{ $c['location'] }}</span>
                                    </div>
                                    <h2 id="cd-modal-title-{{ $c['id'] }}" class="text-[26px] font-bold text-[#1E1B4B] leading-tight">{{ $c['name'] }}</h2>
                                    <p class="text-[13px] text-slate-500 mt-1 max-w-md">{{ $c['desc'] }}</p>
                                </div>
                            </div>
                            <button type="button" data-modal-close class="w-9 h-9 rounded-xl hover:bg-violet-100 text-slate-500 hover:text-violet-700 flex items-center justify-center transition flex-shrink-0 cursor-pointer" aria-label="Tutup">
                                <x-heroicon-o-x-mark class="w-5 h-5" />
                            </button>
                        </div>
                    </div>

                    {{-- Body --}}
                    <div class="flex-1 overflow-y-auto px-7 py-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                            {{-- PIC + contact --}}
                            <div class="rounded-2xl border border-violet-100 p-5">
                                <div class="text-[10.5px] font-bold tracking-wider uppercase text-slate-400 mb-3">Person in Charge</div>
                                <div class="flex items-center gap-3">
                                    <div class="w-11 h-11 rounded-full bg-violet-100 text-violet-700 flex items-center justify-center font-bold text-[13px]">{{ $c['pic_initials'] }}</div>
                                    <div class="min-w-0">
                                        <div class="text-[14.5px] font-bold text-[#1E1B4B] truncate">{{ $c['pic'] }}</div>
                                        <div class="text-[12px] text-slate-500 truncate">{{ $c['pic_role'] }}</div>
                                    </div>
                                </div>
                                <div class="mt-4 space-y-2">
                                    <div class="flex items-center gap-2 text-[12.5px] text-slate-600">
                                        <x-heroicon-o-phone class="w-3.5 h-3.5 text-violet-500" />
                                        <span>{{ $c['phone'] }}</span>
                                    </div>
                                    <div class="flex items-center gap-2 text-[12.5px] text-slate-600">
                                        <x-heroicon-o-envelope class="w-3.5 h-3.5 text-violet-500" />
                                        <span class="truncate">{{ $c['email'] }}</span>
                                    </div>
                                </div>
                                <div class="mt-4 flex gap-2">
                                    <a href="{{ $c['wa_link'] }}" target="_blank" rel="noopener" class="flex-1 inline-flex items-center justify-center gap-1.5 h-9 rounded-lg bg-emerald-500 hover:bg-emerald-600 text-white text-[12.5px] font-semibold transition cursor-pointer">
                                        <x-heroicon-o-chat-bubble-oval-left class="w-4 h-4" />
                                        WhatsApp
                                    </a>
                                    <a href="{{ $c['email_link'] }}" target="_blank" rel="noopener" class="flex-1 inline-flex items-center justify-center gap-1.5 h-9 rounded-lg border border-violet-200 hover:border-violet-400 text-slate-600 hover:text-violet-700 text-[12.5px] font-semibold transition cursor-pointer">
                                        <x-heroicon-o-envelope class="w-4 h-4" />
                                        Email
                                    </a>
                                </div>
                            </div>

                            {{-- Health --}}
                            <div class="rounded-2xl border border-violet-100 p-5">
                                <div class="text-[10.5px] font-bold tracking-wider uppercase text-slate-400 mb-3">Relationship Health</div>
                                <div class="flex items-baseline gap-2 mb-2">
                                    <span class="text-[36px] font-bold leading-none text-[#1E1B4B] tabular-nums">{{ $c['health'] }}%</span>
                                    <span class="text-[12px] font-semibold {{ $hv['class'] }}">{{ $hv['label'] }}</span>
                                </div>
                                <div class="h-1.5 rounded-full bg-[#F3E8FF] overflow-hidden mb-4">
                                    <div class="h-full rounded-full" style="width: {{ $c['health'] }}%; background: {{ $healthFill($c['health']) }};"></div>
                                </div>
                                <div class="grid grid-cols-3 gap-3 text-center">
                                    <div class="rounded-lg bg-violet-50/60 p-3">
                                        <div class="text-[10px] font-bold tracking-wider uppercase text-slate-500">Aktif</div>
                                        <div class="text-[18px] font-bold text-[#1E1B4B] mt-1 tabular-nums">{{ $c['active_projects'] }}</div>
                                    </div>
                                    <div class="rounded-lg bg-violet-50/60 p-3">
                                        <div class="text-[10px] font-bold tracking-wider uppercase text-slate-500">Total</div>
                                        <div class="text-[18px] font-bold text-[#1E1B4B] mt-1 tabular-nums">{{ $c['total_projects'] }}</div>
                                    </div>
                                    <div class="rounded-lg bg-violet-50/60 p-3">
                                        <div class="text-[10px] font-bold tracking-wider uppercase text-slate-500">Last Touch</div>
                                        <div class="text-[14px] font-bold text-[#1E1B4B] mt-1">{{ $c['last_touch'] }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Proyek Terkait --}}
                        <div class="mb-6">
                            <h4 class="text-[12px] font-bold tracking-[0.12em] uppercase text-violet-600 mb-3 inline-flex items-center gap-2">
                                <span class="w-1.5 h-1.5 rounded-full bg-violet-500"></span> Proyek Terkait
                            </h4>
                            <div class="space-y-2">
                                @forelse ($c['projects'] as $p)
                                    @if (! empty($p['id']))
                                        <a href="{{ route('projects.show', $p['id']) }}" class="flex items-center gap-3 p-3 rounded-xl border border-violet-50 hover:border-violet-200 hover:bg-violet-50/40 transition cursor-pointer group">
                                            <div class="w-9 h-9 rounded-lg flex items-center justify-center text-white font-bold text-[11px] flex-shrink-0" style="background: {{ $p['color'] }};">{{ $p['code'] }}</div>
                                            <div class="min-w-0 flex-1">
                                                <div class="text-[14px] font-semibold text-[#1E1B4B] truncate group-hover:text-violet-700 transition">{{ $p['name'] }}</div>
                                                <div class="text-[12px] text-slate-500">{{ $p['phase'] }} &middot; {{ $p['progress'] }}% complete</div>
                                            </div>
                                            <span class="text-[11px] font-semibold rounded-full px-2.5 py-1 {{ $projectStatusPill[$p['status']] ?? 'bg-slate-100 text-slate-600' }}">
                                                {{ $p['status_label'] }}
                                            </span>
                                        </a>
                                    @else
                                        <div class="flex items-center gap-3 p-3 rounded-xl border border-violet-50 transition">
                                            <div class="w-9 h-9 rounded-lg flex items-center justify-center text-white font-bold text-[11px] flex-shrink-0" style="background: {{ $p['color'] }};">{{ $p['code'] }}</div>
                                            <div class="min-w-0 flex-1">
                                                <div class="text-[14px] font-semibold text-[#1E1B4B] truncate">{{ $p['name'] }}</div>
                                                <div class="text-[12px] text-slate-500">{{ $p['phase'] }} &middot; {{ $p['progress'] }}% complete</div>
                                            </div>
                                            <span class="text-[11px] font-semibold rounded-full px-2.5 py-1 {{ $projectStatusPill[$p['status']] ?? 'bg-slate-100 text-slate-600' }}">
                                                {{ $p['status_label'] }}
                                            </span>
                                        </div>
                                    @endif
                                @empty
                                    <div class="text-[12.5px] text-slate-400 text-center py-6">Belum ada proyek tercatat.</div>
                                @endforelse
                            </div>
                        </div>

                        {{-- Riwayat Engagement --}}
                        <div>
                            <h4 class="text-[12px] font-bold tracking-[0.12em] uppercase text-emerald-600 mb-3 inline-flex items-center gap-2">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Riwayat Engagement
                            </h4>
                            <div class="space-y-3">
                                @foreach ($c['timeline'] as $t)
                                    <div class="flex gap-3">
                                        <div class="w-2 h-2 rounded-full mt-2 flex-shrink-0" style="background: {{ $t['color'] }};"></div>
                                        <div class="flex-1 min-w-0">
                                            <p class="text-[13px] text-[#1E1B4B] leading-snug">{!! $t['text'] !!}</p>
                                            <div class="text-[11.5px] text-slate-400 mt-0.5">{{ $t['time'] }}</div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach

            {{-- Static footer (shared across clients) --}}
            <div data-client-footer class="px-7 py-4 border-t border-violet-100 bg-violet-50/30 flex items-center justify-between flex-wrap gap-3">
                <p class="text-[12.5px] text-slate-500 inline-flex items-center gap-1.5">
                    <x-heroicon-o-sparkles class="w-3.5 h-3.5 text-violet-500" />
                    AI Sekretaris siap drafting WA dengan konteks proyek aktif.
                </p>
                <div class="flex gap-2">
                    <button type="button" data-modal-close class="px-5 h-9 rounded-xl bg-white border border-violet-200 text-[13px] font-semibold text-slate-600 hover:border-violet-400 hover:text-violet-700 transition cursor-pointer">Tutup</button>
                    <button type="button" data-toast="AI Drafting WhatsApp segera tersedia." class="inline-flex items-center gap-2 h-9 px-4 rounded-xl bg-gradient-to-r from-pink-500 to-violet-600 text-white text-[13px] font-semibold shadow-[0_2px_8px_rgba(124,58,237,0.2)] hover:scale-[1.02] transition cursor-pointer">
                        <x-heroicon-o-sparkles class="w-4 h-4" />
                        Drafting WhatsApp
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        window.__clientsCsvMap = @json(collect($clients)->keyBy('id'));
    </script>

    <script>
        (function () {
            const wire = () => {
                const chips = document.querySelectorAll('.js-filter-chip');
                const empty = document.querySelector('[data-empty]');

                const matches = (el, id) => {
                    if (id === 'all') return true;
                    if (id === 'attention') return el.dataset.attention === 'true';
                    return el.dataset.tier === id;
                };

                const applyFilter = (id) => {
                    const items = document.querySelectorAll('[data-tier]'); // live query so user-added cards are included
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
                        health:   { key: 'sortHealth',   dir: -1, num: true  },
                        recent:   { key: 'sortId',       dir:  1, num: true  },
                        projects: { key: 'sortProjects', dir: -1, num: true  },
                        idle:     { key: 'sortIdle',     dir: -1, num: true  },
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
                    sortSel.addEventListener('change', () => {
                        sortContainer('[data-view-panel="grid"]');
                        sortContainer('[data-view-panel="list"] tbody');
                    });
                }

                /* === Export CSV === */
                const exportBtn = document.querySelector('[data-export-clients]');
                if (exportBtn) {
                    exportBtn.addEventListener('click', () => {
                        const csvCell = (v) => '"' + String(v ?? '').replace(/"/g, '""') + '"';
                        const rows = [['ID','Name','Industry','Location','Tier','Health %','Active Projects','Total Projects','Last Touch','PIC','PIC Role','Email','Phone','Attention']];
                        document.querySelectorAll('[data-view-panel="grid"] [data-client-id]').forEach(card => {
                            const id = card.dataset.clientId;
                            const data = window.__clientsCsvMap?.[id];
                            if (! data) return;
                            rows.push([data.id, data.name, data.industry, data.location, data.tier, data.health, data.active_projects, data.total_projects, data.last_touch, data.pic, data.pic_role, data.email, data.phone, data.attention ? 'Yes' : 'No']);
                        });
                        const csv = rows.map(r => r.map(csvCell).join(',')).join('\r\n');
                        window.downloadFile && window.downloadFile('clients-' + new Date().toISOString().slice(0,10) + '.csv', csv, 'text/csv;charset=utf-8');
                        window.toast && window.toast('CSV klien diunduh (' + (rows.length - 1) + ' baris).');
                    });
                }

                /* === Modal === */
                const modal    = document.querySelector('[data-modal]');
                const overlay  = modal?.querySelector('[data-modal-overlay]');
                const panel    = modal?.querySelector('[data-modal-panel]');
                const openModal = (id) => {
                    if (! modal) return;
                    /* live query — content blocks may be injected dynamically by renderCard */
                    const contents = modal.querySelectorAll('[data-client-content]');
                    let matched = false;
                    contents.forEach(el => {
                        const isThis = el.dataset.clientContent === String(id);
                        el.classList.toggle('hidden', ! isThis);
                        el.classList.toggle('flex', isThis);
                        if (isThis) matched = true;
                    });
                    if (! matched) return;
                    modal.classList.remove('hidden');
                    modal.classList.add('flex');
                    document.body.style.overflow = 'hidden';
                };

                const closeModal = () => {
                    if (! modal) return;
                    modal.classList.add('hidden');
                    modal.classList.remove('flex');
                    document.body.style.overflow = '';
                };

                document.querySelectorAll('[data-modal-trigger]').forEach(trigger => {
                    trigger.addEventListener('click', (e) => {
                        if (e.target.closest('[data-no-modal]')) return;
                        const id = trigger.dataset.clientId;
                        if (id) openModal(id);
                    });
                });

                /* === Auto-open client modal from ?open=client:{id} === */
                try {
                    const params = new URLSearchParams(window.location.search);
                    const op = params.get('open');
                    if (op && op.startsWith('client:')) {
                        const id = op.split(':')[1];
                        if (id) openModal(id);
                    }
                } catch (e) {}

                /* === Cycle attention clients === */
                let cycleIdx = 0;
                document.querySelector('[data-cycle-attention]')?.addEventListener('click', () => {
                    const attention = Array.from(document.querySelectorAll('[data-view-panel="grid"] [data-modal-trigger][data-attention="true"]'));
                    if (! attention.length) {
                        window.toast && window.toast('Tidak ada klien yang butuh atensi saat ini.');
                        return;
                    }
                    const target = attention[cycleIdx % attention.length];
                    cycleIdx++;
                    openModal(target.dataset.clientId);
                });

                if (panel) panel.addEventListener('click', (e) => e.stopPropagation());
                if (overlay) overlay.addEventListener('click', closeModal);
                modal?.querySelectorAll('[data-modal-close]').forEach(btn => {
                    btn.addEventListener('click', closeModal);
                });
                document.addEventListener('keydown', (e) => {
                    if (e.key === 'Escape' && modal && ! modal.classList.contains('hidden')) closeModal();
                });
            };

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', wire);
            } else {
                wire();
            }
        })();
    </script>

    {{-- ===== Create Client Modal ===== --}}
    <div data-create-modal="client" data-create-has-errors="{{ $errors->any() ? '1' : '0' }}" class="hidden fixed inset-0 z-50 items-center justify-center p-4 sm:p-6" role="dialog" aria-modal="true">
        <div data-create-overlay class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm"></div>
        <form method="POST" action="{{ route('clients.store') }}" data-create-panel class="relative bg-white rounded-3xl shadow-[0_24px_64px_rgba(124,58,237,0.18)] w-full max-w-md flex flex-col overflow-hidden border border-violet-100">
            @csrf
            <div class="px-6 py-4 border-b border-violet-100 flex items-center justify-between">
                <h3 class="text-[16px] font-bold text-[#1E1B4B]">Klien Baru</h3>
                <button type="button" data-create-close aria-label="Tutup" class="w-9 h-9 rounded-full hover:bg-violet-50 flex items-center justify-center text-slate-500 hover:text-rose-500 transition cursor-pointer">
                    <x-heroicon-o-x-mark class="w-5 h-5" />
                </button>
            </div>
            <div class="px-6 py-5 space-y-3 max-h-[72vh] overflow-y-auto">
                <div class="flex items-start gap-2.5 p-3 rounded-lg bg-violet-50/70 border border-violet-100 text-[12px] text-slate-600">
                    <x-heroicon-o-sparkles class="w-4 h-4 text-violet-600 flex-shrink-0 mt-0.5" />
                    <span>Klien baru otomatis di-tier-kan sebagai <strong class="text-[#1E1B4B]">Prospect</strong> dengan health <strong class="text-[#1E1B4B]">50%</strong>.</span>
                </div>
                <div>
                    <label class="block text-[11px] font-bold tracking-wider uppercase text-slate-500 mb-1.5">Kode</label>
                    <input name="code" value="{{ old('code') }}" maxlength="8" class="w-full h-10 rounded-lg border border-violet-100 px-3 text-[13px] uppercase focus:outline-none focus:ring-2 focus:ring-violet-300" placeholder="MJ" />
                    @error('code') <p class="mt-1 text-[11.5px] font-semibold text-rose-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-[11px] font-bold tracking-wider uppercase text-slate-500 mb-1.5">Nama Perusahaan</label>
                    <input name="name" value="{{ old('name') }}" class="w-full h-10 rounded-lg border border-violet-100 px-3 text-[13px] focus:outline-none focus:ring-2 focus:ring-violet-300" placeholder="PT ..." />
                    @error('name') <p class="mt-1 text-[11.5px] font-semibold text-rose-600">{{ $message }}</p> @enderror
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-[11px] font-bold tracking-wider uppercase text-slate-500 mb-1.5">Industri</label>
                        <input name="industry" value="{{ old('industry') }}" class="w-full h-10 rounded-lg border border-violet-100 px-3 text-[13px] focus:outline-none focus:ring-2 focus:ring-violet-300" placeholder="Fintech" />
                        @error('industry') <p class="mt-1 text-[11.5px] font-semibold text-rose-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold tracking-wider uppercase text-slate-500 mb-1.5">Lokasi</label>
                        <input name="location" value="{{ old('location') }}" class="w-full h-10 rounded-lg border border-violet-100 px-3 text-[13px] focus:outline-none focus:ring-2 focus:ring-violet-300" placeholder="Jakarta" />
                        @error('location') <p class="mt-1 text-[11.5px] font-semibold text-rose-600">{{ $message }}</p> @enderror
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-[11px] font-bold tracking-wider uppercase text-slate-500 mb-1.5">PIC</label>
                        <input name="pic_name" value="{{ old('pic_name') }}" class="w-full h-10 rounded-lg border border-violet-100 px-3 text-[13px] focus:outline-none focus:ring-2 focus:ring-violet-300" placeholder="Nama lengkap" />
                        @error('pic_name') <p class="mt-1 text-[11.5px] font-semibold text-rose-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold tracking-wider uppercase text-slate-500 mb-1.5">PIC Role</label>
                        <input name="pic_role" value="{{ old('pic_role') }}" class="w-full h-10 rounded-lg border border-violet-100 px-3 text-[13px] focus:outline-none focus:ring-2 focus:ring-violet-300" placeholder="CTO / Director" />
                        @error('pic_role') <p class="mt-1 text-[11.5px] font-semibold text-rose-600">{{ $message }}</p> @enderror
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-[11px] font-bold tracking-wider uppercase text-slate-500 mb-1.5">Email</label>
                        <input name="email" value="{{ old('email') }}" type="email" class="w-full h-10 rounded-lg border border-violet-100 px-3 text-[13px] focus:outline-none focus:ring-2 focus:ring-violet-300" placeholder="kontak@klien.id" />
                        @error('email') <p class="mt-1 text-[11.5px] font-semibold text-rose-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold tracking-wider uppercase text-slate-500 mb-1.5">WhatsApp</label>
                        <input name="phone" value="{{ old('phone') }}" type="tel" class="w-full h-10 rounded-lg border border-violet-100 px-3 text-[13px] focus:outline-none focus:ring-2 focus:ring-violet-300" placeholder="+62 812 3456 7890" />
                        @error('phone') <p class="mt-1 text-[11.5px] font-semibold text-rose-600">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>
            <div class="px-6 py-3 border-t border-violet-100 bg-violet-50/30 flex items-center justify-end gap-2">
                <button type="button" data-create-close class="h-9 px-4 rounded-lg text-[12.5px] font-semibold text-slate-500 hover:text-slate-700 transition cursor-pointer">Batal</button>
                <button type="submit" class="inline-flex items-center gap-2 h-9 px-4 rounded-lg bg-gradient-to-r from-[#7C3AED] via-[#A855F7] to-[#C084FC] text-white font-semibold text-[12.5px] shadow-[0_2px_8px_rgba(124,58,237,0.2)] hover:scale-[1.02] transition cursor-pointer">
                    <x-heroicon-o-bookmark-square class="w-4 h-4" />
                    Simpan
                </button>
            </div>
        </form>
    </div>

    <script>
        (function () {
            const wire = () => {
                const modal = document.querySelector('[data-create-modal="client"]');
                const trigger = document.querySelector('[data-create-trigger="client"]');
                if (! modal || ! trigger) return;
                const overlay = modal.querySelector('[data-create-overlay]');
                const panel = modal.querySelector('[data-create-panel]');
                const open = () => { modal.classList.remove('hidden'); modal.classList.add('flex'); document.body.style.overflow = 'hidden'; modal.querySelector('input[name="name"]')?.focus(); };
                const close = () => { modal.classList.add('hidden'); modal.classList.remove('flex'); document.body.style.overflow = ''; };

                trigger.addEventListener('click', open);
                panel?.addEventListener('click', (e) => e.stopPropagation());
                overlay?.addEventListener('click', close);
                modal.querySelectorAll('[data-create-close]').forEach(b => b.addEventListener('click', close));
                document.addEventListener('keydown', (e) => { if (e.key === 'Escape' && ! modal.classList.contains('hidden')) close(); });

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
