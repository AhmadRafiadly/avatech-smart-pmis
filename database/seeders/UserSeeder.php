<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Seed Avatech team members with their MVP role assignment.
     * Idempotent via updateOrCreate (by email) + syncRoles.
     */
    public function run(): void
    {
        $users = [
            ['name' => 'Joshua Raphael',          'email' => 'joshua.raphael@avatech.test',  'role' => 'ceo_pm'],
            ['name' => 'Ahmad Rafiadly Arlisyah', 'email' => 'ahmad.arlisyah@avatech.test',  'role' => 'sa_qa'],
            ['name' => 'Ferry Achmad',            'email' => 'ferry.achmad@avatech.test',    'role' => 'fullstack_dev'],
            ['name' => 'Irwan Kurniawan',         'email' => 'irwan.kurniawan@avatech.test', 'role' => 'fullstack_dev'],
            ['name' => 'Genta',                   'email' => 'genta@avatech.test',           'role' => 'fullstack_dev'],
            ['name' => 'Yuda Prayoga',            'email' => 'yuda.prayoga@avatech.test',    'role' => 'uiux_designer'],
        ];

        foreach ($users as $data) {
            $user = User::updateOrCreate(
                ['email' => $data['email']],
                [
                    'name'              => $data['name'],
                    'password'          => Hash::make('password'),
                    'email_verified_at' => now(),
                ],
            );

            $user->syncRoles([$data['role']]);
        }
    }
}
