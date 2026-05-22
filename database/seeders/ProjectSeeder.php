<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class ProjectSeeder extends Seeder
{
    public function run(): void
    {
        $userByEmail = fn (string $email) => User::where('email', $email)->value('id');
        $clientByName = fn (string $name) => Client::where('name', $name)->value('id');

        // Reassign legacy filler codes at IDs 7 and 8 so the featured loop below
        // updates those rows in place (preserves IDs 7 and 8 for OT and KP).
        Project::where('id', 7)->where('code', 'P007')->update(['code' => 'OT']);
        Project::where('id', 8)->where('code', 'P008')->update(['code' => 'KP']);

        $featured = [
            ['code' => 'AC', 'color' => '#7C3AED', 'name' => 'Alpha CRM',          'client' => 'PT Maju Jaya',       'lead_email' => 'ahmad.arlisyah@avatech.test',  'phase' => 'Development', 'due_at' => '2026-06-30', 'progress' => 72,  'status' => 'on-track'],
            ['code' => 'BP', 'color' => '#A855F7', 'name' => 'Beta Portal',        'client' => 'CV Berkah Digital',  'lead_email' => 'yuda.prayoga@avatech.test',    'phase' => 'Design',      'due_at' => '2026-07-12', 'progress' => 45,  'status' => 'attention'],
            ['code' => 'GA', 'color' => '#C084FC', 'name' => 'Gamma API Gateway',  'client' => 'PT Solusi Pintar',   'lead_email' => 'irwan.kurniawan@avatech.test', 'phase' => 'QA',          'due_at' => '2026-05-18', 'progress' => 90,  'status' => 'critical'],
            ['code' => 'DL', 'color' => '#8B5CF6', 'name' => 'Delta Logistics',    'client' => 'PT Trans Nusantara', 'lead_email' => 'ferry.achmad@avatech.test',    'phase' => 'Development', 'due_at' => '2026-08-08', 'progress' => 38,  'status' => 'on-track'],
            ['code' => 'EX', 'color' => '#9333EA', 'name' => 'Epsilon Exchange',   'client' => 'PT Global Prima',    'lead_email' => 'genta@avatech.test',           'phase' => 'Discovery',   'due_at' => '2026-09-22', 'progress' => 18,  'status' => 'attention'],
            ['code' => 'ZN', 'color' => '#7C3AED', 'name' => 'Zeta Mobile App',    'client' => 'PT Maju Jaya',       'lead_email' => 'yuda.prayoga@avatech.test',    'phase' => 'Development', 'due_at' => '2026-07-15', 'progress' => 64,  'status' => 'on-track'],
            ['code' => 'OT', 'color' => '#6D28D9', 'name' => 'Omicron Onboarding', 'client' => 'CV Berkah Digital',  'lead_email' => 'ahmad.arlisyah@avatech.test',  'phase' => 'Done',        'due_at' => '2026-05-02', 'progress' => 100, 'status' => 'on-track'],
            ['code' => 'KP', 'color' => '#9333EA', 'name' => 'Kappa POS',          'client' => 'PT Toko Cerdas',     'lead_email' => 'irwan.kurniawan@avatech.test', 'phase' => 'QA',          'due_at' => '2026-06-28', 'progress' => 82,  'status' => 'on-track'],
        ];

        foreach ($featured as $p) {
            Project::updateOrCreate(
                ['code' => $p['code']],
                [
                    'color'            => $p['color'],
                    'name'             => $p['name'],
                    'client_id'        => $clientByName($p['client']),
                    'lead_user_id'     => $userByEmail($p['lead_email']),
                    'phase'            => $p['phase'],
                    'due_at'           => Carbon::parse($p['due_at']),
                    'progress'         => $p['progress'],
                    'status'           => $p['status'],
                    'ai_wbs_generated' => true,
                    'is_featured'      => true,
                ],
            );
        }

        $clientIds = Client::pluck('id')->all();
        $leadIds   = User::whereHas('roles', function ($q) {
            $q->whereIn('name', ['fullstack_dev', 'uiux_designer', 'sa_qa']);
        })->pluck('id')->all();

        $phases   = ['Discovery', 'Design', 'Development', 'QA', 'Done'];
        $statuses = ['on-track', 'on-track', 'on-track', 'on-track', 'attention', 'attention', 'critical'];
        $colors   = ['#7C3AED', '#A855F7', '#C084FC', '#8B5CF6', '#9333EA'];

        for ($i = 9; $i <= 142; $i++) {
            $code = 'P' . str_pad((string) $i, 3, '0', STR_PAD_LEFT);
            $aiOn = $i <= 138;

            Project::updateOrCreate(
                ['code' => $code],
                [
                    'color'            => $colors[$i % count($colors)],
                    'name'             => 'Proyek Demo ' . $code,
                    'client_id'        => $clientIds[$i % count($clientIds)] ?? null,
                    'lead_user_id'     => $leadIds[$i % count($leadIds)] ?? null,
                    'phase'            => $phases[$i % count($phases)],
                    'due_at'           => Carbon::create(2026, 1, 1)->addDays(($i * 7) % 270),
                    'progress'         => ($i * 17) % 100,
                    'status'           => $statuses[$i % count($statuses)],
                    'ai_wbs_generated' => $aiOn,
                    'is_featured'      => false,
                ],
            );
        }
    }
}
