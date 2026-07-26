<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Permission extends Model
{
    use HasFactory;

    protected $fillable = ['module_id', 'code', 'name', 'description'];

    public function module()
    {
        return $this->belongsTo(Module::class);
    }

    public function roles()
    {
        return $this->belongsToMany(Role::class, 'role_has_permission');
    }

    public function menus()
    {
        return $this->belongsToMany(Menu::class, 'menu_has_permission');
    }

    public function overrides()
    {
        return $this->hasMany(UserPermissionOverride::class);
    }
}
