<?php

namespace Database\Seeders;

use App\Models\AiRequestLog;
use App\Models\AuditLog;
use App\Models\Client;
use App\Models\Project;
use App\Models\ProjectMom;
use App\Models\ProjectModule;
use App\Models\ProjectQcTest;
use App\Models\ProjectTask;
use App\Models\ProjectTaskDesignDeliverable;
use App\Models\TeamAssignment;
use App\Models\User;
use App\Support\AppTime;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Spatie\Permission\PermissionRegistrar;

/**
 * Dedicated, idempotent demo seeder for Avatech Smart-PMIS.
 *
 * Goal: make the product feel alive and operational with realistic — but
 * 100% safe/dummy — internal demo data. It deliberately respects every piece
 * of production-workflow hardening already in place:
 *   - requires_design / Design-phase gating  (non-design tasks stay "planned"
 *     while a project sits in Design)
 *   - repeatable design deliverables (project_task_design_deliverables + the
 *     task-level deliverable_* fields)
 *   - multi-responsibility Quick Assign (team_assignments.responsibilities)
 *   - task-based progress (progress column kept in sync with hour-weighted done)
 *   - QC gating (test cases only seeded on Development / QC / Done phases)
 *   - QC passed/total stays logical (never 100% unless all really pass)
 *   - Audit Trail + AI Monitor metadata-only
 *
 * Safety / idempotency:
 *   - Everything uses firstOrCreate / updateOrCreate on stable keys
 *     (user email, client email, project code, titles) so re-running never
 *     duplicates demo rows.
 *   - User passwords are only set on first creation — never overwritten.
 *   - Nothing is truncated or hard-deleted. No real user data is touched.
 *   - Demo ownership markers (for optional manual cleanup):
 *       * project code prefix      : DM   (projects.code is string(4), so the
 *                                          requested "AVT-DEMO-" prefix does not
 *                                          fit — DM01..DM09 is used instead)
 *       * client email domain      : @demo.avatech.local
 *       * demo user email domain   : @avatech.demo
 *
 * Run manually (NOT wired into DatabaseSeeder):
 *   php artisan db:seed --class=AvatechDemoSeeder
 */
class AvatechDemoSeeder extends Seeder
{
    private const USER_AGENT = 'Smart-PMIS Demo Seeder';
    private const PDF_DIR = 'project-design-deliverables';

    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $users = $this->seedUsers();
        $clients = $this->seedClients();

        if (empty($users) || empty($clients)) {
            $this->command?->warn('AvatechDemoSeeder skipped: could not provision demo users/clients.');

            return;
        }

        $projectCount = 0;
        foreach ($this->portfolio() as $blueprint) {
            $this->seedProject($blueprint, $users, $clients);
            $projectCount++;
        }

        // TODO: Seed ProjectRequirementInboxItem demo data for AIS Universitas project
        // when AIS Universitas project blueprint is added to portfolio().

        $this->command?->info("AvatechDemoSeeder selesai: {$projectCount} demo project, " . count($users) . ' user, ' . count($clients) . ' client.');
    }

    /* ===================================================================
     |  USERS
     * =================================================================== */

    /** @return array<string, User> keyed by short slug */
    private function seedUsers(): array
    {
        // role values must match the app's existing role catalogue
        // (ceo_pm, sa_qa, fullstack_dev, uiux_designer). Anything outside that
        // set would break RBAC guards, so support roles are mapped safely and
        // the *nuance* is carried by team_assignments.responsibilities instead.
        $rows = [
            ['key' => 'joshua', 'name' => 'Joshua Raphael',          'email' => 'joshua@avatech.demo', 'role' => 'ceo_pm'],
            ['key' => 'adly',   'name' => 'Ahmad Rafiadly Arlisyah', 'email' => 'adly@avatech.demo',   'role' => 'sa_qa'],
            ['key' => 'yuda',   'name' => 'Yuda Prayoga',            'email' => 'yuda@avatech.demo',   'role' => 'uiux_designer'],
            ['key' => 'ferry',  'name' => 'Ferry',                   'email' => 'ferry@avatech.demo',  'role' => 'fullstack_dev'],
            ['key' => 'irwan',  'name' => 'Irwan',                   'email' => 'irwan@avatech.demo',  'role' => 'fullstack_dev'],
            ['key' => 'genta',  'name' => 'Genta',                   'email' => 'genta@avatech.demo',  'role' => 'fullstack_dev'],
            ['key' => 'achmad', 'name' => 'Achmad',                  'email' => 'achmad@avatech.demo', 'role' => 'fullstack_dev'],
        ];

        $users = [];
        foreach ($rows as $row) {
            $user = User::firstOrCreate(
                ['email' => $row['email']],
                [
                    'name'              => $row['name'],
                    'password'          => Hash::make('password'),
                    'email_verified_at' => now(),
                ],
            );

            // keep name/verification fresh WITHOUT ever touching the password
            if (! $user->wasRecentlyCreated) {
                $dirty = false;
                if ($user->name !== $row['name']) {
                    $user->name = $row['name'];
                    $dirty = true;
                }
                if ($user->email_verified_at === null) {
                    $user->email_verified_at = now();
                    $dirty = true;
                }
                if ($dirty) {
                    $user->save();
                }
            }

            $user->syncRoles([$row['role']]);
            $users[$row['key']] = $user;
        }

        return $users;
    }

    /* ===================================================================
     |  CLIENTS
     * =================================================================== */

    /** @return array<string, Client> keyed by short slug */
    private function seedClients(): array
    {
        $rows = [
            ['key' => 'ava',      'name' => 'PT Ava Teknologi Nusantara', 'code' => 'AVN', 'tier' => 'strategic', 'industry' => 'Software House',     'location' => 'Tangerang', 'pic' => 'Joshua Raphael',  'pic_role' => 'CEO',                'health' => 88],
            ['key' => 'aloka',    'name' => 'Yayasan Aloka Sejahtera',    'code' => 'ALK', 'tier' => 'growth',    'industry' => 'Non-Profit / Donasi', 'location' => 'Jakarta',   'pic' => 'Ibu Rahmawati',   'pic_role' => 'Program Director',   'health' => 74],
            ['key' => 'stullo',   'name' => 'Stullo Academy Indonesia',   'code' => 'STL', 'tier' => 'strategic', 'industry' => 'EdTech / LMS',        'location' => 'Bandung',   'pic' => 'Bapak Dimas',     'pic_role' => 'Head of Product',    'health' => 69],
            ['key' => 'artisan',  'name' => 'Artisan B2B Creative',       'code' => 'ART', 'tier' => 'standard',  'industry' => 'Creative Agency',     'location' => 'Surabaya',  'pic' => 'Ibu Cynthia',     'pic_role' => 'Operations Lead',    'health' => 61],
            ['key' => 'internal', 'name' => 'Internal Avatech',           'code' => 'INT', 'tier' => 'strategic', 'industry' => 'Internal Tooling',    'location' => 'Tangerang', 'pic' => 'Ahmad Arlisyah',  'pic_role' => 'SA/QA Lead',         'health' => 90],
            ['key' => 'gotech',   'name' => 'Go-Tech Avatech Initiative', 'code' => 'GTC', 'tier' => 'growth',    'industry' => 'AI Product Studio',   'location' => 'Tangerang', 'pic' => 'Joshua Raphael',  'pic_role' => 'Product Owner',      'health' => 80],
            ['key' => 'klinik',   'name' => 'Klinik Sehat Digital',       'code' => 'KSD', 'tier' => 'standard',  'industry' => 'Healthtech',          'location' => 'Depok',     'pic' => 'dr. Nadia',       'pic_role' => 'Clinic Manager',     'health' => 66],
            ['key' => 'properti', 'name' => 'Properti Maju Bersama',      'code' => 'PMB', 'tier' => 'growth',    'industry' => 'Property Listing',    'location' => 'Bekasi',    'pic' => 'Bapak Hartono',   'pic_role' => 'Marketing Head',     'health' => 72],
            ['key' => 'nusantara','name' => 'Nusantara Digital Partner',  'code' => 'NDP', 'tier' => 'standard',  'industry' => 'Maintenance Retainer','location' => 'Jakarta',   'pic' => 'Ibu Vania',       'pic_role' => 'Account Manager',    'health' => 70],
        ];

        $clients = [];
        foreach ($rows as $row) {
            $email = Str::slug($row['code']) . '@demo.avatech.local';
            $clients[$row['key']] = Client::updateOrCreate(
                ['email' => $email],
                [
                    'name'                => $row['name'],
                    'code'                => $row['code'],
                    'tier'                => $row['tier'],
                    'industry'            => $row['industry'],
                    'location'            => $row['location'],
                    'pic_name'            => $row['pic'],
                    'pic_role'            => $row['pic_role'],
                    'phone'               => '+62 800-0000-0000', // safe dummy, not a real number
                    'description'         => 'Klien demo internal Avatech (data dummy aman untuk presentasi produk).',
                    'total_engagement'    => random_int(1, 6),
                    'relationship_health' => $row['health'],
                    'last_touch_label'    => random_int(1, 9) . ' hari',
                    'archived_at'         => null,
                ],
            );
        }

        return $clients;
    }

    /* ===================================================================
     |  PORTFOLIO BLUEPRINT
     * =================================================================== */

    /**
     * Each project blueprint is fully declarative. Statuses are chosen to stay
     * consistent with phase locks:
     *   - Design-phase projects keep every NON-design task at "planned".
     *   - Development/QC projects mark the design handover task "done".
     *   - QC test cases only appear on Development/QC/Done phases.
     *
     * @return array<int, array<string, mixed>>
     */
    private function portfolio(): array
    {
        return [
            // 1 — Company Profile (Development, requires_design)
            [
                'code' => 'DM01', 'client' => 'ava', 'lead' => 'joshua',
                'name' => 'Avatech Company Profile Website',
                'desc' => 'Company profile resmi Avatech: profil perusahaan, layanan, portfolio, dan form inquiry.',
                'phase' => 'Development', 'requires_design' => true, 'status' => 'on-track', 'color' => '#7C3AED',
                'due' => '2026-07-18', 'featured' => true,
                'modules' => [
                    ['title' => 'UI/UX Design', 'status' => 'approved', 'hours' => 14, 'desc' => 'Final mockup UI/UX dan handover desain sebelum development.', 'tasks' => [
                        ['title' => 'Membuat final mockup UI/UX dan handover desain', 'status' => 'done', 'priority' => 'high', 'hours' => 14, 'assignee' => 'yuda', 'design' => true],
                    ]],
                    ['title' => 'Homepage & Company Profile Pages', 'status' => 'approved', 'hours' => 20, 'desc' => 'Halaman utama, tentang kami, dan profil perusahaan.', 'tasks' => [
                        ['title' => 'Implementasi homepage dan hero section', 'status' => 'done', 'priority' => 'high', 'hours' => 10, 'assignee' => 'ferry'],
                        ['title' => 'Halaman About & sejarah perusahaan', 'status' => 'in_progress', 'priority' => 'medium', 'hours' => 6, 'assignee' => 'ferry'],
                    ]],
                    ['title' => 'Services & Portfolio CMS', 'status' => 'waiting_dev', 'hours' => 18, 'desc' => 'CMS untuk layanan dan portfolio proyek.', 'tasks' => [
                        ['title' => 'CRUD layanan dan kategori', 'status' => 'in_progress', 'priority' => 'high', 'hours' => 10, 'assignee' => 'ferry'],
                        ['title' => 'Galeri portfolio dengan filter', 'status' => 'planned', 'priority' => 'medium', 'hours' => 8, 'assignee' => 'irwan'],
                    ]],
                    ['title' => 'Contact Form & Inquiry Management', 'status' => 'waiting_dev', 'hours' => 12, 'desc' => 'Form kontak dan manajemen inquiry masuk.', 'tasks' => [
                        ['title' => 'Form inquiry dan notifikasi email', 'status' => 'planned', 'priority' => 'medium', 'hours' => 8, 'assignee' => 'irwan'],
                    ]],
                    ['title' => 'SEO Basic & Deployment', 'status' => 'waiting_dev', 'hours' => 8, 'desc' => 'Optimasi SEO dasar dan deployment produksi.', 'tasks' => [
                        ['title' => 'Setup meta SEO dan sitemap', 'status' => 'planned', 'priority' => 'low', 'hours' => 4, 'assignee' => 'ferry'],
                    ]],
                ],
                'deliverables' => [
                    ['title' => 'Master Figma Company Profile', 'figma' => 'https://www.figma.com/proto/demo-avatech-company-profile', 'pdf' => true],
                    ['title' => 'Home Page Preview', 'figma' => 'https://www.figma.com/file/demo-avatech-home-preview', 'pdf' => true],
                    ['title' => 'Mobile Responsive Preview', 'figma' => 'https://www.figma.com/file/demo-avatech-mobile-responsive', 'pdf' => false],
                ],
                'team' => [
                    ['user' => 'adly',  'resp' => ['saqa_mom_qc', 'copywriting_support'], 'title' => 'SA/QA, MoM/QC & copywriting konten profil', 'type' => 'review', 'status' => 'in_progress', 'hours' => 14],
                    ['user' => 'yuda',  'resp' => ['uiux_design'],   'title' => 'Final mockup & handover desain company profile', 'type' => 'task', 'status' => 'done', 'hours' => 14],
                    ['user' => 'ferry', 'resp' => ['fullstack_dev'], 'title' => 'Implementasi homepage & CMS layanan', 'type' => 'task', 'status' => 'in_progress', 'hours' => 26],
                ],
                'moms' => [
                    ['date' => '2026-06-02', 'status' => 'ai_fixed'],
                    ['date' => '2026-06-09', 'status' => 'draft'],
                ],
                'qc' => [
                    ['module' => 'Homepage & Company Profile Pages', 'title' => 'Homepage tampil benar di desktop & mobile', 'status' => 'passed', 'priority' => 'high'],
                    ['module' => 'Services & Portfolio CMS', 'title' => 'Tambah layanan baru muncul di halaman publik', 'status' => 'pending', 'priority' => 'medium'],
                    ['module' => 'Contact Form & Inquiry Management', 'title' => 'Form inquiry menolak email tidak valid', 'status' => 'pending', 'priority' => 'medium'],
                ],
            ],

            // 2 — Aloka Donation (Design, requires_design)
            [
                'code' => 'DM02', 'client' => 'aloka', 'lead' => 'joshua',
                'name' => 'Aloka Donation Platform',
                'desc' => 'Platform donasi: katalog program, pembayaran, dashboard donatur, dan pelaporan keuangan.',
                'phase' => 'Design', 'requires_design' => true, 'status' => 'on-track', 'color' => '#0EA5E9',
                'due' => '2026-08-15', 'featured' => false,
                'modules' => [
                    ['title' => 'UI/UX Design', 'status' => 'pending_design', 'hours' => 16, 'desc' => 'Final mockup UI/UX dan handover desain platform donasi.', 'tasks' => [
                        ['title' => 'Membuat final mockup UI/UX dan handover desain', 'status' => 'in_progress', 'priority' => 'high', 'hours' => 16, 'assignee' => 'yuda', 'design' => true],
                    ]],
                    ['title' => 'Donation Program Catalog', 'status' => 'pending_design', 'hours' => 18, 'desc' => 'Katalog program donasi dan detail kampanye.', 'tasks' => [
                        ['title' => 'Skema data program donasi', 'status' => 'planned', 'priority' => 'high', 'hours' => 10, 'assignee' => 'ferry'],
                    ]],
                    ['title' => 'Payment Gateway & Manual Verification', 'status' => 'pending_design', 'hours' => 22, 'desc' => 'Integrasi payment gateway dan verifikasi transfer manual.', 'tasks' => [
                        ['title' => 'Rancang alur verifikasi pembayaran manual', 'status' => 'planned', 'priority' => 'high', 'hours' => 12, 'assignee' => 'ferry'],
                    ]],
                    ['title' => 'Donor Dashboard / Track My Donation', 'status' => 'pending_design', 'hours' => 14, 'desc' => 'Dashboard donatur dan pelacakan donasi.', 'tasks' => [
                        ['title' => 'Wireframe dashboard donatur', 'status' => 'planned', 'priority' => 'medium', 'hours' => 8, 'assignee' => 'irwan'],
                    ]],
                    ['title' => 'Invoice & Email Notification', 'status' => 'pending_design', 'hours' => 10, 'desc' => 'Invoice donasi dan notifikasi email otomatis.', 'tasks' => [
                        ['title' => 'Template invoice & email notifikasi', 'status' => 'planned', 'priority' => 'medium', 'hours' => 6, 'assignee' => 'irwan'],
                    ]],
                    ['title' => 'Admin Program Management', 'status' => 'pending_design', 'hours' => 12, 'desc' => 'Manajemen program oleh admin yayasan.', 'tasks' => [
                        ['title' => 'CRUD program & status kampanye', 'status' => 'planned', 'priority' => 'medium', 'hours' => 8, 'assignee' => 'ferry'],
                    ]],
                    ['title' => 'Financial Reporting Integration', 'status' => 'pending_design', 'hours' => 14, 'desc' => 'Placeholder integrasi pelaporan keuangan (Accurate).', 'tasks' => [
                        ['title' => 'Riset integrasi pelaporan keuangan', 'status' => 'planned', 'priority' => 'low', 'hours' => 6, 'assignee' => 'achmad'],
                    ]],
                ],
                'deliverables' => [
                    ['title' => 'Master Figma Donation Platform', 'figma' => 'https://www.figma.com/file/demo-aloka-donation-platform', 'pdf' => false],
                    ['title' => 'Donation Flow Prototype', 'figma' => 'https://www.figma.com/proto/demo-aloka-donation-flow', 'pdf' => false],
                ],
                'team' => [
                    ['user' => 'adly',  'resp' => ['saqa_mom_qc'],   'title' => 'SA/QA & MoM/QC platform donasi', 'type' => 'review', 'status' => 'planned', 'hours' => 12],
                    ['user' => 'yuda',  'resp' => ['uiux_design'],   'title' => 'Desain end-to-end alur donasi', 'type' => 'task', 'status' => 'in_progress', 'hours' => 16],
                    ['user' => 'ferry', 'resp' => ['fullstack_dev'], 'title' => 'Persiapan arsitektur payment & katalog', 'type' => 'task', 'status' => 'planned', 'hours' => 22],
                    ['user' => 'genta', 'resp' => ['copywriting_support'], 'title' => 'Copywriting kampanye & microcopy donasi', 'type' => 'support', 'status' => 'in_progress', 'hours' => 8],
                ],
                'moms' => [
                    ['date' => '2026-06-04', 'status' => 'ai_fixed'],
                ],
                'qc' => [], // Design phase → no QC yet (respects QC gating)
            ],

            // 3 — Stullo LMS (QC, requires_design)
            [
                'code' => 'DM03', 'client' => 'stullo', 'lead' => 'joshua',
                'name' => 'Stullo Learning Marketplace',
                'desc' => 'LMS / marketplace pembelajaran: katalog kursus, sertifikat, dashboard mitra & learner.',
                'phase' => 'QC', 'requires_design' => true, 'status' => 'attention', 'color' => '#F59E0B',
                'due' => '2026-06-27', 'featured' => true,
                'modules' => [
                    ['title' => 'UI/UX Design', 'status' => 'approved', 'hours' => 18, 'desc' => 'Final mockup UI/UX dan handover desain LMS.', 'tasks' => [
                        ['title' => 'Membuat final mockup UI/UX dan handover desain', 'status' => 'done', 'priority' => 'high', 'hours' => 18, 'assignee' => 'yuda', 'design' => true],
                    ]],
                    ['title' => 'Public Landing Page', 'status' => 'approved', 'hours' => 12, 'desc' => 'Landing page publik marketplace.', 'tasks' => [
                        ['title' => 'Implementasi landing page & hero', 'status' => 'done', 'priority' => 'high', 'hours' => 8, 'assignee' => 'ferry'],
                    ]],
                    ['title' => 'Course/Product Catalog', 'status' => 'approved', 'hours' => 20, 'desc' => 'Katalog kursus dan produk pembelajaran.', 'tasks' => [
                        ['title' => 'Listing kursus & pencarian', 'status' => 'done', 'priority' => 'high', 'hours' => 12, 'assignee' => 'ferry'],
                    ]],
                    ['title' => 'Favorites & Wishlist', 'status' => 'approved', 'hours' => 8, 'desc' => 'Simpan kursus favorit / wishlist.', 'tasks' => [
                        ['title' => 'Fitur wishlist learner', 'status' => 'done', 'priority' => 'medium', 'hours' => 6, 'assignee' => 'irwan'],
                    ]],
                    ['title' => 'Certificate Management', 'status' => 'approved', 'hours' => 12, 'desc' => 'Penerbitan & verifikasi sertifikat.', 'tasks' => [
                        ['title' => 'Generate sertifikat penyelesaian', 'status' => 'review', 'priority' => 'high', 'hours' => 8, 'assignee' => 'ferry'],
                    ]],
                    ['title' => 'Partner/Mitra Dashboard', 'status' => 'approved', 'hours' => 14, 'desc' => 'Dashboard mitra penyedia kursus.', 'tasks' => [
                        ['title' => 'Dashboard performa mitra', 'status' => 'done', 'priority' => 'medium', 'hours' => 10, 'assignee' => 'irwan'],
                    ]],
                    ['title' => 'Finance & CS Dashboard', 'status' => 'approved', 'hours' => 12, 'desc' => 'Dashboard finance dan customer support.', 'tasks' => [
                        ['title' => 'Ringkasan transaksi & tiket CS', 'status' => 'in_progress', 'priority' => 'medium', 'hours' => 8, 'assignee' => 'achmad'],
                    ]],
                    ['title' => 'Learner Dashboard', 'status' => 'approved', 'hours' => 10, 'desc' => 'Dashboard progres belajar learner.', 'tasks' => [
                        ['title' => 'Progress tracking learner', 'status' => 'done', 'priority' => 'medium', 'hours' => 8, 'assignee' => 'ferry'],
                    ]],
                ],
                'deliverables' => [
                    ['title' => 'Master Figma Stullo LMS', 'figma' => 'https://www.figma.com/file/demo-stullo-lms', 'pdf' => true],
                ],
                'team' => [
                    ['user' => 'adly',  'resp' => ['saqa_mom_qc'],   'title' => 'SA/QA & MoM/QC marketplace LMS', 'type' => 'review', 'status' => 'in_progress', 'hours' => 18],
                    ['user' => 'yuda',  'resp' => ['uiux_design'],   'title' => 'Desain & handover LMS', 'type' => 'task', 'status' => 'done', 'hours' => 18],
                    ['user' => 'ferry', 'resp' => ['fullstack_dev'], 'title' => 'Implementasi katalog & sertifikat', 'type' => 'task', 'status' => 'in_progress', 'hours' => 28],
                    ['user' => 'genta', 'resp' => ['copywriting_support'], 'title' => 'Copywriting deskripsi kursus', 'type' => 'support', 'status' => 'done', 'hours' => 6],
                ],
                'moms' => [
                    ['date' => '2026-05-20', 'status' => 'ai_fixed'],
                    ['date' => '2026-06-06', 'status' => 'manual_updated'],
                ],
                'qc' => [
                    ['module' => 'Public Landing Page', 'title' => 'Landing page memuat di bawah 3 detik', 'status' => 'passed', 'priority' => 'medium'],
                    ['module' => 'Course/Product Catalog', 'title' => 'Pencarian kursus mengembalikan hasil relevan', 'status' => 'passed', 'priority' => 'high'],
                    ['module' => 'Favorites & Wishlist', 'title' => 'Wishlist tersimpan setelah relogin', 'status' => 'passed', 'priority' => 'medium'],
                    ['module' => 'Certificate Management', 'title' => 'Sertifikat ter-generate dengan nama benar', 'status' => 'failed', 'priority' => 'high'],
                    ['module' => 'Certificate Management', 'title' => 'Verifikasi sertifikat via kode publik', 'status' => 'retest', 'priority' => 'high'],
                    ['module' => 'Finance & CS Dashboard', 'title' => 'Ringkasan transaksi cocok dengan data', 'status' => 'pending', 'priority' => 'medium'],
                    ['module' => 'Learner Dashboard', 'title' => 'Progress belajar update setelah selesai modul', 'status' => 'passed', 'priority' => 'medium'],
                ],
            ],

            // 4 — Artisan CMS Migration (Development, no design)
            [
                'code' => 'DM04', 'client' => 'artisan', 'lead' => 'achmad',
                'name' => 'B2B Artisan CMS Migration',
                'desc' => 'Migrasi CMS / WordPress: hosting, konten, kompatibilitas plugin, dan UAT akhir.',
                'phase' => 'Development', 'requires_design' => false, 'status' => 'on-track', 'color' => '#10B981',
                'due' => '2026-07-04', 'featured' => false,
                'modules' => [
                    ['title' => 'Hosting & Domain Migration', 'status' => 'approved', 'hours' => 10, 'desc' => 'Migrasi hosting dan domain.', 'tasks' => [
                        ['title' => 'Migrasi hosting & pointing domain', 'status' => 'done', 'priority' => 'high', 'hours' => 6, 'assignee' => 'irwan'],
                    ]],
                    ['title' => 'CMS Setup', 'status' => 'approved', 'hours' => 12, 'desc' => 'Setup CMS dan konfigurasi awal.', 'tasks' => [
                        ['title' => 'Instalasi & konfigurasi WordPress', 'status' => 'done', 'priority' => 'high', 'hours' => 6, 'assignee' => 'irwan'],
                    ]],
                    ['title' => 'Content Migration', 'status' => 'waiting_dev', 'hours' => 16, 'desc' => 'Migrasi konten lama ke CMS baru.', 'tasks' => [
                        ['title' => 'Migrasi artikel & media', 'status' => 'in_progress', 'priority' => 'medium', 'hours' => 10, 'assignee' => 'irwan'],
                    ]],
                    ['title' => 'Plugin/Theme Compatibility Check', 'status' => 'waiting_dev', 'hours' => 10, 'desc' => 'Cek kompatibilitas plugin & theme.', 'tasks' => [
                        ['title' => 'Audit plugin & resolusi konflik', 'status' => 'in_progress', 'priority' => 'high', 'hours' => 8, 'assignee' => 'achmad'],
                    ]],
                    ['title' => 'Final UAT', 'status' => 'waiting_dev', 'hours' => 8, 'desc' => 'UAT akhir sebelum go-live.', 'tasks' => [
                        ['title' => 'Skenario UAT konten & navigasi', 'status' => 'planned', 'priority' => 'medium', 'hours' => 6, 'assignee' => 'adly'],
                    ]],
                ],
                'deliverables' => [],
                'team' => [
                    ['user' => 'irwan',  'resp' => ['wordpress_support', 'fullstack_dev'], 'title' => 'WordPress migration & content', 'type' => 'task', 'status' => 'in_progress', 'hours' => 22],
                    ['user' => 'achmad', 'resp' => ['fullstack_dev'],     'title' => 'Technical advisor kompatibilitas', 'type' => 'support', 'status' => 'in_progress', 'hours' => 8],
                    ['user' => 'adly',   'resp' => ['saqa_mom_qc'],       'title' => 'SA/QA & MoM/QC migrasi', 'type' => 'review', 'status' => 'planned', 'hours' => 6],
                ],
                'moms' => [
                    ['date' => '2026-06-01', 'status' => 'draft'],
                ],
                'qc' => [
                    ['module' => 'Content Migration', 'title' => 'Seluruh artikel lama tampil di CMS baru', 'status' => 'pending', 'priority' => 'high'],
                    ['module' => 'Plugin/Theme Compatibility Check', 'title' => 'Tidak ada error fatal pada plugin aktif', 'status' => 'pending', 'priority' => 'high'],
                ],
            ],

            // 5 — Smart-PMIS Internal (QC ~ done, no design)
            [
                'code' => 'DM05', 'client' => 'internal', 'lead' => 'joshua',
                'name' => 'Smart-PMIS Internal System',
                'desc' => 'Sistem manajemen proyek internal Avatech: Project Master, AI MoM/WBS, Kanban, QC, Monitor.',
                'phase' => 'QC', 'requires_design' => false, 'status' => 'on-track', 'color' => '#7C3AED',
                'due' => '2026-06-20', 'featured' => true,
                'modules' => [
                    ['title' => 'Project Master', 'status' => 'approved', 'hours' => 16, 'desc' => 'Master data proyek dan detail.', 'tasks' => [
                        ['title' => 'CRUD proyek & detail workspace', 'status' => 'done', 'priority' => 'high', 'hours' => 12, 'assignee' => 'ferry'],
                    ]],
                    ['title' => 'AI MoM Fixer', 'status' => 'approved', 'hours' => 12, 'desc' => 'Perapihan MoM dengan AI (HITL).', 'tasks' => [
                        ['title' => 'Preview-before-save MoM fixer', 'status' => 'done', 'priority' => 'high', 'hours' => 10, 'assignee' => 'ferry'],
                    ]],
                    ['title' => 'AI WBS Generator', 'status' => 'approved', 'hours' => 14, 'desc' => 'Generate WBS dari MoM (HITL).', 'tasks' => [
                        ['title' => 'Generator WBS draf editable', 'status' => 'done', 'priority' => 'high', 'hours' => 12, 'assignee' => 'achmad'],
                    ]],
                    ['title' => 'Kanban Workspace', 'status' => 'approved', 'hours' => 10, 'desc' => 'Papan Kanban task per proyek.', 'tasks' => [
                        ['title' => 'Board Kanban & update status', 'status' => 'done', 'priority' => 'medium', 'hours' => 8, 'assignee' => 'ferry'],
                    ]],
                    ['title' => 'Quality Control', 'status' => 'approved', 'hours' => 12, 'desc' => 'Manajemen test case dan QC.', 'tasks' => [
                        ['title' => 'CRUD test case & status QC', 'status' => 'done', 'priority' => 'high', 'hours' => 10, 'assignee' => 'adly'],
                    ]],
                    ['title' => 'AI Monitor & Audit Trail', 'status' => 'approved', 'hours' => 10, 'desc' => 'Monitor metadata AI dan jejak audit.', 'tasks' => [
                        ['title' => 'Audit trail & AI monitor metadata-only', 'status' => 'done', 'priority' => 'medium', 'hours' => 8, 'assignee' => 'achmad'],
                    ]],
                ],
                'deliverables' => [],
                'team' => [
                    ['user' => 'adly',   'resp' => ['saqa_mom_qc'],   'title' => 'SA/QA & MoM/QC sistem internal', 'type' => 'review', 'status' => 'done', 'hours' => 16],
                    ['user' => 'ferry',  'resp' => ['fullstack_dev'], 'title' => 'Core Project Master & Kanban', 'type' => 'task', 'status' => 'done', 'hours' => 30],
                    ['user' => 'achmad', 'resp' => ['fullstack_dev'], 'title' => 'AI integration & technical advisory', 'type' => 'task', 'status' => 'done', 'hours' => 18],
                ],
                'moms' => [
                    ['date' => '2026-05-12', 'status' => 'ai_fixed'],
                    ['date' => '2026-05-26', 'status' => 'manual_updated'],
                ],
                'qc' => [
                    ['module' => 'Project Master', 'title' => 'Hak akses CEO/PM read-only di Project Detail', 'status' => 'passed', 'priority' => 'high'],
                    ['module' => 'AI MoM Fixer', 'title' => 'MoM fixer menampilkan preview sebelum simpan', 'status' => 'passed', 'priority' => 'high'],
                    ['module' => 'AI WBS Generator', 'title' => 'WBS draf bisa dipilih per item sebelum simpan', 'status' => 'passed', 'priority' => 'high'],
                    ['module' => 'Quality Control', 'title' => 'Metrik QC passed/total konsisten', 'status' => 'passed', 'priority' => 'medium'],
                    ['module' => 'AI Monitor & Audit Trail', 'title' => 'AI Monitor hanya menyimpan metadata', 'status' => 'passed', 'priority' => 'high'],
                ],
            ],

            // 6 — Go-Tech AI Factory (Design, requires_design)
            [
                'code' => 'DM06', 'client' => 'gotech', 'lead' => 'joshua',
                'name' => 'Go-Tech AI Product Factory',
                'desc' => 'AI-native product factory: kumpulan clickable prototype produk demo untuk pitch.',
                'phase' => 'Design', 'requires_design' => true, 'status' => 'on-track', 'color' => '#EC4899',
                'due' => '2026-08-30', 'featured' => true,
                'modules' => [
                    ['title' => 'UI/UX Design', 'status' => 'pending_design', 'hours' => 24, 'desc' => 'Final mockup UI/UX dan handover desain seluruh prototype.', 'tasks' => [
                        ['title' => 'Membuat final mockup UI/UX dan handover desain', 'status' => 'in_progress', 'priority' => 'high', 'hours' => 24, 'assignee' => 'yuda', 'design' => true],
                    ]],
                    ['title' => 'Accounting Software Demo', 'status' => 'pending_design', 'hours' => 12, 'desc' => 'Prototype software akuntansi.', 'tasks' => [
                        ['title' => 'Clickable prototype accounting', 'status' => 'planned', 'priority' => 'medium', 'hours' => 8, 'assignee' => 'yuda'],
                    ]],
                    ['title' => 'Property Listing Web Demo', 'status' => 'pending_design', 'hours' => 10, 'desc' => 'Prototype property listing.', 'tasks' => [
                        ['title' => 'Clickable prototype property', 'status' => 'planned', 'priority' => 'medium', 'hours' => 8, 'assignee' => 'yuda'],
                    ]],
                    ['title' => 'Job Poster Demo', 'status' => 'pending_design', 'hours' => 8, 'desc' => 'Prototype job poster.', 'tasks' => [
                        ['title' => 'Clickable prototype job poster', 'status' => 'planned', 'priority' => 'low', 'hours' => 6, 'assignee' => 'yuda'],
                    ]],
                    ['title' => 'Medical Booking Demo', 'status' => 'pending_design', 'hours' => 10, 'desc' => 'Prototype medical booking.', 'tasks' => [
                        ['title' => 'Clickable prototype medical booking', 'status' => 'planned', 'priority' => 'medium', 'hours' => 8, 'assignee' => 'yuda'],
                    ]],
                    ['title' => 'SaaS Dashboard Demo', 'status' => 'pending_design', 'hours' => 12, 'desc' => 'Prototype dashboard SaaS.', 'tasks' => [
                        ['title' => 'Clickable prototype SaaS dashboard', 'status' => 'planned', 'priority' => 'medium', 'hours' => 8, 'assignee' => 'yuda'],
                    ]],
                    ['title' => 'Company Profile Deck/Video Planning', 'status' => 'pending_design', 'hours' => 8, 'desc' => 'Perencanaan aset deck & video company profile.', 'tasks' => [
                        ['title' => 'Susun outline deck & aset video', 'status' => 'planned', 'priority' => 'low', 'hours' => 6, 'assignee' => 'genta'],
                    ]],
                ],
                'deliverables' => [
                    ['title' => 'Accounting Prototype', 'figma' => 'https://www.figma.com/proto/demo-gotech-accounting', 'pdf' => true],
                    ['title' => 'Property Listing Prototype', 'figma' => 'https://www.figma.com/proto/demo-gotech-property', 'pdf' => false],
                    ['title' => 'Medical Booking Prototype', 'figma' => 'https://www.figma.com/proto/demo-gotech-medical', 'pdf' => false],
                    ['title' => 'SaaS Dashboard Prototype', 'figma' => 'https://www.figma.com/proto/demo-gotech-saas', 'pdf' => false],
                ],
                'team' => [
                    ['user' => 'joshua', 'resp' => [],                'title' => 'Product owner & arah produk', 'type' => 'review', 'status' => 'in_progress', 'hours' => 6],
                    ['user' => 'adly',   'resp' => ['saqa_mom_qc'],   'title' => 'SA/QA & MoM/QC prototype factory', 'type' => 'review', 'status' => 'planned', 'hours' => 10],
                    ['user' => 'yuda',   'resp' => ['uiux_design'],   'title' => 'Desain seluruh clickable prototype', 'type' => 'task', 'status' => 'in_progress', 'hours' => 24],
                    ['user' => 'genta',  'resp' => ['copywriting_support'], 'title' => 'Copywriting deck & narasi produk', 'type' => 'support', 'status' => 'in_progress', 'hours' => 8],
                ],
                'moms' => [
                    ['date' => '2026-06-07', 'status' => 'ai_fixed'],
                ],
                'qc' => [],
            ],

            // 7 — Medical Booking Prototype (Gathering, light)
            [
                'code' => 'DM07', 'client' => 'klinik', 'lead' => 'joshua',
                'name' => 'Medical Booking Prototype',
                'desc' => 'Prototype booking pasien, jadwal dokter, dan dashboard admin appointment.',
                'phase' => 'Gathering', 'requires_design' => true, 'status' => 'on-track', 'color' => '#06B6D4',
                'due' => '2026-09-10', 'featured' => false,
                'modules' => [
                    ['title' => 'Patient Booking Flow', 'status' => 'pending_design', 'hours' => 10, 'desc' => 'Alur booking pasien.', 'tasks' => [
                        ['title' => 'Petakan kebutuhan alur booking pasien', 'status' => 'planned', 'priority' => 'medium', 'hours' => 6, 'assignee' => 'adly'],
                    ]],
                    ['title' => 'Doctor Schedule Management', 'status' => 'pending_design', 'hours' => 8, 'desc' => 'Manajemen jadwal dokter.', 'tasks' => [
                        ['title' => 'Kumpulkan kebutuhan jadwal dokter', 'status' => 'planned', 'priority' => 'medium', 'hours' => 5, 'assignee' => 'adly'],
                    ]],
                    ['title' => 'Admin Appointment Dashboard', 'status' => 'pending_design', 'hours' => 8, 'desc' => 'Dashboard admin appointment.', 'tasks' => [
                        ['title' => 'Sketsa kebutuhan dashboard admin', 'status' => 'planned', 'priority' => 'low', 'hours' => 5, 'assignee' => 'yuda'],
                    ]],
                    ['title' => 'WhatsApp Reminder Draft', 'status' => 'pending_design', 'hours' => 6, 'desc' => 'Draft reminder WhatsApp (tanpa auto-send).', 'tasks' => [
                        ['title' => 'Susun template draft reminder WhatsApp', 'status' => 'planned', 'priority' => 'low', 'hours' => 4, 'assignee' => 'genta'],
                    ]],
                ],
                'deliverables' => [],
                'team' => [
                    ['user' => 'adly', 'resp' => ['saqa_mom_qc'], 'title' => 'Requirement gathering & MoM awal', 'type' => 'review', 'status' => 'in_progress', 'hours' => 8],
                    ['user' => 'yuda', 'resp' => ['uiux_design'], 'title' => 'Riset UX awal booking klinik', 'type' => 'task', 'status' => 'planned', 'hours' => 6],
                ],
                'moms' => [
                    ['date' => '2026-06-11', 'status' => 'draft'],
                ],
                'qc' => [],
            ],

            // 8 — Property Listing (Development, requires_design)
            [
                'code' => 'DM08', 'client' => 'properti', 'lead' => 'joshua',
                'name' => 'Property Listing Website',
                'desc' => 'Website listing properti: katalog, detail properti, form agen, dan admin listing.',
                'phase' => 'Development', 'requires_design' => true, 'status' => 'on-track', 'color' => '#F97316',
                'due' => '2026-07-22', 'featured' => false,
                'modules' => [
                    ['title' => 'UI/UX Design', 'status' => 'approved', 'hours' => 14, 'desc' => 'Final mockup UI/UX dan handover desain listing.', 'tasks' => [
                        ['title' => 'Membuat final mockup UI/UX dan handover desain', 'status' => 'done', 'priority' => 'high', 'hours' => 14, 'assignee' => 'yuda', 'design' => true],
                    ]],
                    ['title' => 'Property Listing Catalog', 'status' => 'approved', 'hours' => 18, 'desc' => 'Katalog listing properti dengan filter.', 'tasks' => [
                        ['title' => 'Listing properti & filter lokasi/harga', 'status' => 'in_progress', 'priority' => 'high', 'hours' => 12, 'assignee' => 'ferry'],
                    ]],
                    ['title' => 'Detail Property Page', 'status' => 'waiting_dev', 'hours' => 12, 'desc' => 'Halaman detail properti.', 'tasks' => [
                        ['title' => 'Halaman detail & galeri foto', 'status' => 'in_progress', 'priority' => 'medium', 'hours' => 8, 'assignee' => 'ferry'],
                    ]],
                    ['title' => 'Agent Contact Form', 'status' => 'waiting_dev', 'hours' => 8, 'desc' => 'Form kontak agen properti.', 'tasks' => [
                        ['title' => 'Form kontak agen & lead capture', 'status' => 'planned', 'priority' => 'medium', 'hours' => 6, 'assignee' => 'irwan'],
                    ]],
                    ['title' => 'Admin Listing Management', 'status' => 'waiting_dev', 'hours' => 12, 'desc' => 'Manajemen listing oleh admin.', 'tasks' => [
                        ['title' => 'CRUD listing & moderasi', 'status' => 'planned', 'priority' => 'medium', 'hours' => 8, 'assignee' => 'ferry'],
                    ]],
                    ['title' => 'Basic SEO', 'status' => 'waiting_dev', 'hours' => 6, 'desc' => 'SEO dasar listing.', 'tasks' => [
                        ['title' => 'Meta SEO per listing', 'status' => 'planned', 'priority' => 'low', 'hours' => 4, 'assignee' => 'ferry'],
                    ]],
                ],
                'deliverables' => [
                    ['title' => 'Master Figma Property Listing', 'figma' => 'https://www.figma.com/file/demo-property-listing', 'pdf' => true],
                ],
                'team' => [
                    ['user' => 'yuda',  'resp' => ['uiux_design'],   'title' => 'Desain & handover listing properti', 'type' => 'task', 'status' => 'done', 'hours' => 14],
                    ['user' => 'ferry', 'resp' => ['fullstack_dev'], 'title' => 'Implementasi katalog & detail properti', 'type' => 'task', 'status' => 'in_progress', 'hours' => 28],
                    ['user' => 'adly',  'resp' => ['saqa_mom_qc'],   'title' => 'SA/QA & MoM/QC listing properti', 'type' => 'review', 'status' => 'planned', 'hours' => 10],
                ],
                'moms' => [
                    ['date' => '2026-06-03', 'status' => 'ai_fixed'],
                ],
                'qc' => [
                    ['module' => 'Property Listing Catalog', 'title' => 'Filter harga & lokasi mengembalikan listing benar', 'status' => 'pending', 'priority' => 'high'],
                    ['module' => 'Detail Property Page', 'title' => 'Galeri foto properti tampil responsif', 'status' => 'passed', 'priority' => 'medium'],
                ],
            ],

            // 9 — Maintenance Retainer (Development, no design)
            [
                'code' => 'DM09', 'client' => 'nusantara', 'lead' => 'achmad',
                'name' => 'Corporate Website Maintenance Retainer',
                'desc' => 'Retainer maintenance: bug fixing, update konten bulanan, backup & security, monitoring.',
                'phase' => 'Development', 'requires_design' => false, 'status' => 'on-track', 'color' => '#64748B',
                'due' => '2026-12-31', 'featured' => false,
                'modules' => [
                    ['title' => 'Bug Fixing', 'status' => 'approved', 'hours' => 10, 'desc' => 'Penanganan bug berkala.', 'tasks' => [
                        ['title' => 'Penanganan bug prioritas bulan ini', 'status' => 'in_progress', 'priority' => 'high', 'hours' => 8, 'assignee' => 'irwan'],
                    ]],
                    ['title' => 'Monthly Content Update', 'status' => 'approved', 'hours' => 8, 'desc' => 'Update konten rutin bulanan.', 'tasks' => [
                        ['title' => 'Update konten & banner bulanan', 'status' => 'done', 'priority' => 'medium', 'hours' => 5, 'assignee' => 'genta'],
                    ]],
                    ['title' => 'Backup & Security Check', 'status' => 'approved', 'hours' => 6, 'desc' => 'Backup dan pemeriksaan keamanan.', 'tasks' => [
                        ['title' => 'Verifikasi backup & patch keamanan', 'status' => 'done', 'priority' => 'high', 'hours' => 5, 'assignee' => 'achmad'],
                    ]],
                    ['title' => 'Performance Monitoring', 'status' => 'approved', 'hours' => 6, 'desc' => 'Pemantauan performa situs.', 'tasks' => [
                        ['title' => 'Review metrik performa & uptime', 'status' => 'in_progress', 'priority' => 'medium', 'hours' => 4, 'assignee' => 'achmad'],
                    ]],
                ],
                'deliverables' => [],
                'team' => [
                    ['user' => 'irwan',  'resp' => ['wordpress_support', 'fullstack_dev'], 'title' => 'Bug fixing & maintenance rutin', 'type' => 'task', 'status' => 'in_progress', 'hours' => 12],
                    ['user' => 'achmad', 'resp' => ['fullstack_dev'],     'title' => 'Security check & performance monitoring', 'type' => 'support', 'status' => 'in_progress', 'hours' => 9],
                    ['user' => 'genta',  'resp' => ['copywriting_support'], 'title' => 'Update konten bulanan', 'type' => 'support', 'status' => 'done', 'hours' => 5],
                ],
                'moms' => [
                    ['date' => '2026-06-05', 'status' => 'draft'],
                ],
                'qc' => [
                    ['module' => 'Backup & Security Check', 'title' => 'Backup terbaru dapat di-restore', 'status' => 'passed', 'priority' => 'high'],
                    ['module' => 'Performance Monitoring', 'title' => 'Waktu muat halaman utama < 2.5 detik', 'status' => 'pending', 'priority' => 'medium'],
                ],
            ],
        ];
    }

    /* ===================================================================
     |  PER-PROJECT SEEDING
     * =================================================================== */

    /**
     * @param array<string, mixed>  $bp
     * @param array<string, User>   $users
     * @param array<string, Client> $clients
     */
    private function seedProject(array $bp, array $users, array $clients): void
    {
        $client = $clients[$bp['client']] ?? null;
        $lead = $users[$bp['lead']] ?? null;
        if (! $client) {
            $this->command?->warn("Skip project {$bp['code']}: client tidak tersedia.");

            return;
        }

        $hasModules = ! empty($bp['modules']);

        $project = Project::updateOrCreate(
            ['code' => $bp['code']],
            [
                'color'            => $bp['color'],
                'name'             => $bp['name'],
                'description'      => $bp['desc'],
                'client_id'        => $client->id,
                'lead_user_id'     => $lead?->id,
                'phase'            => $bp['phase'],
                'due_at'           => Carbon::parse($bp['due']),
                'progress'         => 0, // recalculated after tasks are seeded
                'status'           => $bp['status'],
                'ai_wbs_generated' => $hasModules,
                'requires_design'  => (bool) $bp['requires_design'],
                'is_featured'      => (bool) $bp['featured'],
                'archived_at'      => null,
            ],
        );

        $modules = $this->seedModules($project, $bp['modules'] ?? []);
        $tasks = $this->seedTasks($project, $modules, $bp['modules'] ?? [], $users);
        $this->seedDeliverables($project, $tasks, $bp['deliverables'] ?? [], $users);
        $this->seedTeam($project, $bp['team'] ?? [], $users);
        $this->seedMoms($project, $bp['moms'] ?? [], $lead);
        $this->seedQc($project, $modules, $tasks, $bp['qc'] ?? [], $users['adly'] ?? $lead);

        // keep stored progress in sync with hour-weighted "done" tasks
        $project->forceFill(['progress' => $this->computeProgress($project)])->save();

        $this->seedAudit($project, $bp, $lead ?? ($users['joshua'] ?? null));
        $this->seedAiMonitor($project, $client, $lead ?? ($users['joshua'] ?? null), $hasModules);
    }

    /**
     * @param array<int, array<string,mixed>> $rows
     * @return array<string, ProjectModule>
     */
    private function seedModules(Project $project, array $rows): array
    {
        $modules = [];
        foreach ($rows as $i => $row) {
            $modules[$row['title']] = ProjectModule::updateOrCreate(
                ['project_id' => $project->id, 'title' => $row['title']],
                [
                    'description'    => $row['desc'] ?? null,
                    'status'         => $row['status'],
                    'estimate_hours' => $row['hours'] ?? 0,
                    'sort_order'     => $i + 1,
                ],
            );
        }

        return $modules;
    }

    /**
     * @param array<string, ProjectModule>  $modules
     * @param array<int, array<string,mixed>> $rows
     * @param array<string, User>           $users
     * @return array<string, ProjectTask>
     */
    private function seedTasks(Project $project, array $modules, array $rows, array $users): array
    {
        $tasks = [];
        $sort = 0;
        foreach ($rows as $moduleRow) {
            $module = $modules[$moduleRow['title']] ?? null;
            foreach ($moduleRow['tasks'] ?? [] as $taskRow) {
                $sort++;
                $isDesign = ! empty($taskRow['design']);
                $assignee = $users[$taskRow['assignee']] ?? null;

                $task = ProjectTask::updateOrCreate(
                    ['project_id' => $project->id, 'title' => $taskRow['title']],
                    [
                        'project_module_id'     => $module?->id,
                        'assigned_to'           => $assignee?->id,
                        'description'           => 'Task demo Avatech (' . $project->code . ') untuk Kanban, progress, dan QC walkthrough.',
                        'status'                => $taskRow['status'],
                        'priority'              => $taskRow['priority'],
                        'due_date'              => $project->due_at,
                        'estimate_hours'        => $taskRow['hours'] ?? 0,
                        'sort_order'            => $sort,
                        'is_design_deliverable' => $isDesign,
                    ],
                );

                $tasks[$taskRow['title']] = $task;
            }
        }

        return $tasks;
    }

    /**
     * Repeatable design deliverables (project_task_design_deliverables) + the
     * single task-level deliverable_* fields, attached to the design handover
     * task. Small valid dummy PDFs are written to the public disk so Preview /
     * Download PDF can be exercised.
     *
     * @param array<string, ProjectTask>    $tasks
     * @param array<int, array<string,mixed>> $rows
     * @param array<string, User>           $users
     */
    private function seedDeliverables(Project $project, array $tasks, array $rows, array $users): void
    {
        if (empty($rows)) {
            return;
        }

        // the design handover task = the one flagged is_design_deliverable
        $designTask = collect($tasks)->first(fn (ProjectTask $task) => (bool) $task->is_design_deliverable);
        if (! $designTask) {
            return;
        }

        $creator = $designTask->assigned_to
            ? ($users['yuda'] ?? null)
            : null;

        $firstUrl = null;
        $firstPdf = null;

        foreach ($rows as $row) {
            $pdfPath = null;
            if (! empty($row['pdf'])) {
                $pdfPath = $this->writeDummyPdf($project, $row['title']);
            }

            ProjectTaskDesignDeliverable::updateOrCreate(
                ['project_task_id' => $designTask->id, 'title' => $row['title']],
                [
                    'figma_url'     => $row['figma'] ?? null,
                    'pdf_file_path' => $pdfPath,
                    'notes'         => 'Deliverable desain demo (link/PDF dummy aman).',
                    'submitted_at'  => AppTime::now()->copy()->subDays(random_int(1, 6)),
                    'created_by'    => $creator?->id,
                ],
            );

            $firstUrl ??= $row['figma'] ?? null;
            $firstPdf ??= $pdfPath;
        }

        // mirror the primary deliverable onto the task-level fields so the
        // "Isi link Figma dan/atau unggah PDF" handover proof is populated
        $project->loadMissing([]);
        $designTask->forceFill([
            'deliverable_url'          => $firstUrl,
            'deliverable_file_path'    => $firstPdf,
            'deliverable_type'         => ($firstUrl || $firstPdf) ? 'uiux_mockup' : null,
            'deliverable_submitted_at' => ($firstUrl || $firstPdf) ? AppTime::now()->copy()->subDays(2) : null,
        ])->save();
    }

    /**
     * @param array<int, array<string,mixed>> $rows
     * @param array<string, User>            $users
     */
    private function seedTeam(Project $project, array $rows, array $users): void
    {
        foreach ($rows as $row) {
            $user = $users[$row['user']] ?? null;
            if (! $user) {
                continue;
            }

            TeamAssignment::updateOrCreate(
                ['project_id' => $project->id, 'user_id' => $user->id, 'title' => $row['title']],
                [
                    'type'             => $row['type'],
                    'responsibilities' => $row['resp'] ?? [],
                    'status'           => $row['status'],
                    'estimated_hours'  => $row['hours'] ?? 0,
                    'due_date'         => $project->due_at,
                    'notes'            => 'Assignment demo untuk Team Management, Quick Assign multi-responsibility, dan Executive Team Load.',
                ],
            );
        }
    }

    /**
     * @param array<int, array<string,mixed>> $rows
     */
    private function seedMoms(Project $project, array $rows, ?User $lead): void
    {
        foreach ($rows as $row) {
            $notes = "MoM demo {$project->name} (" . $row['date'] . ").\n"
                . "- Klien menyampaikan kebutuhan utama dan prioritas fitur.\n"
                . "- Tim Avatech menyepakati scope MVP dan langkah berikutnya.\n"
                . "- Catatan risiko dan dependensi teknis dicatat untuk WBS.";

            $summary = null;
            if (in_array($row['status'], ['ai_fixed', 'manual_updated'], true)) {
                $summary = "Ringkasan MoM:\n"
                    . "- Scope MVP {$project->name} disepakati bersama klien.\n"
                    . "- Prioritas pada alur inti dan kebutuhan handover desain bila relevan.\n\n"
                    . "Action Items:\n"
                    . "- SA/QA menyiapkan skenario uji.\n"
                    . "- Developer melanjutkan implementasi modul prioritas.";
            }

            ProjectMom::updateOrCreate(
                ['project_id' => $project->id, 'meeting_date' => Carbon::parse($row['date'])],
                [
                    'created_by' => $lead?->id,
                    'notes'      => $notes,
                    'summary'    => $summary,
                    'status'     => $row['status'],
                ],
            );
        }
    }

    /**
     * QC test cases. Only seeded for blueprints whose phase supports QC
     * (Development / QC / Done) — early-phase projects pass an empty list, so
     * QC gating is never conceptually violated.
     *
     * @param array<string, ProjectModule>  $modules
     * @param array<string, ProjectTask>    $tasks
     * @param array<int, array<string,mixed>> $rows
     */
    private function seedQc(Project $project, array $modules, array $tasks, array $rows, ?User $creator): void
    {
        foreach ($rows as $row) {
            $module = $modules[$row['module']] ?? null;
            $tested = in_array($row['status'], ['passed', 'failed'], true);

            $actual = match ($row['status']) {
                'passed' => 'Hasil sesuai ekspektasi pada data demo.',
                'failed' => 'Hasil belum sesuai; perlu perbaikan dan retest.',
                'retest' => 'Menunggu retest setelah penyesuaian.',
                default  => null,
            };

            $qc = ProjectQcTest::updateOrCreate(
                ['project_id' => $project->id, 'title' => $row['title']],
                [
                    'project_module_id' => $module?->id,
                    'project_task_id'   => null,
                    'created_by'        => $creator?->id,
                    'scenario'          => 'Penguji menjalankan skenario "' . $row['title'] . '" pada lingkungan demo.',
                    'expected_result'   => 'Sistem berperilaku sesuai kebutuhan yang disepakati di MoM.',
                    'actual_result'     => $actual,
                    'status'            => $row['status'],
                    'priority'          => $row['priority'],
                    'notes'             => 'Test case demo untuk Quality Control & metrik QC passed/total.',
                ],
            );

            $qc->forceFill([
                'tested_at' => $tested ? AppTime::now()->copy()->subDays(random_int(1, 5)) : null,
            ])->save();
        }
    }

    /* ===================================================================
     |  AUDIT TRAIL + AI MONITOR (metadata only)
     * =================================================================== */

    /**
     * A compact, realistic audit timeline per project. Uses the same action
     * names the real controllers emit so Audit Trail tags & deep-links resolve.
     *
     * @param array<string,mixed> $bp
     */
    private function seedAudit(Project $project, array $bp, ?User $actor): void
    {
        if (! $actor) {
            return;
        }

        $now = AppTime::now();
        $rows = [];

        $rows[] = ['action' => 'created', 'module' => 'Project Master', 'desc' => 'Membuat proyek <strong>' . e($project->name) . '</strong>', 'new' => ['project_id' => $project->id], 'at' => $now->copy()->subDays(9)];

        if (! empty($bp['modules'])) {
            $rows[] = ['action' => 'ai_wbs_generated', 'module' => 'Project Master', 'desc' => 'Menyimpan WBS hasil AI (ditinjau pengguna) untuk proyek <strong>' . e($project->name) . '</strong>', 'new' => ['project_id' => $project->id, 'module_count' => count($bp['modules'])], 'at' => $now->copy()->subDays(8)];
        }

        if (! empty($bp['requires_design']) && ! empty($bp['deliverables'])) {
            $rows[] = ['action' => 'design_deliverable_created', 'module' => 'Project Master', 'desc' => 'Menambah deliverable desain pada proyek <strong>' . e($project->name) . '</strong>', 'new' => ['project_id' => $project->id, 'count' => count($bp['deliverables'])], 'at' => $now->copy()->subDays(6)];

            if (in_array($project->phase, ['Development', 'QC'], true)) {
                $rows[] = ['action' => 'project_phase_changed', 'module' => 'Project Master', 'desc' => 'Mengubah fase proyek <strong>' . e($project->name) . '</strong> menjadi <strong>Development</strong>', 'old' => ['phase' => 'Design'], 'new' => ['phase' => 'Development', 'reason' => 'Handover desain selesai'], 'at' => $now->copy()->subDays(5)];
            }
        }

        if (collect($bp['qc'] ?? [])->contains(fn ($q) => $q['status'] === 'passed')) {
            $rows[] = ['action' => 'qc_status_updated', 'module' => 'Project Master', 'desc' => 'Mengubah status QC pada proyek <strong>' . e($project->name) . '</strong> menjadi <strong>Lulus</strong>', 'old' => ['status' => 'pending'], 'new' => ['status' => 'passed', 'project_id' => $project->id], 'at' => $now->copy()->subDays(2)];
        }

        foreach ($rows as $row) {
            AuditLog::updateOrCreate(
                ['action' => $row['action'], 'module' => $row['module'], 'description' => $row['desc']],
                [
                    'user_id'        => $actor->id,
                    'auditable_type' => $project->getMorphClass(),
                    'auditable_id'   => $project->getKey(),
                    'old_values'     => $row['old'] ?? null,
                    'new_values'     => $row['new'] ?? null,
                    'ip_address'     => '127.0.0.1',
                    'user_agent'     => self::USER_AGENT,
                    'created_at'     => $row['at'],
                ],
            );
        }
    }

    /**
     * AI Monitor metadata — NO prompts, NO responses, NO raw bodies, NO keys.
     * Only feature / provider / model / status / latency / fallback / tokens.
     */
    private function seedAiMonitor(Project $project, Client $client, ?User $actor, bool $hasWbs): void
    {
        if (! $actor || ! $hasWbs) {
            return;
        }

        $now = AppTime::now();
        $rows = [
            ['feature' => 'mom_fixer',           'provider' => 'gemini', 'model' => 'gemini-1.5-flash', 'status' => 'success', 'fallback' => ['gemini'],                      'pt' => 920,  'ct' => 340, 'lat' => 1380, 'at' => $now->copy()->subDays(8)->addHours(1)],
            ['feature' => 'wbs_generator',       'provider' => 'gemini', 'model' => 'gemini-1.5-flash', 'status' => 'success', 'fallback' => ['gemini'],                      'pt' => 1280, 'ct' => 690, 'lat' => 2210, 'at' => $now->copy()->subDays(8)->addHours(2)],
        ];

        // QC-capable projects also exercised the test-case generator
        if (in_array($project->phase, ['Development', 'QC'], true)) {
            $rows[] = ['feature' => 'test_case_generator', 'provider' => 'groq', 'model' => 'llama-3.1-70b-versatile', 'status' => 'success', 'fallback' => ['gemini_failed', 'groq'], 'pt' => 1140, 'ct' => 500, 'lat' => 1890, 'at' => $now->copy()->subDays(3)];
        }

        foreach ($rows as $row) {
            $log = AiRequestLog::firstOrNew([
                'project_id' => $project->id,
                'feature'    => $row['feature'],
                'provider'   => $row['provider'],
                'model'      => $row['model'],
                'status'     => $row['status'],
            ]);

            $log->fill([
                'user_id'           => $actor->id,
                'client_id'         => $client->id,
                'fallback_path'     => $row['fallback'],
                'prompt_tokens'     => $row['pt'],
                'completion_tokens' => $row['ct'],
                'total_tokens'      => $row['pt'] + $row['ct'],
                'latency_ms'        => $row['lat'],
                'error_message'     => null,
            ]);

            $log->forceFill([
                'created_at' => $row['at'],
                'updated_at' => $row['at'],
            ])->save();
        }
    }

    /* ===================================================================
     |  HELPERS
     * =================================================================== */

    private function computeProgress(Project $project): int
    {
        $tasks = $project->tasks()->get(['status', 'estimate_hours']);
        if ($tasks->isEmpty()) {
            return 0;
        }

        $isDone = fn (string $s) => in_array($s, ['done', 'completed'], true);
        $totalHours = (int) $tasks->sum(fn ($t) => max(0, (int) $t->estimate_hours));

        if ($totalHours > 0) {
            $doneHours = (int) $tasks->filter(fn ($t) => $isDone($t->status))->sum(fn ($t) => max(0, (int) $t->estimate_hours));

            return (int) round($doneHours / $totalHours * 100);
        }

        $done = $tasks->filter(fn ($t) => $isDone($t->status))->count();

        return (int) round($done / $tasks->count() * 100);
    }

    /**
     * Write a tiny but VALID single-page PDF to the public disk under the same
     * directory the design-deliverable feature uses. Returns the storage path
     * (relative to the public disk) or null on failure.
     *
     * Idempotent: a stable filename is derived from project code + title, so
     * re-running overwrites the same file instead of piling up duplicates.
     */
    private function writeDummyPdf(Project $project, string $title): ?string
    {
        $slug = Str::slug($project->code . '-' . $title);
        $slug = $slug !== '' ? $slug : 'deliverable-' . $project->id;
        $path = self::PDF_DIR . '/demo-' . $slug . '.pdf';

        try {
            Storage::disk('public')->put($path, $this->buildPdf('Dummy Mockup PDF - ' . $project->name . ' / ' . $title));
        } catch (\Throwable $e) {
            report($e);

            return null;
        }

        return $path;
    }

    /**
     * Build a minimal, structurally valid PDF (with a correct xref table) so
     * the in-app PDF preview/download renders. No external dependency.
     */
    private function buildPdf(string $text): string
    {
        $text = str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $text);
        $content = 'BT /F1 16 Tf 72 770 Td (' . $text . ') Tj ET';

        $objects = [
            '<< /Type /Catalog /Pages 2 0 R >>',
            '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
            '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Contents 4 0 R /Resources << /Font << /F1 5 0 R >> >> >>',
            "<< /Length " . strlen($content) . " >>\nstream\n" . $content . "\nendstream",
            '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>',
        ];

        $pdf = "%PDF-1.4\n";
        $offsets = [];
        foreach ($objects as $i => $obj) {
            $offsets[$i] = strlen($pdf);
            $pdf .= ($i + 1) . " 0 obj\n" . $obj . "\nendobj\n";
        }

        $xrefPos = strlen($pdf);
        $count = count($objects) + 1;
        $pdf .= "xref\n0 {$count}\n";
        $pdf .= "0000000000 65535 f \n";
        foreach ($offsets as $offset) {
            $pdf .= sprintf("%010d 00000 n \n", $offset);
        }
        $pdf .= "trailer\n<< /Size {$count} /Root 1 0 R >>\nstartxref\n{$xrefPos}\n%%EOF";

        return $pdf;
    }
}
