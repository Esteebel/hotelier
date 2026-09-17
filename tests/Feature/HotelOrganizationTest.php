<?php

namespace Tests\Feature;

use App\Enums\PermissionName;
use App\Models\HotelOrganization;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class HotelOrganizationTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionSeeder::class);

        $this->user = User::factory()->create();
        $this->user->givePermissionTo([
            PermissionName::ViewHotels->value,
            PermissionName::ManageHotels->value,
        ]);
    }

    public function test_organization_index_requires_authentication(): void
    {
        $this->get(route('organizations.index'))->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_view_the_organization_listing(): void
    {
        HotelOrganization::factory()->create(['name' => 'Lagos Marina Hotels']);

        $this->actingAs($this->user)
            ->get(route('organizations.index'))
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->component('Organizations/Index')
                    ->has('organizations.data', 1)
                    ->where('organizations.data.0.name', 'Lagos Marina Hotels'),
            );
    }

    public function test_user_without_permission_cannot_view_organizations(): void
    {
        $plainUser = User::factory()->create();

        $this->actingAs($plainUser)
            ->get(route('organizations.index'))
            ->assertForbidden();
    }

    public function test_organization_can_be_created(): void
    {
        $this->actingAs($this->user)
            ->post(route('organizations.store'), [
                'name' => 'Riverside Resort',
                'code' => 'RIV',
                'description' => 'A riverside escape.',
                'address' => '12 Riverbank Road',
                'city' => 'Calabar',
                'state' => 'Cross River State',
                'country' => 'Nigeria',
                'postal_code' => '540001',
                'phone' => '+234 800 000 0000',
                'email' => 'info@riverside.ng',
                'currency' => 'NGN',
                'star_rating' => 5,
                'check_in_time' => '15:00',
                'check_out_time' => '11:00',
                'status' => 'active',
            ])
            ->assertRedirect(route('organizations.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('hotel_organizations', [
            'name' => 'Riverside Resort',
            'code' => 'RIV',
            'slug' => 'riverside-resort',
        ]);
    }

    public function test_organization_name_is_required(): void
    {
        $this->actingAs($this->user)
            ->post(route('organizations.store'), [
                'code' => 'XXX',
                'address' => '1 Main Street',
                'city' => 'Lagos',
                'state' => 'Lagos State',
                'country' => 'Nigeria',
                'currency' => 'NGN',
                'star_rating' => 4,
                'check_in_time' => '14:00',
                'check_out_time' => '12:00',
                'status' => 'active',
            ])
            ->assertSessionHasErrors('name');

        $this->assertDatabaseCount('hotel_organizations', 0);
    }

    public function test_organization_code_must_be_unique(): void
    {
        HotelOrganization::factory()->create(['code' => 'IHH']);

        $this->actingAs($this->user)
            ->post(route('organizations.store'), [
                'name' => 'Another Hotel',
                'code' => 'IHH',
                'address' => '2 Main Street',
                'city' => 'Abeokuta',
                'state' => 'Ogun State',
                'country' => 'Nigeria',
                'currency' => 'NGN',
                'star_rating' => 3,
                'check_in_time' => '14:00',
                'check_out_time' => '12:00',
                'status' => 'active',
            ])
            ->assertSessionHasErrors('code');
    }

    public function test_organization_can_be_updated(): void
    {
        $organization = HotelOrganization::factory()->create();

        $this->actingAs($this->user)
            ->put(route('organizations.update', $organization), [
                'name' => 'Renamed Suites',
                'code' => $organization->code,
                'address' => $organization->address,
                'city' => $organization->city,
                'state' => $organization->state,
                'country' => $organization->country,
                'currency' => 'NGN',
                'star_rating' => 4,
                'check_in_time' => '14:00',
                'check_out_time' => '12:00',
                'status' => 'active',
            ])
            ->assertRedirect(route('organizations.index'));

        $this->assertDatabaseHas('hotel_organizations', [
            'id' => $organization->id,
            'name' => 'Renamed Suites',
            'slug' => 'renamed-suites',
        ]);
    }

    public function test_organization_can_be_soft_deleted(): void
    {
        $organization = HotelOrganization::factory()->create();

        $this->actingAs($this->user)
            ->delete(route('organizations.destroy', $organization))
            ->assertRedirect(route('organizations.index'));

        $this->assertSoftDeleted('hotel_organizations', ['id' => $organization->id]);
    }
}