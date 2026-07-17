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
        // ==========================================================
        // 1. Bersihkan dulu data duplikat (pegawai_id + periode_pengajuan)
        //    yang mungkin sudah terlanjur ada akibat bug sebelumnya.
        //    Simpan hanya baris dengan id TERBESAR (data paling baru)
        //    untuk tiap kombinasi pegawai_id + periode_pengajuan.
        // ==========================================================
        $duplicates = DB::table('index_scorings')
            ->select('pegawai_id', 'periode_pengajuan', DB::raw('MAX(id) as keep_id'))
            ->groupBy('pegawai_id', 'periode_pengajuan')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($duplicates as $dup) {
            DB::table('index_scorings')
                ->where('pegawai_id', $dup->pegawai_id)
                ->where('periode_pengajuan', $dup->periode_pengajuan)
                ->where('id', '!=', $dup->keep_id)
                ->delete();
        }

        // ==========================================================
        // 2. Tambahkan unique constraint sebagai pengaman di level DB.
        // ==========================================================
        Schema::table('index_scorings', function (Blueprint $table) {
            $table->unique(
                ['pegawai_id', 'periode_pengajuan'],
                'index_scorings_pegawai_periode_unique'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('index_scorings', function (Blueprint $table) {
            $table->dropUnique('index_scorings_pegawai_periode_unique');
        });
    }
};