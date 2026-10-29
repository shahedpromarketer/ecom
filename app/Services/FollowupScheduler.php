<?php

namespace App\Services;

use App\Models\Followup;
use App\Models\Order;
use App\Models\Setting;

/** Creates the automatic "time to buy again?" follow-up after a delivery. */
class FollowupScheduler
{
    public static function afterDelivery(Order $order): void
    {
        if (! $order->tenant_id || ! $order->customer_id || $order->skip_followup) {
            return;
        }

        $days = (int) Setting::getValue('followup_after_delivery_days', 7, $order->tenant_id);
        if ($days <= 0) {
            return;
        }

        // One automatic follow-up per order, and none if the customer already has an open one.
        $exists = Followup::withoutGlobalScopes()
            ->where('tenant_id', $order->tenant_id)
            ->where(fn ($q) => $q->where('order_id', $order->id)
                ->orWhere(fn ($w) => $w->where('customer_id', $order->customer_id)->where('status', 'listed')))
            ->exists();

        if ($exists) {
            return;
        }

        Followup::withoutGlobalScopes()->create([
            'tenant_id'     => $order->tenant_id,
            'customer_id'   => $order->customer_id,
            'order_id'      => $order->id,
            'assigned_to'   => $order->assigned_to,
            'scheduled_for' => now()->addDays($days)->toDateString(),
            'status'        => 'listed',
            'tag'           => 'reorder',
            'note'          => 'Auto: order #' . $order->order_number . ' was delivered ' . $days . ' days ago. Ask how it went and offer a re-order.',
        ]);
    }
}
