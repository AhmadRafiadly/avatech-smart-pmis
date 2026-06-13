@php
    /** @var \App\Models\Project $project */
    /** @var \App\Models\ProjectHandoverPack $pack */
    /** @var \Illuminate\Support\Carbon $generatedAt */
    $statusLabel = ucwords(str_replace('-', ' ', (string) $project->status));
    $roleLabel = function (?string $role) {
        return match ($role) {
            'ceo_pm' => 'CEO / PM',
            'sa_qa' => 'SA / QA',
            'uiux_designer', 'ui_ux' => 'UI/UX Designer',
            'fullstack_dev' => 'Fullstack Dev',
            default => $role ? ucfirst(str_replace('_', ' ', $role)) : '—',
        };
    };
    $uatSummary = $uatSummary ?? [];
    $qcSummary = $qcSummary ?? [];
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<title>Handover Pack — {{ $project->name }}</title>
<style>
    @page { size: A4 portrait; margin: 16mm 14mm 16mm 14mm; }
    * { box-sizing: border-box; }
    body { font-family: DejaVu Sans, Arial, Helvetica, sans-serif; color: #1F2937; font-size: 11px; line-height: 1.45; margin: 0; word-wrap: break-word; overflow-wrap: break-word; }
    table { table-layout: fixed; width: 100%; }
    td, th { word-wrap: break-word; overflow-wrap: break-word; }
    h1 { font-size: 20px; margin: 0 0 4px; color: #1E1B4B; }
    h2 { font-size: 13px; margin: 16px 0 6px; color: #1E1B4B; border-bottom: 2px solid #EDE9FE; padding-bottom: 3px; }
    .muted { color: #6B7280; font-size: 10px; }
    .brand { font-size: 10px; letter-spacing: 0.14em; text-transform: uppercase; color: #7C3AED; font-weight: bold; margin-bottom: 2px; }
    .cover { border: 1px solid #E5E7EB; border-radius: 6px; padding: 22px 20px; margin-bottom: 6px; background: #FAF5FF; }
    .cover .pname { font-size: 22px; font-weight: bold; color: #1E1B4B; margin: 6px 0 2px; }
    .meta-table { width: 100%; border-collapse: collapse; margin-top: 4px; }
    .meta-table td { padding: 2px 6px 2px 0; vertical-align: top; font-size: 10.5px; }
    .meta-table td.k { color: #6B7280; width: 110px; font-weight: bold; text-transform: uppercase; font-size: 9.5px; letter-spacing: 0.04em; }
    table.data { width: 100%; border-collapse: collapse; margin-top: 6px; }
    table.data th { background: #F3F4F6; text-align: left; font-size: 9px; text-transform: uppercase; letter-spacing: 0.04em; color: #374151; padding: 5px 6px; border: 1px solid #E5E7EB; }
    table.data td { padding: 5px 6px; border: 1px solid #E5E7EB; vertical-align: top; font-size: 10px; color: #1F2937; }
    table.data td.title { font-weight: bold; }
    .badge { display: inline-block; padding: 1px 6px; border: 1px solid #D1D5DB; border-radius: 3px; font-size: 9px; text-transform: uppercase; letter-spacing: 0.04em; color: #374151; background: #F9FAFB; }
    .summary { width: 100%; border-collapse: collapse; margin-top: 6px; }
    .summary td { border: 1px solid #E5E7EB; padding: 7px 9px; vertical-align: top; text-align: center; }
    .summary .lbl { font-size: 9px; text-transform: uppercase; letter-spacing: 0.06em; color: #6B7280; font-weight: bold; }
    .summary .val { font-size: 16px; font-weight: bold; color: #1E1B4B; }
    .note { color: #4B5563; font-size: 10.5px; margin-top: 4px; white-space: pre-line; }
    .warn { border: 1px solid #FCD34D; background: #FFFBEB; border-radius: 4px; padding: 8px 10px; font-size: 10px; color: #92400E; margin-top: 6px; }
    .pill { display:inline-block; padding: 1px 7px; border-radius: 9px; font-size: 9px; font-weight: bold; }
    .pill.ok { background:#D1FAE5; color:#065F46; }
    .pill.no { background:#FEE2E2; color:#991B1B; }
    .section { page-break-inside: avoid; }
    .footer { margin-top: 18px; font-size: 9px; color: #9CA3AF; text-align: center; border-top: 1px solid #E5E7EB; padding-top: 6px; }
    ul.tight { margin: 4px 0 0; padding-left: 16px; }
    ul.tight li { margin-bottom: 2px; font-size: 10px; }
</style>
</head>
<body>

{{-- A. COVER --}}
<div class="cover">
    <div class="brand">Avatech Smart-PMIS</div>
    <div class="muted" style="text-transform:uppercase; letter-spacing:0.12em; font-weight:bold;">Project Handover Pack</div>
    <div class="pname">{{ $project->name }}</div>
    <table class="meta-table">
        <tr><td class="k">Klien</td><td>{{ $clientName ?: '—' }}</td></tr>
        <tr><td class="k">Kode Proyek</td><td>{{ $project->code }}</td></tr>
        <tr><td class="k">Versi Pack</td><td>v{{ $pack->version }} · {{ \Illuminate\Support\Str::title($pack->status) }}</td></tr>
        <tr><td class="k">Digenerate</td><td>{{ $generatedAt->translatedFormat('d F Y H:i') }}</td></tr>
    </table>
</div>

{{-- B. PROJECT SUMMARY --}}
<div class="section">
    <h2>Ringkasan Proyek</h2>
    <table class="meta-table">
        <tr><td class="k">Nama</td><td><strong>{{ $project->name }}</strong></td></tr>
        <tr><td class="k">Klien</td><td>{{ $clientName ?: '—' }}</td></tr>
        <tr><td class="k">Fase</td><td>{{ $phaseLabel }}</td></tr>
        <tr><td class="k">Status</td><td>{{ $statusLabel ?: '—' }}</td></tr>
        <tr><td class="k">Progress</td><td>{{ $progress }}%</td></tr>
        <tr><td class="k">Target Selesai</td><td>{{ $project->due_at ? $project->due_at->translatedFormat('d F Y') : '—' }}</td></tr>
    </table>
    @if (filled($pack->summary))
        <div class="note">{{ $pack->summary }}</div>
    @endif
</div>

{{-- C. TEAM & RESPONSIBILITIES --}}
<div class="section">
    <h2>Tim &amp; Tanggung Jawab</h2>
    @if (count($team))
        <table class="data">
            <thead><tr><th style="width:30%">Anggota</th><th style="width:22%">Role</th><th>Tanggung Jawab</th></tr></thead>
            <tbody>
                @foreach ($team as $member)
                    <tr>
                        <td class="title">{{ $member['name'] }}</td>
                        <td>{{ $roleLabel($member['role']) }}</td>
                        <td>
                            @if (! empty($member['responsibilities']))
                                @foreach ($member['responsibilities'] as $r)<span class="badge">{{ $r }}</span> @endforeach
                            @else
                                {{ $member['title'] ?: '—' }}
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <div class="muted">Belum ada anggota tim yang ditugaskan.</div>
    @endif
</div>

{{-- D. SCOPE & MODULES --}}
<div class="section">
    <h2>Scope &amp; Modul</h2>
    @if (count($modules))
        <table class="data">
            <thead><tr><th>Modul</th><th style="width:14%">Status</th><th style="width:14%">Task</th><th style="width:12%">Jam</th></tr></thead>
            <tbody>
                @foreach ($modules as $m)
                    <tr>
                        <td class="title">{{ $m['name'] }}</td>
                        <td><span class="badge">{{ $m['status'] }}</span></td>
                        <td>{{ $m['tasks_done'] }}/{{ $m['tasks_total'] }} selesai</td>
                        <td>{{ $m['hours'] }} jam</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <div class="muted">Belum ada modul WBS.</div>
    @endif
</div>

{{-- E. CHANGE REQUESTS --}}
<div class="section">
    <h2>Change Request</h2>
    @if (count($changeRequests))
        <table class="data">
            <thead><tr><th style="width:12%">Kode</th><th>Judul</th><th style="width:14%">Tipe</th><th style="width:12%">Prioritas</th><th style="width:12%">Status</th><th style="width:10%">Dampak</th></tr></thead>
            <tbody>
                @foreach ($changeRequests as $cr)
                    <tr>
                        <td>{{ $cr['code'] }}</td>
                        <td class="title">{{ $cr['title'] }}@if ($cr['created_task'])<div class="muted">→ Task: {{ $cr['created_task'] }}</div>@endif</td>
                        <td>{{ $cr['type_label'] ?: '—' }}</td>
                        <td>{{ $cr['priority_label'] ?: '—' }}</td>
                        <td><span class="badge">{{ $cr['status_label'] }}</span></td>
                        <td>{{ $cr['timeline_impact_days'] !== null ? '+' . $cr['timeline_impact_days'] . ' hari' : '—' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <div class="muted">Tidak ada change request tercatat.</div>
    @endif
</div>

{{-- F. DESIGN DELIVERABLES --}}
<div class="section">
    <h2>Design Deliverables</h2>
    @if (count($deliverables))
        <table class="data">
            <thead><tr><th>Deliverable</th><th style="width:28%">Task</th><th style="width:16%">PDF</th><th style="width:24%">Figma / Link</th></tr></thead>
            <tbody>
                @foreach ($deliverables as $d)
                    <tr>
                        <td class="title">{{ $d['title'] }}</td>
                        <td>{{ $d['task'] }}</td>
                        <td>{{ $d['has_pdf'] ? 'Tersedia (PDF)' : '—' }}</td>
                        <td>{{ $d['figma'] ?: '—' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <div class="muted" style="margin-top:4px;">Dokumen desain dirujuk melalui link/label; file PDF tidak disematkan di dalam pack ini.</div>
    @else
        <div class="muted">Tidak ada design deliverable.</div>
    @endif
</div>

{{-- G. QC SUMMARY --}}
<div class="section">
    <h2>Ringkasan QC</h2>
    <table class="summary">
        <tr>
            <td><div class="val">{{ $qcSummary['passed'] ?? 0 }}/{{ $qcSummary['total'] ?? 0 }}</div><div class="lbl">Lulus</div></td>
            <td><div class="val">{{ $qcSummary['failed'] ?? 0 }}</div><div class="lbl">Gagal</div></td>
            <td><div class="val">{{ $qcSummary['pending'] ?? 0 }}</div><div class="lbl">Pending</div></td>
            <td><div class="val">{{ $qcSummary['retest'] ?? 0 }}</div><div class="lbl">Retest</div></td>
        </tr>
    </table>
</div>

{{-- H. UAT SUMMARY --}}
<div class="section">
    <h2>Ringkasan UAT</h2>
    <table class="summary">
        <tr>
            <td><div class="val">{{ $uatSummary['passed'] ?? 0 }}/{{ $uatSummary['total'] ?? 0 }}</div><div class="lbl">Lulus</div></td>
            <td><div class="val">{{ $uatSummary['failed'] ?? 0 }}</div><div class="lbl">Gagal</div></td>
            <td><div class="val">{{ $uatSummary['blocked'] ?? 0 }}</div><div class="lbl">Tertahan</div></td>
            <td><div class="val">{{ $uatSummary['revision_needed'] ?? 0 }}</div><div class="lbl">Revisi</div></td>
            <td><div class="val">{{ $uatSummary['pending'] ?? 0 }}</div><div class="lbl">Pending</div></td>
        </tr>
    </table>
    @if (count($uatItems))
        <table class="data" style="margin-top:8px;">
            <thead><tr><th>Item UAT</th><th style="width:16%">Kategori</th><th style="width:12%">Prioritas</th><th style="width:12%">Status</th><th style="width:18%">Diuji</th></tr></thead>
            <tbody>
                @foreach ($uatItems as $u)
                    <tr>
                        <td class="title">{{ $u['title'] }}@if (filled($u['notes']))<div class="muted">{{ \Illuminate\Support\Str::limit($u['notes'], 120) }}</div>@endif</td>
                        <td>{{ $u['category_label'] }}</td>
                        <td>{{ $u['priority_label'] }}</td>
                        <td><span class="badge">{{ $u['status_label'] }}</span></td>
                        <td>{{ $u['tested_at'] ?: '—' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</div>

{{-- I. SIGN-OFF SUMMARY --}}
<div class="section">
    <h2>Ringkasan Sign-off</h2>
    @if (count($signoffs))
        <table class="data">
            <thead><tr><th style="width:24%">Jenis</th><th style="width:16%">Status</th><th>Ditandatangani</th><th style="width:24%">Waktu</th></tr></thead>
            <tbody>
                @foreach ($signoffs as $s)
                    <tr>
                        <td class="title">{{ $s['type_label'] }}</td>
                        <td><span class="badge">{{ $s['status_label'] }}</span></td>
                        <td>{{ $s['signed_by_name'] ?: '—' }}@if ($s['signed_by_role']) <span class="muted">({{ $s['signed_by_role'] }})</span>@endif</td>
                        <td>{{ $s['signed_at'] ?: '—' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <div class="muted">Belum ada sign-off tercatat.</div>
    @endif
    @if (count($approvedReviews))
        <div class="note" style="margin-top:6px;"><strong>Bukti persetujuan klien:</strong>
            <ul class="tight">
                @foreach ($approvedReviews as $r)<li>{{ $r['title'] }} — disetujui {{ $r['approved_at'] ?: '—' }}</li>@endforeach
            </ul>
        </div>
    @endif
</div>

{{-- J. DEPLOYMENT / ACCESS HANDOVER --}}
<div class="section">
    <h2>Deployment &amp; Akses</h2>
    <table class="meta-table">
        <tr><td class="k">Production URL</td><td>{{ $pack->deployment_url ?: '—' }}</td></tr>
        <tr><td class="k">Staging URL</td><td>{{ $pack->staging_url ?: '—' }}</td></tr>
        <tr><td class="k">Admin URL</td><td>{{ $pack->admin_url ?: '—' }}</td></tr>
        <tr><td class="k">Repository</td><td>{{ $pack->repository_url ?: '—' }}</td></tr>
        <tr><td class="k">Status Credential</td><td>{{ $credentialLabel }}</td></tr>
    </table>
    <div class="warn">Dokumen ini tidak menyimpan password atau secret. Credential diserahkan melalui kanal terpisah yang aman.</div>
</div>

{{-- K. MAINTENANCE NOTES --}}
<div class="section">
    <h2>Catatan Maintenance &amp; Langkah Lanjut</h2>
    @if (filled($pack->maintenance_notes))
        <div class="note">{{ $pack->maintenance_notes }}</div>
    @else
        <div class="muted">Belum ada catatan maintenance.</div>
    @endif
    @if (filled($pack->client_notes))
        <h3 style="font-size:11px; color:#1E1B4B; margin:10px 0 2px;">Catatan untuk Klien</h3>
        <div class="note">{{ $pack->client_notes }}</div>
    @endif
</div>

<div class="footer">
    Avatech Smart-PMIS · Handover Pack {{ $project->name }} v{{ $pack->version }} · {{ $generatedAt->translatedFormat('d F Y H:i') }}<br>
    Dokumen internal/serah terima. Tidak memuat password, secret, atau kredensial apa pun.
</div>

</body>
</html>
