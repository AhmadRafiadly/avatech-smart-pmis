@php
    $statusStyle = function ($status) {
        return match (mb_strtolower(trim((string) $status))) {
            'lulus', 'diterima', 'valid' => ['badge' => 'bg-emerald-50 text-emerald-700', 'bar' => '#059669'],
            default => ['badge' => 'bg-violet-50 text-violet-700', 'bar' => '#7C3AED'],
        };
    };
@endphp

<x-layouts.authenticated :title="$title">
    <section class="mb-8 flex items-end justify-between gap-5 flex-wrap">
        <div>
            <div class="text-[11px] font-bold tracking-[0.22em] uppercase text-violet-500 mb-3">Support Utility</div>
            <h1 class="text-[42px] leading-[1.05] font-bold tracking-tight text-[#1E1B4B]">
                QA
                <span class="bg-clip-text text-transparent bg-gradient-to-r from-[#7C3AED] to-[#A855F7]">Evidence</span>
            </h1>
            <p class="mt-3 text-[15px] text-slate-500 max-w-2xl">
                QA Evidence menampilkan ringkasan bukti pengujian sistem. Detail skenario dan hasil pengujian tetap dikelola pada dokumen pengujian dan lampiran.
            </p>
        </div>
        <div class="flex gap-2 flex-wrap">
            <a href="{{ route('system-health.index') }}" class="inline-flex items-center gap-2 h-10 px-4 rounded-xl border border-violet-100 bg-white text-[13px] font-semibold text-violet-700 hover:border-violet-300 transition">
                <x-heroicon-o-server-stack class="w-4 h-4" />
                System Health
            </a>
            <a href="{{ route('audit.index') }}" class="inline-flex items-center gap-2 h-10 px-4 rounded-xl border border-violet-100 bg-white text-[13px] font-semibold text-violet-700 hover:border-violet-300 transition">
                <x-heroicon-o-clipboard-document-list class="w-4 h-4" />
                Audit Trail
            </a>
        </div>
    </section>

    <section class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-5 mb-8">
        @foreach ($summaries as $sum)
            @php
                $style = $statusStyle(($sum['failed'] ?? 0) > 0 ? 'review' : 'lulus');
            @endphp
            <article class="relative overflow-hidden bg-white rounded-2xl border border-violet-100 shadow-[0_2px_8px_rgba(124,58,237,0.08)] p-6">
                <span class="absolute left-5 right-5 top-0 h-[3px] rounded-b-[4px]" style="background: {{ $style['bar'] }};"></span>
                <div class="text-[12px] font-semibold text-slate-500 uppercase tracking-wider">{{ $sum['category'] }}</div>
                <div class="mt-3 text-[28px] font-bold text-[#1E1B4B]">{{ $sum['passed'] }}/{{ $sum['total'] }}</div>
                <p class="mt-1 text-[12.5px] text-slate-500">{{ $sum['passed'] }}/{{ $sum['total'] }} {{ $sum['status'] }} · {{ $sum['failed'] }} gagal</p>
            </article>
        @endforeach
    </section>

    @if ($canManage)
        <section class="bg-white rounded-2xl border border-violet-100 p-6 mb-8">
            <h2 class="text-[18px] font-bold text-[#1E1B4B] mb-4">Tambah QA Evidence</h2>
            <form method="POST" action="{{ route('testing-evidence.store') }}" enctype="multipart/form-data" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
                @csrf
                <select name="category" required class="h-10 rounded-xl border border-violet-100 px-3 text-[13px]">
                    @foreach ($categories as $category)<option value="{{ $category }}">{{ $category }}</option>@endforeach
                </select>
                <input name="title" required maxlength="255" placeholder="Judul bukti" class="h-10 rounded-xl border border-violet-100 px-3 text-[13px]">
                <input name="result_status" required maxlength="50" placeholder="Status hasil" class="h-10 rounded-xl border border-violet-100 px-3 text-[13px]">
                <input type="date" name="tested_at" required class="h-10 rounded-xl border border-violet-100 px-3 text-[13px]">
                <input type="number" name="total_scenarios" min="0" required placeholder="Total skenario" class="h-10 rounded-xl border border-violet-100 px-3 text-[13px]">
                <input type="number" name="passed_scenarios" min="0" required placeholder="Lulus" class="h-10 rounded-xl border border-violet-100 px-3 text-[13px]">
                <input type="number" name="failed_scenarios" min="0" required placeholder="Gagal" class="h-10 rounded-xl border border-violet-100 px-3 text-[13px]">
                <input type="file" name="file" class="h-10 rounded-xl border border-violet-100 px-3 py-2 text-[12px]">
                <input type="url" name="evidence_url" maxlength="1000" placeholder="URL bukti (opsional)" class="h-10 rounded-xl border border-violet-100 px-3 text-[13px] xl:col-span-2">
                <textarea name="notes" maxlength="5000" placeholder="Catatan (opsional)" class="rounded-xl border border-violet-100 px-3 py-2 text-[13px] xl:col-span-2"></textarea>
                <button class="h-10 px-4 rounded-xl bg-violet-600 text-white text-[13px] font-semibold">Simpan Evidence</button>
            </form>
        </section>
    @endif

    <section class="grid grid-cols-1 gap-6 mb-8">
        <article class="bg-white rounded-2xl border border-violet-100 shadow-[0_2px_8px_rgba(124,58,237,0.08)] overflow-hidden">
            <div class="px-6 py-5 border-b border-violet-100/70 bg-violet-50/40">
                <h2 class="text-[18px] font-bold text-[#1E1B4B]">Ringkasan QA Evidence</h2>
                <p class="mt-1 text-[12.5px] text-slate-500">Bukti pengujian dikelola oleh peran managerial dan QA.</p>
            </div>
            <div class="divide-y divide-violet-100/60">
                @forelse ($evidences as $ev)
                    @php $style = $statusStyle($ev['result_status']); @endphp
                    <div class="p-6">
                        <div class="flex items-start justify-between gap-4">
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-2 mb-2">
                                    <span class="inline-flex px-2.5 py-1 rounded-full text-[10.5px] font-bold tracking-wider uppercase bg-violet-50 text-violet-700">{{ $ev['category'] }}</span>
                                    <span class="inline-flex px-2.5 py-1 rounded-full text-[10.5px] font-bold tracking-wider uppercase {{ $style['badge'] }}">{{ $ev['result_status'] }}</span>
                                </div>
                                <h3 class="text-[16px] font-bold text-[#1E1B4B] leading-snug">{{ $ev['title'] }}</h3>
                                <div class="mt-2 flex flex-wrap gap-3 text-[12.5px] text-slate-500">
                                    <span>Total: <strong class="text-[#1E1B4B]">{{ $ev['total_scenarios'] }}</strong></span>
                                    <span>Lulus: <strong class="text-emerald-700">{{ $ev['passed_scenarios'] }}</strong></span>
                                    <span>Gagal: <strong class="text-rose-700">{{ $ev['failed_scenarios'] }}</strong></span>
                                    <span>Tanggal: <strong class="text-[#1E1B4B]">{{ $ev['tested_at_label'] }}</strong></span>
                                </div>
                                @if ($ev['notes'])
                                    <p class="mt-3 text-[12.5px] leading-relaxed text-slate-600">{{ $ev['notes'] }}</p>
                                @endif
                                <div class="mt-3 flex flex-wrap items-center gap-3">
                                     @if ($ev['evidence_file_url'])
                                         <a href="{{ $ev['evidence_file_url'] }}" target="_blank" class="inline-flex items-center gap-1.5 text-[12.5px] font-semibold text-violet-700 hover:text-violet-900 transition">
                                             <x-heroicon-o-paper-clip class="w-4 h-4" /> Preview
                                         </a>
                                         <a href="{{ $ev['evidence_download_url'] }}" class="inline-flex items-center gap-1.5 text-[12.5px] font-semibold text-violet-700 hover:text-violet-900 transition">
                                             <x-heroicon-o-arrow-down-tray class="w-4 h-4" /> Download
                                         </a>
                                     @endif
                                    @if ($ev['evidence_url'])
                                        <a href="{{ $ev['evidence_url'] }}" target="_blank" rel="noopener" class="inline-flex items-center gap-1.5 text-[12.5px] font-semibold text-violet-700 hover:text-violet-900 transition">
                                            <x-heroicon-o-link class="w-4 h-4" /> Link Bukti
                                        </a>
                                    @endif
                                     @if ($canManage)
                                         <form method="POST" action="{{ route('testing-evidence.destroy', $ev['id']) }}">
                                             @csrf
                                             @method('DELETE')
                                             <button class="inline-flex items-center gap-1.5 text-[12.5px] font-semibold text-rose-600 hover:text-rose-800 transition" onclick="return confirm('Hapus bukti ini?')">
                                                 <x-heroicon-o-trash class="w-4 h-4" /> Hapus
                                             </button>
                                         </form>
                                     @endif
                                </div>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="p-10 text-center text-[13px] text-slate-400">Belum ada data QA Evidence.</div>
                @endforelse
            </div>
        </article>
    </section>

    <section class="bg-violet-50/70 rounded-2xl border border-violet-200 p-6 mb-8">
        <div class="flex items-start gap-3">
            <x-heroicon-o-information-circle class="w-5 h-5 text-violet-700 mt-0.5 flex-shrink-0" />
            <p class="text-[12.5px] leading-relaxed text-violet-800">
                QA Evidence menampilkan ringkasan bukti pengujian sistem. Detail skenario dan hasil pengujian tetap dikelola pada dokumen pengujian dan lampiran.
            </p>
        </div>
    </section>
</x-layouts.authenticated>
