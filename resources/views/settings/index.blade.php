@php
    $tabs = [
        ['id' => 'general',      'icon' => 'adjustments-horizontal', 'label' => 'Umum'],
        ['id' => 'ai',           'icon' => 'sparkles',                'label' => 'Sekretaris AI'],
        ['id' => 'notif',        'icon' => 'bell',                    'label' => 'Notifikasi'],
        ['id' => 'integrations', 'icon' => 'puzzle-piece',            'label' => 'Integrasi'],
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
        ['label' => 'AI Output Siap',    'desc' => 'WBS, TC, MoM, Reminder draft',       'app' => true, 'email' => true,  'wa' => false],
        ['label' => 'Proyek Critical',   'desc' => 'Status berubah ke Critical Blocker', 'app' => true, 'email' => true,  'wa' => true],
        ['label' => 'Approval Diminta',  'desc' => 'Anda diminta approve sesuatu',       'app' => true, 'email' => true,  'wa' => false],
        ['label' => 'Task Movement',     'desc' => 'Task pindah di Kanban proyek Anda',  'app' => true, 'email' => false, 'wa' => false],
        ['label' => 'Klien Reply',       'desc' => 'Balasan WhatsApp klien diterima',    'app' => true, 'email' => true,  'wa' => true],
        ['label' => 'Workload Alert',    'desc' => 'Anda mendekati overload',            'app' => true, 'email' => false, 'wa' => false],
        ['label' => 'Sistem & Keamanan', 'desc' => 'Login baru, backup, perubahan akun', 'app' => true, 'email' => true,  'wa' => false],
    ];

    $integrations = [
        ['name' => 'WhatsApp Business', 'icon' => 'chat-bubble-oval-left',  'color' => '#10B981', 'connected' => true,  'desc' => 'CRM Lite — pre-filled outbound message via wa.me'],
        ['name' => 'Google Calendar',   'icon' => 'calendar',                'color' => '#3B82F6', 'connected' => true,  'desc' => 'Sinkronisasi deadline proyek & rapat tim'],
        ['name' => 'Slack',             'icon' => 'chat-bubble-left-right', 'color' => '#7C3AED', 'connected' => false, 'desc' => 'Push notifikasi AI ke channel #avatech-pm'],
        ['name' => 'GitHub',            'icon' => 'code-bracket-square',    'color' => '#1E1B4B', 'connected' => true,  'desc' => 'Link PR ke task Kanban + commit di Audit Trail'],
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
@endphp

<x-layouts.authenticated :title="$title">

    <style>
        .js-switch { width: 42px; height: 24px; background: #E2E8F0; border-radius: 9999px; position: relative; transition: .2s; cursor: pointer; flex-shrink: 0; }
        .js-switch::after { content: ''; position: absolute; top: 2px; left: 2px; width: 20px; height: 20px; background: #fff; border-radius: 9999px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); transition: .2s; }
        .js-switch.is-on { background: linear-gradient(135deg, #7C3AED, #A855F7); }
        .js-switch.is-on::after { left: 20px; }
    </style>

    <section class="mb-8">
        <h1 class="text-[44px] leading-[1.05] font-bold tracking-tight text-[#1E1B4B]">
            Set<span class="bg-clip-text text-transparent bg-gradient-to-r from-[#7C3AED] to-[#A855F7]">tings</span>
        </h1>
        <p class="mt-3 text-[15px] text-slate-500 max-w-2xl">
            Konfigurasi sistem, preferensi AI, dan integrasi pihak ketiga untuk akun Avatech Anda.
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
                    <p class="text-[13px] text-slate-500 mb-5">Identitas perusahaan yang muncul di laporan dan ekspor.</p>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-[11px] font-bold tracking-wider uppercase text-slate-500 mb-1.5">Nama Workspace</label>
                            <input type="text" value="PT Ava Teknologi Nusantara" class="w-full h-11 rounded-xl border border-violet-100 px-4 text-[13.5px] text-[#1E1B4B] focus:outline-none focus:ring-2 focus:ring-violet-300 focus:border-violet-300 transition" />
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold tracking-wider uppercase text-slate-500 mb-1.5">Subdomain</label>
                            <div class="flex items-center h-11 rounded-xl border border-violet-100 overflow-hidden focus-within:ring-2 focus-within:ring-violet-300 focus-within:border-violet-300 transition">
                                <input type="text" value="avatech" class="flex-1 h-full px-4 text-[13.5px] text-[#1E1B4B] focus:outline-none" />
                                <span class="px-3 text-[13px] text-slate-400 bg-violet-50/40 h-full flex items-center">.smartpmis.id</span>
                            </div>
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold tracking-wider uppercase text-slate-500 mb-1.5">Bahasa Antarmuka</label>
                            <select class="w-full h-11 rounded-xl border border-violet-100 px-4 text-[13.5px] text-[#1E1B4B] bg-white focus:outline-none focus:ring-2 focus:ring-violet-300 cursor-pointer">
                                <option>Bahasa Indonesia</option>
                                <option>English</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold tracking-wider uppercase text-slate-500 mb-1.5">Zona Waktu</label>
                            <select class="w-full h-11 rounded-xl border border-violet-100 px-4 text-[13.5px] text-[#1E1B4B] bg-white focus:outline-none focus:ring-2 focus:ring-violet-300 cursor-pointer">
                                <option>Asia/Jakarta · GMT+7</option>
                                <option>Asia/Singapore · GMT+8</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-2xl border border-violet-100 shadow-[0_2px_8px_rgba(124,58,237,0.08)] p-7">
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
                <div class="bg-white rounded-2xl border border-violet-100 shadow-[0_2px_8px_rgba(124,58,237,0.08)] p-7 mb-5">
                    <h3 class="text-[16px] font-bold text-[#1E1B4B] mb-1">Mesin AI</h3>
                    <p class="text-[13px] text-slate-500 mb-5">Konfigurasi model dan provider untuk Sekretaris Digital.</p>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-5">
                        <div>
                            <label class="block text-[11px] font-bold tracking-wider uppercase text-slate-500 mb-1.5">Provider</label>
                            <select class="w-full h-11 rounded-xl border border-violet-100 px-4 text-[13.5px] text-[#1E1B4B] bg-white focus:outline-none focus:ring-2 focus:ring-violet-300 cursor-pointer">
                                <option>Anthropic Claude</option>
                                <option>OpenAI GPT-4o</option>
                                <option>Anthropic + OpenAI Fallback</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold tracking-wider uppercase text-slate-500 mb-1.5">Model Default</label>
                            <select class="w-full h-11 rounded-xl border border-violet-100 px-4 text-[13.5px] text-[#1E1B4B] bg-white focus:outline-none focus:ring-2 focus:ring-violet-300 cursor-pointer">
                                <option>claude-haiku-4-5</option>
                                <option>claude-sonnet-4-5</option>
                                <option>gpt-4o</option>
                            </select>
                        </div>
                    </div>
                    <div class="space-y-1">
                        @foreach ($aiFlags as $ai)
                            <div class="flex items-center justify-between py-3 border-b border-violet-50 last:border-0 gap-3">
                                <div class="min-w-0">
                                    <div class="text-[13.5px] font-semibold text-[#1E1B4B]">{{ $ai['label'] }}</div>
                                    <div class="text-[12px] text-slate-500 mt-0.5">{{ $ai['note'] }}</div>
                                </div>
                                <div class="js-switch {{ $ai['on'] ? 'is-on' : '' }}" data-setting-key="ai_{{ $ai['key'] }}" role="switch" aria-checked="{{ $ai['on'] ? 'true' : 'false' }}"></div>
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
                    <h3 class="text-[16px] font-bold text-[#1E1B4B] mb-1">Preferensi Notifikasi</h3>
                    <p class="text-[13px] text-slate-500 mb-5">Pilih kanal untuk setiap kategori. Email digital dan in-app aktif default.</p>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left">
                            <thead>
                                <tr class="text-[11px] font-bold tracking-[0.1em] uppercase text-slate-400 border-b border-violet-50">
                                    <th class="py-3 pr-4">Kategori</th>
                                    <th class="py-3 px-3 text-center">In-App</th>
                                    <th class="py-3 px-3 text-center">Email</th>
                                    <th class="py-3 px-3 text-center">WhatsApp</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($notifRows as $row)
                                    <tr class="border-b border-violet-50 last:border-0">
                                        <td class="py-3 pr-4">
                                            <div class="text-[13.5px] font-semibold text-[#1E1B4B]">{{ $row['label'] }}</div>
                                            <div class="text-[12px] text-slate-500">{{ $row['desc'] }}</div>
                                        </td>
                                        @php $rowSlug = Illuminate\Support\Str::slug($row['label']); @endphp
                                        <td class="py-3 px-3"><div class="js-switch {{ $row['app']   ? 'is-on' : '' }} mx-auto" data-setting-key="notif_app_{{ $rowSlug }}" role="switch"></div></td>
                                        <td class="py-3 px-3"><div class="js-switch {{ $row['email'] ? 'is-on' : '' }} mx-auto" data-setting-key="notif_email_{{ $rowSlug }}" role="switch"></div></td>
                                        <td class="py-3 px-3"><div class="js-switch {{ $row['wa']    ? 'is-on' : '' }} mx-auto" data-setting-key="notif_wa_{{ $rowSlug }}" role="switch"></div></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div data-panel="integrations" class="hidden">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    @foreach ($integrations as $ig)
                        @php $igSlug = Illuminate\Support\Str::slug($ig['name']); @endphp
                        <div data-integration-card data-integration-key="{{ $igSlug }}" data-integration-name="{{ $ig['name'] }}" data-integration-initial="{{ $ig['connected'] ? '1' : '0' }}" class="bg-white rounded-2xl border border-violet-100 shadow-[0_2px_8px_rgba(124,58,237,0.08)] p-5">
                            <div class="flex items-start gap-3 mb-3">
                                <div class="w-11 h-11 rounded-xl flex items-center justify-center text-white flex-shrink-0" style="background: {{ $ig['color'] }};">
                                    <x-dynamic-component :component="'heroicon-o-' . $ig['icon']" class="w-5 h-5" />
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <h4 class="text-[14.5px] font-bold text-[#1E1B4B]">{{ $ig['name'] }}</h4>
                                        <span data-integration-badge @class([
                                            'text-[10px] font-bold tracking-wider uppercase px-2 py-0.5 rounded-md',
                                            'bg-emerald-100 text-emerald-700' => $ig['connected'],
                                            'bg-slate-100 text-slate-500' => ! $ig['connected'],
                                        ])>{{ $ig['connected'] ? 'Terhubung' : 'Belum' }}</span>
                                    </div>
                                    <p class="text-[12.5px] text-slate-500 mt-1 leading-relaxed">{{ $ig['desc'] }}</p>
                                </div>
                            </div>
                            <button
                                type="button"
                                data-integration-toggle
                                @class([
                                    'w-full h-9 rounded-lg text-[12.5px] font-semibold transition cursor-pointer',
                                    'bg-violet-50 text-violet-700 hover:bg-violet-100' => $ig['connected'],
                                    'bg-gradient-to-r from-[#7C3AED] via-[#A855F7] to-[#C084FC] text-white shadow-[0_2px_8px_rgba(124,58,237,0.2)] hover:-translate-y-0.5' => ! $ig['connected'],
                                ])
                            >
                                {{ $ig['connected'] ? 'Kelola' : 'Hubungkan' }}
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
                            <div class="flex items-center justify-between py-3 border-b border-violet-50 last:border-0 gap-3">
                                <div class="min-w-0">
                                    <div class="text-[13.5px] font-semibold text-[#1E1B4B]">{{ $s['label'] }}</div>
                                    <div class="text-[12px] text-slate-500 mt-0.5">{{ $s['desc'] }}</div>
                                </div>
                                @if ($s['kind'] === 'switch')
                                    @php $secSlug = Illuminate\Support\Str::slug($s['label']); @endphp
                                    <div class="js-switch {{ $s['on'] ? 'is-on' : '' }}" data-setting-key="security_{{ $secSlug }}" role="switch"></div>
                                @else
                                    @if (str_starts_with(Illuminate\Support\Str::lower($s['label']), 'recovery'))
                                        <button type="button" data-show-recovery-codes class="h-9 px-3.5 rounded-lg border border-violet-200 text-[12.5px] font-semibold text-slate-600 hover:border-violet-400 hover:text-violet-700 transition cursor-pointer flex-shrink-0">{{ $s['btn'] }}</button>
                                    @else
                                        <button type="button" data-toast="{{ $s['label'] }} segera tersedia." class="h-9 px-3.5 rounded-lg border border-violet-200 text-[12.5px] font-semibold text-slate-600 hover:border-violet-400 hover:text-violet-700 transition cursor-pointer flex-shrink-0">{{ $s['btn'] }}</button>
                                    @endif
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
                                    <button type="button" data-confirm="Hentikan sesi ini?" data-toast-after="Sesi dihentikan (demo)." class="h-9 px-3 rounded-lg text-[12px] font-semibold text-rose-600 hover:bg-rose-50 transition cursor-pointer flex-shrink-0">Hentikan</button>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="rounded-2xl border border-rose-200 bg-rose-50/40 p-6 flex items-start gap-4 flex-wrap">
                    <x-heroicon-o-exclamation-triangle class="w-5 h-5 text-rose-700 mt-0.5 flex-shrink-0" />
                    <div class="flex-1 min-w-[240px]">
                        <h4 class="text-[14px] font-bold text-rose-900">Hapus Akun</h4>
                        <p class="text-[12.5px] text-rose-700 mt-1">Tindakan ini permanen. Semua data audit Anda akan tetap tersimpan untuk kepatuhan.</p>
                    </div>
                    <button type="button" data-confirm="Hapus akun ini? Tindakan ini tidak dapat dibatalkan." data-toast-after="Permintaan hapus akun tercatat (demo — tidak ada penghapusan nyata)." class="h-9 px-4 rounded-lg bg-rose-600 hover:bg-rose-700 text-white text-[12.5px] font-semibold transition cursor-pointer flex-shrink-0">Hapus Akun</button>
                </div>
            </div>

            <div class="flex items-center justify-end gap-3 pt-4 border-t border-violet-100">
                <button type="button" data-settings-cancel class="h-10 px-4 rounded-xl text-[13px] font-semibold text-slate-500 hover:text-slate-700 transition cursor-pointer">Batal</button>
                <button type="button" data-settings-save class="inline-flex items-center gap-2 h-10 px-5 rounded-xl bg-gradient-to-r from-[#7C3AED] via-[#A855F7] to-[#C084FC] text-white font-semibold text-[13px] shadow-[0_4px_14px_rgba(124,58,237,0.35)] hover:shadow-[0_6px_20px_rgba(124,58,237,0.45)] hover:-translate-y-0.5 transition-all cursor-pointer">
                    <x-heroicon-o-bookmark-square class="w-4 h-4" />
                    Simpan Perubahan
                </button>
            </div>

        </section>
    </div>

    <script>
        (function () {
            const wire = () => {
                const tabs   = document.querySelectorAll('.js-settings-tab');
                const panels = document.querySelectorAll('[data-panel]');

                tabs.forEach(t => {
                    t.addEventListener('click', () => {
                        const id = t.dataset.tab;
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

                document.querySelectorAll('.js-switch').forEach(s => {
                    s.addEventListener('click', () => {
                        s.classList.toggle('is-on');
                        s.setAttribute('aria-checked', s.classList.contains('is-on') ? 'true' : 'false');
                    });
                });

                /* === Settings persistence: localStorage Save / Cancel === */
                const SETTINGS_LS_KEY = 'avt-settings-prefs';
                const keyedSwitches = () => document.querySelectorAll('.js-switch[data-setting-key]');
                const snapshot = () => {
                    const out = {};
                    keyedSwitches().forEach(s => {
                        out[s.dataset.settingKey] = s.classList.contains('is-on');
                    });
                    return out;
                };
                const applySnapshot = (snap) => {
                    if (! snap || typeof snap !== 'object') return;
                    keyedSwitches().forEach(s => {
                        const key = s.dataset.settingKey;
                        if (! (key in snap)) return;
                        const on = !! snap[key];
                        s.classList.toggle('is-on', on);
                        s.setAttribute('aria-checked', on ? 'true' : 'false');
                    });
                };
                // 1) On load: restore from localStorage if any
                try {
                    const raw = localStorage.getItem(SETTINGS_LS_KEY);
                    if (raw) applySnapshot(JSON.parse(raw));
                } catch (e) {}
                // 2) Track lastSaved (post-restore baseline) for Cancel revert
                let lastSaved = snapshot();
                // 3) Simpan
                document.querySelector('[data-settings-save]')?.addEventListener('click', () => {
                    const snap = snapshot();
                    try { localStorage.setItem(SETTINGS_LS_KEY, JSON.stringify(snap)); } catch (e) {}
                    lastSaved = snap;
                    if (window.toast) window.toast('Pengaturan tersimpan.');
                });
                // 4) Batal
                document.querySelector('[data-settings-cancel]')?.addEventListener('click', () => {
                    applySnapshot(lastSaved);
                    if (window.toast) window.toast('Perubahan dibatalkan.');
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
                <p class="text-[12.5px] text-slate-500 mb-4">Simpan 10 kode cadangan ini di tempat aman. Tiap kode dapat digunakan sekali bila kehilangan akses 2FA.</p>
                <div data-rc-grid class="grid grid-cols-2 gap-2 font-mono text-[13px] text-[#1E1B4B] bg-violet-50/40 border border-violet-100 rounded-xl p-4"></div>
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
                /* === Integration Hubungkan/Kelola toggle with localStorage === */
                const IG_KEY = 'avt-integrations';
                let igState = {};
                try { igState = JSON.parse(localStorage.getItem(IG_KEY) || '{}'); } catch (e) {}

                const renderIntegration = (card) => {
                    const key       = card.dataset.integrationKey;
                    const name      = card.dataset.integrationName;
                    const initial   = card.dataset.integrationInitial === '1';
                    const connected = (key in igState) ? !! igState[key] : initial;
                    const badge = card.querySelector('[data-integration-badge]');
                    const btn   = card.querySelector('[data-integration-toggle]');
                    if (badge) {
                        badge.className = 'text-[10px] font-bold tracking-wider uppercase px-2 py-0.5 rounded-md ' + (connected ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-500');
                        badge.textContent = connected ? 'Terhubung' : 'Belum';
                    }
                    if (btn) {
                        btn.className = 'w-full h-9 rounded-lg text-[12.5px] font-semibold transition cursor-pointer ' + (connected ? 'bg-violet-50 text-violet-700 hover:bg-violet-100' : 'bg-gradient-to-r from-[#7C3AED] via-[#A855F7] to-[#C084FC] text-white shadow-[0_2px_8px_rgba(124,58,237,0.2)] hover:-translate-y-0.5');
                        btn.textContent = connected ? 'Kelola' : 'Hubungkan';
                    }
                    card.dataset.integrationConnected = connected ? '1' : '0';
                };

                document.querySelectorAll('[data-integration-card]').forEach(renderIntegration);

                document.querySelectorAll('[data-integration-toggle]').forEach(btn => {
                    btn.addEventListener('click', () => {
                        const card = btn.closest('[data-integration-card]');
                        if (! card) return;
                        const key  = card.dataset.integrationKey;
                        const name = card.dataset.integrationName;
                        const cur  = card.dataset.integrationConnected === '1';
                        // Toggle: connected -> open mgmt (toast); not-connected -> connect (toast confirmation)
                        if (cur) {
                            // Currently connected → "Kelola" is mgmt action (placeholder), don't disconnect on first click
                            // Provide a confirm to disconnect for clarity:
                            if (! confirm('Putuskan koneksi ' + name + '?')) return;
                            igState[key] = false;
                            try { localStorage.setItem(IG_KEY, JSON.stringify(igState)); } catch (e) {}
                            renderIntegration(card);
                            window.toast && window.toast(name + ' diputus.');
                        } else {
                            igState[key] = true;
                            try { localStorage.setItem(IG_KEY, JSON.stringify(igState)); } catch (e) {}
                            renderIntegration(card);
                            window.toast && window.toast(name + ' terhubung (demo).');
                        }
                    });
                });

                /* === Recovery Codes modal === */
                const rcModal   = document.querySelector('[data-rc-modal]');
                const rcOverlay = rcModal?.querySelector('[data-rc-overlay]');
                const rcPanel   = rcModal?.querySelector('[data-rc-panel]');
                const rcGrid    = rcModal?.querySelector('[data-rc-grid]');
                const rcCopy    = rcModal?.querySelector('[data-rc-copy]');
                const genCode = () => {
                    const chars = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789'; // omit confusable chars
                    const arr = new Uint8Array(8);
                    (window.crypto || window.msCrypto).getRandomValues(arr);
                    return Array.from(arr).map(b => chars[b % chars.length]).join('').replace(/(.{4})/, '$1-');
                };
                const openRc = () => {
                    if (! rcModal || ! rcGrid) return;
                    rcGrid.innerHTML = '';
                    for (let i = 0; i < 10; i++) {
                        const cell = document.createElement('div');
                        cell.className = 'px-2 py-1.5 bg-white rounded-md text-center tracking-wider';
                        cell.textContent = genCode();
                        rcGrid.appendChild(cell);
                    }
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
                rcPanel?.addEventListener('click', (e) => e.stopPropagation());
                rcOverlay?.addEventListener('click', closeRc);
                rcModal?.querySelectorAll('[data-rc-close]').forEach(b => b.addEventListener('click', closeRc));
                document.addEventListener('keydown', (e) => {
                    if (e.key === 'Escape' && rcModal && ! rcModal.classList.contains('hidden')) closeRc();
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
            };
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', wire);
            } else {
                wire();
            }
        })();
    </script>
</x-layouts.authenticated>
