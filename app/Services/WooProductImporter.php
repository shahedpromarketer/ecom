<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Models\WordPressSite;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Creates/updates CRM products from the Ecom Pilot plugin's product payload.
 *
 * Stock of these products is owned by WooCommerce (the plugin pushes every
 * change), so the CRM does not deduct stock for them itself — see OrderObserver.
 *
 * Must run with the store's tenant pinned (Tenancy::set / Tenancy::as).
 */
class WooProductImporter
{
    /** Not managed / unlimited stock in WooCommerce shows as this in the CRM. */
    public const UNLIMITED_STOCK = 999;

    /** @return array{0: Product, 1: string} created | updated */
    public static function upsert(WordPressSite $site, array $p): array
    {
        return StoreStockPusher::withoutPushing(fn () => DB::transaction(function () use ($p, $site) {
            $wpId = (string) $p['id'];

            $product = Product::withTrashed()->where('wp_product_id', $wpId)
                    ->where(fn ($q) => $q->where('wordpress_site_id', $site->id)->orWhereNull('wordpress_site_id'))->first()
                ?? (! empty($p['sku']) ? Product::withTrashed()->whereNull('wp_product_id')->where('sku', $p['sku'])->first() : null);

            $created = ! $product;
            $product ??= new Product();

            if ($product->trashed()) {
                $product->restore();
            }

            $regular = (float) ($p['regular_price'] ?? 0);
            $sale = isset($p['sale_price']) && $p['sale_price'] !== '' && (float) $p['sale_price'] > 0 ? (float) $p['sale_price'] : null;
            if ($regular <= 0) {
                // Variable products: use the cheapest variation as the base price.
                $regular = (float) collect($p['variations'] ?? [])->map(fn ($v) => (float) ($v['regular_price'] ?? 0))->filter()->min();
            }

            $product->fill([
                'wp_product_id'     => $wpId,
                'wordpress_site_id' => $site->id,
                'name'              => (string) ($p['name'] ?? 'Unnamed product'),
                'slug'              => $product->slug ?: self::uniqueSlug((string) ($p['slug'] ?? $p['name'] ?? $wpId), $product->id),
                'sku'               => ($p['sku'] ?? '') !== '' ? $p['sku'] : $product->sku,
                'price'             => $regular,
                'regular_price'     => $regular,
                'sale_price'        => $sale,
                'status'            => ($p['status'] ?? 'publish') === 'publish' ? 'active' : 'inactive',
                'short_description' => isset($p['short_description']) ? Str::limit(strip_tags((string) $p['short_description']), 1000, '') : $product->short_description,
            ]);
            $product->save();

            self::syncCategories($product, (array) ($p['categories'] ?? []));
            self::syncImages($product, (array) ($p['images'] ?? []));
            self::syncVariants($product, $p, $regular);

            return [$product, $created ? 'created' : 'updated'];
        }));
    }

    /**
     * WooCommerce reported a stock change (its own checkout reduces stock when an order is placed).
     * The CRM owns stock — it only goes down when a parcel goes to the courier — so the CRM keeps
     * its number and sends it back to the store. Returns the product, or null if not imported yet.
     */
    public static function updateStock(array $s): ?Product
    {
        return Product::where('wp_product_id', (string) ($s['product_id'] ?? ''))->first();
    }
    private static function syncVariants(Product $product, array $p, float $basePrice): void
    {
        $keep = [];

        if (($p['type'] ?? 'simple') === 'variable' && ! empty($p['variations'])) {
            foreach ($p['variations'] as $v) {
                [$size, $color] = self::sizeAndColor((array) ($v['attributes'] ?? []));
                $price = (float) (($v['sale_price'] ?? '') !== '' && (float) $v['sale_price'] > 0 ? $v['sale_price'] : ($v['regular_price'] ?? $basePrice));

                $variant = ProductVariant::where('wp_variation_id', (int) $v['id'])->first()
                    ?? new ProductVariant(['product_id' => $product->id]);
                $variant->fill([
                    'product_id'      => $product->id,
                    'wp_variation_id' => (int) $v['id'],
                    'sku'             => ($v['sku'] ?? '') !== '' ? $v['sku'] : null,
                    'size'            => $size,
                    'color'           => $color,
                    // The CRM owns stock: the store's number is only the starting point for a new variation.
                    ...($variant->exists ? [] : ['stock' => self::stock($v['stock_quantity'] ?? null, $v['manage_stock'] ?? false, $v['stock_status'] ?? 'instock')]),
                    'extra_price'     => round($price - ($product->sale_price ?? $basePrice), 2),
                    'is_active'       => ($v['status'] ?? 'publish') === 'publish',
                ])->save();
                $keep[] = $variant->id;
            }
        } else {
            // Simple product. Sizes added in the CRM (e.g. a CartFlows landing-page product sold
            // as simple, size asked at checkout) are the CRM's own: keep them as they are.
            $crmSizes = $product->variants()->whereNull('wp_variation_id')->get()->filter->isCrmOnly();
            $keep = $crmSizes->pluck('id')->all();

            // One size-less slot carries the store's stock — only while the CRM has no sizes for it.
            $default = $product->variants()->defaultSlot()->first();
            if ($crmSizes->where('is_active', true)->isEmpty()) {
                $variant = $default ?? new ProductVariant(['product_id' => $product->id]);
                $variant->fill([
                    'product_id' => $product->id,
                    'sku'        => ($p['sku'] ?? '') !== '' ? $p['sku'] : null,
                    ...($variant->exists ? [] : ['stock' => self::stock($p['stock_quantity'] ?? null, $p['manage_stock'] ?? false, $p['stock_status'] ?? 'instock')]),
                    'extra_price'=> 0,
                    'is_active'  => true,
                ])->save();
                $keep[] = $variant->id;
            }
        }

        // Variations deleted in WooCommerce: switch off (keep them for past orders).
        $product->variants()->whereNotIn('id', $keep)->update(['is_active' => false]);
    }

    private static function syncCategories(Product $product, array $names): void
    {
        $ids = [];
        foreach ($names as $name) {
            $name = trim(html_entity_decode((string) $name));
            if ($name === '' || strtolower($name) === 'uncategorized') {
                continue;
            }
            $category = Category::firstOrCreate(
                ['name' => $name],
                ['slug' => self::uniqueCategorySlug($name), 'is_active' => true]
            );
            $ids[] = $category->id;
        }
        $product->categories()->sync($ids);
    }

    private static function syncImages(Product $product, array $urls): void
    {
        $urls = array_values(array_filter($urls, fn ($u) => is_string($u) && str_starts_with($u, 'https://')));
        if (! $urls) {
            return;
        }

        // Replace only images that came from the store (URLs); keep uploaded files.
        ProductImage::where('product_id', $product->id)->where('image_path', 'like', 'http%')->delete();
        foreach (array_slice($urls, 0, 8) as $i => $url) {
            ProductImage::create([
                'product_id' => $product->id,
                'image_path' => mb_substr($url, 0, 255),
                'is_primary' => $i === 0 && ! ProductImage::where('product_id', $product->id)->where('is_primary', true)->exists(),
                'sort_order' => $i + 1,
            ]);
        }
    }

    /** WooCommerce attributes like {"pa_size":"L","Color":"Black"} → [size, color]. */
    private static function sizeAndColor(array $attributes): array
    {
        $size = $color = null;
        $other = [];
        foreach ($attributes as $key => $value) {
            $k = strtolower((string) $key);
            if ($value === '' || $value === null) {
                continue;
            }
            if (str_contains($k, 'size')) {
                $size = (string) $value;
            } elseif (str_contains($k, 'color') || str_contains($k, 'colour')) {
                $color = (string) $value;
            } else {
                $other[] = (string) $value;
            }
        }
        // Unknown attribute names: show them in "size" so the variant is still recognisable.
        if (! $size && $other) {
            $size = implode(' / ', $other);
        }

        return [$size ? mb_substr($size, 0, 50) : null, $color ? mb_substr($color, 0, 50) : null];
    }

    private static function stock($qty, $managed, string $status): int
    {
        if ($managed && $qty !== null && $qty !== '') {
            return max(0, (int) $qty);
        }

        // The store does not count this product: the shop counts it in the CRM (Inventory), starting at 0.
        return 0;
    }

    private static function uniqueSlug(string $base, ?int $ignoreId): string
    {
        $slug = Str::slug($base) ?: Str::random(8);
        $candidate = $slug;
        $i = 2;
        while (Product::withTrashed()->where('slug', $candidate)->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))->exists()) {
            $candidate = $slug . '-' . $i++;
        }

        return $candidate;
    }

    private static function uniqueCategorySlug(string $name): string
    {
        $slug = Str::slug($name) ?: Str::random(6);
        $candidate = $slug;
        $i = 2;
        while (Category::withTrashed()->where('slug', $candidate)->exists()) {
            $candidate = $slug . '-' . $i++;
        }

        return $candidate;
    }
}
