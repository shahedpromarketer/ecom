<?php

namespace App\Models;

use App\Support\Tenancy;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = ['key', 'value', 'group', 'tenant_id'];

    /** The shop whose settings apply right now: a pinned tenant (API/plugin) first, else the signed-in user's. */
    private static function currentTenantId(): ?int
    {
        if (Tenancy::active()) {
            return Tenancy::id();
        }

        return auth()->user()?->tenant_id;
    }

    public static function getValue(string $key, mixed $default = null, ?int $tenantId = -1): mixed
    {
        if ($tenantId === -1) {
            $tenantId = self::currentTenantId();
        }

        $setting = static::where('key', $key)
            ->where('tenant_id', $tenantId)
            ->first();

        return $setting ? $setting->value : $default;
    }

    public static function setValue(string $key, mixed $value, string $group = 'general', ?int $tenantId = -1): void
    {
        if ($tenantId === -1) {
            $tenantId = self::currentTenantId();
        }

        static::updateOrCreate(
            ['key' => $key, 'tenant_id' => $tenantId],
            ['value' => $value, 'group' => $group]
        );
    }

    public static function forTenant(?int $tenantId = null): \Illuminate\Database\Eloquent\Builder
    {
        $tenantId ??= self::currentTenantId();
        return static::where('tenant_id', $tenantId);
    }
}
