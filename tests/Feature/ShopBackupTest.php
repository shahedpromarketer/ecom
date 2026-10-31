<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Role;
use App\Models\Setting;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Backup\BackupManager;
use App\Services\Backup\GoogleDrive;
use App\Services\Backup\TenantBackupArchive;
use App\Support\Tenancy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ShopBackupTest extends TestCase
{
    use RefreshDatabase;

    private string $dir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dir = storage_path('framework/testing/shop-backups-' . uniqid());
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->dir);
        File::deleteDirectory(storage_path('app/backups'));
        parent::tearDown();
    }

    /** A shop with a product (stock 10), a customer, and an order with one item. */
    private function shop(string $name): array
    {
        $shop = Tenant::create(['name' => $name, 'email' => uniqid() . '@test.local', 'status' => 'active', 'plan' => 'basic']);
        $data = Tenancy::as($shop->id, function () use ($name) {
            $product = Product::create(['name' => $name . ' Polo', 'slug' => uniqid('polo-'), 'price' => 500, 'status' => 'active']);
            $variant = ProductVariant::create(['product_id' => $product->id, 'size' => 'M', 'stock' => 10, 'price' => 500, 'is_active' => true]);
            Customer::create(['name' => $name . ' buyer', 'phone' => '017' . random_int(10000000, 99999999)]);
            $order = Order::create(['shipping_name' => $name . ' buyer', 'shipping_phone' => '01700000000', 'shipping_address' => 'Dhaka',
                'subtotal' => 500, 'total' => 500, 'payment_method' => 'cod', 'status' => 'confirmed']);
            OrderItem::create(['order_id' => $order->id, 'product_id' => $product->id, 'variant_id' => $variant->id,
                'product_name' => 'Polo', 'variant_info' => 'M', 'qty' => 1, 'unit_price' => 500, 'total' => 500]);

            return compact('product', 'variant', 'order');
        });

        return [$shop, $data];
    }

    public function test_a_shop_gets_its_own_data_back_and_other_shops_are_untouched(): void
    {
        [$a, $da] = $this->shop('A');
        [$b, $db] = $this->shop('B');
        Tenant::whereKey($a->id)->update(['wallet_balance' => 50]);

        [$zip, $m] = Tenancy::as($a->id, fn () => TenantBackupArchive::create($a, $this->dir));
        $this->assertSame(1, $m['tables']['orders']);
        $this->assertSame(1, $m['tables']['order_items']);
        $this->assertSame(1, $m['tables']['product_variants']);
        $this->assertArrayNotHasKey('users', $m['tables']);
        $this->assertArrayNotHasKey('courier_settings', $m['tables']);

        // After the backup: A loses its order and stock changes; B keeps working.
        DB::table('order_items')->where('order_id', $da['order']->id)->delete();
        DB::table('orders')->where('id', $da['order']->id)->delete();
        DB::table('product_variants')->where('id', $da['variant']->id)->update(['stock' => 3]);
        $newA = Tenancy::as($a->id, fn () => Order::create(['shipping_name' => 'later', 'shipping_phone' => '01700000001', 'shipping_address' => 'X', 'subtotal' => 1, 'total' => 1, 'status' => 'pending']));
        DB::table('product_variants')->where('id', $db['variant']->id)->update(['stock' => 7]);
        Tenant::whereKey($a->id)->update(['wallet_balance' => 49.55]);

        $restored = Tenancy::as($a->id, fn () => TenantBackupArchive::restore($a, $zip));

        $this->assertSame(1, $restored['orders']);
        $ordersA = DB::table('orders')->where('tenant_id', $a->id)->pluck('id')->all();
        $this->assertSame([$da['order']->id], $ordersA);                         // back, and the later one is gone
        $this->assertFalse(DB::table('orders')->where('id', $newA->id)->exists());
        $this->assertSame(1, DB::table('order_items')->where('order_id', $da['order']->id)->count());
        $this->assertSame(10, (int) DB::table('product_variants')->where('id', $da['variant']->id)->value('stock'));
        // Shop B and the wallet are not touched
        $this->assertSame(7, (int) DB::table('product_variants')->where('id', $db['variant']->id)->value('stock'));
        $this->assertSame(1, DB::table('orders')->where('tenant_id', $b->id)->count());
        $this->assertEquals(49.55, (float) Tenant::find($a->id)->wallet_balance);

        // A shop can't restore another shop's backup.
        $this->expectExceptionMessage('another shop');
        Tenancy::as($b->id, fn () => TenantBackupArchive::restore($b, $zip));
    }

    public function test_orders_csv_is_readable(): void
    {
        [$a] = $this->shop('A');
        $csv = Tenancy::as($a->id, fn () => TenantBackupArchive::ordersCsv($a, $this->dir));
        $text = file_get_contents($csv);
        $this->assertStringContainsString('Order,Date,Status', $text);
        $this->assertStringContainsString('Polo (M) x1', $text);
    }

    public function test_shop_backup_page_connect_queue_and_daily_upload(): void
    {
        [$a] = $this->shop('A');
        $owner = User::factory()->create(['tenant_id' => $a->id, 'role' => 'admin']);

        // Not offered until the super admin set up the Google app
        $this->actingAs($owner)->get(route('admin.backup.index'))->assertOk()->assertSee('not switched on');
        GoogleDrive::saveClient('abc.apps.googleusercontent.com', 'secret');
        $this->get(route('admin.backup.index'))->assertSee('Connect Google Drive');
        $this->post(route('admin.backup.now'))->assertSessionHas('error');   // not connected

        $r = $this->get(route('admin.backup.connect'));
        $this->assertStringContainsString(urlencode(route('admin.backup.callback')), $r->headers->get('Location'));
        $state = session('shop_backup_oauth_state');

        Http::fake([
            'oauth2.googleapis.com/token' => Http::response(['access_token' => 'a', 'refresh_token' => 'r', 'expires_in' => 3600]),
            'www.googleapis.com/drive/v3/about*' => Http::response(['user' => ['emailAddress' => 'shop@gmail.com']]),
            'www.googleapis.com/drive/v3/files/SHOPF*' => Http::response(['id' => 'SHOPF', 'trashed' => false]),
            'www.googleapis.com/drive/v3/files?fields=id' => Http::response(['id' => 'SHOPF']),
            'www.googleapis.com/upload/drive/v3/files*' => Http::sequence()
                ->push('', 200, ['Location' => 'https://www.googleapis.com/upload/drive/v3/files?upload_id=S1'])->push(['id' => 'Z1'])
                ->push('', 200, ['Location' => 'https://www.googleapis.com/upload/drive/v3/files?upload_id=S2'])->push(['id' => 'C1']),
            'www.googleapis.com/drive/v3/files?*' => Http::response(['files' => []]),
        ]);
        $this->get(route('admin.backup.callback', ['state' => $state, 'code' => 'c']))->assertSessionHas('success');
        $drive = GoogleDrive::for($a->id);
        $this->assertTrue($drive->connected());
        $this->assertSame('shop@gmail.com', $drive->email());
        $this->assertFalse(GoogleDrive::for(null)->connected());   // the super admin's is separate
        $this->assertSame('backup', BackupManager::task($a->id)['action']);   // first backup queued

        $this->artisan('backup:run --queued')->assertSuccessful();
        $status = BackupManager::status($a->id);
        $this->assertTrue($status['ok']);
        $this->assertTrue($status['drive']);
        $this->assertSame(1, $status['orders']);
        $this->assertNull(BackupManager::task($a->id));
        $names = collect(Http::recorded())->map(fn ($p) => $p[0]->data()['name'] ?? null)->filter()->values()->all();
        $this->assertTrue(collect($names)->contains(fn ($n) => str_ends_with($n, '.zip')));
        $this->assertTrue(collect($names)->contains(fn ($n) => str_ends_with($n, '.csv')));

        $this->get(route('admin.backup.index'))->assertSee('Your data is backed up');

        $this->post(route('admin.backup.restore'), ['file_id' => 'Z1', 'confirm' => 'no'])->assertSessionHas('error');
        $this->post(route('admin.backup.restore'), ['file_id' => 'Z1', 'name' => 'x.zip', 'confirm' => 'RESTORE'])->assertSessionHas('success');
        $this->assertSame('restore', BackupManager::task($a->id)['action']);
    }

    public function test_staff_without_settings_permission_cannot_use_backups(): void
    {
        [$a] = $this->shop('A');
        $exec = Role::withoutGlobalScopes()->whereNull('tenant_id')->where('name', 'order_executive')->value('id');
        $staff = User::factory()->create(['tenant_id' => $a->id, 'role' => 'staff', 'role_id' => $exec]);

        $this->assertNotSame(200, $this->actingAs($staff)->get(route('admin.backup.index'))->status());
        $this->post(route('admin.backup.restore'), ['file_id' => 'x', 'confirm' => 'RESTORE']);
        $this->assertNull(BackupManager::task($a->id));   // nothing queued
    }
}
