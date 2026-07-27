<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    use HasFactory;

    protected $fillable = ['code', 'name', 'description'];

    /**
     * Role dengan code ini dianggap "super role" -> lolos semua permission check
     * (kecuali ada revoke override eksplisit di user_permission_override).
     */
    public const SUPER_ROLE_CODES = ['admin'];

    public function permissions()
    {
        return $this->belongsToMany(Permission::class, 'role_has_permission');
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function isSuper(): bool
    {
        return in_array($this->code, self::SUPER_ROLE_CODES, true);
    }

    public function hasPermission(string $code): bool
    {
        if ($this->isSuper()) {
            return true;
        }

        if ($this->relationLoaded('permissions')) {
            return $this->permissions->contains('code', $code);
        }

        return $this->permissions()->where('code', $code)->exists();
    }
}
