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
        // 1. Make source_id nullable (required for ON DELETE SET NULL)
        Schema::table('index_scorings', function (Blueprint $table) {
            $table->unsignedBigInteger('source_id')->nullable()->change();
        });

        // 2. Migrate existing data: set source_type='pegawai', source_id=pegawai_id (if not already done)
        DB::table('index_scorings')
            ->where('source_type', 'pegawai')
            ->whereNull('source_id')
            ->update([
                'source_id' => DB::raw('pegawai_id'),
            ]);

        // 3. Add FK for top_leader
        Schema::table('index_scorings', function (Blueprint $table) {
            $table->foreign('source_id')
                ->references('id')
                ->on('top_leaders')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('index_scorings', function (Blueprint $table) {
            // Drop FKs
            $table->dropForeign(['source_id']);
            $table->dropForeign('index_scorings_pegawai_id_foreign');

            // Drop new unique, restore old unique
            $table->dropUnique('index_scorings_source_periode_unique');
            $table->unique(['pegawai_id', 'periode_pengajuan'], 'index_scorings_pegawai_periode_unique');

            // Drop source columns
            $table->dropColumn(['source_type', 'source_id']);

            // Restore NOT NULL for pegawai_id and ruangan_id (back to unsigned)
            $table->unsignedBigInteger('pegawai_id')->nullable(false)->change();
            $table->unsignedBigInteger('ruangan_id')->nullable(false)->change();

            // Re-add FKs with cascade
            $table->foreign('pegawai_id')
                ->references('id')
                ->on('pegawai')
                ->cascadeOnDelete();

            $table->foreign('ruangan_id')
                ->references('id')
                ->on('ruangan')
                ->cascadeOnDelete();
        });
    }
};