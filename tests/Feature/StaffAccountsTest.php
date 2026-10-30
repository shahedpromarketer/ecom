<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class StaffAccountsTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $shop;
    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();
        $this->shop  = Tenant::create(['name' => 'Shop', 'email' => 's@test.local', 'status' => 'active', 'plan' => 'basic', 'max_users' => 10]);
        $this->owner = User::factory()->create(['tenant_id' => $this->shop->id, 'role' => 'admin', 'email' => 'owner@test.local']);
        $this->shop->update(['owner_id' => $this->owner->id]);
    }

    private function role(string $name): int
    {
        return (int) Role::withoutGlobalScopes()->whereNull('tenant_id')->where('name', $name)->value('id');
    }

    public function test_starter_roles_exist(): void
    {
        $this->assertSame('Sales Executive', Role::withoutGlobalScopes()->whereNull('tenant_id')->where('name', 'order_executive')->value('display_name'));
        $packing = Role::withoutGlobalScopes()->whereNull('tenant_id')->where('name', 'packing')->first();
        $this->assertSame('Packing & Dispatch', $packing->display_name);
        $this->assertContains('manage_orders', $packing->permissions);
    }

    public function test_owner_adds_staff_with_only_a_mobile_number_who_signs_in_with_it(): void
    {
        $this->actingAs($this->owner)->post(route('admin.users.store'), [
            'name' => 'Rahim', 'phone' => '+880 1711-223344', 'password' => 'Sales2026', 'role_id' => $this->role('order_executive'),
        ])->assertRedirect(route('admin.users.index'));

        $staff = User::where('name', 'Rahim')->first();
        $this->assertNull($staff->email);
        $this->assertSame('01711223344', $staff->phone);
        $this->assertSame('staff', $staff->role);

        // Same number twice: refused. Neither email nor phone: refused.
        $this->post(route('admin.users.store'), ['name' => 'X', 'phone' => '01711223344', 'password' => 'Sales2026', 'role_id' => $this->role('support')])
            ->assertSessionHasErrors('phone');
        $this->post(route('admin.users.store'), ['name' => 'Y', 'password' => 'Sales2026', 'role_id' => $this->role('support')])
            ->assertSessionHasErrors('email');

        auth()->logout();
        $this->post(route('admin.login'), ['email' => '01711-223344', 'password' => 'wrong'])->assertSessionHasErrors('email');
        $this->post(route('admin.login'), ['email' => '01711-223344', 'password' => 'Sales2026'])->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticatedAs($staff);
    }

    public function test_email_login_still_works(): void
    {
        $this->owner->forceFill(['password' => Hash::make('Owner2026')])->save();
        $this->post(route('admin.login'), ['email' => 'owner@test.local', 'password' => 'Owner2026'])->assertRedirect(route('admin.dashboard'));
    }

    public function test_owner_sets_a_new_password_for_staff_but_not_for_themself(): void
    {
        $staff = User::factory()->create(['tenant_id' => $this->shop->id, 'role' => 'staff', 'role_id' => $this->role('packing'), 'phone' => '01811000000']);
        $this->actingAs($this->owner)->get(route('admin.users.edit', $staff))->assertOk()->assertSee('New password');

        $this->put(route('admin.users.update', $staff), ['name' => $staff->name, 'email' => $staff->email, 'phone' => $staff->phone,
            'role_id' => $staff->role_id, 'password' => 'Packer2026'])->assertSessionHas('success');
        $this->assertTrue(Hash::check('Packer2026', $staff->fresh()->password));

        // Saving without a password keeps it.
        $this->put(route('admin.users.update', $staff), ['name' => 'Packer', 'email' => $staff->email, 'phone' => $staff->phone, 'role_id' => $staff->role_id]);
        $this->assertTrue(Hash::check('Packer2026', $staff->fresh()->password));
        $this->assertSame('Packer', $staff->fresh()->name);

        // Staff of another shop: no.
        $other = Tenant::create(['name' => 'O', 'email' => 'o@test.local', 'status' => 'active', 'plan' => 'basic']);
        $stranger = User::factory()->create(['tenant_id' => $other->id, 'role' => 'admin']);
        $this->put(route('admin.users.update', $stranger), ['name' => 'x', 'email' => $stranger->email, 'role_id' => $this->role('support'), 'password' => 'Hacked2026'])
            ->assertForbidden();
        $this->assertFalse(Hash::check('Hacked2026', $stranger->fresh()->password));
    }
}
