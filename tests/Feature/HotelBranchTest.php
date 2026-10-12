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

class HotelBranchTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionSeeder::class);

        $this->user = User::factory()->create();
        $this->user->givePermissionTo([
            PermissionName::ViewBranches->value,
            PermissionName::ManageBranches->value,
        ]);
    }

    public function test_branch_index_requires_authentication(): void
    {
        $this->get(route('branches.index'))->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_view_the_branch_listing(): void
    {
        HotelBranch::factory()->create(['name' => 'Ijebu Heritage Hotel']);

        $this->actingAs($this->user)
            ->get(route('branches.index'))
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->component('Branches/Index')
                    ->has('branches.data', 1)
                    ->where('branches.data.0.name', 'Ijebu Heritage Hotel'),
            );
    }

    public function test_user_without_permission_cannot_view_branches(): void
    {
        $plainUser = User::factory()->create();

        $this->actingAs($plainUser)
            ->get(route('branches.index'))
            ->assertForbidden();
    }

    public function test_branch_can_be_created(): void
    {
        $organization = HotelOrganization::factory()->create();

        $this->actingAs($this->user)
            ->post(route('branches.store'), [
                'organization_id' => $organization->id,
                'name' => 'Victoria Island Branch',
                'code' => 'VIB',
                'description' => 'Downtown branch.',
                'address' => '3 Adetokunbo Ademola Street',
                'city' => 'Lagos',
                'state' => 'Lagos State',
                'country' => 'Nigeria',
                'currency' => 'NGN',
                'star_rating' => 4,
                'check_in_time' => '14:00',
                'check_out_time' => '12:00',
                'status' => 'active',
            ])
            ->assertRedirect(route('branches.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('hotel_branches', [
            'organization_id' => $organization->id,
            'name' => 'Victoria Island Branch',
            'code' => 'VIB',
            'slug' => 'victoria-island-branch',
        ]);
    }

    public function test_branch_belongs_to_existing_organization(): void
    {
        $this->actingAs($this->user)
            ->post(route('branches.store'), [
                'organization_id' => 9999,
                'name' => 'Orphan Branch',
                'code' => 'ORP',
                'address' => '1 Main Road',
                'city' => 'Lagos',
                'state' => 'Lagos State',
                'country' => 'Nigeria',
                'currency' => 'NGN',
                'star_rating' => 3,
                'check_in_time' => '14:00',
                'check_out_time' => '12:00',
                'status' => 'active',
            ])
            ->assertSessionHasErrors('organization_id');
    }

    public function test_branch_can_be_updated(): void
    {
        $branch = HotelBranch::factory()->create();

        $this->actingAs($this->user)
            ->put(route('branches.update', $branch), [
                'organization_id' => $branch->organization_id,
                'name' => 'Renamed Branch',
                'code' => $branch->code,
                'description' => $branch->description,
                'address' => $branch->address,
                'city' => $branch->city,
                'state' => $branch->state,
                'country' => $branch->country,
                'currency' => 'NGN',
                'star_rating' => $branch->star_rating,
                'check_in_time' => $branch->check_in_time,
                'check_out_time' => $branch->check_out_time,
                'status' => $branch->status->value,
                'is_default' => false,
            ])
            ->assertRedirect(route('branches.index'));

        $this->assertDatabaseHas('hotel_branches', [
            'id' => $branch->id,
            'name' => 'Renamed Branch',
            'slug' => 'renamed-branch',
        ]);
    }

    public function test_branch_can_be_soft_deleted(): void
    {
        $branch = HotelBranch::factory()->create();

        $this->actingAs($this->user)
            ->delete(route('branches.destroy', $branch))
            ->assertRedirect(route('branches.index'));

        $this->assertSoftDeleted('hotel_branches', ['id' => $branch->id]);
    }

    public function test_active_branch_can_be_switched_via_session(): void
    {
        $organization = HotelOrganization::factory()->create();
        $first = HotelBranch::factory()->create(['organization_id' => $organization->id, 'name' => 'Hotel Alpha']);
        $branch = HotelBranch::factory()->create(['organization_id' => $organization->id, 'name' => 'Hotel Beta']);

        $this->user->branches()->sync([$first->id, $branch->id]);

        $this->actingAs($this->user)
            ->from(route('dashboard'))
            ->post(route('branches.switch'), ['branch' => $branch->id])
            ->assertRedirect(route('dashboard'));

        $this->assertSame(
            $branch->id,
            $this->app['session']->get('active_branch_id'),
        );

        $this->get(route('dashboard'))
            ->assertInertia(
                fn (Assert $page) => $page->where('activeBranch.id', $branch->id),
            );
    }

    public function test_guest_pages_do_not_expose_branch_props(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->component('Auth/Login')
                    ->has('branches', 0)
                    ->where('activeBranch', null),
            );
    }

    public function test_branch_edit_page_loads(): void
    {
        $branch = HotelBranch::factory()->create(['name' => 'Edit Target Hotel']);

        $this->actingAs($this->user)
            ->get(route('branches.edit', $branch))
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->component('Branches/Edit')
                    ->where('branch.id', $branch->id)
                    ->has('organizations'),
            );
    }
}
