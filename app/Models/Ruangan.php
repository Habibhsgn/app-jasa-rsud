<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ruangan extends Model
{
    protected $table = 'ruangan';

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
