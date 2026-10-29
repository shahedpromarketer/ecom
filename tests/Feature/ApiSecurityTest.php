<?php

namespace Tests\Feature;

use App\Models\ApiToken;
use App\Models\Order;
use App\Models\Tenant;
use App\Models\User;
use App\Models\WordPressSite;
use App\Support\Tenancy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiSecurityTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $shopA;
    private Tenant $shopB;

    protected function setUp(): void
    {
        parent::setUp();
        $this->shopA = Tenant::create(['name' => 'A', 'email' => 'a@test.local', 'status' => 'active', 'plan' => 'basic']);
        $this->shopB = Tenant::create(['name' => 'B', 'email' => 'b@test.local', 'status' => 'active', 'plan' => 'basic']);
    }

    private function tokenFor(Tenant $shop): string
    {
        $user = User::factory()->create(['tenant_id' => $shop->id, 'role' => 'admin']);
        $this->actingAs($user);
        $plain = ApiToken::generate('test')->plainToken;
        auth()->logout();

        return $plain;
    }

    private function orderPayload(string $sourceId): array
    {
        return [
            'source_order_id' => $sourceId,
            'customer_name'   => 'Rahim', 'customer_phone' => '01711111111', 'address' => 'Dhaka',
            'total' => 500, 'items' => [['product_name' => 'Shirt', 'qty' => 1, 'unit_price' => 500]],
        ];
    }

    public function test_tokens_are_stored_hashed(): void
    {
        $plain = $this->tokenFor($this->shopA);

        $this->assertDatabaseMissing('api_tokens', ['token' => $plain]);
        $this->assertDatabaseHas('api_tokens', ['token' => hash('sha256', $plain), 'tenant_id' => $this->shopA->id]);
    }

    public function test_api_orders_land_in_the_tokens_shop_only(): void
    {
        $tokenA = $this->tokenFor($this->shopA);
        $tokenB = $this->tokenFor($this->shopB);

        $this->withToken($tokenA)->postJson('/api/orders/receive', $this->orderPayload('wp-1'))->assertCreated();

        $this->assertSame($this->shopA->id, Order::withoutGlobalScopes()->first()->tenant_id);

        // Shop B's token cannot list shop A's orders.
        $this->withToken($tokenB)->getJson('/api/orders')->assertOk()->assertJsonPath('total', 0);
        $this->withToken($tokenA)->getJson('/api/orders')->assertOk()->assertJsonPath('total', 1);
    }

    public function test_same_source_order_id_in_two_shops_is_not_a_duplicate(): void
    {
        $this->withToken($this->tokenFor($this->shopA))->postJson('/api/orders/receive', $this->orderPayload('1001'))->assertCreated();
        $this->withToken($this->tokenFor($this->shopB))->postJson('/api/orders/receive', $this->orderPayload('1001'))->assertCreated();

        $this->assertSame(2, Order::withoutGlobalScopes()->count());
    }

    public function test_token_in_query_string_is_rejected(): void
    {
        $plain = $this->tokenFor($this->shopA);

        $this->getJson('/api/ping?api_token=' . $plain)->assertUnauthorized();
        $this->withToken($plain)->getJson('/api/ping')->assertOk();
    }

    public function test_legacy_plain_text_token_still_works_and_gets_hashed(): void
    {
        $user = User::factory()->create(['tenant_id' => $this->shopA->id, 'role' => 'admin']);
        Tenancy::as($this->shopA->id, fn () => ApiToken::forceCreate(['name' => 'old', 'token' => 'legacy-plain-token', 'is_active' => true, 'created_by' => $user->id]));

        $this->withToken('legacy-plain-token')->getJson('/api/ping')->assertOk();
        $this->assertDatabaseHas('api_tokens', ['token' => hash('sha256', 'legacy-plain-token')]);
    }

    private function wooSite(Tenant $shop, string $url): WordPressSite
    {
        return Tenancy::as($shop->id, fn () => WordPressSite::create([
            'name' => 'Shop', 'site_url' => $url, 'api_key' => WordPressSite::generateApiKey(), 'is_active' => true,
        ]));
    }

    private function postWebhook(string $source, array $body, ?string $secret)
    {
        $raw = json_encode($body);
        $headers = [
            'X-WC-Webhook-Topic'  => 'order.created',
            'X-WC-Webhook-Source' => $source,
            'Content-Type'        => 'application/json',
        ];
        if ($secret !== null) {
            $headers['X-WC-Webhook-Signature'] = base64_encode(hash_hmac('sha256', $raw, $secret, true));
        }

        return $this->call('POST', '/api/woocommerce/webhook', [], [], [], $this->transformHeadersToServerVars($headers), $raw);
    }

    private function wooOrder(): array
    {
        return ['id' => 555, 'number' => '555', 'total' => '900', 'payment_method' => 'cod',
            'billing' => ['first_name' => 'Karim', 'phone' => '01722222222', 'address_1' => 'Mirpur'],
            'line_items' => [['name' => 'Polo', 'quantity' => 1, 'price' => 900, 'subtotal' => '900']]];
    }

    public function test_woocommerce_webhook_without_valid_signature_is_rejected(): void
    {
        $site = $this->wooSite($this->shopA, 'https://shop-a.test');

        $this->postWebhook('https://shop-a.test/', $this->wooOrder(), null)->assertUnauthorized();
        $this->postWebhook('https://shop-a.test/', $this->wooOrder(), 'wrong-secret')->assertUnauthorized();

        $this->assertSame(0, Order::withoutGlobalScopes()->count());
    }

    public function test_signed_woocommerce_webhook_creates_order_in_the_sites_shop(): void
    {
        $this->wooSite($this->shopA, 'https://shop-a.test');
        $siteB = $this->wooSite($this->shopB, 'https://shop-b.test');

        $this->postWebhook('https://shop-b.test/', $this->wooOrder(), $siteB->api_key)->assertOk();

        $order = Order::withoutGlobalScopes()->first();
        $this->assertSame($this->shopB->id, $order->tenant_id);
    }

    public function test_lookalike_host_does_not_match_another_site(): void
    {
        $site = $this->wooSite($this->shopA, 'https://shop.test');

        // "evilshop.test" contains "shop.test" — the old LIKE match would have accepted it.
        $this->postWebhook('https://evilshop.test/', $this->wooOrder(), $site->api_key)->assertUnauthorized();
    }
}
