<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StaffPermissionsTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $shop;
    private User $owner;
    private Order $order;

    protected function setUp(): void
    {
        parent::setUp();
        $this->shop  = Tenant::create(['name' => 'Shop', 'email' => 'shop@test.local', 'status' => 'active', 'plan' => 'basic']);
        $this->owner = User::factory()->create(['tenant_id' => $this->shop->id, 'role' => 'admin']);
        $this->shop->update(['owner_id' => $this->owner->id]);

        $this->actingAs($this->owner);
        $this->order = Order::create(['shipping_name' => 'A', 'shipping_phone' => '01700000000', 'shipping_address' => 'Dhaka',
            'subtotal' => 100, 'total' => 100, 'payment_method' => 'cod', 'status' => 'pending']);
        auth()->logout();
    }

    private function staff(string $roleName): User
    {
        $role = Role::whereNull('tenant_id')->where('name', $roleName)->firstOrFail();   // seeded by migration

        return User::factory()->create(['tenant_id' => $this->shop->id, 'role' => 'staff', 'role_id' => $role->id]);
    }

    public function test_staff_can_sign_in_and_see_the_dashboard(): void
    {
        $support = $this->staff('support');

        $this->post('/admin/login', ['email' => $support->email, 'password' => 'password'])->assertRedirect(route('admin.dashboard'));
        $this->get(route('admin.dashboard'))->assertOk();
    }

    public function test_view_only_staff_can_read_but_not_change_orders(): void
    {
        $this->actingAs($this->staff('support'));

        $this->get(route('admin.orders.index'))->assertOk();
        $this->get(route('admin.orders.show', $this->order))->assertOk();

        $this->patch(route('admin.orders.updateStatus', $this->order), ['status' => 'cancelled'])
            ->assertSessionHas('permission_error');
        $this->assertSame('pending', $this->order->fresh()->status);
    }

    public function test_staff_cannot_open_settings_staff_or_courier_pages(): void
    {
        $this->actingAs($this->staff('order_executive'));

        foreach (['admin.settings.index', 'admin.users.index', 'admin.roles.index', 'admin.courier.index', 'admin.integrations.wordpress.index'] as $route) {
            $this->get(route($route))->assertRedirect(route('admin.dashboard'));
        }
    }

    public function test_order_executive_can_change_orders(): void
    {
        $this->actingAs($this->staff('order_executive'));

        $this->patch(route('admin.orders.updateStatus', $this->order), ['status' => 'confirmed'])->assertSessionMissing('permission_error');
        $this->assertSame('confirmed', $this->order->fresh()->status);
    }

    public function test_sidebar_hides_sections_without_permission(): void
    {
        $this->actingAs($this->staff('support'));

        $html = $this->get(route('admin.dashboard'))->getContent();
        $this->assertStringContainsString(route('admin.orders.index'), $html);
        $this->assertStringNotContainsString(route('admin.settings.index'), $html);
        $this->assertStringNotContainsString(route('admin.users.index'), $html);
    }

    public function test_owner_creates_staff_who_can_then_sign_in(): void
    {
        $manager = Role::whereNull('tenant_id')->where('name', 'manager')->first();
        $this->actingAs($this->owner)->post(route('admin.users.store'), [
            'name' => 'Packer One', 'email' => 'packer@test.local', 'password' => 'secret123', 'role_id' => $manager->id,
        ])->assertRedirect(route('admin.users.index'));

        $staff = User::where('email', 'packer@test.local')->first();
        $this->assertSame('staff', $staff->role);
        $this->assertSame($this->shop->id, $staff->tenant_id);
        $this->assertTrue($staff->canUseAdminPanel());
    }

    public function test_staff_cannot_ban_the_owner_or_themselves(): void
    {
        $manager = $this->staff('manager');
        $manager->roleModel->update(['permissions' => array_merge($manager->roleModel->permissions, ['manage_staff'])]);
        $this->actingAs($manager->fresh());

        $this->patchJson(route('admin.users.ban', $this->owner))->assertStatus(422);
        $this->patchJson(route('admin.users.ban', $manager))->assertStatus(422);
        $this->assertFalse($this->owner->fresh()->is_banned);
    }

    public function test_user_without_role_is_logged_out_instead_of_looping(): void
    {
        $nobody = User::factory()->create(['tenant_id' => $this->shop->id, 'role' => 'customer']);

        $this->actingAs($nobody)->get(route('admin.dashboard'))->assertRedirect(route('admin.login'));
        $this->assertGuest();
    }
}
