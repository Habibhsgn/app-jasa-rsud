<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $table = 'settings';

    protected $fillable = [
        'key', 'value', 'label', 'description',
    ];

    /**
     * Get a setting value by key, with default fallback.
     */
    public static function getValue(string $key, mixed $default = null): mixed
    {
        $setting = static::where('key', $key)->first();
        if (!$setting) {
            return $default;
        }

        return $setting->value;
    }
}
