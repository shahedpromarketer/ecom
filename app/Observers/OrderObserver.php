<?php

namespace App\Observers;

use App\Models\Order;
use App\Models\ProductVariant;
use App\Models\StockLog;
use Illuminate\Support\Facades\DB;

class OrderObserver
{
    /** Stock leaves the shelf when the parcel goes out. */
    private const COURIER_STATUSES = ['shipped', 'hand_delivery'];

    /** Stock comes back when the order is cancelled or the parcel is returned. */
    private const RESTOCK_STATUSES = ['cancelled', 'returned'];

    public function updated(Order $order): void
    {
        if (! $order->wasChanged('status')) {
            return;
        }

        if (in_array($order->status, self::COURIER_STATUSES, true) && ! $order->stock_deducted) {
            $this->deductStock($order);
        } elseif (in_array($order->status, self::RESTOCK_STATUSES, true) && $order->stock_deducted) {
            $this->restoreStock($order);
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
