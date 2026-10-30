<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderNote;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\WordPressSite;
use App\Support\Phone;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Turns an order sent by the Ecom Pilot WordPress plugin into a CRM order.
 *
 * Idempotent: the same WooCommerce order (store + Woo id) always maps to one
 * CRM order. Re-sends update contact details; items and totals are only
 * replaced while nobody has started working on the order (status pending).
 * The CRM owns the status after import; a cancel/refund in WooCommerce only
 * cancels orders that are still pending or confirmed.
 *
 * Must run with the store's tenant pinned (Tenancy::set).
 */
class WooOrderImporter
{
    /** WooCommerce statuses that are not real orders yet. */
    private const SKIP_STATUSES = ['checkout-draft', 'trash', 'auto-draft'];

    /** @return array{0: ?Order, 1: string} the order and what happened: created | updated | skipped */
    public static function import(WordPressSite $site, array $p): array
    {
        $wooId = (string) ($p['id'] ?? '');
        if ($wooId === '' || in_array($p['status'] ?? '', self::SKIP_STATUSES, true)) {
            return [null, 'skipped'];
        }

        return DB::transaction(function () use ($site, $p, $wooId) {
            $existing = Order::withoutGlobalScope('assignee')->withTrashed()
                ->where('wordpress_site_id', $site->id)
                ->where('source_order_id', $wooId)
                ->lockForUpdate()
                ->first();

            // Orders imported before plugin v2 were matched by Woo id + website only.
            if (! $existing && in_array($site->type, ['woocommerce', 'wordpress'], true)) {
                $existing = Order::withoutGlobalScope('assignee')
                    ->whereNull('wordpress_site_id')
                    ->where('source_order_id', $wooId)
                    ->whereIn('source', ['woocommerce', 'wordpress'])
                    ->where('source_website', 'like', '%' . parse_url($site->site_url, PHP_URL_HOST) . '%')
                    ->first();
            }

            // Deleted in the CRM on purpose: a re-send must not bring it back.
            if ($existing?->trashed()) {
                return [$existing, 'skipped'];
            }

            return $existing
                ? [self::update($existing, $site, $p), 'updated']
                : [self::create($site, $p), 'created'];
        });
    }

    private static function create(WordPressSite $site, array $p): Order
    {
        $order = new Order(self::orderFields($site, $p) + [
            'status'         => self::wooCancelled($p) ? 'cancelled' : 'pending',
            'print_status'   => 'unprinted',
        ]);
        if (! empty($p['created_at'])) {
            $order->created_at = Carbon::parse($p['created_at']);
        }
        $order->save();

        self::replaceItems($order, $p);

        ActivityLog::log('order.imported', $order, 'Imported from ' . $site->name . ' (#' . ($p['number'] ?? $p['id']) . ')');

        return $order;
    }

    private static function update(Order $order, WordPressSite $site, array $p): Order
    {
        $fields = self::orderFields($site, $p);

        // Money and lines only while untouched; contact details and notes always.
        if ($order->status !== 'pending') {
            $fields = array_intersect_key($fields, array_flip([
                'shipping_name', 'shipping_phone', 'alt_phone', 'email', 'shipping_address',
                'shipping_district', 'shipping_area', 'notes', 'wordpress_site_id', 'source_website',
                'payment_status', 'paid_amount',
            ]));
        }

        $order->fill($fields);

        $cancelledInStore = self::wooCancelled($p) && in_array($order->status, ['pending', 'confirmed'], true);
        if ($cancelledInStore) {
            $order->status = 'cancelled';
        }

        $order->save();

        if ($cancelledInStore) {
            ActivityLog::log('order.cancelled_in_store', $order, 'Cancelled in WooCommerce (' . ($p['status'] ?? '') . ') at ' . $site->name);
        }

        if ($order->status === 'pending' && ! empty($p['items'])) {
            self::replaceItems($order, $p);
        }

        return $order;
    }

    private static function orderFields(WordPressSite $site, array $p): array
    {
        $b = $p['billing'] ?? [];
        $s = $p['shipping'] ?? [];
        $pick = fn (string $key) => trim((string) (($b[$key] ?? '') !== '' ? $b[$key] : ($s[$key] ?? '')));

        $name = trim($pick('first_name') . ' ' . $pick('last_name'));
        $phone = Phone::normalize($pick('phone'));
        $address = trim($pick('address_1') . ' ' . $pick('address_2'));
        $district = $pick('state_name') ?: $pick('state') ?: $pick('city');
        $attr = $p['attribution'] ?? [];
        $paid = ! empty($p['paid']);
        $total = (float) ($p['total'] ?? 0);

        return [
            'wordpress_site_id' => $site->id,
            'source'            => $site->orderSource(),
            'source_order_id'   => (string) $p['id'],
            'source_website'    => $site->site_url,
            'reference_no'      => (string) ($p['number'] ?? $p['id']),
            'shipping_name'     => $name !== '' ? $name : 'Unknown',
            'shipping_phone'    => $phone,
            'email'             => $pick('email') ?: null,
            'shipping_address'  => $address !== '' ? $address : '-',
            'shipping_district' => $district ?: null,
            'shipping_area'     => $pick('city') ?: null,
            'subtotal'          => (float) ($p['subtotal'] ?? $total),
            'discount'          => (float) ($p['discount_total'] ?? 0),
            'shipping_charge'   => (float) ($p['shipping_total'] ?? 0),
            'total'             => $total,
            'payment_method'    => self::paymentMethod((string) ($p['payment_method'] ?? '')),
            'payment_status'    => $paid ? 'paid' : 'unpaid',
            'paid_amount'       => $paid ? $total : 0,
            'notes'             => ($p['customer_note'] ?? '') !== '' ? $p['customer_note'] : null,
            'user_ip'           => $p['customer_ip'] ?? null,
            'utm_source'        => $attr['utm_source'] ?? null,
            'utm_medium'        => $attr['utm_medium'] ?? null,
            'utm_campaign'      => $attr['utm_campaign'] ?? null,
            'platform'          => strtolower((string) ($attr['utm_source'] ?? $attr['source_type'] ?? '')) ?: null,
        ];
    }

    private static function replaceItems(Order $order, array $p): void
    {
        OrderItem::where('order_id', $order->id)->delete();

        // Landing pages (CartFlows) sell a simple product and ask the size in a checkout
        // field (billing_size). That size picks the matching size variant in the CRM.
        $orderSize = trim((string) ($p['size'] ?? $p['billing_size'] ?? ''));

        foreach ($p['items'] ?? [] as $item) {
            if ($orderSize !== '' && ! \App\Support\SizeMatcher::chosenSize((array) ($item['attributes'] ?? []))) {
                $item['attributes'] = array_merge((array) ($item['attributes'] ?? []), ['Size' => $orderSize]);
            }
            [$product, $variant] = self::matchProduct($item);
            // No variant, or only the simple product's size-less slot: use the chosen size if the CRM has it.
            if ($product && (! $variant || $variant->isDefaultSlot())) {
                $sized = \App\Support\SizeMatcher::find($product, \App\Support\SizeMatcher::chosenSize((array) ($item['attributes'] ?? [])));
                $variant = $sized ?? $variant;
            }
            $qty = max(1, (int) ($item['qty'] ?? 1));
            $lineTotal = (float) ($item['total'] ?? 0);

            OrderItem::create([
                'order_id'     => $order->id,
                'product_id'   => $product?->id,
                'variant_id'   => $variant?->id,
                'product_name' => (string) ($item['name'] ?? 'Unknown product'),
                'variant_info' => self::variantInfo($item) ?: null,
                'qty'          => $qty,
                'unit_price'   => (float) ($item['price'] ?? ($lineTotal / $qty)),
                'discount'     => max(0, (float) ($item['subtotal'] ?? $lineTotal) - $lineTotal),
                'total'        => $lineTotal,
            ]);
        }
    }

    /** Most exact first: Woo variation id, Woo product id, then SKU. */
    private static function matchProduct(array $item): array
    {
        $variant = null;
        if (! empty($item['variation_id'])) {
            $variant = ProductVariant::where('wp_variation_id', (int) $item['variation_id'])->first();
        }
        if (! $variant && ! empty($item['sku'])) {
            $variant = ProductVariant::where('sku', $item['sku'])->first();
        }
        if ($variant) {
            return [$variant->product, $variant];
        }

        $product = null;
        if (! empty($item['product_id'])) {
            $product = Product::where('wp_product_id', (int) $item['product_id'])->first();
        }
        if (! $product && ! empty($item['sku'])) {
            $product = Product::where('sku', $item['sku'])->first();
        }

        return [$product, null];
    }

    private static function variantInfo(array $item): string
    {
        return collect($item['attributes'] ?? [])
            ->filter(fn ($v) => $v !== '' && $v !== null)
            ->map(fn ($v, $k) => is_string($k) && ! is_numeric($k) ? ucfirst(str_replace(['pa_', '-', '_'], ['', ' ', ' '], $k)) . ': ' . $v : $v)
            ->implode(' / ');
    }

    private static function wooCancelled(array $p): bool
    {
        return in_array($p['status'] ?? '', ['cancelled', 'refunded', 'failed'], true);
    }

    private static function paymentMethod(string $method): string
    {
        $m = strtolower($method);

        return match (true) {
            $m === '' || str_contains($m, 'cod') || str_contains($m, 'cash') => 'cod',
            str_contains($m, 'bkash')  => 'bkash',
            str_contains($m, 'nagad')  => 'nagad',
            str_contains($m, 'rocket') => 'rocket',
            default                    => 'card',
        };
    }
}
