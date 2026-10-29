<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Order;
use App\Support\Phone;
use Illuminate\Support\Facades\DB;

/**
 * Keeps the customers table in step with orders: every order belongs to the
 * customer with the same (shop, phone), and the customer's counters are
 * recalculated whenever one of their orders changes.
 */
class CustomerSync
{
    public const DELIVERED = ['delivered', 'completed', 'hand_delivery_completed'];
    public const CANCELLED = ['cancelled'];
    public const RETURNED  = ['returned', 'pending_return', 'damage'];

    /** Called from OrderObserver after an order is created or changed. */
    public static function forOrder(Order $order): void
    {
        if (! $order->tenant_id) {
            return;
        }

        $previousCustomerId = $order->customer_id;   // before we (maybe) re-link it below
        $phone = Phone::normalize($order->shipping_phone);

        if ($phone === '') {
            return;
        }

        $customer = Customer::withoutGlobalScopes()->firstOrNew([
            'tenant_id' => $order->tenant_id,
            'phone'     => $phone,
        ]);

        // A customer in the Recycle Bin who orders again comes back.
        if ($customer->exists && $customer->trashed()) {
            $customer->restore();
        }

        // The newest order's contact details win, but an empty value never wipes a saved one.
        if (! $customer->exists || self::isLatestOrder($order, $customer)) {
            foreach ([
                'name'      => $order->shipping_name,
                'address'   => $order->shipping_address,
                'district'  => $order->shipping_district,
                'email'     => $order->email,
                'alt_phone' => $order->alt_phone,
            ] as $field => $value) {
                if (filled($value)) {
                    $customer->{$field} = $value;
                }
            }
        }
        $customer->save();

        if ((int) $order->customer_id !== $customer->id) {
            $order->forceFill(['customer_id' => $customer->id])->saveQuietly();
        }

        self::recalculateById($customer->id);

        // Phone was edited: the old customer lost an order.
        if ($previousCustomerId && (int) $previousCustomerId !== $customer->id) {
            self::recalculateById((int) $previousCustomerId);
        }
    }

    private static function isLatestOrder(Order $order, Customer $customer): bool
    {
        return ! $customer->last_order_at || ! $order->created_at || $order->created_at->gte($customer->last_order_at);
    }

    /** Recount everything from the customer's orders (cheap: one grouped query). */
    public static function recalculateById(int $customerId): void
    {
        $in = fn (array $statuses) => "'" . implode("','", $statuses) . "'";

        $stats = DB::table('orders')
            ->where('customer_id', $customerId)
            ->whereNull('deleted_at')   // orders in the Recycle Bin do not count
            ->selectRaw('COUNT(*) as total')
            ->selectRaw('SUM(CASE WHEN status IN (' . $in(self::DELIVERED) . ') THEN 1 ELSE 0 END) as delivered')
            ->selectRaw('SUM(CASE WHEN status IN (' . $in(self::CANCELLED) . ') THEN 1 ELSE 0 END) as cancelled')
            ->selectRaw('SUM(CASE WHEN status IN (' . $in(self::RETURNED) . ') THEN 1 ELSE 0 END) as returned')
            ->selectRaw('SUM(CASE WHEN status IN (' . $in(self::DELIVERED) . ') THEN total ELSE 0 END) as spent')
            ->selectRaw('MIN(created_at) as first_at, MAX(created_at) as last_at')
            ->first();

        DB::table('customers')->where('id', $customerId)->update([
            'total_orders'     => (int) $stats->total,
            'delivered_orders' => (int) $stats->delivered,
            'cancelled_orders' => (int) $stats->cancelled,
            'returned_orders'  => (int) $stats->returned,
            'total_spent'      => (float) $stats->spent,
            'first_order_at'   => $stats->first_at,
            'last_order_at'    => $stats->last_at,
            'updated_at'       => now(),
        ]);
    }
}
