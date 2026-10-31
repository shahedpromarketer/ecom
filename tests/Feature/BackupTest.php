<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Setting;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Backup\BackupArchive;
use App\Services\Backup\BackupManager;
use App\Services\Backup\GoogleDrive;
use App\Support\Tenancy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class BackupTest extends TestCase
{
    use RefreshDatabase;

    private string $dir;
    private string $fileDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dir = storage_path('framework/testing/backups-' . uniqid());
        $this->fileDir = storage_path('app/private/backup-test-' . uniqid());
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->dir);
        File::deleteDirectory($this->fileDir);
        File::deleteDirectory(storage_path('app/backups'));
        parent::tearDown();
    }

    private function shopWithOrder(string $name = 'Shop A'): array
    {
        $shop = Tenant::create(['name' => $name, 'email' => uniqid() . '@test.local', 'status' => 'active', 'plan' => 'basic']);
        $order = Tenancy::as($shop->id, fn () => Order::create(['order_number' => 'B' . random_int(10000, 99999),
            'shipping_name' => 'R', 'shipping_phone' => '01711111111', 'shipping_address' => 'Dhaka', 'subtotal' => 500, 'total' => 500, 'status' => 'confirmed']));

        return [$shop, $order];
    }

    private function connectDrive(): void
    {
        GoogleDrive::saveClient('abc.apps.googleusercontent.com', 'secret');
        Setting::setValue('backup_google_token', \Illuminate\Support\Facades\Crypt::encryptString(json_encode(
            ['refresh_token' => 'r1', 'access_token' => 'a1', 'expires_at' => time() + 3000, 'email' => 'me@gmail.com'])), 'backup', null);
        Setting::setValue('backup_google_folder', 'FOLDER1', 'backup', null);
    }

    public function test_backup_and_restore_bring_every_shop_and_uploaded_file_back(): void
    {
        [$a, $orderA] = $this->shopWithOrder('Shop A');
        [$b] = $this->shopWithOrder('Shop B');
        Tenant::whereKey($a->id)->update(['wallet_balance' => 123.45]);
        File::ensureDirectoryExists($this->fileDir);
        file_put_contents($this->fileDir . '/pay.png', 'PNGDATA');
        Setting::setValue('backup_keep', 14, 'backup', null);

        [$zip, $manifest] = BackupArchive::create($this->dir);
        $this->assertFileExists($zip);
        $this->assertSame(2, $manifest['tables']['tenants']);
        $this->assertArrayNotHasKey('sessions', $manifest['tables']);

        // Things go wrong after the backup…
        Order::withoutGlobalScopes()->whereKey($orderA->id)->delete();
        $b->delete();
        Tenant::create(['name' => 'Made later', 'email' => 'later@test.local', 'status' => 'active', 'plan' => 'basic']);
        Tenant::whereKey($a->id)->update(['wallet_balance' => 0]);
        File::delete($this->fileDir . '/pay.png');
        Setting::setValue('backup_keep', 30, 'backup', null);   // this server's own setting

        $restored = BackupArchive::restore($zip);

        $this->assertSame(2, $restored['tenants']);
        $this->assertSame(['Shop A', 'Shop B'], Tenant::orderBy('id')->pluck('name')->all());
        $this->assertTrue(Order::withoutGlobalScopes()->whereKey($orderA->id)->exists());
        $this->assertEquals(123.45, (float) Tenant::find($a->id)->wallet_balance);
        $this->assertSame('PNGDATA', file_get_contents($this->fileDir . '/pay.png'));
        $this->assertEquals(30, Setting::getValue('backup_keep', null, null));   // kept, not rolled back
    }

    public function test_a_foreign_zip_is_refused(): void
    {
        File::ensureDirectoryExists($this->dir);
        $zip = new \ZipArchive();
        $zip->open($this->dir . '/x.zip', \ZipArchive::CREATE);
        $zip->addFromString('manifest.json', '{"app":"other"}');
        $zip->close();

        $this->expectException(\RuntimeException::class);
        BackupArchive::restore($this->dir . '/x.zip');
    }

    public function test_daily_backup_uploads_to_drive_and_keeps_only_the_newest(): void
    {
        $this->shopWithOrder();
        $this->connectDrive();
        Setting::setValue('backup_keep', 7, 'backup', null);

        $old = collect(range(1, 9))->map(fn ($i) => ['id' => 'F' . $i, 'name' => "b{$i}.zip", 'size' => '10', 'createdTime' => now()->subDays($i)->toIso8601String()])->all();
        Http::fake([
            'www.googleapis.com/drive/v3/files/FOLDER1*' => Http::response(['id' => 'FOLDER1', 'trashed' => false]),
            'www.googleapis.com/upload/drive/v3/files*'  => Http::sequence()
                ->push('', 200, ['Location' => 'https://www.googleapis.com/upload/drive/v3/files?upload_id=S1'])
                ->push(['id' => 'NEW'], 200),
            'www.googleapis.com/drive/v3/files?*'        => Http::response(['files' => array_merge([['id' => 'NEW', 'name' => 'new.zip', 'size' => '10', 'createdTime' => now()->toIso8601String()]], $old)]),
            'www.googleapis.com/drive/v3/files/*'        => Http::response('', 204),
        ]);

        $this->artisan('backup:run')->assertSuccessful();

        $status = BackupManager::status();
        $this->assertTrue($status['ok']);
        $this->assertTrue($status['drive']);
        // 10 in Drive, keep 7 → the 3 oldest are deleted
        $deleted = collect(Http::recorded())->filter(fn ($p) => $p[0]->method() === 'DELETE')->map(fn ($p) => basename($p[0]->url()))->values()->all();
        $this->assertSame(['F7', 'F8', 'F9'], $deleted);
        $this->assertCount(1, BackupManager::localCopies());
    }

    public function test_super_admin_page_connect_and_queue(): void
    {
        $super = User::factory()->create(['is_super_admin' => true, 'role' => 'admin', 'tenant_id' => null]);
        $this->actingAs($super)->get(route('super.backup'))->assertOk()->assertSee('No backup yet')->assertSee(route('super.backup.callback'));

        $this->post(route('super.backup.client'), ['client_id' => 'nope', 'client_secret' => 's'])->assertSessionHasErrors('client_id');
        $this->post(route('super.backup.client'), ['client_id' => '123-abc.apps.googleusercontent.com', 'client_secret' => 'GOCSPX-x'])->assertSessionHas('success');
        $this->assertSame('GOCSPX-x', GoogleDrive::client()['client_secret']);
        $this->assertStringNotContainsString('GOCSPX-x', (string) Setting::getValue('backup_google_client', null, null));   // encrypted

        $r = $this->get(route('super.backup.connect'));
        $this->assertStringStartsWith('https://accounts.google.com/o/oauth2/v2/auth?', $r->headers->get('Location'));
        $this->assertStringContainsString('drive.file', urldecode($r->headers->get('Location')));
        $state = session('backup_oauth_state');

        $this->get(route('super.backup.callback', ['state' => 'wrong', 'code' => 'c']))->assertSessionHas('error');

        Http::fake([
            'oauth2.googleapis.com/token' => Http::response(['access_token' => 'a', 'refresh_token' => 'r', 'expires_in' => 3600]),
            'www.googleapis.com/drive/v3/about*' => Http::response(['user' => ['emailAddress' => 'me@gmail.com']]),
            'www.googleapis.com/drive/v3/files*' => Http::response(['id' => 'FOLDER9']),
        ]);
        $this->withSession(['backup_oauth_state' => $state ?? 'x'])
            ->get(route('super.backup.callback', ['state' => $state ?? 'x', 'code' => 'c']))->assertSessionHas('success');
        $this->assertTrue(GoogleDrive::for(null)->connected());
        $this->assertSame('me@gmail.com', GoogleDrive::for(null)->email());

        $this->post(route('super.backup.now'))->assertSessionHas('success');
        $this->post(route('super.backup.now'))->assertSessionHas('error');   // one at a time
        $this->assertSame('backup', BackupManager::task()['action']);

        $this->post(route('super.backup.cancel'));
        $this->post(route('super.backup.restore'), ['file_id' => 'F1', 'confirm' => 'yes'])->assertSessionHas('error');
        $this->post(route('super.backup.restore'), ['file_id' => 'F1', 'name' => 'b.zip', 'confirm' => 'restore'])->assertSessionHas('success');
        $this->assertSame('restore', BackupManager::task()['action']);
    }

    public function test_shop_users_cannot_reach_backups(): void
    {
        [$shop] = $this->shopWithOrder();
        $owner = User::factory()->create(['tenant_id' => $shop->id, 'role' => 'admin']);
        $this->actingAs($owner)->get(route('super.backup'))->assertForbidden();
        $this->post(route('super.backup.now'))->assertForbidden();
    }

    public function test_queued_backup_runs_from_the_scheduler(): void
    {
        $this->shopWithOrder();
        BackupManager::queue('backup', 1);

        $this->artisan('backup:run --queued')->assertSuccessful();

        $this->assertNull(BackupManager::task());
        $this->assertFalse(BackupManager::status()['drive']);   // not connected: kept on the server
        $this->assertCount(1, BackupManager::localCopies());
    }
}
