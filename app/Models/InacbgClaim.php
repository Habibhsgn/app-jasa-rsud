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

        // Raber (dokter utama)
        'dpjp_asli', 'dpjp_diubah_oleh', 'dpjp_diubah_at',

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

        // Keterangan per seksi (JSON)
        'keterangan',

        // Tindakan radiologi, dipisahkan koma jika lebih dari satu
        'radiologi_tindakan',
    ];

    /**
     * Seksi keterangan. key = kode seksi (dipakai di permission & URL),
     * column = kolom biaya yang menentukan apakah SEP masuk tab seksi tsb.
     */
    public const SECTIONS = [
        'lab'       => ['label' => 'Laboratorium', 'short' => 'Lab', 'column' => 'laboratorium',   'color' => 'success', 'icon' => 'clipboard'],
        'radiologi' => ['label' => 'Radiologi',    'short' => 'Rad', 'column' => 'radiologi',      'color' => 'primary', 'icon' => 'aperture'],
        'utd'       => ['label' => 'UTD',          'short' => 'UTD', 'column' => 'pelayanan_darah', 'color' => 'danger', 'icon' => 'droplet'],
    ];

    /**
     * Pilihan tindakan radiologi (wajib dipilih sebelum mengisi keterangan).
     */
    public const TINDAKAN_RADIOLOGI = [
        'rontgen' => 'Rontgen',
        'usg'     => 'USG',
    ];

    public function radiologiTindakanList(): array
    {
        return array_values(array_intersect(
            array_keys(self::TINDAKAN_RADIOLOGI),
            explode(',', (string) $this->radiologi_tindakan)
        ));
    }

    public function radiologiTindakanLabel(): string
    {
        return implode(' + ', array_map(
            fn($tindakan) => self::TINDAKAN_RADIOLOGI[$tindakan],
            $this->radiologiTindakanList()
        ));
    }

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'admission_date' => 'date',
            'discharge_date' => 'date',
            'tgl_verifikasi' => 'date',
            'dpjp_diubah_at' => 'datetime',
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
            'laboratorium' => 'decimal:2',
            'radiologi' => 'decimal:2',
            'pelayanan_darah' => 'decimal:2',
            'keterangan' => 'array',
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

    public function scopeForSection($query, string $section)
    {
        $col = self::SECTIONS[$section]['column'] ?? null;

        return $col ? $query->where(function ($q) use ($col, $section) {
            $q->where($col, '>', 0)->orWhereNotNull("keterangan->{$section}->harga_awal");
        }) : $query;
    }

    public function hasSectionCost(string $section): bool
    {
        $col = self::SECTIONS[$section]['column'];

        return (float) $this->$col > 0 || isset($this->keterangan[$section]['harga_awal']);
    }

    /**
     * Rawat bersama: kolom DPJP berisi lebih dari satu dokter (dipisah "/").
     */
    public function scopeRaber($query)
    {
        return $query->where('dpjp', 'like', '%/%');
    }

    /**
     * Pecah kolom DPJP menjadi daftar dokter. Dokter pertama = dokter utama.
     */
    public static function splitDpjp(?string $dpjp): array
    {
        return array_values(array_filter(
            array_map('trim', explode('/', (string) $dpjp)),
            fn($d) => $d !== ''
        ));
    }
}
