<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CourierSetting extends Model
{
    protected $fillable = [
        'courier', 'api_key', 'api_secret', 'base_url',
        'webhook_url', 'extra_config', 'is_active', 'tenant_id',
    ];

    protected $casts = [
        'is_active'    => 'boolean',
        'extra_config' => 'array',
    ];

    public static function forCourier(string $courier, ?int $tenantId = null): ?self
    {
        $q = static::where('courier', $courier);
        if ($tenantId !== null) {
            $q->where('tenant_id', $tenantId);
        }
        return $q->first();
    }
}
