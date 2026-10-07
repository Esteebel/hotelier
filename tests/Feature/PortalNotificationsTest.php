<?php

namespace Tests\Feature;

use App\Enums\PermissionName;
use App\Models\Guest;
use App\Models\HotelBranch;
use App\Models\Notification;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PortalNotificationsTest extends TestCase
{
    use RefreshDatabase;

    private HotelBranch $branch;

    private Guest $guest;

    private Guest $otherGuest;

    private User $customer;

    private User $otherCustomer;

    private User $staff;

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

        $this->otherCustomer = User::factory()->create([
            'user_type' => 'customer',
            'guest_id' => $this->otherGuest->id,
        ]);
        $this->otherCustomer->assignRole('customer');

        $this->staff = User::factory()->create();
        $this->staff->givePermissionTo([
            PermissionName::CreateReservations->value,
            PermissionName::ManageCheckins->value,
            PermissionName::ManageCheckouts->value,
            PermissionName::ManagePayments->value,
        ]);
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

    private function makeNotification(User $user, array $overrides = []): Notification
    {
        return Notification::create(array_merge([
            'notification_number' => 'NTF-'.uniqid(),
            'branch_id' => $this->branch->id,
            'user_id' => $user->id,
            'title' => 'Reservation confirmed',
            'message' => 'Your stay is confirmed.',
            'type' => 'success',
            'status' => 'unread',
            'notifiable_type' => $user->guest?->getMorphClass(),
            'notifiable_id' => $user->guest_id,
        ], $overrides));
    }

    public function test_portal_booking_sends_reservation_confirmation_notification(): void
    {
        $room = $this->makeRoom();

        $this->actingAs($this->customer)
            ->post(route('portal.reservations.store'), [
                'room_id' => $room->id,
                'check_in' => now()->addDays(5)->toDateString(),
                'check_out' => now()->addDays(8)->toDateString(),
                'adults' => 2,
            ])
            ->assertRedirect(route('portal.reservations'));

        $notification = Notification::query()
            ->where('user_id', $this->customer->id)
            ->first();

        $this->assertNotNull($notification);
        $this->assertSame('Reservation confirmed', $notification->title);
        $this->assertSame('unread', $notification->status);
        $this->assertSame('success', $notification->type);
        $this->assertSame($this->guest->id, $notification->notifiable_id);
        $this->assertSame($this->guest->getMorphClass(), $notification->notifiable_type);
        $this->assertStringContainsString('RSV', (string) $notification->message);
    }

    public function test_staff_creating_a_reservation_notifies_only_the_linked_customer(): void
    {
        $this->actingAs($this->staff)
            ->post(route('reservations.store'), [
                'branch_id' => $this->branch->id,
                'guest_id' => $this->guest->id,
                'check_in' => now()->addDays(2)->toDateString(),
                'check_out' => now()->addDays(5)->toDateString(),
                'adults' => 1,
                'children' => 1,
                'total_amount' => 200000,
                'status' => 'confirmed',
            ])
            ->assertRedirect(route('reservations.index'));

        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->customer->id,
            'title' => 'Reservation confirmed',
            'status' => 'unread',
        ]);

        $this->assertDatabaseMissing('notifications', [
            'user_id' => $this->otherCustomer->id,
        ]);
    }

    public function test_checkin_notifies_the_linked_customer(): void
    {
        $reservation = $this->makeReservation($this->guest);
        $room = $this->makeRoom();

        $this->actingAs($this->staff)
            ->post(route('checkins.store'), [
                'reservation_id' => $reservation->id,
                'room_id' => $room->id,
                'guest_id' => $this->guest->id,
            ])
            ->assertRedirect(route('checkins.index'));

        $notification = Notification::query()
            ->where('user_id', $this->customer->id)
            ->where('title', 'Checked in')
            ->first();

        $this->assertNotNull($notification);
        $this->assertSame('unread', $notification->status);
        $this->assertStringContainsString(
            $reservation->reservation_number,
            (string) $notification->message,
        );
    }

    public function test_checkout_notifies_the_linked_customer(): void
    {
        $reservation = $this->makeReservation($this->guest);
        $room = $this->makeRoom();

        $this->actingAs($this->staff)
            ->post(route('checkouts.store'), [
                'reservation_id' => $reservation->id,
                'room_id' => $room->id,
                'guest_id' => $this->guest->id,
            ])
            ->assertRedirect(route('checkouts.index'));

        $notification = Notification::query()
            ->where('user_id', $this->customer->id)
            ->where('title', 'Checked out')
            ->first();

        $this->assertNotNull($notification);
        $this->assertStringContainsString('Balance due', (string) $notification->message);
    }

    public function test_completed_payment_notifies_the_linked_customer_but_pending_does_not(): void
    {
        $base = [
            'branch_id' => $this->branch->id,
            'guest_id' => $this->guest->id,
            'method' => 'card',
            'amount' => 75000,
            'paid_on' => now()->toDateString(),
        ];

        $this->actingAs($this->staff)
            ->post(route('payments.store'), [...$base, 'status' => 'completed'])
            ->assertRedirect(route('payments.index'));

        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->customer->id,
            'title' => 'Payment received',
            'status' => 'unread',
        ]);

        $this->actingAs($this->staff)
            ->post(route('payments.store'), [
                ...$base,
                'payment_number' => 'HQ-PAY-0000099',
                'status' => 'pending',
            ])
            ->assertRedirect(route('payments.index'));

        $this->assertSame(
            1,
            Notification::query()
                ->where('user_id', $this->customer->id)
                ->where('title', 'Payment received')
                ->count(),
        );
    }

    public function test_portal_pages_share_only_the_customers_own_notifications(): void
    {
        $this->makeNotification($this->customer, ['status' => 'unread']);
        $this->makeNotification($this->otherCustomer, ['title' => 'Other stay']);

        $this->actingAs($this->customer)
            ->get(route('portal.home'))
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->component('Portal/Home')
                    ->has('portalNotifications', 1)
                    ->where('portalNotifications.0.title', 'Reservation confirmed')
                    ->where('portalNotifications.0.status', 'unread')
                    ->where('portalNotificationsUnread', 1),
            );
    }

    public function test_staff_pages_do_not_expose_portal_notification_props(): void
    {
        $this->actingAs($this->staff)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->where('portalNotifications', null)
                    ->where('portalNotificationsUnread', 0),
            );
    }

    public function test_customer_can_mark_a_notification_read(): void
    {
        $notification = $this->makeNotification($this->customer);

        $this->actingAs($this->customer)
            ->post(route('portal.notifications.read', $notification->id))
            ->assertRedirect();

        $this->assertDatabaseHas('notifications', [
            'id' => $notification->id,
            'status' => 'read',
        ]);
    }

    public function test_customer_can_mark_all_notifications_read(): void
    {
        $this->makeNotification($this->customer, ['title' => 'Reservation confirmed']);
        $this->makeNotification($this->customer, ['title' => 'Payment received']);

        $this->actingAs($this->customer)
            ->post(route('portal.notifications.readAll'))
            ->assertRedirect();

        $this->assertSame(
            0,
            Notification::query()
                ->where('user_id', $this->customer->id)
                ->where('status', 'unread')
                ->count(),
        );
    }

    public function test_customer_cannot_mark_another_users_notification_read(): void
    {
        $foreign = $this->makeNotification($this->otherCustomer);

        $this->actingAs($this->customer)
            ->post(route('portal.notifications.read', $foreign->id))
            ->assertNotFound();

        $this->assertDatabaseHas('notifications', [
            'id' => $foreign->id,
            'status' => 'unread',
        ]);
    }

    public function test_staff_cannot_access_portal_notification_routes(): void
    {
        $this->actingAs($this->staff)
            ->post(route('portal.notifications.readAll'))
            ->assertRedirect(route('dashboard'));
    }
}
