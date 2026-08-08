<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MasterBidang extends Model
{
    use SoftDeletes;

    protected $table = 'master_bidang';

    protected $fillable = [
        'kode_bidang',
        'nama_bidang',
        'is_active',
    ];

    public function users()
    {
        return $this->hasMany(User::class, 'bidang_id');
    }

    public function ruangans()
    {
        return $this->hasMany(Ruangan::class, 'bidang_id');
    }
}
