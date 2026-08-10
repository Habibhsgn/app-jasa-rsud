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
        Schema::table('top_leaders', function (Blueprint $table) {
            $table->unsignedInteger('gaji_pokok')->default(0)->after('posisi');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('top_leaders', function (Blueprint $table) {
            $table->dropColumn('gaji_pokok');
        });
    }
};