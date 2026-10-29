<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Followup;
use App\Models\Order;
use App\Models\Role;
use App\Models\Setting;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FollowupTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $shop;
    private User $admin;
    private Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->shop  = Tenant::create(['name' => 'Shop', 'email' => 'shop@test.local', 'status' => 'active', 'plan' => 'basic']);
        $this->admin = User::factory()->create(['tenant_id' => $this->shop->id, 'role' => 'admin']);
        $this->actingAs($this->admin);

        $this->order();
        $this->customer = Customer::first();
    }

    private function order(string $status = 'pending', bool $skip = false): Order
    {
        return Order::create(['shipping_name' => 'Rahim', 'shipping_phone' => '01712345678', 'shipping_address' => 'Dhaka',
            'subtotal' => 900, 'total' => 900, 'payment_method' => 'cod', 'status' => $status, 'skip_followup' => $skip,
            'assigned_to' => $this->admin->id]);
    }

    private function staff(string $role): User
    {
        return User::factory()->create(['tenant_id' => $this->shop->id, 'role' => 'staff',
            'role_id' => Role::whereNull('tenant_id')->where('name', $role)->value('id')]);
    }

    public function test_delivery_creates_one_automatic_reorder_followup(): void
    {
        $order = $this->order();
        $order->update(['status' => 'delivered']);
        $order->update(['status' => 'completed']);   // no second one

        $this->assertSame(1, Followup::count());
        $f = Followup::first();
        $this->assertSame('reorder', $f->tag);
        $this->assertSame(now()->addDays(7)->toDateString(), $f->scheduled_for->toDateString());
        $this->assertSame($this->admin->id, $f->assigned_to);
    }

    public function test_auto_followup_respects_skip_flag_and_setting(): void
    {
        $this->order('pending', true)->update(['status' => 'delivered']);
        $this->assertSame(0, Followup::count());

        Setting::setValue('followup_after_delivery_days', 0, 'order_rules', $this->shop->id);
        $this->order()->update(['status' => 'delivered']);
        $this->assertSame(0, Followup::count());
    }

    public function test_schedule_log_call_and_book_next(): void
    {
        $this->post(route('admin.followups.store'), [
            'customer_id' => $this->customer->id, 'scheduled_for' => today()->toDateString(), 'tag' => 'feedback', 'note' => 'Ask about size',
        ])->assertSessionHas('success');

        $f = Followup::first();
        $this->get(route('admin.followups.index'))->assertOk()->assertSee('Rahim')->assertSee('Ask about size');

        $this->patch(route('admin.followups.complete', $f), [
            'status' => 'done', 'result_note' => 'Liked it', 'next_date' => today()->addDays(10)->toDateString(),
        ])->assertSessionHas('success');

        $this->assertSame('done', $f->fresh()->status);
        $this->assertSame(1, Followup::where('status', 'listed')->whereDate('scheduled_for', today()->addDays(10))->count());
        $this->assertStringContainsString('Liked it', $this->customer->notes()->first()->note);
    }

    public function test_tabs_split_today_overdue_upcoming(): void
    {
        foreach ([-2 => 'Overdue guy', 0 => 'Today guy', 5 => 'Later guy'] as $days => $note) {
            Followup::create(['customer_id' => $this->customer->id, 'assigned_to' => $this->admin->id,
                'scheduled_for' => today()->addDays($days), 'status' => 'listed', 'note' => $note]);
        }

        $this->get(route('admin.followups.index', ['tab' => 'today']))->assertSee('Today guy')->assertDontSee('Overdue guy')->assertDontSee('Later guy');
        $this->get(route('admin.followups.index', ['tab' => 'overdue']))->assertSee('Overdue guy')->assertDontSee('Today guy');
        $this->get(route('admin.followups.index', ['tab' => 'upcoming']))->assertSee('Later guy')->assertDontSee('Today guy');
    }

    public function test_support_staff_only_see_and_complete_their_own(): void
    {
        $support = $this->staff('support');
        $mine   = Followup::create(['customer_id' => $this->customer->id, 'assigned_to' => $support->id, 'scheduled_for' => today(), 'status' => 'listed', 'note' => 'Mine to call']);
        $theirs = Followup::create(['customer_id' => $this->customer->id, 'assigned_to' => $this->admin->id, 'scheduled_for' => today(), 'status' => 'listed', 'note' => 'Admin call']);

        $this->actingAs($support);
        $this->get(route('admin.followups.index'))->assertOk()->assertSee('Mine to call')->assertDontSee('Admin call');

        $this->patch(route('admin.followups.complete', $theirs), ['status' => 'done'])->assertForbidden();
        $this->patch(route('admin.followups.complete', $mine), ['status' => 'done'])->assertSessionHas('success');

        // Moving follow-ups to other staff is for managers.
        $this->post(route('admin.followups.bulk'), ['ids' => [$mine->id], 'action' => 'assign', 'assigned_to' => $this->admin->id])
            ->assertSessionHas('permission_error');
    }

    public function test_bulk_reschedule(): void
    {
        $f = Followup::create(['customer_id' => $this->customer->id, 'assigned_to' => $this->admin->id, 'scheduled_for' => today(), 'status' => 'listed']);

        $this->post(route('admin.followups.bulk'), ['ids' => [$f->id], 'action' => 'reschedule', 'scheduled_for' => today()->addDays(3)->toDateString()]);

        $this->assertSame(today()->addDays(3)->toDateString(), $f->fresh()->scheduled_for->toDateString());
    }

    public function test_followups_are_per_shop(): void
    {
        Followup::create(['customer_id' => $this->customer->id, 'assigned_to' => $this->admin->id, 'scheduled_for' => today(), 'status' => 'listed']);
        $other = Tenant::create(['name' => 'Other', 'email' => 'o@test.local', 'status' => 'active', 'plan' => 'basic']);
        $otherAdmin = User::factory()->create(['tenant_id' => $other->id, 'role' => 'admin']);

        $this->actingAs($otherAdmin);
        $this->assertSame(0, Followup::count());
        $this->post(route('admin.followups.store'), ['customer_id' => $this->customer->id, 'scheduled_for' => today()->toDateString()])
            ->assertSessionHasErrors('customer_id');
    }
}
