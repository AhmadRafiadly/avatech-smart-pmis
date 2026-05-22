<?php

namespace Database\Seeders;

use App\Models\TeamWorkload;
use App\Models\User;
use Illuminate\Database\Seeder;

class TeamWorkloadSeeder extends Seeder
{
    public function run(): void
    {
        $rows = [
            ['email' => 'irwan.kurniawan@avatech.test', 'load_pct' => 78, 'active_tasks' => 12, 'is_sim' => false],
            ['email' => 'ferry.achmad@avatech.test',    'load_pct' => 42, 'active_tasks' => 7,  'is_sim' => false],
            ['email' => 'genta@avatech.test',           'load_pct' => 55, 'active_tasks' => 9,  'is_sim' => true],
            ['email' => 'yuda.prayoga@avatech.test',    'load_pct' => 90, 'active_tasks' => 14, 'is_sim' => false],
            ['email' => 'ahmad.arlisyah@avatech.test',  'load_pct' => 75, 'active_tasks' => 11, 'is_sim' => false],
        ];

        foreach ($rows as $row) {
            $userId = User::where('email', $row['email'])->value('id');
            if (! $userId) {
                continue;
            }

            TeamWorkload::updateOrCreate(
                ['user_id' => $userId],
                [
                    'load_pct'     => $row['load_pct'],
                    'active_tasks' => $row['active_tasks'],
                    'is_sim'       => $row['is_sim'],
                ],
            );
        }
    }
}
