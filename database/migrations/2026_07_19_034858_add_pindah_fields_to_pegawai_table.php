<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('pegawai', function (Blueprint $table) {

            if (!Schema::hasColumn('pegawai', 'status')) {
                $table->enum('status', ['aktif', 'pindah'])->default('aktif')->after('emergency');
            }

            if (!Schema::hasColumn('pegawai', 'ruangan_tujuan_id')) {
                $table->unsignedBigInteger('ruangan_tujuan_id')->nullable()->after('status');
            }

            if (!Schema::hasColumn('pegawai', 'diajukan_oleh')) {
                $table->unsignedBigInteger('diajukan_oleh')->nullable()->after('ruangan_tujuan_id');
            }

            if (!Schema::hasColumn('pegawai', 'diajukan_at')) {
                $table->timestamp('diajukan_at')->nullable()->after('diajukan_oleh');
            }

            // Tanpa foreign key constraint - relasi dijaga di level aplikasi (Eloquent + validasi)
        });
    }

    public function down()
    {
        Schema::table('pegawai', function (Blueprint $table) {
            foreach (['status', 'ruangan_tujuan_id', 'diajukan_oleh', 'diajukan_at'] as $col) {
                if (Schema::hasColumn('pegawai', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};