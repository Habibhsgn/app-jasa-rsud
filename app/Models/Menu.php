<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Menu extends Model
{
    use HasFactory;

    protected $fillable = [
        'parent_id',
        'module_id',
        'name',
        'icon',
        'route_name',
        'is_header',
        'order',
        'is_active',
    ];

    protected $casts = [
        'is_header' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function parent()
    {
        return $this->belongsTo(Menu::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Menu::class, 'parent_id')->orderBy('order');
    }

    public function module()
    {
        return $this->belongsTo(Module::class);
    }

    public function permissions()
    {
        return $this->belongsToMany(Permission::class, 'menu_has_permission');
    }

    /**
     * Sebuah menu item terlihat kalau:
     * - is_active, dan
     * - tidak punya permission sama sekali (terbuka untuk semua user login), ATAU
     * - user punya SALAH SATU (OR) dari permission yang di-attach ke menu ini.
     *
     * Cek $user->hasPermission() di bawah sudah otomatis memperhitungkan
     * role permission + user_permission_override.
     */
    public function isVisibleTo(?User $user): bool
    {
        if (!$this->is_active) {
            return false;
        }

        if ($this->permissions->isEmpty()) {
            return true;
        }

        if (!$user) {
            return false;
        }

        foreach ($this->permissions as $permission) {
            if ($user->hasPermission($permission->code)) {
                return true;
            }
        }

        return false;
    }
}
