@php
    $events ??= [];
    $generatedAt ??= now();
    $filters ??= ['chip' => 'all', 'actor' => 'all', 'range' => 'all'];

    $col = collect($events);
    $groups = $col->groupBy('date');

    $filterLabels = [
        'all'      => 'Semua modul',
        'proyek'   => 'Proyek',
        'klien'    => 'Klien',
        'tim'      => 'Tim',
        'settings' => 'Settings',
        'login'    => 'Login',
    ];

    $rangeLabels = [
        '7'   => '7 hari terakhir',
        '30'  => '30 hari terakhir',
        '90'  => '90 hari terakhir',
        'all' => 'Semua waktu',
    ];

    $moduleCounts = [
        'Project Master'   => $col->where('module', 'Project Master')->count(),
        'Client Directory' => $col->where('module', 'Client Directory')->count(),
        'Team Management'  => $col->where('module', 'Team Management')->count(),
        'Settings'         => $col->where('module', 'Settings')->count(),
    ];
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>{{ $title ?? 'Laporan Audit Trail' }}</title>
    <style>
        @page { size: A4 portrait; margin: 18mm 15mm; }
        * { box-sizing: border-box; }
        body { font-family: -apple-system, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; color: #1E1B4B; margin: 0; padding: 24px 28px; background: #FFFFFF; font-size: 12px; line-height: 1.45; }
        h1 { font-size: 22px; margin: 0 0 4px; color: #1E1B4B; }
        .subtitle { color: #64748B; font-size: 12px; margin: 0; }
        .meta { margin-top: 14px; display: flex; gap: 18px; flex-wrap: wrap; font-size: 11px; color: #475569; }
        .meta b { color: #1E1B4B; }
        .summary { margin: 18px 0 22px; display: flex; gap: 14px; flex-wrap: wrap; }
        .summary .card { border: 1px solid #E9D5FF; border-radius: 8px; padding: 10px 14px; min-width: 140px; }
        .summary .card .label { font-size: 10px; text-transform: uppercase; letter-spacing: 0.08em; color: #6B7280; font-weight: 700; }
        .summary .card .value { font-size: 22px; font-weight: 700; color: #1E1B4B; margin-top: 2px; }
        .group-title { font-size: 11px; font-weight: 700; letter-spacing: 0.12em; text-transform: uppercase; color: #7C3AED; margin: 20px 0 6px; }
        table { width: 100%; border-collapse: collapse; font-size: 11.5px; }
        thead th { text-align: left; padding: 8px 10px; background: #F5F3FF; color: #4C1D95; font-size: 10.5px; text-transform: uppercase; letter-spacing: 0.06em; border-bottom: 1px solid #DDD6FE; }
        tbody td { padding: 8px 10px; border-bottom: 1px solid #F1F5F9; vertical-align: top; }
        .tag { display: inline-block; padding: 2px 6px; border-radius: 4px; font-size: 9.5px; font-weight: 700; letter-spacing: 0.04em; background: #EDE9FE; color: #5B21B6; white-space: nowrap; }
        .tag.proyek   { background: #EDE9FE; color: #5B21B6; }
        .tag.klien    { background: #DCFCE7; color: #166534; }
        .tag.tim      { background: #FAE8FF; color: #86198F; }
        .tag.settings { background: #FED7AA; color: #9A3412; }
        .tag.login    { background: #F1F5F9; color: #334155; }
        .col-time { width: 60px; color: #6B7280; white-space: nowrap; }
        .col-tag  { width: 150px; }
        .col-actor { width: 160px; }
        .actor-name { font-weight: 600; }
        .desc { color: #1E293B; }
        .empty { padding: 40px 0; text-align: center; color: #94A3B8; }
        .footer { margin-top: 28px; padding-top: 10px; border-top: 1px solid #E2E8F0; font-size: 10.5px; color: #64748B; display: flex; justify-content: space-between; }
        .print-btn { position: fixed; top: 14px; right: 14px; background: linear-gradient(135deg, #7C3AED, #A855F7); color: #fff; border: 0; padding: 9px 14px; border-radius: 8px; font-weight: 600; cursor: pointer; box-shadow: 0 4px 12px rgba(124,58,237,0.25); font-size: 12px; }
        @media print { .print-btn { display: none; } body { padding: 0; } }
    </style>
</head>
<body>
    <button type="button" class="print-btn" onclick="window.print()">Print / Save as PDF</button>

    <header>
        <h1>Laporan Audit Trail — Avatech Smart-PMIS</h1>
        <p class="subtitle">Riwayat aktivitas tercatat di database, diurut dari yang terbaru.</p>
        <div class="meta">
            <span><b>Dibuat:</b> {{ $generatedAt->translatedFormat('d F Y H:i') }}</span>
            <span><b>Modul:</b> {{ $filterLabels[$filters['chip']] ?? $filters['chip'] }}</span>
            <span><b>Anggota:</b> {{ $filters['actor'] === 'all' ? 'Semua anggota' : $filters['actor'] }}</span>
            <span><b>Rentang:</b> {{ $rangeLabels[$filters['range']] ?? $filters['range'] }}</span>
            <span><b>Total entri:</b> {{ $col->count() }}</span>
        </div>
    </header>

    <section class="summary">
        @foreach ($moduleCounts as $module => $count)
            <div class="card">
                <div class="label">{{ $module }}</div>
                <div class="value">{{ $count }}</div>
            </div>
        @endforeach
    </section>

    @forelse ($groups as $date => $items)
        <div class="group-title">{{ $date }} &mdash; {{ $items->count() }} entri</div>
        <table>
            <thead>
                <tr>
                    <th class="col-time">Waktu</th>
                    <th class="col-tag">Tag</th>
                    <th class="col-actor">Anggota</th>
                    <th>Deskripsi</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($items as $a)
                    <tr>
                        <td class="col-time">{{ $a['time'] }}</td>
                        <td class="col-tag"><span class="tag {{ $a['filter'] }}">{{ $a['tag'] }}</span></td>
                        <td class="col-actor">
                            <span class="actor-name">{{ $a['actor'] }}</span><br>
                            <span style="color:#94A3B8;font-size:10px;">{{ $a['module'] }}</span>
                        </td>
                        <td class="desc">{!! $a['text'] !!}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @empty
        <div class="empty">Tidak ada aktivitas yang cocok dengan filter laporan ini.</div>
    @endforelse

    <footer class="footer">
        <span>Avatech Smart-PMIS &middot; Laporan Audit Trail</span>
        <span>Halaman dibuat otomatis dari database audit_logs</span>
    </footer>
</body>
</html>
