<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PeriodeJasa extends Model
{
    protected $table = 'periode_jasa'; // Sesuaikan jika nama tabel Anda berbeda (misal: periode_jasas)
    
    protected $guarded = []; // Mengizinkan semua kolom diisi

    // Tambahkan relasi ini agar foreach saat menghapus tidak bernilai NULL
    public function pembagianRuangan()
    {
        return $this->hasMany(JasaRuangan::class, 'periode_id');
    }

    protected static function booted()
    {
        static::deleting(function ($periode) {
            // Loop semua anak (JasaRuangan) dan hapus satu per satu
            // Memanggil $ruangan->delete() di sini akan memicu event deleting di JasaRuangan di atas
            if ($periode->pembagianRuangan) {
                foreach ($periode->pembagianRuangan as $ruangan) {
                    $ruangan->delete();
                }
            }
        });
    }
}