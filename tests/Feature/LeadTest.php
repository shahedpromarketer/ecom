<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Lead;
use App\Models\Order;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class LeadTest extends TestCase
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

    private function staff(string $role): User
    {
        return User::factory()->create(['tenant_id' => $this->shop->id, 'role' => 'staff',
            'role_id' => Role::whereNull('tenant_id')->where('name', $role)->value('id')]);
    }

    public function test_add_a_lead_creates_customer_and_blocks_duplicates(): void
    {
        $this->post(route('admin.leads.store'), ['phone' => '+880 1712-345678', 'name' => 'Rahim', 'source' => 'messenger', 'interest' => 'Polo L'])
            ->assertSessionHas('success');
        $this->post(route('admin.leads.store'), ['phone' => '01712345678'])->assertSessionHas('permission_error');

        $this->assertSame(1, Lead::count());
        $this->assertSame('01712345678', Lead::first()->customer->phone);
        $this->assertSame('Rahim', Customer::first()->name);
        $this->assertSame($this->admin->id, Lead::first()->assigned_to);
    }

    public function test_invalid_number_is_rejected(): void
    {
        $this->post(route('admin.leads.store'), ['phone' => '12345'])->assertSessionHasErrors('phone');
    }

    public function test_import_parses_lines_and_shares_out_equally(): void
    {
        $a = $this->staff('order_executive');
        $b = $this->staff('order_executive');

        $this->post(route('admin.leads.import'), [
            'numbers' => "01711111111 Rahim\n01822222222, Karim\n+880 1933-333333 Jamal\nnot a number\n01711111111 duplicate line",
            'staff'   => [$a->id, $b->id],
        ])->assertSessionHas('success', 'Imported 3 lead(s).');

        $this->assertSame(3, Lead::count());
        $this->assertSame(2, Lead::where('assigned_to', $a->id)->count());
        $this->assertSame(1, Lead::where('assigned_to', $b->id)->count());
        $this->assertSame('Karim', Customer::where('phone', '01822222222')->value('name'));
    }

    public function test_import_from_csv_skips_existing_open_leads(): void
    {
        $this->post(route('admin.leads.store'), ['phone' => '01711111111']);
        $csv = UploadedFile::fake()->createWithContent('leads.csv', "name,phone\nRahim,01711111111\nKarim,01822222222\n");

        $this->post(route('admin.leads.import'), ['file' => $csv])
            ->assertSessionHas('success', 'Imported 1 lead(s). 1 skipped (already had an open lead).');
    }

    public function test_an_order_for_the_number_confirms_the_lead(): void
    {
        $this->post(route('admin.leads.store'), ['phone' => '01712345678', 'name' => 'Rahim']);

        $order = Order::create(['shipping_name' => 'Rahim', 'shipping_phone' => '01712345678', 'shipping_address' => 'Dhaka',
            'subtotal' => 900, 'total' => 900, 'payment_method' => 'cod', 'status' => 'pending']);

        $lead = Lead::first();
        $this->assertSame('confirmed', $lead->status);
        $this->assertSame($order->id, $lead->order_id);
    }

    public function test_cancel_and_reopen(): void
    {
        $this->post(route('admin.leads.store'), ['phone' => '01712345678']);
        $lead = Lead::first();

        $this->patch(route('admin.leads.update', $lead), ['action' => 'cancel', 'cancel_reason' => 'Wrong number']);
        $this->assertSame('canceled', $lead->fresh()->status);

        $this->patch(route('admin.leads.update', $lead), ['action' => 'reopen']);
        $this->assertSame('pending', $lead->fresh()->status);
    }

    public function test_pages_and_tabs_load(): void
    {
        $this->post(route('admin.leads.store'), ['phone' => '01712345678', 'name' => 'Rahim', 'interest' => 'Blue polo']);

        $this->get(route('admin.leads.index'))->assertOk()->assertSee('Rahim')->assertSee('Blue polo')->assertSee('NEW');
        $this->get(route('admin.leads.index', ['tab' => 'unread']))->assertOk()->assertSee('Rahim');
        $this->get(route('admin.leads.index', ['tab' => 'confirmed']))->assertOk()->assertDontSee('Blue polo');
    }

    public function test_executives_only_see_their_own_leads_and_cannot_import(): void
    {
        $exec = $this->staff('order_executive');
        $this->post(route('admin.leads.import'), ['numbers' => "01711111111 Mine", 'staff' => [$exec->id]]);
        $this->post(route('admin.leads.store'), ['phone' => '01822222222', 'name' => 'Admins lead']);

        $this->actingAs($exec);
        $this->get(route('admin.leads.index'))->assertSee('Mine')->assertDontSee('Admins lead');
        $this->post(route('admin.leads.import'), ['numbers' => '01933333333'])->assertForbidden();

        $other = Lead::whereHas('customer', fn ($q) => $q->where('phone', '01822222222'))->first();
        $this->patch(route('admin.leads.update', $other), ['action' => 'cancel'])->assertForbidden();
    }

    public function test_support_staff_cannot_open_leads(): void
    {
        $this->actingAs($this->staff('support'));
        // Support can view orders, so they may look at leads, but not add one.
        $this->get(route('admin.leads.index'))->assertOk();
        $this->post(route('admin.leads.store'), ['phone' => '01712345678'])->assertSessionHas('permission_error');
        $this->assertSame(0, Lead::count());
    }
}
