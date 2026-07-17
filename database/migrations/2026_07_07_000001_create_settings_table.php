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
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key', 100)->unique();
            $table->text('value')->nullable();
            $table->string('label', 200)->nullable();
            $table->string('description', 500)->nullable();
            $table->timestamps();
        });

        // Insert default settings
        DB::table('settings')->insert([
            [
                'key' => 'persen_biaya_operasional',
                'value' => '56',
                'label' => 'Biaya Operasional',
                'description' => 'Persentase biaya operasional dari total tarif',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'persen_jasa',
                'value' => '44',
                'label' => 'Total Jasa',
                'description' => 'Persentase jasa yang akan dibagikan dari total tarif',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
