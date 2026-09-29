<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PeriodeJasa extends Model
{
    protected $table = 'periode_jasa';
    protected $fillable = [
        'periode',
        'total_jasa',
        'status',
        'keterangan',
        'periode_referensi_id',
    ];

    public function referensi()
    {
        return $this->belongsTo(PeriodeJasa::class, 'periode_referensi_id');
    }

    public function pendingTurunan()
    {
        return $this->hasMany(PeriodeJasa::class, 'periode_referensi_id');
    }

    const REGULER = 'JASA REGULER';
    const PENDING = 'JASA PENDING';
    protected $guarded = [];


    public function pembagianRuangan()
    {
        return $this->hasMany(JasaRuangan::class, 'periode_id');
    }

    protected static function booted()
    {
        static::deleting(function ($periode) {
            if ($periode->pembagianRuangan) {
                foreach ($periode->pembagianRuangan as $ruangan) {
                    $ruangan->delete();
                }
            }
        });

        static::updated(function ($periode) {
            $periode->jasaRuangan()->update([
                'keterangan' => $periode->keterangan
            ]);
        });
    }
}
