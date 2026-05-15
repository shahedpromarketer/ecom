<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CourierSetting extends Model
{
    protected $fillable = [
        'courier', 'api_key', 'api_secret', 'base_url',
        'webhook_url', 'extra_config', 'is_active',
    ];

    protected $casts = [
        'is_active'    => 'boolean',
        'extra_config' => 'array',
    ];

    public static function forCourier(string $courier): ?self
    {
        return static::where('courier', $courier)->first();
    }
}
