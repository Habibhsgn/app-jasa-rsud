<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Bulatkan dulu data lama yang mungkin masih desimal, sebelum ubah tipe kolom.
        DB::statement('UPDATE pegawai SET gaji_pokok = ROUND(gaji_pokok)');

        Schema::table('pegawai', function (Blueprint $table) {
            $table->unsignedInteger('gaji_pokok')->default(0)->change();
        });
    }

    public function down(): void
    {
        Schema::table('pegawai', function (Blueprint $table) {
            $table->decimal('gaji_pokok', 12, 2)->default(0)->change();
        });
    }
};