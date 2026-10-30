<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Tenant;
use App\Models\WordPressSite;
use App\Support\PluginSignature;
use App\Support\SizeMatcher;
use App\Support\Tenancy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * CartFlows landing pages sell a simple WooCommerce product and ask the size in a
 * "billing_size" checkout field. In the CRM the product has sizes; the order picks one.
 */
class LandingPageSizeTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $shop;
    private WordPressSite $store;

    protected function setUp(): void
    {
        parent::setUp();
        $this->shop = Tenant::create(['name' => 'Shop', 'email' => 'shop@test.local', 'status' => 'active', 'plan' => 'basic']);
        $this->store = Tenancy::as($this->shop->id, fn () => WordPressSite::create([
            'name' => 'Landing', 'site_url' => 'https://landing.test', 'type' => 'woocommerce', 'api_key' => WordPressSite::generateApiKey(),
            'is_active' => true, 'plugin_connected' => true, 'rest_url' => 'https://landing.test/wp-json',
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

    private function inShop(callable $fn)
    {
        return Tenancy::as($this->shop->id, $fn);
    }

    private function simpleProduct(int $stock = 50): void
    {
        $this->signed('products', ['id' => 900, 'name' => 'Panjabi', 'type' => 'simple', 'status' => 'publish', 'sku' => 'PJ-1',
            'regular_price' => '1200', 'manage_stock' => true, 'stock_quantity' => $stock, 'stock_status' => 'instock', 'images' => []])->assertOk();
    }

    /** What the shop owner does in the CRM: add sizes to the product. */
    private function addSizes(array $sizes): void
    {
        $this->inShop(function () use ($sizes) {
            $p = Product::where('wp_product_id', 900)->first();
            foreach ($sizes as $size => $stock) {
                ProductVariant::create(['product_id' => $p->id, 'size' => $size, 'stock' => $stock, 'is_active' => true]);
            }
        });
    }

    private function order(int $id, string $size, array $over = []): void
    {
        $this->signed('orders', array_replace_recursive(['id' => $id, 'number' => (string) $id, 'status' => 'processing', 'total' => 1260,
            'payment_method' => 'cod', 'size' => $size,
            'billing' => ['first_name' => 'Rahim', 'phone' => '01711111111', 'address_1' => 'Mirpur, Dhaka'],
            'items' => [['product_id' => 900, 'variation_id' => 0, 'name' => 'Panjabi', 'sku' => 'PJ-1', 'qty' => 2, 'price' => 600,
                'subtotal' => 1200, 'total' => 1200, 'attributes' => []]]], $over))->assertSuccessful();
    }

    public function test_size_words_are_understood(): void
    {
        foreach (['L' => 'l', ' large ' => 'l', 'Size: L' => 'l', 'এল' => 'l', 'X-Large' => 'xl', '2XL' => 'xxl', 'XXL' => 'xxl',
                     'সাইজ: এম' => 'm', '৪২' => '42', 'Free Size' => 'free', 'Medium' => 'm'] as $in => $out) {
            $this->assertSame($out, SizeMatcher::normalize($in), $in);
        }
        $this->assertSame('L', SizeMatcher::chosenSize(['Size' => 'L']));
        $this->assertSame('XL', SizeMatcher::chosenSize(['Size: XL']));
        $this->assertSame('M', SizeMatcher::chosenSize(['M']));
        $this->assertSame('42', SizeMatcher::chosenSize([], '42'));
        $this->assertNull(SizeMatcher::chosenSize(['Color' => 'Black']));
    }

    public function test_billing_size_picks_the_crm_size_variant(): void
    {
        $this->simpleProduct();
        $this->addSizes(['M' => 10, 'L' => 10, 'XL' => 10]);
        $this->order(5001, 'Large');

        $this->inShop(function () {
            $item = OrderItem::first();
            $this->assertSame('L', $item->variant->size);
            $this->assertSame('Size: Large', $item->variant_info);
            $this->assertSame(Product::where('wp_product_id', 900)->value('id'), $item->product_id);
        });
    }

    public function test_bengali_size_and_item_level_size(): void
    {
        $this->simpleProduct();
        $this->addSizes(['M' => 10, 'L' => 10]);
        $this->order(5002, '', ['items' => [['attributes' => ['সাইজ' => 'এম']]]]);

        $this->inShop(fn () => $this->assertSame('M', OrderItem::first()->variant->size));
    }

    public function test_unknown_size_keeps_the_product_and_the_text(): void
    {
        $this->simpleProduct();
        $this->addSizes(['M' => 10]);
        $this->order(5003, 'XXL');

        $this->inShop(function () {
            $item = OrderItem::first();
            $this->assertNotNull($item->product_id);
            $this->assertNotSame('M', $item->variant?->size);
            $this->assertSame('Size: XXL', $item->variant_info);
        });
    }

    public function test_product_sync_keeps_crm_sizes(): void
    {
        $this->simpleProduct(50);
        $this->addSizes(['M' => 7, 'L' => 9]);

        // The store re-sends the product and reports a stock change.
        $this->simpleProduct(40);
        $this->signed('stock', ['product_id' => 900, 'manage_stock' => true, 'stock_quantity' => 35, 'stock_status' => 'instock'])->assertOk();

        $this->inShop(function () {
            $p = Product::where('wp_product_id', 900)->first();
            $sizes = $p->variants()->whereNotNull('size')->where('size', '!=', '')->get()->keyBy('size');
            $this->assertTrue($sizes['M']->is_active);
            $this->assertSame(7, $sizes['M']->stock);
            $this->assertSame(9, $sizes['L']->stock);
            // The size-less slot is switched off once the product has sizes (it is not a choice any more).
            $this->assertFalse((bool) $p->variants()->defaultSlot()->value('is_active'));
        });
    }

    public function test_shipping_counts_crm_size_stock(): void
    {
        $this->simpleProduct();
        $this->addSizes(['L' => 10]);
        $this->order(5004, 'L');

        $this->inShop(function () {
            $order = Order::first();
            $order->update(['status' => 'shipped']);
            $this->assertSame(8, ProductVariant::where('size', 'L')->value('stock'));

            $order->update(['status' => 'returned']);
            $this->assertSame(10, ProductVariant::where('size', 'L')->value('stock'));
        });
    }

    public function test_custom_api_item_size(): void
    {
        $custom = Tenancy::as($this->shop->id, fn () => WordPressSite::create([
            'name' => 'Site', 'site_url' => 'https://site.test', 'type' => 'custom', 'api_key' => WordPressSite::generateApiKey(), 'is_active' => true,
        ]));
        $this->inShop(function () {
            $p = Product::create(['name' => 'Polo', 'slug' => 'polo', 'price' => 500, 'sku' => 'POLO']);
            ProductVariant::create(['product_id' => $p->id, 'size' => 'XL', 'stock' => 5, 'is_active' => true]);
        });

        $this->withToken(PluginSignature::connectionKey($custom->id, $custom->api_key))->postJson('/api/v1/orders', [
            'external_id' => 'W-1', 'customer' => ['name' => 'R', 'phone' => '01712345678', 'address' => 'Dhaka road 1'],
            'items' => [['name' => 'Polo', 'sku' => 'POLO', 'size' => 'x-large', 'qty' => 1, 'price' => 500]],
        ])->assertCreated();

        $this->inShop(fn () => $this->assertSame('XL', OrderItem::first()->variant->size));
    }
}
