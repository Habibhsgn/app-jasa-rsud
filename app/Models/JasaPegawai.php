<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JasaPegawai extends Model
{
    protected $table = 'jasa_pegawai';

    protected $fillable = [
        'jasa_ruangan_id',
        'pegawai_id',
        'persen',
        'nominal',
        'keterangan'
    ];

    public function pegawai()
    {
        return $this->belongsTo(Pegawai::class);
    }

    public function jasaRuangan()
    {
        return $this->belongsTo(JasaRuangan::class);
    }
}
