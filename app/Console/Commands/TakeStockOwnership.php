<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ProductVariant;
use App\Models\StockLog;
use App\Services\StoreStockPusher;
use App\Support\Tenancy;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * One-time switch to "the CRM owns stock". Until now WooCommerce reduced stock when an
 * order was placed and the CRM copied that number, so orders not yet given to the courier
 * are already taken off. They will be taken off again when they ship, so put them back now.
 *
 *   php artisan stock:take-ownership --dry   (show what would change)
 *   php artisan stock:take-ownership         (do it)
 */
class TakeStockOwnership extends Command
{
    protected $signature   = 'stock:take-ownership {--dry : only show what would change} {--tenant= : one shop only}';
    protected $description = 'Add back store orders not yet shipped, now that stock goes down at the courier';

    /** Orders WooCommerce has already counted but the CRM has not sent to a courier. */
    private const OPEN = ['pending', 'confirmed', 'processing', 'hold', 'hold_followup'];

    public function handle(): void
    {
        $dry = (bool) $this->option('dry');
        $orders = Order::withoutGlobalScopes()
            ->whereNotNull('wordpress_site_id')
            ->whereIn('status', self::OPEN)
            ->where('stock_deducted', false)
            ->when($this->option('tenant'), fn ($q, $t) => $q->where('tenant_id', $t))
            ->get(['id', 'tenant_id', 'order_number']);

        $changes = [];   // variant id => qty to add back
        foreach ($orders as $order) {
            foreach (OrderItem::withoutGlobalScopes()->where('order_id', $order->id)->whereNotNull('variant_id')->get() as $item) {
                $variant = ProductVariant::withoutGlobalScopes()->find($item->variant_id);
                // Only stock the store counted: its own variations / the simple product's slot.
                if (! $variant || $variant->isCrmOnly()) {
                    continue;
                }
                $changes[$variant->id] = ($changes[$variant->id] ?? 0) + (int) $item->qty;
            }
        }

        $this->info($orders->count() . ' open store orders, ' . count($changes) . ' variants to correct.');
        $rows = [];
        foreach ($changes as $variantId => $qty) {
            $v = ProductVariant::withoutGlobalScopes()->with(['product' => fn ($q) => $q->withoutGlobalScopes()])->find($variantId);
            $rows[] = [$v->product?->name, trim($v->size . ' ' . $v->color) ?: '-', $v->stock, '+' . $qty, $v->stock + $qty];

            if (! $dry) {
                Tenancy::as($v->product->tenant_id, function () use ($v, $qty) {
                    DB::transaction(function () use ($v, $qty) {
                        $locked = ProductVariant::whereKey($v->id)->lockForUpdate()->first();
                        $before = $locked->stock;
                        StoreStockPusher::withoutPushing(fn () => $locked->update(['stock' => $before + $qty]));
                        StockLog::create(['variant_id' => $locked->id, 'qty_before' => $before, 'change' => $qty, 'qty_after' => $before + $qty,
                            'type' => 'adjustment', 'note' => 'Stock now goes down at the courier: put back orders not shipped yet']);
                    });
                    StoreStockPusher::push($v->product);
                });
            }
        }
        $this->table(['Product', 'Size / colour', 'Stock now', 'Add back', 'New stock'], $rows);
        $this->line($dry ? 'Nothing changed (--dry).' : 'Done. The stores were sent the new numbers.');
    }
}
