<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProjectController extends Controller
{
    private const ID_MONTHS = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Ags', 'Sep', 'Okt', 'Nov', 'Des'];

    private const STATUS_UI = [
        'on-track'  => ['pill' => 'bg-emerald-100 text-emerald-700', 'dot' => 'bg-emerald-500', 'label' => 'Active'],
        'attention' => ['pill' => 'bg-amber-100 text-amber-700',     'dot' => 'bg-amber-500',   'label' => 'Needs Attention'],
        'critical'  => ['pill' => 'bg-rose-100 text-rose-700',       'dot' => 'bg-rose-500',    'label' => 'Critical'],
    ];

    private const DESC_MAP = [
        'AC' => 'Sales pipeline & customer dashboard internal untuk tim sales B2B PT Maju Jaya. Mencakup modul autentikasi, dashboard ringkas, dan project hub Kanban.',
        'BP' => 'Self-service portal untuk merchant onboarding & verifikasi dokumen end-to-end.',
        'GA' => 'API gateway terpusat untuk integrasi sistem internal & partner enterprise.',
        'DL' => 'Mobile + dashboard untuk monitoring armada & tracking pengiriman real-time.',
        'EX' => 'Internal tooling exchange data antar sistem legacy klien enterprise.',
        'ZN' => 'Customer-facing mobile app untuk loyalty program & point reward.',
        'OT' => 'Modul onboarding karyawan dengan e-signing & dokumen otomatis.',
        'KP' => 'Point-of-sale lightweight untuk SMB ritel dengan multi-cabang.',
    ];

    private const PROJECT_META = [
        'AC' => ['team' => ['AR', 'IK', 'YP'], 'team_more' => 1, 'tasks_done' => 21, 'tasks_total' => 42, 'mom' => 6],
        'BP' => ['team' => ['YP', 'AR'],       'team_more' => 0, 'tasks_done' => 8,  'tasks_total' => 28, 'mom' => 4],
        'GA' => ['team' => ['IK', 'AR', 'FA'], 'team_more' => 0, 'tasks_done' => 26, 'tasks_total' => 30, 'mom' => 8],
        'DL' => ['team' => ['FA', 'IK', 'YP'], 'team_more' => 2, 'tasks_done' => 15, 'tasks_total' => 48, 'mom' => 3],
        'EX' => ['team' => ['AR', 'YP'],       'team_more' => 0, 'tasks_done' => 2,  'tasks_total' => 18, 'mom' => 2],
        'ZN' => ['team' => ['YP', 'FA', 'IK'], 'team_more' => 0, 'tasks_done' => 19, 'tasks_total' => 34, 'mom' => 5],
        'OT' => ['team' => ['AR', 'YP'],       'team_more' => 0, 'tasks_done' => 24, 'tasks_total' => 24, 'mom' => 9],
        'KP' => ['team' => ['IK', 'AR'],       'team_more' => 1, 'tasks_done' => 30, 'tasks_total' => 38, 'mom' => 7],
    ];

    public function index(Request $request)
    {
        $archiveScope = $request->query('archive', 'active');
        if (! in_array($archiveScope, ['active', 'archived', 'all'], true)) {
            $archiveScope = 'active';
        }

        $projects = Project::with(['client', 'lead'])
            ->when($archiveScope === 'active', fn ($query) => $query->whereNull('archived_at'))
            ->when($archiveScope === 'archived', fn ($query) => $query->whereNotNull('archived_at'))
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get()
            ->map(fn (Project $project) => $this->projectRow($project))
            ->all();

        return view('projects.index', [
            'title'    => 'Project Master',
            'projects' => $projects,
            'clients'  => Client::orderBy('name')->get(['id', 'name']),
            'archiveScope' => $archiveScope,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code'        => ['required', 'string', 'max:4', 'regex:/^[A-Za-z0-9]+$/', Rule::unique('projects', 'code')],
            'name'        => ['required', 'string', 'max:160'],
            'client_id'   => ['required', Rule::exists('clients', 'id')],
            'description' => ['nullable', 'string', 'max:2000'],
            'due_at'      => ['nullable', 'date'],
        ], [
            'code.required'      => 'Kode proyek wajib diisi.',
            'code.regex'         => 'Kode hanya boleh huruf dan angka.',
            'code.unique'        => 'Kode proyek sudah digunakan.',
            'name.required'      => 'Nama proyek wajib diisi.',
            'client_id.required' => 'Klien wajib dipilih.',
            'client_id.exists'   => 'Klien tidak valid.',
            'due_at.date'        => 'Due date tidak valid.',
        ]);

        $project = Project::create([
            'code'             => mb_strtoupper($validated['code']),
            'color'            => '#7C3AED',
            'name'             => $validated['name'],
            'description'      => $validated['description'] ?? null,
            'client_id'        => $validated['client_id'],
            'lead_user_id'     => null,
            'phase'            => 'Planning',
            'due_at'           => $validated['due_at'] ?? null,
            'progress'         => 0,
            'status'           => 'on-track',
            'ai_wbs_generated' => false,
            'is_featured'      => false,
        ]);

        return redirect()
            ->route('projects.show', $project)
            ->with('status', 'Proyek "' . $project->name . '" berhasil dibuat.');
    }

    public function update(Request $request, Project $project)
    {
        $validated = $this->validateProject($request, $project);

        $project->update([
            'code'        => mb_strtoupper($validated['code']),
            'name'        => $validated['name'],
            'client_id'   => $validated['client_id'],
            'description' => $validated['description'] ?? null,
            'due_at'      => $validated['due_at'] ?? null,
        ]);

        return redirect()
            ->back()
            ->with('status', 'Proyek "' . $project->name . '" berhasil diperbarui.');
    }

    public function archive(Project $project)
    {
        if (! $project->archived_at) {
            $project->forceFill(['archived_at' => now()])->save();
        }

        return redirect()
            ->route('projects.index')
            ->with('status', 'Proyek "' . $project->name . '" berhasil diarsipkan.');
    }

    public function restore(Project $project)
    {
        if ($project->archived_at) {
            $project->forceFill(['archived_at' => null])->save();
        }

        return redirect()
            ->route('projects.index')
            ->with('status', 'Proyek "' . $project->name . '" berhasil dipulihkan.');
    }

    public function show(Project $project)
    {
        $project->load(['client', 'lead']);

        $statusUi = self::STATUS_UI[$project->status] ?? self::STATUS_UI['on-track'];

        $leadName     = $project->lead?->name;
        $leadInitials = $leadName ? $this->initials($leadName) : '—';

        $desc = $project->description ?: (self::DESC_MAP[$project->code] ?? 'Belum ada deskripsi untuk proyek ini.');

        $due       = $project->due_at ? $this->formatDateId($project->due_at) : '—';
        $createdAt = $project->created_at ? $this->formatDateLongId($project->created_at) : '—';

        return view('projects.show', [
            'title'        => $project->name,
            'project'      => $project,
            'desc'         => $desc,
            'createdBy'    => 'Ahmad Rafiadly A.',
            'createdAt'    => $createdAt,
            'dueFormatted' => $due,
            'statusUi'     => $statusUi,
            'leadName'     => $leadName,
            'leadInitials' => $leadInitials,
            'useReferenceProjectData' => $this->usesReferenceProjectData($project),
            'clients'      => Client::orderBy('name')->get(['id', 'name']),
        ]);
    }

    private function validateProject(Request $request, ?Project $project = null): array
    {
        return $request->validate([
            'code'        => ['required', 'string', 'max:4', 'regex:/^[A-Za-z0-9]+$/', Rule::unique('projects', 'code')->ignore($project?->id)],
            'name'        => ['required', 'string', 'max:160'],
            'client_id'   => ['required', Rule::exists('clients', 'id')],
            'description' => ['nullable', 'string', 'max:2000'],
            'due_at'      => ['nullable', 'date'],
        ], [
            'code.required'      => 'Kode proyek wajib diisi.',
            'code.regex'         => 'Kode hanya boleh huruf dan angka.',
            'code.unique'        => 'Kode proyek sudah digunakan.',
            'name.required'      => 'Nama proyek wajib diisi.',
            'client_id.required' => 'Klien wajib dipilih.',
            'client_id.exists'   => 'Klien tidak valid.',
            'due_at.date'        => 'Due date tidak valid.',
        ]);
    }

    private function usesReferenceProjectData(Project $project): bool
    {
        return array_key_exists($project->code, self::PROJECT_META);
    }

    private function initials(string $fullName): string
    {
        $parts = array_values(array_filter(preg_split('/\s+/', trim($fullName)) ?: []));
        if (empty($parts)) {
            return '?';
        }
        if (count($parts) === 1) {
            return mb_strtoupper(mb_substr($parts[0], 0, 2));
        }
        return mb_strtoupper(mb_substr($parts[0], 0, 1) . mb_substr($parts[1], 0, 1));
    }

    private function formatDateId(\Illuminate\Support\Carbon $date): string
    {
        $month = self::ID_MONTHS[((int) $date->format('n')) - 1] ?? $date->format('M');
        return $date->format('d') . ' ' . $month;
    }

    private function formatDateLongId(\Illuminate\Support\Carbon $date): string
    {
        $month = self::ID_MONTHS[((int) $date->format('n')) - 1] ?? $date->format('M');
        return $date->format('d') . ' ' . $month . ' ' . $date->format('Y');
    }

    private function projectRow(Project $project): array
    {
        $meta = self::PROJECT_META[$project->code] ?? null;
        $team = $meta['team'] ?? ($project->lead ? [$this->initials($project->lead->name)] : []);

        return [
            'id'           => $project->id,
            'code'         => $project->code,
            'color'        => $project->color,
            'name'         => $project->name,
            'client'       => $project->client?->name ?? '—',
            'client_id'    => $project->client_id,
            'desc'         => $project->description ?: (self::DESC_MAP[$project->code] ?? 'Belum ada deskripsi untuk proyek ini.'),
            'phase'        => $project->phase,
            'phase_key'    => $this->phaseKey($project->phase),
            'due'          => $project->due_at ? $this->formatDateId($project->due_at) : '—',
            'due_at'       => $project->due_at?->format('Y-m-d'),
            'progress'     => (int) $project->progress,
            'status'       => $project->status,
            'status_label' => $this->statusLabel($project->status),
            'archived'     => (bool) $project->archived_at,
            'team'         => $team,
            'team_more'    => (int) ($meta['team_more'] ?? 0),
            'tasks_done'   => (int) ($meta['tasks_done'] ?? 0),
            'tasks_total'  => (int) ($meta['tasks_total'] ?? 0),
            'mom'          => (int) ($meta['mom'] ?? 0),
            'ai_flag'      => (bool) $project->ai_wbs_generated,
        ];
    }

    private function phaseKey(string $phase): string
    {
        return match (mb_strtolower($phase)) {
            'design' => 'design',
            'development' => 'dev',
            'qa' => 'qa',
            'done' => 'done',
            default => 'planning',
        };
    }

    private function statusLabel(string $status): string
    {
        return match ($status) {
            'attention' => 'Needs Attention',
            'critical' => 'Critical',
            default => 'On Track',
        };
    }
}
