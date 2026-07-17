<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('index_scorings', function (Blueprint $table) {
            // 1. Tambahkan index biasa dulu untuk menopang FK ruangan_id,
            //    supaya unique key lama boleh di-drop.
            $table->index('ruangan_id', 'index_scorings_ruangan_id_index');
        });

        Schema::table('index_scorings', function (Blueprint $table) {
            // 2. Baru drop unique key lama yang bermasalah.
            $table->dropUnique('index_scoring_unique');
        });
    }

    public function down(): void
    {
        Schema::table('index_scorings', function (Blueprint $table) {
            $table->dropIndex('index_scorings_ruangan_id_index');
            $table->unique(['ruangan_id', 'pegawai_id'], 'index_scoring_unique');
        });
    }
};