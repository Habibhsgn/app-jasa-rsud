<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('periode_jasa', function (Blueprint $table) {
            // Hapus unique lama (hanya periode)
            $table->dropUnique('periode');

            // Unique baru: satu bulan boleh punya 1 REGULER dan 1 PENDING
            $table->unique(['periode', 'keterangan'], 'periode_jasa_periode_keterangan_unique');
        });
    }

    public function down(): void
    {
        Schema::table('periode_jasa', function (Blueprint $table) {
            $table->dropUnique('periode_jasa_periode_keterangan_unique');
            $table->unique('periode', 'periode');
        });
    }
};
