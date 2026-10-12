<?php

namespace Tests\Feature;

use App\Enums\HotelStatus;
use App\Enums\PermissionName;
use App\Enums\RoomStatus;
use App\Models\HotelBranch;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class RoomCrudTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private HotelBranch $branch;

    private RoomType $roomType;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionSeeder::class);

        $this->branch = HotelBranch::factory()->create(['is_default' => true]);
        $this->roomType = RoomType::factory()->create([
            'branch_id' => $this->branch->id,
            'name' => 'Deluxe King',
        ]);

        $this->user = User::factory()->create();
        $this->user->givePermissionTo([
            PermissionName::ViewRooms->value,
            PermissionName::ManageRooms->value,
        ]);
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'room_type_id' => $this->roomType->id,
            'room_number' => 'A-101',
            'floor' => '1',
            'capacity' => 2,
            'price' => 50000,
            'status' => RoomStatus::Available->value,
            'description' => 'Test room',
            'is_active' => true,
        ], $overrides);
    }

    public function test_room_create_page_loads_for_authorized_user(): void
    {
        $this->actingAs($this->user)
            ->get(route('rooms.create'))
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->component('Rooms/Create')
                    ->has('roomTypes')
                    ->where('activeBranch.id', $this->branch->id),
            );
    }

    public function test_room_is_created_with_branch_from_session_context(): void
    {
        $this->actingAs($this->user)
            ->withSession(['active_branch_id' => $this->branch->id])
            ->post(route('rooms.store'), $this->payload())
            ->assertRedirect(route('rooms.index'));

        $this->assertDatabaseHas('rooms', [
            'branch_id' => $this->branch->id,
            'room_type_id' => $this->roomType->id,
            'room_number' => 'A-101',
            'capacity' => 2,
            'status' => RoomStatus::Available->value,
        ]);
    }

    public function test_room_create_without_active_branch_returns_validation_error(): void
    {
        $this->branch->update(['status' => HotelStatus::Inactive]);

        $this->actingAs($this->user)
            ->post(route('rooms.store'), $this->payload())
            ->assertSessionHasErrors('branch_id');

        $this->assertDatabaseCount('rooms', 0);
    }

    public function test_room_update_preserves_existing_branch(): void
    {
        $otherBranch = HotelBranch::factory()->create();

        $room = Room::factory()->create([
            'branch_id' => $this->branch->id,
            'room_type_id' => $this->roomType->id,
            'room_number' => 'B-202',
        ]);

        $this->actingAs($this->user)
            ->withSession(['active_branch_id' => $otherBranch->id])
            ->put(route('rooms.update', $room), $this->payload([
                'room_number' => 'B-202',
                'capacity' => 3,
            ]))
            ->assertRedirect(route('rooms.index'));

        $this->assertDatabaseHas('rooms', [
            'id' => $room->id,
            'branch_id' => $this->branch->id,
            'capacity' => 3,
        ]);
    }

    public function test_room_number_must_be_unique_within_branch(): void
    {
        Room::factory()->create([
            'branch_id' => $this->branch->id,
            'room_type_id' => $this->roomType->id,
            'room_number' => 'A-101',
        ]);

        $this->actingAs($this->user)
            ->withSession(['active_branch_id' => $this->branch->id])
            ->post(route('rooms.store'), $this->payload())
            ->assertSessionHasErrors('room_number');
    }

    public function test_room_number_can_be_reused_across_branches(): void
    {
        Room::factory()->create([
            'branch_id' => $this->branch->id,
            'room_type_id' => $this->roomType->id,
            'room_number' => 'A-101',
        ]);

        $otherBranch = HotelBranch::factory()->create();
        $otherType = RoomType::factory()->create([
            'branch_id' => $otherBranch->id,
            'name' => 'Standard Twin',
        ]);

        $this->actingAs($this->user)
            ->withSession(['active_branch_id' => $otherBranch->id])
            ->post(route('rooms.store'), $this->payload([
                'room_type_id' => $otherType->id,
            ]))
            ->assertRedirect(route('rooms.index'));

        $this->assertDatabaseHas('rooms', [
            'branch_id' => $otherBranch->id,
            'room_number' => 'A-101',
        ]);
        $this->assertSame(2, Room::count());
    }

    public function test_user_without_manage_permission_cannot_create_room(): void
    {
        $plain = User::factory()->create();

        $this->actingAs($plain)
            ->withSession(['active_branch_id' => $this->branch->id])
            ->post(route('rooms.store'), $this->payload())
            ->assertForbidden();
    }

    public function test_room_amenities_are_persisted_as_an_array(): void
    {
        $amenities = ['WiFi', 'Air Conditioning', 'Balcony'];

        $this->actingAs($this->user)
            ->withSession(['active_branch_id' => $this->branch->id])
            ->post(route('rooms.store'), $this->payload(['amenities' => $amenities]))
            ->assertRedirect(route('rooms.index'));

        $room = Room::query()->where('room_number', 'A-101')->first();

        $this->assertNotNull($room);
        $this->assertSame($amenities, $room->amenities);
    }

    public function test_room_amenities_can_be_cleared_on_update(): void
    {
        $room = Room::factory()->create([
            'branch_id' => $this->branch->id,
            'room_type_id' => $this->roomType->id,
            'room_number' => 'B-303',
            'amenities' => ['WiFi'],
        ]);

        $this->actingAs($this->user)
            ->withSession(['active_branch_id' => $this->branch->id])
            ->put(route('rooms.update', $room), $this->payload([
                'room_number' => 'B-303',
                'amenities' => [],
            ]))
            ->assertRedirect(route('rooms.index'));

        $this->assertSame([], $room->fresh()->amenities);
    }
}
