<?php

namespace Tests\Feature;

use App\Models\FoodOrder;
use App\Models\Guest;
use App\Models\HotelBranch;
use App\Models\Invoice;
use App\Models\MenuItem;
use App\Models\Payment;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CustomerPortalTest extends TestCase
{
    use RefreshDatabase;

    private HotelBranch $branch;

    private Guest $guest;

    private Guest $otherGuest;

    private User $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionSeeder::class);

        $this->branch = HotelBranch::factory()->create([
            'status' => 'active',
            'is_default' => true,
        ]);

        $this->guest = Guest::create([
            'branch_id' => $this->branch->id,
            'first_name' => 'Ada',
            'last_name' => 'Lovelace',
            'email' => 'ada@example.com',
            'status' => 'active',
        ]);

        $this->otherGuest = Guest::create([
            'branch_id' => $this->branch->id,
            'first_name' => 'Eve',
            'last_name' => 'Mallory',
            'email' => 'eve@example.com',
            'status' => 'active',
        ]);

        $this->customer = User::factory()->create([
            'user_type' => 'customer',
            'guest_id' => $this->guest->id,
        ]);
        $this->customer->assignRole('customer');
    }

    private function makeMenuItem(array $overrides = []): MenuItem
    {
        return MenuItem::create(array_merge([
            'branch_id' => $this->branch->id,
            'name' => 'Jollof Rice',
            'category' => 'Lunch',
            'price' => 5000,
            'is_available' => true,
            'sort_order' => 1,
        ], $overrides));
    }

    private function makeReservation(Guest $guest, array $overrides = []): Reservation
    {
        return Reservation::create(array_merge([
            'branch_id' => $this->branch->id,
            'guest_id' => $guest->id,
            'reservation_number' => 'HQ-RES-'.uniqid(),
            'check_in' => now()->addDay()->toDateString(),
            'check_out' => now()->addDays(3)->toDateString(),
            'adults' => 1,
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
            'capacity' => 4,
            'price' => 50000,
            'status' => 'available',
            'is_active' => true,
        ], $overrides));
    }

    public function test_customer_is_redirected_to_the_portal_after_login(): void
    {
        $response = $this->post('/login', [
            'email' => $this->customer->email,
            'password' => 'password',
        ]);

        $response->assertRedirect(route('portal.home', absolute: false));
    }

    public function test_staff_is_redirected_to_the_dashboard_after_login(): void
    {
        $staff = User::factory()->create();

        $response = $this->post('/login', [
            'email' => $staff->email,
            'password' => 'password',
        ]);

        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_customers_are_blocked_from_the_staff_area(): void
    {
        $this->actingAs($this->customer)
            ->get(route('dashboard'))
            ->assertRedirect(route('portal.home'));

        $this->actingAs($this->customer)
            ->get(route('guests.index'))
            ->assertRedirect(route('portal.home'));
    }

    public function test_staff_are_blocked_from_the_customer_portal(): void
    {
        $staff = User::factory()->create();

        $this->actingAs($staff)
            ->get(route('portal.home'))
            ->assertRedirect(route('dashboard'));
    }

    public function test_guests_must_log_in_to_reach_the_portal(): void
    {
        $this->get(route('portal.home'))->assertRedirect(route('login'));
    }

    public function test_customer_can_view_portal_home_with_their_own_stats(): void
    {
        $this->makeReservation($this->guest);
        $this->makeReservation($this->otherGuest);

        $this->actingAs($this->customer)
            ->get(route('portal.home'))
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->component('Portal/Home')
                    ->where('guest.first_name', 'Ada')
                    ->where('stats.total_stays', 1)
                    ->where('stats.upcoming_reservations', 1),
            );
    }

    public function test_customer_can_view_the_menu(): void
    {
        $this->makeMenuItem();
        $this->makeMenuItem(['name' => 'Unavailable', 'is_available' => false]);

        $this->actingAs($this->customer)
            ->get(route('portal.menu'))
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->component('Portal/Menu')
                    ->has('menuItems', 1)
                    ->where('menuItems.0.name', 'Jollof Rice'),
            );
    }

    public function test_customer_can_place_an_order_with_a_server_computed_total(): void
    {
        $item = $this->makeMenuItem(['price' => 5000]);

        $response = $this->actingAs($this->customer)->post(
            route('portal.menu.orders.store'),
            [
                'order_type' => 'dine_in',
                'items' => [
                    ['menu_item_id' => $item->id, 'quantity' => 3],
                ],
                'notes' => 'Extra pepper please',
            ],
        );

        $response->assertRedirect(route('portal.orders'));

        $order = FoodOrder::firstOrFail();

        $this->assertSame($this->guest->id, $order->guest_id);
        $this->assertSame($this->customer->id, $order->created_by);
        $this->assertEquals(15000, (float) $order->total_amount);
        $this->assertSame(3, $order->items[0]['quantity']);
        $this->assertSame(5000, (int) $order->items[0]['price']);
        $this->assertSame('pending', $order->status);
    }

    public function test_order_requires_at_least_one_menu_item(): void
    {
        $this->actingAs($this->customer)
            ->post(route('portal.menu.orders.store'), [
                'order_type' => 'takeout',
                'items' => [],
            ])
            ->assertSessionHasErrors('items');

        $this->assertSame(0, FoodOrder::count());
    }

    public function test_order_cannot_tamper_with_the_total_amount(): void
    {
        $item = $this->makeMenuItem(['price' => 5000]);

        $this->actingAs($this->customer)->post(route('portal.menu.orders.store'), [
            'order_type' => 'dine_in',
            'total_amount' => 1,
            'items' => [['menu_item_id' => $item->id, 'quantity' => 2]],
        ]);

        $this->assertEquals(10000, (float) FoodOrder::firstOrFail()->total_amount);
    }

    public function test_customer_only_sees_their_own_orders(): void
    {
        FoodOrder::create([
            'order_number' => 'A-FOD-0000001',
            'branch_id' => $this->branch->id,
            'guest_id' => $this->guest->id,
            'order_type' => 'dine_in',
            'items' => [['menu_item_id' => 1, 'name' => 'Jollof Rice', 'quantity' => 1, 'price' => 5000, 'subtotal' => 5000]],
            'total_amount' => 5000,
            'status' => 'pending',
            'created_by' => $this->customer->id,
        ]);

        FoodOrder::create([
            'order_number' => 'B-FOD-0000002',
            'branch_id' => $this->branch->id,
            'guest_id' => $this->otherGuest->id,
            'order_type' => 'dine_in',
            'items' => [['menu_item_id' => 1, 'name' => 'Pounded Yam', 'quantity' => 1, 'price' => 4000, 'subtotal' => 4000]],
            'total_amount' => 4000,
            'status' => 'pending',
        ]);

        $this->actingAs($this->customer)
            ->get(route('portal.orders'))
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->component('Portal/Orders')
                    ->has('orders.data', 1)
                    ->where('orders.data.0.order_number', 'A-FOD-0000001'),
            );
    }

    public function test_customer_only_sees_their_own_reservations(): void
    {
        $this->makeReservation($this->guest, ['reservation_number' => 'MINE-001']);
        $this->makeReservation($this->otherGuest, ['reservation_number' => 'THEIRS-001']);

        $this->actingAs($this->customer)
            ->get(route('portal.reservations'))
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->component('Portal/Reservations')
                    ->has('reservations.data', 1)
                    ->where('reservations.data.0.reservation_number', 'MINE-001'),
            );
    }

    public function test_customer_only_sees_their_own_bills(): void
    {
        Invoice::create([
            'branch_id' => $this->branch->id,
            'guest_id' => $this->guest->id,
            'invoice_number' => 'MINE-INV',
            'issued_on' => now()->toDateString(),
            'status' => 'sent',
            'total' => 50000,
            'amount_paid' => 0,
        ]);

        Invoice::create([
            'branch_id' => $this->branch->id,
            'guest_id' => $this->otherGuest->id,
            'invoice_number' => 'THEIRS-INV',
            'issued_on' => now()->toDateString(),
            'status' => 'sent',
            'total' => 90000,
            'amount_paid' => 0,
        ]);

        Payment::create([
            'branch_id' => $this->branch->id,
            'guest_id' => $this->guest->id,
            'payment_number' => 'MINE-PAY',
            'method' => 'card',
            'amount' => 50000,
            'paid_on' => now()->toDateString(),
        ]);

        Payment::create([
            'branch_id' => $this->branch->id,
            'guest_id' => $this->otherGuest->id,
            'payment_number' => 'THEIRS-PAY',
            'method' => 'cash',
            'amount' => 90000,
            'paid_on' => now()->toDateString(),
        ]);

        $this->actingAs($this->customer)
            ->get(route('portal.bills'))
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->component('Portal/Bills')
                    ->has('invoices.data', 1)
                    ->where('invoices.data.0.invoice_number', 'MINE-INV')
                    ->has('payments.data', 1)
                    ->where('payments.data.0.payment_number', 'MINE-PAY'),
            );
    }

    public function test_customer_without_a_guest_profile_can_still_use_the_portal(): void
    {
        $user = User::factory()->create([
            'user_type' => 'customer',
            'guest_id' => null,
        ]);
        $user->assignRole('customer');

        $this->actingAs($user)
            ->get(route('portal.home'))
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->component('Portal/Home')
                    ->where('guest', null),
            );

        $this->actingAs($user)
            ->get(route('portal.bills'))
            ->assertOk();
    }

    public function test_rooms_page_lists_rooms_free_for_the_selected_dates(): void
    {
        $this->makeRoom(['room_number' => 'A101']);
        $booked = $this->makeRoom(['room_number' => 'A102']);

        $this->makeReservation($this->otherGuest, [
            'room_id' => $booked->id,
            'check_in' => now()->toDateString(),
            'check_out' => now()->addDays(3)->toDateString(),
            'status' => 'checked_in',
        ]);

        $this->actingAs($this->customer)
            ->get(route('portal.rooms', [
                'check_in' => now()->addDay()->toDateString(),
                'check_out' => now()->addDays(2)->toDateString(),
            ]))
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->component('Portal/Rooms')
                    ->has('rooms', 1)
                    ->where('rooms.0.room_number', 'A101')
                    ->where('nights', 1)
                    ->where('filters.check_in', now()->addDay()->toDateString()),
            );
    }

    public function test_rooms_page_does_not_list_rooms_from_other_branches(): void
    {
        $this->makeRoom(['room_number' => 'IN-BRANCH']);

        $otherBranch = HotelBranch::factory()->create(['status' => 'active']);
        Room::factory()->create([
            'branch_id' => $otherBranch->id,
            'room_number' => 'OTHER-BRANCH',
        ]);

        $this->actingAs($this->customer)
            ->get(route('portal.rooms'))
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->component('Portal/Rooms')
                    ->has('rooms', 1)
                    ->where('rooms.0.room_number', 'IN-BRANCH'),
            );
    }

    public function test_guest_can_book_a_room(): void
    {
        $room = $this->makeRoom(['room_number' => 'B201', 'price' => 75000]);

        $response = $this->actingAs($this->customer)->post(
            route('portal.reservations.store'),
            [
                'room_id' => $room->id,
                'check_in' => now()->addDays(5)->toDateString(),
                'check_out' => now()->addDays(8)->toDateString(),
                'adults' => 2,
                'children' => 1,
                'special_requests' => 'High floor please',
            ],
        );

        $response->assertRedirect(route('portal.reservations'));

        $reservation = Reservation::firstOrFail();

        $this->assertSame($this->guest->id, $reservation->guest_id);
        $this->assertSame($room->id, $reservation->room_id);
        $this->assertSame($this->branch->id, $reservation->branch_id);
        $this->assertSame('confirmed', $reservation->status->value);
        $this->assertSame($this->customer->id, $reservation->created_by);
        $this->assertSame('High floor please', $reservation->special_requests);
        $this->assertEquals(225000, (float) $reservation->total_amount);
        $this->assertEquals(225000, (float) $reservation->balance_due);
        $this->assertNotEmpty($reservation->reservation_number);
        $this->assertSame('reserved', $room->fresh()->status->value);
    }

    public function test_booking_is_rejected_when_the_room_is_taken_for_those_dates(): void
    {
        $room = $this->makeRoom();

        $this->makeReservation($this->otherGuest, [
            'room_id' => $room->id,
            'check_in' => now()->toDateString(),
            'check_out' => now()->addDays(4)->toDateString(),
            'status' => 'checked_in',
        ]);

        $this->actingAs($this->customer)
            ->post(route('portal.reservations.store'), [
                'room_id' => $room->id,
                'check_in' => now()->addDay()->toDateString(),
                'check_out' => now()->addDays(2)->toDateString(),
                'adults' => 1,
            ])
            ->assertSessionHasErrors('room_id');

        $this->assertSame(1, Reservation::count());
    }

    public function test_booking_is_rejected_when_the_party_is_too_large(): void
    {
        $room = $this->makeRoom(['capacity' => 2]);

        $this->actingAs($this->customer)
            ->post(route('portal.reservations.store'), [
                'room_id' => $room->id,
                'check_in' => now()->addDay()->toDateString(),
                'check_out' => now()->addDays(2)->toDateString(),
                'adults' => 4,
            ])
            ->assertSessionHasErrors('room_id');

        $this->assertSame(0, Reservation::count());
    }

    public function test_booking_requires_a_linked_guest_profile(): void
    {
        $room = $this->makeRoom();

        $user = User::factory()->create([
            'user_type' => 'customer',
            'guest_id' => null,
        ]);
        $user->assignRole('customer');

        $this->actingAs($user)
            ->post(route('portal.reservations.store'), [
                'room_id' => $room->id,
                'check_in' => now()->addDay()->toDateString(),
                'check_out' => now()->addDays(2)->toDateString(),
                'adults' => 1,
            ])
            ->assertSessionHasErrors('room_id');

        $this->assertSame(0, Reservation::count());
    }

    public function test_rooms_page_is_blocked_for_staff_accounts(): void
    {
        $staff = User::factory()->create();

        $this->actingAs($staff)
            ->get(route('portal.rooms'))
            ->assertRedirect(route('dashboard'));
    }

    public function test_customer_can_cancel_their_own_confirmed_reservation(): void
    {
        $room = $this->makeRoom(['status' => 'reserved']);
        $reservation = $this->makeReservation($this->guest, [
            'room_id' => $room->id,
        ]);

        $this->actingAs($this->customer)
            ->post(route('portal.reservations.cancel', $reservation->id))
            ->assertRedirect(route('portal.reservations'));

        $this->assertSame('cancelled', $reservation->fresh()->status->value);
        $this->assertSame('available', $room->fresh()->status->value);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->customer->id,
            'title' => 'Reservation cancelled',
        ]);
    }

    public function test_customer_cannot_cancel_another_guests_reservation(): void
    {
        $reservation = $this->makeReservation($this->otherGuest);

        $this->actingAs($this->customer)
            ->post(route('portal.reservations.cancel', $reservation->id))
            ->assertNotFound();

        $this->assertSame('confirmed', $reservation->fresh()->status->value);
    }

    public function test_checked_in_reservation_cannot_be_cancelled_online(): void
    {
        $reservation = $this->makeReservation($this->guest, [
            'status' => 'checked_in',
        ]);

        $this->actingAs($this->customer)
            ->post(route('portal.reservations.cancel', $reservation->id))
            ->assertSessionHasErrors('reservation');

        $this->assertSame('checked_in', $reservation->fresh()->status->value);
    }

    public function test_customer_without_a_guest_profile_cannot_cancel_reservations(): void
    {
        $reservation = $this->makeReservation($this->guest);
        $user = User::factory()->create(['user_type' => 'customer', 'guest_id' => null]);
        $user->assignRole('customer');

        $this->actingAs($user)
            ->post(route('portal.reservations.cancel', $reservation->id))
            ->assertNotFound();

        $this->assertSame('confirmed', $reservation->fresh()->status->value);
    }

    public function test_staff_cannot_access_portal_cancel_route(): void
    {
        $staff = User::factory()->create();
        $reservation = $this->makeReservation($this->guest);

        $this->actingAs($staff)
            ->post(route('portal.reservations.cancel', $reservation->id))
            ->assertRedirect(route('dashboard'));

        $this->assertSame('confirmed', $reservation->fresh()->status->value);
    }

    public function test_order_cannot_target_a_room_the_guest_has_not_booked(): void
    {
        $item = $this->makeMenuItem();
        $room = $this->makeRoom();

        $this->actingAs($this->customer)->post(route('portal.menu.orders.store'), [
            'order_type' => 'dine_in',
            'room_id' => $room->id,
            'items' => [['menu_item_id' => $item->id, 'quantity' => 1]],
        ])->assertSessionHasErrors('room_id');

        $this->assertSame(0, FoodOrder::count());

        $this->makeReservation($this->guest, ['room_id' => $room->id]);

        $this->actingAs($this->customer)->post(route('portal.menu.orders.store'), [
            'order_type' => 'dine_in',
            'room_id' => $room->id,
            'items' => [['menu_item_id' => $item->id, 'quantity' => 1]],
        ])->assertRedirect(route('portal.orders'));

        $this->assertSame(1, FoodOrder::count());
        $this->assertSame($room->id, FoodOrder::firstOrFail()->room_id);
    }

    public function test_customer_profile_page_loads(): void
    {
        $this->actingAs($this->customer)
            ->get(route('portal.profile'))
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->component('Portal/Profile')
                    ->where('profile.email', $this->customer->email),
            );
    }
}
