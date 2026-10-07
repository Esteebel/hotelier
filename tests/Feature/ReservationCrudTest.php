<?php

namespace Tests\Feature;

use App\Enums\PermissionName;
use App\Models\Guest;
use App\Models\HotelBranch;
use App\Models\Reservation;
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

        $this->branch = HotelBranch::factory()->create();

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
        $this->actingAs($this->user)
            ->post(route('reservations.store'), [
                'branch_id' => $this->branch->id,
                'guest_id' => $this->guest->id,
                'check_in' => now()->addDays(2)->toDateString(),
                'check_out' => now()->addDays(5)->toDateString(),
                'adults' => 1,
                'children' => 1,
                'total_amount' => 200000,
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
        $this->assertEquals(200000, (float) $created->balance_due);
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

        $this->actingAs($viewOnly)
            ->post(route('reservations.store'), [
                'branch_id' => $this->branch->id,
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
                'total_amount' => 250000,
                'balance_due' => 100000,
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
