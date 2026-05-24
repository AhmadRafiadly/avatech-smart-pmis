<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Project;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ClientController extends Controller
{
    private const TIER_LABELS = [
        'strategic' => 'Strategic',
        'growth' => 'Growth',
        'standard' => 'Standard',
        'prospect' => 'Prospect',
    ];

    public function index(Request $request)
    {
        $archiveScope = $request->query('archive', 'active');
        if (! in_array($archiveScope, ['active', 'archived', 'all'], true)) {
            $archiveScope = 'active';
        }

        $clients = Client::with(['projects' => fn ($query) => $query->orderByDesc('created_at')->orderByDesc('id')])
            ->when($archiveScope === 'active', fn ($query) => $query->whereNull('archived_at'))
            ->when($archiveScope === 'archived', fn ($query) => $query->whereNotNull('archived_at'))
            ->orderByRaw("FIELD(tier, 'strategic', 'growth', 'standard', 'prospect')")
            ->orderBy('name')
            ->get()
            ->map(fn (Client $client) => $this->clientRow($client))
            ->all();

        return view('clients.index', [
            'title' => 'Client Directory',
            'clients' => $clients,
            'archiveScope' => $archiveScope,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validateClient($request);

        $client = Client::create([
            'code' => mb_strtoupper($validated['code']),
            'name' => $validated['name'],
            'industry' => $validated['industry'],
            'location' => $validated['location'],
            'pic_name' => $validated['pic_name'],
            'pic_role' => $validated['pic_role'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'tier' => 'prospect',
            'total_engagement' => 0,
            'relationship_health' => 50,
            'last_touch_label' => 'baru saja',
        ]);

        AuditLogger::logCreated($client, 'Client Directory', 'Menambah klien <strong>' . e($client->name) . '</strong>');

        return redirect()
            ->route('clients.index', ['open' => 'client:' . $client->id])
            ->with('status', 'Klien "' . $client->name . '" berhasil ditambahkan.');
    }

    public function update(Request $request, Client $client)
    {
        $validated = $this->validateClient($request, $client);

        $original = $client->getOriginal();

        $client->update([
            'code' => mb_strtoupper($validated['code']),
            'name' => $validated['name'],
            'industry' => $validated['industry'],
            'location' => $validated['location'],
            'pic_name' => $validated['pic_name'],
            'pic_role' => $validated['pic_role'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
        ]);

        AuditLogger::logUpdated($client, 'Client Directory', 'Memperbarui klien <strong>' . e($client->name) . '</strong>', $original);

        return redirect()
            ->route('clients.index', [
                'archive' => $request->input('_archive_scope', $client->archived_at ? 'archived' : 'active'),
                'open' => 'client:' . $client->id,
            ])
            ->with('status', 'Klien "' . $client->name . '" berhasil diperbarui.');
    }

    public function archive(Client $client)
    {
        if (! $client->archived_at) {
            $client->forceFill(['archived_at' => now()])->save();
            AuditLogger::logArchived($client, 'Client Directory', 'Mengarsipkan klien <strong>' . e($client->name) . '</strong>');
        }

        return redirect()
            ->route('clients.index')
            ->with('status', 'Klien "' . $client->name . '" berhasil diarsipkan.');
    }

    public function restore(Client $client)
    {
        if ($client->archived_at) {
            $client->forceFill(['archived_at' => null])->save();
            AuditLogger::logRestored($client, 'Client Directory', 'Memulihkan klien <strong>' . e($client->name) . '</strong>');
        }

        return redirect()
            ->route('clients.index', ['open' => 'client:' . $client->id])
            ->with('status', 'Klien "' . $client->name . '" berhasil dipulihkan.');
    }

    private function validateClient(Request $request, ?Client $client = null): array
    {
        $codeRule = Rule::unique('clients', 'code');
        if ($client) {
            $codeRule->ignore($client->id);
        }

        return $request->validate([
            'code' => ['required', 'string', 'max:8', 'regex:/^[A-Za-z0-9]+$/', $codeRule],
            'name' => ['required', 'string', 'max:160'],
            'industry' => ['required', 'string', 'max:120'],
            'location' => ['required', 'string', 'max:120'],
            'pic_name' => ['required', 'string', 'max:160'],
            'pic_role' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:160'],
            'phone' => ['required', 'string', 'max:40'],
        ], [
            'code.required' => 'Kode klien wajib diisi.',
            'code.regex' => 'Kode hanya boleh huruf dan angka.',
            'code.unique' => 'Kode klien sudah digunakan.',
            'name.required' => 'Nama perusahaan wajib diisi.',
            'industry.required' => 'Industri wajib diisi.',
            'location.required' => 'Lokasi wajib diisi.',
            'pic_name.required' => 'Nama PIC wajib diisi.',
            'pic_role.required' => 'Role PIC wajib diisi.',
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'phone.required' => 'Nomor WhatsApp wajib diisi.',
        ]);
    }

    private function clientRow(Client $client): array
    {
        $projects = $client->projects;
        $activeProjects = $projects->where('phase', '!=', 'Done')->count();
        $totalProjects = max((int) ($client->total_engagement ?? 0), $projects->count());
        $health = (int) ($client->relationship_health ?? 50);
        $picName = $client->pic_name ?: 'Belum ada PIC';
        $tier = self::TIER_LABELS[$client->tier] ?? 'Prospect';
        $lastTouch = $client->last_touch_label ?: $client->created_at?->diffForHumans() ?: 'baru saja';

        return [
            'id' => $client->id,
            'code' => $client->code ?: $this->initials($client->name),
            'name' => $client->name,
            'raw_code' => $client->code,
            'raw_name' => $client->name,
            'raw_industry' => $client->industry,
            'raw_location' => $client->location,
            'raw_pic_name' => $client->pic_name,
            'raw_pic_role' => $client->pic_role,
            'raw_email' => $client->email,
            'raw_phone' => $client->phone,
            'archived' => (bool) $client->archived_at,
            'industry' => $client->industry ?: 'Belum diisi',
            'location' => $client->location ?: 'Belum diisi',
            'tier' => $tier,
            'health' => max(0, min(100, $health)),
            'active_projects' => $activeProjects,
            'total_projects' => $totalProjects,
            'last_touch' => $lastTouch,
            'last_touch_sort' => $this->lastTouchSort($lastTouch),
            'pic' => $picName,
            'pic_initials' => $this->initials($picName),
            'pic_role' => $client->pic_role ?: 'Belum diisi',
            'attention' => $health < 65,
            'phone' => $client->phone ?: '-',
            'email' => $client->email ?: '-',
            'wa_link' => $this->whatsAppLink($client->phone),
            'email_link' => $this->gmailLink($client->email),
            'desc' => $client->description ?: 'Profil klien belum dilengkapi.',
            'projects' => $projects->map(fn (Project $project) => [
                'id' => $project->id,
                'code' => $project->code,
                'color' => $project->color,
                'name' => $project->name,
                'phase' => $project->phase,
                'progress' => (int) $project->progress,
                'status' => $project->phase === 'Done' ? 'done' : $project->status,
                'status_label' => $this->projectStatusLabel($project),
            ])->all(),
            'timeline' => [
                ['color' => '#7C3AED', 'text' => '<strong>Profil klien</strong> tersedia dari database.', 'time' => $lastTouch],
            ],
        ];
    }

    private function whatsAppLink(?string $phone): string
    {
        $digits = preg_replace('/\D+/', '', (string) $phone);
        if ($digits === '') {
            return '#';
        }
        if (str_starts_with($digits, '0')) {
            $digits = '62' . substr($digits, 1);
        }

        return 'https://wa.me/' . $digits;
    }

    private function gmailLink(?string $email): string
    {
        if (! $email) {
            return '#';
        }

        return 'https://mail.google.com/mail/?view=cm&fs=1&to=' . rawurlencode($email);
    }

    private function initials(string $value): string
    {
        $parts = array_values(array_filter(preg_split('/\s+/', trim($value)) ?: []));
        if ($parts === []) {
            return '?';
        }
        if (count($parts) === 1) {
            return mb_strtoupper(mb_substr($parts[0], 0, 2));
        }

        return mb_strtoupper(mb_substr($parts[0], 0, 1) . mb_substr($parts[1], 0, 1));
    }

    private function lastTouchSort(string $label): int
    {
        if (str_contains($label, 'baru')) {
            return 0;
        }

        preg_match('/\d+/', $label, $match);
        return (int) ($match[0] ?? 0);
    }

    private function projectStatusLabel(Project $project): string
    {
        if ($project->phase === 'Done') {
            return 'Selesai';
        }

        return match ($project->status) {
            'attention' => 'Needs Attention',
            'critical' => 'Critical',
            default => 'On Track',
        };
    }
}
