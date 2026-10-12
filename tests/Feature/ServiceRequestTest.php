<?php

namespace Tests\Feature;

use App\Enums\PermissionName;
use App\Models\HotelBranch;
use App\Models\Room;
use App\Models\ServiceRequest;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ServiceRequestTest extends TestCase
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
            PermissionName::ViewServices->value,
            PermissionName::ManageServices->value,
        ]);
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'room_id' => $this->room->id,
            'service_type' => 'room-service',
            'description' => 'Extra towels',
            'priority' => 'normal',
        ], $overrides);
    }

    public function test_index_lists_created_service_requests(): void
    {
        $this->actingAs($this->user)
            ->withSession(['active_branch_id' => $this->branch->id])
            ->post(route('services.store'), $this->payload())
            ->assertRedirect(route('services.index'));

        $this->actingAs($this->user)
            ->withSession(['active_branch_id' => $this->branch->id])
            ->get(route('services.index'))
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->component('Services/Index')
                    ->has('services.data', 1)
                    ->where('services.data.0.service_type', 'room-service'),
            );
    }

    public function test_store_creates_service_request(): void
    {
        $this->actingAs($this->user)
            ->withSession(['active_branch_id' => $this->branch->id])
            ->post(route('services.store'), $this->payload())
            ->assertRedirect(route('services.index'));

        $this->assertDatabaseHas('service_requests', [
            'branch_id' => $this->branch->id,
            'room_id' => $this->room->id,
            'status' => 'pending',
            'service_type' => 'room-service',
            'requested_by' => $this->user->id,
        ]);
    }

    public function test_store_rejects_missing_required_fields(): void
    {
        $this->actingAs($this->user)
            ->withSession(['active_branch_id' => $this->branch->id])
            ->post(route('services.store'), ['description' => 'No type'])
            ->assertSessionHasErrors(['service_type', 'priority']);

        $this->assertDatabaseCount('service_requests', 0);
    }

    public function test_user_without_permission_is_denied(): void
    {
        $viewer = User::factory()->create();

        $this->actingAs($viewer)
            ->get(route('services.index'))
            ->assertForbidden();

        $this->actingAs($viewer)
            ->post(route('services.store'), $this->payload())
            ->assertForbidden();

        $this->assertDatabaseCount('service_requests', 0);
    }

    private function makeService(): ServiceRequest
    {
        $this->actingAs($this->user)
            ->withSession(['active_branch_id' => $this->branch->id])
            ->post(route('services.store'), $this->payload());

        return ServiceRequest::firstOrFail();
    }

    public function test_edit_page_loads_for_existing_service(): void
    {
        $service = $this->makeService();

        $this->actingAs($this->user)
            ->withSession(['active_branch_id' => $this->branch->id])
            ->get(route('services.edit', $service))
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->component('Services/Edit')
                    ->where('service.id', $service->id),
            );
    }

    public function test_update_persists_changes(): void
    {
        $service = $this->makeService();

        $this->actingAs($this->user)
            ->withSession(['active_branch_id' => $this->branch->id])
            ->put(route('services.update', $service), $this->payload([
                'service_number' => $service->service_number,
                'service_type' => 'laundry-pickup',
                'priority' => 'high',
            ]))
            ->assertRedirect(route('services.index'));

        $this->assertDatabaseHas('service_requests', [
            'id' => $service->id,
            'service_type' => 'laundry-pickup',
            'priority' => 'high',
        ]);
    }

    public function test_complete_marks_service_completed(): void
    {
        $service = $this->makeService();

        $this->actingAs($this->user)
            ->post(route('services.complete', $service))
            ->assertRedirect(route('services.index'));

        $this->assertDatabaseHas('service_requests', [
            'id' => $service->id,
            'status' => 'completed',
            'assigned_to' => $this->user->id,
        ]);
        $this->assertNotNull($service->fresh()->completed_at);
    }

    public function test_destroy_removes_service(): void
    {
        $service = $this->makeService();

        $this->actingAs($this->user)
            ->delete(route('services.destroy', $service))
            ->assertRedirect(route('services.index'));

        $this->assertSame(0, ServiceRequest::count());
    }
}
