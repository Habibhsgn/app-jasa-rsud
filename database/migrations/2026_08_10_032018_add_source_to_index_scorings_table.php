<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // 1. Drop FK lama di pegawai_id & ruangan_id sebelum kolom diubah
        Schema::table('index_scorings', function (Blueprint $table) {
            $table->dropForeign('index_scorings_pegawai_id_foreign');
            $table->dropForeign('index_scorings_ruangan_id_foreign');
        });

        // 2. Jadikan pegawai_id & ruangan_id nullable
        //    PENTING: tetap bigInteger (signed), JANGAN unsignedBigInteger,
        //    karena pegawai.id & ruangan.id aslinya signed bigint.
        Schema::table('index_scorings', function (Blueprint $table) {
            $table->bigInteger('pegawai_id')->nullable()->change();
            $table->bigInteger('ruangan_id')->nullable()->change();
        });

        // 3. Tambah kolom source_type & source_id
        Schema::table('index_scorings', function (Blueprint $table) {
            $table->enum('source_type', ['pegawai', 'top_leader'])
                ->default('pegawai')
                ->after('pegawai_id');

            $table->unsignedBigInteger('source_id')   // <-- unsigned, cocok dgn top_leaders.id
                ->nullable()
                ->after('source_type');
        });

        // 4. Migrate existing data
        DB::table('index_scorings')
            ->where('source_type', 'pegawai')
            ->whereNull('source_id')
            ->update([
                'source_id' => DB::raw('pegawai_id'),
            ]);

        // 5. Ganti unique constraint lama -> baru
        Schema::table('index_scorings', function (Blueprint $table) {
            $table->dropUnique('index_scorings_pegawai_periode_unique');
            $table->unique(
                ['source_type', 'source_id', 'periode_pengajuan'],
                'index_scorings_source_periode_unique'
            );
        });

        // 6. Tambah kembali FK pegawai_id (SET NULL, bukan CASCADE lagi)
        Schema::table('index_scorings', function (Blueprint $table) {
            $table->foreign('pegawai_id', 'index_scorings_pegawai_id_foreign')
                ->references('id')
                ->on('pegawai')
                ->nullOnDelete();
        });

        // 7. Tambah kembali FK ruangan_id
        Schema::table('index_scorings', function (Blueprint $table) {
            $table->foreign('ruangan_id', 'index_scorings_ruangan_id_foreign')
                ->references('id')
                ->on('ruangan')
                ->nullOnDelete();
        });

        // 8. Tambah FK source_id -> top_leaders
        //    (Perlu cek dulu tipe top_leaders.id - lihat catatan di bawah)
        Schema::table('index_scorings', function (Blueprint $table) {
            $table->foreign('source_id', 'index_scorings_source_id_foreign')
                ->references('id')
                ->on('top_leaders')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('index_scorings', function (Blueprint $table) {
            $table->dropForeign('index_scorings_source_id_foreign');
            $table->dropForeign('index_scorings_pegawai_id_foreign');
            $table->dropForeign('index_scorings_ruangan_id_foreign');

            $table->dropUnique('index_scorings_source_periode_unique');
            $table->unique(['pegawai_id', 'periode_pengajuan'], 'index_scorings_pegawai_periode_unique');

            $table->dropColumn(['source_type', 'source_id']);

            $table->bigInteger('pegawai_id')->nullable(false)->change();
            $table->bigInteger('ruangan_id')->nullable(false)->change();

            $table->foreign('pegawai_id', 'index_scorings_pegawai_id_foreign')
                ->references('id')
                ->on('pegawai')
                ->cascadeOnDelete();

            $table->foreign('ruangan_id', 'index_scorings_ruangan_id_foreign')
                ->references('id')
                ->on('ruangan')
                ->cascadeOnDelete();
        });
    }
};