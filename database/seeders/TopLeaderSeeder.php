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
            ['nama' => 'Dr. Ahmad Sudirman', 'posisi' => 'Direktur', 'is_active' => true],
            ['nama' => 'Dr. Siti Rahayu', 'posisi' => 'Kabid', 'is_active' => true],
            ['nama' => 'Dr. Budi Santoso', 'posisi' => 'Kasie', 'is_active' => true],
            ['nama' => 'Dra. Maya Sari', 'posisi' => 'Kasie', 'is_active' => true],
            ['nama' => 'Ir. Dedi Kusuma', 'posisi' => 'Bendahara', 'is_active' => true],
            ['nama' => 'Dr. Andi Wijaya', 'posisi' => 'Casemix', 'is_active' => true],
            ['nama' => 'Dr. Rina Marlina', 'posisi' => 'Costing', 'is_active' => true],
        ];

        foreach ($topLeaders as $tl) {
            TopLeader::create($tl);
        }
    }
}