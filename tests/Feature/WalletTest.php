<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Tenant;
use App\Models\User;
use App\Models\WalletRechargeRequest;
use App\Models\WalletTransaction;
use App\Models\WordPressSite;
use App\Services\Wallet;
use App\Support\PluginSignature;
use App\Support\Tenancy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class WalletTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $shop;
    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        $this->shop = Tenant::create(['name' => 'Paying shop', 'email' => 'p@test.local', 'status' => 'active', 'plan' => 'basic', 'order_charge' => 0.45]);
        $this->owner = User::factory()->create(['tenant_id' => $this->shop->id, 'role' => 'admin']);
    }

    private function order(?Tenant $shop = null): Order
    {
        return Tenancy::as(($shop ?? $this->shop)->id, fn () => Order::create(['order_number' => 'W' . random_int(10000, 99999),
            'shipping_name' => 'R', 'shipping_phone' => '01711111111', 'shipping_address' => 'Dhaka', 'subtotal' => 500, 'total' => 500, 'status' => 'pending']));
    }

    public function test_the_fee_is_taken_once_when_an_order_is_confirmed(): void
    {
        Wallet::credit($this->shop, 10, 'recharge');
        $order = $this->order();
        $this->assertEquals(10, (float) $this->shop->fresh()->wallet_balance);   // pending: nothing yet

        Tenancy::as($this->shop->id, fn () => $order->update(['status' => 'confirmed']));
        $this->assertEquals(9.55, (float) $this->shop->fresh()->wallet_balance);

        Tenancy::as($this->shop->id, function () use ($order) {
            $order->update(['status' => 'shipped']);
            $order->update(['status' => 'pending']);
            $order->update(['status' => 'confirmed']);   // back and forth: still one fee
        });
        Wallet::chargeOrder($order->fresh());
        $this->assertEquals(9.55, (float) $this->shop->fresh()->wallet_balance);
        $this->assertSame(1, WalletTransaction::withoutGlobalScopes()->where('type', 'order')->count());
    }

    public function test_orders_cancelled_before_confirming_cost_nothing_and_skipping_confirm_still_counts(): void
    {
        Wallet::credit($this->shop, 10, 'recharge');
        $cancelled = $this->order();
        Tenancy::as($this->shop->id, fn () => $cancelled->update(['status' => 'cancelled']));
        $this->assertEquals(10, (float) $this->shop->fresh()->wallet_balance);

        $straight = $this->order();
        Tenancy::as($this->shop->id, fn () => $straight->update(['status' => 'processing']));   // confirmed by moving on
        $this->assertEquals(9.55, (float) $this->shop->fresh()->wallet_balance);
    }

    public function test_bulk_status_change_charges_each_order(): void
    {
        Wallet::credit($this->shop, 10, 'recharge');
        $a = $this->order();
        $b = $this->order();
        $this->actingAs($this->owner)->postJson(route('admin.orders.bulkStatus'), ['ids' => [$a->id, $b->id], 'status' => 'confirmed'])->assertOk();

        $this->assertEquals(9.10, (float) $this->shop->fresh()->wallet_balance);
    }

    public function test_shops_charged_on_arrival(): void
    {
        $this->shop->update(['charge_on' => 'created']);
        Wallet::credit($this->shop, 10, 'recharge');
        $order = $this->order();
        $this->assertEquals(9.55, (float) $this->shop->fresh()->wallet_balance);
        Tenancy::as($this->shop->id, fn () => $order->update(['status' => 'confirmed']));
        $this->assertEquals(9.55, (float) $this->shop->fresh()->wallet_balance);
    }
    public function test_free_shops_are_never_charged(): void
    {
        $free = Tenant::create(['name' => 'Free', 'email' => 'f@test.local', 'status' => 'active', 'plan' => 'basic', 'order_charge' => 0]);
        $this->order($free);

        $this->assertEquals(0, (float) $free->fresh()->wallet_balance);
        $this->assertFalse($free->fresh()->walletLocked());
    }

    public function test_orders_still_arrive_with_an_empty_wallet(): void
    {
        $this->shop->update(['charge_on' => 'created']);
        $site = Tenancy::as($this->shop->id, fn () => WordPressSite::create(['name' => 'Woo', 'site_url' => 'https://s.test', 'type' => 'woocommerce',
            'api_key' => WordPressSite::generateApiKey(), 'is_active' => true]));
        $body = json_encode(['id' => 1, 'number' => '1', 'status' => 'processing', 'total' => 500, 'payment_method' => 'cod',
            'billing' => ['first_name' => 'R', 'phone' => '01711111111', 'address_1' => 'Dhaka'],
            'items' => [['product_id' => 0, 'name' => 'X', 'qty' => 1, 'price' => 500, 'subtotal' => 500, 'total' => 500]]]);
        $sig = PluginSignature::sign($site->api_key, $body);
        $this->call('POST', '/api/v1/store/orders', [], [], [], $this->transformHeadersToServerVars([
            'Content-Type' => 'application/json', 'Accept' => 'application/json', 'X-EP-Site' => (string) $site->id,
            'X-EP-Timestamp' => (string) $sig['timestamp'], 'X-EP-Signature' => $sig['signature']]), $body)->assertSuccessful();

        $this->assertSame(1, Order::withoutGlobalScopes()->count());
        $this->assertEquals(-0.45, (float) $this->shop->fresh()->wallet_balance);
    }

    public function test_empty_wallet_locks_order_work_only(): void
    {
        $this->actingAs($this->owner);
        $this->get(route('admin.orders.index'))->assertRedirect(route('admin.wallet.index'));
        $this->getJson(route('admin.orders.searchLive'))->assertStatus(402)->assertJson(['wallet_locked' => true]);
        $this->get(route('admin.dashboard'))->assertOk()->assertSee('Wallet empty');
        $this->get(route('admin.wallet.index'))->assertOk()->assertSee('Your balance is empty');

        Wallet::credit($this->shop, 50, 'recharge');
        $this->actingAs($this->owner->fresh())->get(route('admin.orders.index'))->assertOk();   // fresh: as on the next real request
    }

    public function test_recharge_request_approve_and_reject(): void
    {
        $this->actingAs($this->owner)->post(route('admin.wallet.request'), [
            'amount' => 500, 'method' => 'bkash', 'note' => 'From 01700000000',
            'screenshot' => UploadedFile::fake()->image('pay.jpg'),
        ])->assertSessionHas('success');
        $this->post(route('admin.wallet.request'), ['amount' => 500, 'method' => 'bkash',
            'screenshot' => UploadedFile::fake()->image('pay.jpg')])->assertSessionHas('error');   // double click: sent once

        $r = WalletRechargeRequest::withoutGlobalScopes()->first();
        Storage::disk('local')->assertExists($r->screenshot_path);
        $this->get(route('admin.wallet.screenshot', $r))->assertOk();

        $super = User::factory()->create(['is_super_admin' => true, 'role' => 'admin', 'tenant_id' => null]);
        $this->actingAs($super)->get(route('super.wallet'))->assertOk()->assertSee('From 01700000000')->assertSee('View screenshot');
        $this->get(route('super.wallet.screenshot', $r->id))->assertOk();
        $this->post(route('super.wallet.approve', $r->id), ['amount' => 450])->assertSessionHas('success');   // received less
        $this->post(route('super.wallet.approve', $r->id))->assertSessionHas('error');                       // never twice

        $this->assertEquals(450, (float) $this->shop->fresh()->wallet_balance);
        $this->assertSame('approved', $r->fresh()->status);

        $this->actingAs($this->owner)->post(route('admin.wallet.request'), ['amount' => 100, 'method' => 'nagad',
            'screenshot' => UploadedFile::fake()->image('p.png')]);
        $r2 = WalletRechargeRequest::withoutGlobalScopes()->where('method', 'nagad')->first();
        $this->actingAs($super)->post(route('super.wallet.reject', $r2->id), ['reason' => 'Not received'])->assertSessionHas('success');
        $this->assertSame('rejected', $r2->fresh()->status);
        $this->assertEquals(450, (float) $this->shop->fresh()->wallet_balance);
    }

    public function test_other_shops_cannot_see_a_screenshot(): void
    {
        $this->actingAs($this->owner)->post(route('admin.wallet.request'), ['amount' => 100, 'method' => 'bkash',
            'screenshot' => UploadedFile::fake()->image('p.png')]);
        $r = WalletRechargeRequest::withoutGlobalScopes()->first();

        $other = Tenant::create(['name' => 'O', 'email' => 'o@test.local', 'status' => 'active', 'plan' => 'basic']);
        $this->actingAs(User::factory()->create(['tenant_id' => $other->id, 'role' => 'admin']))
            ->get(route('admin.wallet.screenshot', $r))->assertNotFound();
    }

    public function test_super_admin_sets_charge_and_adjusts_balance(): void
    {
        $super = User::factory()->create(['is_super_admin' => true, 'role' => 'admin', 'tenant_id' => null]);
        $this->actingAs($super)->post(route('super.wallet.tenant', $this->shop->id), ['order_charge' => 0.5, 'charge_on' => 'confirmed', 'adjust' => 100, 'note' => 'Welcome credit'])
            ->assertSessionHas('success');
        $this->post(route('super.wallet.payment-info'), ['payment_info' => 'bKash: 01700000000'])->assertSessionHas('success');

        $this->assertEquals(0.5, (float) $this->shop->fresh()->order_charge);
        $this->assertEquals(100, (float) $this->shop->fresh()->wallet_balance);
        $this->actingAs($this->owner)->get(route('admin.wallet.index'))->assertSee('bKash: 01700000000')->assertSee('৳100.00');
        $this->get(route('admin.wallet.history', ['day' => now()->toDateString()]))->assertSee('Welcome credit');
    }

    public function test_paying_shops_are_not_logged_out_by_a_plan_date(): void
    {
        $this->shop->update(['plan_expires_at' => now()->subDay()]);
        Wallet::credit($this->shop, 10, 'recharge');

        $this->actingAs($this->owner)->get(route('admin.dashboard'))->assertOk();
    }
    public function test_each_confirmed_orders_fee_is_visible(): void
    {
        Wallet::credit($this->shop, 10, 'recharge');
        $order = $this->order();
        $this->actingAs($this->owner->fresh())->get(route('admin.orders.show', $order))->assertSee('not charged yet');

        $this->patch(route('admin.orders.updateStatus', $order), ['status' => 'confirmed']);
        $this->actingAs($this->owner->fresh())->get(route('admin.orders.show', $order))->assertSee('৳0.45 charged');
        $this->get(route('admin.wallet.index'))->assertSee('৳9.55');
        $this->get(route('admin.wallet.history', ['day' => now()->toDateString()]))->assertSee('#' . $order->order_number)->assertSee('Confirmed');

        $super = User::factory()->create(['is_super_admin' => true, 'role' => 'admin', 'tenant_id' => null]);
        $this->actingAs($super)->get(route('super.wallet'))->assertSee('History →');
        $this->get(route('super.wallet.history', $this->shop->id))->assertOk()
            ->assertSee($order->order_number)->assertSee('Confirmed')->assertSee('৳0.45');
    }
    public function test_wallet_screens_show_balance_health_costs_and_daily_history(): void
    {
        Wallet::credit($this->shop, 100, 'recharge', 'Recharge via bKash');
        $orders = collect(range(1, 3))->map(fn () => $this->order());
        foreach ($orders as $o) {
            Tenancy::as($this->shop->id, fn () => $o->update(['status' => 'confirmed']));
        }
        // ৳98.65 left ≈ 219 orders: green
        $this->actingAs($this->owner->fresh())->get(route('admin.wallet.index'))->assertOk()
            ->assertSee('৳98.65')->assertSee('219')->assertSee('Balance is good')->assertSee('৳1.35')->assertSee('walletChart');
        $this->get(route('admin.wallet.history'))->assertOk()->assertSee('Today')->assertSee('−৳1.35')->assertSee('+৳100.00');
        $this->get(route('admin.wallet.history', ['day' => now()->toDateString()]))->assertOk()
            ->assertSee('#' . $orders[0]->order_number)->assertSee('Confirmed');

        $this->shop->forceFill(['wallet_balance' => 30])->save();   // ≈ 66 orders: yellow
        $this->actingAs($this->owner->fresh())->get(route('admin.wallet.index'))->assertSee('getting low');
        $this->shop->forceFill(['wallet_balance' => 5])->save();   // ≈ 11 orders: red
        $this->actingAs($this->owner->fresh())->get(route('admin.wallet.index'))->assertSee('almost empty');

        $super = User::factory()->create(['is_super_admin' => true, 'role' => 'admin', 'tenant_id' => null]);
        $this->actingAs($super)->get(route('super.tenants.show', $this->shop->id))->assertOk()
            ->assertSee('Wallet')->assertSee('almost empty')->assertSee($orders[0]->order_number)->assertSee('Last 14 days');
        $this->get(route('super.wallet'))->assertOk()->assertSee('almost empty');
        $this->get(route('super.wallet.history', $this->shop->id))->assertOk()->assertSee($orders[0]->order_number);
    }
}
