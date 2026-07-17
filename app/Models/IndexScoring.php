<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IndexScoring extends Model
{
    use HasFactory;

    protected $table = 'index_scorings';

    protected $fillable = [
        'index_periode_id',
        'ruangan_id',
        'pegawai_id',
        'periode_pengajuan',

        // Index
        'jabatan',
        'pendidikan_formal',
        'pendidikan_non_formal',
        'risk',
        'emergency',

        // Bobot Pengurang
        'cuti',
        'izin',
        'tanpa_izin',
        'telat',
        'sikap',

        // Hasil
        'jumlah',
        'jumlah_akhir',

        // Lainnya
        'keterangan',
        'status_pengajuan',
    ];

    protected $casts = [
        'pendidikan_non_formal' => 'decimal:1',
        'jumlah'                => 'decimal:2',
        'jumlah_akhir'          => 'decimal:2',
    ];

    /*
    |--------------------------------------------------------------------------
    | RELATIONSHIP
    |--------------------------------------------------------------------------
    */


    public function ruangan()
    {
        return $this->belongsTo(Ruangan::class, 'ruangan_id');
    }

    public function pegawai()
    {
        return $this->belongsTo(Pegawai::class, 'pegawai_id');
    }
}
