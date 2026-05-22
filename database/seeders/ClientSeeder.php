<?php

namespace Database\Seeders;

use App\Models\Client;
use Illuminate\Database\Seeder;

class ClientSeeder extends Seeder
{
    public function run(): void
    {
        $named = [
            ['name' => 'PT Maju Jaya',          'code' => 'MJ', 'tier' => 'strategic'],
            ['name' => 'CV Berkah Digital',     'code' => 'BD', 'tier' => 'growth'],
            ['name' => 'PT Solusi Pintar',      'code' => 'SP', 'tier' => 'growth'],
            ['name' => 'PT Trans Nusantara',    'code' => 'TN', 'tier' => 'standard'],
            ['name' => 'PT Global Prima',       'code' => 'GP', 'tier' => 'strategic'],
            ['name' => 'PT Karya Mandiri',      'code' => 'KM', 'tier' => 'standard'],
            ['name' => 'PT Sinergi Cipta',      'code' => 'SC', 'tier' => 'prospect'],
            ['name' => 'CV Inovasi Bersama',    'code' => 'IB', 'tier' => 'prospect'],
            ['name' => 'PT Toko Cerdas',        'code' => 'TC', 'tier' => 'growth'],
        ];

        foreach ($named as $row) {
            Client::updateOrCreate(['name' => $row['name']], $row);
        }

        // Cleanup leftover filler from prior 37-row run to keep total at 45.
        Client::where('name', 'PT Generic Demo 037')->delete();

        $tiers = ['standard', 'standard', 'standard', 'growth', 'prospect'];
        for ($i = 1; $i <= 36; $i++) {
            $num = str_pad((string) $i, 3, '0', STR_PAD_LEFT);
            Client::updateOrCreate(
                ['name' => "PT Generic Demo {$num}"],
                ['code' => 'G' . $num, 'tier' => $tiers[$i % count($tiers)]],
            );
        }
    }
}
