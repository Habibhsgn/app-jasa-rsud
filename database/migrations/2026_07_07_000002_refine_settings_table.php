<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Update data: ganti label_total_jasa → persen_jasa
        DB::table('settings')->where('key', 'label_total_jasa')->delete();

        // Upsert by key
        $now = now();
        $existing = DB::table('settings')->pluck('key')->toArray();

        if (!in_array('persen_jasa', $existing)) {
            DB::table('settings')->insert([
                'key' => 'persen_jasa',
                'value' => '44',
                'label' => 'Total Jasa',
                'description' => 'Persentase jasa yang akan dibagikan dari total tarif',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        // Update label untuk persen_biaya_operasional
        DB::table('settings')->where('key', 'persen_biaya_operasional')->update([
            'label' => 'Biaya Operasional',
            'description' => 'Persentase biaya operasional dari total tarif',
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('settings')->where('key', 'persen_jasa')->delete();
    }
};
