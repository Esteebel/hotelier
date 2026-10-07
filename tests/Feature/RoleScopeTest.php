<?php

namespace Tests\Feature;

use App\Enums\PermissionName;
use App\Models\HotelBranch;
use App\Models\HotelOrganization;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class RoleScopeTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;

    private User $hotelAdmin;

    private User $hotelSuperAdmin;

    private User $branchManager;

    private User $receptionist;

    private HotelOrganization $organization;

    private HotelBranch $branch;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionSeeder::class);

        $this->organization = HotelOrganization::factory()->create();
        $this->branch = HotelBranch::factory()->create([
            'organization_id' => $this->organization->id,
        ]);

        $this->superAdmin = User::factory()->create();
        $this->superAdmin->assignRole('super-admin');

        $this->hotelAdmin = User::factory()->create([
            'organization_id' => $this->organization->id,
        ]);
        $this->hotelAdmin->assignRole('hotel-admin');

        $this->hotelSuperAdmin = User::factory()->create([
            'organization_id' => $this->organization->id,
        ]);
        $this->hotelSuperAdmin->assignRole('hotel-super-admin');

        $this->branchManager = User::factory()->create();
        $this->branchManager->assignRole('manager');

        $this->receptionist = User::factory()->create();
        $this->receptionist->assignRole('receptionist');
    }

    private function orgPayload(string $name = 'Scope Org'): array
    {
        return [
            'name' => $name,
            'code' => 'SC01',
            'address' => '1 Scope Road',
            'city' => 'Lagos',
            'state' => 'Lagos',
            'country' => 'Nigeria',
            'currency' => 'NGN',
            'star_rating' => 3,
            'check_in_time' => '14:00',
            'check_out_time' => '11:00',
            'status' => 'active',
        ];
    }

    private function branchPayload(): array
    {
        return [
            'organization_id' => $this->organization->id,
            'name' => 'Scope Branch',
            'code' => 'SC-BR1',
            'address' => '1 Scope Road',
            'city' => 'Lagos',
            'state' => 'Lagos',
            'country' => 'Nigeria',
            'currency' => 'NGN',
            'star_rating' => 3,
            'check_in_time' => '14:00',
            'check_out_time' => '11:00',
            'status' => 'active',
        ];
    }

    public function test_only_super_admin_can_open_organization_create_page(): void
    {
        $this->actingAs($this->superAdmin)
            ->get(route('organizations.create'))
            ->assertOk();

        $this->actingAs($this->hotelAdmin)
            ->get(route('organizations.create'))
            ->assertForbidden();

        $this->actingAs($this->hotelSuperAdmin)
            ->get(route('organizations.create'))
            ->assertForbidden();

        $this->actingAs($this->branchManager)
            ->get(route('organizations.create'))
            ->assertForbidden();

        $this->actingAs($this->receptionist)
            ->get(route('organizations.create'))
            ->assertForbidden();
    }

    public function test_only_super_admin_can_create_organizations(): void
    {
        $this->actingAs($this->branchManager)
            ->post(route('organizations.store'), $this->orgPayload('Manager Org'))
            ->assertForbidden();

        $this->actingAs($this->hotelAdmin)
            ->post(route('organizations.store'), $this->orgPayload('Admin Org'))
            ->assertForbidden();

        $this->assertDatabaseMissing('hotel_organizations', ['name' => 'Manager Org']);
        $this->assertDatabaseMissing('hotel_organizations', ['name' => 'Admin Org']);

        $this->actingAs($this->superAdmin)
            ->post(route('organizations.store'), $this->orgPayload())
            ->assertRedirect(route('organizations.index'));

        $this->assertDatabaseHas('hotel_organizations', ['name' => 'Scope Org']);
    }

    public function test_hotel_admin_without_organization_cannot_create_organizations(): void
    {
        $homelessAdmin = User::factory()->create(['organization_id' => null]);
        $homelessAdmin->assignRole('hotel-admin');

        $this->actingAs($homelessAdmin)
            ->post(route('organizations.store'), $this->orgPayload('Sneaky Org'))
            ->assertForbidden();

        $this->assertDatabaseMissing('hotel_organizations', ['name' => 'Sneaky Org']);
    }

    public function test_only_super_admin_can_delete_organizations(): void
    {
        $target = HotelOrganization::factory()->create();

        // Org-scoped admins cannot even resolve other organizations (404).
        $this->actingAs($this->hotelAdmin)
            ->delete(route('organizations.destroy', $target))
            ->assertNotFound();

        $this->actingAs($this->branchManager)
            ->delete(route('organizations.destroy', $target))
            ->assertForbidden();

        $this->assertDatabaseHas('hotel_organizations', ['id' => $target->id]);

        $this->actingAs($this->superAdmin)
            ->delete(route('organizations.destroy', $target))
            ->assertRedirect(route('organizations.index'));

        $this->assertSoftDeleted('hotel_organizations', ['id' => $target->id]);
    }

    public function test_branch_manager_cannot_create_branches(): void
    {
        $this->actingAs($this->branchManager)
            ->post(route('branches.store'), $this->branchPayload())
            ->assertForbidden();

        $this->assertDatabaseMissing('hotel_branches', ['name' => 'Scope Branch']);
    }

    public function test_receptionist_cannot_create_branches(): void
    {
        $this->actingAs($this->receptionist)
            ->post(route('branches.store'), $this->branchPayload())
            ->assertForbidden();

        $this->assertDatabaseMissing('hotel_branches', ['name' => 'Scope Branch']);
    }

    public function test_hotel_admin_can_create_branch_in_own_organization(): void
    {
        $this->actingAs($this->hotelAdmin)
            ->post(route('branches.store'), $this->branchPayload())
            ->assertRedirect(route('branches.index'));

        $this->assertDatabaseHas('hotel_branches', [
            'name' => 'Scope Branch',
            'organization_id' => $this->organization->id,
        ]);
    }

    public function test_branch_manager_cannot_access_system_pages(): void
    {
        $this->actingAs($this->branchManager)
            ->get(route('users.index'))
            ->assertForbidden();

        $this->actingAs($this->branchManager)
            ->get(route('roles.index'))
            ->assertForbidden();

        $this->actingAs($this->branchManager)
            ->get(route('settings.index'))
            ->assertForbidden();

        $this->actingAs($this->branchManager)
            ->get(route('organizations.index'))
            ->assertForbidden();

        $this->actingAs($this->branchManager)
            ->get(route('audit-logs.index'))
            ->assertForbidden();
    }

    public function test_branch_manager_can_access_operational_pages(): void
    {
        $this->actingAs($this->branchManager)
            ->get(route('dashboard'))
            ->assertOk();

        $this->actingAs($this->branchManager)
            ->get(route('branches.index'))
            ->assertOk();

        $this->actingAs($this->branchManager)
            ->get(route('rooms.index'))
            ->assertOk();

        $this->actingAs($this->branchManager)
            ->get(route('reservations.index'))
            ->assertOk();

        $this->actingAs($this->branchManager)
            ->get(route('guests.index'))
            ->assertOk();
    }

    public function test_receptionist_cannot_manage_users_or_roles(): void
    {
        $this->actingAs($this->receptionist)
            ->get(route('users.index'))
            ->assertForbidden();

        $this->actingAs($this->receptionist)
            ->get(route('roles.index'))
            ->assertForbidden();
    }

    public function test_hotel_admin_can_manage_users_but_not_roles(): void
    {
        $this->actingAs($this->hotelAdmin)
            ->get(route('users.index'))
            ->assertOk();

        $this->actingAs($this->hotelAdmin)
            ->get(route('roles.index'))
            ->assertForbidden();
    }

    public function test_roles_page_requires_manage_roles_permission(): void
    {
        $viewer = User::factory()->create();
        $viewer->givePermissionTo(PermissionName::ViewUsers->value);

        $this->actingAs($viewer)
            ->get(route('roles.index'))
            ->assertForbidden();
    }

    public function test_role_scope_is_reflected_in_shared_permissions(): void
    {
        $this->actingAs($this->branchManager)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->has('auth.permissions')
                    ->where(
                        'auth.permissions',
                        function ($permissions) {
                            $permissions = collect($permissions);

                            return $permissions->contains('view-branches')
                                && ! $permissions->contains('manage-branches')
                                && ! $permissions->contains('manage-hotels')
                                && ! $permissions->contains('manage-users')
                                && ! $permissions->contains('manage-roles')
                                && ! $permissions->contains('manage-settings');
                        },
                    ),
            );
    }

    public function test_hotel_super_admin_can_create_branch_in_own_organization(): void
    {
        $this->actingAs($this->hotelSuperAdmin)
            ->get(route('branches.create'))
            ->assertOk();

        $this->actingAs($this->hotelSuperAdmin)
            ->post(route('branches.store'), $this->branchPayload())
            ->assertRedirect(route('branches.index'));

        $this->assertDatabaseHas('hotel_branches', [
            'name' => 'Scope Branch',
            'organization_id' => $this->organization->id,
        ]);
    }

    public function test_hotel_super_admin_cannot_access_organization_module(): void
    {
        $this->actingAs($this->hotelSuperAdmin)
            ->get(route('organizations.index'))
            ->assertForbidden();

        $this->actingAs($this->hotelSuperAdmin)
            ->get(route('organizations.create'))
            ->assertForbidden();

        $this->actingAs($this->hotelSuperAdmin)
            ->post(route('organizations.store'), $this->orgPayload('Blocked Org'))
            ->assertForbidden();

        $this->assertDatabaseMissing('hotel_organizations', ['name' => 'Blocked Org']);

        // Own organization is in scope (resolvable), but policy still denies.
        $this->actingAs($this->hotelSuperAdmin)
            ->put(route('organizations.update', $this->organization), $this->orgPayload('Renamed'))
            ->assertForbidden();

        $this->actingAs($this->hotelSuperAdmin)
            ->delete(route('organizations.destroy', $this->organization))
            ->assertForbidden();

        $this->assertDatabaseHas('hotel_organizations', [
            'id' => $this->organization->id,
            'deleted_at' => null,
        ]);
    }

    public function test_hotel_super_admin_oversees_all_branches_of_own_hotel_only(): void
    {
        HotelBranch::factory()->create([
            'organization_id' => $this->organization->id,
            'name' => 'Own Branch Two',
            'code' => 'OWN-B2',
        ]);

        $foreignOrg = HotelOrganization::factory()->create();
        HotelBranch::factory()->create([
            'organization_id' => $foreignOrg->id,
            'name' => 'Foreign Branch',
            'code' => 'FOR-B1',
        ]);

        $this->actingAs($this->hotelSuperAdmin)
            ->get(route('branches.index'))
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->component('Branches/Index')
                    ->has('branches.data', 2)
                    ->where(
                        'branches.data',
                        fn ($rows) => ! collect($rows)->pluck('name')->contains('Foreign Branch'),
                    )
                    ->has('organizations', 1)
                    ->where('organizations.0.name', $this->organization->name),
            );
    }

    public function test_hotel_super_admin_can_manage_users_but_not_roles(): void
    {
        $this->actingAs($this->hotelSuperAdmin)
            ->get(route('users.index'))
            ->assertOk();

        $this->actingAs($this->hotelSuperAdmin)
            ->get(route('roles.index'))
            ->assertForbidden();
    }

    public function test_hotel_super_admin_role_requires_an_organization_on_user_creation(): void
    {
        $payload = [
            'name' => 'Hotel Super',
            'email' => 'hotel.super@heritage.ng',
            'status' => 'active',
            'roles' => ['hotel-super-admin'],
        ];

        $this->actingAs($this->superAdmin)
            ->post(route('users.store'), $payload)
            ->assertSessionHasErrors('organization_id');

        $this->assertDatabaseMissing('users', ['email' => 'hotel.super@heritage.ng']);

        $this->actingAs($this->superAdmin)
            ->post(route('users.store'), $payload + ['organization_id' => $this->organization->id])
            ->assertRedirect(route('users.index'));

        $user = User::where('email', 'hotel.super@heritage.ng')->firstOrFail();

        $this->assertSame($this->organization->id, $user->organization_id);
        $this->assertTrue($user->hasRole('hotel-super-admin'));
    }
}
