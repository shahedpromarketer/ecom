<?php

namespace App\Observers;

use App\Models\Order;
use App\Models\StockLog;
use Illuminate\Support\Facades\DB;

class OrderObserver
{
    private const COURIER_STATUSES = ['shipped', 'hand_delivery'];

    public function updated(Order $order): void
    {
        // Only trigger when status just changed to a courier status AND not already deducted
        if (
            $order->wasChanged('status') &&
            in_array($order->status, self::COURIER_STATUSES) &&
            !$order->stock_deducted
        ) {
            $this->deductStock($order);
        }
    }

    private function deductStock(Order $order): void
    {
        DB::transaction(function () use ($order) {
            foreach ($order->items()->with('variant')->get() as $item) {
                // If variant_id not set, try to match by product + size info
                $variant = $item->variant ?? \App\Models\ProductVariant::where('product_id', $item->product_id)
                    ->where(function ($q) use ($item) {
                        $q->where('size', $item->variant_info)
                          ->orWhere('color', $item->variant_info);
                    })
                    ->first();
                if (!$variant) continue;

                $qty    = (int) $item->qty;
                $before = $variant->stock;
                $after  = max(0, $before - $qty);

                $variant->update(['stock' => $after]);

                StockLog::create([
                    'variant_id' => $variant->id,
                    'user_id'    => null,
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
}
