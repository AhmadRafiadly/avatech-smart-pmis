<?php

namespace App\Http\Controllers;

use App\Models\AccountDeletionRequest;
use App\Models\UserIntegrationState;
use App\Models\UserNotificationPreference;
use App\Models\UserRecoveryCode;
use App\Models\UserSecurityPreference;
use App\Models\WorkspaceSetting;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class SettingsController extends Controller
{
    private const NOTIFICATION_ROWS = [
        ['key' => 'ai-output-siap',    'label' => 'AI Output Siap',    'desc' => 'WBS, TC, MoM, Reminder draft',       'defaults' => ['app' => true, 'email' => true,  'wa' => false]],
        ['key' => 'proyek-critical',   'label' => 'Proyek Critical',   'desc' => 'Status berubah ke Critical Blocker', 'defaults' => ['app' => true, 'email' => true,  'wa' => true]],
        ['key' => 'approval-diminta',  'label' => 'Approval Diminta',  'desc' => 'Anda diminta approve sesuatu',       'defaults' => ['app' => true, 'email' => true,  'wa' => false]],
        ['key' => 'task-movement',     'label' => 'Task Movement',     'desc' => 'Task pindah di Kanban proyek Anda',  'defaults' => ['app' => true, 'email' => false, 'wa' => false]],
        ['key' => 'klien-reply',       'label' => 'Klien Reply',       'desc' => 'Balasan WhatsApp klien diterima',    'defaults' => ['app' => true, 'email' => true,  'wa' => true]],
        ['key' => 'workload-alert',    'label' => 'Workload Alert',    'desc' => 'Anda mendekati overload',            'defaults' => ['app' => true, 'email' => false, 'wa' => false]],
        ['key' => 'sistem-keamanan',   'label' => 'Sistem & Keamanan', 'desc' => 'Login baru, backup, perubahan akun', 'defaults' => ['app' => true, 'email' => true,  'wa' => false]],
    ];

    private const CHANNELS = ['app', 'email', 'wa'];

    private const INTEGRATIONS = [
        ['key' => 'whatsapp-business', 'name' => 'WhatsApp Business', 'icon' => 'chat-bubble-oval-left',  'color' => '#10B981', 'default' => true,  'desc' => 'CRM Lite - pre-filled outbound message via wa.me'],
        ['key' => 'google-calendar',   'name' => 'Google Calendar',   'icon' => 'calendar',               'color' => '#3B82F6', 'default' => true,  'desc' => 'Sinkronisasi deadline proyek & rapat tim'],
        ['key' => 'slack',             'name' => 'Slack',             'icon' => 'chat-bubble-left-right', 'color' => '#7C3AED', 'default' => false, 'desc' => 'Push notifikasi AI ke channel #avatech-pm'],
        ['key' => 'github',            'name' => 'GitHub',            'icon' => 'code-bracket-square',    'color' => '#1E1B4B', 'default' => true,  'desc' => 'Link PR ke task Kanban + commit di Audit Trail'],
        ['key' => 'figma',             'name' => 'Figma',             'icon' => 'paint-brush',            'color' => '#EC4899', 'default' => false, 'desc' => 'Embed mockup ke task UI/UX'],
        ['key' => 'webhook',           'name' => 'Webhook',           'icon' => 'bolt',                   'color' => '#F59E0B', 'default' => false, 'desc' => 'Outbound event ke endpoint custom Anda'],
    ];

    private const SECURITY_SWITCHES = [
        ['key' => 'two-factor-authentication', 'label' => 'Two-Factor Authentication', 'desc' => 'Persiapan 2FA; enforcement penuh menunggu modul autentikasi berikutnya', 'default' => false],
        ['key' => 'login-alert',               'label' => 'Login Alert',               'desc' => 'Notifikasi setiap kali ada login baru',                                'default' => true],
        ['key' => 'ip-allowlist',              'label' => 'IP Allowlist',              'desc' => 'Preferensi allowlist IP; enforcement penuh menunggu backend jaringan',  'default' => false],
    ];

    public function index(Request $request)
    {
        $user = $request->user();
        $workspace = WorkspaceSetting::firstOrCreate(
            ['id' => 1],
            [
                'workspace_name'     => 'PT Ava Teknologi Nusantara',
                'subdomain'          => 'avatech',
                'interface_language' => 'id',
                'timezone'           => 'Asia/Jakarta',
            ],
        );

        return view('settings.index', [
            'title'             => 'Settings',
            'settingsWorkspace' => $workspace,
            'settingsNotifRows' => $this->notificationRows($user->id),
            'settingsIntegrations' => $this->integrationRows($user->id),
            'settingsSecurity'  => $this->securityRows($user->id),
            'settingsSessions'  => $this->sessionRows($user->id, $request->session()->getId()),
            'recoveryCodeCount' => UserRecoveryCode::where('user_id', $user->id)->whereNull('used_at')->count(),
            'deletionPending'   => AccountDeletionRequest::where('user_id', $user->id)->where('status', 'pending')->exists(),
        ]);
    }

    public function updatePreferences(Request $request)
    {
        $validated = $request->validate([
            'workspace_name'     => ['required', 'string', 'max:120'],
            'subdomain'          => ['required', 'string', 'max:40', 'regex:/^[a-z0-9-]+$/'],
            'interface_language' => ['required', Rule::in(['id', 'en'])],
            'timezone'           => ['required', Rule::in(['Asia/Jakarta', 'Asia/Singapore'])],
            'notifications'      => ['array'],
            'security'           => ['array'],
        ]);

        $user = $request->user();

        $workspaceBefore = WorkspaceSetting::find(1)?->only(['workspace_name', 'subdomain', 'interface_language', 'timezone']);

        DB::transaction(function () use ($validated, $request, $user) {
            WorkspaceSetting::updateOrCreate(
                ['id' => 1],
                [
                    'workspace_name'     => $validated['workspace_name'],
                    'subdomain'          => $validated['subdomain'],
                    'interface_language' => $validated['interface_language'],
                    'timezone'           => $validated['timezone'],
                ],
            );

            foreach (self::NOTIFICATION_ROWS as $row) {
                foreach (self::CHANNELS as $channel) {
                    UserNotificationPreference::updateOrCreate(
                        [
                            'user_id'   => $user->id,
                            'category'  => $row['key'],
                            'channel'   => $channel,
                        ],
                        ['enabled' => $request->boolean("notifications.{$row['key']}.{$channel}")],
                    );
                }
            }

            foreach (self::SECURITY_SWITCHES as $switch) {
                UserSecurityPreference::updateOrCreate(
                    [
                        'user_id' => $user->id,
                        'key'     => $switch['key'],
                    ],
                    ['enabled' => $request->boolean("security.{$switch['key']}")],
                );
            }
        });

        AuditLogger::log(
            'preferences_updated',
            'Settings',
            'Memperbarui preferensi workspace, notifikasi, dan keamanan',
            WorkspaceSetting::find(1),
            $workspaceBefore,
            [
                'workspace_name'     => $validated['workspace_name'],
                'subdomain'          => $validated['subdomain'],
                'interface_language' => $validated['interface_language'],
                'timezone'           => $validated['timezone'],
            ],
        );

        return redirect()
            ->route('settings.index', ['tab' => $request->input('active_tab', 'general')])
            ->with('status', 'Pengaturan tersimpan di database.');
    }

    public function toggleIntegration(Request $request, string $provider)
    {
        $definition = collect(self::INTEGRATIONS)->firstWhere('key', $provider);
        abort_unless($definition, 404);

        $state = UserIntegrationState::firstOrNew([
            'user_id'  => $request->user()->id,
            'provider' => $provider,
        ]);

        $current = $state->exists ? (bool) $state->connected : (bool) $definition['default'];
        $next = ! $current;
        $state->fill([
            'connected'       => $next,
            'connected_at'    => $next ? now() : $state->connected_at,
            'disconnected_at' => $next ? null : now(),
        ])->save();

        AuditLogger::log(
            $next ? 'integration_connected' : 'integration_disconnected',
            'Settings',
            ($next ? 'Menghubungkan' : 'Memutus') . ' integrasi <strong>' . e($definition['name']) . '</strong>',
            $state,
            ['connected' => $current],
            ['connected' => $next],
        );

        return redirect()
            ->route('settings.index', ['tab' => 'integrations'])
            ->with('status', $definition['name'] . ($next ? ' terhubung.' : ' diputus.'));
    }

    public function updatePassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'current_password' => ['required', 'string'],
            'new_password'     => ['required', 'string', Password::min(8)],
            'new_password_confirmation' => ['required', 'string', 'same:new_password'],
        ], [
            'current_password.required' => 'Password saat ini wajib diisi.',
            'new_password.required' => 'Password baru wajib diisi.',
            'new_password.min' => 'Password baru minimal 8 karakter.',
            'new_password_confirmation.required' => 'Konfirmasi password wajib diisi.',
            'new_password_confirmation.same' => 'Konfirmasi password tidak sama.',
        ]);

        $validator->after(function ($validator) use ($request) {
            $currentPassword = (string) $request->input('current_password', '');
            if ($currentPassword !== '' && ! Hash::check($currentPassword, $request->user()->password)) {
                $validator->errors()->add('current_password', 'Password saat ini tidak sesuai.');
            }
        });

        if ($validator->fails()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Validasi password gagal.',
                    'errors' => $validator->errors()->toArray(),
                ], 422);
            }

            return redirect()
                ->route('settings.index', ['tab' => 'security'])
                ->withErrors($validator)
                ->withInput();
        }

        $validated = $validator->validated();

        $user = $request->user();

        $user->forceFill([
            'password' => Hash::make($validated['new_password']),
        ])->save();

        AuditLogger::log(
            'password_updated',
            'Settings',
            'Mengubah password akun',
            $user,
        );

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Password berhasil diperbarui.',
            ]);
        }

        return redirect()
            ->route('settings.index', ['tab' => 'security'])
            ->with('status', 'Password berhasil diperbarui.');
    }

    public function regenerateRecoveryCodes(Request $request)
    {
        $user = $request->user();
        $plainCodes = collect(range(1, 10))->map(fn () => $this->makeRecoveryCode())->all();

        DB::transaction(function () use ($user, $plainCodes) {
            UserRecoveryCode::where('user_id', $user->id)->delete();
            foreach ($plainCodes as $code) {
                UserRecoveryCode::create([
                    'user_id'    => $user->id,
                    'code_hash'  => Hash::make($code),
                ]);
            }
        });

        return redirect()
            ->route('settings.index', ['tab' => 'security'])
            ->with('status', 'Recovery codes dibuat dan disimpan sebagai hash.')
            ->with('recovery_codes', $plainCodes);
    }

    public function destroySession(Request $request, string $session)
    {
        if (config('session.driver') !== 'database') {
            return redirect()
                ->route('settings.index', ['tab' => 'security'])
                ->withErrors(['session' => 'Session termination hanya tersedia untuk database sessions.']);
        }

        if ($session === $request->session()->getId()) {
            return redirect()
                ->route('settings.index', ['tab' => 'security'])
                ->withErrors(['session' => 'Sesi saat ini tidak bisa dihentikan dari daftar ini.']);
        }

        $deleted = DB::table(config('session.table', 'sessions'))
            ->where('id', $session)
            ->where('user_id', $request->user()->id)
            ->delete();

        return redirect()
            ->route('settings.index', ['tab' => 'security'])
            ->with('status', $deleted ? 'Sesi berhasil dihentikan.' : 'Sesi tidak ditemukan atau bukan milik akun ini.');
    }

    public function requestAccountDeletion(Request $request)
    {
        AccountDeletionRequest::updateOrCreate(
            [
                'user_id' => $request->user()->id,
                'status'  => 'pending',
            ],
            ['confirmed_at' => now()],
        );

        return redirect()
            ->route('settings.index', ['tab' => 'security'])
            ->with('status', 'Permintaan hapus akun tercatat. Akun belum dihapus.');
    }

    private function notificationRows(int $userId): array
    {
        $prefs = UserNotificationPreference::where('user_id', $userId)
            ->get()
            ->groupBy('category')
            ->map(fn ($rows) => $rows->pluck('enabled', 'channel'));

        return collect(self::NOTIFICATION_ROWS)->map(function (array $row) use ($prefs) {
            $saved = $prefs->get($row['key']);

            return [
                'key'   => $row['key'],
                'label' => $row['label'],
                'desc'  => $row['desc'],
                'app'   => $saved ? (bool) $saved->get('app', false) : $row['defaults']['app'],
                'email' => $saved ? (bool) $saved->get('email', false) : $row['defaults']['email'],
                'wa'    => $saved ? (bool) $saved->get('wa', false) : $row['defaults']['wa'],
            ];
        })->all();
    }

    private function integrationRows(int $userId): array
    {
        $states = UserIntegrationState::where('user_id', $userId)->pluck('connected', 'provider');

        return collect(self::INTEGRATIONS)->map(function (array $integration) use ($states) {
            $integration['connected'] = $states->has($integration['key'])
                ? (bool) $states->get($integration['key'])
                : $integration['default'];

            return $integration;
        })->all();
    }

    private function securityRows(int $userId): array
    {
        $states = UserSecurityPreference::where('user_id', $userId)->pluck('enabled', 'key');

        $rows = collect(self::SECURITY_SWITCHES)->map(function (array $switch) use ($states) {
            return [
                'kind'  => 'switch',
                'key'   => $switch['key'],
                'label' => $switch['label'],
                'desc'  => $switch['desc'],
                'on'    => $states->has($switch['key']) ? (bool) $states->get($switch['key']) : $switch['default'],
            ];
        })->all();

        return [
            ...$rows,
            ['kind' => 'button', 'label' => 'Ubah Password',  'desc' => 'Gunakan password saat ini untuk mengubah password akun', 'btn' => 'Ubah'],
            ['kind' => 'button', 'label' => 'Recovery Codes', 'desc' => 'Backup-code preparation sampai full 2FA tersedia',         'btn' => 'Generate'],
        ];
    }

    private function sessionRows(int $userId, string $currentSessionId): array
    {
        if (config('session.driver') !== 'database') {
            return [[
                'id'      => $currentSessionId,
                'device'  => 'Sesi saat ini',
                'icon'    => 'computer-desktop',
                'where'   => 'Database session tidak aktif',
                'time'    => 'Aktif sekarang',
                'current' => true,
            ]];
        }

        return DB::table(config('session.table', 'sessions'))
            ->where('user_id', $userId)
            ->orderByDesc('last_activity')
            ->get()
            ->map(fn ($session) => [
                'id'      => $session->id,
                'device'  => $this->deviceLabel($session->user_agent),
                'icon'    => $this->deviceIcon($session->user_agent),
                'where'   => $session->ip_address ?: 'IP tidak tersedia',
                'time'    => $this->relativeSessionTime((int) $session->last_activity),
                'current' => hash_equals($currentSessionId, $session->id),
            ])
            ->all();
    }

    private function deviceLabel(?string $userAgent): string
    {
        $ua = $userAgent ?: '';
        $browser = str_contains($ua, 'Firefox') ? 'Firefox' : (str_contains($ua, 'Safari') && ! str_contains($ua, 'Chrome') ? 'Safari' : 'Chrome');
        $os = str_contains($ua, 'Windows') ? 'Windows' : (str_contains($ua, 'Mac OS') ? 'macOS' : (str_contains($ua, 'Linux') ? 'Linux' : 'Perangkat'));

        return $browser . ' - ' . $os;
    }

    private function deviceIcon(?string $userAgent): string
    {
        return str_contains($userAgent ?: '', 'Mobile') ? 'device-phone-mobile' : 'computer-desktop';
    }

    private function relativeSessionTime(int $lastActivity): string
    {
        $diff = max(0, now()->timestamp - $lastActivity);
        if ($diff < 60) {
            return 'Aktif sekarang';
        }
        if ($diff < 3600) {
            return floor($diff / 60) . ' menit lalu';
        }
        if ($diff < 86400) {
            return floor($diff / 3600) . ' jam lalu';
        }

        return floor($diff / 86400) . ' hari lalu';
    }

    private function makeRecoveryCode(): string
    {
        $chars = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';
        $out = '';
        for ($i = 0; $i < 8; $i++) {
            $out .= $chars[random_int(0, strlen($chars) - 1)];
        }

        return substr($out, 0, 4) . '-' . substr($out, 4);
    }
}
