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

    public function test_every_new_order_costs_the_fee_once(): void
    {
        Wallet::credit($this->shop, 10, 'recharge');
        $order = $this->order();
        Wallet::chargeOrder($order);   // a second call (e.g. a resend) never charges twice

        $this->assertEquals(9.55, (float) $this->shop->fresh()->wallet_balance);
        $this->assertSame(1, WalletTransaction::withoutGlobalScopes()->where('type', 'order')->count());
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
            'amount' => 500, 'method' => 'bkash', 'transaction_id' => 'TX123', 'sender' => '01700000000',
            'screenshot' => UploadedFile::fake()->image('pay.jpg'),
        ])->assertSessionHas('success');
        $this->post(route('admin.wallet.request'), ['amount' => 500, 'method' => 'bkash', 'transaction_id' => 'TX123',
            'screenshot' => UploadedFile::fake()->image('pay.jpg')])->assertSessionHas('error');   // same transaction twice

        $r = WalletRechargeRequest::withoutGlobalScopes()->first();
        Storage::disk('local')->assertExists($r->screenshot_path);
        $this->get(route('admin.wallet.screenshot', $r))->assertOk();

        $super = User::factory()->create(['is_super_admin' => true, 'role' => 'admin', 'tenant_id' => null]);
        $this->actingAs($super)->get(route('super.wallet'))->assertOk()->assertSee('TX123');
        $this->get(route('super.wallet.screenshot', $r->id))->assertOk();
        $this->post(route('super.wallet.approve', $r->id), ['amount' => 450])->assertSessionHas('success');   // received less
        $this->post(route('super.wallet.approve', $r->id))->assertSessionHas('error');                       // never twice

        $this->assertEquals(450, (float) $this->shop->fresh()->wallet_balance);
        $this->assertSame('approved', $r->fresh()->status);

        $this->actingAs($this->owner)->post(route('admin.wallet.request'), ['amount' => 100, 'method' => 'nagad', 'transaction_id' => 'TX9',
            'screenshot' => UploadedFile::fake()->image('p.png')]);
        $r2 = WalletRechargeRequest::withoutGlobalScopes()->where('transaction_id', 'TX9')->first();
        $this->actingAs($super)->post(route('super.wallet.reject', $r2->id), ['reason' => 'Not received'])->assertSessionHas('success');
        $this->assertSame('rejected', $r2->fresh()->status);
        $this->assertEquals(450, (float) $this->shop->fresh()->wallet_balance);
    }

    public function test_other_shops_cannot_see_a_screenshot(): void
    {
        $this->actingAs($this->owner)->post(route('admin.wallet.request'), ['amount' => 100, 'method' => 'bkash', 'transaction_id' => 'T1',
            'screenshot' => UploadedFile::fake()->image('p.png')]);
        $r = WalletRechargeRequest::withoutGlobalScopes()->first();

        $other = Tenant::create(['name' => 'O', 'email' => 'o@test.local', 'status' => 'active', 'plan' => 'basic']);
        $this->actingAs(User::factory()->create(['tenant_id' => $other->id, 'role' => 'admin']))
            ->get(route('admin.wallet.screenshot', $r))->assertNotFound();
    }

    public function test_super_admin_sets_charge_and_adjusts_balance(): void
    {
        $super = User::factory()->create(['is_super_admin' => true, 'role' => 'admin', 'tenant_id' => null]);
        $this->actingAs($super)->post(route('super.wallet.tenant', $this->shop->id), ['order_charge' => 0.5, 'adjust' => 100, 'note' => 'Welcome credit'])
            ->assertSessionHas('success');
        $this->post(route('super.wallet.payment-info'), ['payment_info' => 'bKash: 01700000000'])->assertSessionHas('success');

        $this->assertEquals(0.5, (float) $this->shop->fresh()->order_charge);
        $this->assertEquals(100, (float) $this->shop->fresh()->wallet_balance);
        $this->actingAs($this->owner)->get(route('admin.wallet.index'))->assertSee('bKash: 01700000000')->assertSee('Welcome credit');
    }

    public function test_paying_shops_are_not_logged_out_by_a_plan_date(): void
    {
        $this->shop->update(['plan_expires_at' => now()->subDay()]);
        Wallet::credit($this->shop, 10, 'recharge');

        $this->actingAs($this->owner)->get(route('admin.dashboard'))->assertOk();
    }
}
