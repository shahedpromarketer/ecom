<?php

namespace Tests\Feature;

use App\Models\CourierEvent;
use App\Models\CourierSetting;
use App\Models\CourierShipment;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Couriers\CourierTracker;
use App\Support\Tenancy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CourierTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $shop;
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->shop  = Tenant::create(['name' => 'Shop', 'email' => 'shop@test.local', 'status' => 'active', 'plan' => 'basic']);
        $this->admin = User::factory()->create(['tenant_id' => $this->shop->id, 'role' => 'admin']);
        $this->actingAs($this->admin);
    }

    private function account(string $courier, array $over = []): CourierSetting
    {
        $keys = [
            'steadfast' => ['api_key' => 'sf-key', 'api_secret' => 'sf-secret'],
            'pathao'    => ['api_key' => 'cid', 'api_secret' => 'csec', 'extra_config' => ['username' => 'u@x.test', 'password' => 'p', 'store_id' => '77']],
            'redx'      => ['api_key' => 'rx-token'],
            'carrybee'  => ['api_key' => 'cb-id', 'api_secret' => 'cb-sec', 'extra_config' => ['client_context' => 'ctx', 'store_id' => 's1']],
            'paperfly'  => ['api_key' => 'pf-key', 'extra_config' => ['username' => 'pu', 'password' => 'pp', 'store_name' => 'Shop']],
        ][$courier];

        return CourierSetting::create(['courier' => $courier, 'is_active' => true, 'is_default' => true] + $over + $keys);
    }

    private function order(array $over = []): Order
    {
        $order = Order::create($over + [
            'order_number' => 'EP' . random_int(10000, 99999), 'shipping_name' => 'Rahim', 'shipping_phone' => '01712345678',
            'shipping_address' => 'House 7, Road 3, Mirpur 10', 'shipping_district' => 'Dhaka', 'shipping_area' => 'Mirpur',
            'subtotal' => 1350, 'shipping_charge' => 90, 'total' => 1440, 'payment_method' => 'cod', 'status' => 'confirmed',
        ]);
        OrderItem::create(['order_id' => $order->id, 'product_name' => 'Jacket', 'variant_info' => 'L', 'qty' => 2, 'unit_price' => 675, 'total' => 1350]);

        return $order;
    }

    // ── Accounts ───────────────────────────────────────────────────

    public function test_accounts_are_encrypted_and_secrets_kept_when_left_empty(): void
    {
        $this->post(route('admin.courier.store'), ['courier' => 'steadfast', 'name' => 'Main', 'api_key' => 'AAA', 'api_secret' => 'BBB', 'is_active' => 1])
            ->assertSessionHas('success');
        $this->post(route('admin.courier.store'), ['courier' => 'steadfast', 'name' => 'Second', 'api_key' => 'CCC', 'api_secret' => 'DDD', 'is_active' => 1]);

        $a = CourierSetting::where('name', 'Main')->first();
        $this->assertTrue($a->is_default);
        $this->assertFalse(CourierSetting::where('name', 'Second')->first()->is_default);
        $this->assertSame('AAA', $a->api_key);
        $this->assertStringNotContainsString('AAA', DB::table('courier_settings')->where('id', $a->id)->value('api_key'));
        $this->assertSame(40, strlen($a->webhook_token));

        $this->put(route('admin.courier.update', $a), ['name' => 'Main 2', 'api_key' => '', 'api_secret' => '', 'is_active' => 1]);
        $this->assertSame('AAA', $a->fresh()->api_key);
        $this->assertSame('Main 2', $a->fresh()->name);

        $this->get(route('admin.courier.index'))->assertOk()->assertSee('Main 2')->assertDontSee('AAA');
    }

    public function test_other_shops_cannot_touch_an_account(): void
    {
        $a = $this->account('steadfast');
        $other = Tenant::create(['name' => 'O', 'email' => 'o@test.local', 'status' => 'active', 'plan' => 'basic']);
        $this->actingAs(User::factory()->create(['tenant_id' => $other->id, 'role' => 'admin']));
        $this->delete(route('admin.courier.destroy', $a))->assertNotFound();
        $this->post(route('admin.courier.test', $a))->assertNotFound();
    }

    // ── Booking ────────────────────────────────────────────────────

    public function test_steadfast_booking(): void
    {
        $a = $this->account('steadfast');
        Http::fake(['portal.packzy.com/*' => Http::response(['status' => 200, 'consignment' => ['consignment_id' => 9001, 'tracking_code' => 'SFR1']])]);
        $order = $this->order(['paid_amount' => 200]);

        $this->post(route('admin.orders.submitCourier', $order), ['courier' => 'a' . $a->id])->assertSessionHas('success');

        $s = $order->fresh()->shipment;
        $this->assertSame('9001', $s->consignment_id);
        $this->assertSame('https://steadfast.com.bd/t/SFR1', $s->tracking_link);
        $this->assertSame($a->id, $s->courier_account_id);
        $this->assertSame('shipped', $order->fresh()->status);
        Http::assertSent(fn ($r) => $r['invoice'] === $order->order_number && (float) $r['cod_amount'] === 1240.0
            && $r->header('Api-Key')[0] === 'sf-key' && str_contains($r['item_description'], 'Jacket L x2'));
    }

    public function test_pathao_booking_logs_in_once(): void
    {
        $this->account('pathao');
        Http::fake([
            '*/issue-token' => Http::response(['access_token' => 'TOK', 'expires_in' => 3600]),
            '*/aladdin/api/v1/orders' => Http::response(['data' => ['consignment_id' => 'DP123', 'delivery_fee' => 60]]),
        ]);
        $order = $this->order();
        $s = CourierTracker::book($order, CourierSetting::defaultFor('pathao'));

        $this->assertSame('DP123', $s->consignment_id);
        $this->assertEquals(60, $s->courier_charge);
        Http::assertSent(fn ($r) => str_ends_with($r->url(), '/aladdin/api/v1/orders') && $r['store_id'] === 77
            && $r['amount_to_collect'] === 1440 && $r['item_quantity'] === 2 && ! isset($r['recipient_city']) && $r->header('Authorization')[0] === 'Bearer TOK');
    }

    public function test_redx_booking_finds_the_delivery_area(): void
    {
        $this->account('redx');
        Http::fake([
            '*/areas*' => Http::response(['areas' => [['id' => 1, 'name' => 'Dhanmondi'], ['id' => 2, 'name' => 'Mirpur 10'], ['id' => 3, 'name' => 'Mirpur']]]),
            '*/parcel' => Http::response(['tracking_id' => '23A1B2']),
        ]);
        $s = CourierTracker::book($this->order(), CourierSetting::defaultFor('redx'));

        $this->assertSame('23A1B2', $s->tracking_code);
        Http::assertSent(fn ($r) => str_ends_with($r->url(), '/parcel') && $r['delivery_area_id'] === 2
            && $r->header('API-ACCESS-TOKEN')[0] === 'Bearer rx-token');
    }

    public function test_carrybee_and_paperfly_booking(): void
    {
        $this->account('carrybee');
        $this->account('paperfly');
        Http::fake([
            '*/api/v2/address-details' => Http::response(['error' => false, 'data' => ['city_id' => 1, 'zone_id' => 12]]),
            '*/api/v2/orders' => Http::response(['error' => false, 'data' => ['order' => ['consignment_id' => 'CB55']]]),
            '*/new_order_v2.php' => Http::response(['success' => ['tracking_number' => 'PF77', 'message' => 'ok']]),
        ]);

        $cb = CourierTracker::book($this->order(), CourierSetting::defaultFor('carrybee'));
        $this->assertSame('CB55', $cb->consignment_id);

        $o = $this->order();
        $pf = CourierTracker::book($o, CourierSetting::defaultFor('paperfly'));
        $this->assertSame('PF77', $pf->tracking_code);
        $this->assertSame($o->order_number, $pf->consignment_id);
        Http::assertSent(fn ($r) => str_ends_with($r->url(), '/api/v2/orders') && $r['city_id'] === 1 && $r['zone_id'] === 12 && $r->header('Client-Context')[0] === 'ctx');
    }

    public function test_refused_booking_leaves_the_order_alone(): void
    {
        $a = $this->account('steadfast');
        Http::fake(['*' => Http::response(['status' => 400, 'message' => 'Invalid phone'], 400)]);
        $order = $this->order();

        $this->postJson(route('admin.orders.bulkCourier'), ['ids' => [$order->id], 'courier' => 'a' . $a->id])
            ->assertOk()->assertJson(['booked' => 0]);
        $this->assertSame('confirmed', $order->fresh()->status);
        $this->assertNull($order->fresh()->shipment);
    }

    public function test_an_order_is_not_booked_twice(): void
    {
        $a = $this->account('steadfast');
        Http::fake(['*' => Http::response(['consignment' => ['consignment_id' => 1, 'tracking_code' => 'T1']])]);
        $order = $this->order();
        CourierTracker::book($order, $a);

        $this->expectExceptionMessage('already booked');
        CourierTracker::book($order->fresh(), $a);
    }

    // ── Webhooks and sync ──────────────────────────────────────────

    private function booked(string $courier, array $ids): array
    {
        $a = $this->account($courier);
        $order = $this->order(['status' => 'shipped']);
        $s = CourierShipment::create(['order_id' => $order->id, 'courier_account_id' => $a->id, 'courier_name' => $courier, 'status' => 'submitted'] + $ids);

        return [$a, $order, $s];
    }

    public function test_steadfast_webhook_marks_delivered_and_checks_the_secret(): void
    {
        [$a, $order, $s] = $this->booked('steadfast', ['consignment_id' => '9001', 'tracking_code' => 'SFR1']);
        $url = '/api/courier-webhook/' . $a->webhook_token;
        $body = ['notification_type' => 'delivery_status', 'consignment_id' => 9001, 'invoice' => $order->order_number,
            'cod_amount' => 1440, 'status' => 'delivered', 'delivery_charge' => 60, 'tracking_message' => 'Delivered to customer', 'updated_at' => '2026-10-31 12:00:00'];

        $this->postJson('/api/courier-webhook/' . str_repeat('x', 40), $body)->assertNotFound();
        $this->withToken('wrong')->postJson($url, $body)->assertStatus(401);
        $this->assertSame('shipped', $order->fresh()->status);

        $this->withToken($a->webhook_token)->postJson($url, $body)->assertOk();
        $this->postJson($url, $body)->assertOk();   // sent again

        $s->refresh();
        $this->assertSame('delivered', $s->status);
        $this->assertEquals(1440, $s->collected_amount);
        $this->assertEquals(60, $s->courier_charge);
        $this->assertSame('delivered', $order->fresh()->status);
        $this->assertSame(1, CourierEvent::withoutGlobalScopes()->where('status', 'delivered')->count());
        $this->assertNotNull($a->fresh()->last_webhook_at);
    }

    public function test_pathao_handshake_and_return_flow(): void
    {
        [$a, $order, $s] = $this->booked('pathao', ['consignment_id' => 'DP1', 'tracking_code' => 'DP1']);
        $url = '/api/courier-webhook/' . $a->webhook_token;

        $this->postJson($url, ['event' => 'webhook_integration'])->assertStatus(202)
            ->assertHeader('X-Pathao-Merchant-Webhook-Integration-Secret', $a->webhook_token);

        $this->postJson($url, ['consignment_id' => 'DP1', 'event' => 'order.in-transit'])->assertStatus(202);
        $this->assertSame('in_transit', $s->fresh()->status);

        $this->postJson($url, ['consignment_id' => 'DP1', 'event' => 'order.returned'])->assertStatus(202);
        $this->assertSame('pending_return', $order->fresh()->status);

        // An older, late update does not move the parcel back.
        $this->postJson($url, ['consignment_id' => 'DP1', 'event' => 'order.picked'])->assertStatus(202);
        $this->assertSame('returning', $s->fresh()->status);
    }

    public function test_redx_returned_waits_for_the_shop_to_receive_it(): void
    {
        [$a, $order] = $this->booked('redx', ['consignment_id' => '23A', 'tracking_code' => '23A']);
        $this->postJson('/api/courier-webhook/' . $a->webhook_token, ['tracking_number' => '23A', 'status' => 'returned', 'message_en' => 'Returned to merchant'])->assertOk();

        $this->assertSame('return_collection', $order->fresh()->status);
    }

    public function test_webhook_never_reaches_another_shop(): void
    {
        [$a] = $this->booked('steadfast', ['consignment_id' => '1', 'tracking_code' => 'T']);
        $other = Tenant::create(['name' => 'O', 'email' => 'o@test.local', 'status' => 'active', 'plan' => 'basic']);
        $theirOrder = Tenancy::as($other->id, function () {
            $o = Order::create(['order_number' => 'EP55555', 'shipping_name' => 'X', 'shipping_phone' => '01811111111', 'shipping_address' => 'X',
                'subtotal' => 1, 'total' => 1, 'status' => 'shipped']);
            CourierShipment::create(['order_id' => $o->id, 'courier_name' => 'steadfast', 'consignment_id' => '2', 'status' => 'submitted']);

            return $o;
        });

        $this->postJson('/api/courier-webhook/' . $a->webhook_token, ['consignment_id' => 2, 'status' => 'delivered'])
            ->assertOk()->assertJson(['result' => 'not_found']);
        $this->assertSame('shipped', Order::withoutGlobalScopes()->find($theirOrder->id)->status);
    }

    public function test_sync_command_asks_the_courier(): void
    {
        [, $order, $s] = $this->booked('steadfast', ['consignment_id' => '9001', 'tracking_code' => 'SF']);
        $s->update(['next_sync_at' => now()->subMinute()]);
        Http::fake(['*/status_by_cid/9001' => Http::response(['status' => 200, 'delivery_status' => 'partial_delivered'])]);

        $this->artisan('couriers:sync')->assertSuccessful();

        $this->assertSame('partial', $s->fresh()->status);
        $this->assertSame('delivered', $order->fresh()->status);
        $this->assertNull($s->fresh()->next_sync_at);
    }

    // ── Scanning ───────────────────────────────────────────────────

    public function test_pack_by_scanning_the_order_number(): void
    {
        $order = $this->order();
        $this->get(route('admin.dispatch.index'))->assertOk()->assertSee('Packing');

        $this->postJson(route('admin.dispatch.lookup'), ['code' => '#' . $order->order_number, 'mode' => 'pack'])
            ->assertOk()->assertJson(['ok' => true, 'order' => ['number' => $order->order_number, 'items' => [['name' => 'Jacket', 'qty' => 2]]]]);
        $this->postJson(route('admin.dispatch.pack'), ['order_id' => $order->id])->assertOk();

        $this->assertNotNull($order->fresh()->packed_at);
        $this->assertSame('processing', $order->fresh()->status);
        $this->postJson(route('admin.dispatch.lookup'), ['code' => $order->order_number, 'mode' => 'pack'])->assertJson(['ok' => false]);
        $this->postJson(route('admin.dispatch.lookup'), ['code' => 'NOPE', 'mode' => 'pack'])->assertNotFound();
    }

    public function test_hand_over_books_unbooked_parcels(): void
    {
        $a = $this->account('steadfast');
        Http::fake(['*' => Http::response(['consignment' => ['consignment_id' => 5, 'tracking_code' => 'T5']])]);
        $order = $this->order(['status' => 'processing']);
        $cancelled = $this->order(['status' => 'cancelled']);

        $this->postJson(route('admin.dispatch.lookup'), ['code' => $cancelled->order_number, 'mode' => 'handover'])->assertJson(['ok' => false]);
        $r = $this->postJson(route('admin.dispatch.handover'), ['account_id' => $a->id, 'order_ids' => [$order->id, $cancelled->id]])->assertOk();

        $this->assertSame(1, $r->json('done'));
        $s = $order->fresh()->shipment;
        $this->assertSame('T5', $s->tracking_code);
        $this->assertNotNull($s->handed_over_at);
        $this->assertSame('shipped', $order->fresh()->status);
    }

    public function test_receiving_a_return_puts_stock_back(): void
    {
        $product = \App\Models\Product::create(['name' => 'Jacket', 'slug' => 'jacket', 'price' => 675]);
        $variant = \App\Models\ProductVariant::create(['product_id' => $product->id, 'stock' => 10, 'price' => 675, 'sku' => 'J-L']);
        $order = $this->order();
        $order->items()->update(['product_id' => $product->id, 'variant_id' => $variant->id]);
        [$a] = [$this->account('redx')];
        CourierShipment::create(['order_id' => $order->id, 'courier_account_id' => $a->id, 'courier_name' => 'redx', 'consignment_id' => 'R9', 'tracking_code' => 'R9', 'status' => 'submitted']);
        $order->update(['status' => 'shipped']);
        $this->assertSame(8, $variant->fresh()->stock);

        $this->postJson(route('admin.dispatch.lookup'), ['code' => 'R9', 'mode' => 'returns'])->assertJson(['ok' => false]);   // not a return yet
        $this->postJson('/api/courier-webhook/' . $a->webhook_token, ['tracking_number' => 'R9', 'status' => 'returned']);
        $this->postJson(route('admin.dispatch.lookup'), ['code' => 'R9', 'mode' => 'returns'])->assertJson(['ok' => true]);
        $this->postJson(route('admin.dispatch.receive'), ['order_id' => $order->id, 'note' => 'All good'])->assertOk();

        $this->assertSame('returned', $order->fresh()->status);
        $this->assertSame(10, $variant->fresh()->stock);
        $this->assertNotNull($order->fresh()->shipment->return_received_at);
    }

    // ── Payments ───────────────────────────────────────────────────

    public function test_payment_file_is_matched(): void
    {
        [$a, $order, $s] = $this->booked('steadfast', ['consignment_id' => '9001', 'tracking_code' => 'SF1', 'cod_amount' => 1440]);
        $order->update(['status' => 'delivered']);
        [, $order2] = [null, $this->order(['status' => 'delivered'])];
        CourierShipment::create(['order_id' => $order2->id, 'courier_account_id' => $a->id, 'courier_name' => 'steadfast', 'consignment_id' => '9002', 'tracking_code' => 'SF2', 'status' => 'delivered', 'cod_amount' => 1440]);

        $csv = "\xEF\xBB\xBFConsignment ID,Invoice,COD Amount,Delivery Charge\n9001,{$order->order_number},1440,60\n9002,x,1000,60\n7777,y,500,60\n";
        $file = \Illuminate\Http\UploadedFile::fake()->createWithContent('payment.csv', $csv);

        $this->post(route('admin.courier-payments.store'), ['account_id' => $a->id, 'file' => $file, 'reference' => 'INV-1'])->assertRedirect();

        $p = \App\Models\CourierPayment::first();
        $this->assertSame([1, 1, 1], [$p->matched, $p->mismatched, $p->unknown]);
        $this->assertSame('completed', $order->fresh()->status);
        $this->assertSame('delivered', $order2->fresh()->status);   // amount differs: left for a person to check
        $this->assertNotNull($s->fresh()->paid_at);
        $this->assertEquals(60, $s->fresh()->courier_charge);
        $this->get(route('admin.courier-payments.show', $p))->assertOk()->assertSee('order COD is');
        $this->get(route('admin.courier-payments.index'))->assertOk();
    }

    public function test_barcode_encodes_code128(): void
    {
        // "EP1": start B(104) E(37) P(48) 1(17), checksum (104 + 37 + 96 + 51) % 103 = 82, stop(106)
        $this->assertSame([104, 37, 48, 17, 82, 106], \App\Support\Barcode::encode('EP1'));
        $this->assertStringContainsString('<svg', \App\Support\Barcode::svg('EP12345'));
    }

    public function test_tracking_popup_uses_our_history(): void
    {
        [, $order, $s] = $this->booked('steadfast', ['consignment_id' => '9001', 'tracking_code' => 'SF']);
        CourierTracker::apply($s, ['courier_status' => 'hold', 'message' => 'Customer not reachable']);
        Http::fake(['*' => Http::response([], 500)]);

        $this->getJson(route('admin.orders.trackCourier', $order))->assertOk()
            ->assertJsonPath('status', 'On hold')
            ->assertJsonFragment(['note' => 'On hold — Customer not reachable']);
    }

    // ── Confirm before courier ─────────────────────────────────────

    public function test_pending_orders_never_go_to_the_courier(): void
    {
        $a = $this->account('steadfast');
        Http::fake(['*' => Http::response(['status' => 200, 'consignment' => ['consignment_id' => 1, 'tracking_code' => 'T1']])]);
        $pending = $this->order(['status' => 'pending']);
        $hold    = $this->order(['status' => 'hold']);

        $this->get(route('admin.orders.show', $pending))->assertOk()->assertSee('Confirm the order first');
        $this->post(route('admin.orders.submitCourier', $pending), ['courier' => 'a' . $a->id])->assertSessionHas('error');
        $this->post(route('admin.orders.submitCourier', $pending), ['courier' => 'manual'])->assertSessionHas('error');
        $this->patch(route('admin.orders.updateStatus', $pending), ['status' => 'shipped'])->assertSessionHas('error');
        $this->postJson(route('admin.orders.bulkCourier'), ['ids' => [$pending->id, $hold->id], 'courier' => 'a' . $a->id])
            ->assertJsonCount(2, 'errors');
        $this->postJson(route('admin.orders.bulkStatus'), ['ids' => [$pending->id], 'status' => 'hand_delivery'])
            ->assertJsonCount(1, 'errors');
        $this->postJson(route('admin.dispatch.handover'), ['account_id' => $a->id, 'order_ids' => [$hold->id]])->assertJsonPath('done', 0);

        try {
            CourierTracker::book($pending->fresh(), $a);
            $this->fail('Booked a pending order');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('confirm it first', $e->getMessage());
        }
        $this->assertSame('pending', $pending->fresh()->status);
        $this->assertSame('hold', $hold->fresh()->status);
        $this->assertSame(0, CourierShipment::count());
        Http::assertNothingSent();

        // Confirm it → now it can go.
        $this->patch(route('admin.orders.updateStatus', $pending), ['status' => 'confirmed'])->assertSessionHas('success');
        $this->get(route('admin.orders.show', $pending))->assertDontSee('Confirm the order first');
        $this->post(route('admin.orders.submitCourier', $pending), ['courier' => 'a' . $a->id])->assertSessionHas('success');
        $this->assertSame('shipped', $pending->fresh()->status);
    }
}
