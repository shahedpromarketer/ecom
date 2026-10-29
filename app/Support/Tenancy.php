<?php

namespace App\Support;

/**
 * Works out which tenant (shop) the current request belongs to.
 *
 * - A logged-in shop user  → their tenant.
 * - A super admin          → no tenant (sees every shop), unless impersonating.
 * - API / webhook requests → whatever the controller set with Tenancy::set()
 *                            after it verified the API key.
 * - Console / queue        → no tenant; code there must filter explicitly.
 */
class Tenancy
{
    private const KEY = 'tenancy.current_id';

    /** Pin the tenant for the rest of this request (used by API endpoints). */
    public static function set(?int $tenantId): void
    {
        app()->instance(self::KEY, $tenantId);
    }

    public static function forget(): void
    {
        app()->forgetInstance(self::KEY);
    }

    /** Run a callback as if the given tenant were logged in. */
    public static function as(?int $tenantId, callable $callback): mixed
    {
        $had      = app()->bound(self::KEY);
        $previous = $had ? app(self::KEY) : null;

        self::set($tenantId);

        try {
            return $callback();
        } finally {
            $had ? self::set($previous) : self::forget();
        }
    }

    /** Should queries be limited to one tenant right now? */
    public static function active(): bool
    {
        if (app()->bound(self::KEY)) {
            return app(self::KEY) !== null;
        }

        $user = auth()->user();

        return $user !== null && ! $user->is_super_admin;
    }

    /**
     * Validation rule: the id must exist in $table AND belong to the current shop.
     * (Plain "exists:" rules hit the database directly and skip model scopes.)
     */
    public static function exists(string $table, string $column = 'id'): \Illuminate\Validation\Rules\Exists
    {
        return \Illuminate\Validation\Rule::exists($table, $column)->where(function ($query) {
            if (self::active()) {
                $query->where('tenant_id', self::id());
            }
        });
    }

    /**
     * The tenant id queries are limited to. Only meaningful when active() is true.
     * A shop user without a tenant gets 0, which matches nothing.
     */
    public static function id(): ?int
    {
        if (app()->bound(self::KEY)) {
            return app(self::KEY);
        }

        $user = auth()->user();

        if ($user === null || $user->is_super_admin) {
            return null;
        }

        return $user->tenant_id ? (int) $user->tenant_id : 0;
    }
}
