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

class MaintenanceTest extends TestCase
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
            PermissionName::ViewMaintenance->value,
            PermissionName::ManageMaintenance->value,
        ]);
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'room_id' => $this->room->id,
            'priority' => 'urgent',
            'task_type' => 'plumbing',
            'due_date' => '2026-11-05',
            'assigned_to' => $this->user->id,
            'notes' => 'Leaking faucet',
        ], $overrides);
    }

    public function test_index_lists_created_tasks_with_assignee(): void
    {
        $this->actingAs($this->user)
            ->withSession(['active_branch_id' => $this->branch->id])
            ->post(route('maintenance.store'), $this->payload())
            ->assertRedirect(route('maintenance.index'));

        $this->actingAs($this->user)
            ->withSession(['active_branch_id' => $this->branch->id])
            ->get(route('maintenance.index'))
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->component('Maintenance/Index')
                    ->has('tasks.data', 1)
                    ->where('tasks.data.0.assignee.name', $this->user->name),
            );
    }

    public function test_store_creates_task_with_branch_context(): void
    {
        $this->actingAs($this->user)
            ->withSession(['active_branch_id' => $this->branch->id])
            ->post(route('maintenance.store'), $this->payload())
            ->assertRedirect(route('maintenance.index'));

        $this->assertDatabaseHas('maintenance_tasks', [
            'branch_id' => $this->branch->id,
            'room_id' => $this->room->id,
            'status' => 'pending',
            'task_type' => 'plumbing',
            'priority' => 'urgent',
            'assigned_to' => $this->user->id,
            'requested_by' => $this->user->id,
        ]);
    }

    public function test_store_rejects_invalid_task_type(): void
    {
        $this->actingAs($this->user)
            ->withSession(['active_branch_id' => $this->branch->id])
            ->post(route('maintenance.store'), $this->payload(['task_type' => 'dancing']))
            ->assertSessionHasErrors('task_type');

        $this->assertDatabaseCount('maintenance_tasks', 0);
    }

    public function test_user_without_permission_is_denied(): void
    {
        $viewer = User::factory()->create();

        $this->actingAs($viewer)
            ->get(route('maintenance.index'))
            ->assertForbidden();

        $this->actingAs($viewer)
            ->post(route('maintenance.store'), $this->payload())
            ->assertForbidden();

        $this->assertDatabaseCount('maintenance_tasks', 0);
    }
}
