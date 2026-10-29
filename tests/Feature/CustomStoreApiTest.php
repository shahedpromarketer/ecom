<?php

namespace Tests\Feature;

use App\Models\BlacklistedPhone;
use App\Models\IncompleteOrder;
use App\Models\Lead;
use App\Models\Order;
use App\Models\Tenant;
use App\Models\User;
use App\Models\WordPressSite;
use App\Services\GoogleSheetImporter;
use App\Services\StoreStatusPusher;
use App\Support\PluginSignature;
use App\Support\Tenancy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/** Custom website API, failed (unfinished) orders and Google Sheet import. */
class CustomStoreApiTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $shop;
    private WordPressSite $store;

    protected function setUp(): void
    {
        parent::setUp();
        $this->shop = Tenant::create(['name' => 'Shop', 'email' => 'shop@test.local', 'status' => 'active', 'plan' => 'basic']);
        $this->store = Tenancy::as($this->shop->id, fn () => WordPressSite::create([
            'name' => 'My website', 'site_url' => 'https://shop.example.test', 'type' => 'custom',
            'api_key' => WordPressSite::generateApiKey(), 'is_active' => true,
        ]));
    }

    private function key(?WordPressSite $s = null): string
    {
        $s ??= $this->store;

        return PluginSignature::connectionKey($s->id, $s->api_key);
    }

    private function api(string $method, string $path, array $data = [], ?string $key = null)
    {
        return $this->withHeaders(['Authorization' => 'Bearer ' . ($key ?? $this->key()), 'Accept' => 'application/json'])
            ->json($method, '/api/v1/' . $path, $data);
    }

    private function order(array $over = []): array
    {
        return array_replace_recursive([
            'external_id' => 'WEB-1001',
            'customer'    => ['name' => 'Rahim Uddin', 'phone' => '01712345678', 'address' => 'House 7, Mirpur 10', 'district' => 'Dhaka'],
            'items'       => [['name' => 'Winter Jacket', 'variant' => 'Size: L', 'qty' => 2, 'price' => 1000]],
            'shipping_charge' => 90,
            'utm_source'  => 'facebook',
        ], $over);
    }

    private function admin(): User
    {
        $u = User::factory()->create(['tenant_id' => $this->shop->id, 'role' => 'admin']);
        $this->actingAs($u);

        return $u;
    }

    // ── Custom website API ───────────────────────────────────────────

    public function test_wrong_or_missing_key_is_refused(): void
    {
        $this->postJson('/api/v1/orders', $this->order())->assertStatus(401);
        $this->api('POST', 'orders', $this->order(), $this->store->id . '.wrong')->assertStatus(401);
        $this->assertSame(0, Order::withoutGlobalScopes()->count());

        $this->store->update(['is_active' => false]);
        $this->api('POST', 'orders', $this->order())->assertStatus(403);
    }

    public function test_create_order_then_resend_updates_instead_of_duplicating(): void
    {
        $this->api('POST', 'orders', $this->order())->assertCreated()
            ->assertJson(['ok' => true, 'result' => 'created', 'order' => ['external_id' => 'WEB-1001', 'status' => 'pending', 'total' => 2090]]);

        $order = Order::withoutGlobalScopes()->first();
        $this->assertSame($this->shop->id, $order->tenant_id);
        $this->assertSame('website', $order->source);
        $this->assertSame($this->store->id, $order->wordpress_site_id);
        $this->assertSame('01712345678', $order->shipping_phone);
        $this->assertSame(1, $order->items()->count());

        $this->api('POST', 'orders', $this->order(['customer' => ['address' => 'New address']]))->assertOk()->assertJson(['result' => 'updated']);
        $this->assertSame(1, Order::withoutGlobalScopes()->count());
        $this->assertSame('New address', Order::withoutGlobalScopes()->first()->shipping_address);
    }

    public function test_validation_errors_are_clear(): void
    {
        $this->api('POST', 'orders', $this->order(['customer' => ['phone' => '12345']]))
            ->assertStatus(422)->assertJsonValidationErrors('customer.phone');
        $this->api('POST', 'orders', ['external_id' => 'X'])->assertStatus(422)->assertJsonValidationErrors(['customer', 'items']);
    }

    public function test_show_order_returns_status_and_only_for_its_own_store(): void
    {
        $this->api('POST', 'orders', $this->order());
        $this->api('GET', 'orders/WEB-1001')->assertOk()->assertJson(['order' => ['external_id' => 'WEB-1001', 'status' => 'pending']]);
        $this->api('GET', 'orders/NOPE')->assertNotFound();

        $other = Tenancy::as($this->shop->id, fn () => WordPressSite::create([
            'name' => 'Other', 'site_url' => 'https://other.test', 'type' => 'custom', 'api_key' => WordPressSite::generateApiKey(), 'is_active' => true,
        ]));
        $this->api('GET', 'orders/WEB-1001', [], $this->key($other))->assertNotFound();
    }

    public function test_checkout_check_blocks_blacklisted_numbers(): void
    {
        Tenancy::as($this->shop->id, fn () => BlacklistedPhone::create(['phone' => '01799999999', 'reason' => 'fake']));

        $this->api('POST', 'checkout/check', ['phone' => '+880 1799-999999'])->assertOk()->assertJson(['allowed' => false, 'reason' => 'phone_blocked']);
        $this->api('POST', 'checkout/check', ['phone' => '01711111111'])->assertOk()->assertJson(['allowed' => true]);
    }

    public function test_status_change_is_sent_to_the_webhook(): void
    {
        Http::fake(['*' => Http::response(['ok' => true], 200)]);
        $this->store->update(['webhook_url' => 'https://shop.example.test/ep-webhook']);
        $this->api('POST', 'orders', $this->order());
        $order = Order::withoutGlobalScopes()->first();

        $this->admin();
        $order->update(['status' => 'confirmed']);
        $this->assertTrue(StoreStatusPusher::push($order->fresh()));

        Http::assertSent(function ($request) {
            $data = json_decode($request->body(), true);

            return $request->url() === 'https://shop.example.test/ep-webhook'
                && $data['event'] === 'order.status_changed'
                && $data['order']['external_id'] === 'WEB-1001' && $data['order']['status'] === 'confirmed'
                && PluginSignature::verify($this->store->api_key, $request->body(), $request->header('X-EP-Timestamp')[0], $request->header('X-EP-Signature')[0]);
        });
    }

    public function test_no_webhook_means_nothing_is_sent(): void
    {
        Http::fake();
        $this->api('POST', 'orders', $this->order());
        $this->assertFalse(StoreStatusPusher::push(Order::withoutGlobalScopes()->first()));
        Http::assertNothingSent();
    }

    public function test_api_docs_page_shows_the_key_to_the_owner(): void
    {
        $this->admin();
        $this->get(route('admin.integrations.api-docs', $this->store))->assertOk()->assertSee('/api/v1/orders')->assertSee($this->key());
    }

    // ── Failed orders (unfinished checkouts) ─────────────────────────

    public function test_unfinished_checkout_is_recorded_and_updated_by_session(): void
    {
        $this->api('POST', 'incomplete', ['session_id' => 's1', 'phone' => '01712345678'])->assertOk()->assertJson(['recorded' => true, 'status' => 'pending']);
        $this->api('POST', 'incomplete', ['session_id' => 's1', 'phone' => '01712345678', 'name' => 'Rahim',
            'items' => [['name' => 'Jacket', 'qty' => 1, 'price' => 1350]], 'total' => 1440])->assertOk();
        $this->api('POST', 'incomplete', ['session_id' => 's2', 'phone' => '123'])->assertOk()->assertJson(['recorded' => false]);

        $rows = IncompleteOrder::withoutGlobalScopes()->get();
        $this->assertCount(1, $rows);
        $this->assertSame('Rahim', $rows[0]->name);
        $this->assertSame($this->shop->id, $rows[0]->tenant_id);
        $this->assertEquals(1440, $rows[0]->total);
    }

    public function test_placing_the_order_marks_it_recovered(): void
    {
        $this->api('POST', 'incomplete', ['session_id' => 's1', 'phone' => '01712345678', 'name' => 'Rahim']);
        $this->api('POST', 'orders', $this->order());

        $row = IncompleteOrder::withoutGlobalScopes()->first();
        $this->assertSame('recovered', $row->status);
        $this->assertSame(Order::withoutGlobalScopes()->first()->id, $row->order_id);

        // Typing again after ordering does not make a new "to call" row.
        $this->api('POST', 'incomplete', ['session_id' => 's9', 'phone' => '01712345678'])->assertJson(['status' => 'recovered']);
    }

    public function test_plugin_endpoint_records_unfinished_checkout(): void
    {
        $this->store->update(['type' => 'woocommerce']);
        $body = json_encode(['session_id' => 'wc-abc', 'phone' => '01812345678', 'name' => 'Karim']);
        $sig = PluginSignature::sign($this->store->api_key, $body);

        $this->call('POST', '/api/v1/store/incomplete', [], [], [], $this->transformHeadersToServerVars([
            'Content-Type' => 'application/json', 'Accept' => 'application/json',
            'X-EP-Site' => (string) $this->store->id, 'X-EP-Timestamp' => (string) $sig['timestamp'], 'X-EP-Signature' => $sig['signature'],
        ]), $body)->assertOk();

        $this->assertSame('Karim', IncompleteOrder::withoutGlobalScopes()->first()->name);
    }

    public function test_failed_orders_page_and_actions(): void
    {
        $this->api('POST', 'incomplete', ['session_id' => 'a', 'phone' => '01712345678', 'name' => 'Rahim', 'items' => [['name' => 'Jacket']]]);
        $this->api('POST', 'incomplete', ['session_id' => 'b', 'phone' => '01812345678', 'name' => 'Karim', 'ip' => '103.1.1.9']);
        $this->api('POST', 'incomplete', ['session_id' => 'c', 'phone' => '01912345678', 'name' => 'Jamal']);
        [$a, $b, $c] = IncompleteOrder::withoutGlobalScopes()->orderBy('id')->get()->all();

        $this->admin();
        $this->get(route('admin.failed-orders.index'))->assertOk()->assertSee('Rahim')->assertSee('Karim');

        $this->patch(route('admin.failed-orders.update', $a), ['action' => 'lead'])->assertRedirect();
        $this->assertSame(1, Lead::count());
        $this->assertSame('deleted', $a->fresh()->status);

        $this->patch(route('admin.failed-orders.update', $b), ['action' => 'block'])->assertRedirect();
        $this->assertTrue(BlacklistedPhone::where('phone', '01812345678')->exists());

        $this->patch(route('admin.failed-orders.update', $c), ['action' => 'delete', 'delete_reason' => 'Fake'])->assertRedirect();
        $this->assertSame('Fake', $c->fresh()->delete_reason);
        $this->patch(route('admin.failed-orders.update', $c), ['action' => 'restore'])->assertRedirect();
        $this->assertSame('pending', $c->fresh()->status);
    }

    public function test_other_shops_cannot_see_failed_orders(): void
    {
        $this->api('POST', 'incomplete', ['session_id' => 'a', 'phone' => '01712345678', 'name' => 'Rahim']);
        $row = IncompleteOrder::withoutGlobalScopes()->first();

        $other = Tenant::create(['name' => 'Other', 'email' => 'o@test.local', 'status' => 'active', 'plan' => 'basic']);
        $this->actingAs(User::factory()->create(['tenant_id' => $other->id, 'role' => 'admin']));
        $this->get(route('admin.failed-orders.index'))->assertOk()->assertDontSee('Rahim');
        $this->patch(route('admin.failed-orders.update', $row), ['action' => 'delete'])->assertNotFound();
    }

    // ── Google Sheet ────────────────────────────────────────────────

    private function sheetStore(array $settings = []): WordPressSite
    {
        $this->store->update(['type' => 'google_sheet', 'settings' => $settings + [
            'sheet_url' => 'https://docs.google.com/spreadsheets/d/1AbCdEfGhIjKlMnOpQrStUvWxYz0123456789/edit#gid=55',
            'auto_import' => true,
            'mapping' => ['order_id' => 'Order ID', 'name' => 'Name', 'phone' => 'Phone', 'address' => 'Address',
                'product' => 'Product', 'variant' => 'Size', 'qty' => 'Qty', 'price' => 'Price'],
        ]]);

        return $this->store->fresh();
    }

    private function fakeSheet(string $csv): void
    {
        Http::fake(['docs.google.com/*' => Http::response($csv, 200, ['Content-Type' => 'text/csv'])]);
    }

    public function test_csv_url_is_built_from_the_sheet_link(): void
    {
        $this->assertSame(
            'https://docs.google.com/spreadsheets/d/1AbCdEfGhIjKlMnOpQrStUvWxYz0123456789/export?format=csv&gid=55',
            GoogleSheetImporter::csvUrl('https://docs.google.com/spreadsheets/d/1AbCdEfGhIjKlMnOpQrStUvWxYz0123456789/edit#gid=55')
        );
        $this->assertNull(GoogleSheetImporter::csvUrl('https://example.com/sheet'));
    }

    public function test_sheet_rows_become_orders_once(): void
    {
        $site = $this->sheetStore();
        $this->fakeSheet("Order ID,Name,Phone,Address,Product,Size,Qty,Price\n"
            . "S-1,Rahim,01712345678,\"House 7, Mirpur\",Jacket,L,1,1350\n"
            . "S-1,Rahim,01712345678,\"House 7, Mirpur\",Cap,,2,200\n"
            . "S-2,Karim,+8801812345678,Uttara,Polo,M,1,650\n"
            . ",,,,,,,\n"
            . "S-3,NoPhone,123,Gulshan,Polo,M,1,650\n");

        $r = GoogleSheetImporter::run($site);
        $this->assertSame(['created' => 2, 'updated' => 0, 'skipped' => 0, 'invalid' => 1, 'error' => null], $r);

        $orders = Order::withoutGlobalScopes()->orderBy('id')->get();
        $this->assertCount(2, $orders);
        $this->assertSame('google_sheet', $orders[0]->source);
        $this->assertSame(2, $orders[0]->items()->count());
        $this->assertEquals(1750, $orders[0]->total);
        $this->assertSame('01812345678', $orders[1]->shipping_phone);

        // Reading again does not duplicate.
        $r = GoogleSheetImporter::run($site->fresh());
        $this->assertSame(0, $r['created']);
        $this->assertSame(2, Order::withoutGlobalScopes()->count());
    }

    public function test_rows_without_order_id_survive_new_rows_above_them(): void
    {
        $site = $this->sheetStore(['mapping' => ['name' => 'Name', 'phone' => 'Phone', 'address' => 'Address', 'product' => 'Product']]);
        Http::fake(['docs.google.com/*' => Http::sequence()
            ->push("Name,Phone,Address,Product\nRahim,01712345678,Mirpur,Jacket\n")
            ->push("Name,Phone,Address,Product\nKarim,01812345678,Uttara,Polo\nRahim,01712345678,Mirpur,Jacket\n")]);
        GoogleSheetImporter::run($site);
        $r = GoogleSheetImporter::run($site->fresh());

        $this->assertSame(1, $r['created']);
        $this->assertSame(2, Order::withoutGlobalScopes()->count());
    }

    public function test_private_sheet_gives_a_helpful_error(): void
    {
        $site = $this->sheetStore();
        Http::fake(['docs.google.com/*' => Http::response('<html><body>Sign in</body></html>', 200)]);

        $r = GoogleSheetImporter::run($site);
        $this->assertStringContainsString('Anyone with the link', $r['error']);
        $this->assertSame(0, Order::withoutGlobalScopes()->count());
    }

    public function test_sheet_setup_page_reads_columns_and_saves_mapping(): void
    {
        $this->store->update(['type' => 'google_sheet']);
        $this->admin();
        $this->fakeSheet("Timestamp,Customer Name,Mobile Number,Full Address,Product Name,Quantity\n2026-10-30,Rahim,01712345678,Mirpur,Jacket,1\n");

        $this->get(route('admin.integrations.sheet.edit', $this->store))->assertOk()->assertSee('Read columns');

        $this->postJson(route('admin.integrations.sheet.columns', $this->store), ['sheet_url' => 'https://docs.google.com/spreadsheets/d/1AbCdEfGhIjKlMnOpQrStUvWxYz0123456789/edit'])
            ->assertOk()->assertJson(['rows' => 1, 'guess' => ['name' => 'Customer Name', 'phone' => 'Mobile Number', 'address' => 'Full Address',
                'product' => 'Product Name', 'qty' => 'Quantity', 'date' => 'Timestamp']]);

        $this->post(route('admin.integrations.sheet.update', $this->store), [
            'sheet_url' => 'https://docs.google.com/spreadsheets/d/1AbCdEfGhIjKlMnOpQrStUvWxYz0123456789/edit',
            'auto_import' => 1,
            'mapping' => ['name' => 'Customer Name', 'phone' => 'Mobile Number', 'address' => 'Full Address', 'product' => 'Product Name', 'qty' => 'Quantity'],
        ])->assertSessionHas('success');

        $this->post(route('admin.integrations.sheet.import', $this->store))->assertSessionHas('success');
        $this->assertSame(1, Order::withoutGlobalScopes()->count());

        $this->artisan('sheets:import')->assertSuccessful();
        $this->assertSame(1, Order::withoutGlobalScopes()->count());
    }

    public function test_sheet_page_is_only_for_sheet_stores(): void
    {
        $this->admin();
        $this->get(route('admin.integrations.sheet.edit', $this->store))->assertNotFound();
    }
}
