<?php

namespace Tests\Feature;

use App\Models\BlacklistedPhone;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Tenant;
use App\Models\User;
use App\Models\WordPressSite;
use App\Services\StoreStatusPusher;
use App\Support\PluginSignature;
use App\Support\Tenancy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class StorePluginTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $shop;
    private WordPressSite $store;

    protected function setUp(): void
    {
        parent::setUp();
        $this->shop = Tenant::create(['name' => 'Shop', 'email' => 'shop@test.local', 'status' => 'active', 'plan' => 'basic']);
        $this->store = Tenancy::as($this->shop->id, fn () => WordPressSite::create([
            'name' => 'WearOn test', 'site_url' => 'https://admin.wearon.test', 'type' => 'woocommerce',
            'api_key' => WordPressSite::generateApiKey(), 'is_active' => true,
        ]));
    }

    private function signed(string $path, array $data, ?string $secret = null, ?int $ts = null)
    {
        $body = json_encode($data);
        $sig = PluginSignature::sign($secret ?? $this->store->api_key, $body, $ts);

        return $this->call('POST', '/api/v1/store/' . $path, [], [], [], $this->transformHeadersToServerVars([
            'Content-Type'   => 'application/json',
            'Accept'         => 'application/json',
            'X-EP-Site'      => (string) $this->store->id,
            'X-EP-Timestamp' => (string) $sig['timestamp'],
            'X-EP-Signature' => $sig['signature'],
        ]), $body);
    }

    private function wooOrder(array $over = []): array
    {
        return array_replace_recursive([
            'id' => 4021, 'number' => '4021', 'status' => 'processing', 'created_at' => '2026-10-30T10:15:00+06:00',
            'total' => 1440, 'subtotal' => 1350, 'discount_total' => 0, 'shipping_total' => 90,
            'payment_method' => 'cod', 'paid' => false, 'customer_note' => 'Call before delivery', 'customer_ip' => '103.1.1.1',
            'billing' => ['first_name' => 'Rahim', 'last_name' => 'Uddin', 'phone' => '+8801712345678', 'address_1' => 'House 7, Road 3',
                'address_2' => 'Mirpur 10', 'city' => 'Mirpur', 'state' => 'BD-13', 'state_name' => 'Dhaka'],
            'items' => [['product_id' => 77, 'variation_id' => 78, 'name' => 'Makelee Jacket – Black', 'sku' => 'WO-68628-L',
                'qty' => 1, 'price' => 1350, 'subtotal' => 1350, 'total' => 1350, 'attributes' => ['Size' => 'L']]],
            'attribution' => ['utm_source' => 'facebook'],
        ], $over);
    }

    public function test_connection_key_format(): void
    {
        $this->assertSame($this->store->id . '.' . $this->store->api_key, PluginSignature::connectionKey($this->store->id, $this->store->api_key));
    }

    public function test_connect_marks_the_store_connected(): void
    {
        $this->signed('connect', ['plugin_version' => '2.0.0', 'rest_url' => 'https://admin.wearon.test/wp-json/'])
            ->assertOk()->assertJson(['ok' => true, 'shop' => 'Shop']);

        $store = $this->store->fresh();
        $this->assertTrue($store->plugin_connected);
        $this->assertSame('https://admin.wearon.test/wp-json', $store->rest_url);
        $this->assertNotNull($store->last_seen_at);
    }

    public function test_bad_or_old_signatures_are_refused(): void
    {
        $this->signed('connect', [], 'wrong-secret')->assertUnauthorized();
        $this->signed('connect', [], null, time() - 3600)->assertUnauthorized();
        $this->postJson('/api/v1/store/connect', [])->assertUnauthorized();
    }

    public function test_order_is_imported_into_the_stores_shop(): void
    {
        Tenancy::as($this->shop->id, function () {
            $p = Product::create(['name' => 'Makelee Jacket', 'slug' => 'makelee', 'price' => 1350, 'status' => 'active', 'wp_product_id' => 77]);
            ProductVariant::create(['product_id' => $p->id, 'size' => 'L', 'stock' => 5, 'price' => 1350, 'is_active' => true, 'wp_variation_id' => 78]);
        });

        $this->signed('orders', $this->wooOrder())->assertCreated()->assertJson(['ok' => true, 'result' => 'created']);

        $order = Order::withoutGlobalScopes()->with('items')->first();
        $this->assertSame($this->shop->id, $order->tenant_id);
        $this->assertSame($this->store->id, $order->wordpress_site_id);
        $this->assertSame('4021', $order->source_order_id);
        $this->assertSame('01712345678', $order->shipping_phone);
        $this->assertSame('Rahim Uddin', $order->shipping_name);
        $this->assertSame('House 7, Road 3 Mirpur 10', $order->shipping_address);
        $this->assertSame('Dhaka', $order->shipping_district);
        $this->assertSame('pending', $order->status);
        $this->assertEquals(1440, $order->total);
        $this->assertSame('facebook', $order->utm_source);
        $this->assertNotNull($order->items->first()->variant_id);   // matched by Woo variation id
        $this->assertSame('Size: L', $order->items->first()->variant_info);
        $this->assertSame(1, Customer::withoutGlobalScopes()->count());
    }

    public function test_resending_updates_instead_of_duplicating(): void
    {
        $this->signed('orders', $this->wooOrder())->assertCreated();
        $this->signed('orders', $this->wooOrder(['billing' => ['address_1' => 'New address']]))->assertOk()->assertJson(['result' => 'updated']);

        $this->assertSame(1, Order::withoutGlobalScopes()->count());
        $this->assertStringStartsWith('New address', Order::withoutGlobalScopes()->first()->shipping_address);
    }

    public function test_cancel_in_woocommerce_cancels_untouched_order_only(): void
    {
        $this->signed('orders', $this->wooOrder());
        $this->signed('orders', $this->wooOrder(['status' => 'cancelled']));
        $this->assertSame('cancelled', Order::withoutGlobalScopes()->first()->status);

        // Shipped orders are not overridden by WooCommerce.
        $this->signed('orders', $this->wooOrder(['id' => 5000, 'number' => '5000']));
        Order::withoutGlobalScopes()->where('source_order_id', '5000')->update(['status' => 'shipped']);
        $this->signed('orders', $this->wooOrder(['id' => 5000, 'number' => '5000', 'status' => 'cancelled']));
        $this->assertSame('shipped', Order::withoutGlobalScopes()->where('source_order_id', '5000')->value('status'));
    }

    public function test_draft_checkouts_are_skipped(): void
    {
        $this->signed('orders', $this->wooOrder(['status' => 'checkout-draft']))->assertOk()->assertJson(['result' => 'skipped']);
        $this->assertSame(0, Order::withoutGlobalScopes()->count());
    }

    public function test_checkout_validation_uses_the_stores_blacklist(): void
    {
        Tenancy::as($this->shop->id, fn () => BlacklistedPhone::create(['phone' => '01799999999', 'reason' => 'fraud']));

        $this->signed('validate', ['phone' => '+880 1799-999999'])->assertOk()->assertJson(['allowed' => false, 'reason' => 'phone_blocked']);
        $this->signed('validate', ['phone' => '01711111111'])->assertOk()->assertJson(['allowed' => true]);
    }

    public function test_status_change_in_crm_is_pushed_to_woocommerce(): void
    {
        Http::fake(['*' => Http::response(['ok' => true], 200)]);
        $this->store->update(['plugin_connected' => true, 'rest_url' => 'https://admin.wearon.test/wp-json']);
        $this->signed('orders', $this->wooOrder());
        $order = Order::withoutGlobalScopes()->first();

        $this->actingAs(User::factory()->create(['tenant_id' => $this->shop->id, 'role' => 'admin']));
        $order->update(['status' => 'shipped']);
        StoreStatusPusher::push($order->fresh());

        Http::assertSent(function ($request) {
            $data = json_decode($request->body(), true);

            return $request->url() === 'https://admin.wearon.test/wp-json/ecom-pilot/v1/order-status'
                && $data['order_id'] === 4021 && $data['status'] === 'ep-in-courier'
                && PluginSignature::verify($this->store->api_key, $request->body(), $request->header('X-EP-Timestamp')[0], $request->header('X-EP-Signature')[0]);
        });
    }

    public function test_statuses_without_a_woocommerce_match_are_not_sent(): void
    {
        $this->assertNull(StoreStatusPusher::wooStatusFor($this->store, 'pending'));
        $this->assertSame('completed', StoreStatusPusher::wooStatusFor($this->store, 'delivered'));

        $this->store->update(['status_map' => ['delivered' => 'none']]);
        $this->assertNull(StoreStatusPusher::wooStatusFor($this->store->fresh(), 'delivered'));
    }

    public function test_inactive_store_is_refused(): void
    {
        $this->store->update(['is_active' => false]);
        $this->signed('orders', $this->wooOrder())->assertForbidden();
    }
}
