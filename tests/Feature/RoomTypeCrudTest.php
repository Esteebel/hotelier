<?php

namespace Tests\Feature;

use App\Enums\PermissionName;
use App\Models\HotelBranch;
use App\Models\RoomType;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class RoomTypeCrudTest extends TestCase
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
            PermissionName::ViewRoomTypes->value,
            PermissionName::ManageRoomTypes->value,
        ]);
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'branch_id' => $this->branch->id,
            'name' => 'Deluxe Suite',
            'description' => 'Spacious suite with a view.',
            'base_price' => 75000,
            'capacity' => 2,
            'number_of_beds' => 1,
            'bed_configuration' => 'King',
            'is_active' => true,
        ], $overrides);
    }

    private function makeRoomType(array $overrides = []): RoomType
    {
        $this->actingAs($this->user)
            ->withSession(['active_branch_id' => $this->branch->id])
            ->post(route('room-types.store'), $this->payload($overrides));

        return RoomType::firstOrFail();
    }

    public function test_index_lists_created_room_types(): void
    {
        $this->makeRoomType();

        $this->actingAs($this->user)
            ->withSession(['active_branch_id' => $this->branch->id])
            ->get(route('room-types.index'))
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->component('RoomTypes/Index')
                    ->has('roomTypes.data', 1)
                    ->where('roomTypes.data.0.name', 'Deluxe Suite'),
            );
    }

    public function test_create_page_loads_for_authorized_user(): void
    {
        $this->actingAs($this->user)
            ->get(route('room-types.create'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('RoomTypes/Create'));
    }

    public function test_store_creates_room_type(): void
    {
        $this->actingAs($this->user)
            ->withSession(['active_branch_id' => $this->branch->id])
            ->post(route('room-types.store'), $this->payload())
            ->assertRedirect(route('room-types.index'));

        $this->assertDatabaseHas('room_types', [
            'branch_id' => $this->branch->id,
            'name' => 'Deluxe Suite',
            'base_price' => 75000,
            'capacity' => 2,
        ]);
    }

    public function test_store_rejects_duplicate_name_in_same_branch(): void
    {
        $this->makeRoomType();

        $this->actingAs($this->user)
            ->withSession(['active_branch_id' => $this->branch->id])
            ->post(route('room-types.store'), $this->payload())
            ->assertSessionHasErrors('name');

        $this->assertDatabaseCount('room_types', 1);
    }

    public function test_edit_page_loads_for_existing_room_type(): void
    {
        $roomType = $this->makeRoomType();

        $this->actingAs($this->user)
            ->get(route('room-types.edit', $roomType))
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->component('RoomTypes/Edit')
                    ->where('roomType.id', $roomType->id),
            );
    }

    public function test_update_persists_changes(): void
    {
        $roomType = $this->makeRoomType();

        $this->actingAs($this->user)
            ->withSession(['active_branch_id' => $this->branch->id])
            ->put(route('room-types.update', $roomType), $this->payload([
                'name' => 'Super Deluxe Suite',
                'base_price' => 95000,
            ]))
            ->assertRedirect(route('room-types.index'));

        $this->assertDatabaseHas('room_types', [
            'id' => $roomType->id,
            'name' => 'Super Deluxe Suite',
            'base_price' => 95000,
        ]);
    }

    public function test_destroy_removes_room_type(): void
    {
        $roomType = $this->makeRoomType();

        $this->actingAs($this->user)
            ->delete(route('room-types.destroy', $roomType))
            ->assertRedirect(route('room-types.index'));

        $this->assertDatabaseCount('room_types', 0);
    }

    public function test_user_without_permission_is_denied(): void
    {
        $viewer = User::factory()->create();

        $this->actingAs($viewer)
            ->get(route('room-types.index'))
            ->assertForbidden();

        $this->actingAs($viewer)
            ->post(route('room-types.store'), $this->payload())
            ->assertForbidden();

        $this->assertDatabaseCount('room_types', 0);
    }
}
