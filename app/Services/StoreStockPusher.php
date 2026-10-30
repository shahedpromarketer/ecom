<?php

namespace App\Services;

use App\Models\Product;
use App\Models\SyncLog;
use App\Models\WordPressSite;
use App\Support\PluginSignature;
use Illuminate\Support\Facades\Http;

/**
 * The CRM owns stock (it goes down when a parcel goes to the courier). Whenever a synced
 * product's stock changes here, its numbers are sent to the WooCommerce store so the
 * website shows the CRM's stock.
 */
class StoreStockPusher
{

    /** While the store itself is sending us a product, there is nothing to send back. */
    private static bool $paused = false;

    public static function withoutPushing(callable $fn): mixed
    {
        $was = self::$paused;
        self::$paused = true;
        try {
            return $fn();
        } finally {
            self::$paused = $was;
        }
    }

    public static function queue(?int $productId): void
    {
        if (self::$paused || ! $productId) {
            return;
        }
        // One push per product per request (the list lives with this request, not the whole process).
        if (! app()->bound('ep.stock-queue')) {
            app()->instance('ep.stock-queue', new \ArrayObject());
        }
        $queued = app('ep.stock-queue');
        if (isset($queued[$productId])) {
            return;
        }
        $queued[$productId] = true;
        \Illuminate\Support\defer(function () use ($productId, $queued) {
            unset($queued[$productId]);
            self::push(Product::withoutGlobalScopes()->find($productId));
        });
    }

    /** @return array<int, array{product_id:int, variation_id:int, stock:int}> what the store should show */
    public static function payload(Product $product): array
    {
        $variants = $product->variants()->get();
        $storeVariations = $variants->filter(fn ($v) => $v->wp_variation_id);

        if ($storeVariations->isNotEmpty()) {
            return $storeVariations->map(fn ($v) => [
                'product_id' => (int) $product->wp_product_id, 'variation_id' => (int) $v->wp_variation_id, 'stock' => max(0, (int) $v->stock),
            ])->values()->all();
        }

        // Simple in the store: sizes added in the CRM add up; otherwise the size-less slot.
        $sizes = $variants->filter(fn ($v) => $v->isCrmOnly() && $v->is_active);
        $stock = $sizes->isNotEmpty() ? $sizes->sum('stock') : (int) ($variants->first(fn ($v) => $v->isDefaultSlot())?->stock ?? 0);

        return [['product_id' => (int) $product->wp_product_id, 'variation_id' => 0, 'stock' => max(0, (int) $stock)]];
    }

    public static function push(?Product $product): bool
    {
        if (! $product || ! $product->wp_product_id) {
            return false;
        }

        $site = $product->wordpress_site_id
            ? WordPressSite::withoutGlobalScopes()->find($product->wordpress_site_id)
            : WordPressSite::withoutGlobalScopes()->where('tenant_id', $product->tenant_id)
                ->whereIn('type', ['woocommerce', 'wordpress'])->where('plugin_connected', true)->first();
        if (! $site || ! $site->is_active || ! $site->plugin_connected) {
            return false;
        }

        $body = json_encode(['items' => self::payload($product)]);
        $sig = PluginSignature::sign((string) $site->api_key, $body);
        $url = rtrim($site->rest_url ?: rtrim($site->site_url, '/') . '/wp-json', '/') . '/ecom-pilot/v1/stock-set';

        try {
            $res = Http::timeout(8)
                ->withOptions(['verify' => (bool) config('services.woocommerce.verify_ssl', true)])
                ->withHeaders(['X-EP-Site' => (string) $site->id, 'X-EP-Timestamp' => (string) $sig['timestamp'], 'X-EP-Signature' => $sig['signature']])
                ->withBody($body, 'application/json')
                ->post($url);
            $ok = $res->successful();
            $msg = $ok ? null : 'Stock push failed: HTTP ' . $res->status() . ' ' . mb_substr($res->body(), 0, 200);
        } catch (\Throwable $e) {
            $ok = false;
            $msg = 'Stock push failed: ' . mb_substr($e->getMessage(), 0, 200);
        }

        if (! $ok) {
            \App\Support\Tenancy::as($site->tenant_id, fn () => SyncLog::create(['site_id' => $site->id, 'status' => 'failed', 'message' => $msg . ' (' . $product->name . ')']));
        }

        return $ok;
    }
}
