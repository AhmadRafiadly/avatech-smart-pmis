<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Seed Avatech team members with their MVP role assignment.
     *
     * Password handling: only set when the row is first created. Re-running
     * the seeder will keep names/role/verification in sync without ever
     * overwriting an existing user's password — so this is safe to run in
     * any environment without trampling a real credential.
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
            $user = User::firstOrCreate(
                ['email' => $data['email']],
                [
                    'name'              => $data['name'],
                    'password'          => Hash::make('password'),
                    'email_verified_at' => now(),
                ],
            );

            // Keep name + verification in sync without touching password.
            if (! $user->wasRecentlyCreated) {
                $dirty = false;
                if ($user->name !== $data['name']) {
                    $user->name = $data['name'];
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

            $user->syncRoles([$data['role']]);
        }
    }
}
