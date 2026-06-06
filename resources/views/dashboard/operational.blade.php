@php
    /** @var \Illuminate\Database\Eloquent\Collection $projects */
    /** @var \Illuminate\Database\Eloquent\Collection $tasks */
    /** @var \Illuminate\Database\Eloquent\Collection $recentActivities */
    $statusLabel = [
        'planned'     => 'Todo',
        'in_progress' => 'Doing',
        'review'      => 'Review',
        'done'        => 'Done',
        'completed'   => 'Done',
    ];
    $statusPill = [
        'planned'     => 'bg-slate-100 text-slate-600',
        'in_progress' => 'bg-blue-100 text-blue-700',
        'review'      => 'bg-amber-100 text-amber-700',
        'done'        => 'bg-emerald-100 text-emerald-700',
        'completed'   => 'bg-emerald-100 text-emerald-700',
    ];
    $priorityPill = [
        'high'   => 'bg-rose-100 text-rose-700',
        'medium' => 'bg-violet-50 text-violet-700',
        'low'    => 'bg-slate-100 text-slate-600',
    ];
    $insightTone = [
        'violet' => ['bg' => 'bg-violet-50',  'fg' => 'text-violet-700',   'border' => 'border-violet-100'],
        'amber'  => ['bg' => 'bg-amber-50',   'fg' => 'text-amber-700',    'border' => 'border-amber-100'],
        'rose'   => ['bg' => 'bg-rose-50',    'fg' => 'text-rose-700',     'border' => 'border-rose-100'],
        'slate'  => ['bg' => 'bg-slate-50',   'fg' => 'text-slate-600',    'border' => 'border-slate-200'],
    ];
@endphp

<x-layouts.authenticated :title="$title">

    {{-- ============== Greeting + Focus capsule ============== --}}
    <section class="mb-9 flex items-end justify-between gap-6 flex-wrap">
        <div class="min-w-0">
            <div class="inline-flex items-center gap-2 mb-3 flex-wrap">
                <span class="text-[11px] font-bold tracking-[0.18em] uppercase bg-violet-100 text-violet-700 px-2.5 py-1 rounded-md inline-flex items-center gap-1.5">
                    <x-heroicon-o-sun class="w-3 h-3" />
                    {{ $greetingLabel }}
                </span>
                <span class="text-[12px] text-slate-400 inline-flex items-center gap-1.5">
                    <x-heroicon-o-calendar class="w-3 h-3" />
                    {{ $todayLabel }}
                </span>
                <span class="text-[10.5px] font-bold tracking-[0.18em] uppercase bg-violet-50 text-violet-700 px-2.5 py-1 rounded-md">
                    {{ $roleLabel }}
                </span>
            </div>
            <h1 class="text-[40px] leading-[1.05] font-bold tracking-tight text-[#1E1B4B]">
                Selamat Datang,
                <span class="bg-clip-text text-transparent bg-gradient-to-r from-[#7C3AED] to-[#A855F7]">{{ $firstName }}</span>.
            </h1>
            <p class="mt-3 text-[15px] text-slate-500 max-w-xl">{{ $subtitle }}</p>
        </div>

        <a href="{{ $focusHref }}" class="rounded-2xl border border-violet-100 bg-white shadow-[0_2px_8px_rgba(124,58,237,0.08)] hover:shadow-[0_8px_24px_rgba(124,58,237,0.12)] px-5 py-4 flex items-center gap-4 min-w-[260px] transition no-underline">
            <div class="relative">
                <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-[#7C3AED] to-[#A855F7] text-white flex items-center justify-center font-bold text-[14px]">{{ $initials }}</div>
                <span class="absolute -bottom-0.5 -right-0.5 w-3 h-3 rounded-full bg-emerald-500 shadow-[0_0_0_3px_#fff]"></span>
            </div>
            <div class="min-w-0">
                <div class="text-[10.5px] font-bold tracking-wider uppercase text-slate-400">Hari ini</div>
                <div class="text-[15px] font-bold text-[#1E1B4B] leading-tight truncate">{{ $focusLine }}</div>
                <div class="text-[11.5px] text-slate-500 mt-0.5">Fokus utama Anda</div>
            </div>
        </a>
    </section>

    {{-- ============== Summary cards ============== --}}
    <section class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-5 mb-9">
        @foreach ($metrics as $m)
            <a href="{{ $m['href'] }}" class="relative overflow-hidden rounded-2xl border border-violet-100 bg-white shadow-[0_2px_8px_rgba(124,58,237,0.08)] hover:shadow-[0_8px_24px_rgba(124,58,237,0.12)] p-6 transition no-underline">
                <span class="absolute top-0 left-6 right-6 h-[3px] rounded-b-full" style="background: {{ $m['accent'] }};"></span>
                <div class="flex items-start justify-between mb-5">
                    <div class="w-11 h-11 rounded-xl flex items-center justify-center" style="background: {{ $m['tileBg'] }}; color: {{ $m['tileFg'] }};">
                        <x-dynamic-component :component="'heroicon-o-' . $m['icon']" class="w-5 h-5" />
                    </div>
                    <span class="text-[10.5px] font-bold tracking-wider uppercase text-slate-400">{{ $m['label'] }}</span>
                </div>
                <div class="text-[40px] font-bold tracking-tight leading-none text-[#1E1B4B] tabular-nums">{{ $m['value'] }}</div>
                <div class="mt-2.5 text-[12.5px] text-slate-500 inline-flex items-center gap-1.5">
                    <span class="w-1.5 h-1.5 rounded-full" style="background: {{ $m['accent'] }};"></span>
                    <span>{{ $m['sub'] }}</span>
                </div>
            </a>
        @endforeach
    </section>

    {{-- ============== Quick actions ============== --}}
    <section class="mb-9">
        <div class="flex items-center gap-2 mb-4">
            <span class="w-1.5 h-1.5 rounded-full bg-violet-500"></span>
            <h2 class="text-[12px] font-bold tracking-[0.14em] uppercase text-violet-700">Quick Actions</h2>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
            @foreach ($quickActions as $q)
                @php
                    $disabled = ! empty($q['disabled']);
                    $tooltip  = $q['tooltip'] ?? '';
                @endphp
                @if ($disabled)
                    <button
                        type="button"
                        disabled
                        title="{{ $tooltip ?: 'Segera tersedia' }}"
                        class="rounded-2xl border border-violet-100 bg-white shadow-[0_2px_8px_rgba(124,58,237,0.08)] p-5 flex items-start gap-4 opacity-60 cursor-not-allowed text-left"
                    >
                        <div class="w-11 h-11 rounded-xl flex items-center justify-center flex-shrink-0" style="background: {{ $q['tileBg'] }}; color: {{ $q['tileFg'] }};">
                            <x-dynamic-component :component="'heroicon-o-' . $q['icon']" class="w-5 h-5" />
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="text-[14.5px] font-bold text-[#1E1B4B] leading-tight">{{ $q['label'] }}</div>
                            <div class="text-[12px] text-slate-500 mt-1 leading-relaxed">{{ $q['sub'] }}</div>
                            <div class="mt-1.5 text-[10.5px] font-semibold uppercase tracking-wider text-amber-600">Segera tersedia</div>
                        </div>
                    </button>
                @else
                    <a
                        href="{{ $q['href'] }}"
                        class="group rounded-2xl border border-violet-100 bg-white shadow-[0_2px_8px_rgba(124,58,237,0.08)] hover:shadow-[0_8px_24px_rgba(124,58,237,0.12)] hover:-translate-y-0.5 p-5 flex items-start gap-4 transition"
                    >
                        <div class="w-11 h-11 rounded-xl flex items-center justify-center flex-shrink-0" style="background: {{ $q['tileBg'] }}; color: {{ $q['tileFg'] }};">
                            <x-dynamic-component :component="'heroicon-o-' . $q['icon']" class="w-5 h-5" />
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="text-[14.5px] font-bold text-[#1E1B4B] leading-tight group-hover:text-violet-700 transition">{{ $q['label'] }}</div>
                            <div class="text-[12px] text-slate-500 mt-1 leading-relaxed">{{ $q['sub'] }}</div>
                        </div>
                        <x-heroicon-o-arrow-up-right class="w-4 h-4 text-slate-300 group-hover:text-violet-500 transition shrink-0 mt-0.5" />
                    </a>
                @endif
            @endforeach
        </div>
    </section>

    {{-- ============== Proyek Saya + Priority Insight ============== --}}
    <section class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-9">

        <div class="lg:col-span-2 bg-white rounded-2xl border border-violet-100 shadow-[0_2px_8px_rgba(124,58,237,0.08)] overflow-hidden">
            <div class="px-6 pt-6 pb-4 flex items-center justify-between gap-3 flex-wrap">
                <div>
                    <div class="flex items-center gap-2 mb-1">
                        <span class="w-1.5 h-1.5 rounded-full bg-violet-500"></span>
                        <h2 class="text-[12px] font-bold tracking-[0.14em] uppercase text-violet-700">Proyek Saya</h2>
                    </div>
                    <p class="text-[13px] text-slate-500">Proyek aktif yang ditugaskan kepada Anda.</p>
                </div>
                <a href="{{ route('projects.index') }}" class="text-[13px] font-semibold text-violet-700 hover:text-violet-900 inline-flex items-center gap-1.5">
                    Semua Proyek
                    <x-heroicon-o-arrow-right class="w-4 h-4" />
                </a>
            </div>

            <div class="divide-y divide-violet-50">
                @forelse ($projects as $p)
                    <a href="{{ route('projects.show', $p) }}" class="flex items-center gap-5 px-6 py-5 hover:bg-violet-50/40 transition group">
                        <div class="w-12 h-12 rounded-xl flex items-center justify-center text-white font-bold text-[12.5px] shrink-0 shadow-[0_2px_8px_rgba(124,58,237,0.08)]"
                             style="background: {{ $p->color ?: '#7C3AED' }};">
                            {{ $p->code }}
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-2 flex-wrap">
                                <h3 class="text-[15px] font-bold text-[#1E1B4B] leading-tight group-hover:text-violet-700 transition truncate">{{ $p->name }}</h3>
                                <span class="inline-flex items-center text-[10.5px] font-bold tracking-wide uppercase px-2 py-0.5 rounded-md bg-violet-100 text-violet-700">{{ $p->phase ?: 'Planning' }}</span>
                            </div>
                            <div class="mt-1 text-[12.5px] text-slate-500 inline-flex items-center gap-3 flex-wrap">
                                <span class="inline-flex items-center gap-1">
                                    <x-heroicon-o-building-office class="w-3 h-3" />
                                    {{ $p->client?->name ?? '—' }}
                                </span>
                                <span class="inline-flex items-center gap-1">
                                    <x-heroicon-o-list-bullet class="w-3 h-3" />
                                    {{ $p->tasks_done_count }}/{{ $p->tasks_count }} task
                                </span>
                                <span class="inline-flex items-center gap-1">
                                    <x-heroicon-o-clock class="w-3 h-3" />
                                    Update {{ \App\Support\AppTime::diff($p->updated_at) }}
                                </span>
                            </div>
                        </div>
                        <div class="w-[200px] shrink-0 hidden md:block">
                            <div class="flex items-center justify-between mb-1.5">
                                <span class="text-[11px] font-semibold uppercase tracking-wider text-slate-500">{{ ucwords(str_replace('-', ' ', $p->status ?: 'planning')) }}</span>
                                <span class="text-[13px] font-bold text-[#1E1B4B] tabular-nums">{{ (int) $p->progress }}%</span>
                            </div>
                            <div class="h-2 rounded-full bg-[#F3E8FF] overflow-hidden">
                                <div class="h-full rounded-full"
                                     style="width: {{ (int) $p->progress }}%; background: {{ ((int) $p->progress) >= 80 ? '#10B981' : (((int) $p->progress) >= 50 ? '#7C3AED' : '#F59E0B') }};"></div>
                            </div>
                        </div>
                        <div class="w-8 h-8 rounded-lg flex items-center justify-center shrink-0 group-hover:bg-violet-100 transition">
                            <x-heroicon-o-chevron-right class="w-4 h-4 text-slate-400 group-hover:text-violet-700" />
                        </div>
                    </a>
                @empty
                    <div class="px-6 py-12 text-center">
                        <div class="w-14 h-14 mx-auto rounded-2xl bg-violet-50 text-violet-400 flex items-center justify-center mb-3">
                            <x-heroicon-o-rectangle-stack class="w-6 h-6" />
                        </div>
                        <h3 class="text-[15px] font-semibold text-[#1E1B4B]">Belum ada proyek ditugaskan</h3>
                        <p class="text-[13px] text-slate-500 mt-1">Hubungi CEO/PM untuk meminta penugasan proyek.</p>
                    </div>
                @endforelse
            </div>
        </div>

        <aside class="rounded-2xl border border-violet-100 bg-white shadow-[0_2px_8px_rgba(124,58,237,0.08)] p-6 relative overflow-hidden flex flex-col">
            <div class="flex items-center gap-2 mb-1">
                <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-[#7C3AED] to-[#A855F7] text-white flex items-center justify-center shrink-0">
                    <x-heroicon-o-light-bulb class="w-4 h-4" />
                </div>
                <div>
                    <div class="text-[10.5px] font-bold tracking-[0.14em] uppercase text-violet-700">{{ $insightTitle }}</div>
                    <div class="text-[11px] text-slate-500">{{ $insightSubtitle }}</div>
                </div>
            </div>

            <div class="mt-4 space-y-3 flex-1">
                @foreach ($insightItems as $ins)
                    @php $tone = $insightTone[$ins['tone']] ?? $insightTone['slate']; @endphp
                    <a href="{{ $ins['href'] ?? route('projects.index') }}" class="block bg-white border {{ $tone['border'] }} rounded-xl p-4 hover:shadow-sm transition no-underline">
                        <div class="flex items-start gap-3">
                            <div class="w-8 h-8 rounded-lg shrink-0 flex items-center justify-center {{ $tone['bg'] }} {{ $tone['fg'] }}">
                                <x-dynamic-component :component="'heroicon-o-' . $ins['icon']" class="w-4 h-4" />
                            </div>
                            <p class="text-[13px] text-[#1E1B4B] leading-snug font-medium">{{ $ins['text'] }}</p>
                        </div>
                    </a>
                @endforeach
            </div>

            <p class="mt-4 text-[11px] text-slate-400 italic leading-relaxed">
                Insight ini berasal dari data sistem (task, due date, penugasan). Belum melibatkan AI.
            </p>
        </aside>
    </section>

    {{-- ============== Aktivitas Terakhir ============== --}}
    <section class="bg-white rounded-2xl border border-violet-100 shadow-[0_2px_8px_rgba(124,58,237,0.08)] overflow-hidden">
        <div class="px-6 pt-6 pb-4 flex items-center justify-between flex-wrap gap-3">
            <div>
                <div class="flex items-center gap-2 mb-1">
                    <span class="w-1.5 h-1.5 rounded-full bg-violet-500"></span>
                    <h2 class="text-[12px] font-bold tracking-[0.14em] uppercase text-violet-700">Aktivitas Terakhir</h2>
                </div>
                <p class="text-[13px] text-slate-500">Riwayat singkat aksi Anda di sistem.</p>
            </div>
            <a href="{{ route('audit.index') }}?actor={{ urlencode($currentUser->name) }}"
               class="text-[13px] font-semibold text-violet-700 hover:text-violet-900 inline-flex items-center gap-1.5">
                Buka riwayat lengkap
                <x-heroicon-o-clock class="w-4 h-4" />
            </a>
        </div>

        <div class="divide-y divide-violet-50">
            @forelse ($recentActivities as $a)
                <a href="{{ $a['href'] }}" class="flex items-start gap-4 px-6 py-4 hover:bg-violet-50/40 transition no-underline">
                    <div class="w-9 h-9 rounded-lg flex items-center justify-center shrink-0 bg-violet-50 text-violet-600">
                        <x-heroicon-o-bolt class="w-4 h-4" />
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-[13.5px] text-[#1E1B4B] leading-snug">{!! $a['description'] !!}</p>
                        <div class="text-[11.5px] text-slate-400 mt-1 inline-flex items-center gap-2 flex-wrap">
                            <span>{{ $a['module'] }}</span>
                            <span class="w-1 h-1 rounded-full bg-slate-300"></span>
                            <span>{{ $a['time'] }}</span>
                        </div>
                    </div>
                </a>
            @empty
                <div class="px-6 py-12 text-center">
                    <div class="w-14 h-14 mx-auto rounded-2xl bg-violet-50 text-violet-400 flex items-center justify-center mb-3">
                        <x-heroicon-o-inbox class="w-6 h-6" />
                    </div>
                    <h3 class="text-[15px] font-semibold text-[#1E1B4B]">Belum ada aktivitas</h3>
                    <p class="text-[13px] text-slate-500 mt-1">Aksi Anda di proyek dan task akan tercatat di sini.</p>
                </div>
            @endforelse
        </div>
    </section>

</x-layouts.authenticated>
