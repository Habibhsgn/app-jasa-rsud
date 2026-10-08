<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Menambahkan penanda "penerima jasa 30%" pada Master Ruangan,
 * menggantikan daftar hardcode RUANGAN_PENERIMA_JASA di controller.
 *
 * PENTING: sesuaikan nama tabel jika model Ruangan tidak memakai tabel 'ruangan'.
 */
return new class extends Migration
{
    private string $table = 'ruangan';

    public function up(): void
    {
        Schema::table($this->table, function (Blueprint $table) {
            $table->boolean('penerima_jasa')
                ->default(false)
                ->after('persen_default');
        });

        // Data awal: ruangan yang sekarang punya persentase > 0
        // otomatis ditandai sebagai penerima jasa.
        DB::table($this->table)
            ->where('persen_default', '>', 0)
            ->update(['penerima_jasa' => true]);
    }

    public function down(): void
    {
        Schema::table($this->table, function (Blueprint $table) {
            $table->dropColumn('penerima_jasa');
        });
    }
};
