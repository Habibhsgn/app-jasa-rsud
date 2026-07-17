<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ruangan extends Model
{
    protected $table = 'ruangan';
    protected $fillable = [
        'nama_ruangan',
        'karu_id',
        'persen_default',
        'resiko',
        'emergency',
        'is_active'

    ];

    public function karu()
    {
        return $this->belongsTo(User::class, 'karu_id');
    }

    public function pegawai()
    {
        return $this->hasMany(Pegawai::class);
    }

    public function jasaRuangan()
    {
        return $this->hasMany(JasaRuangan::class);
    }
}
