<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ProductVariant;
use App\Models\StockLog;
use App\Support\Tenancy;
use Illuminate\Support\Facades\DB;

/**
 * When a shop starts keeping stock in the CRM: until then WooCommerce took stock off when an
 * order was placed (and the CRM copied it), so orders not yet given to the courier are already
 * taken off. They will be taken off again when they ship — so put them back once, now.
 */
class StockOwnership
{
    /** Orders the store has already counted but the CRM has not sent to a courier. */
    private const OPEN = ['pending', 'confirmed', 'processing', 'hold', 'hold_followup'];

    /** @return array<int, array{product: ?string, variant: string, before: int, add: int}> */
    public static function takeOver(int $tenantId, bool $dry = false): array
    {
        $orderIds = Order::withoutGlobalScopes()->where('tenant_id', $tenantId)
            ->whereNotNull('wordpress_site_id')->whereIn('status', self::OPEN)->where('stock_deducted', false)
            ->pluck('id');

        $add = [];   // variant id => qty
        foreach (OrderItem::withoutGlobalScopes()->whereIn('order_id', $orderIds)->whereNotNull('variant_id')->get() as $item) {
            $v = ProductVariant::withoutGlobalScopes()->find($item->variant_id);
            // Only stock the store counted: its own variations / a simple product's size-less slot.
            if ($v && ! $v->isCrmOnly()) {
                $add[$v->id] = ($add[$v->id] ?? 0) + (int) $item->qty;
            }
        }

        $rows = [];
        Tenancy::as($tenantId, function () use ($add, $dry, &$rows) {
            $touched = [];
            foreach ($add as $variantId => $qty) {
                $v = ProductVariant::with('product')->find($variantId);
                if (! $v) {
                    continue;
                }
                $rows[] = ['product' => $v->product?->name, 'variant' => trim($v->size . ' ' . $v->color) ?: '-', 'before' => (int) $v->stock, 'add' => $qty];
                if ($dry) {
                    continue;
                }
                DB::transaction(function () use ($v, $qty) {
                    $locked = ProductVariant::whereKey($v->id)->lockForUpdate()->first();
                    $before = $locked->stock;
                    StoreStockPusher::withoutPushing(fn () => $locked->update(['stock' => $before + $qty]));
                    StockLog::create(['variant_id' => $locked->id, 'qty_before' => $before, 'change' => $qty, 'qty_after' => $before + $qty,
                        'type' => 'adjustment', 'note' => 'Stock now kept in Ecom Pilot: put back orders not shipped yet']);
                });
                $touched[$v->product_id] = $v->product;
            }
            foreach ($touched as $product) {
                StoreStockPusher::push($product);
            }
        });

        return $rows;
    }
}
