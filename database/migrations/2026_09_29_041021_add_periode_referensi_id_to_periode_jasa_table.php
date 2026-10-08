<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasColumn('periode_jasa', 'periode_referensi_id')) {
            Schema::table('periode_jasa', function (Blueprint $table) {
                // signed, menyesuaikan periode_jasa.id yang bertipe bigint signed
                $table->bigInteger('periode_referensi_id')->nullable()->after('keterangan');
            });
        }

        Schema::table('periode_jasa', function (Blueprint $table) {
            $table->foreign('periode_referensi_id')
                ->references('id')->on('periode_jasa')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('periode_jasa', function (Blueprint $table) {
            $table->dropForeign(['periode_referensi_id']);
            $table->dropColumn('periode_referensi_id');
        });
    }
};
