<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InacbgClaim extends Model
{
    use HasFactory;

    protected $table = 'inacbg_claims';

    protected $fillable = [
        // Informasi Pasien
        'mrn', 'sep', 'nama_pasien', 'dpjp', 'payor_id',

        // Demografi
        'birth_date', 'umur_tahun', 'umur_hari', 'sex', 'discharge_status',

        // Rawat
        'kelas_rs', 'kelas_rawat', 'kode_tarif',
        'admission_date', 'discharge_date', 'los',

        // Diagnosis & Prosedur
        'diaglist', 'proclist',

        // Hasil Grouper
        'inacbg', 'subacute', 'chronic', 'deskripsi_inacbg',

        // Tarif
        'tarif_inacbg', 'tarif_subacute', 'tarif_chronic',
        'tarif_sp', 'tarif_sr', 'tarif_si', 'tarif_sd',
        'total_tarif', 'tarif_rs',

        // Komponen Biaya RS
        'prosedur_non_bedah', 'prosedur_bedah', 'konsultasi',
        'tenaga_ahli', 'keperawatan', 'penunjang', 'radiologi',
        'laboratorium', 'pelayanan_darah', 'rehabilitasi',
        'kamar_akomodasi', 'rawat_intensif', 'obat', 'alkes',
        'bmhp', 'sewa_alat', 'obat_kronis', 'obat_kemo',

        // iDRG
        'idrg_mdc_number', 'idrg_mdc_description',
        'idrg_drg_code', 'idrg_drg_description',
        'idrg_cost_weight', 'idrg_total_cost_weight', 'idrg_total_tarif',

        // Versi Grouper
        'versi_inacbg', 'versi_grouper',

        // Verifikasi & Klaim
        'jenis_rawat',
        'tgl_verifikasi', 'biaya_riil_rs', 'biaya_diajukan',
        'biaya_disetujui', 'status',
    ];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'admission_date' => 'date',
            'discharge_date' => 'date',
            'tgl_verifikasi' => 'date',
            'tarif_inacbg' => 'decimal:2',
            'tarif_subacute' => 'decimal:2',
            'tarif_chronic' => 'decimal:2',
            'tarif_sp' => 'decimal:2',
            'tarif_sr' => 'decimal:2',
            'tarif_si' => 'decimal:2',
            'tarif_sd' => 'decimal:2',
            'total_tarif' => 'decimal:2',
            'tarif_rs' => 'decimal:2',
            'biaya_riil_rs' => 'decimal:2',
            'biaya_diajukan' => 'decimal:2',
            'biaya_disetujui' => 'decimal:2',
        ];
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeDisetujui($query)
    {
        return $query->where('status', 'disetujui');
    }
}
