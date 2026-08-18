<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        $table = 'index_scorings';

        // Helper: cek apakah FK constraint sudah ada
        $fkExists = function (string $constraintName) use ($table) {
            return DB::table('information_schema.TABLE_CONSTRAINTS')
                ->where('CONSTRAINT_SCHEMA', DB::getDatabaseName())
                ->where('TABLE_NAME', $table)
                ->where('CONSTRAINT_NAME', $constraintName)
                ->where('CONSTRAINT_TYPE', 'FOREIGN KEY')
                ->exists();
        };

        // Helper: cek apakah index/unique sudah ada
        $indexExists = function (string $indexName) use ($table) {
            return DB::table('information_schema.STATISTICS')
                ->where('TABLE_SCHEMA', DB::getDatabaseName())
                ->where('TABLE_NAME', $table)
                ->where('INDEX_NAME', $indexName)
                ->exists();
        };

        // 1. Drop FK lama (hanya kalau masih ada)
        Schema::table($table, function (Blueprint $t) use ($fkExists) {
            if ($fkExists('index_scorings_pegawai_id_foreign')) {
                $t->dropForeign('index_scorings_pegawai_id_foreign');
            }
            if ($fkExists('index_scorings_ruangan_id_foreign')) {
                $t->dropForeign('index_scorings_ruangan_id_foreign');
            }
        });

        // 2. Jadikan pegawai_id & ruangan_id nullable (signed bigint)
        Schema::table($table, function (Blueprint $t) {
            $t->bigInteger('pegawai_id')->nullable()->change();
            $t->bigInteger('ruangan_id')->nullable()->change();
        });

        // 3. Tambah kolom source_type & source_id (hanya kalau belum ada)
        Schema::table($table, function (Blueprint $t) use ($table) {
            if (!Schema::hasColumn($table, 'source_type')) {
                $t->enum('source_type', ['pegawai', 'top_leader'])
                    ->default('pegawai')
                    ->after('pegawai_id');
            }
            if (!Schema::hasColumn($table, 'source_id')) {
                $t->unsignedBigInteger('source_id')
                    ->nullable()
                    ->after('source_type');
            }
        });

        // 4. Migrate existing data (whereNull -> aman diulang, tidak menimpa yang sudah terisi)
        DB::table($table)
            ->where('source_type', 'pegawai')
            ->whereNull('source_id')
            ->update([
                'source_id' => DB::raw('pegawai_id'),
            ]);

        // 5. Ganti unique constraint lama -> baru (hanya kalau belum sesuai)
        Schema::table($table, function (Blueprint $t) use ($indexExists) {
            if ($indexExists('index_scorings_pegawai_periode_unique')) {
                $t->dropUnique('index_scorings_pegawai_periode_unique');
            }
            if (!$indexExists('index_scorings_source_periode_unique')) {
                $t->unique(
                    ['source_type', 'source_id', 'periode_pengajuan'],
                    'index_scorings_source_periode_unique'
                );
            }
        });

        // 6. Tambah kembali FK pegawai_id (hanya kalau belum ada)
        Schema::table($table, function (Blueprint $t) use ($fkExists) {
            if (!$fkExists('index_scorings_pegawai_id_foreign')) {
                $t->foreign('pegawai_id', 'index_scorings_pegawai_id_foreign')
                    ->references('id')->on('pegawai')->nullOnDelete();
            }
        });

        // 7. Tambah kembali FK ruangan_id (hanya kalau belum ada)
        Schema::table($table, function (Blueprint $t) use ($fkExists) {
            if (!$fkExists('index_scorings_ruangan_id_foreign')) {
                $t->foreign('ruangan_id', 'index_scorings_ruangan_id_foreign')
                    ->references('id')->on('ruangan')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('index_scorings', function (Blueprint $t) {
            $t->dropForeign('index_scorings_source_id_foreign');
            $t->dropForeign('index_scorings_pegawai_id_foreign');
            $t->dropForeign('index_scorings_ruangan_id_foreign');

            $t->dropUnique('index_scorings_source_periode_unique');
            $t->unique(['pegawai_id', 'periode_pengajuan'], 'index_scorings_pegawai_periode_unique');

            $t->dropColumn(['source_type', 'source_id']);

            $t->bigInteger('pegawai_id')->nullable(false)->change();
            $t->bigInteger('ruangan_id')->nullable(false)->change();

            $t->foreign('pegawai_id', 'index_scorings_pegawai_id_foreign')
                ->references('id')->on('pegawai')->cascadeOnDelete();

            $t->foreign('ruangan_id', 'index_scorings_ruangan_id_foreign')
                ->references('id')->on('ruangan')->cascadeOnDelete();
        });
    }
};