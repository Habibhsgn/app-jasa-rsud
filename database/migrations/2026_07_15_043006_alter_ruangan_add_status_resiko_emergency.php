<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('ruangan', function (Blueprint $table) {

            // Faktor Risiko
            $table->decimal('resiko', 5, 2)
                ->default(0)
                ->after('persen_default');

            // Faktor Emergency
            $table->decimal('emergency', 5, 2)
                ->default(0)
                ->after('resiko');

            // Status Aktif / Non Aktif
            $table->boolean('is_active')
                ->default(true)
                ->after('emergency');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ruangan', function (Blueprint $table) {

            $table->dropColumn([
                'resiko',
                'emergency',
                'is_active'
            ]);

        });
    }
};