<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\Role;
use App\Models\SavedFilter;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecycleBinAndFiltersTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $shop;
    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();
        $this->shop  = Tenant::create(['name' => 'Shop', 'email' => 'shop@test.local', 'status' => 'active', 'plan' => 'basic']);
        $this->owner = User::factory()->create(['tenant_id' => $this->shop->id, 'role' => 'admin']);
        $this->actingAs($this->owner);
    }

    private function order(string $phone = '01712345678', string $status = 'pending', array $extra = []): Order
    {
        return Order::create($extra + ['shipping_name' => 'C', 'shipping_phone' => $phone, 'shipping_address' => 'Dhaka',
            'subtotal' => 500, 'total' => 500, 'payment_method' => 'cod', 'status' => $status]);
    }

    private function manager(): User
    {
        return User::factory()->create(['tenant_id' => $this->shop->id, 'role' => 'staff',
            'role_id' => Role::whereNull('tenant_id')->where('name', 'manager')->value('id')]);
    }

    // ── Recycle bin ─────────────────────────────────────────────

    public function test_deleting_an_order_moves_it_to_the_bin_and_restore_brings_it_back(): void
    {
        $order = $this->order('01712345678', 'delivered');
        $this->assertSame(1, Customer::first()->total_orders);

        $this->delete(route('admin.orders.destroy', $order))->assertRedirect(route('admin.orders.index'));
        $this->assertSoftDeleted($order);
        $this->assertSame(0, Customer::first()->total_orders);   // no longer counted
        $this->assertSame($this->owner->id, Order::onlyTrashed()->first()->deleted_by);

        $this->get(route('admin.recycle-bin.index', ['type' => 'orders']))->assertOk()->assertSee($order->order_number);

        $this->post(route('admin.recycle-bin.restore', 'orders'), ['ids' => [$order->id]])->assertSessionHas('success');
        $this->assertNotSoftDeleted($order);
        $this->assertSame(1, Customer::first()->total_orders);
    }

    public function test_product_delete_keeps_images_until_purged(): void
    {
        $product = Product::create(['name' => 'Polo', 'slug' => 'polo', 'price' => 500, 'status' => 'active']);

        $this->delete(route('admin.products.destroy', $product));
        $this->assertSoftDeleted($product);

        $this->post(route('admin.recycle-bin.destroy', 'products'), ['ids' => [$product->id]]);
        $this->assertDatabaseMissing('products', ['id' => $product->id]);
    }

    public function test_product_used_on_orders_is_kept_when_purging(): void
    {
        $product = Product::create(['name' => 'Polo', 'slug' => 'polo', 'price' => 500, 'status' => 'active']);
        $order = $this->order();
        \App\Models\OrderItem::create(['order_id' => $order->id, 'product_id' => $product->id, 'product_name' => 'Polo', 'qty' => 1, 'unit_price' => 500, 'total' => 500]);
        $product->delete();

        $this->post(route('admin.recycle-bin.destroy', 'products'), ['ids' => [$product->id]])
            ->assertSessionHas('success', fn ($m) => str_contains($m, 'kept'));
        $this->assertSoftDeleted($product);
    }

    public function test_deleted_customer_comes_back_when_they_order_again(): void
    {
        $this->order('01712345678');
        $customer = Customer::first();
        $this->delete(route('admin.customers.destroy', $customer));
        $this->assertSoftDeleted($customer);

        $this->order('01712345678');

        $this->assertNotSoftDeleted($customer);
        $this->assertSame(2, $customer->fresh()->total_orders);
    }

    public function test_only_the_owner_can_use_the_recycle_bin(): void
    {
        $order = $this->order();
        $order->delete();

        $this->actingAs($this->manager());
        $this->get(route('admin.recycle-bin.index'))->assertRedirect(route('admin.dashboard'));
        $this->post(route('admin.recycle-bin.destroy', 'orders'), ['ids' => [$order->id]])->assertSessionHas('permission_error');
        $this->assertSoftDeleted($order);
    }

    public function test_recycle_bin_is_per_shop(): void
    {
        $order = $this->order();
        $order->delete();

        $other = Tenant::create(['name' => 'O', 'email' => 'o@test.local', 'status' => 'active', 'plan' => 'basic']);
        $this->actingAs(User::factory()->create(['tenant_id' => $other->id, 'role' => 'admin']));

        $this->get(route('admin.recycle-bin.index'))->assertOk()->assertDontSee($order->order_number);
        $this->post(route('admin.recycle-bin.restore', 'orders'), ['ids' => [$order->id]]);
        $this->assertSoftDeleted($order);
    }

    // ── Filters ─────────────────────────────────────────────────

    public function test_exclude_status_and_customer_type_filters(): void
    {
        $this->order('01711111111', 'pending', ['shipping_name' => 'Newbie']);
        $this->order('01722222222', 'delivered', ['shipping_name' => 'Zubair Loyal']);
        $this->order('01722222222', 'cancelled', ['shipping_name' => 'Zubair Loyal']);

        $html = $this->get(route('admin.orders.index', ['exclude_status' => ['cancelled', 'delivered']]))->assertOk()->getContent();
        $this->assertStringContainsString('Newbie', $html);
        $this->assertStringNotContainsString('Zubair Loyal', $html);

        $html = $this->get(route('admin.orders.index', ['customer_type' => 'repeat']))->getContent();
        $this->assertStringContainsString('Zubair Loyal', $html);
        $this->assertStringNotContainsString('Newbie', $html);
    }

    public function test_assigned_to_filter_including_queue(): void
    {
        $staff = $this->manager();
        $this->order('01711111111', 'pending', ['shipping_name' => 'Queued one']);
        $this->order('01722222222', 'pending', ['shipping_name' => 'Owned one', 'assigned_to' => $staff->id]);

        $html = $this->get(route('admin.orders.index', ['assigned_to' => 'none']))->getContent();
        $this->assertStringContainsString('Queued one', $html);
        $this->assertStringNotContainsString('Owned one', $html);
    }

    public function test_save_share_and_remove_filters(): void
    {
        $this->post(route('admin.saved-filters.store'), [
            'page' => 'orders', 'name' => 'Pending COD', 'query' => 'status=pending&payment_method=cod&page=3', 'is_shared' => 1,
        ])->assertSessionHas('success');

        $f = SavedFilter::first();
        $this->assertSame('status=pending&payment_method=cod', $f->query);   // paging dropped
        $this->get(route('admin.orders.index'))->assertSee('Pending COD');

        // A colleague sees the shared filter but cannot delete it.
        $this->actingAs($this->manager());
        $this->get(route('admin.orders.index'))->assertSee('Pending COD');
        $this->delete(route('admin.saved-filters.destroy', $f))->assertForbidden();

        $this->actingAs($this->owner);
        $this->delete(route('admin.saved-filters.destroy', $f))->assertSessionHas('success');
        $this->assertSame(0, SavedFilter::count());
    }

    public function test_private_filters_stay_private(): void
    {
        $this->post(route('admin.saved-filters.store'), ['page' => 'customers', 'name' => 'My VIPs', 'query' => 'tag=vip']);

        $this->actingAs($this->manager());
        $this->get(route('admin.customers.index'))->assertDontSee('My VIPs');
    }
}
