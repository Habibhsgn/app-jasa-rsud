<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PeriodeJasa extends Model
{
    protected $table = 'periode_jasa';

    // Disamakan dengan nilai yang benar-benar tersimpan di database
    const REGULER = 'REGULER';
    const PENDING = 'PENDING';

    protected $fillable = [
        'periode',
        'total_jasa',
        'status',
        'keterangan',
        'periode_referensi_id',
    ];

    public function pembagianRuangan()
    {
        return $this->hasMany(JasaRuangan::class, 'periode_id');
    }

    public function referensi()
    {
        return $this->belongsTo(PeriodeJasa::class, 'periode_referensi_id');
    }

    public function pendingTurunan()
    {
        return $this->hasMany(PeriodeJasa::class, 'periode_referensi_id');
    }

    protected static function booted()
    {
        static::deleting(function ($periode) {
            foreach ($periode->pembagianRuangan as $ruangan) {
                $ruangan->delete();
            }
        });

        static::updated(function ($periode) {
            if ($periode->wasChanged('keterangan')) {
                $periode->pembagianRuangan()->update([
                    'keterangan' => $periode->keterangan,
                ]);
            }
        });
    }
}
