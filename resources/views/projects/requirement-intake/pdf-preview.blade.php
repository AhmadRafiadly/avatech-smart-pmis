<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title }}</title>
    <style>
        *{box-sizing:border-box}body{margin:0;background:#f8fafc;color:#334155;font:14px/1.5 system-ui,-apple-system,sans-serif}main{padding:24px}.header,.card{max-width:1440px;margin:0 auto}.header{display:flex;justify-content:space-between;align-items:center;gap:20px;margin-bottom:20px}.eyebrow{margin:0;color:#8b5cf6;font-size:11px;font-weight:700;letter-spacing:.18em;text-transform:uppercase}h1{margin:4px 0;color:#1e1b4b;font-size:24px}.project,.meta,.note{margin:0;color:#64748b}.actions{display:flex;flex-wrap:wrap;gap:8px}.button{display:inline-flex;align-items:center;height:40px;padding:0 16px;border:1px solid #ede9fe;border-radius:12px;background:#fff;color:#475569;font-weight:600;text-decoration:none}.button.primary{border-color:#7c3aed;background:#7c3aed;color:#fff}.card{overflow:hidden;border:1px solid #ede9fe;border-radius:16px;background:#fff;box-shadow:0 2px 8px rgba(109,40,217,.06)}.details{display:flex;justify-content:space-between;align-items:center;gap:16px;padding:16px 20px;border-bottom:1px solid #f5f3ff}.name{margin:0;color:#1e1b4b;font-weight:700}.viewer{padding:16px;background:#f1f5f9}iframe{display:block;width:100%;height:80vh;min-height:520px;border:1px solid #e2e8f0;border-radius:12px;background:#fff}.loading,.warning{padding:14px 16px;border-radius:12px}.loading{border:1px solid #ddd6fe;background:#f5f3ff;color:#5b21b6}.warning{border:1px solid #fde68a;background:#fffbeb;color:#92400e}.hidden{display:none}@media(max-width:720px){main{padding:16px}.header,.details{align-items:flex-start;flex-direction:column}}
    </style>
</head>
<body>
<main>
    <header class="header">
        <div>
            <p class="eyebrow">Requirement Intake</p>
            <h1>{{ $title }}</h1>
            <p class="project">{{ $project->code }} - {{ $project->name }}</p>
        </div>
        <nav class="actions" aria-label="Aksi dokumen">
            <a href="{{ route('projects.show', $project) }}#intake" class="button">Back to Project</a>
            <a href="{{ $downloadUrl }}" class="button primary">Download Original</a>
        </nav>
    </header>

    <section class="card">
        <div class="details">
            <div>
                <p class="name">{{ $intake->title }}</p>
                <p class="meta">{{ $filename }} · {{ $type }} · {{ number_format($size / 1024, 1) }} KB</p>
            </div>
            <p class="note">Preview dimuat di halaman internal Smart-PMIS.</p>
        </div>
        <div class="viewer">
            @if ($pdfBase64)
                <div id="pdf-loading" class="loading">Memuat preview PDF…</div>
                <div id="pdf-error" class="warning hidden">Preview PDF tidak dapat dibuat. Gunakan Download Original untuk membuka file secara manual.</div>
                <iframe id="pdf-frame" class="hidden" title="Preview PDF Requirement Intake"></iframe>
                <script type="application/json" id="pdf-payload" nonce="{{ $nonce }}">@json($pdfBase64)</script>
                <script nonce="{{ $nonce }}">
                    (() => {
                        let objectUrl;
                        try {
                            const base64 = JSON.parse(document.getElementById('pdf-payload').textContent);
                            const binary = atob(base64);
                            const bytes = new Uint8Array(binary.length);
                            for (let index = 0; index < binary.length; index++) bytes[index] = binary.charCodeAt(index);
                            objectUrl = URL.createObjectURL(new Blob([bytes], { type: 'application/pdf' }));
                            const frame = document.getElementById('pdf-frame');
                            frame.src = objectUrl;
                            frame.classList.remove('hidden');
                            document.getElementById('pdf-loading').classList.add('hidden');
                        } catch (error) {
                            document.getElementById('pdf-loading').classList.add('hidden');
                            document.getElementById('pdf-error').classList.remove('hidden');
                        }
                        window.addEventListener('unload', () => {
                            if (objectUrl) URL.revokeObjectURL(objectUrl);
                        });
                    })();
                </script>
            @else
                <div class="warning">{{ $previewMessage ?: 'Preview tidak dapat dimuat otomatis. Gunakan Download Original untuk membuka file secara manual.' }}</div>
            @endif
        </div>
    </section>
</main>
</body>
</html>
