<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }} - {{ config('app.name', 'Smart-PMIS') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-[#FAF5FF] text-slate-700 antialiased">
    <main class="mx-auto max-w-6xl px-4 py-6 sm:px-6 lg:px-8">
        <div class="mb-4 flex flex-col gap-3 rounded-2xl border border-violet-100 bg-white px-5 py-4 shadow-[0_2px_8px_rgba(124,58,237,0.08)] sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-violet-500">Project Review</p>
                <h1 class="mt-1 text-2xl font-bold text-[#1E1B4B]">Preview PDF Mockup</h1>
                <p class="mt-1 text-sm text-slate-500">{{ $review->project->name }} - {{ $deliverable->title }}</p>
            </div>
            <a href="{{ $downloadUrl }}" class="inline-flex h-10 items-center justify-center rounded-xl bg-violet-600 px-4 text-sm font-semibold text-white shadow-sm transition hover:bg-violet-700">
                Download PDF
            </a>
        </div>

        <section class="overflow-hidden rounded-2xl border border-violet-100 bg-white shadow-[0_2px_8px_rgba(124,58,237,0.08)]">
            <div class="border-b border-violet-50 px-5 py-3">
                <p class="text-sm font-bold text-[#1E1B4B]">{{ $filename }}</p>
                <p class="text-xs text-slate-500">Preview dimuat dari halaman internal token review, tanpa membuka path storage langsung.</p>
            </div>
            <div class="bg-slate-100 p-3 md:p-5">
                @if ($pdfDataUri)
                    <iframe src="{{ $pdfDataUri }}" title="Preview PDF Mockup" class="h-[76vh] min-h-[560px] w-full rounded-xl border border-slate-200 bg-white"></iframe>
                @else
                    <div class="rounded-xl border border-amber-100 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                        {{ $previewMessage ?: 'Preview tidak dapat dimuat otomatis. Gunakan tombol Download PDF untuk membuka file secara manual.' }}
                    </div>
                @endif
            </div>
        </section>
    </main>
</body>
</html>
