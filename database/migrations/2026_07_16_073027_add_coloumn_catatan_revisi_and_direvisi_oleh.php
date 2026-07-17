<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('index_scorings', function (Blueprint $table) {
            $table->text('catatan_revisi')->nullable()->after('status_pengajuan');

            $table->unsignedBigInteger('direvisi_oleh')->nullable()->after('catatan_revisi');
            $table->timestamp('direvisi_at')->nullable()->after('direvisi_oleh');

            $table->foreign('direvisi_oleh')
                ->references('id')->on('users')
                ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('index_scorings', function (Blueprint $table) {
            $table->dropForeign(['direvisi_oleh']);
            $table->dropColumn(['catatan_revisi', 'direvisi_oleh', 'direvisi_at']);
        });
    }
};