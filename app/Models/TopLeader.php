<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TopLeader extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'top_leaders';

    protected $fillable = [
        'nama',
        'posisi',
        'gaji_pokok',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'gaji_pokok' => 'integer',
    ];
}