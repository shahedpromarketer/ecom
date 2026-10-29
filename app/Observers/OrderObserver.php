<?php

namespace App\Observers;

use App\Models\Order;
use App\Models\ProductVariant;
use App\Models\StockLog;
use App\Services\CustomerSync;
use Illuminate\Support\Facades\DB;

class OrderObserver
{
    /** Stock leaves the shelf when the parcel goes out. */
    private const COURIER_STATUSES = ['shipped', 'hand_delivery'];

    /** Stock comes back when the order is cancelled or the parcel is returned. */
    private const RESTOCK_STATUSES = ['cancelled', 'returned'];

    /** Order fields that change a customer's profile or counters. */
    private const CUSTOMER_FIELDS = ['shipping_phone', 'status', 'total', 'shipping_name', 'shipping_address', 'shipping_district', 'email', 'alt_phone', 'tenant_id'];

    public function created(Order $order): void
    {
        CustomerSync::forOrder($order);

        // The customer ordered (from the CRM or straight on the website): their open leads are won.
        if ($order->customer_id) {
            \App\Models\Lead::withoutGlobalScopes()
                ->where('tenant_id', $order->tenant_id)
                ->where('customer_id', $order->customer_id)
                ->where('status', 'pending')
                ->get()
                ->each(fn ($lead) => $lead->confirmWith($order));
        }

        // Website / API orders arrive without anyone responsible: hand them out.
        if (! $order->assigned_to) {
            \App\Services\OrderDistributor::assign($order);
        }
    }

    public function updated(Order $order): void
    {
        if ($order->wasChanged(self::CUSTOMER_FIELDS)) {
            CustomerSync::forOrder($order);
        }

        if (! $order->wasChanged('status')) {
            return;
        }

        if (in_array($order->status, self::COURIER_STATUSES, true) && ! $order->stock_deducted) {
            $this->deductStock($order);
        } elseif (in_array($order->status, self::RESTOCK_STATUSES, true) && $order->stock_deducted) {
            $this->restoreStock($order);
        }

        if (in_array($order->status, ['delivered', 'completed', 'hand_delivery_completed'], true)) {
            \App\Services\FollowupScheduler::afterDelivery($order);
        }

        // Orders from a WooCommerce store: tell the store (after the response).
        \App\Services\StoreStatusPusher::queue($order);
    }

    public function deleted(Order $order): void
    {
        if ($order->customer_id) {
            CustomerSync::recalculateById($order->customer_id);
        }
    }

    /** Back from the Recycle Bin: count it for the customer again. */
    public function restored(Order $order): void
    {
        if ($order->customer_id) {
            CustomerSync::recalculateById($order->customer_id);
        }
    }

    private function deductStock(Order $order): void
    {
        DB::transaction(function () use ($order) {
            foreach ($order->items()->with('variant')->get() as $item) {
                // If variant_id not set, try to match by product + size info
                $variant = $item->variant ?? ProductVariant::where('product_id', $item->product_id)
                    ->where(function ($q) use ($item) {
                        $q->where('size', $item->variant_info)
                          ->orWhere('color', $item->variant_info);
                    })
                    ->first();
                if (! $variant) {
                    continue;
                }

                $variant = ProductVariant::whereKey($variant->id)->lockForUpdate()->first();

                $qty    = (int) $item->qty;
                $before = $variant->stock;
                $after  = max(0, $before - $qty);

                $variant->update(['stock' => $after]);

                StockLog::create([
                    'variant_id' => $variant->id,
                    'user_id'    => auth()->id(),
                    'order_id'   => $order->id,
                    'qty_before' => $before,
                    'change'     => -$qty,
                    'qty_after'  => $after,
                    'type'       => 'courier_deduct',
                    'note'       => 'Auto deducted — order #' . $order->order_number . ' sent to courier',
                ]);
            }

            $order->updateQuietly(['stock_deducted' => true]);
        });
    }

    /**
     * Put back exactly what was taken off for this order (read from the
     * courier_deduct stock log, so a later edit to the order items does not
     * change how much comes back).
     */
    private function restoreStock(Order $order): void
    {
        DB::transaction(function () use ($order) {
            $deductions = StockLog::where('order_id', $order->id)
                ->where('type', 'courier_deduct')
                ->get()
                ->groupBy('variant_id');

            foreach ($deductions as $variantId => $logs) {
                $variant = ProductVariant::whereKey($variantId)->lockForUpdate()->first();
                if (! $variant) {
                    continue;
                }

                // qty_before - qty_after is what really left the shelf (stock never went below 0).
                $qty = $logs->sum(fn ($log) => $log->qty_before - $log->qty_after);
                if ($qty <= 0) {
                    continue;
                }

                $before = $variant->stock;
                $after  = $before + $qty;
                $variant->update(['stock' => $after]);

                StockLog::create([
                    'variant_id' => $variant->id,
                    'user_id'    => auth()->id(),
                    'order_id'   => $order->id,
                    'qty_before' => $before,
                    'change'     => $qty,
                    'qty_after'  => $after,
                    'type'       => 'order_restock',
                    'note'       => 'Auto restocked — order #' . $order->order_number . ' ' . $order->status,
                ]);
            }

            // Re-tag the old deductions so a second ship/cancel cycle is counted fresh.
            StockLog::where('order_id', $order->id)->where('type', 'courier_deduct')->update(['type' => 'courier_deduct_reversed']);

            $order->updateQuietly(['stock_deducted' => false]);
        });
    }
}
