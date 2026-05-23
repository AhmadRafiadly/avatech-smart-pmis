<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\TeamAssignment;
use App\Models\TeamWorkload;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class TeamController extends Controller
{
    private const UI_ROLES = [
        'sa_qa' => ['label' => 'SA/QA', 'rbac' => 'sa_qa'],
        'ui_ux' => ['label' => 'UI/UX Designer', 'rbac' => 'uiux_designer'],
        'fullstack_dev' => ['label' => 'Fullstack Dev', 'rbac' => 'fullstack_dev'],
    ];

    private const RBAC_TO_UI = [
        'sa_qa' => 'sa_qa',
        'uiux_designer' => 'ui_ux',
        'ui_ux' => 'ui_ux',
        'fullstack_dev' => 'fullstack_dev',
    ];

    public function index(Request $request)
    {
        $archiveScope = $request->query('archive', 'active');
        if (! in_array($archiveScope, ['active', 'archived', 'all'], true)) {
            $archiveScope = 'active';
        }

        $members = User::with(['roles', 'workload', 'teamAssignments.project'])
            ->whereDoesntHave('roles', fn ($query) => $query->where('name', 'ceo_pm'))
            ->whereHas('roles', fn ($query) => $query->whereIn('name', ['sa_qa', 'uiux_designer', 'ui_ux', 'fullstack_dev']))
            ->when($archiveScope === 'active', fn ($query) => $query->whereNull('archived_at'))
            ->when($archiveScope === 'archived', fn ($query) => $query->whereNotNull('archived_at'))
            ->orderBy('name')
            ->get()
            ->map(fn (User $user) => $this->memberRow($user))
            ->all();

        return view('team.index', [
            'title' => 'Team Management',
            'members' => $members,
            'projects' => Project::orderBy('name')->get(['id', 'code', 'color', 'name']),
            'archiveScope' => $archiveScope,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validateMember($request);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make(Str::password(16)),
            'phone' => $validated['phone'] ?? null,
            'level' => $validated['level'],
            'skills' => $this->skillsFromInput($validated['skills'] ?? ''),
            'avatar_color' => $validated['avatar_color'] ?? $this->colorForRole($validated['role']),
            'email_verified_at' => now(),
        ]);

        $user->syncRoles([self::UI_ROLES[$validated['role']]['rbac']]);

        TeamWorkload::create([
            'user_id' => $user->id,
            'load_pct' => 0,
            'active_tasks' => 0,
            'is_sim' => false,
        ]);

        return redirect()
            ->route('team.index', ['open' => 'member:' . $user->id])
            ->with('status', 'Anggota "' . $user->name . '" berhasil ditambahkan.');
    }

    public function update(Request $request, User $member)
    {
        $this->ensureOperationalMember($member);
        $validated = $this->validateMember($request, $member);

        $member->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? $member->phone,
            'level' => $validated['level'],
            'skills' => $this->skillsFromInput($validated['skills'] ?? ''),
            'avatar_color' => $validated['avatar_color'] ?? $member->avatar_color ?? $this->colorForRole($validated['role']),
        ]);

        $member->syncRoles([self::UI_ROLES[$validated['role']]['rbac']]);

        return redirect()
            ->route('team.index', [
                'archive' => $request->input('_archive_scope', $member->archived_at ? 'archived' : 'active'),
                'open' => 'member:' . $member->id,
            ])
            ->with('status', 'Anggota "' . $member->name . '" berhasil diperbarui.');
    }

    public function archive(User $member)
    {
        $this->ensureOperationalMember($member);

        if (! $member->archived_at) {
            $member->forceFill(['archived_at' => now()])->save();
        }

        return redirect()
            ->route('team.index')
            ->with('status', 'Anggota "' . $member->name . '" berhasil diarsipkan.');
    }

    public function restore(User $member)
    {
        $this->ensureOperationalMember($member);

        if ($member->archived_at) {
            $member->forceFill(['archived_at' => null])->save();
        }

        return redirect()
            ->route('team.index', ['open' => 'member:' . $member->id])
            ->with('status', 'Anggota "' . $member->name . '" berhasil dipulihkan.');
    }

    public function storeAssignment(Request $request, User $member)
    {
        $this->ensureOperationalMember($member);

        $validated = $request->validate([
            'project_id' => ['required', Rule::exists('projects', 'id')],
            'title' => ['required', 'string', 'max:160'],
            'type' => ['required', 'string', 'max:60'],
            'status' => ['required', 'string', 'max:60'],
            'due_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ], [
            'project_id.required' => 'Proyek wajib dipilih.',
            'project_id.exists' => 'Proyek tidak valid.',
            'title.required' => 'Ringkasan penugasan wajib diisi.',
            'type.required' => 'Tipe penugasan wajib diisi.',
            'status.required' => 'Status wajib dipilih.',
        ]);

        TeamAssignment::create([
            'user_id' => $member->id,
            'project_id' => $validated['project_id'],
            'title' => $validated['title'],
            'type' => $validated['type'],
            'status' => $validated['status'],
            'due_date' => $validated['due_date'] ?? null,
            'notes' => $validated['notes'] ?? null,
        ]);

        return redirect()
            ->route('team.index', ['open' => 'member:' . $member->id])
            ->with('status', 'Penugasan untuk "' . $member->name . '" berhasil disimpan.');
    }

    private function validateMember(Request $request, ?User $member = null): array
    {
        $emailRule = Rule::unique('users', 'email');
        if ($member) {
            $emailRule->ignore($member->id);
        }

        return $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'email' => ['required', 'email', 'max:160', $emailRule],
            'role' => ['required', Rule::in(array_keys(self::UI_ROLES))],
            'phone' => ['nullable', 'string', 'max:40'],
            'level' => ['required', 'string', 'max:40'],
            'skills' => ['nullable', 'string', 'max:1000'],
            'avatar_color' => ['nullable', 'string', 'max:9'],
        ], [
            'name.required' => 'Nama anggota wajib diisi.',
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'email.unique' => 'Email sudah digunakan.',
            'role.required' => 'Role wajib dipilih.',
            'role.in' => 'Role tidak valid.',
            'level.required' => 'Level wajib dipilih.',
        ]);
    }

    private function memberRow(User $user): array
    {
        $roleName = $user->roles->first()?->name ?? '';
        $roleKey = self::RBAC_TO_UI[$roleName] ?? 'fullstack_dev';
        $workload = $user->workload;
        $assignments = $user->teamAssignments->sortByDesc('created_at')->values();
        $load = (int) ($workload?->load_pct ?? 0);
        $capacityHours = 40;
        $skills = $user->skills ?: $this->fallbackSkills($roleKey);
        $joinedAt = $user->created_at ?: now();
        $openAssignments = $assignments->whereNotIn('status', ['done', 'completed']);
        $ledProjects = Project::where('lead_user_id', $user->id)->count();
        $activeProjects = $assignments->pluck('project_id')->unique()->count() + $ledProjects;

        return [
            'id' => $user->id,
            'name' => $user->name,
            'initials' => $this->initials($user->name),
            'email' => $user->email,
            'role_key' => $roleKey,
            'role' => self::UI_ROLES[$roleKey]['label'],
            'level' => $user->level ?: 'Mid',
            'tenure' => $joinedAt->diffForHumans(null, true),
            'avatar_color' => $user->avatar_color ?: $this->colorForRole($roleKey),
            'load' => $load,
            'load_hours' => (int) round($capacityHours * ($load / 100)),
            'capacity_hours' => $capacityHours,
            'projects_active' => $activeProjects,
            'tasks_open' => (int) ($workload?->active_tasks ?? $openAssignments->count()),
            'perf' => max(70, min(98, 92 - max(0, $load - 70))),
            'presence' => $user->archived_at ? 'offline' : ($load >= 85 ? 'away' : 'online'),
            'skills' => $skills,
            'phone' => $user->phone ?: '',
            'location' => 'GMT+7',
            'join_date' => $joinedAt->translatedFormat('d M Y'),
            'last_active' => $user->updated_at?->diffForHumans() ?: 'baru saja',
            'projects_lead' => $ledProjects,
            'tasks_done' => $assignments->whereIn('status', ['done', 'completed'])->count(),
            'bio' => self::UI_ROLES[$roleKey]['label'] . ' · ' . ($user->level ?: 'Mid') . '. Profil ini tersimpan di database Smart-PMIS.',
            'archived' => (bool) $user->archived_at,
            'raw_role' => $roleKey,
            'raw_level' => $user->level ?: 'Mid',
            'raw_skills' => implode(', ', $skills),
            'raw_phone' => $user->phone ?: '',
            'raw_avatar_color' => $user->avatar_color ?: $this->colorForRole($roleKey),
            'wa_link' => $this->whatsAppLink($user->phone),
            'email_link' => $this->gmailLink($user->email),
            'assignment_url' => route('team.assignments.store', $user),
            'allocations' => $assignments->map(fn (TeamAssignment $assignment) => [
                'code' => $assignment->project?->code ?: 'PRJ',
                'color' => $assignment->project?->color ?: '#7C3AED',
                'name' => $assignment->project?->name ?: 'Project',
                'role' => $assignment->title,
                'pct' => 0,
                'hours' => 0,
                'status' => $assignment->status,
                'due_date' => $assignment->due_date?->format('Y-m-d'),
                'notes' => $assignment->notes,
            ])->all(),
            'activities' => $this->activityRows($assignments),
            'permissions' => $this->permissionsForRole($roleKey),
        ];
    }

    private function ensureOperationalMember(User $member): void
    {
        abort_if($member->hasRole('ceo_pm'), 403);
    }

    private function skillsFromInput(string $skills): array
    {
        return collect(preg_split('/[,;\n]+/', $skills) ?: [])
            ->map(fn ($skill) => trim($skill))
            ->filter()
            ->values()
            ->all();
    }

    private function fallbackSkills(string $roleKey): array
    {
        return match ($roleKey) {
            'sa_qa' => ['WBS Drafting', 'Quality Control', 'MoM'],
            'ui_ux' => ['Figma', 'Design System', 'Prototype'],
            default => ['Laravel', 'JavaScript', 'API'],
        };
    }

    private function permissionsForRole(string $roleKey): array
    {
        return match ($roleKey) {
            'sa_qa' => [
                ['name' => 'AI Planning', 'desc' => 'Generate WBS & MoM Fixer', 'level' => 'Penuh'],
                ['name' => 'Quality Control', 'desc' => 'Eksekusi & retest test case', 'level' => 'Penuh'],
                ['name' => 'Project Workspace', 'desc' => 'Akses Kanban dan task editing', 'level' => 'Penuh'],
            ],
            'ui_ux' => [
                ['name' => 'Project Workspace', 'desc' => 'Akses Kanban dan task editing', 'level' => 'Penuh'],
                ['name' => 'AI Planning', 'desc' => 'Lihat hasil WBS', 'level' => 'Terbatas'],
                ['name' => 'Quality Control', 'desc' => 'Update status task', 'level' => 'Terbatas'],
            ],
            default => [
                ['name' => 'Project Workspace', 'desc' => 'Akses Kanban dan task editing', 'level' => 'Penuh'],
                ['name' => 'Quality Control', 'desc' => 'Update status task', 'level' => 'Terbatas'],
                ['name' => 'Audit Trail', 'desc' => 'Lihat riwayat sistem', 'level' => 'Terbatas'],
            ],
        };
    }

    private function activityRows($assignments): array
    {
        if ($assignments->isEmpty()) {
            return [[
                'icon' => 'user-plus',
                'bg' => '#EDE9FE',
                'color' => '#7C3AED',
                'text' => 'Profil anggota tersimpan di database.',
                'time' => 'baru saja',
            ]];
        }

        return $assignments->take(5)->map(fn (TeamAssignment $assignment) => [
            'icon' => 'clipboard-document-check',
            'bg' => '#EDE9FE',
            'color' => '#7C3AED',
            'text' => 'Penugasan <strong>' . e($assignment->title) . '</strong> pada ' . e($assignment->project?->name ?? 'project'),
            'time' => $assignment->created_at?->diffForHumans() ?: 'baru saja',
        ])->all();
    }

    private function colorForRole(string $roleKey): string
    {
        return match ($roleKey) {
            'sa_qa' => '#8B5CF6',
            'ui_ux' => '#EC4899',
            default => '#10B981',
        };
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
        return 'https://mail.google.com/mail/?view=cm&fs=1&to=' . rawurlencode((string) $email);
    }
}
