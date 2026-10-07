<?php

namespace Tests\Feature;

use App\Enums\PermissionName;
use App\Models\Guest;
use App\Models\HotelBranch;
use App\Models\Payment;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PaymentCrudTest extends TestCase
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
            PermissionName::ViewPayments->value,
            PermissionName::ManagePayments->value,
        ]);

        $this->guest = Guest::create([
            'branch_id' => $this->branch->id,
            'first_name' => 'Ada',
            'last_name' => 'Lovelace',
            'email' => 'ada@example.com',
            'status' => 'active',
        ]);
    }

    private function makePayment(array $overrides = []): Payment
    {
        return Payment::create(array_merge([
            'branch_id' => $this->branch->id,
            'guest_id' => $this->guest->id,
            'payment_number' => 'HQ-PAY-0000001',
            'method' => 'cash',
            'status' => 'completed',
            'amount' => 50000,
            'paid_on' => now()->toDateString(),
        ], $overrides));
    }

    public function test_payment_index_requires_authentication(): void
    {
        $this->get(route('payments.index'))->assertRedirect(route('login'));
    }

    public function test_user_with_view_permission_can_view_payment_listing(): void
    {
        $this->makePayment();

        $this->actingAs($this->user)
            ->get(route('payments.index'))
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->component('Payments/Index')
                    ->has('payments.data', 1)
                    ->where('payments.data.0.payment_number', 'HQ-PAY-0000001')
                    ->where('payments.data.0.status', 'completed'),
            );
    }

    public function test_user_without_permission_cannot_view_payments(): void
    {
        $plain = User::factory()->create();

        $this->actingAs($plain)
            ->get(route('payments.index'))
            ->assertForbidden();
    }

    public function test_create_page_loads_with_guest_options(): void
    {
        $this->actingAs($this->user)
            ->get(route('payments.create'))
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->component('Payments/Create')
                    ->has('guests'),
            );
    }

    public function test_payment_can_be_created_with_generated_number(): void
    {
        $this->actingAs($this->user)
            ->post(route('payments.store'), [
                'branch_id' => $this->branch->id,
                'guest_id' => $this->guest->id,
                'method' => 'card',
                'amount' => 75000,
                'paid_on' => now()->toDateString(),
                'reference' => 'REF-TEST-1',
                'status' => 'completed',
            ])
            ->assertRedirect(route('payments.index'))
            ->assertSessionHas('success');

        $created = Payment::query()->withoutGlobalScope('branch')->latest('id')->first();

        $this->assertNotNull($created);
        $this->assertMatchesRegularExpression('/-PAY-\d{7}$/', $created->payment_number);
        $this->assertSame($this->user->id, $created->received_by);
        $this->assertEquals(75000, (float) $created->amount);
    }

    public function test_payment_requires_valid_amount_and_method(): void
    {
        $this->actingAs($this->user)
            ->from(route('payments.create'))
            ->post(route('payments.store'), [
                'branch_id' => $this->branch->id,
                'guest_id' => $this->guest->id,
                'method' => 'bitcoin',
                'amount' => 0,
                'paid_on' => now()->toDateString(),
                'status' => 'completed',
            ])
            ->assertRedirect(route('payments.create'))
            ->assertSessionHasErrors(['method', 'amount']);
    }

    public function test_user_without_manage_permission_cannot_create_payment(): void
    {
        $viewOnly = User::factory()->create();
        $viewOnly->givePermissionTo(PermissionName::ViewPayments->value);

        $this->actingAs($viewOnly)
            ->post(route('payments.store'), [
                'branch_id' => $this->branch->id,
                'guest_id' => $this->guest->id,
                'method' => 'cash',
                'amount' => 1000,
                'paid_on' => now()->toDateString(),
                'status' => 'completed',
            ])
            ->assertForbidden();

        $this->assertSame(0, Payment::withoutGlobalScope('branch')->count());
    }

    public function test_payment_can_be_updated(): void
    {
        $payment = $this->makePayment();

        $this->actingAs($this->user)
            ->put(route('payments.update', $payment), [
                'branch_id' => $this->branch->id,
                'guest_id' => $this->guest->id,
                'payment_number' => $payment->payment_number,
                'method' => 'bank_transfer',
                'amount' => 60000,
                'paid_on' => $payment->paid_on->toDateString(),
                'status' => 'completed',
            ])
            ->assertRedirect(route('payments.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'method' => 'bank_transfer',
            'status' => 'completed',
        ]);
    }

    public function test_payment_can_be_deleted(): void
    {
        $payment = $this->makePayment();

        $this->actingAs($this->user)
            ->delete(route('payments.destroy', $payment))
            ->assertRedirect(route('payments.index'))
            ->assertSessionHas('success');

        $this->assertSoftDeleted('payments', ['id' => $payment->id]);
    }

    public function test_user_without_manage_permission_cannot_delete_payment(): void
    {
        $viewOnly = User::factory()->create();
        $viewOnly->givePermissionTo(PermissionName::ViewPayments->value);
        $payment = $this->makePayment();

        $this->actingAs($viewOnly)
            ->delete(route('payments.destroy', $payment))
            ->assertForbidden();

        $this->assertDatabaseHas('payments', ['id' => $payment->id]);
    }
}
