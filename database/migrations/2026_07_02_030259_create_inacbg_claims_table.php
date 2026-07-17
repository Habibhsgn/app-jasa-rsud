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
        Schema::create('inacbg_claims', function (Blueprint $table) {
            $table->id();

            // Informasi Pasien
            $table->string('mrn')->index();
            $table->string('sep')->nullable()->index();
            $table->string('nama_pasien');
            $table->string('dpjp')->nullable();
            $table->string('payor_id')->nullable();

            // Demografi
            $table->date('birth_date')->nullable();
            $table->unsignedSmallInteger('umur_tahun')->nullable();
            $table->unsignedSmallInteger('umur_hari')->nullable();
            $table->char('sex', 1)->nullable();
            $table->string('discharge_status')->nullable();

            // Rawat
            $table->string('kelas_rs')->nullable();
            $table->string('kelas_rawat')->nullable();
            $table->string('kode_tarif')->nullable();

            $table->date('admission_date')->nullable()->index();
            $table->date('discharge_date')->nullable();
            $table->unsignedSmallInteger('los')->nullable();

            // Diagnosis & Prosedur
            $table->longText('diaglist')->nullable();
            $table->longText('proclist')->nullable();

            // Hasil Grouper
            $table->string('inacbg')->nullable()->index();
            $table->string('subacute')->nullable();
            $table->string('chronic')->nullable();
            $table->string('deskripsi_inacbg')->nullable();

            // Tarif
            $table->decimal('tarif_inacbg', 15, 2)->default(0);
            $table->decimal('tarif_subacute', 15, 2)->default(0);
            $table->decimal('tarif_chronic', 15, 2)->default(0);
            $table->decimal('tarif_sp', 15, 2)->default(0);
            $table->decimal('tarif_sr', 15, 2)->default(0);
            $table->decimal('tarif_si', 15, 2)->default(0);
            $table->decimal('tarif_sd', 15, 2)->default(0);
            $table->decimal('total_tarif', 15, 2)->default(0);
            $table->decimal('tarif_rs', 15, 2)->default(0);

            // Komponen Biaya RS
            $table->decimal('prosedur_non_bedah', 15, 2)->default(0);
            $table->decimal('prosedur_bedah', 15, 2)->default(0);
            $table->decimal('konsultasi', 15, 2)->default(0);
            $table->decimal('tenaga_ahli', 15, 2)->default(0);
            $table->decimal('keperawatan', 15, 2)->default(0);
            $table->decimal('penunjang', 15, 2)->default(0);
            $table->decimal('radiologi', 15, 2)->default(0);
            $table->decimal('laboratorium', 15, 2)->default(0);
            $table->decimal('pelayanan_darah', 15, 2)->default(0);
            $table->decimal('rehabilitasi', 15, 2)->default(0);
            $table->decimal('kamar_akomodasi', 15, 2)->default(0);
            $table->decimal('rawat_intensif', 15, 2)->default(0);
            $table->decimal('obat', 15, 2)->default(0);
            $table->decimal('alkes', 15, 2)->default(0);
            $table->decimal('bmhp', 15, 2)->default(0);
            $table->decimal('sewa_alat', 15, 2)->default(0);
            $table->decimal('obat_kronis', 15, 2)->default(0);
            $table->decimal('obat_kemo', 15, 2)->default(0);

            // iDRG
            $table->string('idrg_mdc_number')->nullable();
            $table->string('idrg_mdc_description')->nullable();
            $table->string('idrg_drg_code')->nullable();
            $table->string('idrg_drg_description')->nullable();
            $table->decimal('idrg_cost_weight', 10, 4)->default(0);
            $table->decimal('idrg_total_cost_weight', 10, 4)->default(0);
            $table->decimal('idrg_total_tarif', 15, 2)->default(0);

            // Versi Grouper
            $table->string('versi_inacbg')->nullable();
            $table->string('versi_grouper')->nullable();

            // Verifikasi & Klaim
            $table->date('tgl_verifikasi')->nullable();
            $table->decimal('biaya_riil_rs', 15, 2)->default(0);
            $table->decimal('biaya_diajukan', 15, 2)->default(0);
            $table->decimal('biaya_disetujui', 15, 2)->default(0);
            $table->enum('status', ['pending', 'disetujui'])->default('pending');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inacbg_claims');
    }
};
