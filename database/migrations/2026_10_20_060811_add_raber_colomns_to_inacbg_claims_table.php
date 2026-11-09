<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('inacbg_claims', function (Blueprint $table) {
            $table->text('dpjp_asli')->nullable()->after('dpjp');          // null = masih default
            $table->string('dpjp_diubah_oleh')->nullable()->after('dpjp_asli');
            $table->timestamp('dpjp_diubah_at')->nullable()->after('dpjp_diubah_oleh');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inacbg_claims', function (Blueprint $table) {
            $table->dropColumn('dpjp_asli');
            $table->dropColumn('dpjp_diubah_oleh');
            $table->dropColumn('dpjp_diubah_at');
        });
    }
};
