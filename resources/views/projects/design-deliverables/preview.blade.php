<x-layouts.authenticated :title="$title">
    <div class="space-y-5">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-violet-500">Design Deliverable</p>
                <h1 class="mt-1 text-2xl font-bold text-[#1E1B4B]">Preview PDF Mockup</h1>
                <p class="mt-1 text-sm text-slate-500">
                    {{ $project->code }} - {{ $project->name }} - {{ $task->title }}
                </p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('projects.show', $project) }}#workspace" class="inline-flex h-10 items-center rounded-xl border border-violet-100 bg-white px-4 text-sm font-semibold text-slate-600 shadow-sm transition hover:border-violet-200 hover:text-violet-700">
                    Kembali ke Project
                </a>
                <a href="{{ $downloadUrl }}" class="inline-flex h-10 items-center rounded-xl bg-violet-600 px-4 text-sm font-semibold text-white shadow-sm transition hover:bg-violet-700">
                    Download PDF
                </a>
            </div>
        </div>

        <div class="overflow-hidden rounded-2xl border border-violet-100 bg-white shadow-[0_2px_8px_rgb(109,40,217,0.06)]">
            <div class="flex flex-col gap-2 border-b border-violet-50 px-5 py-4 md:flex-row md:items-center md:justify-between">
                <div>
                    <p class="text-sm font-bold text-[#1E1B4B]">{{ $deliverable->title }}</p>
                    <p class="text-xs text-slate-500">{{ $filename }}</p>
                </div>
                <p class="text-xs text-slate-500">Preview dimuat di halaman internal Smart-PMIS.</p>
            </div>

            <div class="bg-slate-100 p-3 md:p-5">
                @if ($pdfDataUri)
                    <iframe
                        src="{{ $pdfDataUri }}"
                        title="Preview PDF Mockup"
                        class="h-[72vh] min-h-[520px] w-full rounded-xl border border-slate-200 bg-white"
                    ></iframe>
                @else
                    <div class="rounded-xl border border-amber-100 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                        {{ $previewMessage ?: 'Preview tidak dapat dimuat otomatis. Gunakan tombol Download PDF untuk membuka file secara manual.' }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-layouts.authenticated>
