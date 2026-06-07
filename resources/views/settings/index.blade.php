@php
    $tabs = [
        ['id' => 'general',      'icon' => 'adjustments-horizontal', 'label' => 'Umum'],
        ['id' => 'security',     'icon' => 'shield-check',            'label' => 'Keamanan'],
    ];

    $aiFlags = [
        ['key' => 'wbs',      'label' => 'AI WBS Generator',       'note' => 'Generate struktur modul & task otomatis dari MoM',     'on' => true],
        ['key' => 'mom',      'label' => 'AI MoM Fixer',           'note' => 'Rapikan notulensi mentah jadi struktur action items',  'on' => true],
        ['key' => 'tc',       'label' => 'AI Test Case Generator', 'note' => 'Buat test case black-box dari WBS yang disetujui',     'on' => true],
        ['key' => 'reminder', 'label' => 'AI Smart Reminders',     'note' => 'Reminder klien & deadline berbasis konteks proyek',    'on' => true],
        ['key' => 'risk',     'label' => 'AI Risk Detection',      'note' => 'Deteksi proyek critical & burnout otomatis',           'on' => true],
        ['key' => 'wa',       'label' => 'AI WhatsApp Drafting',   'note' => 'Saran pesan WhatsApp dengan tone konteks-aware',       'on' => true],
        ['key' => 'sandbox',  'label' => 'Mode Sandbox AI',        'note' => 'Test output AI tanpa memengaruhi data produksi',       'on' => false],
    ];

    $notifRows = [
        ['label' => 'AI Output Siap',    'desc' => 'WBS, TC, MoM, Reminder draft',       'app' => true],
        ['label' => 'Proyek Critical',   'desc' => 'Status berubah ke Critical Blocker', 'app' => true],
        ['label' => 'Approval Diminta',  'desc' => 'Anda diminta approve sesuatu',       'app' => true],
        ['label' => 'Task Movement',     'desc' => 'Task pindah di Kanban proyek Anda',  'app' => true],
        ['label' => 'Klien Reply',       'desc' => 'Balasan WhatsApp klien diterima',    'app' => true],
        ['label' => 'Workload Alert',    'desc' => 'Anda mendekati overload',            'app' => true],
        ['label' => 'Sistem & Keamanan', 'desc' => 'Login baru, backup, perubahan akun', 'app' => true],
    ];

    $integrations = [
        ['name' => 'WhatsApp Business', 'icon' => 'chat-bubble-oval-left',  'color' => '#10B981', 'connected' => false, 'desc' => 'CRM Lite - pre-filled outbound message via wa.me'],
        ['name' => 'Google Calendar',   'icon' => 'calendar',                'color' => '#3B82F6', 'connected' => false, 'desc' => 'Sinkronisasi deadline proyek & rapat tim'],
        ['name' => 'Slack',             'icon' => 'chat-bubble-left-right', 'color' => '#7C3AED', 'connected' => false, 'desc' => 'Push notifikasi AI ke channel #avatech-pm'],
        ['name' => 'GitHub',            'icon' => 'code-bracket-square',    'color' => '#1E1B4B', 'connected' => false, 'desc' => 'Link PR ke task Kanban + commit di Audit Trail'],
        ['name' => 'Figma',             'icon' => 'paint-brush',             'color' => '#EC4899', 'connected' => false, 'desc' => 'Embed mockup ke task UI/UX'],
        ['name' => 'Webhook',           'icon' => 'bolt',                    'color' => '#F59E0B', 'connected' => false, 'desc' => 'Outbound event ke endpoint custom Anda'],
    ];

    $security = [
        ['kind' => 'switch', 'label' => 'Two-Factor Authentication', 'desc' => 'TOTP via Google Authenticator atau 1Password', 'on' => true],
        ['kind' => 'switch', 'label' => 'Login Alert',               'desc' => 'Notifikasi setiap kali ada login baru',         'on' => true],
        ['kind' => 'switch', 'label' => 'IP Allowlist',              'desc' => 'Batasi akses dari IP kantor saja',              'on' => false],
        ['kind' => 'button', 'label' => 'Ubah Password',             'desc' => 'Terakhir diubah 27 Mar 2026',                   'btn' => 'Ubah'],
        ['kind' => 'button', 'label' => 'Recovery Codes',            'desc' => '10 kode cadangan untuk 2FA',                    'btn' => 'Lihat'],
    ];

    $sessions = [
        ['device' => 'Chrome 124 · macOS',   'icon' => 'computer-desktop',    'where' => 'Jakarta, ID', 'time' => 'Aktif sekarang', 'current' => true],
        ['device' => 'Safari · iPhone 15',    'icon' => 'device-phone-mobile', 'where' => 'Jakarta, ID', 'time' => '2 jam lalu',     'current' => false],
        ['device' => 'Firefox · Windows 11',  'icon' => 'computer-desktop',    'where' => 'Bandung, ID', 'time' => '3 hari lalu',    'current' => false],
    ];
    $workspace = $settingsWorkspace ?? null;
    $notifRows = $settingsNotifRows ?? $notifRows;
    $integrations = $settingsIntegrations ?? $integrations;
    $security = $settingsSecurity ?? $security;
    $sessions = $settingsSessions ?? $sessions;
    $workspaceName = old('workspace_name', data_get($workspace, 'workspace_name', 'PT Ava Teknologi Nusantara'));
    $workspaceSubdomain = old('subdomain', data_get($workspace, 'subdomain', 'avatech'));
    $workspaceLanguage = old('interface_language', data_get($workspace, 'interface_language', 'id'));
    $workspaceTimezone = old('timezone', data_get($workspace, 'timezone', 'Asia/Jakarta'));
    $recoveryCodes = session('recovery_codes', []);
    $flashStatus = session('status');
    $flashErrors = collect($errors->getBag('default')->getMessages())
        ->except(['current_password', 'new_password', 'new_password_confirmation'])
        ->flatten()
        ->all();
@endphp

<x-layouts.authenticated :title="$title">

    <style>
        .js-switch { width: 42px; height: 24px; background: #E2E8F0; border-radius: 9999px; position: relative; transition: .2s; cursor: pointer; flex-shrink: 0; }
        .js-switch::after { content: ''; position: absolute; top: 2px; left: 2px; width: 20px; height: 20px; background: #fff; border-radius: 9999px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); transition: .2s; }
        .js-switch.is-on { background: linear-gradient(135deg, #7C3AED, #A855F7); }
        .js-switch.is-on::after { left: 20px; }
    </style>

    <form id="settings-preferences-form" method="POST" action="{{ route('settings.preferences.update') }}" class="hidden">
        @csrf
        @method('PUT')
        <input type="hidden" name="active_tab" value="{{ request('tab', 'general') }}" data-settings-active-tab>
    </form>

    <section class="mb-8">
        <h1 class="text-[44px] leading-[1.05] font-bold tracking-tight text-[#1E1B4B]">
            Set<span class="bg-clip-text text-transparent bg-gradient-to-r from-[#7C3AED] to-[#A855F7]">tings</span>
        </h1>
        <p class="mt-3 text-[15px] text-slate-500 max-w-2xl">
            Pengaturan workspace dan keamanan akun yang aktif digunakan di Smart-PMIS.
        </p>
    </section>

    <div class="grid grid-cols-1 lg:grid-cols-[260px_1fr] gap-8">

        <aside>
            <div class="bg-white rounded-2xl border border-violet-100 shadow-[0_2px_8px_rgba(124,58,237,0.08)] p-3 sticky top-[88px]">
                @foreach ($tabs as $idx => $t)
                    @php $isFirst = $idx === 0; @endphp
                    <button
                        type="button"
                        data-tab="{{ $t['id'] }}"
                        @class([
                            'js-settings-tab w-full flex items-center gap-3 px-4 py-3 rounded-xl text-[14px] font-semibold transition mb-1 cursor-pointer',
                            'bg-gradient-to-br from-violet-100 to-fuchsia-100 text-violet-700' => $isFirst,
                            'text-slate-600 hover:bg-violet-50/70 hover:text-violet-700' => ! $isFirst,
                        ])
                    >
                        <x-dynamic-component :component="'heroicon-o-' . $t['icon']" class="w-[18px] h-[18px] flex-shrink-0" />
                        <span>{{ $t['label'] }}</span>
                    </button>
                @endforeach
            </div>
        </aside>

        <section class="space-y-6">

            <div data-panel="general">
                <div class="bg-white rounded-2xl border border-violet-100 shadow-[0_2px_8px_rgba(124,58,237,0.08)] p-7 mb-5">
                    <h3 class="text-[16px] font-bold text-[#1E1B4B] mb-1">Workspace</h3>
                    <p class="text-[13px] text-slate-500 mb-2">Identitas perusahaan yang muncul di laporan dan ekspor.</p>
                    <p class="text-[12px] text-slate-400 mb-5">Settings hanya menampilkan pengaturan yang aktif digunakan. Timestamp runtime mengikuti APP_TIMEZONE=Asia/Jakarta pada konfigurasi aplikasi.</p>
                    <input form="settings-preferences-form" name="subdomain" type="hidden" value="{{ $workspaceSubdomain }}">
                    <input form="settings-preferences-form" name="interface_language" type="hidden" value="id">
                    <input form="settings-preferences-form" name="timezone" type="hidden" value="Asia/Jakarta">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-[11px] font-bold tracking-wider uppercase text-slate-500 mb-1.5">Nama Workspace</label>
                            <input form="settings-preferences-form" name="workspace_name" type="text" value="{{ $workspaceName }}" class="w-full h-11 rounded-xl border border-violet-100 px-4 text-[13.5px] text-[#1E1B4B] focus:outline-none focus:ring-2 focus:ring-violet-300 focus:border-violet-300 transition" />
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold tracking-wider uppercase text-slate-500 mb-1.5">Subdomain</label>
                            <div class="flex items-center h-11 rounded-xl border border-violet-100 overflow-hidden focus-within:ring-2 focus-within:ring-violet-300 focus-within:border-violet-300 transition">
                                <input type="text" value="{{ $workspaceSubdomain }}" readonly aria-readonly="true" class="flex-1 h-full px-4 text-[13.5px] text-[#1E1B4B] bg-violet-50/30 focus:outline-none cursor-not-allowed" />
                                <span class="px-3 text-[13px] text-slate-400 bg-violet-50/40 h-full flex items-center">.smartpmis.id</span>
                            </div>
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold tracking-wider uppercase text-slate-500 mb-1.5">Bahasa Antarmuka</label>
                            <div class="h-11 rounded-xl border border-violet-100 px-4 text-[13.5px] text-[#1E1B4B] bg-violet-50/40 flex items-center">Bahasa Indonesia</div>
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold tracking-wider uppercase text-slate-500 mb-1.5">Zona Waktu Runtime</label>
                            <select disabled aria-disabled="true" class="w-full h-11 rounded-xl border border-violet-100 px-4 text-[13.5px] text-[#1E1B4B] bg-violet-50/40 cursor-not-allowed">
                                <option value="Asia/Jakarta" selected>Asia/Jakarta - GMT+7</option>
                                <option value="Asia/Singapore" @selected($workspaceTimezone === 'Asia/Singapore')>Asia/Singapore · GMT+8</option>
                            </select>
                            <p class="mt-1.5 text-[11px] text-slate-400 leading-snug">Nilai ini dikunci oleh APP_TIMEZONE aplikasi, bukan preferensi pengguna.</p>
                        </div>
                    </div>
                </div>

                @if (\Illuminate\Support\Facades\Route::has('system-health.index'))
                    <div class="bg-violet-50/70 border border-violet-200 rounded-2xl p-6 mb-5 flex items-start justify-between gap-4 flex-wrap">
                        <div class="flex items-start gap-4 min-w-0">
                            <div class="w-11 h-11 rounded-xl bg-white text-violet-700 flex items-center justify-center border border-violet-100 flex-shrink-0">
                                <x-heroicon-o-server-stack class="w-5 h-5" />
                            </div>
                            <div class="min-w-0">
                                <h3 class="text-[15px] font-bold text-[#1E1B4B]">System Health</h3>
                                <p class="text-[12.5px] text-slate-500 mt-1 max-w-xl">
                                    Cek kesiapan database, storage, PDF export, AI provider, dan environment sistem.
                                </p>
                            </div>
                        </div>
                        <a href="{{ route('system-health.index') }}" class="inline-flex items-center gap-1.5 h-9 px-4 rounded-xl bg-white border border-violet-200 text-violet-700 text-[12.5px] font-semibold hover:border-violet-400 transition">
                            <x-heroicon-o-arrow-top-right-on-square class="w-4 h-4" />
                            Buka System Health
                        </a>
                    </div>
                @endif

                <div class="hidden bg-white rounded-2xl border border-violet-100 shadow-[0_2px_8px_rgba(124,58,237,0.08)] p-7">
                    <h3 class="text-[16px] font-bold text-[#1E1B4B] mb-1">Tampilan</h3>
                    <p class="text-[13px] text-slate-500 mb-5">Personalisasi tampilan dan kepadatan layout.</p>
                    <div class="space-y-1">
                        @foreach ([
                            ['label' => 'Tema',      'value' => 'Violet Edition (default)', 'note' => 'Avatech tidak menyediakan tema lain untuk versi 2.1'],
                            ['label' => 'Kepadatan', 'value' => 'Comfortable',               'note' => 'Spacing lega — direkomendasikan'],
                            ['label' => 'Mode',      'value' => 'Light',                     'note' => 'Dark mode rilis Q3 2026'],
                        ] as $r)
                            <div class="flex items-center justify-between py-3 border-b border-violet-50 last:border-0 gap-3">
                                <div class="min-w-0">
                                    <div class="text-[13.5px] font-semibold text-[#1E1B4B]">{{ $r['label'] }}</div>
                                    <div class="text-[12px] text-slate-500">{{ $r['note'] }}</div>
                                </div>
                                <span class="text-[12.5px] font-semibold text-violet-700 inline-flex items-center gap-1.5 flex-shrink-0">
                                    <span>{{ $r['value'] }}</span>
                                    <x-heroicon-o-check class="w-4 h-4" />
                                </span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <div data-panel="ai" class="hidden">
                @php
                    /* Read provider status straight from config — no UI mutation
                     * because keys live in .env. The dropdowns we used to show
                     * for Anthropic/Claude were never wired to a saver. */
                    $aiProviders = [
                        ['key' => 'gemini',     'label' => 'Gemini',      'model' => (string) config('ai.gemini.model'),     'configured' => is_string(config('ai.gemini.api_key'))     && trim((string) config('ai.gemini.api_key'))     !== ''],
                        ['key' => 'groq',       'label' => 'Groq',        'model' => (string) config('ai.groq.model'),       'configured' => is_string(config('ai.groq.api_key'))       && trim((string) config('ai.groq.api_key'))       !== ''],
                        ['key' => 'openrouter', 'label' => 'OpenRouter',  'model' => (string) config('ai.openrouter.model'), 'configured' => is_string(config('ai.openrouter.api_key')) && trim((string) config('ai.openrouter.api_key')) !== ''],
                    ];
                    $aiOrder = (array) (config('ai.provider_order') ?: ['gemini', 'groq', 'openrouter']);
                    $aiReady = \App\Services\AiPlanner::isConfigured();
                @endphp
                <div class="bg-white rounded-2xl border border-violet-100 shadow-[0_2px_8px_rgba(124,58,237,0.08)] p-7 mb-5">
                    <div class="flex items-start justify-between gap-4 mb-1 flex-wrap">
                        <div>
                            <h3 class="text-[16px] font-bold text-[#1E1B4B]">Mesin AI</h3>
                            <p class="text-[13px] text-slate-500 mt-1">Provider AI dikonfigurasi lewat environment variables. Tidak ada pengaturan model di sini.</p>
                        </div>
                        <div class="flex items-center gap-2 flex-wrap">
                            @if (\Illuminate\Support\Facades\Route::has('ai-monitor.index'))
                                <a href="{{ route('ai-monitor.index') }}" class="inline-flex items-center gap-1.5 h-8 px-3 rounded-lg border border-violet-100 bg-white text-violet-700 text-[12px] font-semibold hover:border-violet-300 transition">
                                    <x-heroicon-o-cpu-chip class="w-4 h-4" />
                                    Buka AI Monitor
                                </a>
                            @endif
                            @if ($aiReady)
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-emerald-50 text-emerald-700 text-[11px] font-bold tracking-wider uppercase">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                    AI siap digunakan
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-slate-100 text-slate-500 text-[11px] font-bold tracking-wider uppercase">
                                    <span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span>
                                    AI belum dikonfigurasi
                                </span>
                            @endif
                        </div>
                    </div>

                    <div class="mt-5">
                        <div class="text-[10.5px] font-bold tracking-wider uppercase text-slate-500 mb-2">Urutan Fallback</div>
                        <div class="flex items-center gap-2 flex-wrap text-[12.5px] text-slate-600">
                            @foreach ($aiOrder as $idx => $providerKey)
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-violet-50 text-violet-700 text-[11.5px] font-semibold tracking-wide">{{ ucfirst($providerKey) }}</span>
                                @if ($idx < count($aiOrder) - 1)
                                    <x-heroicon-o-arrow-right class="w-3.5 h-3.5 text-slate-300" />
                                @endif
                            @endforeach
                        </div>
                    </div>

                    <div class="mt-6 space-y-1">
                        @foreach ($aiProviders as $p)
                            <div class="flex items-center justify-between py-3 border-b border-violet-50 last:border-0 gap-3">
                                <div class="min-w-0">
                                    <div class="text-[13.5px] font-semibold text-[#1E1B4B]">{{ $p['label'] }}</div>
                                    <div class="text-[12px] text-slate-500 mt-0.5">Model: <span class="font-mono text-[11.5px]">{{ $p['model'] ?: '—' }}</span></div>
                                </div>
                                @if ($p['configured'])
                                    <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-md bg-emerald-50 text-emerald-700 text-[10.5px] font-bold tracking-wider uppercase">Aktif</span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-md bg-slate-100 text-slate-500 text-[10.5px] font-bold tracking-wider uppercase">Belum dikonfigurasi</span>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="bg-violet-50/60 border border-violet-200 rounded-2xl p-6 flex items-start gap-4">
                    <x-heroicon-o-shield-check class="w-5 h-5 text-violet-700 mt-0.5 flex-shrink-0" />
                    <div>
                        <h4 class="text-[14px] font-bold text-violet-900">HITL Tetap Wajib</h4>
                        <p class="text-[12.5px] text-violet-700 mt-1">Output AI selalu masuk sebagai <strong>Draft</strong> — perlu approval eksplisit dari SA/QA sebelum tersimpan permanen. Aturan ini tidak bisa dimatikan.</p>
                    </div>
                </div>
            </div>

            <div data-panel="notif" class="hidden">
                <div class="bg-white rounded-2xl border border-violet-100 shadow-[0_2px_8px_rgba(124,58,237,0.08)] p-7">
                    <h3 class="text-[16px] font-bold text-[#1E1B4B] mb-1">Status Notifikasi</h3>
                    <p class="text-[13px] text-slate-500 mb-5">Saat ini notifikasi aktif di dalam aplikasi. Email/WhatsApp tersedia setelah integrasi resmi diaktifkan.</p>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left">
                            <thead>
                                <tr class="text-[11px] font-bold tracking-[0.1em] uppercase text-slate-400 border-b border-violet-50">
                                    <th class="py-3 pr-4">Kategori</th>
                                    <th class="py-3 px-3 text-center w-[160px]">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($notifRows as $row)
                                    <tr class="border-b border-violet-50 last:border-0">
                                        <td class="py-3 pr-4">
                                            <div class="text-[13.5px] font-semibold text-[#1E1B4B]">{{ $row['label'] }}</div>
                                            <div class="text-[12px] text-slate-500">{{ $row['desc'] }}</div>
                                        </td>
                                        <td class="py-3 px-3 text-center">
                                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-emerald-50 text-emerald-700 text-[10.5px] font-bold tracking-wider uppercase">
                                                In-app aktif
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-5 rounded-xl border border-violet-100 bg-violet-50/40 px-4 py-3 text-[12.5px] text-slate-600 leading-snug">
                        Saat ini notifikasi aktif di dalam aplikasi. Email/WhatsApp tersedia setelah integrasi resmi diaktifkan.
                    </div>
                </div>
            </div>

            <div data-panel="integrations" class="hidden">
                <div class="mb-5 rounded-xl border border-violet-100 bg-violet-50/40 px-4 py-3 text-[12.5px] text-slate-600 leading-snug">
                    Integrasi eksternal (WhatsApp Business, Google Calendar, Slack, GitHub, Figma, Webhook) belum diaktifkan di server ini. Status di bawah hanya informasional. Draft pesan klien tetap tersedia di Client Directory tanpa koneksi resmi.
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    @foreach ($integrations as $ig)
                        <div class="bg-white rounded-2xl border border-violet-100 shadow-[0_2px_8px_rgba(124,58,237,0.08)] p-5">
                            <div class="flex items-start gap-3 mb-3">
                                <div class="w-11 h-11 rounded-xl flex items-center justify-center text-white flex-shrink-0" style="background: {{ $ig['color'] }};">
                                    <x-dynamic-component :component="'heroicon-o-' . $ig['icon']" class="w-5 h-5" />
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <h4 class="text-[14.5px] font-bold text-[#1E1B4B]">{{ $ig['name'] }}</h4>
                                        <span class="text-[10px] font-bold tracking-wider uppercase px-2 py-0.5 rounded-md bg-slate-100 text-slate-500">Belum dikonfigurasi</span>
                                    </div>
                                    <p class="text-[12.5px] text-slate-500 mt-1 leading-relaxed">{{ $ig['desc'] }}</p>
                                </div>
                            </div>
                            <button
                                type="button"
                                disabled
                                aria-disabled="true"
                                title="Integrasi resmi {{ $ig['name'] }} belum aktif."
                                class="w-full h-9 rounded-lg text-[12.5px] font-semibold bg-slate-100 text-slate-400 cursor-not-allowed"
                            >
                                Segera tersedia
                            </button>
                        </div>
                    @endforeach
                </div>
            </div>

            <div data-panel="security" class="hidden">
                <div class="bg-white rounded-2xl border border-violet-100 shadow-[0_2px_8px_rgba(124,58,237,0.08)] p-7 mb-5">
                    <h3 class="text-[16px] font-bold text-[#1E1B4B] mb-1">Keamanan Akun</h3>
                    <p class="text-[13px] text-slate-500 mb-5">Lindungi akun dan data sensitif Avatech.</p>
                    <div class="space-y-1">
                        @foreach ($security as $s)
                            @php
                                /* Working control in this release:
                                 *  - "Ubah Password" button (real password update form)
                                 * 2FA / login alerts / IP allowlist / recovery codes are placeholders
                                 * until those backends are actually wired up. */
                                $label = (string) $s['label'];
                                $lower = mb_strtolower($label);
                                $isPasswordBtn = str_contains($lower, 'password');
                                $isRecovery    = str_starts_with($lower, 'recovery');
                                $isRealSwitch  = false;
                            @endphp
                            <div class="flex items-center justify-between py-3 border-b border-violet-50 last:border-0 gap-3">
                                <div class="min-w-0">
                                    <div class="text-[13.5px] font-semibold text-[#1E1B4B]">{{ $s['label'] }}</div>
                                    <div class="text-[12px] text-slate-500 mt-0.5">{{ $s['desc'] }}</div>
                                </div>
                                @if ($s['kind'] === 'switch')
                                    @if ($isRealSwitch)
                                        @php $secSlug = $s['key'] ?? Illuminate\Support\Str::slug($s['label']); @endphp
                                        <input form="settings-preferences-form" type="hidden" name="security[{{ $secSlug }}]" value="{{ $s['on'] ? '1' : '0' }}" data-switch-input>
                                        <div class="js-switch {{ $s['on'] ? 'is-on' : '' }}" data-backed-switch role="switch" aria-checked="{{ $s['on'] ? 'true' : 'false' }}"></div>
                                    @else
                                        <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-md bg-slate-100 text-slate-500 text-[10.5px] font-bold tracking-wider uppercase flex-shrink-0">Segera tersedia</span>
                                    @endif
                                @elseif ($isPasswordBtn)
                                    <button type="button" data-show-password-modal class="h-9 px-3.5 rounded-lg border border-violet-200 text-[12.5px] font-semibold text-slate-600 hover:border-violet-400 hover:text-violet-700 transition cursor-pointer flex-shrink-0">{{ $s['btn'] }}</button>
                                @elseif ($isRecovery)
                                    <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-md bg-slate-100 text-slate-500 text-[10.5px] font-bold tracking-wider uppercase flex-shrink-0">Segera tersedia</span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-md bg-slate-100 text-slate-500 text-[10.5px] font-bold tracking-wider uppercase flex-shrink-0">Segera tersedia</span>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="bg-white rounded-2xl border border-violet-100 shadow-[0_2px_8px_rgba(124,58,237,0.08)] p-7 mb-5">
                    <h3 class="text-[16px] font-bold text-[#1E1B4B] mb-1">Sesi Aktif</h3>
                    <p class="text-[13px] text-slate-500 mb-5">Perangkat yang saat ini login ke akun Anda.</p>
                    <div class="space-y-2">
                        @foreach ($sessions as $ses)
                            <div class="flex items-center justify-between gap-3 p-3 rounded-xl border border-violet-50">
                                <div class="flex items-center gap-3 min-w-0">
                                    <div class="w-9 h-9 rounded-lg bg-violet-50 text-violet-700 flex items-center justify-center flex-shrink-0">
                                        <x-dynamic-component :component="'heroicon-o-' . $ses['icon']" class="w-4 h-4" />
                                    </div>
                                    <div class="min-w-0">
                                        <div class="text-[13.5px] font-semibold text-[#1E1B4B]">
                                            {{ $ses['device'] }}@if ($ses['current']) <span class="text-slate-400 font-medium"> · ini</span>@endif
                                        </div>
                                        <div class="text-[11.5px] text-slate-500">{{ $ses['where'] }} · {{ $ses['time'] }}</div>
                                    </div>
                                </div>
                                @if ($ses['current'])
                                    <button type="button" disabled class="h-9 px-3 rounded-lg text-[12px] font-semibold text-slate-400 cursor-not-allowed flex-shrink-0">Sesi saat ini</button>
                                @else
                                    <form method="POST" action="{{ route('settings.sessions.destroy', $ses['id']) }}" onsubmit="return confirm('Hentikan sesi ini?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="h-9 px-3 rounded-lg text-[12px] font-semibold text-rose-600 hover:bg-rose-50 transition cursor-pointer flex-shrink-0">Hentikan</button>
                                    </form>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- "Hapus Akun" was a fake destructive control: the request was logged
                     but no deletion pipeline exists. Hidden until the flow is real. --}}
            </div>

            <div data-settings-actions class="flex items-center justify-end gap-3 pt-4 border-t border-violet-100">
                <a href="{{ route('settings.index', ['tab' => request('tab', 'general')]) }}" class="h-10 px-4 rounded-xl text-[13px] font-semibold text-slate-500 hover:text-slate-700 transition cursor-pointer inline-flex items-center">Batal</a>
                <button form="settings-preferences-form" type="submit" data-settings-save data-active-label="Simpan Umum" data-inactive-label="Belum ada perubahan" class="inline-flex items-center gap-2 h-10 px-5 rounded-xl bg-gradient-to-r from-[#7C3AED] via-[#A855F7] to-[#C084FC] text-white font-semibold text-[13px] shadow-[0_4px_14px_rgba(124,58,237,0.35)] hover:shadow-[0_6px_20px_rgba(124,58,237,0.45)] hover:-translate-y-0.5 transition-all cursor-pointer">
                    <x-heroicon-o-bookmark-square class="w-4 h-4" />
                    <span data-settings-save-label>Simpan Umum</span>
                </button>
            </div>

        </section>
    </div>

    {{-- ===== Password Modal ===== --}}
    <div data-pw-modal data-pw-has-errors="{{ $errors->has('current_password') || $errors->has('new_password') || $errors->has('new_password_confirmation') ? '1' : '0' }}" class="hidden fixed inset-0 z-50 items-center justify-center p-4 sm:p-6" role="dialog" aria-modal="true" aria-labelledby="pw-title">
        <div data-pw-overlay class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm"></div>
        <form method="POST" action="{{ route('settings.password.update') }}" data-pw-panel novalidate class="relative bg-white rounded-3xl shadow-[0_24px_64px_rgba(124,58,237,0.18)] w-full max-w-md flex flex-col overflow-hidden border border-violet-100">
            @csrf
            @method('PUT')
            <div class="px-6 py-4 border-b border-violet-100 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-violet-100 flex items-center justify-center">
                        <x-heroicon-o-lock-closed class="w-5 h-5 text-violet-700" />
                    </div>
                    <h3 id="pw-title" class="text-[16px] font-bold text-[#1E1B4B]">Ubah Password</h3>
                </div>
                <button type="button" data-pw-close aria-label="Tutup" class="w-9 h-9 rounded-full hover:bg-violet-50 flex items-center justify-center text-slate-500 hover:text-rose-500 transition cursor-pointer">
                    <x-heroicon-o-x-mark class="w-5 h-5" />
                </button>
            </div>
            <div class="px-6 py-5 space-y-4">
                <div>
                    <label class="block text-[11px] font-bold tracking-wider uppercase text-slate-500 mb-1.5">Password Saat Ini</label>
                    <input name="current_password" type="password" autocomplete="current-password" class="w-full h-11 rounded-xl border border-violet-100 px-4 text-[13.5px] text-[#1E1B4B] focus:outline-none focus:ring-2 focus:ring-violet-300 focus:border-violet-300 transition">
                    @error('current_password')
                        <p data-pw-server-error class="mt-1.5 text-[12px] font-semibold text-rose-600">{{ $message }}</p>
                    @enderror
                    <p data-pw-error="current_password" class="hidden mt-1.5 text-[12px] font-semibold text-rose-600"></p>
                </div>
                <div>
                    <label class="block text-[11px] font-bold tracking-wider uppercase text-slate-500 mb-1.5">Password Baru</label>
                    <input name="new_password" type="password" autocomplete="new-password" class="w-full h-11 rounded-xl border border-violet-100 px-4 text-[13.5px] text-[#1E1B4B] focus:outline-none focus:ring-2 focus:ring-violet-300 focus:border-violet-300 transition">
                    @error('new_password')
                        <p data-pw-server-error class="mt-1.5 text-[12px] font-semibold text-rose-600">{{ $message }}</p>
                    @enderror
                    <p data-pw-error="new_password" class="hidden mt-1.5 text-[12px] font-semibold text-rose-600"></p>
                </div>
                <div>
                    <label class="block text-[11px] font-bold tracking-wider uppercase text-slate-500 mb-1.5">Konfirmasi Password Baru</label>
                    <input name="new_password_confirmation" type="password" autocomplete="new-password" class="w-full h-11 rounded-xl border border-violet-100 px-4 text-[13.5px] text-[#1E1B4B] focus:outline-none focus:ring-2 focus:ring-violet-300 focus:border-violet-300 transition">
                    @error('new_password_confirmation')
                        <p data-pw-server-error class="mt-1.5 text-[12px] font-semibold text-rose-600">{{ $message }}</p>
                    @enderror
                    <p data-pw-error="new_password_confirmation" class="hidden mt-1.5 text-[12px] font-semibold text-rose-600"></p>
                </div>
            </div>
            <p data-pw-success class="hidden mx-6 mb-4 rounded-xl bg-emerald-50 border border-emerald-100 px-4 py-2 text-[12.5px] font-semibold text-emerald-700"></p>
            <div class="px-6 py-3 border-t border-violet-100 bg-violet-50/30 flex items-center justify-end gap-3">
                <button type="button" data-pw-close class="px-5 h-9 rounded-xl bg-white border border-violet-200 text-[13px] font-semibold text-slate-600 hover:border-violet-400 hover:text-violet-700 transition cursor-pointer">Batal</button>
                <button type="submit" data-pw-submit class="px-5 h-9 rounded-xl bg-gradient-to-r from-[#7C3AED] via-[#A855F7] to-[#C084FC] text-white text-[13px] font-semibold transition cursor-pointer">Simpan</button>
            </div>
        </form>
    </div>

    <script>
        (function () {
            const wire = () => {
                const tabs   = document.querySelectorAll('.js-settings-tab');
                const panels = document.querySelectorAll('[data-panel]');
                const actions = document.querySelector('[data-settings-actions]');
                const saveButton = document.querySelector('[data-settings-save]');
                const saveLabel = document.querySelector('[data-settings-save-label]');
                const applyFooterState = (id) => {
                    actions?.classList.toggle('hidden', id !== 'general');
                    if (! saveButton) return;
                    const active = id === 'general';
                    saveButton.disabled = ! active;
                    saveButton.setAttribute('aria-disabled', active ? 'false' : 'true');
                    saveButton.classList.toggle('bg-gradient-to-r', active);
                    saveButton.classList.toggle('from-[#7C3AED]', active);
                    saveButton.classList.toggle('via-[#A855F7]', active);
                    saveButton.classList.toggle('to-[#C084FC]', active);
                    saveButton.classList.toggle('text-white', active);
                    saveButton.classList.toggle('shadow-[0_4px_14px_rgba(124,58,237,0.35)]', active);
                    saveButton.classList.toggle('hover:shadow-[0_6px_20px_rgba(124,58,237,0.45)]', active);
                    saveButton.classList.toggle('hover:-translate-y-0.5', active);
                    saveButton.classList.toggle('cursor-pointer', active);
                    saveButton.classList.toggle('bg-slate-100', ! active);
                    saveButton.classList.toggle('text-slate-400', ! active);
                    saveButton.classList.toggle('shadow-none', ! active);
                    saveButton.classList.toggle('cursor-not-allowed', ! active);
                    if (saveLabel) saveLabel.textContent = 'Simpan Umum';
                };

                tabs.forEach(t => {
                    t.addEventListener('click', () => {
                        const id = t.dataset.tab;
                        const activeTab = document.querySelector('[data-settings-active-tab]');
                        if (activeTab) activeTab.value = id;
                        applyFooterState(id);
                        tabs.forEach(x => {
                            const active = x.dataset.tab === id;
                            x.classList.toggle('bg-gradient-to-br', active);
                            x.classList.toggle('from-violet-100', active);
                            x.classList.toggle('to-fuchsia-100', active);
                            x.classList.toggle('text-violet-700', active);
                            x.classList.toggle('text-slate-600', !active);
                            x.classList.toggle('hover:bg-violet-50/70', !active);
                            x.classList.toggle('hover:text-violet-700', !active);
                        });
                        panels.forEach(p => {
                            p.classList.toggle('hidden', p.dataset.panel !== id);
                        });
                    });
                });
                applyFooterState(document.querySelector('.js-settings-tab.bg-gradient-to-br')?.dataset.tab || 'general');

                document.querySelectorAll('.js-switch[data-backed-switch]').forEach(s => {
                    s.addEventListener('click', () => {
                        s.classList.toggle('is-on');
                        const on = s.classList.contains('is-on');
                        s.setAttribute('aria-checked', on ? 'true' : 'false');
                        const input = s.previousElementSibling?.matches?.('[data-switch-input]') ? s.previousElementSibling : null;
                        if (input) input.value = on ? '1' : '0';
                    });
                });

                const flashMessages = @json(array_values(array_filter(array_merge($flashStatus ? [$flashStatus] : [], $flashErrors))));
                flashMessages.forEach((msg, idx) => {
                    setTimeout(() => window.toast && window.toast(msg), idx * 350);
                });

                /* Activate a tab from URL: /settings?tab=notif or /settings#notif */
                const activateTabFromUrl = () => {
                    const params    = new URLSearchParams(window.location.search);
                    const fromQuery = (params.get('tab') || '').trim();
                    const fromHash  = window.location.hash.replace(/^#/, '').trim();
                    const requested = fromQuery || fromHash;
                    if (! requested) return;
                    const target = document.querySelector(`.js-settings-tab[data-tab="${CSS.escape(requested)}"]`);
                    if (target) target.click();
                };
                activateTabFromUrl();
                window.addEventListener('hashchange', activateTabFromUrl);
            };

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', wire);
            } else {
                wire();
            }
        })();
    </script>

    {{-- ===== Recovery Codes Modal ===== --}}
    <div data-rc-modal class="hidden fixed inset-0 z-50 items-center justify-center p-4 sm:p-6" role="dialog" aria-modal="true" aria-labelledby="rc-title">
        <div data-rc-overlay class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm"></div>
        <div data-rc-panel class="relative bg-white rounded-3xl shadow-[0_24px_64px_rgba(124,58,237,0.18)] w-full max-w-md flex flex-col overflow-hidden border border-violet-100">
            <div class="px-6 py-4 border-b border-violet-100 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-violet-100 flex items-center justify-center">
                        <x-heroicon-o-key class="w-5 h-5 text-violet-700" />
                    </div>
                    <h3 id="rc-title" class="text-[16px] font-bold text-[#1E1B4B]">Recovery Codes</h3>
                </div>
                <button type="button" data-rc-close aria-label="Tutup" class="w-9 h-9 rounded-full hover:bg-violet-50 flex items-center justify-center text-slate-500 hover:text-rose-500 transition cursor-pointer">
                    <x-heroicon-o-x-mark class="w-5 h-5" />
                </button>
            </div>
            <div class="px-6 py-5">
                <p class="text-[12.5px] text-slate-500 mb-4">Backup-code preparation sampai full 2FA tersedia. Plaintext hanya ditampilkan sekali; database menyimpan hash.</p>
                <div data-rc-grid data-rc-has-codes="{{ ! empty($recoveryCodes) ? '1' : '0' }}" class="grid grid-cols-2 gap-2 font-mono text-[13px] text-[#1E1B4B] bg-violet-50/40 border border-violet-100 rounded-xl p-4">
                    @foreach ($recoveryCodes as $code)
                        <div class="px-2 py-1.5 bg-white rounded-md text-center tracking-wider">{{ $code }}</div>
                    @endforeach
                </div>
            </div>
            <div class="px-6 py-3 border-t border-violet-100 bg-violet-50/30 flex items-center justify-between gap-3">
                <button type="button" data-rc-copy class="text-[12px] font-semibold text-violet-700 hover:text-violet-900 transition cursor-pointer inline-flex items-center gap-1.5">
                    <x-heroicon-o-clipboard class="w-3.5 h-3.5" />
                    Salin Semua
                </button>
                <button type="button" data-rc-close class="px-5 h-9 rounded-xl bg-white border border-violet-200 text-[13px] font-semibold text-slate-600 hover:border-violet-400 hover:text-violet-700 transition cursor-pointer">Tutup</button>
            </div>
        </div>
    </div>

    <script>
        (function () {
            const wire = () => {
                /* === Legacy integration helper kept inert; integration cards are informational in this build. === */
                const IG_KEY = 'avt-integrations';
                let igState = {};
                try { igState = {}; } catch (e) {}

                const renderIntegration = (card) => {
                    const key       = card.dataset.integrationKey;
                    const name      = card.dataset.integrationName;
                    const initial   = card.dataset.integrationInitial === '1';
                    const connected = (key in igState) ? !! igState[key] : initial;
                    const badge = card.querySelector('[data-integration-badge]');
                    const btn   = card.querySelector('[data-db-integration-toggle]');
                    if (badge) {
                        badge.className = 'text-[10px] font-bold tracking-wider uppercase px-2 py-0.5 rounded-md bg-slate-100 text-slate-500';
                        badge.textContent = 'Belum dikonfigurasi';
                    }
                    if (btn) {
                        btn.className = 'w-full h-9 rounded-lg text-[12.5px] font-semibold bg-slate-100 text-slate-400 cursor-not-allowed';
                        btn.textContent = 'Segera tersedia';
                        btn.disabled = true;
                    }
                    card.dataset.integrationConnected = connected ? '1' : '0';
                };

                document.querySelectorAll('[data-integration-card]').forEach(renderIntegration);

                document.querySelectorAll('[data-db-integration-toggle]').forEach(btn => {
                    btn.addEventListener('click', () => {
                        const card = btn.closest('[data-integration-card]');
                        if (! card) return;
                        const key  = card.dataset.integrationKey;
                        const name = card.dataset.integrationName;
                        const cur  = card.dataset.integrationConnected === '1';
                        window.toast && window.toast(name + ' belum dikonfigurasi.');
                    });
                });

                /* === Recovery Codes modal === */
                const rcModal   = document.querySelector('[data-rc-modal]');
                const rcOverlay = rcModal?.querySelector('[data-rc-overlay]');
                const rcPanel   = rcModal?.querySelector('[data-rc-panel]');
                const rcGrid    = rcModal?.querySelector('[data-rc-grid]');
                const rcCopy    = rcModal?.querySelector('[data-rc-copy]');
                const openRc = () => {
                    if (! rcModal || ! rcGrid) return;
                    rcModal.classList.remove('hidden');
                    rcModal.classList.add('flex');
                    document.body.style.overflow = 'hidden';
                };
                const closeRc = () => {
                    if (! rcModal) return;
                    rcModal.classList.add('hidden');
                    rcModal.classList.remove('flex');
                    document.body.style.overflow = '';
                };
                document.querySelector('[data-show-recovery-codes]')?.addEventListener('click', openRc);
                if (rcGrid?.dataset.rcHasCodes === '1') openRc();
                rcPanel?.addEventListener('click', (e) => e.stopPropagation());
                rcOverlay?.addEventListener('click', closeRc);
                rcModal?.querySelectorAll('[data-rc-close]').forEach(b => b.addEventListener('click', closeRc));
                document.addEventListener('keydown', (e) => {
                    if (e.key === 'Escape' && rcModal && ! rcModal.classList.contains('hidden')) closeRc();
                    if (e.key === 'Escape' && pwModal && ! pwModal.classList.contains('hidden')) closePw();
                });
                rcCopy?.addEventListener('click', () => {
                    const codes = Array.from(rcGrid?.children || []).map(el => el.textContent).join('\n');
                    if (navigator.clipboard) {
                        navigator.clipboard.writeText(codes).then(
                            () => window.toast && window.toast('10 recovery codes disalin ke clipboard.'),
                            () => window.toast && window.toast('Gagal menyalin — pilih manual.')
                        );
                    } else {
                        window.toast && window.toast('Clipboard API tidak tersedia.');
                    }
                });

                /* === Password modal === */
                const pwModal = document.querySelector('[data-pw-modal]');
                const pwOverlay = pwModal?.querySelector('[data-pw-overlay]');
                const pwPanel = pwModal?.querySelector('[data-pw-panel]');
                const pwSubmit = pwModal?.querySelector('[data-pw-submit]');
                const pwSuccess = pwModal?.querySelector('[data-pw-success]');
                let pwSubmitting = false;
                const setPwClientError = (name, message) => {
                    const el = pwModal?.querySelector(`[data-pw-error="${name}"]`);
                    if (! el) return;
                    el.textContent = Array.isArray(message) ? message[0] : (message || '');
                    el.classList.toggle('hidden', !message);
                };
                const clearPwFeedback = () => {
                    ['current_password', 'new_password', 'new_password_confirmation'].forEach(name => setPwClientError(name, ''));
                    pwModal?.querySelectorAll('[data-pw-server-error]').forEach(el => el.classList.add('hidden'));
                    if (pwSuccess) {
                        pwSuccess.textContent = '';
                        pwSuccess.classList.add('hidden');
                    }
                };
                const setPwSubmitting = (submitting) => {
                    pwSubmitting = submitting;
                    if (! pwSubmit) return;
                    pwSubmit.disabled = submitting;
                    pwSubmit.classList.toggle('opacity-70', submitting);
                    pwSubmit.classList.toggle('cursor-not-allowed', submitting);
                    pwSubmit.textContent = submitting ? 'Menyimpan...' : 'Simpan';
                };
                const showPwErrors = (errors) => {
                    Object.entries(errors || {}).forEach(([name, messages]) => setPwClientError(name, messages));
                };
                const openPw = () => {
                    if (! pwModal) return;
                    pwModal.classList.remove('hidden');
                    pwModal.classList.add('flex');
                    document.body.style.overflow = 'hidden';
                };
                const closePw = () => {
                    if (! pwModal) return;
                    pwModal.classList.add('hidden');
                    pwModal.classList.remove('flex');
                    document.body.style.overflow = '';
                };
                document.querySelector('[data-show-password-modal]')?.addEventListener('click', openPw);
                pwPanel?.addEventListener('click', (e) => e.stopPropagation());
                pwPanel?.addEventListener('submit', async (e) => {
                    e.preventDefault();
                    if (pwSubmitting) return;
                    clearPwFeedback();
                    setPwSubmitting(true);

                    try {
                        const response = await fetch(pwPanel.action, {
                            method: 'POST',
                            body: new FormData(pwPanel),
                            credentials: 'same-origin',
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                        });
                        const data = await response.json().catch(() => ({}));

                        if (response.status === 422) {
                            showPwErrors(data.errors || {});
                            openPw();
                            return;
                        }

                        if (! response.ok) {
                            window.toast && window.toast(data.message || 'Gagal memperbarui password.');
                            return;
                        }

                        pwPanel.querySelectorAll('input[type="password"]').forEach(input => { input.value = ''; });
                        if (pwSuccess) {
                            pwSuccess.textContent = data.message || 'Password berhasil diperbarui.';
                            pwSuccess.classList.remove('hidden');
                        }
                        window.toast && window.toast(data.message || 'Password berhasil diperbarui.');
                        setTimeout(closePw, 1100);
                    } catch (err) {
                        window.toast && window.toast('Gagal memperbarui password.');
                    } finally {
                        setPwSubmitting(false);
                    }
                });
                pwOverlay?.addEventListener('click', closePw);
                pwModal?.querySelectorAll('[data-pw-close]').forEach(b => b.addEventListener('click', closePw));
                if (pwModal?.dataset.pwHasErrors === '1') openPw();
            };
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', wire);
            } else {
                wire();
            }
        })();
    </script>
</x-layouts.authenticated>
