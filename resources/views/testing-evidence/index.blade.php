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
            <h1 class="text-[42px] leading-[1.05] font-bold tracking-tight text-[#1E1B4B]">
                Testing
                <span class="bg-clip-text text-transparent bg-gradient-to-r from-[#7C3AED] to-[#A855F7]">Evidence</span>
            </h1>
            <p class="mt-3 text-[15px] text-slate-500 max-w-2xl">
                Bukti ringkas hasil pengujian untuk sidang final: Black-Box, UAT, Validasi LLM, dan TestSprite.
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
                <p class="mt-1 text-[12.5px] text-slate-500">{{ $sum['failed'] }} gagal · {{ max(0, ($sum['total'] - $sum['failed'])) }} tervalidasi</p>
            </article>
        @endforeach
    </section>

    <section class="grid grid-cols-1 xl:grid-cols-[1.2fr_0.8fr] gap-6 mb-8">
        <article class="bg-white rounded-2xl border border-violet-100 shadow-[0_2px_8px_rgba(124,58,237,0.08)] overflow-hidden">
            <div class="px-6 py-5 border-b border-violet-100/70 bg-violet-50/40">
                <h2 class="text-[18px] font-bold text-[#1E1B4B]">Daftar Evidence</h2>
                <p class="mt-1 text-[12.5px] text-slate-500">Bukti hasil pengujian yang ditampilkan di dalam sistem tanpa menggantikan dokumen pengujian utama.</p>
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
                                            <x-heroicon-o-paper-clip class="w-4 h-4" /> File Bukti
                                        </a>
                                    @endif
                                    @if ($ev['evidence_url'])
                                        <a href="{{ $ev['evidence_url'] }}" target="_blank" rel="noopener" class="inline-flex items-center gap-1.5 text-[12.5px] font-semibold text-violet-700 hover:text-violet-900 transition">
                                            <x-heroicon-o-link class="w-4 h-4" /> Link Bukti
                                        </a>
                                    @endif
                                    <form method="POST" action="{{ route('testing-evidence.destroy', $ev['id']) }}" class="inline" onsubmit="return confirm('Hapus evidence ini?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="inline-flex items-center gap-1.5 text-[12.5px] font-semibold text-rose-600 hover:text-rose-800 transition cursor-pointer">
                                            <x-heroicon-o-trash class="w-4 h-4" /> Hapus
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="p-10 text-center text-[13px] text-slate-400">Belum ada data Testing Evidence.</div>
                @endforelse
            </div>
        </article>

        <article class="bg-white rounded-2xl border border-violet-100 shadow-[0_2px_8px_rgba(124,58,237,0.08)] p-6">
            <h2 class="text-[18px] font-bold text-[#1E1B4B]">Tambah Evidence</h2>
            <p class="mt-1 text-[12.5px] text-slate-500">Upload screenshot/PDF sederhana atau tautkan evidence eksternal bila diperlukan.</p>

            <form method="POST" action="{{ route('testing-evidence.store') }}" enctype="multipart/form-data" class="mt-5 space-y-4">
                @csrf
                <div>
                    <label class="block text-[12px] font-semibold text-slate-600 mb-1">Kategori</label>
                    <select name="category" class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-[13px] focus:border-violet-400 focus:ring-violet-200 focus:ring-2 outline-none transition">
                        @foreach ($categories as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-[12px] font-semibold text-slate-600 mb-1">Judul</label>
                    <input type="text" name="title" required maxlength="255" class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-[13px] focus:border-violet-400 focus:ring-violet-200 focus:ring-2 outline-none transition" placeholder="Contoh: Final Black-Box Test Sidang">
                </div>
                <div class="grid grid-cols-3 gap-3">
                    <div>
                        <label class="block text-[12px] font-semibold text-slate-600 mb-1">Total</label>
                        <input type="number" name="total_scenarios" min="0" required class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-[13px]">
                    </div>
                    <div>
                        <label class="block text-[12px] font-semibold text-slate-600 mb-1">Lulus</label>
                        <input type="number" name="passed_scenarios" min="0" required class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-[13px]">
                    </div>
                    <div>
                        <label class="block text-[12px] font-semibold text-slate-600 mb-1">Gagal</label>
                        <input type="number" name="failed_scenarios" min="0" required class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-[13px]">
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-[12px] font-semibold text-slate-600 mb-1">Status Hasil</label>
                        <input type="text" name="result_status" required maxlength="50" class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-[13px]" placeholder="Lulus / Diterima / Valid">
                    </div>
                    <div>
                        <label class="block text-[12px] font-semibold text-slate-600 mb-1">Tanggal Uji</label>
                        <input type="date" name="tested_at" required class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-[13px]">
                    </div>
                </div>
                <div>
                    <label class="block text-[12px] font-semibold text-slate-600 mb-1">Catatan</label>
                    <textarea name="notes" rows="3" maxlength="5000" class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-[13px] resize-y" placeholder="Ringkasan hasil atau konteks pengujian"></textarea>
                </div>
                <div class="grid grid-cols-1 gap-3">
                    <div>
                        <label class="block text-[12px] font-semibold text-slate-600 mb-1">File Bukti (opsional, maks 10MB)</label>
                        <input type="file" name="file" class="w-full text-[13px] file:mr-3 file:rounded-lg file:border-0 file:bg-violet-50 file:px-3 file:py-2 file:text-[12px] file:font-semibold file:text-violet-700 hover:file:bg-violet-100 transition">
                    </div>
                    <div>
                        <label class="block text-[12px] font-semibold text-slate-600 mb-1">External URL (opsional)</label>
                        <input type="url" name="evidence_url" maxlength="1000" class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-[13px]" placeholder="https://...">
                    </div>
                </div>
                <div class="flex justify-end">
                    <button type="submit" class="inline-flex items-center gap-2 h-10 px-5 rounded-xl bg-violet-600 text-white font-semibold text-[13px] shadow hover:bg-violet-700 transition cursor-pointer">
                        <x-heroicon-o-plus class="w-4 h-4" /> Tambah Evidence
                    </button>
                </div>
            </form>
        </article>
    </section>

    <section class="bg-violet-50/70 rounded-2xl border border-violet-200 p-6 mb-8">
        <div class="flex items-start gap-3">
            <x-heroicon-o-information-circle class="w-5 h-5 text-violet-700 mt-0.5 flex-shrink-0" />
            <p class="text-[12.5px] leading-relaxed text-violet-800">
                Testing Evidence adalah bukti ringkas di dalam sistem untuk sidang final. Halaman ini tidak menggantikan dokumen pengujian utama, hanya merangkum hasil dan menyimpan tautan/file pendukung bila diperlukan.
            </p>
        </div>
    </section>
</x-layouts.authenticated>
