<?php

namespace Tests\Feature;

use App\Enums\PermissionName;
use App\Enums\RoomStatus;
use App\Events\CheckinCompleted;
use App\Events\CheckoutCompleted;
use App\Models\Checkin;
use App\Models\Checkout;
use App\Models\Guest;
use App\Models\HotelBranch;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CheckinCheckoutTest extends TestCase
{
    use RefreshDatabase;

    private HotelBranch $branch;

    private User $user;

    private Guest $guest;

    private Room $room;

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
            PermissionName::ManageCheckins->value,
            PermissionName::ManageCheckouts->value,
        ]);

        $this->guest = Guest::create([
            'branch_id' => $this->branch->id,
            'first_name' => 'Ada',
            'last_name' => 'Lovelace',
            'email' => 'ada@example.com',
            'status' => 'active',
        ]);

        $this->room = Room::factory()->create([
            'branch_id' => $this->branch->id,
            'price' => 50000,
            'capacity' => 4,
            'status' => RoomStatus::Available,
            'is_active' => true,
        ]);
    }

    private function makeReservation(array $overrides = []): Reservation
    {
        return Reservation::create(array_merge([
            'branch_id' => $this->branch->id,
            'guest_id' => $this->guest->id,
            'room_id' => $this->room->id,
            'reservation_number' => 'HQ-RSV-0000001',
            'check_in' => now()->toDateString(),
            'check_out' => now()->addDays(3)->toDateString(),
            'adults' => 2,
            'children' => 0,
            'status' => 'confirmed',
            'total_amount' => 150000,
            'balance_due' => 150000,
        ], $overrides));
    }

    public function test_checkin_marks_the_stay_in_progress(): void
    {
        Event::fake([CheckinCompleted::class]);

        $reservation = $this->makeReservation();
        $this->room->update(['status' => RoomStatus::Reserved]);

        $this->actingAs($this->user)
            ->post(route('checkins.store'), [
                'reservation_id' => $reservation->id,
                'room_id' => $this->room->id,
                'guest_id' => $this->guest->id,
                'notes' => 'Early arrival',
            ])
            ->assertRedirect(route('checkins.index'));

        Event::assertDispatched(CheckinCompleted::class);

        $this->assertSame('checked_in', $reservation->fresh()->status->value);
        $this->assertSame($this->room->id, (int) $reservation->fresh()->room_id);
        $this->assertSame('occupied', $this->room->fresh()->status->value);
        $this->assertDatabaseHas('checkins', [
            'reservation_id' => $reservation->id,
            'room_id' => $this->room->id,
            'status' => 'checked_in',
        ]);
    }

    public function test_checkin_rejects_a_reservation_that_is_not_confirmed(): void
    {
        $reservation = $this->makeReservation(['status' => 'checked_in']);

        $this->actingAs($this->user)
            ->post(route('checkins.store'), [
                'reservation_id' => $reservation->id,
                'room_id' => $this->room->id,
                'guest_id' => $this->guest->id,
            ])
            ->assertSessionHasErrors('reservation_id');

        $this->assertDatabaseCount('checkins', 0);
    }

    public function test_checkin_rejects_a_guest_that_does_not_match_the_reservation(): void
    {
        $reservation = $this->makeReservation();

        $otherGuest = Guest::create([
            'branch_id' => $this->branch->id,
            'first_name' => 'Eve',
            'last_name' => 'Mallory',
            'email' => 'eve@example.com',
            'status' => 'active',
        ]);

        $this->actingAs($this->user)
            ->post(route('checkins.store'), [
                'reservation_id' => $reservation->id,
                'room_id' => $this->room->id,
                'guest_id' => $otherGuest->id,
            ])
            ->assertSessionHasErrors('guest_id');

        $this->assertDatabaseCount('checkins', 0);
        $this->assertSame('confirmed', $reservation->fresh()->status->value);
    }

    public function test_checkin_rejects_a_room_that_is_not_assigned_to_the_reservation(): void
    {
        $reservation = $this->makeReservation();

        $otherRoom = Room::factory()->create([
            'branch_id' => $this->branch->id,
            'price' => 50000,
            'status' => RoomStatus::Available,
        ]);

        $this->actingAs($this->user)
            ->post(route('checkins.store'), [
                'reservation_id' => $reservation->id,
                'room_id' => $otherRoom->id,
                'guest_id' => $this->guest->id,
            ])
            ->assertSessionHasErrors('room_id');

        $this->assertDatabaseCount('checkins', 0);
    }

    public function test_checkout_generates_a_final_invoice_and_syncs_balances(): void
    {
        Event::fake([CheckoutCompleted::class]);

        $reservation = $this->makeReservation(['status' => 'checked_in']);
        $this->room->update(['status' => RoomStatus::Occupied]);

        Payment::create([
            'branch_id' => $this->branch->id,
            'guest_id' => $this->guest->id,
            'reservation_id' => $reservation->id,
            'payment_number' => 'HQ-PAY-0000001',
            'method' => 'cash',
            'status' => 'completed',
            'amount' => 50000,
            'paid_on' => now()->toDateString(),
        ]);

        $this->actingAs($this->user)
            ->post(route('checkouts.store'), [
                'reservation_id' => $reservation->id,
                'room_id' => $this->room->id,
                'guest_id' => $this->guest->id,
            ])
            ->assertRedirect(route('checkouts.index'));

        Event::assertDispatched(CheckoutCompleted::class);

        $fresh = $reservation->fresh();
        $this->assertSame('checked_out', $fresh->status->value);
        $this->assertEquals(100000, (float) $fresh->balance_due);
        $this->assertSame('dirty', $this->room->fresh()->status->value);

        $this->assertDatabaseHas('checkouts', [
            'reservation_id' => $reservation->id,
            'status' => 'completed',
        ]);

        $invoice = Invoice::query()->where('reservation_id', $reservation->id)->firstOrFail();
        $this->assertEquals(150000, (float) $invoice->total);
        $this->assertEquals(50000, (float) $invoice->amount_paid);
        $this->assertSame('partially_paid', $invoice->status->value);
    }

    public function test_checkout_generates_an_unpaid_invoice_when_no_payments_exist(): void
    {
        $reservation = $this->makeReservation(['status' => 'checked_in']);
        $this->room->update(['status' => RoomStatus::Occupied]);

        $this->actingAs($this->user)
            ->post(route('checkouts.store'), [
                'reservation_id' => $reservation->id,
                'room_id' => $this->room->id,
                'guest_id' => $this->guest->id,
            ])
            ->assertRedirect(route('checkouts.index'));

        $invoice = Invoice::query()->where('reservation_id', $reservation->id)->firstOrFail();
        $this->assertEquals(150000, (float) $invoice->total);
        $this->assertEquals(0, (float) $invoice->amount_paid);
        $this->assertSame('sent', $invoice->status->value);
        $this->assertEquals(150000, (float) $reservation->fresh()->balance_due);
    }

    public function test_checkout_does_not_duplicate_an_existing_invoice(): void
    {
        $reservation = $this->makeReservation(['status' => 'checked_in']);
        $this->room->update(['status' => RoomStatus::Occupied]);

        Invoice::create([
            'branch_id' => $this->branch->id,
            'reservation_id' => $reservation->id,
            'guest_id' => $this->guest->id,
            'invoice_number' => 'HQ-INV-0000009',
            'issued_on' => now()->toDateString(),
            'due_on' => now()->toDateString(),
            'subtotal' => 150000,
            'tax' => 0,
            'discount' => 0,
            'total' => 150000,
            'amount_paid' => 0,
            'status' => 'sent',
        ]);

        $this->actingAs($this->user)
            ->post(route('checkouts.store'), [
                'reservation_id' => $reservation->id,
                'room_id' => $this->room->id,
                'guest_id' => $this->guest->id,
            ])
            ->assertRedirect(route('checkouts.index'));

        $this->assertSame(1, Invoice::count());
    }

    public function test_checkout_rejects_a_reservation_that_has_not_checked_in(): void
    {
        $reservation = $this->makeReservation(['status' => 'confirmed']);

        $this->actingAs($this->user)
            ->post(route('checkouts.store'), [
                'reservation_id' => $reservation->id,
                'room_id' => $this->room->id,
                'guest_id' => $this->guest->id,
            ])
            ->assertSessionHasErrors('reservation_id');

        $this->assertDatabaseCount('checkouts', 0);
        $this->assertSame(0, Invoice::count());
    }

    public function test_checkin_index_lists_checkins_with_processor(): void
    {
        $reservation = $this->makeReservation();
        $this->room->update(['status' => RoomStatus::Reserved]);

        $this->actingAs($this->user)
            ->post(route('checkins.store'), [
                'reservation_id' => $reservation->id,
                'room_id' => $this->room->id,
                'guest_id' => $this->guest->id,
            ])
            ->assertRedirect(route('checkins.index'));

        $this->actingAs($this->user)
            ->get(route('checkins.index'))
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->component('Checkins/Index')
                    ->has('checkins.data', 1)
                    ->where('checkins.data.0.processor.name', $this->user->name),
            );
    }

    public function test_checkout_index_lists_checkouts_with_processor(): void
    {
        $reservation = $this->makeReservation(['status' => 'checked_in']);
        $this->room->update(['status' => RoomStatus::Occupied]);

        $this->actingAs($this->user)
            ->post(route('checkouts.store'), [
                'reservation_id' => $reservation->id,
                'room_id' => $this->room->id,
                'guest_id' => $this->guest->id,
            ])
            ->assertRedirect(route('checkouts.index'));

        $this->actingAs($this->user)
            ->get(route('checkouts.index'))
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->component('Checkouts/Index')
                    ->has('checkouts.data', 1)
                    ->where('checkouts.data.0.processor.name', $this->user->name),
            );
    }

    private function storeCheckin(): Checkin
    {
        $reservation = $this->makeReservation();
        $this->room->update(['status' => RoomStatus::Reserved]);

        $this->actingAs($this->user)
            ->post(route('checkins.store'), [
                'reservation_id' => $reservation->id,
                'room_id' => $this->room->id,
                'guest_id' => $this->guest->id,
                'notes' => 'Early arrival',
            ])
            ->assertRedirect(route('checkins.index'));

        return Checkin::firstOrFail();
    }

    private function storeCheckout(): Checkout
    {
        $reservation = $this->makeReservation(['status' => 'checked_in']);
        $this->room->update(['status' => RoomStatus::Occupied]);

        $this->actingAs($this->user)
            ->post(route('checkouts.store'), [
                'reservation_id' => $reservation->id,
                'room_id' => $this->room->id,
                'guest_id' => $this->guest->id,
            ])
            ->assertRedirect(route('checkouts.index'));

        return Checkout::firstOrFail();
    }

    public function test_checkin_edit_page_loads(): void
    {
        $checkin = $this->storeCheckin();

        $this->actingAs($this->user)
            ->get(route('checkins.edit', $checkin))
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->component('Checkins/Edit')
                    ->where('checkin.id', $checkin->id),
            );
    }

    public function test_checkin_update_persists_notes(): void
    {
        $checkin = $this->storeCheckin();

        $this->actingAs($this->user)
            ->put(route('checkins.update', $checkin), [
                'reservation_id' => $checkin->reservation_id,
                'room_id' => $checkin->room_id,
                'guest_id' => $checkin->guest_id,
                'notes' => 'Updated check-in notes',
            ])
            ->assertRedirect(route('checkins.index'));

        $this->assertDatabaseHas('checkins', [
            'id' => $checkin->id,
            'notes' => 'Updated check-in notes',
        ]);
    }

    public function test_checkin_complete_marks_completed(): void
    {
        $checkin = $this->storeCheckin();

        $this->actingAs($this->user)
            ->post(route('checkins.complete', $checkin))
            ->assertRedirect(route('checkins.index'));

        $this->assertDatabaseHas('checkins', [
            'id' => $checkin->id,
            'status' => 'completed',
            'processed_by' => $this->user->id,
        ]);
    }

    public function test_checkin_destroy_restores_room_to_reserved(): void
    {
        $checkin = $this->storeCheckin();

        $this->actingAs($this->user)
            ->delete(route('checkins.destroy', $checkin))
            ->assertRedirect(route('checkins.index'));

        $this->assertSame(0, Checkin::count());
        $this->assertSame('reserved', $this->room->fresh()->status->value);
    }

    public function test_checkout_edit_page_loads(): void
    {
        $checkout = $this->storeCheckout();

        $this->actingAs($this->user)
            ->get(route('checkouts.edit', $checkout))
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->component('Checkouts/Edit')
                    ->where('checkout.id', $checkout->id),
            );
    }

    public function test_checkout_complete_records_processor(): void
    {
        $checkout = $this->storeCheckout();

        $this->actingAs($this->user)
            ->post(route('checkouts.complete', $checkout))
            ->assertRedirect(route('checkouts.index'));

        $this->assertDatabaseHas('checkouts', [
            'id' => $checkout->id,
            'status' => 'completed',
            'processed_by' => $this->user->id,
        ]);
    }

    public function test_checkout_destroy_restores_room_and_reservation(): void
    {
        $checkout = $this->storeCheckout();
        $reservationId = $checkout->reservation_id;

        $this->actingAs($this->user)
            ->delete(route('checkouts.destroy', $checkout))
            ->assertRedirect(route('checkouts.index'));

        $this->assertSame(0, Checkout::count());
        $this->assertSame('occupied', $this->room->fresh()->status->value);
        $this->assertSame('checked_in', Reservation::find($reservationId)->status->value);
    }

    public function test_checkin_create_page_loads_with_confirmed_stays(): void
    {
        $this->makeReservation();

        $this->actingAs($this->user)
            ->get(route('checkins.create'))
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->component('Checkins/Create')
                    ->has('reservations', 1),
            );
    }

    public function test_checkout_create_page_loads(): void
    {
        $this->makeReservation(['status' => 'checked_in']);

        $this->actingAs($this->user)
            ->get(route('checkouts.create'))
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page->component('Checkouts/Create'),
            );
    }

    public function test_checkout_update_persists_notes(): void
    {
        $checkout = $this->storeCheckout();

        $this->actingAs($this->user)
            ->put(route('checkouts.update', $checkout), [
                'reservation_id' => $checkout->reservation_id,
                'room_id' => $checkout->room_id,
                'guest_id' => $checkout->guest_id,
                'notes' => 'Late checkout approved',
            ])
            ->assertRedirect(route('checkouts.index'));

        $this->assertDatabaseHas('checkouts', [
            'id' => $checkout->id,
            'notes' => 'Late checkout approved',
        ]);
    }
}
