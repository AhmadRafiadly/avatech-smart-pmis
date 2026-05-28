@php
    $statusClass = fn ($status) => $status === 'success'
        ? 'bg-emerald-50 text-emerald-700'
        : 'bg-rose-50 text-rose-700';
@endphp

<x-layouts.authenticated :title="$title">
    <section class="mb-8 flex items-end justify-between gap-5 flex-wrap">
        <div>
            <h1 class="text-[42px] leading-[1.05] font-bold tracking-tight text-[#1E1B4B]">
                AI
                <span class="bg-clip-text text-transparent bg-gradient-to-r from-[#7C3AED] to-[#A855F7]">Monitor</span>
            </h1>
            <p class="mt-3 text-[15px] text-slate-500 max-w-2xl">
                Pantau penggunaan, performa, dan fallback layanan AI pada Smart-PMIS.
            </p>
        </div>
        <a href="{{ route('settings.index', ['tab' => 'ai']) }}" class="inline-flex items-center gap-2 h-10 px-4 rounded-xl border border-violet-100 bg-white text-[13px] font-semibold text-violet-700 hover:border-violet-300 transition">
            <x-heroicon-o-cog-6-tooth class="w-4 h-4" />
            Sekretaris AI
        </a>
    </section>

    <section class="grid grid-cols-1 lg:grid-cols-3 gap-5 mb-8">
        @foreach ($providers as $provider)
            <article class="bg-white rounded-2xl border border-violet-100 shadow-[0_2px_8px_rgba(124,58,237,0.08)] p-6">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <div class="text-[11px] font-bold tracking-[0.14em] uppercase text-violet-500">{{ $provider['role'] }}</div>
                        <h2 class="mt-1 text-[18px] font-bold text-[#1E1B4B]">{{ $provider['label'] }}</h2>
                    </div>
                    @if ($provider['configured'])
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-emerald-50 text-emerald-700 text-[10.5px] font-bold tracking-wider uppercase">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                            Provider Aktif
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-slate-100 text-slate-500 text-[10.5px] font-bold tracking-wider uppercase">
                            <span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span>
                            AI belum dikonfigurasi
                        </span>
                    @endif
                </div>
                <div class="mt-5 text-[11px] font-bold tracking-wider uppercase text-slate-400">Model Aktif</div>
                <div class="mt-1 font-mono text-[12px] text-slate-600 break-all">{{ $provider['model'] ?: 'Tidak tersedia' }}</div>
            </article>
        @endforeach
    </section>

    <section class="grid grid-cols-2 xl:grid-cols-6 gap-5 mb-8">
        @foreach ([
            ['label' => 'Penggunaan Hari Ini', 'value' => $summary['today_calls'], 'icon' => 'bolt'],
            ['label' => 'Penggunaan Bulan Ini', 'value' => $summary['month_calls'], 'icon' => 'calendar-days'],
            ['label' => 'Rasio Sukses', 'value' => $summary['success_rate'] . '%', 'icon' => 'check-circle'],
            ['label' => 'Permintaan Gagal', 'value' => $summary['failed_requests'], 'icon' => 'exclamation-triangle'],
            ['label' => 'Latensi Rata-rata', 'value' => $summary['average_latency'] ? number_format($summary['average_latency']) . ' ms' : '-', 'icon' => 'clock'],
            ['label' => 'Fallback Terjadi', 'value' => $summary['fallback_events'], 'icon' => 'arrow-path'],
        ] as $metric)
            <article class="bg-white rounded-2xl border border-violet-100 shadow-[0_2px_8px_rgba(124,58,237,0.08)] p-5">
                <div class="w-10 h-10 rounded-xl bg-violet-50 text-violet-700 flex items-center justify-center mb-4">
                    <x-dynamic-component :component="'heroicon-o-' . $metric['icon']" class="w-5 h-5" />
                </div>
                <div class="text-[28px] leading-none font-bold text-[#1E1B4B]">{{ $metric['value'] }}</div>
                <div class="mt-2 text-[12px] text-slate-500">{{ $metric['label'] }}</div>
            </article>
        @endforeach
    </section>

    <section class="grid grid-cols-1 xl:grid-cols-2 gap-6 mb-8">
        <article class="bg-white rounded-2xl border border-violet-100 shadow-[0_2px_8px_rgba(124,58,237,0.08)] p-6">
            <h2 class="text-[17px] font-bold text-[#1E1B4B] mb-5">Penggunaan per Fitur</h2>
            <div class="space-y-3">
                @foreach ($featureUsage as $row)
                    <div class="flex items-center justify-between gap-4 rounded-xl bg-slate-50 px-4 py-3">
                        <div>
                            <div class="text-[13px] font-semibold text-[#1E1B4B]">{{ $row['label'] }}</div>
                            <div class="text-[11.5px] text-slate-500">{{ $row['success'] }} berhasil · {{ $row['failed'] }} gagal</div>
                        </div>
                        <div class="text-[20px] font-bold text-violet-700">{{ $row['count'] }}</div>
                    </div>
                @endforeach
            </div>
        </article>

        <article class="bg-white rounded-2xl border border-violet-100 shadow-[0_2px_8px_rgba(124,58,237,0.08)] p-6">
            <h2 class="text-[17px] font-bold text-[#1E1B4B] mb-5">Penggunaan per Provider</h2>
            <div class="space-y-3">
                @foreach ($providerUsage as $row)
                    <div class="flex items-center justify-between gap-4 rounded-xl bg-slate-50 px-4 py-3">
                        <div>
                            <div class="text-[13px] font-semibold text-[#1E1B4B]">{{ $row['label'] }}</div>
                            <div class="text-[11.5px] text-slate-500">{{ $row['success'] }} berhasil · {{ $row['failed'] }} gagal</div>
                        </div>
                        <div class="text-[20px] font-bold text-violet-700">{{ $row['count'] }}</div>
                    </div>
                @endforeach
            </div>
        </article>
    </section>

    <section class="grid grid-cols-1 xl:grid-cols-[1fr_360px] gap-6 mb-8">
        <article class="bg-white rounded-2xl border border-violet-100 shadow-[0_2px_8px_rgba(124,58,237,0.08)] overflow-hidden">
            <div class="p-6 border-b border-violet-50">
                <h2 class="text-[17px] font-bold text-[#1E1B4B]">Riwayat Pemrosesan AI</h2>
                <p class="mt-1 text-[12.5px] text-slate-500">Metadata aman dari pemrosesan AI terbaru. Prompt dan respons penuh tidak disimpan.</p>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead class="bg-slate-50 text-[11px] uppercase tracking-wider text-slate-400">
                        <tr>
                            <th class="px-5 py-3">Waktu</th>
                            <th class="px-5 py-3">Feature</th>
                            <th class="px-5 py-3">Provider</th>
                            <th class="px-5 py-3">Status</th>
                            <th class="px-5 py-3">Latensi</th>
                            <th class="px-5 py-3">User</th>
                            <th class="px-5 py-3">Konteks</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-violet-50 text-[12.5px]">
                        @forelse ($recentLogs as $log)
                            <tr class="align-top">
                                <td class="px-5 py-4 text-slate-500 whitespace-nowrap">{{ $log['time'] }}</td>
                                <td class="px-5 py-4 font-semibold text-[#1E1B4B]">{{ $log['feature'] }}</td>
                                <td class="px-5 py-4">
                                    <div class="font-semibold text-slate-700">{{ $log['provider'] }}</div>
                                    <div class="text-[11px] text-slate-400">{{ $log['model'] }}</div>
                                </td>
                                <td class="px-5 py-4">
                                    <span class="inline-flex px-2 py-0.5 rounded-md text-[10.5px] font-bold uppercase {{ $statusClass($log['status_key']) }}">{{ $log['status'] }}</span>
                                    @if ($log['error'])
                                        <div class="mt-1 max-w-[240px] text-[11px] text-rose-600">{{ $log['error'] }}</div>
                                    @endif
                                </td>
                                <td class="px-5 py-4 text-slate-600 whitespace-nowrap">{{ $log['latency'] }}</td>
                                <td class="px-5 py-4 text-slate-600 whitespace-nowrap">{{ $log['user'] }}</td>
                                <td class="px-5 py-4 text-slate-600 min-w-[160px]">{{ $log['related'] }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-5 py-12 text-center text-slate-500">Belum ada riwayat pemrosesan AI.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </article>

        <aside class="space-y-6">
            <article class="bg-white rounded-2xl border border-violet-100 shadow-[0_2px_8px_rgba(124,58,237,0.08)] p-6">
                <div class="text-[11px] font-bold tracking-[0.14em] uppercase text-violet-500">Fallback Terakhir</div>
                @if ($lastFallback)
                    <h2 class="mt-2 text-[17px] font-bold text-[#1E1B4B]">{{ $lastFallback['feature'] }}</h2>
                    <p class="mt-2 text-[13px] text-slate-500">{{ $lastFallback['path'] }}</p>
                    <p class="mt-3 text-[12.5px] text-slate-500">{{ $lastFallback['reason'] }}</p>
                    <div class="mt-4 text-[11px] text-slate-400">{{ $lastFallback['time'] }}</div>
                @else
                    <h2 class="mt-2 text-[17px] font-bold text-[#1E1B4B]">Belum ada fallback</h2>
                    <p class="mt-2 text-[13px] text-slate-500">Provider utama masih berjalan normal atau belum ada pemrosesan AI pada periode ini.</p>
                @endif
            </article>

            <article class="bg-violet-50/70 rounded-2xl border border-violet-200 p-6">
                <div class="flex items-start gap-3">
                    <x-heroicon-o-information-circle class="w-5 h-5 text-violet-700 mt-0.5 flex-shrink-0" />
                    <p class="text-[12.5px] leading-relaxed text-violet-800">
                        Angka penggunaan pada halaman ini berasal dari log lokal Smart-PMIS. Sisa kuota resmi tetap mengacu pada dashboard masing-masing provider.
                    </p>
                </div>
            </article>
        </aside>
    </section>
</x-layouts.authenticated>
