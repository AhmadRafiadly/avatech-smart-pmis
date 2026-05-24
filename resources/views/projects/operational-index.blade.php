@php
    /** @var \Illuminate\Database\Eloquent\Collection $projects */
    $statusPill = [
        'on-track'  => ['pill' => 'bg-emerald-100 text-emerald-700', 'label' => 'On Track'],
        'attention' => ['pill' => 'bg-amber-100 text-amber-700',     'label' => 'Needs Attention'],
        'critical'  => ['pill' => 'bg-rose-100 text-rose-700',       'label' => 'Critical'],
    ];
@endphp

<x-layouts.authenticated :title="$title">

    <section class="flex items-end justify-between mb-8 gap-6 flex-wrap">
        <div>
            <h1 class="text-[40px] leading-[1.05] font-bold tracking-tight text-[#1E1B4B]">
                <span class="bg-clip-text text-transparent bg-gradient-to-r from-[#7C3AED] to-[#A855F7]">Projects</span>
            </h1>
            <p class="mt-3 text-[15px] text-slate-500 max-w-2xl">
                Daftar proyek aktif yang ditugaskan kepada Anda. Buka proyek untuk masuk ke Workspace, QC, atau task Anda.
            </p>
        </div>
        <div class="text-[12.5px] text-slate-500 inline-flex items-center gap-2">
            <span class="w-2 h-2 rounded-full bg-violet-500"></span>
            {{ $projects->count() }} proyek aktif
        </div>
    </section>

    @if ($projects->isEmpty())
        <section class="bg-white rounded-2xl border border-dashed border-violet-200 p-12 text-center">
            <div class="w-14 h-14 mx-auto rounded-2xl bg-violet-50 text-violet-400 flex items-center justify-center mb-3">
                <x-heroicon-o-rectangle-stack class="w-6 h-6" />
            </div>
            <h3 class="text-[16px] font-semibold text-[#1E1B4B]">Belum ada proyek yang ditugaskan</h3>
            <p class="text-[13.5px] text-slate-500 mt-1 max-w-md mx-auto">
                Penugasan proyek dikelola oleh CEO/PM lewat Team Management. Hubungi PM Anda untuk mendapatkan akses proyek.
            </p>
        </section>
    @else
        <section class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
            @foreach ($projects as $p)
                @php
                    $status = $statusPill[$p->status] ?? $statusPill['on-track'];
                    $progress = (int) $p->progress;
                    $progressColor = $progress >= 80 ? '#10B981' : ($progress >= 50 ? '#7C3AED' : '#F59E0B');
                @endphp
                <article class="group bg-white rounded-2xl border border-violet-100 shadow-[0_2px_8px_rgba(124,58,237,0.08)] hover:shadow-[0_8px_24px_rgba(124,58,237,0.12)] transition p-6 flex flex-col relative overflow-hidden">
                    <span class="absolute top-0 left-6 right-6 h-[3px] rounded-b-full" style="background: {{ $progressColor }};"></span>

                    <div class="flex items-start gap-4 mb-5">
                        <div class="w-12 h-12 rounded-xl flex items-center justify-center text-white font-bold text-[13px] shrink-0 shadow-[0_2px_8px_rgba(124,58,237,0.08)]"
                             style="background: {{ $p->color ?: '#7C3AED' }};">
                            {{ $p->code }}
                        </div>
                        <div class="flex-1 min-w-0">
                            <h3 class="text-[16px] font-bold text-[#1E1B4B] leading-tight group-hover:text-violet-700 transition truncate">{{ $p->name }}</h3>
                            <div class="text-[12.5px] text-slate-500 mt-0.5 truncate inline-flex items-center gap-1.5">
                                <x-heroicon-o-building-office class="w-3 h-3" />
                                {{ $p->client?->name ?? '—' }}
                            </div>
                            <div class="flex items-center gap-1.5 flex-wrap mt-2">
                                <span class="inline-flex items-center text-[10.5px] font-bold tracking-wide uppercase px-2 py-1 rounded-md bg-violet-50 text-violet-700">{{ $p->phase ?: 'Planning' }}</span>
                                <span class="inline-flex items-center text-[10.5px] font-bold tracking-wide uppercase px-2 py-1 rounded-md {{ $status['pill'] }}">{{ $status['label'] }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="mb-4">
                        <div class="flex items-center justify-between mb-1.5">
                            <span class="text-[11px] font-semibold uppercase tracking-wider text-slate-500">Progress</span>
                            <span class="text-[13px] font-bold tabular-nums" style="color: {{ $progressColor }};">{{ $progress }}%</span>
                        </div>
                        <div class="h-2 rounded-full bg-[#F3E8FF] overflow-hidden">
                            <div class="h-full rounded-full" style="width: {{ $progress }}%; background: {{ $progressColor }};"></div>
                        </div>
                    </div>

                    <div class="mt-auto pt-4 border-t border-violet-50 grid grid-cols-2 gap-3 text-center mb-4">
                        <div>
                            <div class="text-[18px] font-bold text-[#1E1B4B] tabular-nums">{{ $p->tasks_mine_count }}</div>
                            <div class="text-[10.5px] text-slate-400 font-semibold uppercase tracking-wider mt-0.5">Task Saya</div>
                        </div>
                        <div class="border-l border-violet-50">
                            <div class="text-[18px] font-bold text-[#1E1B4B] tabular-nums">{{ $p->tasks_done_count }}/{{ $p->tasks_count }}</div>
                            <div class="text-[10.5px] text-slate-400 font-semibold uppercase tracking-wider mt-0.5">Done / Total</div>
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        <a
                            href="{{ route('projects.show', $p) }}{{ $actionPresets['anchor'] }}"
                            class="inline-flex items-center gap-2 h-10 px-4 rounded-xl bg-gradient-to-r from-[#7C3AED] via-[#A855F7] to-[#C084FC] text-white font-semibold text-[13px] shadow-[0_2px_8px_rgba(124,58,237,0.2)] hover:scale-[1.02] transition cursor-pointer flex-1 justify-center"
                        >
                            <x-dynamic-component :component="'heroicon-o-' . $actionPresets['icon']" class="w-4 h-4" />
                            {{ $actionPresets['label'] }}
                        </a>
                        <a
                            href="{{ route('projects.show', $p) }}"
                            class="inline-flex items-center justify-center w-10 h-10 rounded-xl border border-violet-100 text-slate-500 hover:border-violet-300 hover:text-violet-700 transition cursor-pointer"
                            title="Buka detail proyek"
                        >
                            <x-heroicon-o-arrow-up-right class="w-4 h-4" />
                        </a>
                    </div>
                </article>
            @endforeach
        </section>
    @endif

</x-layouts.authenticated>
