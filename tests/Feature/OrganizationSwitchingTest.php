<?php

namespace Tests\Feature;

use App\Models\Guest;
use App\Models\HotelBranch;
use App\Models\HotelOrganization;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class OrganizationSwitchingTest extends TestCase
{
    use RefreshDatabase;

    private HotelOrganization $orgA;

    private HotelOrganization $orgB;

    private HotelBranch $a1;

    private HotelBranch $a2;

    private HotelBranch $b1;

    private User $superAdmin;

    private User $adminA;

    private User $adminB;

    private User $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionSeeder::class);

        $this->orgA = HotelOrganization::factory()->create(['name' => 'Org Alpha', 'code' => 'ALP']);
        $this->orgB = HotelOrganization::factory()->create(['name' => 'Org Beta', 'code' => 'BET']);

        $this->a1 = HotelBranch::factory()->create([
            'organization_id' => $this->orgA->id,
            'name' => 'Alpha One',
            'status' => 'active',
            'is_default' => true,
        ]);
        $this->a2 = HotelBranch::factory()->create([
            'organization_id' => $this->orgA->id,
            'name' => 'Alpha Two',
            'status' => 'active',
        ]);
        $this->b1 = HotelBranch::factory()->create([
            'organization_id' => $this->orgB->id,
            'name' => 'Beta One',
            'status' => 'active',
            'is_default' => true,
        ]);

        $roomA1 = $this->makeRoom($this->a1, 'A-101');
        $this->makeRoom($this->a2, 'A-201');
        $roomB1 = $this->makeRoom($this->b1, 'B-101');

        $guestA = $this->makeGuest($this->a1, 'alpha-guest@example.com');
        $guestB = $this->makeGuest($this->b1, 'beta-guest@example.com');

        $this->makeReservation($this->a1, $roomA1, $guestA, 'ALP-RSV-0000001');
        $this->makeReservation($this->b1, $roomB1, $guestB, 'BET-RSV-0000001');

        $this->superAdmin = User::factory()->create();
        $this->superAdmin->assignRole('super-admin');

        $this->adminA = User::factory()->create([
            'user_type' => 'staff',
            'organization_id' => $this->orgA->id,
        ]);
        $this->adminA->assignRole('hotel-admin');
        $this->adminA->branches()->sync([$this->a1->id]);

        $this->adminB = User::factory()->create([
            'user_type' => 'staff',
            'organization_id' => $this->orgB->id,
        ]);
        $this->adminB->assignRole('hotel-admin');
        $this->adminB->branches()->sync([$this->b1->id]);

        $this->customer = User::factory()->create(['user_type' => 'customer']);
        $this->customer->assignRole('customer');
    }

    private function makeGuest(HotelBranch $branch, string $email): Guest
    {
        return Guest::create([
            'branch_id' => $branch->id,
            'first_name' => 'Test',
            'last_name' => 'Guest',
            'email' => $email,
            'status' => 'active',
        ]);
    }

    private function makeRoom(HotelBranch $branch, string $number): Room
    {
        $roomType = RoomType::factory()->create([
            'branch_id' => $branch->id,
            'name' => 'Type '.$number,
        ]);

        return Room::factory()->create([
            'branch_id' => $branch->id,
            'room_type_id' => $roomType->id,
            'room_number' => $number,
            'capacity' => 3,
        ]);
    }

    private function makeReservation(
        HotelBranch $branch,
        Room $room,
        Guest $guest,
        string $number,
    ): Reservation {
        return Reservation::create([
            'branch_id' => $branch->id,
            'guest_id' => $guest->id,
            'room_id' => $room->id,
            'reservation_number' => $number,
            'check_in' => now()->addDay()->toDateString(),
            'check_out' => now()->addDays(2)->toDateString(),
            'adults' => 1,
            'children' => 0,
            'rate_plan' => 'standard',
            'total_amount' => 75000,
            'status' => 'confirmed',
        ]);
    }

    public function test_super_admin_defaults_to_all_organizations(): void
    {
        $this->actingAs($this->superAdmin)
            ->get(route('rooms.index', ['branch_id' => 0]))
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->has('rooms.data', 3)
                    ->where('activeOrganizationId', null)
                    ->where('canFilterOrganizations', true)
                    ->has('navOrganizations', 2),
            );
    }

    public function test_super_admin_can_narrow_the_panel_to_one_organization(): void
    {
        $this->actingAs($this->superAdmin)
            ->from(route('rooms.index'))
            ->post(route('organizations.switch'), ['organization' => $this->orgB->id])
            ->assertRedirect(route('rooms.index'))
            ->assertSessionHas('active_organization_id', $this->orgB->id)
            ->assertSessionHas('success');

        $this->actingAs($this->superAdmin)
            ->get(route('rooms.index', ['branch_id' => 0]))
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->has('rooms.data', 1)
                    ->where('activeOrganizationId', $this->orgB->id)
                    ->has('navOrganizations', 2),
            );

        $this->actingAs($this->superAdmin)
            ->get(route('organizations.index'))
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->has('organizations.data', 1)
                    ->where('organizations.data.0.name', 'Org Beta'),
            );

        $this->actingAs($this->superAdmin)
            ->get(route('users.index'))
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->has('users.data', 1)
                    ->where('users.data.0.email', $this->adminB->email),
            );

        $this->actingAs($this->superAdmin)
            ->get(route('guests.index', ['branch_id' => 0]))
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page->has('guests.data', 1),
            );

        $this->actingAs($this->superAdmin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->has('branches', 1)
                    ->where('branches.0.name', 'Beta One')
                    ->where('activeBranch.name', 'Beta One'),
            );
    }

    public function test_switching_back_to_all_restores_everything(): void
    {
        $this->actingAs($this->superAdmin)
            ->post(route('organizations.switch'), ['organization' => $this->orgB->id])
            ->assertSessionHas('active_organization_id', $this->orgB->id);

        $this->actingAs($this->superAdmin)
            ->post(route('organizations.switch'), ['organization' => null])
            ->assertSessionMissing('active_organization_id');

        $this->actingAs($this->superAdmin)
            ->get(route('rooms.index', ['branch_id' => 0]))
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->has('rooms.data', 3)
                    ->where('activeOrganizationId', null),
            );
    }

    public function test_switching_organizations_resets_the_active_branch(): void
    {
        $this->actingAs($this->superAdmin)
            ->post(route('branches.switch'), ['branch' => $this->b1->id])
            ->assertSessionHas('active_branch_id', $this->b1->id);

        $this->actingAs($this->superAdmin)
            ->post(route('organizations.switch'), ['organization' => $this->orgA->id])
            ->assertSessionMissing('active_branch_id');
    }

    public function test_hotel_admin_cannot_use_the_organization_switcher(): void
    {
        $this->actingAs($this->adminA)
            ->post(route('organizations.switch'), ['organization' => $this->orgB->id])
            ->assertForbidden();

        $this->assertNull(session('active_organization_id'));
    }

    public function test_customer_cannot_use_the_organization_switcher(): void
    {
        $this->actingAs($this->customer)
            ->post(route('organizations.switch'), ['organization' => $this->orgA->id])
            ->assertRedirect();

        $this->assertNull(session('active_organization_id'));
    }

    public function test_hotel_admin_stays_pinned_to_their_own_organization_even_with_a_tampered_session(): void
    {
        $this->actingAs($this->adminA)
            ->withSession(['active_organization_id' => $this->orgB->id])
            ->get(route('rooms.index', ['branch_id' => 0]))
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->has('rooms.data', 2)
                    ->where('activeOrganizationId', $this->orgA->id)
                    ->where('canFilterOrganizations', false)
                    ->has('navOrganizations', 1)
                    ->where('navOrganizations.0.name', 'Org Alpha'),
            );
    }

    public function test_organization_filter_applies_to_the_branch_switcher_only(): void
    {
        $this->actingAs($this->superAdmin)
            ->post(route('organizations.switch'), ['organization' => $this->orgB->id]);

        $this->actingAs($this->superAdmin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->has('branches', 1)
                    ->where('branches.0.name', 'Beta One'),
            );
    }

    public function test_dashboard_analytics_follow_the_organization_selection(): void
    {
        // Default = All organizations: everything, everywhere.
        $this->actingAs($this->superAdmin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->where('stats.total_rooms', 3)
                    ->has('room_type_breakdown', 3)
                    ->has('recent_reservations', 2)
                    ->where('scopeLabel', 'All organizations')
                    ->where('activeOrganizationId', null),
            );

        // Org Beta: only its room, breakdown and reservation.
        $this->actingAs($this->superAdmin)
            ->post(route('organizations.switch'), ['organization' => $this->orgB->id]);

        $this->actingAs($this->superAdmin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->where('stats.total_rooms', 1)
                    ->has('room_type_breakdown', 1)
                    ->has('recent_reservations', 1)
                    ->where('recent_reservations.0.code', 'BET-RSV-0000001')
                    ->where('scopeLabel', 'Org Beta'),
            );

        // Org Alpha: its two rooms and reservation only.
        $this->actingAs($this->superAdmin)
            ->post(route('organizations.switch'), ['organization' => $this->orgA->id]);

        $this->actingAs($this->superAdmin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->where('stats.total_rooms', 2)
                    ->has('room_type_breakdown', 2)
                    ->has('recent_reservations', 1)
                    ->where('recent_reservations.0.code', 'ALP-RSV-0000001')
                    ->where('scopeLabel', 'Org Alpha'),
            );

        // Back to All: aggregate restored.
        $this->actingAs($this->superAdmin)
            ->post(route('organizations.switch'), ['organization' => null]);

        $this->actingAs($this->superAdmin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->where('stats.total_rooms', 3)
                    ->has('recent_reservations', 2)
                    ->where('scopeLabel', 'All organizations'),
            );
    }

    public function test_explicit_branch_selection_keeps_the_dashboard_branch_scoped(): void
    {
        $this->actingAs($this->superAdmin)
            ->withSession(['active_branch_id' => $this->a1->id])
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->where('stats.total_rooms', 1)
                    ->where('scopeLabel', 'Alpha One'),
            );
    }

    public function test_hotel_admin_dashboard_aggregates_their_own_organization(): void
    {
        $this->actingAs($this->adminA)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->where('stats.total_rooms', 2)
                    ->where('scopeLabel', 'Org Alpha'),
            );
    }

    public function test_room_type_breakdown_merges_same_named_types_across_branches(): void
    {
        $typeA = RoomType::factory()->create(['branch_id' => $this->a1->id, 'name' => 'Standard Double']);
        $typeB = RoomType::factory()->create(['branch_id' => $this->b1->id, 'name' => 'Standard Double']);

        Room::factory()->create([
            'branch_id' => $this->a1->id,
            'room_type_id' => $typeA->id,
            'room_number' => 'A-901',
            'capacity' => 2,
        ]);
        Room::factory()->create([
            'branch_id' => $this->b1->id,
            'room_type_id' => $typeB->id,
            'room_number' => 'B-901',
            'capacity' => 2,
        ]);

        $this->actingAs($this->superAdmin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->has('room_type_breakdown', 4)
                    ->where(
                        'room_type_breakdown',
                        fn ($rows) => collect($rows)->firstWhere('type', 'Standard Double') === [
                            'type' => 'Standard Double',
                            'total' => 2,
                            'occupied' => 0,
                        ],
                    ),
            );
    }
}
