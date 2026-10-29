<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use App\Support\Tenancy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    private User $adminA;
    private User $adminB;
    private Order $orderB;
    private Product $productB;

    protected function setUp(): void
    {
        parent::setUp();

        [$this->adminA] = $this->makeShop('Shop A');
        [$this->adminB, $shopB] = $this->makeShop('Shop B');

        // Shop B's data is created "as" shop B so the trait stamps tenant_id.
        Tenancy::as($shopB->id, function () {
            $this->productB = Product::create(['name' => 'B Shirt', 'slug' => 'b-shirt', 'price' => 500, 'status' => 'active']);
            ProductVariant::create(['product_id' => $this->productB->id, 'size' => 'M', 'stock' => 10, 'price' => 500, 'is_active' => true]);
            $this->orderB = Order::create([
                'shipping_name' => 'Customer B', 'shipping_phone' => '01700000002', 'shipping_address' => 'Dhaka',
                'subtotal' => 500, 'total' => 500, 'payment_method' => 'cod', 'status' => 'pending',
            ]);
            OrderItem::create(['order_id' => $this->orderB->id, 'product_id' => $this->productB->id,
                'product_name' => 'B Shirt', 'qty' => 1, 'unit_price' => 500, 'total' => 500]);
        });
    }

    private function makeShop(string $name): array
    {
        $tenant = Tenant::create(['name' => $name, 'email' => str()->slug($name).'@test.local', 'status' => 'active', 'plan' => 'basic']);
        $admin  = User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'admin', 'is_super_admin' => false]);

        return [$admin, $tenant];
    }

    public function test_new_rows_get_the_creators_tenant(): void
    {
        $this->assertSame($this->adminB->tenant_id, $this->orderB->tenant_id);
        $this->assertSame($this->adminB->tenant_id, $this->productB->tenant_id);
    }

    public function test_queries_only_return_own_shop_rows(): void
    {
        $this->actingAs($this->adminA);

        $this->assertSame(0, Order::count());
        $this->assertSame(0, Product::count());
        $this->assertSame(0, OrderItem::count());       // child table, scoped through its order
        $this->assertSame(0, ProductVariant::count());  // child table, scoped through its product
        $this->assertNull(Order::find($this->orderB->id));
    }

    public function test_owner_still_sees_own_rows(): void
    {
        $this->actingAs($this->adminB);

        $this->assertSame(1, Order::count());
        $this->assertSame(1, OrderItem::count());
        $this->assertSame(1, ProductVariant::count());
    }

    public function test_super_admin_sees_every_shop(): void
    {
        $super = User::factory()->create(['is_super_admin' => true, 'role' => 'admin']);
        $this->actingAs($super);

        $this->assertSame(1, Order::count());
    }

    public function test_other_shops_order_pages_return_404(): void
    {
        $this->actingAs($this->adminA);

        $this->get(route('admin.orders.show', $this->orderB->id))->assertNotFound();
        $this->patch(route('admin.orders.updateStatus', $this->orderB->id), ['status' => 'cancelled'])->assertNotFound();
        $this->get(route('admin.products.edit', $this->productB->id))->assertNotFound();

        $this->assertSame('pending', $this->orderB->fresh()->status);
    }

    public function test_cannot_add_another_shops_product_to_an_order(): void
    {
        $this->actingAs($this->adminA);

        $this->post(route('admin.orders.store'), [
            'shipping_name'  => 'X', 'shipping_phone' => '01700000001', 'payment_method' => 'cod',
            'items' => [['product_id' => $this->productB->id, 'qty' => 1, 'unit_price' => 1]],
        ])->assertSessionHasErrors('items.0.product_id');
    }

    public function test_shared_system_roles_cannot_be_edited_by_a_shop(): void
    {
        $shared = Role::create(['name' => 'manager', 'display_name' => 'Manager', 'permissions' => [], 'is_system' => true]);
        $this->actingAs($this->adminA);

        $this->put(route('admin.roles.update', $shared), ['display_name' => 'Hacked', 'permissions' => []]);

        $this->assertSame('Manager', $shared->fresh()->display_name);
    }

    public function test_custom_roles_stay_inside_their_shop(): void
    {
        $this->actingAs($this->adminB);
        $this->post(route('admin.roles.store'), ['name' => 'packer', 'display_name' => 'Packer', 'permissions' => []]);

        $this->actingAs($this->adminA);
        $this->assertFalse(Role::where('name', 'packer')->exists());
    }

    public function test_category_slugs_are_unique_per_shop_not_globally(): void
    {
        Tenancy::as($this->adminB->tenant_id, fn () => Category::create(['name' => 'Polo', 'slug' => 'polo', 'is_active' => true]));

        $this->actingAs($this->adminA);
        $this->post(route('admin.categories.store'), ['name' => 'Polo', 'slug' => 'polo'])->assertSessionDoesntHaveErrors('slug');
    }
}
