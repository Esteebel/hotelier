<?php

namespace Tests\Feature;

use App\Enums\PermissionName;
use App\Enums\RoomStatus;
use App\Models\Guest;
use App\Models\HotelBranch;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ReservationCrudTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private HotelBranch $branch;

    private Guest $guest;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionSeeder::class);

        $this->branch = HotelBranch::factory()->create(['is_default' => true]);

        $this->user = User::factory()->create();
        $this->user->givePermissionTo([
            PermissionName::ViewReservations->value,
            PermissionName::CreateReservations->value,
            PermissionName::ManageReservations->value,
        ]);

        $this->guest = Guest::create([
            'branch_id' => $this->branch->id,
            'first_name' => 'Ada',
            'last_name' => 'Lovelace',
            'email' => 'ada@example.com',
            'status' => 'active',
        ]);
    }

    private function makeReservation(array $overrides = []): Reservation
    {
        return Reservation::create(array_merge([
            'branch_id' => $this->branch->id,
            'guest_id' => $this->guest->id,
            'reservation_number' => 'HQ-RSV-0000001',
            'check_in' => now()->addDay()->toDateString(),
            'check_out' => now()->addDays(3)->toDateString(),
            'adults' => 2,
            'children' => 0,
            'status' => 'confirmed',
            'total_amount' => 150000,
            'balance_due' => 150000,
        ], $overrides));
    }

    private function makeRoom(array $overrides = []): Room
    {
        return Room::factory()->create(array_merge([
            'branch_id' => $this->branch->id,
            'price' => 50000,
            'capacity' => 4,
            'status' => RoomStatus::Available,
            'is_active' => true,
        ], $overrides));
    }

    public function test_reservation_index_requires_authentication(): void
    {
        $this->get(route('reservations.index'))->assertRedirect(route('login'));
    }

    public function test_user_with_view_permission_can_view_reservation_listing(): void
    {
        $this->makeReservation();

        $this->actingAs($this->user)
            ->get(route('reservations.index'))
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->component('Reservations/Index')
                    ->has('reservations.data', 1)
                    ->where('reservations.data.0.code', 'HQ-RSV-0000001')
                    ->where('reservations.data.0.status', 'confirmed'),
            );
    }

    public function test_user_without_permission_cannot_view_reservations(): void
    {
        $plain = User::factory()->create();

        $this->actingAs($plain)
            ->get(route('reservations.index'))
            ->assertForbidden();
    }

    public function test_create_page_loads_for_authorized_user(): void
    {
        $this->actingAs($this->user)
            ->get(route('reservations.create'))
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->component('Reservations/Create')
                    ->has('guests'),
            );
    }

    public function test_reservation_can_be_created_with_generated_number(): void
    {
        $room = $this->makeRoom();

        $this->actingAs($this->user)
            ->post(route('reservations.store'), [
                'branch_id' => $this->branch->id,
                'guest_id' => $this->guest->id,
                'room_id' => $room->id,
                'check_in' => now()->addDays(2)->toDateString(),
                'check_out' => now()->addDays(5)->toDateString(),
                'adults' => 1,
                'children' => 1,
                'total_amount' => 999999,
                'balance_due' => 999999,
                'notes' => 'Late arrival',
                'status' => 'confirmed',
            ])
            ->assertRedirect(route('reservations.index'))
            ->assertSessionHas('success');

        $created = Reservation::query()->latest('id')->first();

        $this->assertNotNull($created);
        $this->assertMatchesRegularExpression('/-RSV-\d{7}$/', $created->reservation_number);
        $this->assertSame('Late arrival', $created->special_requests);
        $this->assertSame($this->user->id, $created->created_by);

        // Pricing is computed server-side (3 nights × ₦50,000) — client
        // supplied totals are ignored.
        $this->assertEquals(150000, (float) $created->total_amount);
        $this->assertEquals(150000, (float) $created->balance_due);
        $this->assertSame($room->id, (int) $created->room_id);
        $this->assertSame('reserved', $room->fresh()->status->value);
    }

    public function test_reservation_store_rejects_overlapping_booking_on_same_room(): void
    {
        $room = $this->makeRoom();

        $this->makeReservation([
            'room_id' => $room->id,
            'check_in' => now()->toDateString(),
            'check_out' => now()->addDays(4)->toDateString(),
        ]);

        $this->actingAs($this->user)
            ->post(route('reservations.store'), [
                'branch_id' => $this->branch->id,
                'guest_id' => $this->guest->id,
                'room_id' => $room->id,
                'check_in' => now()->addDays(2)->toDateString(),
                'check_out' => now()->addDays(6)->toDateString(),
                'adults' => 1,
                'children' => 0,
                'status' => 'confirmed',
            ])
            ->assertSessionHasErrors('room_id');

        $this->assertSame(1, Reservation::count());
    }

    public function test_reservation_store_allows_back_to_back_stays_on_the_same_room(): void
    {
        $room = $this->makeRoom();

        $this->makeReservation([
            'room_id' => $room->id,
            'check_in' => now()->toDateString(),
            'check_out' => now()->addDays(3)->toDateString(),
        ]);

        $this->actingAs($this->user)
            ->post(route('reservations.store'), [
                'branch_id' => $this->branch->id,
                'guest_id' => $this->guest->id,
                'room_id' => $room->id,
                'check_in' => now()->addDays(3)->toDateString(),
                'check_out' => now()->addDays(5)->toDateString(),
                'adults' => 1,
                'children' => 0,
                'status' => 'confirmed',
            ])
            ->assertRedirect(route('reservations.index'));

        $this->assertSame(2, Reservation::count());
    }

    public function test_reservation_store_rejects_room_from_another_branch(): void
    {
        $otherBranch = HotelBranch::factory()->create();
        $foreignRoom = Room::factory()->create([
            'branch_id' => $otherBranch->id,
            'price' => 50000,
        ]);

        $this->actingAs($this->user)
            ->post(route('reservations.store'), [
                'branch_id' => $this->branch->id,
                'guest_id' => $this->guest->id,
                'room_id' => $foreignRoom->id,
                'check_in' => now()->addDay()->toDateString(),
                'check_out' => now()->addDays(3)->toDateString(),
                'adults' => 1,
                'children' => 0,
                'status' => 'confirmed',
            ])
            ->assertSessionHasErrors('room_id');

        $this->assertSame(0, Reservation::count());
    }

    public function test_reservation_requires_valid_date_range(): void
    {
        $this->actingAs($this->user)
            ->from(route('reservations.create'))
            ->post(route('reservations.store'), [
                'branch_id' => $this->branch->id,
                'guest_id' => $this->guest->id,
                'check_in' => now()->addDays(5)->toDateString(),
                'check_out' => now()->addDays(2)->toDateString(),
                'adults' => 1,
                'children' => 0,
                'status' => 'confirmed',
            ])
            ->assertRedirect(route('reservations.create'))
            ->assertSessionHasErrors('check_out');
    }

    public function test_user_without_create_permission_cannot_store_reservation(): void
    {
        $viewOnly = User::factory()->create();
        $viewOnly->givePermissionTo(PermissionName::ViewReservations->value);

        $room = $this->makeRoom();

        $this->actingAs($viewOnly)
            ->post(route('reservations.store'), [
                'branch_id' => $this->branch->id,
                'room_id' => $room->id,
                'guest_id' => $this->guest->id,
                'check_in' => now()->addDay()->toDateString(),
                'check_out' => now()->addDays(3)->toDateString(),
                'adults' => 1,
                'children' => 0,
                'status' => 'confirmed',
            ])
            ->assertForbidden();

        $this->assertSame(0, Reservation::withTrashed()->count());
    }

    public function test_reservation_can_be_updated(): void
    {
        $reservation = $this->makeReservation();

        $this->actingAs($this->user)
            ->put(route('reservations.update', $reservation), [
                'branch_id' => $this->branch->id,
                'guest_id' => $this->guest->id,
                'reservation_number' => $reservation->reservation_number,
                'check_in' => $reservation->check_in->toDateString(),
                'check_out' => $reservation->check_out->toDateString(),
                'adults' => 3,
                'children' => 2,
                'status' => 'confirmed',
            ])
            ->assertRedirect(route('reservations.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('reservations', [
            'id' => $reservation->id,
            'adults' => 3,
            'children' => 2,
            'status' => 'confirmed',
        ]);
    }

    public function test_reservation_update_recomputes_totals_server_side(): void
    {
        $room = $this->makeRoom();
        $reservation = $this->makeReservation([
            'room_id' => $room->id,
            'total_amount' => 1000,
            'balance_due' => 1000,
        ]);

        $this->actingAs($this->user)
            ->put(route('reservations.update', $reservation), [
                'branch_id' => $this->branch->id,
                'guest_id' => $this->guest->id,
                'reservation_number' => $reservation->reservation_number,
                'check_in' => now()->addDay()->toDateString(),
                'check_out' => now()->addDays(5)->toDateString(),
                'adults' => 2,
                'children' => 0,
                'status' => 'confirmed',
            ])
            ->assertSessionHas('success');

        $fresh = $reservation->fresh();

        // 4 nights × ₦50,000 — stale client/model totals are overwritten.
        $this->assertEquals(200000, (float) $fresh->total_amount);
        $this->assertEquals(200000, (float) $fresh->balance_due);
        $this->assertSame('reserved', $room->fresh()->status->value);
    }

    public function test_reservation_update_rejects_overlapping_dates_on_the_same_room(): void
    {
        $room = $this->makeRoom();
        $sibling = $this->makeReservation([
            'reservation_number' => 'HQ-RSV-0000002',
            'room_id' => $room->id,
            'check_in' => now()->addDays(10)->toDateString(),
            'check_out' => now()->addDays(14)->toDateString(),
        ]);
        $reservation = $this->makeReservation([
            'room_id' => $room->id,
            'check_in' => now()->toDateString(),
            'check_out' => now()->addDays(3)->toDateString(),
        ]);

        $this->actingAs($this->user)
            ->put(route('reservations.update', $reservation), [
                'branch_id' => $this->branch->id,
                'guest_id' => $this->guest->id,
                'reservation_number' => $reservation->reservation_number,
                'check_in' => now()->addDays(9)->toDateString(),
                'check_out' => now()->addDays(13)->toDateString(),
                'adults' => 2,
                'children' => 0,
                'status' => 'confirmed',
            ])
            ->assertSessionHasErrors('room_id');

        // Dates unchanged after the rejected update.
        $this->assertEquals(
            now()->toDateString(),
            $reservation->fresh()->check_in->toDateString(),
        );
        $this->assertSame($sibling->id, (int) $sibling->fresh()->id);
    }

    public function test_deleting_a_reservation_releases_its_room(): void
    {
        $room = $this->makeRoom(['status' => RoomStatus::Reserved]);
        $reservation = $this->makeReservation(['room_id' => $room->id]);

        $this->actingAs($this->user)
            ->delete(route('reservations.destroy', $reservation))
            ->assertRedirect(route('reservations.index'))
            ->assertSessionHas('success');

        $this->assertSoftDeleted('reservations', ['id' => $reservation->id]);
        $this->assertSame('available', $room->fresh()->status->value);
    }

    public function test_cancelling_a_reservation_keeps_a_sibling_reservation_on_the_same_room(): void
    {
        $room = $this->makeRoom(['status' => RoomStatus::Reserved]);
        $sibling = $this->makeReservation([
            'reservation_number' => 'HQ-RSV-0000002',
            'room_id' => $room->id,
            'check_in' => now()->addDays(10)->toDateString(),
            'check_out' => now()->addDays(14)->toDateString(),
        ]);
        $reservation = $this->makeReservation(['room_id' => $room->id]);

        $this->actingAs($this->user)
            ->delete(route('reservations.destroy', $reservation))
            ->assertSessionHas('success');

        $this->assertSame('reserved', $room->fresh()->status->value);
        $this->assertSame('confirmed', $sibling->fresh()->status->value);
    }

    public function test_reservation_can_be_deleted(): void
    {
        $reservation = $this->makeReservation();

        $this->actingAs($this->user)
            ->delete(route('reservations.destroy', $reservation))
            ->assertRedirect(route('reservations.index'))
            ->assertSessionHas('success');

        $this->assertSoftDeleted('reservations', ['id' => $reservation->id]);
    }

    public function test_user_without_manage_permission_cannot_delete_reservation(): void
    {
        $createOnly = User::factory()->create();
        $createOnly->givePermissionTo(PermissionName::CreateReservations->value);
        $reservation = $this->makeReservation();

        $this->actingAs($createOnly)
            ->delete(route('reservations.destroy', $reservation))
            ->assertForbidden();

        $this->assertDatabaseHas('reservations', ['id' => $reservation->id]);
    }
}
