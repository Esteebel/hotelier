<?php

namespace Tests\Feature;

use App\Enums\PermissionName;
use App\Enums\RoomStatus;
use App\Models\Guest;
use App\Models\HotelBranch;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\RoomTransfer;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class RoomTransferTest extends TestCase
{
    use RefreshDatabase;

    private HotelBranch $branch;

    private User $user;

    private Guest $guest;

    private Room $fromRoom;

    private Room $toRoom;

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
            PermissionName::ManageRoomTransfers->value,
        ]);

        $this->guest = Guest::create([
            'branch_id' => $this->branch->id,
            'first_name' => 'Ada',
            'last_name' => 'Lovelace',
            'email' => 'ada@example.com',
            'status' => 'active',
        ]);

        $this->fromRoom = Room::factory()->create([
            'branch_id' => $this->branch->id,
            'price' => 50000,
            'status' => RoomStatus::Occupied,
        ]);

        $this->toRoom = Room::factory()->create([
            'branch_id' => $this->branch->id,
            'price' => 50000,
            'status' => RoomStatus::Available,
        ]);
    }

    private function makeReservation(array $overrides = []): Reservation
    {
        return Reservation::create(array_merge([
            'branch_id' => $this->branch->id,
            'guest_id' => $this->guest->id,
            'room_id' => $this->fromRoom->id,
            'reservation_number' => 'HQ-RSV-0000001',
            'check_in' => now()->subDays(2)->toDateString(),
            'check_out' => now()->addDays(3)->toDateString(),
            'adults' => 2,
            'children' => 0,
            'status' => 'checked_in',
            'total_amount' => 250000,
            'balance_due' => 250000,
        ], $overrides));
    }

    private function makeTransfer(Reservation $reservation, array $overrides = []): RoomTransfer
    {
        return RoomTransfer::create(array_merge([
            'transfer_number' => 'HQ-TR-00001',
            'branch_id' => $this->branch->id,
            'reservation_id' => $reservation->id,
            'from_room_id' => $this->fromRoom->id,
            'to_room_id' => $this->toRoom->id,
            'transfer_date' => now()->toDateString(),
            'reason' => 'Guest request',
            'status' => 'pending',
            'requested_by' => $this->user->id,
        ], $overrides));
    }

    public function test_create_page_lists_checked_in_stays(): void
    {
        $this->makeReservation();

        $this->actingAs($this->user)
            ->get(route('room-transfers.create'))
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->component('RoomTransfers/Create')
                    ->has('reservations', 1)
                    ->where('reservations.0.status', 'checked_in'),
            );
    }

    public function test_store_creates_a_pending_transfer(): void
    {
        $reservation = $this->makeReservation();

        $this->actingAs($this->user)
            ->post(route('room-transfers.store'), [
                'from_room_id' => $this->fromRoom->id,
                'to_room_id' => $this->toRoom->id,
                'reservation_id' => $reservation->id,
                'transfer_date' => now()->toDateString(),
                'reason' => 'Guest request',
                'notes' => 'Higher floor',
                'status' => 'pending',
            ])
            ->assertRedirect(route('room-transfers.index'));

        $this->assertDatabaseHas('room_transfers', [
            'reservation_id' => $reservation->id,
            'status' => 'pending',
        ]);
    }

    public function test_store_rejects_a_reservation_that_is_not_on_the_source_room(): void
    {
        $reservation = $this->makeReservation(['room_id' => $this->toRoom->id]);

        $this->actingAs($this->user)
            ->post(route('room-transfers.store'), [
                'from_room_id' => $this->fromRoom->id,
                'to_room_id' => $this->toRoom->id,
                'reservation_id' => $reservation->id,
                'transfer_date' => now()->toDateString(),
                'reason' => 'Guest request',
                'status' => 'pending',
            ])
            ->assertSessionHasErrors('reservation_id');

        $this->assertDatabaseCount('room_transfers', 0);
    }

    public function test_completing_a_transfer_moves_the_guest(): void
    {
        $reservation = $this->makeReservation();
        $transfer = $this->makeTransfer($reservation);

        $this->actingAs($this->user)
            ->post(route('room-transfers.complete', $transfer))
            ->assertRedirect(route('room-transfers.index'));

        $fresh = $reservation->fresh();
        $this->assertSame('checked_in', $fresh->status->value);
        $this->assertSame($this->toRoom->id, (int) $fresh->room_id);

        $this->assertSame('dirty', $this->fromRoom->fresh()->status->value);
        $this->assertSame('occupied', $this->toRoom->fresh()->status->value);

        $transfer->refresh();
        $this->assertSame('completed', $transfer->status);
        $this->assertSame($this->user->id, (int) $transfer->handled_by);
    }

    public function test_completion_is_blocked_when_the_destination_is_unavailable(): void
    {
        $reservation = $this->makeReservation();
        $this->toRoom->update(['status' => RoomStatus::Occupied]);
        $transfer = $this->makeTransfer($reservation);

        $this->actingAs($this->user)
            ->post(route('room-transfers.complete', $transfer))
            ->assertSessionHasErrors('to_room_id');

        $transfer->refresh();
        $this->assertSame('pending', $transfer->status);
        $this->assertSame($this->fromRoom->id, (int) $reservation->fresh()->room_id);
        $this->assertSame('occupied', $this->fromRoom->fresh()->status->value);
    }

    public function test_completion_handles_extended_stays(): void
    {
        $reservation = $this->makeReservation(['status' => 'extended']);
        $transfer = $this->makeTransfer($reservation);

        $this->actingAs($this->user)
            ->post(route('room-transfers.complete', $transfer))
            ->assertRedirect(route('room-transfers.index'));

        $this->assertSame($this->toRoom->id, (int) $reservation->fresh()->room_id);
        $this->assertSame('occupied', $this->toRoom->fresh()->status->value);
    }

    public function test_edit_page_loads_for_an_existing_transfer(): void
    {
        $reservation = $this->makeReservation();
        $transfer = $this->makeTransfer($reservation);

        $this->actingAs($this->user)
            ->get(route('room-transfers.edit', $transfer))
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->component('RoomTransfers/Edit')
                    ->has('reservations', 1),
            );
    }

    public function test_completing_twice_is_a_no_op(): void
    {
        $reservation = $this->makeReservation();
        $transfer = $this->makeTransfer($reservation);

        $this->actingAs($this->user)
            ->post(route('room-transfers.complete', $transfer))
            ->assertSessionHas('success');

        // Second completion: guest already moved to the new room; the source
        // room must not be re-dirtied and the destination stays intact.
        $this->fromRoom->update(['status' => RoomStatus::Available]);

        $this->actingAs($this->user)
            ->post(route('room-transfers.complete', $transfer->fresh()))
            ->assertSessionHas('success');

        $this->assertSame('available', $this->fromRoom->fresh()->status->value);
        $this->assertSame('occupied', $this->toRoom->fresh()->status->value);
        $this->assertSame($this->toRoom->id, (int) $reservation->fresh()->room_id);
    }

    public function test_index_lists_transfers_with_requester(): void
    {
        $reservation = $this->makeReservation();
        $this->makeTransfer($reservation);

        $this->actingAs($this->user)
            ->get(route('room-transfers.index'))
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->component('RoomTransfers/Index')
                    ->has('transfers.data', 1)
                    ->where('transfers.data.0.requester.name', $this->user->name),
            );
    }

    public function test_update_persists_changes(): void
    {
        $reservation = $this->makeReservation();
        $transfer = $this->makeTransfer($reservation);

        $this->actingAs($this->user)
            ->put(route('room-transfers.update', $transfer), [
                'from_room_id' => $this->fromRoom->id,
                'to_room_id' => $this->toRoom->id,
                'reservation_id' => $reservation->id,
                'transfer_date' => now()->toDateString(),
                'reason' => 'Suite upgrade',
                'notes' => 'VIP guest',
                'status' => 'approved',
            ])
            ->assertRedirect(route('room-transfers.index'));

        $this->assertDatabaseHas('room_transfers', [
            'id' => $transfer->id,
            'reason' => 'Suite upgrade',
            'notes' => 'VIP guest',
            'status' => 'approved',
        ]);
    }

    public function test_destroy_removes_transfer(): void
    {
        $reservation = $this->makeReservation();
        $transfer = $this->makeTransfer($reservation);

        $this->actingAs($this->user)
            ->delete(route('room-transfers.destroy', $transfer))
            ->assertRedirect(route('room-transfers.index'));

        $this->assertSame(0, RoomTransfer::count());
    }
}
