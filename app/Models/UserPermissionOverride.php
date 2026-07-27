<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserPermissionOverride extends Model
{
    use HasFactory;

    protected $table = 'user_permission_override';

    protected $fillable = ['user_id', 'permission_id', 'type', 'note'];

    public const TYPE_GRANT = 'grant';
    public const TYPE_REVOKE = 'revoke';

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function permission()
    {
        return $this->belongsTo(Permission::class);
    }
}
