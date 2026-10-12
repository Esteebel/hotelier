<?php

namespace Tests\Feature;

use App\Enums\PermissionName;
use App\Models\HotelBranch;
use App\Models\Room;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class HousekeepingTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private HotelBranch $branch;

    private Room $room;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionSeeder::class);

        $this->branch = HotelBranch::factory()->create(['is_default' => true]);
        $this->room = Room::factory()->create(['branch_id' => $this->branch->id]);

        $this->user = User::factory()->create();
        $this->user->givePermissionTo([
            PermissionName::ViewHousekeeping->value,
            PermissionName::ManageHousekeeping->value,
        ]);
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'room_id' => $this->room->id,
            'priority' => 'high',
            'task_type' => 'cleaning',
            'due_date' => '2026-11-01',
            'assigned_to' => $this->user->id,
            'notes' => 'Test task',
        ], $overrides);
    }

    public function test_index_lists_created_tasks_with_assignee(): void
    {
        $this->actingAs($this->user)
            ->withSession(['active_branch_id' => $this->branch->id])
            ->post(route('housekeeping.store'), $this->payload())
            ->assertRedirect(route('housekeeping.index'));

        $this->actingAs($this->user)
            ->withSession(['active_branch_id' => $this->branch->id])
            ->get(route('housekeeping.index'))
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->component('Housekeeping/Index')
                    ->has('tasks.data', 1)
                    ->where('tasks.data.0.assignee.name', $this->user->name),
            );
    }

    public function test_store_creates_task_with_branch_context(): void
    {
        $this->actingAs($this->user)
            ->withSession(['active_branch_id' => $this->branch->id])
            ->post(route('housekeeping.store'), $this->payload())
            ->assertRedirect(route('housekeeping.index'));

        $this->assertDatabaseHas('housekeeping_tasks', [
            'branch_id' => $this->branch->id,
            'room_id' => $this->room->id,
            'status' => 'pending',
            'task_type' => 'cleaning',
            'priority' => 'high',
            'assigned_to' => $this->user->id,
            'requested_by' => $this->user->id,
        ]);
    }

    public function test_store_rejects_missing_required_fields(): void
    {
        $this->actingAs($this->user)
            ->withSession(['active_branch_id' => $this->branch->id])
            ->post(route('housekeeping.store'), [
                'priority' => 'high',
                'task_type' => 'cleaning',
            ])
            ->assertSessionHasErrors(['room_id', 'due_date']);

        $this->assertDatabaseCount('housekeeping_tasks', 0);
    }

    public function test_user_without_permission_is_denied(): void
    {
        $viewer = User::factory()->create();

        $this->actingAs($viewer)
            ->get(route('housekeeping.index'))
            ->assertForbidden();

        $this->actingAs($viewer)
            ->post(route('housekeeping.store'), $this->payload())
            ->assertForbidden();

        $this->assertDatabaseCount('housekeeping_tasks', 0);
    }
}
