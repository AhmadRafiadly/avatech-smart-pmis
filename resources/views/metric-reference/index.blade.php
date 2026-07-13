@php
    $metrics = [
        [
            'name' => 'Project Progress',
            'formula' => '(Total estimasi jam task selesai / Total estimasi jam semua task) × 100%. Jika estimasi kosong: (Jumlah task selesai / Jumlah seluruh task) × 100%.',
            'explanation' => 'Menunjukkan persentase penyelesaian proyek berdasarkan bobot pekerjaan yang sudah selesai.',
            'source' => 'projects, project_tasks',
            'appears' => 'Dashboard, Executive Monitor, Project Detail',
        ],
        [
            'name' => 'QC Pass Rate',
            'formula' => '(Jumlah test case passed / Total test case) × 100%',
            'explanation' => 'Mengukur rasio skenario QC yang sudah lulus dibanding seluruh test case proyek.',
            'source' => 'project_qc_tests',
            'appears' => 'Project Detail - Quality Control, Dashboard Ringkasan QC',
        ],
        [
            'name' => 'Team Load',
            'formula' => '(Total estimasi jam task aktif user / Kapasitas jam user) × 100%',
            'explanation' => 'Membantu membaca beban kerja anggota tim dari task aktif yang masih berjalan.',
            'source' => 'users, project_tasks, team_assignments',
            'appears' => 'Team Page, Executive Monitor, Dashboard Operasional',
        ],
        [
            'name' => 'Overdue Task',
            'formula' => 'Task belum done dan due date < tanggal hari ini',
            'explanation' => 'Menghitung pekerjaan yang melewati tenggat dan belum selesai.',
            'source' => 'project_tasks',
            'appears' => 'Dashboard, Project Detail, Smart Insights',
        ],
        [
            'name' => 'AI Success Rate',
            'formula' => '(Jumlah request AI sukses / Total request AI) × 100%',
            'explanation' => 'Menunjukkan stabilitas eksekusi permintaan AI yang berhasil diproses.',
            'source' => 'ai_request_logs',
            'appears' => 'AI Monitor',
        ],
        [
            'name' => 'Fallback Count',
            'formula' => 'Jumlah request AI yang berpindah dari provider utama ke provider berikutnya',
            'explanation' => 'Mencatat berapa kali sistem memakai provider cadangan saat provider utama gagal atau tidak tersedia.',
            'source' => 'ai_request_logs metadata',
            'appears' => 'AI Monitor, Fallback Diagnostic',
        ],
        [
            'name' => 'AI Latency',
            'formula' => 'Waktu selesai request - waktu mulai request',
            'explanation' => 'Mengukur durasi respons AI untuk membantu diagnosa performa provider.',
            'source' => 'ai_request_logs',
            'appears' => 'AI Monitor',
        ],
        [
            'name' => 'System Health',
            'formula' => 'Status environment, database, storage link, cache, PDF export, dan konfigurasi AI provider',
            'explanation' => 'Ringkasan kesiapan teknis sistem dari pemeriksaan konfigurasi dan dependensi runtime.',
            'source' => '.env, config, database connection, storage, cache',
            'appears' => 'System Health, Settings / Support',
        ],
    ];
@endphp

<x-layouts.authenticated :title="$title">
    <section class="mb-8 flex items-end justify-between gap-5 flex-wrap">
        <div>
            <div class="text-[11px] font-bold tracking-[0.22em] uppercase text-violet-500 mb-3">Support Utility</div>
            <h1 class="text-[42px] leading-[1.05] font-bold tracking-tight text-[#1E1B4B]">
                Metric
                <span class="bg-clip-text text-transparent bg-gradient-to-r from-[#7C3AED] to-[#A855F7]">Reference</span>
            </h1>
            <p class="mt-3 text-[15px] text-slate-500 max-w-2xl">
                Referensi read-only untuk menjelaskan rumus, sumber data, dan lokasi kemunculan metric utama di Smart-PMIS.
            </p>
        </div>
        <a href="{{ route('settings.index') }}" class="inline-flex items-center gap-2 h-10 px-4 rounded-xl border border-violet-100 bg-white text-[13px] font-semibold text-violet-700 hover:border-violet-300 transition">
            <x-heroicon-o-cog-6-tooth class="w-4 h-4" />
            Settings / Support
        </a>
    </section>

    <section class="grid grid-cols-1 xl:grid-cols-2 gap-5 mb-8">
        @foreach ($metrics as $metric)
            <article class="bg-white rounded-2xl border border-violet-100 shadow-[0_2px_8px_rgba(124,58,237,0.08)] p-6">
                <div class="flex items-start justify-between gap-4 mb-4">
                    <div>
                        <div class="text-[11px] font-bold tracking-wider uppercase text-violet-500 mb-1">Metric</div>
                        <h2 class="text-[18px] font-bold text-[#1E1B4B]">{{ $metric['name'] }}</h2>
                    </div>
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-slate-100 text-slate-500 text-[11px] font-bold tracking-wider uppercase">
                        <x-heroicon-o-lock-closed class="w-3.5 h-3.5" /> Read-only
                    </span>
                </div>
                <div class="space-y-3 text-[13px] text-slate-600 leading-relaxed">
                    <div>
                        <div class="text-[11px] font-bold tracking-wider uppercase text-slate-400 mb-1">Formula</div>
                        <p class="font-semibold text-[#1E1B4B]">{{ $metric['formula'] }}</p>
                    </div>
                    <div>
                        <div class="text-[11px] font-bold tracking-wider uppercase text-slate-400 mb-1">Penjelasan</div>
                        <p>{{ $metric['explanation'] }}</p>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3 pt-2">
                        <div class="rounded-xl bg-violet-50/60 border border-violet-100 p-3">
                            <div class="text-[11px] font-bold tracking-wider uppercase text-violet-500 mb-1">Sumber Data</div>
                            <p class="text-[12.5px] text-[#1E1B4B] font-semibold">{{ $metric['source'] }}</p>
                        </div>
                        <div class="rounded-xl bg-violet-50/60 border border-violet-100 p-3">
                            <div class="text-[11px] font-bold tracking-wider uppercase text-violet-500 mb-1">Muncul Di</div>
                            <p class="text-[12.5px] text-[#1E1B4B] font-semibold">{{ $metric['appears'] }}</p>
                        </div>
                    </div>
                </div>
            </article>
        @endforeach
    </section>
</x-layouts.authenticated>
