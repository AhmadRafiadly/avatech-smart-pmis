@php
    /** @var \App\Models\Project $project */
    /** @var \Illuminate\Support\Carbon $generatedAt */
    /** @var array $testCases */
    /** @var array $summary */ // ['total','passed','failed','pending','retest']
    $clientName = $project->client?->name;
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<title>Laporan Test Case — {{ $project->name }}</title>
<style>
    @page { size: A4 portrait; margin: 18mm 14mm 18mm 14mm; }
    * { box-sizing: border-box; }
    body { font-family: DejaVu Sans, Arial, Helvetica, sans-serif; color: #1F2937; font-size: 11px; line-height: 1.45; margin: 0; }
    h1 { font-size: 18px; margin: 0 0 4px; color: #1E1B4B; }
    h2 { font-size: 13px; margin: 14px 0 6px; color: #1E1B4B; }
    .muted { color: #6B7280; font-size: 10px; }
    .brand { font-size: 10px; letter-spacing: 0.14em; text-transform: uppercase; color: #7C3AED; font-weight: bold; margin-bottom: 2px; }
    .hr { border: 0; border-top: 1px solid #E5E7EB; margin: 10px 0 12px; }
    .meta-table { width: 100%; border-collapse: collapse; margin-top: 4px; }
    .meta-table td { padding: 2px 6px 2px 0; vertical-align: top; font-size: 10.5px; }
    .meta-table td.k { color: #6B7280; width: 90px; font-weight: bold; text-transform: uppercase; font-size: 9.5px; letter-spacing: 0.04em; }
    .summary { width: 100%; border-collapse: collapse; margin-top: 8px; }
    .summary td { border: 1px solid #E5E7EB; padding: 6px 8px; vertical-align: top; }
    .summary .lbl { font-size: 9px; text-transform: uppercase; letter-spacing: 0.06em; color: #6B7280; font-weight: bold; }
    .summary .val { font-size: 15px; font-weight: bold; color: #1E1B4B; padding-top: 2px; }
    table.tc { width: 100%; border-collapse: collapse; margin-top: 8px; }
    table.tc th { background: #F3F4F6; text-align: left; font-size: 9px; text-transform: uppercase; letter-spacing: 0.04em; color: #374151; padding: 5px 6px; border: 1px solid #E5E7EB; }
    table.tc td { padding: 5px 6px; border: 1px solid #E5E7EB; vertical-align: top; font-size: 10px; color: #1F2937; page-break-inside: avoid; }
    table.tc td.code { font-weight: bold; color: #5B21B6; white-space: nowrap; }
    table.tc td.t-title { font-weight: bold; }
    .pill { display: inline-block; padding: 1px 5px; border: 1px solid #D1D5DB; border-radius: 3px; font-size: 8.5px; text-transform: uppercase; letter-spacing: 0.04em; color: #374151; background: #F9FAFB; }
    .pill-passed  { background: #ECFDF5; border-color: #A7F3D0; color: #047857; }
    .pill-failed  { background: #FFF1F2; border-color: #FECDD3; color: #BE123C; }
    .pill-pending { background: #FFFBEB; border-color: #FDE68A; color: #B45309; }
    .pill-retest  { background: #F5F3FF; border-color: #DDD6FE; color: #6D28D9; }
    .empty { padding: 30px; text-align: center; border: 1px dashed #D1D5DB; border-radius: 4px; color: #6B7280; margin-top: 14px; font-size: 11.5px; }
    .footer { margin-top: 16px; font-size: 9.5px; color: #9CA3AF; text-align: center; }
    .small { font-size: 9.5px; color: #6B7280; }
</style>
</head>
<body>

<div class="brand">Avatech Smart-PMIS</div>
<h1>Laporan Test Case / Quality Control</h1>
<div class="muted">Detail test case QC pada proyek terkait. Dokumen ini dihasilkan langsung dari database Smart-PMIS.</div>

<table class="meta-table">
    <tr><td class="k">Proyek</td><td><strong>{{ $project->name }}</strong> · {{ $project->code }}</td></tr>
    @if ($clientName)
        <tr><td class="k">Klien</td><td>{{ $clientName }}</td></tr>
    @endif
    <tr><td class="k">Diekspor</td><td>{{ $generatedAt->translatedFormat('d F Y H:i') }}</td></tr>
</table>

<hr class="hr">

<h2>Ringkasan</h2>
<table class="summary">
    <tr>
        <td><div class="lbl">Total</div><div class="val">{{ $summary['total'] }}</div></td>
        <td><div class="lbl">Lulus</div><div class="val" style="color:#047857;">{{ $summary['passed'] }}</div></td>
        <td><div class="lbl">Gagal</div><div class="val" style="color:#BE123C;">{{ $summary['failed'] }}</div></td>
        <td><div class="lbl">Pending</div><div class="val" style="color:#B45309;">{{ $summary['pending'] }}</div></td>
        <td><div class="lbl">Retest</div><div class="val" style="color:#6D28D9;">{{ $summary['retest'] }}</div></td>
    </tr>
</table>

<h2>Daftar Test Case</h2>

@if (count($testCases) === 0)
    <div class="empty">Belum ada test case yang tersedia untuk proyek ini.</div>
@else
    <table class="tc">
        <thead>
            <tr>
                <th style="width:8%;">ID</th>
                <th style="width:18%;">Judul</th>
                <th>Skenario / Expected / Actual</th>
                <th style="width:12%;">Modul</th>
                <th style="width:8%;">Prioritas</th>
                <th style="width:9%;">Status</th>
                <th style="width:10%;">Tested</th>
            </tr>
        </thead>
        <tbody>
        @foreach ($testCases as $i => $tc)
            @php
                $pillCls = 'pill pill-' . ($tc['status'] ?? 'pending');
                $code = $tc['code'] ?? ('QC-' . str_pad((string) ($i + 1), 4, '0', STR_PAD_LEFT));
            @endphp
            <tr>
                <td class="code">{{ $code }}</td>
                <td class="t-title">{{ $tc['title'] ?: '—' }}</td>
                <td>
                    <div><span class="small">Skenario:</span> {{ $tc['scenario'] ?: '—' }}</div>
                    @if (! empty($tc['expected_result']))
                        <div style="margin-top:3px;"><span class="small">Expected:</span> {{ $tc['expected_result'] }}</div>
                    @endif
                    @if (! empty($tc['actual_result']))
                        <div style="margin-top:3px;"><span class="small">Actual:</span> {{ $tc['actual_result'] }}</div>
                    @endif
                    @if (! empty($tc['notes']))
                        <div style="margin-top:3px;"><span class="small">Catatan:</span> {{ $tc['notes'] }}</div>
                    @endif
                </td>
                <td>{{ $tc['module'] ?: '—' }}</td>
                <td>{{ ucfirst((string) ($tc['priority'] ?? 'medium')) }}</td>
                <td><span class="{{ $pillCls }}">{{ strtoupper((string) ($tc['status'] ?? 'pending')) }}</span></td>
                <td>{{ $tc['tested_at'] ?: '—' }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
@endif

<div class="footer">Avatech Smart-PMIS &middot; Laporan Test Case / Quality Control &middot; {{ $generatedAt->format('Y-m-d H:i') }}</div>

</body>
</html>
