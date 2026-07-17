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
        Schema::create('index_scorings', function (Blueprint $table) {
            $table->id();

            /*
            |--------------------------------------------------------------------------
            | Relasi
            |--------------------------------------------------------------------------
            */
            $table->bigInteger('ruangan_id');
            $table->bigInteger('pegawai_id');

            $table->foreign('ruangan_id')
                ->references('id')
                ->on('ruangan')
                ->cascadeOnDelete();

            $table->foreign('pegawai_id')
                ->references('id')
                ->on('pegawai')
                ->cascadeOnDelete();

            /*
            |--------------------------------------------------------------------------
            | INDEX SKOR
            |--------------------------------------------------------------------------
            */

            // Position Index
            $table->tinyInteger('jabatan')->nullable();

            // Competency Index
            $table->tinyInteger('pendidikan_formal')->nullable();

            // Tambahan nilai pelatihan
            $table->decimal('pendidikan_non_formal', 3, 1)->default(0);

            // Risk Index
            $table->tinyInteger('risk')->default(0);

            // Emergency Index
            $table->tinyInteger('emergency')->default(0);

            /*
            |--------------------------------------------------------------------------
            | BOBOT PENGURANG (%)
            |--------------------------------------------------------------------------
            */

            $table->smallInteger('cuti')->default(0);
            $table->smallInteger('izin')->default(0);
            $table->smallInteger('tanpa_izin')->default(0);
            $table->smallInteger('telat')->default(0);
            $table->smallInteger('sikap')->default(0);

            /*
            |--------------------------------------------------------------------------
            | HASIL PERHITUNGAN
            |--------------------------------------------------------------------------
            */

            $table->decimal('jumlah', 8, 2)->default(0);
            $table->decimal('jumlah_akhir', 8, 2)->default(0);

            /*
            |--------------------------------------------------------------------------
            | Keterangan
            |--------------------------------------------------------------------------
            */

            $table->text('keterangan')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Status
            |--------------------------------------------------------------------------
            */

            $table->enum('status_pengajuan', [
                'draft',
                'submit',
                'verifikasi',
                'revisi',
                'selesai',
            ])->default('draft');

            $table->timestamps();

            // Satu pegawai hanya boleh memiliki satu index scoring
            // dalam satu periode dan satu ruangan
            $table->unique(
                ['ruangan_id', 'pegawai_id'],
                'index_scoring_unique'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('index_scorings');
    }
};
