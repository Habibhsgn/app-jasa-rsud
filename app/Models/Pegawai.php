<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pegawai extends Model
{
    protected $table = 'pegawai';

    protected $fillable = [
        'nama',
        'id_petugas',
        'ruangan_id',
        'jabatan'
    ];

    public function ruangan()
    {
        return $this->belongsTo(Ruangan::class);
    }
}
