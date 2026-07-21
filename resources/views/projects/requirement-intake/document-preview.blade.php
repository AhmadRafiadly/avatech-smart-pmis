<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }}</title>
    <style>
        body{margin:0;background:#f8fafc;color:#1e293b;font:14px/1.6 system-ui,sans-serif}.wrap{max-width:900px;margin:auto;padding:28px 28px 48px}.card{background:#fff;border:1px solid #e2e8f0;border-radius:14px;padding:24px}.meta{color:#64748b;font-size:12px;margin-bottom:20px}.body{white-space:pre-wrap;overflow-wrap:anywhere}.notice{padding:14px;border-radius:10px;background:#fef3c7;color:#92400e}.summary{white-space:pre-wrap;margin-top:16px}@media(max-width:640px){.wrap{padding:16px 16px 40px}.card{padding:18px}}
    </style>
</head>
<body>
    @php
        $isDocx = strtolower(pathinfo((string) $filename, PATHINFO_EXTENSION)) === 'docx'
            || strtolower(trim(explode(';', (string) $type)[0])) === 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';
    @endphp
    <main class="wrap">
        <article class="card">
            <h1>{{ $title }}</h1>
            <div class="meta">{{ $filename }} · {{ $type }} · {{ number_format($size / 1024, 1) }} KB</div>
            <strong>{{ $isDocx ? 'Preview teks hasil ekstraksi' : 'Preview teks TXT' }}</strong>
            @if ($isDocx)
                <div class="notice">Tampilan ini adalah teks hasil ekstraksi. Tata letak Word asli, tabel, dan gambar dapat berbeda atau tidak ditampilkan.</div>
            @endif
            @if (trim((string) $body) !== '')
                <div class="body">{{ $body }}</div>
            @else
                <div class="notice">Teks dokumen tidak tersedia untuk preview.</div>
                @if (trim((string) $summary) !== '')
                    <div class="summary">{{ $summary }}</div>
                @endif
            @endif
        </article>
    </main>
</body>
</html>
