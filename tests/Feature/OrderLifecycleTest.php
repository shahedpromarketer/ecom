<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderLifecycleTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private ProductVariant $variant;

    protected function setUp(): void
    {
        parent::setUp();
        $shop = Tenant::create(['name' => 'Shop', 'email' => 'shop@test.local', 'status' => 'active', 'plan' => 'basic']);
        $this->admin = User::factory()->create(['tenant_id' => $shop->id, 'role' => 'admin']);
        $this->actingAs($this->admin);

        $product = Product::create(['name' => 'Polo', 'slug' => 'polo', 'price' => 500, 'status' => 'active']);
        $this->variant = ProductVariant::create(['product_id' => $product->id, 'size' => 'M', 'stock' => 10, 'price' => 500, 'is_active' => true]);
    }

    private function order(int $qty = 3): Order
    {
        $order = Order::create(['shipping_name' => 'A', 'shipping_phone' => '01700000000', 'shipping_address' => 'Dhaka',
            'subtotal' => 1500, 'total' => 1500, 'payment_method' => 'cod', 'status' => 'confirmed']);
        OrderItem::create(['order_id' => $order->id, 'product_id' => $this->variant->product_id, 'variant_id' => $this->variant->id,
            'product_name' => 'Polo', 'qty' => $qty, 'unit_price' => 500, 'total' => 500 * $qty]);

        return $order;
    }

    public function test_shipping_deducts_and_cancelling_restores_stock(): void
    {
        $order = $this->order(3);

        $order->update(['status' => 'shipped']);
        $this->assertSame(7, $this->variant->fresh()->stock);

        $order->update(['status' => 'cancelled']);
        $this->assertSame(10, $this->variant->fresh()->stock);
        $this->assertFalse($order->fresh()->stock_deducted);
    }

    public function test_return_restores_only_what_was_actually_taken(): void
    {
        $this->variant->update(['stock' => 2]);   // fewer than ordered: only 2 can leave the shelf
        $order = $this->order(3);

        $order->update(['status' => 'shipped']);
        $this->assertSame(0, $this->variant->fresh()->stock);

        $order->update(['status' => 'pending_return']);
        $this->assertSame(0, $this->variant->fresh()->stock);   // not back yet

        $order->update(['status' => 'returned']);
        $this->assertSame(2, $this->variant->fresh()->stock);
    }

    public function test_ship_cancel_ship_again_counts_correctly(): void
    {
        $order = $this->order(3);
        $order->update(['status' => 'shipped']);
        $order->update(['status' => 'cancelled']);
        $order->update(['status' => 'shipped']);

        $this->assertSame(7, $this->variant->fresh()->stock);
    }

    public function test_statuses_offered_in_the_ui_can_be_saved(): void
    {
        $order = $this->order();

        foreach (['damage', 'hand_delivery', 'hand_delivery_completed', 'others'] as $status) {
            $this->patch(route('admin.orders.updateStatus', $order), ['status' => $status])->assertRedirect();
            $this->assertSame($status, $order->fresh()->status);
        }
    }

    public function test_order_numbers_are_unique_per_shop_only(): void
    {
        $a = $this->order();
        $other = Tenant::create(['name' => 'Other', 'email' => 'o@test.local', 'status' => 'active', 'plan' => 'basic']);

        $copy = \App\Support\Tenancy::as($other->id, fn () => Order::create([
            'order_number' => $a->order_number,
            'shipping_name' => 'B', 'shipping_phone' => '01800000000', 'shipping_address' => 'Ctg',
            'subtotal' => 1, 'total' => 1, 'payment_method' => 'cod', 'status' => 'pending',
        ]));

        $this->assertSame($a->order_number, $copy->order_number);
        $this->assertMatchesRegularExpression('/^EPL\d{8}-\d{5}$/', $a->order_number);
    }

    public function test_admin_login_locks_after_five_wrong_passwords(): void
    {
        auth()->logout();

        for ($i = 0; $i < 5; $i++) {
            $this->post('/admin/login', ['email' => $this->admin->email, 'password' => 'wrong']);
        }

        $this->post('/admin/login', ['email' => $this->admin->email, 'password' => 'password'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_super_admin_can_stop_impersonating(): void
    {
        $super = User::factory()->create(['is_super_admin' => true, 'role' => 'admin']);
        $this->actingAs($super)->post(route('super.impersonate', $this->admin->tenant_id))->assertRedirect('/admin/dashboard');
        $this->assertSame($this->admin->id, auth()->id());

        $this->post(route('super.stop-impersonating'))->assertRedirect(route('super.dashboard'));
        $this->assertSame($super->id, auth()->id());
    }
}
