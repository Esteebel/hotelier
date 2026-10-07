<?php

namespace Tests\Feature;

use App\Enums\PermissionName;
use App\Models\Guest;
use App\Models\HotelBranch;
use App\Models\Invoice;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class InvoiceCrudTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private HotelBranch $branch;

    private Guest $guest;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionSeeder::class);

        $this->branch = HotelBranch::factory()->create();

        $this->user = User::factory()->create();
        $this->user->givePermissionTo([
            PermissionName::ViewInvoices->value,
            PermissionName::ManageInvoices->value,
        ]);

        $this->guest = Guest::create([
            'branch_id' => $this->branch->id,
            'first_name' => 'Ada',
            'last_name' => 'Lovelace',
            'email' => 'ada@example.com',
            'status' => 'active',
        ]);
    }

    private function makeInvoice(array $overrides = []): Invoice
    {
        return Invoice::create(array_merge([
            'branch_id' => $this->branch->id,
            'guest_id' => $this->guest->id,
            'invoice_number' => 'HQ-INV-0000001',
            'issued_on' => now()->toDateString(),
            'due_on' => now()->addWeek()->toDateString(),
            'status' => 'sent',
            'subtotal' => 100000,
            'tax' => 7500,
            'discount' => 5000,
            'total' => 102500,
            'amount_paid' => 0,
        ], $overrides));
    }

    public function test_invoice_index_requires_authentication(): void
    {
        $this->get(route('invoices.index'))->assertRedirect(route('login'));
    }

    public function test_user_with_view_permission_can_view_invoice_listing(): void
    {
        $this->makeInvoice();

        $this->actingAs($this->user)
            ->get(route('invoices.index'))
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->component('Invoices/Index')
                    ->has('invoices.data', 1)
                    ->where('invoices.data.0.number', 'HQ-INV-0000001')
                    ->where('invoices.data.0.status', 'sent'),
            );
    }

    public function test_user_without_permission_cannot_view_invoices(): void
    {
        $plain = User::factory()->create();

        $this->actingAs($plain)
            ->get(route('invoices.index'))
            ->assertForbidden();
    }

    public function test_create_page_loads_with_guest_and_reservation_options(): void
    {
        $this->actingAs($this->user)
            ->get(route('invoices.create'))
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->component('Invoices/Create')
                    ->has('guests')
                    ->has('reservations'),
            );
    }

    public function test_invoice_can_be_created_with_computed_total(): void
    {
        $this->actingAs($this->user)
            ->post(route('invoices.store'), [
                'branch_id' => $this->branch->id,
                'guest_id' => $this->guest->id,
                'issued_on' => now()->toDateString(),
                'due_on' => now()->addDays(14)->toDateString(),
                'subtotal' => 200000,
                'tax' => 15000,
                'discount' => 10000,
                'status' => 'draft',
                'notes' => 'Suite invoice',
            ])
            ->assertRedirect(route('invoices.index'))
            ->assertSessionHas('success');

        $created = Invoice::query()->withoutGlobalScope('branch')->latest('id')->first();

        $this->assertNotNull($created);
        $this->assertMatchesRegularExpression('/-INV-\d{7}$/', $created->invoice_number);
        $this->assertSame($this->user->id, $created->created_by);
        $this->assertEquals(0, (float) $created->amount_paid);
        $this->assertEquals(205000, (float) $created->total);
    }

    public function test_invoice_requires_required_financial_fields(): void
    {
        $this->actingAs($this->user)
            ->from(route('invoices.create'))
            ->post(route('invoices.store'), [
                'branch_id' => $this->branch->id,
                'guest_id' => $this->guest->id,
                'status' => 'draft',
            ])
            ->assertRedirect(route('invoices.create'))
            ->assertSessionHasErrors(['issued_on', 'subtotal', 'tax', 'discount']);
    }

    public function test_user_without_manage_permission_cannot_create_invoice(): void
    {
        $viewOnly = User::factory()->create();
        $viewOnly->givePermissionTo(PermissionName::ViewInvoices->value);

        $this->actingAs($viewOnly)
            ->post(route('invoices.store'), [
                'branch_id' => $this->branch->id,
                'guest_id' => $this->guest->id,
                'issued_on' => now()->toDateString(),
                'due_on' => now()->addWeek()->toDateString(),
                'subtotal' => 1000,
                'tax' => 0,
                'discount' => 0,
                'status' => 'draft',
            ])
            ->assertForbidden();

        $this->assertSame(0, Invoice::withoutGlobalScope('branch')->count());
    }

    public function test_invoice_can_be_updated(): void
    {
        $invoice = $this->makeInvoice();

        $this->actingAs($this->user)
            ->put(route('invoices.update', $invoice), [
                'branch_id' => $this->branch->id,
                'guest_id' => $this->guest->id,
                'invoice_number' => $invoice->invoice_number,
                'issued_on' => $invoice->issued_on->toDateString(),
                'due_on' => $invoice->due_on->toDateString(),
                'subtotal' => 120000,
                'tax' => 9000,
                'discount' => 0,
                'total' => 129000,
                'amount_paid' => 50000,
                'status' => 'partially_paid',
            ])
            ->assertRedirect(route('invoices.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('invoices', [
            'id' => $invoice->id,
            'status' => 'partially_paid',
        ]);
    }

    public function test_invoice_can_be_deleted(): void
    {
        $invoice = $this->makeInvoice();

        $this->actingAs($this->user)
            ->delete(route('invoices.destroy', $invoice))
            ->assertRedirect(route('invoices.index'))
            ->assertSessionHas('success');

        $this->assertSoftDeleted('invoices', ['id' => $invoice->id]);
    }

    public function test_user_without_manage_permission_cannot_delete_invoice(): void
    {
        $viewOnly = User::factory()->create();
        $viewOnly->givePermissionTo(PermissionName::ViewInvoices->value);
        $invoice = $this->makeInvoice();

        $this->actingAs($viewOnly)
            ->delete(route('invoices.destroy', $invoice))
            ->assertForbidden();

        $this->assertDatabaseHas('invoices', ['id' => $invoice->id]);
    }
}
