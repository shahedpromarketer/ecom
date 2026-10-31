<?php

namespace Tests\Feature;

use App\Models\CourierSetting;
use App\Models\Order;
use App\Models\Tenant;
use App\Models\User;
use App\Services\CustomerCheck\CustomerCheck;
use App\Support\Tenancy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CustomerCheckTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $shop;
    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();
        $this->shop = Tenant::create(['name' => 'Shop', 'email' => 's@test.local', 'status' => 'active', 'plan' => 'basic']);
        $this->owner = User::factory()->create(['tenant_id' => $this->shop->id, 'role' => 'admin']);
    }

    private function order(Tenant $shop, string $phone, string $status): Order
    {
        return Tenancy::as($shop->id, fn () => Order::create(['shipping_name' => 'C', 'shipping_phone' => $phone, 'shipping_address' => 'Dhaka',
            'subtotal' => 100, 'total' => 100, 'status' => $status]));
    }

    public function test_own_history_counts_every_shop_without_names(): void
    {
        $other = Tenant::create(['name' => 'Other', 'email' => 'o@test.local', 'status' => 'active', 'plan' => 'basic']);
        $this->order($this->shop, '01711223344', 'delivered');
        $this->order($other, '+880 1711-223344', 'delivered');
        $this->order($other, '8801711223344', 'returned');
        $this->order($other, '01711223344', 'pending');         // not finished: not counted
        $this->order($other, '01711223344', 'cancelled');       // cancelled before the courier: not counted
        $this->order($other, '01999999999', 'returned');        // someone else

        $all = CustomerCheck::own('01711223344', null);
        $this->assertSame(['total' => 3, 'success' => 2, 'failed' => 1, 'rate' => 66.7], $all);
        $this->assertSame(1, CustomerCheck::own('01711223344', $this->shop->id)['total']);
    }

    public function test_couriers_are_asked_with_the_shops_logins_and_the_card_shows_it(): void
    {
        Tenancy::as($this->shop->id, function () {
            CourierSetting::create(['courier' => 'pathao', 'is_active' => true, 'api_key' => 'c', 'api_secret' => 's',
                'extra_config' => ['username' => 'p@x.test', 'password' => 'pw', 'store_id' => '1']]);
            CourierSetting::create(['courier' => 'steadfast', 'is_active' => true, 'api_key' => 'k', 'api_secret' => 's',
                'extra_config' => ['panel_email' => 'sf@x.test', 'panel_password' => 'pw2']]);
        });
        Http::fake([
            'merchant.pathao.com/api/v1/login' => Http::response(['access_token' => 'T']),
            'merchant.pathao.com/api/v1/user/success' => Http::response(['data' => ['customer' => ['total_delivery' => 4, 'successful_delivery' => 4], 'customer_rating' => 'excellent_customer']]),
            'steadfast.com.bd/login' => Http::sequence()
                ->push('<form><input type="hidden" name="_token" value="CSRF1" autocomplete="off"></form>', 200, ['Content-Type' => 'text/html'])
                ->push('<html>dashboard</html>', 200, ['Content-Type' => 'text/html']),
            'steadfast.com.bd/user/frauds/check/*' => Http::response(['delivery_ratio' => 80, 'cancellation_ratio' => 20, 'fraud_reports' => 2, 'delivered_count' => 4, 'cancelled_count' => 1]),
        ]);

        $c = CustomerCheck::for($this->shop->id, '+8801711223344');
        $this->assertSame(4, $c['couriers']['pathao']['success']);
        $this->assertSame('Excellent', $c['couriers']['pathao']['rating']);
        $this->assertSame(5, $c['couriers']['steadfast']['total']);
        $this->assertSame(1, $c['couriers']['steadfast']['failed']);
        $this->assertSame(2, $c['couriers']['steadfast']['reports']);
        $this->assertSame('RedX no longer shares this', $c['couriers']['redx']['note']);
        $this->assertSame(['total' => 9, 'success' => 8, 'failed' => 1, 'rate' => 88.9], $c['all']);

        // Steadfast login sent the page's token; the check asked for the normalised number.
        Http::assertSent(fn ($r) => $r->url() === 'https://steadfast.com.bd/login' && $r->method() === 'POST' && $r['_token'] === 'CSRF1' && $r['email'] === 'sf@x.test');
        Http::assertSent(fn ($r) => str_ends_with($r->url(), '/user/frauds/check/01711223344'));

        // Cached: asking again does not call the couriers.
        $calls = count(Http::recorded());
        CustomerCheck::for($this->shop->id, '01711223344');
        $this->assertCount($calls, Http::recorded());

        $order = $this->order($this->shop, '01711223344', 'confirmed');
        $this->actingAs($this->owner)->get(route('admin.orders.show', $order))->assertOk()->assertSee('Courier Success Rate');
        $this->get(route('admin.orders.customerCheck', $order))->assertOk()
            ->assertSee('Pathao')->assertSee('To: <b>5</b>', false)->assertSee('RedX no longer shares this')->assertSee('88.9%');
    }

    public function test_a_courier_failing_never_breaks_the_card(): void
    {
        Tenancy::as($this->shop->id, fn () => CourierSetting::create(['courier' => 'pathao', 'is_active' => true, 'api_key' => 'c', 'api_secret' => 's',
            'extra_config' => ['username' => 'p@x.test', 'password' => 'bad']]));
        Tenancy::as($this->shop->id, fn () => CourierSetting::create(['courier' => 'steadfast', 'is_active' => true, 'api_key' => 'k', 'api_secret' => 's']));
        Http::fake(['merchant.pathao.com/*' => Http::response(['message' => 'Invalid credentials'], 401)]);

        $c = CustomerCheck::for($this->shop->id, '01711223344', true);
        $this->assertStringContainsString('login failed', $c['couriers']['pathao']['note']);
        $this->assertStringContainsString('add the panel login', $c['couriers']['steadfast']['note']);
        $this->assertSame(0, $c['all']['total']);
        $this->assertNull($c['all']['rate']);
    }
    public function test_last_numbers_are_kept_when_a_courier_stops_answering(): void
    {
        $sf = Tenancy::as($this->shop->id, fn () => CourierSetting::create(['courier' => 'steadfast', 'is_active' => true, 'api_key' => 'k', 'api_secret' => 's',
            'extra_config' => ['panel_email' => 'sf@x.test', 'panel_password' => 'pw']]));
        Http::fake([
            'steadfast.com.bd/login' => Http::sequence()
                ->push('<input type="hidden" name="_token" value="T1" autocomplete="off">', 200, ['Content-Type' => 'text/html'])->push('ok', 200, ['Content-Type' => 'text/html']),
            'steadfast.com.bd/user/frauds/check/*' => Http::sequence()
                ->push(['delivered_count' => 7, 'cancelled_count' => 1, 'fraud_reports' => 0])
                ->push(['error' => 'This feature is no longer available.'], 403),
        ]);

        $first = CustomerCheck::for($this->shop->id, '01711223344', true);
        $this->assertSame(8, $first['couriers']['steadfast']['total']);
        $this->assertArrayNotHasKey('saved_at', $first['couriers']['steadfast']);

        // Steadfast stops sharing: the saved numbers are shown, marked with their date.
        $later = CustomerCheck::for($this->shop->id, '01711223344', true);
        $s = $later['couriers']['steadfast'];
        $this->assertSame([8, 7, 1], [$s['total'], $s['success'], $s['failed']]);
        $this->assertNotEmpty($s['saved_at']);
        $this->assertStringContainsString('no longer available', $s['why']);
        $this->assertSame(8, $later['all']['total']);

        // Even with the Steadfast account removed.
        $sf->delete();
        $this->assertSame(7, CustomerCheck::for($this->shop->id, '01711223344', true)['couriers']['steadfast']['success']);

        // Another shop never sees this shop's saved numbers.
        $other = Tenant::create(['name' => 'Other', 'email' => 'o@test.local', 'status' => 'active', 'plan' => 'basic']);
        $this->assertNull(CustomerCheck::for($other->id, '01711223344', true)['couriers']['steadfast']['total']);

        $order = $this->order($this->shop, '01711223344', 'confirmed');
        $this->actingAs($this->owner)->get(route('admin.orders.customerCheck', $order))->assertOk()->assertSee('Saved ' . now()->format('d M Y'));
    }
}
