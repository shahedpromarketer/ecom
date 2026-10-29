<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Role;
use App\Models\Setting;
use App\Models\Tenant;
use App\Models\User;
use App\Services\OrderDistributor;
use App\Support\Tenancy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderDistributionTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $shop;
    private User $owner;
    private User $a;
    private User $b;

    protected function setUp(): void
    {
        parent::setUp();
        $this->shop  = Tenant::create(['name' => 'Shop', 'email' => 'shop@test.local', 'status' => 'active', 'plan' => 'basic']);
        $this->owner = User::factory()->create(['tenant_id' => $this->shop->id, 'role' => 'admin']);
        $exec = Role::whereNull('tenant_id')->where('name', 'order_executive')->value('id');
        $this->a = User::factory()->create(['tenant_id' => $this->shop->id, 'role' => 'staff', 'role_id' => $exec, 'receives_orders' => true]);
        $this->b = User::factory()->create(['tenant_id' => $this->shop->id, 'role' => 'staff', 'role_id' => $exec, 'receives_orders' => true]);
    }

    private function configure(string $mode, int $max = 0, bool $sticky = false): void
    {
        Setting::setValue('distribution_mode', $mode, 'distribution', $this->shop->id);
        Setting::setValue('distribution_max_open', $max, 'distribution', $this->shop->id);
        Setting::setValue('distribution_sticky', $sticky ? 1 : 0, 'distribution', $this->shop->id);
    }

    /** An order arriving from the website/API: nobody logged in, shop pinned. */
    private function websiteOrder(string $phone = '01700000000', string $status = 'pending'): Order
    {
        return Tenancy::as($this->shop->id, fn () => Order::create([
            'shipping_name' => 'C', 'shipping_phone' => $phone, 'shipping_address' => 'Dhaka',
            'subtotal' => 100, 'total' => 100, 'payment_method' => 'cod', 'status' => $status,
        ]))->fresh();
    }

    public function test_off_by_default(): void
    {
        $this->assertNull($this->websiteOrder()->assigned_to);
    }

    public function test_round_robin_alternates(): void
    {
        $this->configure('round_robin');

        $ids = collect(range(1, 4))->map(fn ($i) => $this->websiteOrder('0170000000' . $i)->assigned_to)->all();

        $this->assertSame([$this->a->id, $this->b->id, $this->a->id, $this->b->id], $ids);
    }

    public function test_least_load_picks_the_least_busy(): void
    {
        $this->configure('least_load');
        // a already has two open orders
        $this->websiteOrder('01711111111')->update(['assigned_to' => $this->a->id]);
        $this->websiteOrder('01722222222')->update(['assigned_to' => $this->a->id]);
        Order::withoutGlobalScopes()->where('assigned_to', $this->b->id)->update(['assigned_to' => $this->a->id]);

        $this->assertSame($this->b->id, $this->websiteOrder('01733333333')->assigned_to);
    }

    public function test_limit_queues_orders_and_the_queue_drains_later(): void
    {
        $this->configure('round_robin', 1);

        $this->assertNotNull($this->websiteOrder('01711111111')->assigned_to);
        $this->assertNotNull($this->websiteOrder('01722222222')->assigned_to);
        $queued = $this->websiteOrder('01733333333');
        $this->assertNull($queued->assigned_to);   // both at their limit of 1

        // a finishes an order → room again
        Order::withoutGlobalScopes()->where('assigned_to', $this->a->id)->update(['status' => 'delivered']);
        $this->artisan('orders:distribute')->assertSuccessful();

        $this->assertSame($this->a->id, $queued->fresh()->assigned_to);
    }

    public function test_returning_customer_goes_back_to_previous_handler(): void
    {
        $this->configure('round_robin', 0, true);

        $first = $this->websiteOrder('01799999999');
        $second = $this->websiteOrder('01799999999');

        $this->assertSame($first->assigned_to, $second->assigned_to);
    }

    public function test_staff_who_do_not_receive_orders_are_skipped(): void
    {
        $this->configure('round_robin');
        $this->b->update(['receives_orders' => false]);

        $this->assertSame($this->a->id, $this->websiteOrder('01711111111')->assigned_to);
        $this->assertSame($this->a->id, $this->websiteOrder('01722222222')->assigned_to);
    }

    public function test_manual_orders_keep_their_creator(): void
    {
        $this->configure('round_robin');
        $order = Tenancy::as($this->shop->id, fn () => Order::create([
            'shipping_name' => 'C', 'shipping_phone' => '01711111111', 'shipping_address' => 'Dhaka',
            'subtotal' => 1, 'total' => 1, 'payment_method' => 'cod', 'status' => 'pending', 'assigned_to' => $this->owner->id,
        ]));

        $this->assertSame($this->owner->id, $order->fresh()->assigned_to);
    }

    public function test_executive_only_sees_own_orders_everywhere(): void
    {
        $this->configure('round_robin');
        $mine   = $this->websiteOrder('01711111111');   // → a
        $theirs = $this->websiteOrder('01722222222');   // → b

        $this->actingAs($this->a);
        $this->assertSame(1, Order::count());
        $this->get(route('admin.orders.show', $mine))->assertOk();
        $this->get(route('admin.orders.show', $theirs))->assertNotFound();

        // Owner and managers see everything.
        $this->actingAs($this->owner);
        $this->assertSame(2, Order::count());
    }

    public function test_settings_page_saves_who_receives_orders(): void
    {
        $this->actingAs($this->owner);
        $this->get(route('admin.distribution.index'))->assertOk()->assertSee($this->a->name);

        $this->post(route('admin.distribution.update'), [
            'mode' => 'least_load', 'max_open' => 5, 'sticky' => 1, 'receivers' => [$this->b->id],
        ])->assertSessionHas('success');

        $this->assertSame('least_load', OrderDistributor::settings($this->shop->id)['mode']);
        $this->assertSame(5, OrderDistributor::settings($this->shop->id)['max_open']);
        $this->assertFalse($this->a->fresh()->receives_orders);
        $this->assertTrue($this->b->fresh()->receives_orders);
    }

    public function test_executives_cannot_change_distribution(): void
    {
        $this->actingAs($this->a);
        $this->get(route('admin.distribution.index'))->assertRedirect(route('admin.dashboard'));
    }
}
