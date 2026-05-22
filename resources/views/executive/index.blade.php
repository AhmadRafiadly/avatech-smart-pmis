@php
    $reminders = [
        ['badge' => 'TINDAK LANJUT KLIEN', 'icon' => 'phone',                 'color' => '#7C3AED', 'badge_bg' => '#EDE9FE', 'title' => 'Update klien PT. Maju Jaya diperlukan',     'body' => 'Proyek telah selesai 30 hari lalu. Belum ada laporan akhir atau komunikasi lanjutan yang tercatat di sistem.',     'action' => 'Ambil Tindakan',  'time' => '2 jam lalu'],
        ['badge' => 'PERINGATAN PROYEK',   'icon' => 'exclamation-triangle', 'color' => '#F59E0B', 'badge_bg' => '#FEF3C7', 'title' => 'Proyek Alpha butuh perhatian segera',       'body' => 'Tidak ada pembaruan selama 3 hari terakhir. Risiko keterlambatan milestone Q3 meningkat signifikan.',              'action' => 'Tinjau Proyek',   'time' => '5 jam lalu'],
        ['badge' => 'KONTRAK MENDEKATI',   'icon' => 'clock',                 'color' => '#EF4444', 'badge_bg' => '#FEE2E2', 'title' => 'Kontrak PT. Global Prima habis 7 hari lagi', 'body' => 'Nilai engagement Rp 850 Juta. Perlu konfirmasi perpanjangan segera untuk menghindari gangguan layanan.',           'action' => 'Perbarui Kontrak','time' => '1 hari lalu'],
    ];

    $statusPill = [
        'on-track'  => 'bg-emerald-50 text-emerald-700',
        'attention' => 'bg-amber-50 text-amber-700',
        'critical'  => 'bg-rose-50 text-rose-700',
    ];
    $statusDot = [
        'on-track'  => 'bg-emerald-500',
        'attention' => 'bg-amber-500',
        'critical'  => 'bg-rose-500',
    ];

    $loadStyle = function ($load) {
        if ($load > 85)  return ['fill' => 'bg-rose-500',    'pill' => 'bg-rose-50 text-rose-700',     'dot' => 'bg-rose-500',    'label' => 'Overloaded'];
        if ($load >= 70) return ['fill' => 'bg-amber-500',   'pill' => 'bg-amber-50 text-amber-700',   'dot' => 'bg-amber-500',   'label' => 'Near Capacity'];
        return                  ['fill' => 'bg-emerald-500', 'pill' => 'bg-emerald-50 text-emerald-700','dot' => 'bg-emerald-500','label' => 'Optimal'];
    };

    $user = auth()->user();
@endphp

<x-layouts.authenticated :title="$title">

    <section class="mb-8">
            <h1 class="text-[44px] leading-[1.05] font-bold tracking-tight text-[#1E1B4B]">
                Executive
                <span class="bg-clip-text text-transparent bg-gradient-to-r from-[#7C3AED] to-[#A855F7]">Monitor</span>
            </h1>
            <p class="mt-3 text-[15px] text-slate-500 max-w-2xl">
                Ikhtisar real-time kinerja bisnis &amp; kesehatan proyek perusahaan. Selamat datang kembali,
                <span class="text-violet-700 font-medium">{{ $user?->name }}</span>.
            </p>
        </section>

        <div class="border-b border-violet-100 mb-8">
            <div class="flex items-center gap-2">
                <button type="button" data-tab="overview" class="js-tab relative px-4 py-3 text-[15px] font-medium transition cursor-pointer text-violet-700">
                    Overview
                    <span class="js-tab-underline absolute left-0 right-0 -bottom-px h-[3px] rounded-full bg-gradient-to-r from-[#7C3AED] via-[#A855F7] to-[#C084FC]"></span>
                </button>
                <button type="button" data-tab="teamLoad" class="js-tab relative px-4 py-3 text-[15px] font-medium transition cursor-pointer text-slate-500 hover:text-slate-700">
                    Team Load
                    <span class="js-tab-underline absolute left-0 right-0 -bottom-px h-[3px] rounded-full bg-transparent"></span>
                </button>
            </div>
        </div>

        <div data-panel="overview">

            <section class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-6 mb-10">
                @foreach ($metrics as $m)
                    <article class="relative overflow-hidden rounded-2xl p-7 bg-white border border-violet-100 shadow-[0_2px_8px_rgba(124,58,237,0.08)] hover:shadow-[0_8px_24px_rgba(124,58,237,0.12)] transition">
                        <div class="absolute -top-7 -right-7 w-24 h-24 rounded-full bg-violet-100/55 pointer-events-none"></div>
                        <div class="relative flex items-start gap-3 mb-4">
                            <div class="w-11 h-11 rounded-xl bg-violet-100 text-violet-700 flex items-center justify-center flex-shrink-0">
                                <x-dynamic-component :component="'heroicon-o-' . $m['icon']" class="w-5 h-5" />
                            </div>
                            <div class="text-[13px] font-medium text-slate-500 leading-tight pt-1">{{ $m['label'] }}</div>
                        </div>
                        <div class="relative text-[44px] font-bold tracking-tight leading-none text-[#1E1B4B]">{{ $m['value'] }}</div>
                        <div class="relative mt-5">
                            @if ($m['progress'] !== null)
                                <div class="h-2 rounded-full bg-[#F3E8FF] overflow-hidden">
                                    <div class="h-full rounded-full bg-gradient-to-r from-[#7C3AED] via-[#A855F7] to-[#C084FC]" style="width: {{ $m['progress'] }}%"></div>
                                </div>
                                <div class="mt-2 text-[12px] text-slate-500">{{ $m['foot'] }}</div>
                            @else
                                <div class="flex items-center gap-1.5 text-[12px] {{ $m['foot_color'] ?? 'text-slate-500' }}">
                                    @if ($m['foot_icon'])
                                        <x-dynamic-component :component="'heroicon-o-' . $m['foot_icon']" class="w-3.5 h-3.5" />
                                    @endif
                                    <span>{{ $m['foot'] }}</span>
                                </div>
                            @endif
                        </div>
                    </article>
                @endforeach
            </section>

            <section id="reminders-section" class="mb-12 scroll-mt-24">
                <div class="flex items-end justify-between mb-5">
                    <div class="flex items-center gap-3 flex-wrap">
                        <span class="w-2.5 h-2.5 rounded-full bg-violet-600 animate-pulse"></span>
                        <h2 class="text-[22px] font-bold tracking-tight text-[#1E1B4B]">Pengingat Cerdas AI</h2>
                        <span class="inline-flex items-center gap-1.5 text-[11px] font-semibold tracking-wide uppercase px-2.5 py-1 rounded-full bg-violet-100 text-violet-700">
                            <x-heroicon-o-sparkles class="w-3 h-3" />
                            Sekretaris Digital
                        </span>
                    </div>
                    <button type="button" data-scroll-to="#reminders-section" class="text-[14px] font-semibold text-violet-700 hover:text-violet-900 transition flex items-center gap-1.5 cursor-pointer">
                        Lihat Semua
                        <x-heroicon-o-arrow-right class="w-4 h-4" />
                    </button>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    @foreach ($reminders as $r)
                        <article data-reminder-card class="relative bg-white rounded-2xl border border-violet-100 shadow-[0_2px_8px_rgba(124,58,237,0.08)] hover:shadow-[0_8px_24px_rgba(124,58,237,0.12)] transition p-7 flex flex-col">
                            <span class="absolute left-5 right-5 top-0 h-[3px] rounded-b-[4px]" style="background: {{ $r['color'] }};"></span>

                            <div class="mb-4">
                                <span class="inline-flex items-center gap-1.5 text-[10.5px] font-bold tracking-[0.08em] uppercase px-2.5 py-1.5 rounded-md" style="background: {{ $r['badge_bg'] }}; color: {{ $r['color'] }};">
                                    <x-dynamic-component :component="'heroicon-o-' . $r['icon']" class="w-3.5 h-3.5" />
                                    <span>{{ $r['badge'] }}</span>
                                </span>
                            </div>

                            <h3 class="text-[17px] font-bold text-[#1E1B4B] leading-tight mb-2.5">{{ $r['title'] }}</h3>
                            <p class="text-[13.5px] text-slate-500 leading-relaxed flex-1">{{ $r['body'] }}</p>

                            <div class="mt-4 flex items-center gap-1.5 text-[11px] text-violet-500/80">
                                <x-heroicon-o-sparkles class="w-3 h-3" />
                                <span>AI-generated &middot; {{ $r['time'] }}</span>
                            </div>

                            <div class="mt-5 pt-5 border-t border-violet-50 flex items-center justify-between">
                                <button type="button" data-toast="Aksi pengingat AI segera tersedia." class="inline-flex items-center gap-2 text-[13.5px] font-semibold text-violet-700 hover:text-violet-900 transition group cursor-pointer">
                                    <span class="w-7 h-7 rounded-full bg-violet-100 group-hover:bg-violet-200 flex items-center justify-center transition">
                                        <x-heroicon-o-arrow-right class="w-3.5 h-3.5" />
                                    </span>
                                    <span>{{ $r['action'] }}</span>
                                </button>
                                <button type="button" title="Tandai selesai" data-reminder-dismiss class="text-slate-400 hover:text-violet-700 transition cursor-pointer">
                                    <x-heroicon-o-check class="w-4 h-4" />
                                </button>
                            </div>
                        </article>
                    @endforeach
                </div>
            </section>

            <section id="project-health-section" class="bg-white rounded-2xl border border-violet-100 shadow-[0_2px_8px_rgba(124,58,237,0.08)] overflow-hidden mb-10 scroll-mt-24">
                <div class="px-7 py-6 flex items-center justify-between border-b border-violet-50">
                    <div>
                        <h2 class="text-[20px] font-bold tracking-tight text-[#1E1B4B]">Project Health</h2>
                        <p class="text-[13px] text-slate-500 mt-1">Status real-time semua proyek aktif</p>
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="relative" data-ph-filter>
                            <button
                                type="button"
                                data-ph-trigger
                                aria-haspopup="listbox"
                                aria-expanded="false"
                                class="inline-flex items-center gap-2 px-3.5 h-9 rounded-lg border border-violet-100 text-[13px] text-slate-600 hover:bg-violet-50 transition cursor-pointer"
                            >
                                <x-heroicon-o-funnel class="w-4 h-4" />
                                <span>Filter:</span>
                                <span data-ph-label class="font-semibold text-violet-700">Semua</span>
                                <x-heroicon-o-chevron-down class="w-3.5 h-3.5" />
                            </button>
                            <div data-ph-menu role="listbox" class="hidden absolute right-0 top-full mt-2 z-20 w-48 bg-white rounded-xl border border-violet-100 shadow-[0_12px_32px_rgba(124,58,237,0.18)] overflow-hidden py-1">
                                <button type="button" data-ph-option="all"       data-ph-label-text="Semua"           role="option" class="w-full text-left px-3 py-2 text-[13px] text-slate-600 hover:bg-violet-50 hover:text-violet-700 transition cursor-pointer">Semua status</button>
                                <button type="button" data-ph-option="on-track"  data-ph-label-text="On Track"        role="option" class="w-full text-left px-3 py-2 text-[13px] text-slate-600 hover:bg-violet-50 hover:text-violet-700 transition cursor-pointer">On Track</button>
                                <button type="button" data-ph-option="attention" data-ph-label-text="Needs Attention" role="option" class="w-full text-left px-3 py-2 text-[13px] text-slate-600 hover:bg-violet-50 hover:text-violet-700 transition cursor-pointer">Needs Attention</button>
                                <button type="button" data-ph-option="critical"  data-ph-label-text="Critical"        role="option" class="w-full text-left px-3 py-2 text-[13px] text-slate-600 hover:bg-violet-50 hover:text-violet-700 transition cursor-pointer">Critical Blocker</button>
                            </div>
                        </div>
                        <button type="button" data-scroll-to="#project-health-section" class="text-[14px] font-semibold text-violet-700 hover:text-violet-900 transition flex items-center gap-1.5 cursor-pointer">
                            View Full Report
                            <x-heroicon-o-arrow-top-right-on-square class="w-4 h-4" />
                        </button>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="text-[11px] font-bold tracking-[0.1em] uppercase text-slate-400 border-b border-violet-50">
                                <th class="px-7 py-4">Nama Proyek</th>
                                <th class="px-4 py-4">Klien</th>
                                <th class="px-4 py-4">SA/Lead</th>
                                <th class="px-4 py-4">Health Status</th>
                                <th class="px-4 py-4 w-[260px]">Progress</th>
                                <th class="px-7 py-4 text-right"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($projects as $p)
                                <tr data-ph-row data-ph-status="{{ $p['status'] }}" onclick="window.location='{{ route('projects.show', $p['id']) }}'" class="hover:bg-[#FAF5FF] border-b border-violet-50/60 last:border-0 transition cursor-pointer">
                                    <td class="px-7 py-5">
                                        <div class="flex items-center gap-3">
                                            <div class="w-10 h-10 rounded-lg flex items-center justify-center text-white font-semibold text-[12px] flex-shrink-0" style="background: {{ $p['color'] }};">
                                                {{ $p['code'] }}
                                            </div>
                                            <div>
                                                <div class="text-[14px] font-semibold text-[#1E1B4B]">{{ $p['name'] }}</div>
                                                <div class="text-[12px] text-slate-500">{{ $p['phase'] }} &middot; Due {{ $p['due'] }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-5 text-[13.5px] text-slate-600">{{ $p['client'] }}</td>
                                    <td class="px-4 py-5">
                                        <div class="flex items-center gap-2">
                                            <div class="w-7 h-7 rounded-full bg-violet-100 text-violet-700 flex items-center justify-center text-[11px] font-bold">{{ $p['lead_initials'] }}</div>
                                            <span class="text-[13.5px] text-slate-600">{{ $p['lead'] }}</span>
                                        </div>
                                    </td>
                                    <td class="px-4 py-5">
                                        <span class="inline-flex items-center gap-1.5 text-[12px] font-semibold rounded-full px-3 py-1.5 {{ $statusPill[$p['status']] }}">
                                            <span class="w-1.5 h-1.5 rounded-full {{ $statusDot[$p['status']] }}"></span>
                                            {{ $p['status_label'] }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-5">
                                        <div class="flex items-center gap-3">
                                            <div class="flex-1 h-2 rounded-full bg-[#F3E8FF] overflow-hidden">
                                                <div class="h-full rounded-full {{ $statusDot[$p['status']] }}" style="width: {{ $p['progress'] }}%"></div>
                                            </div>
                                            <span class="text-[13px] font-semibold text-[#1E1B4B] w-9 text-right tabular-nums">{{ $p['progress'] }}%</span>
                                        </div>
                                    </td>
                                    <td class="px-7 py-5 text-right">
                                        <span class="inline-flex items-center justify-center w-8 h-8 text-slate-300" aria-hidden="true">
                                            <x-heroicon-o-chevron-right class="w-4 h-4" />
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="px-7 py-5 border-t border-violet-50 bg-violet-50/40 grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-lg bg-emerald-100 text-emerald-700 flex items-center justify-center flex-shrink-0">
                            <x-heroicon-o-check-circle class="w-4 h-4" />
                        </div>
                        <div>
                            <div class="text-[13px] text-slate-500">On Track</div>
                            <div class="text-[18px] font-bold text-[#1E1B4B]">{{ $projectStats['onTrack'] }} proyek</div>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-lg bg-amber-100 text-amber-700 flex items-center justify-center flex-shrink-0">
                            <x-heroicon-o-exclamation-triangle class="w-4 h-4" />
                        </div>
                        <div>
                            <div class="text-[13px] text-slate-500">Needs Attention</div>
                            <div class="text-[18px] font-bold text-[#1E1B4B]">{{ $projectStats['attention'] }} proyek</div>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-lg bg-rose-100 text-rose-700 flex items-center justify-center flex-shrink-0">
                            <x-heroicon-o-x-circle class="w-4 h-4" />
                        </div>
                        <div>
                            <div class="text-[13px] text-slate-500">Critical Blocker</div>
                            <div class="text-[18px] font-bold text-[#1E1B4B]">{{ $projectStats['critical'] }} proyek</div>
                        </div>
                    </div>
                </div>
            </section>

        </div>

        <div data-panel="teamLoad" class="hidden">

            <section class="bg-white rounded-2xl border border-violet-100 shadow-[0_2px_8px_rgba(124,58,237,0.08)] p-8 mb-8">
                <div class="flex items-center justify-between mb-7">
                    <div>
                        <h2 class="text-[20px] font-bold tracking-tight text-[#1E1B4B]">Team Load Distribution</h2>
                        <p class="text-[13px] text-slate-500 mt-1">Visualisasi beban kerja real-time tiap anggota tim</p>
                    </div>
                    <div class="relative" data-month-filter>
                        <button
                            type="button"
                            data-month-trigger
                            aria-haspopup="listbox"
                            aria-expanded="false"
                            class="inline-flex items-center gap-2 px-4 h-10 rounded-lg border border-violet-100 text-[13px] text-slate-600 hover:bg-violet-50 transition cursor-pointer"
                        >
                            <x-heroicon-o-calendar class="w-4 h-4" />
                            <span data-month-label>Mei 2026</span>
                            <x-heroicon-o-chevron-down class="w-4 h-4 text-slate-400" />
                        </button>
                        <div data-month-menu role="listbox" class="hidden absolute right-0 top-full mt-1 z-30 w-44 bg-white rounded-xl border border-violet-100 shadow-[0_12px_32px_rgba(124,58,237,0.15)] overflow-hidden">
                            <button type="button" data-month-option="Mei 2026" role="option" class="w-full text-left px-3 py-2 text-[13px] text-slate-600 hover:bg-violet-50 hover:text-violet-700 transition cursor-pointer">Mei 2026</button>
                            <button type="button" data-month-option="Apr 2026" role="option" class="w-full text-left px-3 py-2 text-[13px] text-slate-600 hover:bg-violet-50 hover:text-violet-700 transition cursor-pointer">Apr 2026</button>
                            <button type="button" data-month-option="Mar 2026" role="option" class="w-full text-left px-3 py-2 text-[13px] text-slate-600 hover:bg-violet-50 hover:text-violet-700 transition cursor-pointer">Mar 2026</button>
                        </div>
                    </div>
                </div>

                <div class="space-y-5">
                    @foreach ($teamLoad as $m)
                        @php $ls = $loadStyle($m['load']); @endphp
                        <div class="flex items-center gap-5">
                            <div class="flex items-center gap-3 w-[240px] flex-shrink-0">
                                <div class="w-10 h-10 rounded-full bg-gradient-to-br from-[#7C3AED] via-[#A855F7] to-[#C084FC] text-white flex items-center justify-center font-semibold text-[12px] flex-shrink-0">{{ $m['initials'] }}</div>
                                <div class="min-w-0">
                                    <div class="text-[14px] font-semibold text-[#1E1B4B] flex items-center gap-1.5">
                                        <span class="truncate">{{ $m['name'] }}</span>
                                        @if ($m['sim'])
                                            <span class="text-[10px] font-bold tracking-wide text-violet-500 bg-violet-100 px-1.5 py-0.5 rounded flex-shrink-0">SIM</span>
                                        @endif
                                    </div>
                                    <div class="text-[12px] text-slate-500">{{ $m['role'] }}</div>
                                </div>
                            </div>

                            <div class="flex-1">
                                <div class="flex items-center justify-between mb-1.5">
                                    <span class="text-[12px] text-slate-500">{{ $m['tasks'] }} tasks aktif</span>
                                    <span class="text-[13px] font-bold text-[#1E1B4B] tabular-nums">{{ $m['load'] }}%</span>
                                </div>
                                <div class="h-2 rounded-full bg-[#F3E8FF] overflow-hidden">
                                    <div class="h-full rounded-full {{ $ls['fill'] }}" style="width: {{ $m['load'] }}%"></div>
                                </div>
                            </div>

                            <div class="w-[140px] flex-shrink-0">
                                <span class="inline-flex items-center gap-1.5 text-[11.5px] font-semibold rounded-full px-2.5 py-1 {{ $ls['pill'] }}">
                                    <span class="w-1.5 h-1.5 rounded-full {{ $ls['dot'] }}"></span>
                                    {{ $ls['label'] }}
                                </span>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="mt-8 relative rounded-2xl p-5 bg-gradient-to-br from-violet-50 to-fuchsia-50 border border-violet-200">
                    <div class="flex items-start gap-4">
                        <div class="w-11 h-11 rounded-xl bg-gradient-to-br from-[#7C3AED] via-[#A855F7] to-[#C084FC] text-white flex items-center justify-center flex-shrink-0 shadow-[0_2px_8px_rgba(124,58,237,0.2)]">
                            <x-heroicon-o-sparkles class="w-5 h-5" />
                        </div>
                        <div class="flex-1">
                            <div class="flex items-center gap-2 mb-1">
                                <span class="text-[11px] font-bold tracking-[0.1em] uppercase text-violet-700">AI Workload Alert</span>
                                <span class="w-1 h-1 rounded-full bg-violet-300"></span>
                                <span class="text-[11.5px] text-slate-500">baru saja</span>
                            </div>
                            <h4 class="text-[15px] font-bold text-[#1E1B4B] mb-1">Yuda Prayoga mendekati kapasitas maksimum (90%)</h4>
                            <p class="text-[13px] text-slate-600 leading-relaxed">Saran: distribusi ulang 2-3 task UI review ke sprint berikutnya, atau prioritaskan deliverable kritis dengan diskusi singkat.</p>
                            <div class="mt-3 flex gap-2 flex-wrap" data-workload-actions>
                                <button type="button" data-workload-rebalance class="inline-flex items-center gap-1.5 text-[12.5px] font-semibold px-3 py-1.5 rounded-lg bg-white border border-violet-200 hover:border-violet-400 text-violet-700 transition cursor-pointer">
                                    <x-heroicon-o-arrows-right-left class="w-3.5 h-3.5" />
                                    <span data-workload-rebalance-label>Rebalance Sprint</span>
                                </button>
                                <button type="button" data-workload-acknowledge class="inline-flex items-center gap-1.5 text-[12.5px] font-semibold px-3 py-1.5 rounded-lg text-slate-500 hover:bg-white/70 transition cursor-pointer">
                                    <span data-workload-acknowledge-label>Abaikan</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section class="grid grid-cols-1 md:grid-cols-3 gap-6">
                @foreach ($pools as $pool)
                    <div class="bg-white rounded-2xl border border-violet-100 shadow-[0_2px_8px_rgba(124,58,237,0.08)] p-6">
                        <div class="text-[12px] font-semibold tracking-wider uppercase text-violet-500 mb-2">{{ $pool['label'] }}</div>
                        <div class="text-[32px] font-bold text-[#1E1B4B]">{{ $pool['avg'] }}%</div>
                        <div class="text-[13px] text-slate-500 mt-1">avg load &middot; {{ $pool['count'] }} anggota</div>
                    </div>
                @endforeach
            </section>

        </div>

    <script>
        (function () {
            const wire = () => {
                const tabs   = document.querySelectorAll('.js-tab');
                const panels = document.querySelectorAll('[data-panel]');

                tabs.forEach(t => {
                    t.addEventListener('click', () => {
                        const id = t.dataset.tab;

                        tabs.forEach(x => {
                            const active = x.dataset.tab === id;
                            x.classList.toggle('text-violet-700', active);
                            x.classList.toggle('text-slate-500', !active);
                            x.classList.toggle('hover:text-slate-700', !active);

                            const u = x.querySelector('.js-tab-underline');
                            if (u) {
                                u.classList.toggle('bg-gradient-to-r', active);
                                u.classList.toggle('from-[#7C3AED]', active);
                                u.classList.toggle('via-[#A855F7]', active);
                                u.classList.toggle('to-[#C084FC]', active);
                                u.classList.toggle('bg-transparent', !active);
                            }
                        });

                        panels.forEach(p => {
                            p.classList.toggle('hidden', p.dataset.panel !== id);
                        });
                    });
                });

                /* Reminder "Tandai selesai" — hide card client-side */
                document.querySelectorAll('[data-reminder-dismiss]').forEach(btn => {
                    btn.addEventListener('click', (e) => {
                        e.stopPropagation();
                        const card = btn.closest('[data-reminder-card]');
                        if (card) card.classList.add('hidden');
                        if (window.toast) window.toast('Pengingat ditandai selesai.');
                    });
                });

                /* Scroll-to with flash highlight */
                document.querySelectorAll('[data-scroll-to]').forEach(btn => {
                    btn.addEventListener('click', () => {
                        const target = document.querySelector(btn.dataset.scrollTo);
                        if (! target) return;
                        target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                        target.classList.add('ring-2', 'ring-violet-300', 'ring-offset-2');
                        setTimeout(() => target.classList.remove('ring-2', 'ring-violet-300', 'ring-offset-2'), 1200);
                    });
                });

                /* AI Workload Alert — Rebalance + Abaikan with localStorage state.
                   Per project rule: card stays visible; buttons reflect persisted state and prevent repeat. */
                const WL_REBALANCE_KEY = 'avt-workload-rebalance-requested-at';
                const WL_ACK_KEY       = 'avt-workload-alert-acknowledged';
                const fmtTime = (ts) => {
                    try { return new Date(Number(ts)).toLocaleString('id-ID', { day: '2-digit', month: 'short', hour: '2-digit', minute: '2-digit' }); }
                    catch (e) { return ''; }
                };
                const rebalanceBtn   = document.querySelector('[data-workload-rebalance]');
                const rebalanceLabel = document.querySelector('[data-workload-rebalance-label]');
                const ackBtn         = document.querySelector('[data-workload-acknowledge]');
                const ackLabel       = document.querySelector('[data-workload-acknowledge-label]');

                const markRebalanceDone = (ts) => {
                    if (! rebalanceBtn || ! rebalanceLabel) return;
                    rebalanceBtn.disabled = true;
                    rebalanceBtn.setAttribute('aria-disabled', 'true');
                    rebalanceBtn.title = 'Permintaan rebalance sudah dikirim pada ' + fmtTime(ts);
                    rebalanceBtn.classList.remove('bg-white', 'border-violet-200', 'hover:border-violet-400', 'text-violet-700', 'cursor-pointer');
                    rebalanceBtn.classList.add('bg-violet-50', 'border-violet-100', 'text-violet-500', 'cursor-not-allowed');
                    rebalanceLabel.textContent = 'Sudah Dikirim';
                };
                const markAckDone = (ts) => {
                    if (! ackBtn || ! ackLabel) return;
                    ackBtn.disabled = true;
                    ackBtn.setAttribute('aria-disabled', 'true');
                    ackBtn.title = 'Saran ditandai diabaikan pada ' + fmtTime(ts);
                    ackBtn.classList.remove('hover:bg-white/70', 'cursor-pointer');
                    ackBtn.classList.add('opacity-60', 'cursor-not-allowed');
                    ackLabel.textContent = 'Diabaikan';
                };

                /* Restore persisted state */
                try {
                    const rTs = localStorage.getItem(WL_REBALANCE_KEY);
                    if (rTs) markRebalanceDone(rTs);
                    const aTs = localStorage.getItem(WL_ACK_KEY);
                    if (aTs) markAckDone(aTs);
                } catch (e) {}

                rebalanceBtn?.addEventListener('click', () => {
                    if (rebalanceBtn.disabled) return;
                    const ts = Date.now();
                    try { localStorage.setItem(WL_REBALANCE_KEY, String(ts)); } catch (e) {}
                    markRebalanceDone(ts);
                    if (window.toast) window.toast('Permintaan rebalance dikirim ke SA/QA (demo).');
                });
                ackBtn?.addEventListener('click', () => {
                    if (ackBtn.disabled) return;
                    const ts = Date.now();
                    try { localStorage.setItem(WL_ACK_KEY, String(ts)); } catch (e) {}
                    markAckDone(ts);
                    if (window.toast) window.toast('Saran AI workload ditandai diabaikan.');
                });

                /* Project Health status filter dropdown */
                const phWrap = document.querySelector('[data-ph-filter]');
                if (phWrap) {
                    const phBtn   = phWrap.querySelector('[data-ph-trigger]');
                    const phMenu  = phWrap.querySelector('[data-ph-menu]');
                    const phLabel = phWrap.querySelector('[data-ph-label]');
                    const rows    = document.querySelectorAll('[data-ph-row]');
                    const openPh  = () => { phMenu.classList.remove('hidden'); phBtn.setAttribute('aria-expanded', 'true'); };
                    const closePh = () => { phMenu.classList.add('hidden');    phBtn.setAttribute('aria-expanded', 'false'); };
                    const applyPh = (status, label) => {
                        rows.forEach(r => {
                            const match = status === 'all' || r.dataset.phStatus === status;
                            r.classList.toggle('hidden', ! match);
                        });
                        if (phLabel) phLabel.textContent = label;
                    };
                    phBtn.addEventListener('click', (e) => {
                        e.stopPropagation();
                        phMenu.classList.contains('hidden') ? openPh() : closePh();
                    });
                    phWrap.querySelectorAll('[data-ph-option]').forEach(opt => {
                        opt.addEventListener('click', () => {
                            applyPh(opt.dataset.phOption, opt.dataset.phLabelText);
                            closePh();
                        });
                    });
                    document.addEventListener('click', (e) => {
                        if (! phMenu.classList.contains('hidden') && ! phWrap.contains(e.target)) closePh();
                    });
                    document.addEventListener('keydown', (e) => {
                        if (e.key === 'Escape' && ! phMenu.classList.contains('hidden')) closePh();
                    });
                }

                /* Team Load month filter dropdown */
                const monthWrap = document.querySelector('[data-month-filter]');
                if (monthWrap) {
                    const monthBtn   = monthWrap.querySelector('[data-month-trigger]');
                    const monthMenu  = monthWrap.querySelector('[data-month-menu]');
                    const monthLabel = monthWrap.querySelector('[data-month-label]');
                    const openMonth  = () => { monthMenu.classList.remove('hidden'); monthBtn.setAttribute('aria-expanded', 'true'); };
                    const closeMonth = () => { monthMenu.classList.add('hidden');    monthBtn.setAttribute('aria-expanded', 'false'); };
                    monthBtn.addEventListener('click', (e) => {
                        e.stopPropagation();
                        monthMenu.classList.contains('hidden') ? openMonth() : closeMonth();
                    });
                    monthWrap.querySelectorAll('[data-month-option]').forEach(opt => {
                        opt.addEventListener('click', () => {
                            const v = opt.dataset.monthOption;
                            if (monthLabel) monthLabel.textContent = v;
                            closeMonth();
                            if (window.toast) window.toast('Filter Team Load: ' + v);
                        });
                    });
                    document.addEventListener('click', (e) => {
                        if (! monthMenu.classList.contains('hidden') && ! monthWrap.contains(e.target)) closeMonth();
                    });
                    document.addEventListener('keydown', (e) => {
                        if (e.key === 'Escape' && ! monthMenu.classList.contains('hidden')) closeMonth();
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
