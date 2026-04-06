<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            // 1. Buat kolomnya secara manual dengan tipe Unsigned Big Integer (standar Laravel)
            $table->bigInteger('ruangan_id')->nullable()->after('role');

            // 2. Hubungkan secara manual ke tabel 'ruangan'
            $table->foreign('ruangan_id')
                ->references('id')->on('ruangan')
                ->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['ruangan_id']);
            $table->dropColumn('ruangan_id');
        });
    }
};
