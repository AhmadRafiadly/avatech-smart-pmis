<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title }} - {{ config('app.name', 'Smart-PMIS') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-[#FAF5FF] text-slate-700 antialiased">
    <main class="mx-auto max-w-5xl px-4 py-8 sm:px-6 lg:px-8">
        <section class="overflow-hidden rounded-2xl border border-violet-100 bg-white shadow-[0_2px_12px_rgba(124,58,237,0.08)]">
            <div class="border-b border-violet-100 bg-gradient-to-r from-violet-700 to-fuchsia-500 px-6 py-7 text-white">
                <p class="text-[11px] font-bold uppercase tracking-[0.22em] opacity-80">Avatech Smart-PMIS</p>
                <h1 class="mt-2 text-3xl font-extrabold tracking-tight">Project Review</h1>
                <p class="mt-2 max-w-3xl text-sm leading-relaxed text-violet-50">
                    Halaman ini hanya menampilkan informasi yang dipilih oleh tim Avatech untuk kebutuhan review client.
                </p>
            </div>

            <div class="space-y-6 px-6 py-6">
                @if (session('status'))
                    <div class="rounded-xl border border-emerald-100 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700">
                        {{ session('status') }}
                    </div>
                @endif

                <div class="grid gap-4 lg:grid-cols-3">
                    <div class="lg:col-span-2">
                        <p class="text-xs font-bold uppercase tracking-wider text-violet-500">{{ $review->review_type ? ucfirst($review->review_type) : 'General' }} Review</p>
                        <h2 class="mt-1 text-2xl font-bold text-[#1E1B4B]">{{ $review->title }}</h2>
                        <p class="mt-2 text-sm text-slate-500">{{ $review->description ?: 'Silakan tinjau informasi project berikut dan berikan approval atau catatan revisi.' }}</p>
                    </div>
                    <div class="rounded-xl border border-violet-100 bg-violet-50/50 p-4 text-sm">
                        <div class="font-bold text-[#1E1B4B]">{{ $project->name }}</div>
                        <div class="mt-1 text-slate-500">{{ $project->client?->name ?? $review->client_name ?? 'Client' }}</div>
                        <div class="mt-3 flex items-center justify-between text-xs">
                            <span>Fase</span>
                            <strong>{{ $project->phase }}</strong>
                        </div>
                        @if ($review->include_progress)
                            <div class="mt-2">
                                <div class="mb-1 flex items-center justify-between text-xs">
                                    <span>Progress</span>
                                    <strong>{{ $progress }}%</strong>
                                </div>
                                <div class="h-2 overflow-hidden rounded-full bg-white">
                                    <div class="h-full rounded-full bg-violet-600" style="width: {{ $progress }}%;"></div>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

                @if ($momSummary)
                    <section class="rounded-2xl border border-violet-100 bg-white p-5">
                        <h3 class="text-sm font-extrabold uppercase tracking-tight text-[#1E1B4B]">MoM Summary</h3>
                        <p class="mt-1 text-xs text-slate-400">{{ $momSummary['title'] }}</p>
                        <p class="mt-3 whitespace-pre-line text-sm leading-relaxed text-slate-600">{{ $momSummary['summary'] }}</p>
                    </section>
                @endif

                @if (! empty($deliverables))
                    <section class="rounded-2xl border border-violet-100 bg-white p-5">
                        <h3 class="text-sm font-extrabold uppercase tracking-tight text-[#1E1B4B]">Design Deliverables</h3>
                        <div class="mt-4 space-y-3">
                            @foreach ($deliverables as $deliverable)
                                <div class="rounded-xl border border-violet-100 bg-violet-50/30 px-4 py-3">
                                    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                                        <div>
                                            <p class="text-sm font-bold text-[#1E1B4B]">{{ $deliverable['title'] }}</p>
                                            <p class="text-xs text-slate-500">{{ $deliverable['task_title'] }}</p>
                                        </div>
                                        <div class="flex flex-wrap gap-2 text-xs font-semibold">
                                            @if ($deliverable['figma_url'])
                                                <a href="{{ $deliverable['figma_url'] }}" target="_blank" rel="noopener" class="rounded-lg bg-white px-3 py-2 text-violet-700 hover:bg-violet-50">Figma/mockup</a>
                                            @endif
                                            @if ($deliverable['preview_url'])
                                                <a href="{{ $deliverable['preview_url'] }}" target="_blank" rel="noopener" class="rounded-lg bg-violet-600 px-3 py-2 text-white hover:bg-violet-700">Preview PDF</a>
                                            @endif
                                            @if ($deliverable['download_url'])
                                                <a href="{{ $deliverable['download_url'] }}" class="rounded-lg bg-white px-3 py-2 text-slate-600 hover:bg-slate-50">Download PDF</a>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </section>
                @endif

                @if ($qcSummary)
                    <section class="rounded-2xl border border-violet-100 bg-white p-5">
                        <h3 class="text-sm font-extrabold uppercase tracking-tight text-[#1E1B4B]">QC Summary</h3>
                        <p class="mt-2 text-sm text-slate-600">
                            {{ $qcSummary['passed'] }}/{{ $qcSummary['total'] }} test case lulus ({{ $qcSummary['percent'] }}%).
                            {{ $qcSummary['failed'] }} gagal, {{ $qcSummary['pending'] }} pending, {{ $qcSummary['retest'] }} retest.
                        </p>
                    </section>
                @endif

                @if (! empty($changeRequests))
                    <section class="rounded-2xl border border-violet-100 bg-white p-5">
                        <h3 class="text-sm font-extrabold uppercase tracking-tight text-[#1E1B4B]">Change Request Summary</h3>
                        <div class="mt-4 space-y-2">
                            @foreach ($changeRequests as $cr)
                                <div class="flex flex-col gap-1 rounded-xl border border-violet-100 bg-violet-50/30 px-4 py-3 text-sm sm:flex-row sm:items-center sm:justify-between">
                                    <span class="font-bold text-[#1E1B4B]">{{ $cr['title'] }}</span>
                                    <span class="text-xs text-slate-500">{{ $cr['status'] }} · {{ $cr['type'] ?: 'general' }} · {{ $cr['priority'] ?: 'medium' }}@if ($cr['timeline_impact_days'] !== null) · +{{ $cr['timeline_impact_days'] }} hari @endif</span>
                                </div>
                            @endforeach
                        </div>
                    </section>
                @endif

                @if ($review->status === 'approved' || $review->status === 'revision_requested')
                    <section class="rounded-2xl border border-violet-100 bg-violet-50/70 p-5">
                        <h3 class="text-sm font-extrabold text-[#1E1B4B]">
                            {{ $review->status === 'approved' ? 'Review sudah disetujui.' : 'Catatan revisi sudah dikirim.' }}
                        </h3>
                        @if ($review->client_feedback)
                            <p class="mt-2 whitespace-pre-line text-sm text-slate-600">{{ $review->client_feedback }}</p>
                        @endif
                    </section>
                @else
                    <section class="rounded-2xl border border-violet-100 bg-white p-5">
                        <h3 class="text-sm font-extrabold uppercase tracking-tight text-[#1E1B4B]">Response Client</h3>
                        <div class="mt-4 grid gap-4 lg:grid-cols-2">
                            <form method="POST" action="{{ route('client-reviews.approve', $review->token) }}" class="space-y-3 rounded-xl border border-emerald-100 bg-emerald-50/50 p-4">
                                @csrf
                                <input name="client_name" value="{{ old('client_name', $review->client_name) }}" placeholder="Nama client" class="w-full rounded-lg border border-emerald-100 bg-white px-3 py-2 text-sm">
                                <input name="client_email" type="email" value="{{ old('client_email', $review->client_email) }}" placeholder="Email client (opsional)" class="w-full rounded-lg border border-emerald-100 bg-white px-3 py-2 text-sm">
                                <textarea name="client_feedback" rows="3" placeholder="Catatan approval opsional" class="w-full rounded-lg border border-emerald-100 bg-white px-3 py-2 text-sm">{{ old('client_feedback') }}</textarea>
                                <button type="submit" class="w-full rounded-xl bg-emerald-600 px-4 py-3 text-sm font-bold text-white hover:bg-emerald-700">Setujui</button>
                            </form>

                            <form method="POST" action="{{ route('client-reviews.revision', $review->token) }}" class="space-y-3 rounded-xl border border-amber-100 bg-amber-50/60 p-4">
                                @csrf
                                <input name="client_name" value="{{ old('client_name', $review->client_name) }}" placeholder="Nama client" class="w-full rounded-lg border border-amber-100 bg-white px-3 py-2 text-sm">
                                <input name="client_email" type="email" value="{{ old('client_email', $review->client_email) }}" placeholder="Email client (opsional)" class="w-full rounded-lg border border-amber-100 bg-white px-3 py-2 text-sm">
                                <textarea name="client_feedback" rows="3" required placeholder="Tuliskan catatan revisi" class="w-full rounded-lg border border-amber-100 bg-white px-3 py-2 text-sm">{{ old('client_feedback') }}</textarea>
                                @error('client_feedback')<p class="text-xs font-semibold text-rose-600">{{ $message }}</p>@enderror
                                <button type="submit" class="w-full rounded-xl bg-amber-500 px-4 py-3 text-sm font-bold text-white hover:bg-amber-600">Minta Revisi</button>
                            </form>
                        </div>
                    </section>
                @endif
            </div>
        </section>
    </main>
</body>
</html>
