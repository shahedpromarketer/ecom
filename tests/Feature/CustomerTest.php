<?php

namespace Tests\Feature;

use App\Models\BlacklistedPhone;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use App\Support\Phone;
use App\Support\Tenancy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerTest extends TestCase
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

    private function order(string $phone, string $status = 'pending', float $total = 1000, string $name = 'Rahim'): Order
    {
        return Order::create(['shipping_name' => $name, 'shipping_phone' => $phone, 'shipping_address' => 'Mirpur, Dhaka',
            'subtotal' => $total, 'total' => $total, 'payment_method' => 'cod', 'status' => $status]);
    }

    public function test_phone_numbers_are_normalized(): void
    {
        $this->assertSame('01712345678', Phone::normalize('+880 1712-345678'));
        $this->assertSame('01712345678', Phone::normalize('8801712345678'));
        $this->assertSame('01712345678', Phone::normalize('1712345678'));
        $this->assertTrue(Phone::isValidBdMobile('01712345678'));
        $this->assertFalse(Phone::isValidBdMobile('01212345678'));
    }

    public function test_orders_with_the_same_number_share_one_customer(): void
    {
        $a = $this->order('01712345678');
        $b = $this->order('+8801712345678');

        $this->assertSame(1, Customer::count());
        $this->assertSame($a->fresh()->customer_id, $b->fresh()->customer_id);
        $this->assertSame(2, Customer::first()->total_orders);
    }

    public function test_counters_follow_order_status(): void
    {
        $o1 = $this->order('01712345678', 'pending', 1000);
        $o2 = $this->order('01712345678', 'pending', 500);
        $o3 = $this->order('01712345678', 'pending', 700);

        $o1->update(['status' => 'delivered']);
        $o2->update(['status' => 'cancelled']);
        $o3->update(['status' => 'returned']);

        $c = Customer::first();
        $this->assertSame(3, $c->total_orders);
        $this->assertSame(1, $c->delivered_orders);
        $this->assertSame(1, $c->cancelled_orders);
        $this->assertSame(1, $c->returned_orders);
        $this->assertEquals(1000, $c->total_spent);
        $this->assertSame(33, $c->success_rate);
    }

    public function test_changing_an_orders_phone_moves_it_to_the_other_customer(): void
    {
        $order = $this->order('01712345678');
        $this->order('01812345678');

        $order->update(['shipping_phone' => '01812345678']);

        $this->assertSame(0, Customer::where('phone', '01712345678')->first()->total_orders);
        $this->assertSame(2, Customer::where('phone', '01812345678')->first()->total_orders);
    }

    public function test_customers_are_per_shop(): void
    {
        $this->order('01712345678');
        $other = Tenant::create(['name' => 'Other', 'email' => 'o@test.local', 'status' => 'active', 'plan' => 'basic']);
        Tenancy::as($other->id, fn () => $this->order('01712345678'));

        $this->assertSame(1, Customer::count());                       // only this shop's
        $this->assertSame(2, Customer::withoutGlobalScopes()->count()); // one per shop
    }

    public function test_list_and_profile_pages_load_and_filter(): void
    {
        $this->order('01712345678', 'delivered', 1000, 'Rahim');
        $this->order('01712345678', 'delivered', 1000, 'Rahim');
        $this->order('01912345678', 'pending', 1000, 'Karim');
        $rahim = Customer::where('phone', '01712345678')->first();

        $this->get(route('admin.customers.index'))->assertOk()->assertSee('Rahim')->assertSee('Karim');
        $this->get(route('admin.customers.index', ['type' => 'repeat']))->assertOk()->assertSee('Rahim')->assertDontSee('Karim');
        $this->get(route('admin.customers.index', ['search' => '+880 1912']))->assertOk()->assertSee('Karim')->assertDontSee('Rahim');
        $this->get(route('admin.customers.show', $rahim))->assertOk()->assertSee('01712345678');
    }

    public function test_blocking_a_customer_blacklists_the_number(): void
    {
        $this->order('01712345678');
        $c = Customer::first();

        $this->patch(route('admin.customers.update', $c), ['is_blocked' => 1, 'name' => 'Rahim'])->assertRedirect();
        $this->assertTrue($c->fresh()->is_blocked);
        $this->assertTrue(BlacklistedPhone::where('phone', '01712345678')->exists());

        $this->patch(route('admin.customers.update', $c), ['is_blocked' => 0, 'name' => 'Rahim']);
        $this->assertFalse(BlacklistedPhone::where('phone', '01712345678')->exists());
    }

    public function test_notes_can_be_added_and_removed(): void
    {
        $this->order('01712345678');
        $c = Customer::first();

        $this->post(route('admin.customers.notes.store', $c), ['note' => 'Call after 5pm'])->assertRedirect();
        $this->assertSame(1, $c->notes()->count());

        $this->delete(route('admin.customers.notes.destroy', $c->notes()->first()))->assertRedirect();
        $this->assertSame(0, $c->notes()->count());
    }

    public function test_view_only_staff_can_see_but_not_edit_or_block(): void
    {
        $this->order('01712345678');
        $c = Customer::first();
        $support = User::factory()->create(['tenant_id' => $this->shop->id, 'role' => 'staff',
            'role_id' => Role::whereNull('tenant_id')->where('name', 'support')->value('id')]);

        $this->actingAs($support);
        $this->get(route('admin.customers.show', $c))->assertOk();
        $this->patch(route('admin.customers.update', $c), ['is_blocked' => 1])->assertSessionHas('permission_error');
        $this->assertFalse($c->fresh()->is_blocked);
    }
}
