<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Mengubah kolom risk & emergency di tabel pegawai dari decimal(8,2)
     * menjadi tinyint unsigned, karena nilainya memang hanya salah satu
     * dari 4 grade tetap (1, 2, 4, 6) sesuai pedoman index scoring —
     * konsisten dengan kolom resiko & emergency di tabel ruangan yang
     * sudah tinyint unsigned.
     */
    public function up(): void
    {
        // Jaga-jaga: bulatkan dulu data yang mungkin sudah kepalang
        // tersimpan sebagai desimal (mis. 1.00, 2.00) sebelum tipe diubah,
        // supaya tidak ada nilai pecahan yang terpotong tidak sesuai.
        DB::table('pegawai')->update([
            'risk'      => DB::raw('ROUND(risk)'),
            'emergency' => DB::raw('ROUND(emergency)'),
        ]);

        Schema::table('pegawai', function (Blueprint $table) {
            $table->unsignedTinyInteger('risk')->default(1)->change();
            $table->unsignedTinyInteger('emergency')->default(1)->change();
        });
    }

    /**
     * Reverse the migrations.
     * Kembalikan ke decimal(8,2) seperti semula jika perlu rollback.
     */
    public function down(): void
    {
        Schema::table('pegawai', function (Blueprint $table) {
            $table->decimal('risk', 8, 2)->default(0.00)->change();
            $table->decimal('emergency', 8, 2)->default(0.00)->change();
        });
    }
};
