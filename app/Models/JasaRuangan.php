<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JasaRuangan extends Model
{
    protected $table = 'jasa_ruangan';

    protected $fillable = [
        'periode_id',
        'ruangan_id',
        'persen',
        'nominal',
        'status'
    ];

    public function ruangan()
    {
        return $this->belongsTo(Ruangan::class);
    }

    public function periode()
    {
        return $this->belongsTo(PeriodeJasa::class);
    }

    public function jasaPegawai()
    {
        // Ini tabel transaksi uang (jasa_pegawai)
        return $this->hasMany(JasaPegawai::class);
    }

    public function pegawai()
    {
        // Ini tabel master orangnya (pegawai)
        return $this->hasMany(Pegawai::class, 'ruangan_id', 'ruangan_id');
    }

    protected static function booted()
    {
        static::deleting(function ($jasaRuangan) {
            // [SANGAT PENTING] Hapus tabel transaksi uangnya (jasaPegawai), BUKAN master pegawainya!
            $jasaRuangan->jasaPegawai()->delete();
        });
    }
}