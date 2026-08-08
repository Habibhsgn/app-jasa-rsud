<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable([
    'name',
    'email',
    'password',
    'role_id',
    'ruangan_id',
    'bidang_id',
])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function ruangan()
    {
        return $this->belongsTo(Ruangan::class);
    }

    /*
    |--------------------------------------------------------------------------
    | RBAC
    |--------------------------------------------------------------------------
    */

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function permissionOverrides()
    {
        return $this->hasMany(UserPermissionOverride::class);
    }

    public function hasPermission(string $code): bool
    {
        $permission = Permission::where('code', $code)->first();

        if (!$permission) {
            return false;
        }

        // 1. Override per-user selalu menang (grant maupun revoke)
        $override = $this->relationLoaded('permissionOverrides')
            ? $this->permissionOverrides->firstWhere('permission_id', $permission->id)
            : $this->permissionOverrides()->where('permission_id', $permission->id)->first();

        if ($override) {
            return $override->type === UserPermissionOverride::TYPE_GRANT;
        }

        // 2. Fallback ke permission dari role
        return $this->role?->hasPermission($code) ?? false;
    }

    public function hasRole(string ...$codes): bool
    {
        return $this->role && in_array($this->role->code, $codes, true);
    }

    public function bidang()
    {
        return $this->belongsTo(MasterBidang::class, 'bidang_id');
    }
}
