<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $settings = [
            [
                'key' => 'persen_biaya_operasional',
                'value' => '56',
                'label' => 'Biaya Operasional',
                'description' => 'Persentase biaya operasional dari total tarif',
            ],
            [
                'key' => 'persen_jasa',
                'value' => '44',
                'label' => 'Total Jasa',
                'description' => 'Persentase jasa yang akan dibagikan dari total tarif',
            ],
            [
                'key' => 'persen_jasa_top_leader',
                'value' => '10',
                'label' => 'Jasa Top Leader',
                'description' => 'Persentase dari Total Jasa yang dialokasikan untuk Top Leader',
            ],
            [
                'key' => 'persen_jasa_staff',
                'value' => '90',
                'label' => 'Jasa Staff',
                'description' => 'Persentase dari Total Jasa yang dialokasikan untuk Staff',
            ],
            [
                'key' => 'top_leader.direktur',
                'value' => '22.5',
                'label' => 'Direktur',
                'description' => 'Persentase pembagian Jasa Top Leader untuk Direktur',
            ],
            [
                'key' => 'top_leader.kabid',
                'value' => '18.5',
                'label' => 'Kabid',
                'description' => 'Persentase pembagian Jasa Top Leader untuk Kabid',
            ],
            [
                'key' => 'top_leader.kasie',
                'value' => '31.5',
                'label' => 'Kasie',
                'description' => 'Persentase pembagian Jasa Top Leader untuk Kasie',
            ],
            [
                'key' => 'top_leader.bendahara',
                'value' => '5.5',
                'label' => 'Bendahara',
                'description' => 'Persentase pembagian Jasa Top Leader untuk Bendahara',
            ],
            [
                'key' => 'top_leader.casemix',
                'value' => '18.5',
                'label' => 'Casemix',
                'description' => 'Persentase pembagian Jasa Top Leader untuk Casemix',
            ],
            [
                'key' => 'top_leader.costing',
                'value' => '3.5',
                'label' => 'Costing',
                'description' => 'Persentase pembagian Jasa Top Leader untuk Costing',
            ],
            [
                'key' => 'staff.staf_manajemen',
                'value' => '6',
                'label' => 'Staf Manajemen',
                'description' => 'Persentase pembagian Jasa Staff untuk Staf Manajemen',
            ],
            [
                'key' => 'staff.dokter_umum',
                'value' => '3',
                'label' => 'Dokter Umum',
                'description' => 'Persentase pembagian Jasa Staff untuk Dokter Umum',
            ],
            [
                'key' => 'staff.dokter_gigi',
                'value' => '0.5',
                'label' => 'Dokter Gigi',
                'description' => 'Persentase pembagian Jasa Staff untuk Dokter Gigi',
            ],
            [
                'key' => 'staff.medis_paramedis',
                'value' => '90.5',
                'label' => 'Medis & Paramedis',
                'description' => 'Persentase pembagian Jasa Staff untuk Medis & Paramedis',
            ],
        ];

        foreach ($settings as $item) {
            Setting::firstOrCreate(
                ['key' => $item['key']],
                $item
            );
        }
    }
}
