<?php

namespace Tests\Feature;

use App\Enums\PermissionName;
use App\Models\Guest;
use App\Models\HotelBranch;
use App\Models\HotelOrganization;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class OrganizationTenancyTest extends TestCase
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
        ]);

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
        $roomType = RoomType::factory()->create(['branch_id' => $branch->id]);

        return Room::factory()->create([
            'branch_id' => $branch->id,
            'room_type_id' => $roomType->id,
            'room_number' => $number,
            'capacity' => 3,
            'status' => 'available',
            'is_active' => true,
        ]);
    }

    public function test_super_admin_sees_every_organization(): void
    {
        $this->actingAs($this->superAdmin)
            ->get(route('organizations.index'))
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->component('Organizations/Index')
                    ->has('organizations.data', 2),
            );
    }

    public function test_hotel_admin_only_sees_their_own_organization(): void
    {
        $this->actingAs($this->adminA)
            ->get(route('organizations.index'))
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->component('Organizations/Index')
                    ->has('organizations.data', 1)
                    ->where('organizations.data.0.name', 'Org Alpha'),
            );
    }

    public function test_hotel_admin_cannot_view_or_update_another_organization(): void
    {
        $this->actingAs($this->adminA)
            ->get(route('organizations.edit', $this->orgB))
            ->assertNotFound();

        $this->actingAs($this->adminA)
            ->put(route('organizations.update', $this->orgB), $this->orgPayload('Org Beta Hacked'))
            ->assertNotFound();

        $this->assertDatabaseHas('hotel_organizations', [
            'id' => $this->orgB->id,
            'name' => 'Org Beta',
        ]);
    }

    public function test_hotel_admin_can_update_their_own_organization(): void
    {
        $this->actingAs($this->adminA)
            ->put(route('organizations.update', $this->orgA), $this->orgPayload('Org Alpha Renamed'))
            ->assertRedirect(route('organizations.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('hotel_organizations', [
            'id' => $this->orgA->id,
            'name' => 'Org Alpha Renamed',
        ]);
    }

    public function test_hotel_admin_cannot_create_new_organizations(): void
    {
        $this->actingAs($this->adminA)
            ->post(route('organizations.store'), $this->orgPayload('Org Gamma'))
            ->assertForbidden();

        $this->assertDatabaseMissing('hotel_organizations', ['name' => 'Org Gamma']);
    }

    public function test_hotel_admin_branch_listing_only_shows_own_organization(): void
    {
        $this->actingAs($this->adminA)
            ->get(route('branches.index'))
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->component('Branches/Index')
                    ->has('branches.data', 2)
                    ->where(
                        'branches.data',
                        fn ($rows) => collect($rows)->pluck('name')->sort()->values()->all()
                            === ['Alpha One', 'Alpha Two'],
                    )
                    ->has('organizations', 1)
                    ->where('organizations.0.name', 'Org Alpha'),
            );
    }

    public function test_hotel_admin_cannot_switch_to_another_organization_branch(): void
    {
        $this->actingAs($this->adminA)
            ->post(route('branches.switch'), ['branch' => $this->b1->id])
            ->assertNotFound();

        $this->assertNull(session('active_branch_id'));
    }

    public function test_super_admin_can_switch_to_any_branch(): void
    {
        $this->actingAs($this->superAdmin)
            ->from(route('dashboard'))
            ->post(route('branches.switch'), ['branch' => $this->b1->id])
            ->assertRedirect(route('dashboard'))
            ->assertSessionHas('success');

        $this->assertSame($this->b1->id, (int) session('active_branch_id'));
    }

    public function test_hotel_admin_cannot_create_a_branch_in_another_organization(): void
    {
        $payload = [
            'name' => 'Beta Two',
            'code' => 'BT2',
            'description' => 'Cross tenant attempt.',
            'address' => '1 Test Road',
            'city' => 'Lagos',
            'state' => 'Lagos State',
            'country' => 'Nigeria',
            'currency' => 'NGN',
            'star_rating' => 4,
            'check_in_time' => '14:00',
            'check_out_time' => '12:00',
            'status' => 'active',
        ];

        $this->actingAs($this->adminA)
            ->post(route('branches.store'), [...$payload, 'organization_id' => $this->orgB->id])
            ->assertForbidden();

        $this->assertDatabaseMissing('hotel_branches', ['code' => 'BT2']);

        $this->actingAs($this->adminA)
            ->post(route('branches.store'), [...$payload, 'organization_id' => $this->orgA->id])
            ->assertRedirect(route('branches.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('hotel_branches', ['code' => 'BT2', 'organization_id' => $this->orgA->id]);
    }

    public function test_rooms_are_scoped_to_the_callers_organization(): void
    {
        $this->makeRoom($this->a1, 'A101');
        $roomB = $this->makeRoom($this->b1, 'B101');

        $this->actingAs($this->adminA)
            ->get(route('rooms.index'))
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->component('Rooms/Index')
                    ->has('rooms.data', 1)
                    ->where('rooms.data.0.room_number', 'A101'),
            );

        // Branch filter disabled entirely — organization scope still applies.
        $this->actingAs($this->adminA)
            ->get(route('rooms.index', ['branch_id' => 0]))
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->has('rooms.data', 1)
                    ->where('rooms.data.0.room_number', 'A101'),
            );

        // Explicitly asking for another tenant's branch yields nothing.
        $this->actingAs($this->adminA)
            ->get(route('rooms.index', ['branch_id' => $roomB->branch_id]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->has('rooms.data', 0));

        // Super admin sees everything.
        $this->actingAs($this->superAdmin)
            ->get(route('rooms.index', ['branch_id' => 0]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->has('rooms.data', 2));
    }

    public function test_guests_are_scoped_to_the_callers_organization(): void
    {
        $this->makeGuest($this->a1, 'alpha@example.com');
        $this->makeGuest($this->b1, 'beta@example.com');

        $this->actingAs($this->adminA)
            ->get(route('guests.index', ['branch_id' => 0]))
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->component('Guests/Index')
                    ->has('guests.data', 1)
                    ->where('guests.data.0.email', 'alpha@example.com'),
            );

        $this->actingAs($this->superAdmin)
            ->get(route('guests.index', ['branch_id' => 0]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->has('guests.data', 2));
    }

    public function test_hotel_admin_cannot_create_guests_in_another_organization_branch(): void
    {
        $this->actingAs($this->adminA)
            ->post(route('guests.store'), [
                'branch_id' => $this->b1->id,
                'first_name' => 'Cross',
                'last_name' => 'Tenant',
                'email' => 'cross@example.com',
                'status' => 'active',
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('guests', ['email' => 'cross@example.com']);
    }

    public function test_hotel_admin_users_listing_is_limited_to_own_organization(): void
    {
        $userB = User::factory()->create([
            'user_type' => 'staff',
            'organization_id' => $this->orgB->id,
        ]);

        $this->actingAs($this->adminA)
            ->get(route('users.index'))
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->component('Users/Index')
                    ->has('users.data', 1)
                    ->where('users.data.0.email', $this->adminA->email),
            );

        $this->actingAs($this->adminB)
            ->get(route('users.index'))
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->has('users.data', 2)
                    ->where(
                        'users.data',
                        fn ($rows) => collect($rows)->pluck('email')->sort()->values()->all()
                            === collect([$userB->email, $this->adminB->email])->sort()->values()->all(),
                    ),
            );

        $this->actingAs($this->superAdmin)
            ->get(route('users.index'))
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page->has('users.data', 4),
            );
    }

    public function test_hotel_admin_cannot_update_users_of_another_organization(): void
    {
        $userB = User::factory()->create([
            'user_type' => 'staff',
            'organization_id' => $this->orgB->id,
        ]);

        $this->actingAs($this->adminA)
            ->put(route('users.update', $userB), [
                'name' => 'Hijacked',
                'email' => $userB->email,
                'status' => 'active',
                'roles' => ['receptionist'],
                'branches' => [],
            ])
            ->assertForbidden();
    }

    public function test_hotel_admin_created_users_are_owned_by_their_organization(): void
    {
        $this->actingAs($this->adminA)
            ->post(route('users.store'), [
                'name' => 'Alpha Reception',
                'email' => 'alpha.reception@example.com',
                'password' => 'password',
                'password_confirmation' => 'password',
                'status' => 'active',
                'roles' => ['receptionist', 'super-admin'],
                'branches' => [$this->a1->id],
            ])
            ->assertRedirect(route('users.index'))
            ->assertSessionHas('success');

        $created = User::query()->where('email', 'alpha.reception@example.com')->firstOrFail();

        $this->assertSame($this->orgA->id, $created->organization_id);
        $this->assertTrue($created->hasRole('receptionist'));
        $this->assertFalse($created->hasRole('super-admin'));
    }

    public function test_hotel_admin_cannot_attach_users_to_foreign_branches(): void
    {
        $this->actingAs($this->adminA)
            ->post(route('users.store'), [
                'name' => 'Cross Staff',
                'email' => 'cross.staff@example.com',
                'password' => 'password',
                'password_confirmation' => 'password',
                'status' => 'active',
                'roles' => ['receptionist'],
                'branches' => [$this->b1->id],
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('users', ['email' => 'cross.staff@example.com']);
    }

    public function test_hotel_admin_create_form_offers_only_their_own_scope(): void
    {
        $this->actingAs($this->adminA)
            ->get(route('users.create'))
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->component('Users/Create')
                    ->has('branches', 2)
                    ->has('organizations', 1)
                    ->where('organizations.0.name', 'Org Alpha')
                    ->where('canChooseOrganization', false)
                    ->where('roles', fn ($roles) => ! collect($roles)->contains('name', 'super-admin')),
            );

        $this->actingAs($this->superAdmin)
            ->get(route('users.create'))
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->has('branches', 3)
                    ->has('organizations', 2)
                    ->where('canChooseOrganization', true)
                    ->where('roles', fn ($roles) => collect($roles)->contains('name', 'super-admin')),
            );
    }

    public function test_branch_switcher_lists_only_visible_branches(): void
    {
        $this->actingAs($this->adminA)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->component('Dashboard')
                    ->has('branches', 2)
                    ->where('activeBranch.id', fn ($id) => in_array($id, [$this->a1->id, $this->a2->id])),
            );

        $this->actingAs($this->adminB)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->has('branches', 1)
                    ->where('activeBranch.id', $this->b1->id),
            );

        $this->actingAs($this->superAdmin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page->has('branches', 3),
            );
    }

    private function orgPayload(string $name): array
    {
        return [
            'name' => $name,
            'code' => 'UNQ',
            'address' => '1 Test Road',
            'city' => 'Lagos',
            'state' => 'Lagos State',
            'country' => 'Nigeria',
            'currency' => 'NGN',
            'star_rating' => 4,
            'check_in_time' => '14:00',
            'check_out_time' => '12:00',
            'status' => 'active',
        ];
    }
}
