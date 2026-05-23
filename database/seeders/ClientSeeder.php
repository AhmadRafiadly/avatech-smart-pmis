<?php

namespace Database\Seeders;

use App\Models\Client;
use Illuminate\Database\Seeder;

class ClientSeeder extends Seeder
{
    public function run(): void
    {
        $named = [
            [
                'name' => 'PT Maju Jaya', 'code' => 'MJ', 'tier' => 'strategic',
                'industry' => 'Manufaktur', 'location' => 'Jakarta',
                'pic_name' => 'Bapak Hendra Wijaya', 'pic_role' => 'CTO',
                'email' => 'hendra@majujaya.co.id', 'phone' => '+62 812-3456-7890',
                'description' => 'Klien tetap sejak 2023, fokus modernisasi sistem internal manufaktur.',
                'total_engagement' => 6, 'relationship_health' => 82, 'last_touch_label' => '2 hari',
            ],
            [
                'name' => 'CV Berkah Digital', 'code' => 'BD', 'tier' => 'growth',
                'industry' => 'Digital Agency', 'location' => 'Bandung',
                'pic_name' => 'Ibu Sari Lestari', 'pic_role' => 'Director',
                'email' => 'sari@berkahdigital.id', 'phone' => '+62 813-1111-2222',
                'description' => 'Partner agency yang merujuk beberapa proyek dalam 8 bulan terakhir.',
                'total_engagement' => 3, 'relationship_health' => 58, 'last_touch_label' => '6 hari',
            ],
            [
                'name' => 'PT Solusi Pintar', 'code' => 'SP', 'tier' => 'strategic',
                'industry' => 'Fintech', 'location' => 'Jakarta',
                'pic_name' => 'Bapak Adi Pratama', 'pic_role' => 'Head of Product',
                'email' => 'adi@solusipintar.io', 'phone' => '+62 811-2233-4455',
                'description' => 'Klien fintech dengan kebutuhan integrasi sistem critical.',
                'total_engagement' => 4, 'relationship_health' => 64, 'last_touch_label' => '1 hari',
            ],
            [
                'name' => 'PT Trans Nusantara', 'code' => 'TN', 'tier' => 'growth',
                'industry' => 'Logistik', 'location' => 'Surabaya',
                'pic_name' => 'Bapak Reza Saputra', 'pic_role' => 'Operations Lead',
                'email' => 'reza@transnusantara.co.id', 'phone' => '+62 821-9999-1111',
                'description' => 'Pemain logistik regional, fokus modernisasi armada digital.',
                'total_engagement' => 2, 'relationship_health' => 78, 'last_touch_label' => '4 hari',
            ],
            [
                'name' => 'PT Global Prima', 'code' => 'GP', 'tier' => 'strategic',
                'industry' => 'Trading', 'location' => 'Jakarta',
                'pic_name' => 'Ibu Linda Hartono', 'pic_role' => 'Procurement Manager',
                'email' => 'linda@globalprima.com', 'phone' => '+62 815-7777-8888',
                'description' => 'Klien lama dengan engagement value tinggi dan perlu perhatian.',
                'total_engagement' => 3, 'relationship_health' => 45, 'last_touch_label' => '12 hari',
            ],
            [
                'name' => 'PT Karya Mandiri', 'code' => 'KM', 'tier' => 'standard',
                'industry' => 'Konstruksi', 'location' => 'Semarang',
                'pic_name' => 'Bapak Doni Kurniawan', 'pic_role' => 'Direktur Utama',
                'email' => 'doni@karyamandiri.co.id', 'phone' => '+62 819-2222-7777',
                'description' => 'Calon klien tahap nurture, belum memiliki proyek aktif.',
                'total_engagement' => 0, 'relationship_health' => 35, 'last_touch_label' => '21 hari',
            ],
            [
                'name' => 'PT Sinergi Cipta', 'code' => 'SC', 'tier' => 'prospect',
                'industry' => 'Konsultan', 'location' => 'Bekasi',
                'pic_name' => 'Ibu Rani Permata', 'pic_role' => 'Business Owner',
                'email' => 'rani@sinergicpta.id', 'phone' => '+62 812-9090-1010',
                'description' => 'Prospek baru untuk implementasi sistem operasional internal.',
                'total_engagement' => 0, 'relationship_health' => 50, 'last_touch_label' => 'baru saja',
            ],
            [
                'name' => 'CV Inovasi Bersama', 'code' => 'IB', 'tier' => 'prospect',
                'industry' => 'Startup Studio', 'location' => 'Bali',
                'pic_name' => 'Ibu Maya Putri', 'pic_role' => 'Founder',
                'email' => 'maya@inovasibersama.co', 'phone' => '+62 877-1010-2020',
                'description' => 'Studio startup yang berpotensi merujuk klien baru ke Avatech.',
                'total_engagement' => 0, 'relationship_health' => 88, 'last_touch_label' => '5 hari',
            ],
            [
                'name' => 'PT Toko Cerdas', 'code' => 'TC', 'tier' => 'growth',
                'industry' => 'Retail', 'location' => 'Yogyakarta',
                'pic_name' => 'Bapak Budi Santoso', 'pic_role' => 'IT Manager',
                'email' => 'budi@tokocerdas.id', 'phone' => '+62 856-5555-3333',
                'description' => 'Ritel multi-cabang dengan kebutuhan POS lightweight.',
                'total_engagement' => 1, 'relationship_health' => 81, 'last_touch_label' => '3 hari',
            ],
        ];

        foreach ($named as $row) {
            Client::updateOrCreate(['name' => $row['name']], $row);
        }

        Client::where('name', 'like', 'PT Generic Demo %')
            ->orWhere('code', 'regexp', '^G[0-9]{3}$')
            ->delete();
    }
}
