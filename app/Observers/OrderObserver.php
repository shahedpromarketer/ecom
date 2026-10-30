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

    /** Side menu counts are cached: refresh them whenever an order comes, goes or changes status. */
    public function saved(Order $order): void
    {
        if ($order->wasRecentlyCreated || $order->wasChanged('status')) {
            \App\Support\SidebarCounts::forget($order->tenant_id);
        }
    }

    public function created(Order $order): void
    {
        // Every new order (website, sheet, API or made here) costs the shop's per-order fee, once.
        \App\Services\Wallet::chargeOrder($order);

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

        // Abandoned checkouts by the same number are now recovered.
        \App\Services\IncompleteCheckoutRecorder::markRecovered($order);

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
        \App\Support\SidebarCounts::forget($order->tenant_id);
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
                // The CRM owns stock for every product (store products too): it goes down here,
                // when the parcel goes to the courier, and the new number is sent to the store.
                $variant = $item->variant ?? $this->variantFor($item);
                if ($variant && ! $item->variant_id) {
                    $item->forceFill(['variant_id' => $variant->id])->saveQuietly();   // remember which one it was
                }
                if (! $variant) {
                    if ($item->product_id) {
                        \App\Models\ActivityLog::log('order.stock_skipped', $order,
                            "Stock not reduced for \"{$item->product_name}\": choose its size / variant on the order.");
                    }
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
    /**
     * A line without a chosen variant: its size from the order text ("Size: L"),
     * or the product's only variant when it has just one.
     */
    private function variantFor(\App\Models\OrderItem $item): ?ProductVariant
    {
        if (! $item->product_id) {
            return null;
        }
        $product = \App\Models\Product::find($item->product_id);
        if (! $product) {
            return null;
        }
        $info = (string) $item->variant_info;
        $sized = \App\Support\SizeMatcher::find($product, \App\Support\SizeMatcher::chosenSize($info !== '' ? explode(' / ', $info) : []));
        if ($sized) {
            return $sized;
        }
        $active = $product->variants()->where('is_active', true)->get();

        return $active->count() === 1 ? $active->first() : null;
    }

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
