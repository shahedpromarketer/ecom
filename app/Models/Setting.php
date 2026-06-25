<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = ['key', 'value', 'group', 'tenant_id'];

    public static function getValue(string $key, mixed $default = null, ?int $tenantId = -1): mixed
    {
        if ($tenantId === -1) {
            $tenantId = auth()->user()?->tenant_id;
        }

        $setting = static::where('key', $key)
            ->where('tenant_id', $tenantId)
            ->first();

        return $setting ? $setting->value : $default;
    }

    public static function setValue(string $key, mixed $value, string $group = 'general', ?int $tenantId = -1): void
    {
        if ($tenantId === -1) {
            $tenantId = auth()->user()?->tenant_id;
        }

        static::updateOrCreate(
            ['key' => $key, 'tenant_id' => $tenantId],
            ['value' => $value, 'group' => $group]
        );
    }

    public static function forTenant(?int $tenantId = null): \Illuminate\Database\Eloquent\Builder
    {
        $tenantId ??= auth()->user()?->tenant_id;
        return static::where('tenant_id', $tenantId);
    }
}
