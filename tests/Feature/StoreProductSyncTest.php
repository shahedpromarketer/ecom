<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Tenant;
use App\Models\WordPressSite;
use App\Services\StoreProductSync;
use App\Support\PluginSignature;
use App\Support\Tenancy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class StoreProductSyncTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $shop;
    private WordPressSite $store;

    protected function setUp(): void
    {
        parent::setUp();
        $this->shop = Tenant::create(['name' => 'Shop', 'email' => 'shop@test.local', 'status' => 'active', 'plan' => 'basic']);
        $this->store = Tenancy::as($this->shop->id, fn () => WordPressSite::create([
            'name' => 'Woo', 'site_url' => 'https://store.test', 'type' => 'woocommerce', 'api_key' => WordPressSite::generateApiKey(),
            'is_active' => true, 'plugin_connected' => true, 'rest_url' => 'https://store.test/wp-json',
        ]));
    }

    private function signed(string $path, array $data)
    {
        $body = json_encode($data);
        $sig = PluginSignature::sign($this->store->api_key, $body);

        return $this->call('POST', '/api/v1/store/' . $path, [], [], [], $this->transformHeadersToServerVars([
            'Content-Type' => 'application/json', 'Accept' => 'application/json', 'X-EP-Site' => (string) $this->store->id,
            'X-EP-Timestamp' => (string) $sig['timestamp'], 'X-EP-Signature' => $sig['signature'],
        ]), $body);
    }

    private function jacket(array $over = []): array
    {
        return array_replace([
            'id' => 501, 'name' => 'Makelee Jacket – Black', 'slug' => 'makelee-jacket-black', 'type' => 'variable', 'status' => 'publish',
            'sku' => 'WO-68628', 'regular_price' => '', 'sale_price' => '', 'manage_stock' => false, 'stock_quantity' => null, 'stock_status' => 'instock',
            'categories' => ['Jacket', 'Uncategorized'], 'images' => ['https://store.test/img/black.jpg', 'https://store.test/img/back.jpg'],
            'variations' => [
                ['id' => 601, 'sku' => 'WO-68628-L', 'status' => 'publish', 'regular_price' => '1450', 'sale_price' => '1350', 'manage_stock' => true, 'stock_quantity' => 7, 'stock_status' => 'instock', 'attributes' => ['Size' => 'L']],
                ['id' => 602, 'sku' => 'WO-68628-XL', 'status' => 'publish', 'regular_price' => '1450', 'sale_price' => '', 'manage_stock' => true, 'stock_quantity' => 0, 'stock_status' => 'outofstock', 'attributes' => ['Size' => 'XL', 'Color' => 'Black']],
            ],
        ], $over);
    }

    private function inShop(callable $fn)
    {
        return Tenancy::as($this->shop->id, $fn);
    }

    public function test_variable_product_with_variations_categories_and_images(): void
    {
        $this->signed('products', $this->jacket())->assertOk()->assertJson(['result' => 'created']);

        $this->inShop(function () {
            $p = Product::with(['variants', 'images', 'categories'])->first();
            $this->assertSame('501', (string) $p->wp_product_id);
            $this->assertSame('Makelee Jacket – Black', $p->name);
            $this->assertEquals(1450, $p->price);            // cheapest variation regular price
            $this->assertSame('active', $p->status);
            $this->assertSame(['Jacket'], $p->categories->pluck('name')->all());   // "Uncategorized" skipped
            $this->assertSame(2, $p->images->count());
            $this->assertSame('https://store.test/img/black.jpg', $p->images->firstWhere('is_primary', true)->url);

            $l = $p->variants->firstWhere('wp_variation_id', 601);
            $this->assertSame('L', $l->size);
            $this->assertSame(0, $l->stock);   // stock is counted in the CRM, not taken from the store
            $this->assertEquals(-100, $l->extra_price);       // 1350 sale vs 1450 base
            $xl = $p->variants->firstWhere('wp_variation_id', 602);
            $this->assertSame(['XL', 'Black'], [$xl->size, $xl->color]);
            $this->assertSame(0, $xl->stock);
        });
    }

    public function test_resend_updates_and_removed_variations_are_switched_off(): void
    {
        $this->signed('products', $this->jacket());
        $j = $this->jacket(['name' => 'Makelee Jacket (new name)']);
        unset($j['variations'][1]);
        $this->signed('products', $j)->assertJson(['result' => 'updated']);

        $this->inShop(function () {
            $this->assertSame(1, Product::count());
            $this->assertSame('Makelee Jacket (new name)', Product::first()->name);
            $this->assertFalse(ProductVariant::where('wp_variation_id', 602)->first()->is_active);
        });
    }

    public function test_simple_product_gets_one_variant(): void
    {
        $this->signed('products', ['id' => 700, 'name' => 'Cap', 'type' => 'simple', 'status' => 'publish', 'sku' => 'CAP-1',
            'regular_price' => '300', 'sale_price' => '250', 'manage_stock' => true, 'stock_quantity' => 12, 'stock_status' => 'instock', 'images' => []]);

        $this->inShop(function () {
            $p = Product::with('variants')->first();
            $this->assertEquals(250, $p->sale_price);
            $this->assertSame(1, $p->variants->count());
            $this->assertSame(0, $p->variants->first()->stock);   // stock is entered in the CRM, not copied
        });
    }

    public function test_store_stock_is_not_used(): void
    {
        $this->signed('products', $this->jacket());   // the store says 7
        $this->signed('stock', ['product_id' => 501, 'variation_id' => 601, 'manage_stock' => true, 'stock_quantity' => 3, 'stock_status' => 'instock'])
            ->assertOk()->assertJson(['updated' => false]);

        // Stock is counted in the CRM only: it starts at 0 until the shop enters it.
        $this->inShop(fn () => $this->assertSame(0, ProductVariant::where('wp_variation_id', 601)->value('stock')));
    }

    public function test_resending_a_product_keeps_crm_stock(): void
    {
        $this->signed('products', $this->jacket());
        $this->inShop(fn () => ProductVariant::where('wp_variation_id', 601)->update(['stock' => 4]));
        $this->signed('products', $this->jacket());

        $this->inShop(fn () => $this->assertSame(4, ProductVariant::where('wp_variation_id', 601)->value('stock')));
    }

    public function test_store_product_stock_goes_down_at_the_courier_and_never_touches_the_store(): void
    {
        \Illuminate\Support\Facades\Http::fake();
        $this->signed('products', $this->jacket());

        $this->inShop(function () {
            $variant = ProductVariant::where('wp_variation_id', 601)->first();
            $variant->update(['stock' => 7]);   // the shop entered its stock
            $order = Order::create(['shipping_name' => 'A', 'shipping_phone' => '01700000000', 'shipping_address' => 'Dhaka',
                'subtotal' => 1350, 'total' => 1350, 'payment_method' => 'cod', 'status' => 'confirmed']);
            OrderItem::create(['order_id' => $order->id, 'product_id' => $variant->product_id, 'variant_id' => $variant->id,
                'product_name' => 'Jacket', 'qty' => 2, 'unit_price' => 1350, 'total' => 2700]);

            $this->assertSame(7, $variant->fresh()->stock);   // confirmed: nothing taken yet
            $order->update(['status' => 'shipped']);
            $this->assertSame(5, $variant->fresh()->stock);   // handed to the courier
            $order->update(['status' => 'cancelled']);
            $this->assertSame(7, $variant->fresh()->stock);
        });
        \Illuminate\Support\Facades\Http::assertNothingSent();
    }

    public function test_shops_without_stock_still_ship_everything(): void
    {
        $this->signed('products', $this->jacket());

        $this->inShop(function () {
            $variant = ProductVariant::where('wp_variation_id', 601)->first();   // stock 0, never entered
            $order = Order::create(['shipping_name' => 'A', 'shipping_phone' => '01700000000', 'shipping_address' => 'Dhaka',
                'subtotal' => 1350, 'total' => 1350, 'payment_method' => 'cod', 'status' => 'confirmed']);
            OrderItem::create(['order_id' => $order->id, 'product_id' => $variant->product_id, 'variant_id' => $variant->id,
                'product_name' => 'Jacket', 'qty' => 3, 'unit_price' => 1350, 'total' => 4050]);

            $order->update(['status' => 'shipped']);
            $this->assertSame('shipped', $order->fresh()->status);
            $this->assertSame(0, $variant->fresh()->stock);   // never below 0
            $order->update(['status' => 'returned']);
            $this->assertSame('returned', $order->fresh()->status);
        });
    }
    public function test_order_line_without_a_variant_uses_the_only_one(): void
    {
        $this->inShop(function () {
            $p = Product::create(['name' => 'Cap', 'slug' => 'cap', 'price' => 300]);
            $v = ProductVariant::create(['product_id' => $p->id, 'stock' => 10, 'is_active' => true]);
            $order = Order::create(['shipping_name' => 'A', 'shipping_phone' => '01700000000', 'shipping_address' => 'Dhaka',
                'subtotal' => 300, 'total' => 300, 'payment_method' => 'cod', 'status' => 'confirmed']);
            $item = OrderItem::create(['order_id' => $order->id, 'product_id' => $p->id, 'product_name' => 'Cap', 'qty' => 3, 'unit_price' => 100, 'total' => 300]);

            $order->update(['status' => 'shipped']);
            $this->assertSame(7, $v->fresh()->stock);
            $this->assertSame($v->id, $item->fresh()->variant_id);
        });
    }

    public function test_orders_link_to_synced_products(): void
    {
        $this->signed('products', $this->jacket());
        $this->signed('orders', ['id' => 9001, 'number' => '9001', 'status' => 'processing', 'total' => 1350, 'payment_method' => 'cod',
            'billing' => ['first_name' => 'R', 'phone' => '01711111111', 'address_1' => 'Dhaka'],
            'items' => [['product_id' => 501, 'variation_id' => 601, 'name' => 'Makelee Jacket – Black - L', 'qty' => 1, 'price' => 1350, 'subtotal' => 1350, 'total' => 1350]]]);

        $this->inShop(function () {
            $item = OrderItem::first();
            $this->assertNotNull($item->product_id);
            $this->assertSame(601, $item->variant->wp_variation_id);
        });
    }

    public function test_pull_walks_all_pages_through_the_plugin(): void
    {
        Http::fake([
            'store.test/wp-json/ecom-pilot/v1/products' => Http::sequence()
                ->push(['ok' => true, 'total_pages' => 2, 'products' => [$this->jacket()]])
                ->push(['ok' => true, 'total_pages' => 2, 'products' => [$this->jacket(['id' => 502, 'name' => 'Second', 'slug' => 'second', 'variations' => []])]]),
        ]);

        $r = StoreProductSync::pull($this->store);

        $this->assertSame(['created' => 2, 'updated' => 0, 'failed' => 0, 'error' => null], $r);
        Http::assertSentCount(2);
        $this->inShop(fn () => $this->assertSame(2, Product::count()));
    }

    public function test_category_slugs_can_repeat_across_shops(): void
    {
        $this->signed('products', $this->jacket());
        $other = Tenant::create(['name' => 'O', 'email' => 'o@test.local', 'status' => 'active', 'plan' => 'basic']);
        Tenancy::as($other->id, fn () => Category::create(['name' => 'Jacket', 'slug' => 'jacket', 'is_active' => true]));

        $this->assertSame(2, Category::withoutGlobalScopes()->where('slug', 'jacket')->count());
    }
}
