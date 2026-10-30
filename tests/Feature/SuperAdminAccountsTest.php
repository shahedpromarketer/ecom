<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SuperAdminAccountsTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_sign_up_is_closed(): void
    {
        $this->get('/register')->assertRedirect('/admin/login');
        $this->post('/register', ['shop_name' => 'X', 'name' => 'X', 'email' => 'x@test.local', 'password' => 'secret123', 'password_confirmation' => 'secret123'])
            ->assertRedirect('/admin/login');

        $this->assertSame(0, Tenant::count());
        $this->assertSame(0, User::count());
    }

    public function test_super_admin_sets_a_new_password_for_a_client(): void
    {
        $shop  = Tenant::create(['name' => 'Shop', 'email' => 's@test.local', 'status' => 'active', 'plan' => 'basic']);
        $owner = User::factory()->create(['tenant_id' => $shop->id, 'role' => 'admin', 'email' => 'owner@test.local']);
        $other = Tenant::create(['name' => 'Other', 'email' => 'o@test.local', 'status' => 'active', 'plan' => 'basic']);
        $super = User::factory()->create(['is_super_admin' => true, 'role' => 'admin', 'tenant_id' => null]);

        $this->actingAs($super)->get(route('super.tenants.show', $shop->id))->assertOk()->assertSee('Set new password');
        $this->post(route('super.tenants.user-password', [$shop->id, $owner->id]), ['password' => 'short'])->assertSessionHasErrors('password');
        $this->post(route('super.tenants.user-password', [$shop->id, $owner->id]), ['password' => 'NewPass2026'])->assertSessionHas('success');
        $this->assertTrue(Hash::check('NewPass2026', $owner->fresh()->password));

        // A user of another shop can't be reached through this shop.
        $this->post(route('super.tenants.user-password', [$other->id, $owner->id]), ['password' => 'Hacked2026'])->assertNotFound();

        // Only super admins.
        $this->actingAs($owner->fresh())->post(route('super.tenants.user-password', [$shop->id, $owner->id]), ['password' => 'Mine20266'])->assertForbidden();
        $this->assertTrue(Hash::check('NewPass2026', $owner->fresh()->password));
    }
}
