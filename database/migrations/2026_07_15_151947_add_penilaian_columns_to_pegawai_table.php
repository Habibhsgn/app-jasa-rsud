<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('pegawai', function (Blueprint $table) {
            $table->string('pendidikan_non_formal')->nullable()->after('jabatan');
            $table->decimal('gaji_pokok', 15, 2)->default(0)->after('pendidikan_non_formal');
            $table->decimal('risk', 8, 2)->default(0)->after('gaji_pokok');
            $table->decimal('emergency', 8, 2)->default(0)->after('risk');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pegawai', function (Blueprint $table) {
            $table->dropColumn([
                'pendidikan_non_formal',
                'gaji_pokok',
                'risk',
                'emergency',
            ]);
        });
    }
};