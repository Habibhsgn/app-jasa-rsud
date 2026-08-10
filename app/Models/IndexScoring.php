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
        'source_type',
        'source_id',
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

    public function topLeader()
    {
        return $this->belongsTo(TopLeader::class, 'source_id');
    }

    /**
     * Get the source (pegawai or top_leader) based on source_type
     */
    public function source()
    {
        if ($this->source_type === 'top_leader') {
            return $this->topLeader();
        }
        return $this->pegawai();
    }

    /**
     * Get source name for display
     */
    public function getSourceNameAttribute(): string
    {
        if ($this->source_type === 'top_leader' && $this->topLeader) {
            return $this->topLeader->nama;
        }
        return $this->pegawai?->nama ?? '-';
    }

    /**
     * Get source identifier (NIP/ID petugas or posisi)
     */
    public function getSourceIdentifierAttribute(): string
    {
        if ($this->source_type === 'top_leader' && $this->topLeader) {
            return $this->topLeader->posisi;
        }
        return $this->pegawai?->id_petugas ?? '-';
    }

    /**
     * Check if this is a top leader record
     */
    public function isTopLeader(): bool
    {
        return $this->source_type === 'top_leader';
    }

    /**
     * Check if this is a regular pegawai record
     */
    public function isPegawai(): bool
    {
        return $this->source_type === 'pegawai';
    }
}