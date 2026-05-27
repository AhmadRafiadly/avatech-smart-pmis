@php
    $style = function ($severity) {
        return match ($severity) {
            'critical' => ['bar' => '#E11D48', 'badge' => 'bg-rose-50 text-rose-700', 'icon' => 'exclamation-triangle'],
            'warning' => ['bar' => '#D97706', 'badge' => 'bg-amber-50 text-amber-700', 'icon' => 'exclamation-circle'],
            'success' => ['bar' => '#059669', 'badge' => 'bg-emerald-50 text-emerald-700', 'icon' => 'check-circle'],
            default => ['bar' => '#7C3AED', 'badge' => 'bg-violet-50 text-violet-700', 'icon' => 'sparkles'],
        };
    };
@endphp

<x-layouts.authenticated :title="$title">
    <section class="mb-8 flex items-end justify-between gap-5 flex-wrap">
        <div>
            <h1 class="text-[40px] leading-[1.05] font-bold tracking-tight text-[#1E1B4B]">
                Pengingat
                <span class="bg-clip-text text-transparent bg-gradient-to-r from-[#7C3AED] to-[#A855F7]">Cerdas</span>
            </h1>
            <p class="mt-3 text-[15px] text-slate-500 max-w-2xl">
                Daftar saran rule-based dari data proyek, task, QC, workload, dan relasi klien. Semua tindakan tetap manual.
            </p>
        </div>
        <a href="{{ route('executive.index') }}" class="inline-flex items-center gap-2 h-10 px-4 rounded-xl border border-violet-100 bg-white text-[13px] font-semibold text-violet-700 hover:border-violet-300 transition">
            <x-heroicon-o-arrow-left class="w-4 h-4" />
            Executive Monitor
        </a>
    </section>

    <section class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
        @forelse ($insights as $insight)
            @php $s = $style($insight['severity'] ?? 'info'); @endphp
            <article class="relative bg-white rounded-2xl border border-violet-100 shadow-[0_2px_8px_rgba(124,58,237,0.08)] p-6 flex flex-col">
                <span class="absolute left-5 right-5 top-0 h-[3px] rounded-b-[4px]" style="background: {{ $s['bar'] }};"></span>
                <div class="flex items-center justify-between gap-3 mb-4">
                    <span class="inline-flex items-center gap-1.5 text-[10.5px] font-bold tracking-[0.1em] uppercase px-2.5 py-1 rounded-md {{ $s['badge'] }}">
                        <x-dynamic-component :component="'heroicon-o-' . ($insight['icon'] ?? $s['icon'])" class="w-3.5 h-3.5" />
                        {{ $insight['category'] }}
                    </span>
                    <span class="text-[11px] text-slate-400">{{ $insight['time'] }}</span>
                </div>
                <h2 class="text-[17px] font-bold text-[#1E1B4B] leading-tight">{{ $insight['title'] }}</h2>
                <p class="mt-2 text-[13.5px] leading-relaxed text-slate-500 flex-1">{{ $insight['description'] }}</p>
                <div class="mt-4 text-[11px] text-violet-500">{{ $insight['source'] }}</div>
                <div class="mt-5 pt-5 border-t border-violet-50">
                    <a href="{{ $insight['action_url'] }}" class="inline-flex items-center gap-2 text-[13px] font-semibold text-violet-700 hover:text-violet-900">
                        {{ $insight['action_label'] }}
                        <x-heroicon-o-arrow-right class="w-4 h-4" />
                    </a>
                </div>
            </article>
        @empty
            <div class="md:col-span-2 xl:col-span-3 bg-white rounded-2xl border border-dashed border-violet-200 p-12 text-center">
                <div class="w-14 h-14 mx-auto rounded-2xl bg-violet-50 text-violet-400 flex items-center justify-center mb-3">
                    <x-heroicon-o-check-circle class="w-6 h-6" />
                </div>
                <h3 class="text-[15px] font-semibold text-[#1E1B4B]">Belum ada pengingat</h3>
                <p class="text-[13px] text-slate-500 mt-1">Semua indikator utama sedang stabil.</p>
            </div>
        @endforelse
    </section>
</x-layouts.authenticated>
