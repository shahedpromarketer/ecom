<?php

namespace App\Support;

use App\Models\Setting;

/**
 * Stock keeping is optional per shop ("Manage stock in Ecom Pilot", Inventory page).
 *
 * Off (default): nothing ever waits on stock; store products keep the store's numbers and
 * the CRM never changes the website's stock. The shop's own CRM products still count down
 * when they go to the courier.
 *
 * On: the CRM owns every product's stock. It goes down when a parcel goes to the courier,
 * comes back on cancel / return, and is sent to WooCommerce so the website shows it.
 *
 * Stock never blocks taking, confirming or shipping an order — in either mode.
 */
class Inventory
{
    public const SETTING = 'inventory_managed_in_crm';

    public static function managedInCrm(?int $tenantId = null): bool
    {
        $tenantId ??= Tenancy::active() ? Tenancy::id() : auth()->user()?->tenant_id;
        if (! $tenantId) {
            return false;
        }

        return (string) Setting::withoutGlobalScopes()->where('tenant_id', $tenantId)->where('key', self::SETTING)->value('value') === '1';
    }

    public static function setManagedInCrm(bool $on, int $tenantId): void
    {
        Setting::withoutGlobalScopes()->updateOrCreate(
            ['key' => self::SETTING, 'tenant_id' => $tenantId],
            ['value' => $on ? '1' : '0', 'group' => 'inventory'],
        );
    }
}
