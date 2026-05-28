@php
    $statusStyle = function ($status) {
        return match ($status) {
            'ready' => ['badge' => 'bg-emerald-50 text-emerald-700', 'bar' => '#059669', 'label' => 'Siap'],
            'critical' => ['badge' => 'bg-rose-50 text-rose-700', 'bar' => '#E11D48', 'label' => 'Perlu Perhatian'],
            'warning' => ['badge' => 'bg-amber-50 text-amber-700', 'bar' => '#D97706', 'label' => 'Perlu Perhatian'],
            default => ['badge' => 'bg-violet-50 text-violet-700', 'bar' => '#7C3AED', 'label' => 'Info'],
        };
    };
@endphp

<x-layouts.authenticated :title="$title">
    <section class="mb-8 flex items-end justify-between gap-5 flex-wrap">
        <div>
            <h1 class="text-[42px] leading-[1.05] font-bold tracking-tight text-[#1E1B4B]">
                System
                <span class="bg-clip-text text-transparent bg-gradient-to-r from-[#7C3AED] to-[#A855F7]">Health</span>
            </h1>
            <p class="mt-3 text-[15px] text-slate-500 max-w-2xl">
                Pantau kesiapan komponen utama Smart-PMIS sebelum demo, deployment, atau pengujian.
            </p>
        </div>
        <div class="flex gap-2 flex-wrap">
            <a href="{{ route('ai-monitor.index') }}" class="inline-flex items-center gap-2 h-10 px-4 rounded-xl border border-violet-100 bg-white text-[13px] font-semibold text-violet-700 hover:border-violet-300 transition">
                <x-heroicon-o-cpu-chip class="w-4 h-4" />
                AI Monitor
            </a>
            <a href="{{ route('settings.index') }}" class="inline-flex items-center gap-2 h-10 px-4 rounded-xl border border-violet-100 bg-white text-[13px] font-semibold text-violet-700 hover:border-violet-300 transition">
                <x-heroicon-o-cog-6-tooth class="w-4 h-4" />
                Settings
            </a>
        </div>
    </section>

    <section class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5 mb-8">
        @foreach ($checks as $check)
            @php $style = $statusStyle($check['status']); @endphp
            <article class="relative overflow-hidden bg-white rounded-2xl border border-violet-100 shadow-[0_2px_8px_rgba(124,58,237,0.08)] p-6">
                <span class="absolute left-5 right-5 top-0 h-[3px] rounded-b-[4px]" style="background: {{ $style['bar'] }};"></span>
                <div class="flex items-start justify-between gap-4">
                    <div class="w-11 h-11 rounded-xl bg-violet-50 text-violet-700 flex items-center justify-center flex-shrink-0">
                        <x-dynamic-component :component="'heroicon-o-' . $check['icon']" class="w-5 h-5" />
                    </div>
                    <span class="inline-flex px-2.5 py-1 rounded-full text-[10.5px] font-bold tracking-wider uppercase {{ $style['badge'] }}">
                        {{ $style['label'] }}
                    </span>
                </div>
                <h2 class="mt-4 text-[16px] font-bold text-[#1E1B4B]">{{ $check['label'] }}</h2>
                <div class="mt-1 text-[13px] font-semibold text-violet-700">{{ $check['value'] }}</div>
                <p class="mt-2 text-[12.5px] leading-relaxed text-slate-500">{{ $check['description'] }}</p>
            </article>
        @endforeach
    </section>

    <section class="grid grid-cols-1 xl:grid-cols-[1.1fr_0.9fr] gap-6 mb-8">
        <article class="bg-white rounded-2xl border border-violet-100 shadow-[0_2px_8px_rgba(124,58,237,0.08)] p-6">
            <div class="flex items-start justify-between gap-4 mb-5">
                <div>
                    <h2 class="text-[18px] font-bold text-[#1E1B4B]">Matriks Akses Role</h2>
                    <p class="mt-1 text-[12.5px] text-slate-500">Ringkasan akses yang sudah diimplementasikan pada Smart-PMIS.</p>
                </div>
                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-violet-50 text-violet-700 text-[10.5px] font-bold tracking-wider uppercase">
                    Informasional
                </span>
            </div>
            <div class="space-y-3">
                @foreach ($roleMatrix as $row)
                    <div class="rounded-xl border border-violet-50 bg-violet-50/25 p-4">
                        <div class="text-[13.5px] font-bold text-[#1E1B4B]">{{ $row['role'] }}</div>
                        <div class="mt-2 flex flex-wrap gap-1.5">
                            @foreach ($row['access'] as $access)
                                <span class="inline-flex px-2.5 py-1 rounded-lg bg-white border border-violet-100 text-[11.5px] font-semibold text-slate-600">{{ $access }}</span>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
            <p class="mt-4 text-[12px] text-slate-500 italic">
                Matriks ini bersifat ringkasan akses. Validasi akses tetap dikendalikan oleh middleware dan controller guard.
            </p>
        </article>

        <div class="space-y-6">
            <article class="bg-white rounded-2xl border border-violet-100 shadow-[0_2px_8px_rgba(124,58,237,0.08)] p-6">
                <h2 class="text-[18px] font-bold text-[#1E1B4B]">Keamanan Data AI</h2>
                <div class="mt-4 space-y-3">
                    @foreach ($safetyNotes as $note)
                        <div class="flex items-start gap-2.5 text-[12.5px] text-slate-600">
                            <x-heroicon-o-shield-check class="w-4 h-4 text-violet-600 flex-shrink-0 mt-0.5" />
                            <span>{{ $note }}</span>
                        </div>
                    @endforeach
                </div>
            </article>

            <article class="bg-white rounded-2xl border border-violet-100 shadow-[0_2px_8px_rgba(124,58,237,0.08)] p-6">
                <h2 class="text-[18px] font-bold text-[#1E1B4B]">Info Deployment</h2>
                <div class="mt-4 divide-y divide-violet-50">
                    @foreach ($appInfo as $row)
                        <div class="py-2.5 flex items-center justify-between gap-4">
                            <span class="text-[12px] font-semibold text-slate-500">{{ $row['label'] }}</span>
                            <span class="text-[12.5px] font-bold text-[#1E1B4B] text-right">{{ $row['value'] }}</span>
                        </div>
                    @endforeach
                </div>
            </article>
        </div>
    </section>

    <section class="bg-violet-50/70 rounded-2xl border border-violet-200 p-6 mb-8">
        <div class="flex items-start gap-3">
            <x-heroicon-o-information-circle class="w-5 h-5 text-violet-700 mt-0.5 flex-shrink-0" />
            <p class="text-[12.5px] leading-relaxed text-violet-800">
                System Health adalah monitoring lokal ringan. Halaman ini tidak menjalankan migrasi otomatis, tidak memanggil quota provider eksternal, dan tidak menampilkan secrets.
            </p>
        </div>
    </section>
</x-layouts.authenticated>
