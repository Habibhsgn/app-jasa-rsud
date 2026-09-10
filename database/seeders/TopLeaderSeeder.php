<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\TopLeader;

class TopLeaderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $topLeaders = [
            // Direktur (1)
            ['nama' => 'Dr. Ahmad Sudirman', 'posisi' => 'Direktur', 'gaji_pokok' => 5000000, 'is_active' => true],

            // Kabid (2)
            ['nama' => 'Dr. Siti Rahayu', 'posisi' => 'Kabid', 'gaji_pokok' => 4500000, 'is_active' => true],
            ['nama' => 'Dr. Hendra Gunawan', 'posisi' => 'Kabid', 'gaji_pokok' => 4500000, 'is_active' => true],

            // Kasie (3)
            ['nama' => 'Dr. Budi Santoso', 'posisi' => 'Kasie', 'gaji_pokok' => 4000000, 'is_active' => true],
            ['nama' => 'Dra. Maya Sari', 'posisi' => 'Kasie', 'gaji_pokok' => 4000000, 'is_active' => true],
            ['nama' => 'Eko Prasetyo, S.Kom', 'posisi' => 'Kasie', 'gaji_pokok' => 4000000, 'is_active' => true],

            // Bendahara (3)
            ['nama' => 'Ir. Dedi Kusuma', 'posisi' => 'Bendahara', 'gaji_pokok' => 3500000, 'is_active' => true],
            ['nama' => 'Rina Astuti, S.E.', 'posisi' => 'Bendahara', 'gaji_pokok' => 3500000, 'is_active' => true],
            ['nama' => 'Agus Hermawan, A.Md', 'posisi' => 'Bendahara', 'gaji_pokok' => 3500000, 'is_active' => true],

            // Casemix (3)
            ['nama' => 'Dr. Andi Wijaya', 'posisi' => 'Casemix', 'gaji_pokok' => 3250000, 'is_active' => true],
            ['nama' => 'Fitriani, S.Kep', 'posisi' => 'Casemix', 'gaji_pokok' => 3250000, 'is_active' => true],
            ['nama' => 'Bambang Utomo, S.K.M.', 'posisi' => 'Casemix', 'gaji_pokok' => 3250000, 'is_active' => true],

            // Costing (4)
            ['nama' => 'Dr. Rina Marlina', 'posisi' => 'Costing', 'gaji_pokok' => 3000000, 'is_active' => true],
            ['nama' => 'Dewi Lestari, S.E.', 'posisi' => 'Costing', 'gaji_pokok' => 3000000, 'is_active' => true],
            ['nama' => 'Fajar Nugraha, S.E.', 'posisi' => 'Costing', 'gaji_pokok' => 3000000, 'is_active' => true],
            ['nama' => 'Indah Permata, A.Md.Ak', 'posisi' => 'Costing', 'gaji_pokok' => 3000000, 'is_active' => true],
        ];
        foreach ($topLeaders as $tl) {
            TopLeader::create($tl);
        }
    }
}