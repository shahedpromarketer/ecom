<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * The permission list a role can have, and which admin pages need which permission.
 *
 * Shop owners (users.role = 'admin') and super admins can do everything.
 * Staff (users.role = 'staff') can only do what their role (users.role_id) allows.
 */
class Permissions
{
    /** Shown as checkbox groups on the Roles page. */
    public const GROUPS = [
        'Orders'    => ['view_orders', 'view_all_orders', 'manage_orders', 'export_orders', 'view_all_leads'],
        'Products'  => ['view_products', 'manage_products'],
        'Customers' => ['view_customers', 'manage_customers', 'ban_customers', 'view_all_followups'],
        'Marketing' => ['manage_marketing'],
        'Reports'   => ['view_reports', 'export_reports'],
        'Settings'  => ['manage_settings', 'manage_staff'],
    ];

    /**
     * Route name pattern => permission. The first match wins.
     * An array value means [permission for GET/HEAD, permission for changes].
     * Routes not listed here are open to shop owners only.
     */
    private const ROUTES = [
        // Everyone who can sign in
        'admin.dashboard*'          => null,
        'admin.profile*'            => null,
        'admin.logout'              => null,
        'admin.sidebar.counts'      => null,
        'admin.saved-filters.*'     => null,   // the list pages themselves are permission-checked

        // Exports
        'admin.orders.exportCsv'    => 'export_orders',
        'admin.invoices.csv'        => 'export_orders',
        'admin.inventory.exportCsv' => 'export_reports',

        // Orders & money
        'admin.orders.*'            => ['view_orders', 'manage_orders'],
        'admin.payments.*'          => ['view_orders', 'manage_orders'],
        // Leads: importing / moving between staff also needs view_all_leads (checked in the controller).
        'admin.leads.*'             => ['view_orders', 'manage_orders'],
        'admin.returns.*'           => ['view_orders', 'manage_orders'],
        'admin.invoices.*'          => ['view_orders', 'manage_orders'],

        // Catalog
        'admin.products.*'          => ['view_products', 'manage_products'],
        'admin.categories.*'        => ['view_products', 'manage_products'],
        'admin.inventory.*'         => ['view_products', 'manage_products'],

        // Customers / fraud
        'admin.customers.*'         => ['view_customers', 'manage_customers'],
        // Anyone who works with customers can work their own call list;
        // seeing/moving other people's follow-ups needs view_all_followups (checked in the controller).
        'admin.followups.*'         => 'view_customers',
        'admin.blacklist.*'         => 'ban_customers',

        // Marketing
        'admin.coupons.*'           => 'manage_marketing',
        'admin.marketing.*'         => 'manage_marketing',

        // Reports
        'admin.reports.*'           => 'view_reports',
        'admin.analytics.*'         => 'view_reports',
        'admin.activity-log.*'      => 'view_reports',

        // Staff
        'admin.users.*'             => 'manage_staff',
        'admin.distribution.*'      => 'manage_staff',
        'admin.roles.*'             => 'manage_staff',

        // Settings
        'admin.settings.*'          => 'manage_settings',
        'admin.courier.*'           => 'manage_settings',
        'admin.integrations.*'      => 'manage_settings',
        'admin.wordpress'           => 'manage_settings',
        'admin.invoice-setup.*'     => 'manage_settings',
        'admin.invoice-templates.*' => 'manage_settings',
    ];

    public static function all(): array
    {
        return array_merge(...array_values(self::GROUPS));
    }

    /**
     * Permission needed for this request.
     * Returns null when any signed-in user may continue, false when only owners may.
     */
    public static function requiredFor(Request $request): string|null|false
    {
        $name = $request->route()?->getName();
        if (! $name) {
            return false;
        }

        foreach (self::ROUTES as $pattern => $permission) {
            if (! Str::is($pattern, $name)) {
                continue;
            }

            if (is_array($permission)) {
                return $request->isMethodSafe() ? $permission[0] : $permission[1];
            }

            return $permission;
        }

        return false;
    }
}
