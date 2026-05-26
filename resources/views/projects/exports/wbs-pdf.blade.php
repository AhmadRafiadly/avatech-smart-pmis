@php
    /** @var \App\Models\Project $project */
    /** @var \Illuminate\Support\Carbon $generatedAt */
    /** @var array $modules */          // [['title','description','status','estimate_hours','tasks'=>[...]], ...]
    /** @var int $totalModules */
    /** @var int $totalTasks */
    /** @var int $totalEstimateHours */
    $clientName  = $project->client?->name;
    $statusLabel = ucwords(str_replace('-', ' ', (string) $project->status));
    $phaseLabel  = $project->phase ?: '—';
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<title>Laporan WBS — {{ $project->name }}</title>
<style>
    @page { size: A4 portrait; margin: 18mm 14mm 18mm 14mm; }
    * { box-sizing: border-box; }
    body { font-family: DejaVu Sans, Arial, Helvetica, sans-serif; color: #1F2937; font-size: 11px; line-height: 1.45; margin: 0; }
    h1 { font-size: 18px; margin: 0 0 4px; color: #1E1B4B; }
    h2 { font-size: 13px; margin: 14px 0 6px; color: #1E1B4B; }
    h3 { font-size: 12px; margin: 8px 0 4px; color: #1E1B4B; }
    .muted { color: #6B7280; font-size: 10px; }
    .brand { font-size: 10px; letter-spacing: 0.14em; text-transform: uppercase; color: #7C3AED; font-weight: bold; margin-bottom: 2px; }
    .hr { border: 0; border-top: 1px solid #E5E7EB; margin: 10px 0 12px; }
    .meta-table { width: 100%; border-collapse: collapse; margin-top: 4px; }
    .meta-table td { padding: 2px 6px 2px 0; vertical-align: top; font-size: 10.5px; }
    .meta-table td.k { color: #6B7280; width: 90px; font-weight: bold; text-transform: uppercase; font-size: 9.5px; letter-spacing: 0.04em; }
    .summary { width: 100%; border-collapse: collapse; margin-top: 8px; }
    .summary td { border: 1px solid #E5E7EB; padding: 7px 9px; vertical-align: top; }
    .summary .lbl { font-size: 9.5px; text-transform: uppercase; letter-spacing: 0.06em; color: #6B7280; font-weight: bold; }
    .summary .val { font-size: 16px; font-weight: bold; color: #1E1B4B; padding-top: 2px; }
    .module { border: 1px solid #E5E7EB; border-radius: 4px; padding: 8px 10px; margin-top: 10px; page-break-inside: avoid; }
    .module .title { font-size: 12.5px; font-weight: bold; color: #1E1B4B; margin-bottom: 3px; }
    .module .row { color: #4B5563; font-size: 10px; margin-bottom: 2px; }
    .badge { display: inline-block; padding: 1px 6px; border: 1px solid #D1D5DB; border-radius: 3px; font-size: 9px; text-transform: uppercase; letter-spacing: 0.04em; color: #374151; background: #F9FAFB; }
    .desc { color: #4B5563; font-size: 10.5px; margin-top: 4px; }
    table.tasks { width: 100%; border-collapse: collapse; margin-top: 6px; }
    table.tasks th { background: #F3F4F6; text-align: left; font-size: 9px; text-transform: uppercase; letter-spacing: 0.04em; color: #374151; padding: 5px 6px; border: 1px solid #E5E7EB; }
    table.tasks td { padding: 5px 6px; border: 1px solid #E5E7EB; vertical-align: top; font-size: 10px; color: #1F2937; }
    table.tasks td.title { font-weight: bold; }
    .empty { padding: 30px; text-align: center; border: 1px dashed #D1D5DB; border-radius: 4px; color: #6B7280; margin-top: 14px; font-size: 11.5px; }
    .footer { margin-top: 16px; font-size: 9.5px; color: #9CA3AF; text-align: center; }
</style>
</head>
<body>

<div class="brand">Avatech Smart-PMIS</div>
<h1>Laporan Work Breakdown Structure</h1>
<div class="muted">Detail modul + task pada proyek terkait. Dokumen ini dihasilkan langsung dari database Smart-PMIS.</div>

<table class="meta-table">
    <tr><td class="k">Proyek</td><td><strong>{{ $project->name }}</strong> · {{ $project->code }}</td></tr>
    @if ($clientName)
        <tr><td class="k">Klien</td><td>{{ $clientName }}</td></tr>
    @endif
    <tr><td class="k">Fase</td><td>{{ $phaseLabel }}</td></tr>
    <tr><td class="k">Status</td><td>{{ $statusLabel ?: '—' }}</td></tr>
    <tr><td class="k">Diekspor</td><td>{{ $generatedAt->translatedFormat('d F Y H:i') }}</td></tr>
</table>

<hr class="hr">

<h2>Ringkasan</h2>
<table class="summary">
    <tr>
        <td><div class="lbl">Total Modul</div><div class="val">{{ $totalModules }}</div></td>
        <td><div class="lbl">Total Task</div><div class="val">{{ $totalTasks }}</div></td>
        <td><div class="lbl">Estimasi Total</div><div class="val">{{ $totalEstimateHours }}<span style="font-size:10px;font-weight:normal;color:#6B7280;"> jam</span></div></td>
    </tr>
</table>

<h2>Daftar Modul &amp; Task</h2>

@forelse ($modules as $idx => $mod)
    <div class="module">
        <div class="title">{{ ($idx + 1) . '. ' . $mod['title'] }}</div>
        <div class="row">
            <span class="badge">{{ ucwords(str_replace('_', ' ', (string) $mod['status'])) }}</span>
            &nbsp;&middot;&nbsp; Estimasi {{ (int) $mod['estimate_hours'] }} jam
            &nbsp;&middot;&nbsp; {{ count($mod['tasks']) }} task
        </div>
        @if (! empty($mod['description']))
            <div class="desc">{{ $mod['description'] }}</div>
        @endif

        @if (! empty($mod['tasks']))
            <table class="tasks">
                <thead>
                    <tr>
                        <th style="width:22%;">Task</th>
                        <th style="width:11%;">Status</th>
                        <th style="width:9%;">Prioritas</th>
                        <th style="width:14%;">Assignee</th>
                        <th style="width:8%;">Est. Jam</th>
                        <th style="width:11%;">Due Date</th>
                        <th>Deskripsi</th>
                    </tr>
                </thead>
                <tbody>
                @foreach ($mod['tasks'] as $task)
                    <tr>
                        <td class="title">{{ $task['title'] }}</td>
                        <td>{{ ucwords(str_replace('_', ' ', (string) $task['status'])) }}</td>
                        <td>{{ ucfirst((string) $task['priority']) }}</td>
                        <td>{{ $task['assignee'] ?: '—' }}</td>
                        <td>{{ (int) $task['estimate_hours'] }}</td>
                        <td>{{ $task['due_date'] ?: '—' }}</td>
                        <td>{{ $task['description'] ?: '—' }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        @endif
    </div>
@empty
    <div class="empty">Belum ada WBS yang tersedia untuk proyek ini.</div>
@endforelse

<div class="footer">Avatech Smart-PMIS &middot; Laporan Work Breakdown Structure &middot; {{ $generatedAt->format('Y-m-d H:i') }}</div>

</body>
</html>
