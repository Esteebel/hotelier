<?php

namespace Tests\Feature;

use App\Enums\PermissionName;
use App\Models\Guest;
use App\Models\HotelBranch;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class GuestCrudTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private HotelBranch $branch;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionSeeder::class);

        $this->branch = HotelBranch::factory()->create(['is_default' => true]);

        $this->user = User::factory()->create();
        $this->user->givePermissionTo([
            PermissionName::ViewGuests->value,
            PermissionName::ManageGuests->value,
        ]);
    }

    private function makeGuest(array $overrides = []): Guest
    {
        return Guest::create(array_merge([
            'branch_id' => $this->branch->id,
            'first_name' => 'Ada',
            'last_name' => 'Lovelace',
            'email' => 'ada@example.com',
            'phone' => '08012345678',
            'status' => 'active',
        ], $overrides));
    }

    public function test_guest_index_requires_authentication(): void
    {
        $this->get(route('guests.index'))->assertRedirect(route('login'));
    }

    public function test_user_with_view_permission_can_view_guest_listing(): void
    {
        $this->makeGuest();

        $this->actingAs($this->user)
            ->get(route('guests.index'))
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->component('Guests/Index')
                    ->has('guests.data', 1)
                    ->where('guests.data.0.name', 'Ada Lovelace')
                    ->where('guests.data.0.status', 'active'),
            );
    }

    public function test_user_without_permission_cannot_view_guests(): void
    {
        $plain = User::factory()->create();

        $this->actingAs($plain)
            ->get(route('guests.index'))
            ->assertForbidden();
    }

    public function test_create_page_loads_for_authorized_user(): void
    {
        $this->actingAs($this->user)
            ->get(route('guests.create'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Guests/Create'));
    }

    public function test_guest_can_be_created(): void
    {
        $this->actingAs($this->user)
            ->post(route('guests.store'), [
                'branch_id' => $this->branch->id,
                'first_name' => 'Grace',
                'last_name' => 'Hopper',
                'email' => 'grace@example.com',
                'phone' => '08098765432',
                'company' => 'Navy',
                'status' => 'active',
            ])
            ->assertRedirect(route('guests.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('guests', [
            'branch_id' => $this->branch->id,
            'first_name' => 'Grace',
            'last_name' => 'Hopper',
            'email' => 'grace@example.com',
            'company' => 'Navy',
            'status' => 'active',
        ]);
    }

    public function test_guest_requires_first_and_last_name(): void
    {
        $this->actingAs($this->user)
            ->from(route('guests.create'))
            ->post(route('guests.store'), [
                'branch_id' => $this->branch->id,
                'status' => 'active',
            ])
            ->assertRedirect(route('guests.create'))
            ->assertSessionHasErrors(['first_name', 'last_name']);
    }

    public function test_user_without_manage_permission_cannot_create_guest(): void
    {
        $viewOnly = User::factory()->create();
        $viewOnly->givePermissionTo(PermissionName::ViewGuests->value);

        $this->actingAs($viewOnly)
            ->post(route('guests.store'), [
                'branch_id' => $this->branch->id,
                'first_name' => 'Blocked',
                'last_name' => 'Guest',
                'status' => 'active',
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('guests', ['first_name' => 'Blocked']);
    }

    public function test_guest_can_be_updated(): void
    {
        $guest = $this->makeGuest();

        $this->actingAs($this->user)
            ->put(route('guests.update', $guest), [
                'branch_id' => $this->branch->id,
                'first_name' => 'Augusta',
                'last_name' => 'Lovelace',
                'email' => 'ada@example.com',
                'status' => 'former',
            ])
            ->assertRedirect(route('guests.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('guests', [
            'id' => $guest->id,
            'first_name' => 'Augusta',
            'status' => 'former',
        ]);
    }

    public function test_guest_can_be_deleted(): void
    {
        $guest = $this->makeGuest();

        $this->actingAs($this->user)
            ->delete(route('guests.destroy', $guest))
            ->assertRedirect(route('guests.index'))
            ->assertSessionHas('success');

        $this->assertSoftDeleted('guests', ['id' => $guest->id]);
    }

    public function test_user_without_manage_permission_cannot_delete_guest(): void
    {
        $viewOnly = User::factory()->create();
        $viewOnly->givePermissionTo(PermissionName::ViewGuests->value);
        $guest = $this->makeGuest();

        $this->actingAs($viewOnly)
            ->delete(route('guests.destroy', $guest))
            ->assertForbidden();

        $this->assertDatabaseHas('guests', ['id' => $guest->id]);
    }

    public function test_guest_edit_page_loads(): void
    {
        $guest = $this->makeGuest();

        $this->actingAs($this->user)
            ->get(route('guests.edit', $guest))
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->component('Guests/Edit')
                    ->where('guest.id', $guest->id),
            );
    }
}
