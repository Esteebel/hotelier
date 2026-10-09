<?php

namespace Tests\Feature;

use App\Enums\PermissionName;
use App\Events\PaymentReceived;
use App\Models\Guest;
use App\Models\HotelBranch;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Reservation;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class PaymentBalanceSyncTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private HotelBranch $branch;

    private Guest $guest;

    private Reservation $reservation;

    private Invoice $invoice;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionSeeder::class);

        $this->branch = HotelBranch::factory()->create([
            'status' => 'active',
            'is_default' => true,
        ]);

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

        $this->reservation = Reservation::create([
            'branch_id' => $this->branch->id,
            'guest_id' => $this->guest->id,
            'reservation_number' => 'HQ-RSV-0000001',
            'check_in' => now()->toDateString(),
            'check_out' => now()->addDays(3)->toDateString(),
            'adults' => 2,
            'children' => 0,
            'status' => 'checked_in',
            'total_amount' => 150000,
            'balance_due' => 150000,
        ]);

        $this->invoice = Invoice::create([
            'branch_id' => $this->branch->id,
            'reservation_id' => $this->reservation->id,
            'guest_id' => $this->guest->id,
            'invoice_number' => 'HQ-INV-0000001',
            'issued_on' => now()->toDateString(),
            'due_on' => now()->toDateString(),
            'subtotal' => 150000,
            'tax' => 0,
            'discount' => 0,
            'total' => 150000,
            'amount_paid' => 0,
            'status' => 'sent',
        ]);
    }

    public function test_completed_payment_syncs_invoice_and_reservation_balances(): void
    {
        Event::fake([PaymentReceived::class]);

        $this->actingAs($this->user)
            ->post(route('payments.store'), [
                'branch_id' => $this->branch->id,
                'guest_id' => $this->guest->id,
                'reservation_id' => $this->reservation->id,
                'invoice_id' => $this->invoice->id,
                'method' => 'card',
                'amount' => 40000,
                'paid_on' => now()->toDateString(),
                'status' => 'completed',
            ])
            ->assertRedirect(route('payments.index'));

        Event::assertDispatched(PaymentReceived::class);

        $invoice = $this->invoice->fresh();
        $this->assertEquals(40000, (float) $invoice->amount_paid);
        $this->assertSame('partially_paid', $invoice->status->value);

        $this->assertEquals(110000, (float) $this->reservation->fresh()->balance_due);
    }

    public function test_fully_settled_payment_marks_the_invoice_paid(): void
    {
        $this->actingAs($this->user)
            ->post(route('payments.store'), [
                'branch_id' => $this->branch->id,
                'guest_id' => $this->guest->id,
                'reservation_id' => $this->reservation->id,
                'invoice_id' => $this->invoice->id,
                'method' => 'bank_transfer',
                'amount' => 150000,
                'paid_on' => now()->toDateString(),
                'status' => 'completed',
            ])
            ->assertRedirect(route('payments.index'));

        $invoice = $this->invoice->fresh();
        $this->assertEquals(150000, (float) $invoice->amount_paid);
        $this->assertSame('paid', $invoice->status->value);
        $this->assertEquals(0, (float) $this->reservation->fresh()->balance_due);
    }

    public function test_pending_payments_do_not_reduce_the_balance(): void
    {
        $this->actingAs($this->user)
            ->post(route('payments.store'), [
                'branch_id' => $this->branch->id,
                'guest_id' => $this->guest->id,
                'reservation_id' => $this->reservation->id,
                'invoice_id' => $this->invoice->id,
                'method' => 'cash',
                'amount' => 40000,
                'paid_on' => now()->toDateString(),
                'status' => 'pending',
            ])
            ->assertRedirect(route('payments.index'));

        $this->assertEquals(0, (float) $this->invoice->fresh()->amount_paid);
        $this->assertEquals(150000, (float) $this->reservation->fresh()->balance_due);
    }

    public function test_payment_status_change_re_syncs_balances(): void
    {
        $payment = Payment::create([
            'branch_id' => $this->branch->id,
            'guest_id' => $this->guest->id,
            'reservation_id' => $this->reservation->id,
            'invoice_id' => $this->invoice->id,
            'payment_number' => 'HQ-PAY-0000001',
            'method' => 'cash',
            'status' => 'completed',
            'amount' => 60000,
            'paid_on' => now()->toDateString(),
        ]);

        $this->actingAs($this->user)
            ->put(route('payments.update', $payment), [
                'branch_id' => $this->branch->id,
                'guest_id' => $this->guest->id,
                'reservation_id' => $this->reservation->id,
                'invoice_id' => $this->invoice->id,
                'payment_number' => $payment->payment_number,
                'method' => 'cash',
                'amount' => 60000,
                'paid_on' => now()->toDateString(),
                'status' => 'refunded',
            ])
            ->assertRedirect(route('payments.index'));

        $this->assertEquals(0, (float) $this->invoice->fresh()->amount_paid);
        $this->assertSame('sent', $this->invoice->fresh()->status->value);
        $this->assertEquals(150000, (float) $this->reservation->fresh()->balance_due);
    }

    public function test_deleting_a_payment_restores_the_balance(): void
    {
        $payment = Payment::create([
            'branch_id' => $this->branch->id,
            'guest_id' => $this->guest->id,
            'reservation_id' => $this->reservation->id,
            'invoice_id' => $this->invoice->id,
            'payment_number' => 'HQ-PAY-0000001',
            'method' => 'cash',
            'status' => 'completed',
            'amount' => 60000,
            'paid_on' => now()->toDateString(),
        ]);

        $this->actingAs($this->user)
            ->delete(route('payments.destroy', $payment))
            ->assertRedirect(route('payments.index'));

        $this->assertEquals(0, (float) $this->invoice->fresh()->amount_paid);
        $this->assertSame('sent', $this->invoice->fresh()->status->value);
        $this->assertEquals(150000, (float) $this->reservation->fresh()->balance_due);
    }
}
