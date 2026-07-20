<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Pegawai extends Model
{
    use SoftDeletes;

    protected $table = 'pegawai';

    protected $fillable = [
        'nama',
        'id_petugas',
        'ruangan_id',
        'jabatan',
        'resiko',
        'emergency',
        'status',
        'ruangan_tujuan_id',
        'diajukan_oleh'
    ];

    public function ruangan()
    {
        return $this->belongsTo(Ruangan::class);
    }

    public function ruanganTujuan()
    {
        return $this->belongsTo(Ruangan::class, 'ruangan_tujuan_id');
    }
    public function diajukanOleh(){
        return $this->belongsTo(User::class, 'diajukan_oleh');
    }
}
