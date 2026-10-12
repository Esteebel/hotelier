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

class LaundryTest extends TestCase
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
            PermissionName::ViewLaundry->value,
            PermissionName::ManageLaundry->value,
        ]);
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'room_id' => $this->room->id,
            'total_amount' => 2500,
            'pickup_date' => '2026-11-01',
            'delivery_date' => '2026-11-03',
            'notes' => 'Two shirts',
        ], $overrides);
    }

    public function test_index_lists_created_laundry_orders(): void
    {
        $this->actingAs($this->user)
            ->withSession(['active_branch_id' => $this->branch->id])
            ->post(route('laundry.store'), $this->payload())
            ->assertRedirect(route('laundry.index'));

        $this->actingAs($this->user)
            ->withSession(['active_branch_id' => $this->branch->id])
            ->get(route('laundry.index'))
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->component('Laundry/Index')
                    ->has('orders.data', 1)
                    ->where(
                        'orders.data.0.total_amount',
                        fn ($amount) => (float) $amount === 2500.0,
                    ),
            );
    }

    public function test_store_creates_laundry_order(): void
    {
        $this->actingAs($this->user)
            ->withSession(['active_branch_id' => $this->branch->id])
            ->post(route('laundry.store'), $this->payload())
            ->assertRedirect(route('laundry.index'));

        $this->assertDatabaseHas('laundry_orders', [
            'branch_id' => $this->branch->id,
            'room_id' => $this->room->id,
            'status' => 'pending',
            'total_amount' => 2500,
            'created_by' => $this->user->id,
        ]);
    }

    public function test_store_rejects_delivery_before_pickup(): void
    {
        $this->actingAs($this->user)
            ->withSession(['active_branch_id' => $this->branch->id])
            ->post(route('laundry.store'), $this->payload([
                'pickup_date' => '2026-11-05',
                'delivery_date' => '2026-11-01',
            ]))
            ->assertSessionHasErrors('delivery_date');

        $this->assertDatabaseCount('laundry_orders', 0);
    }

    public function test_user_without_permission_is_denied(): void
    {
        $viewer = User::factory()->create();

        $this->actingAs($viewer)
            ->get(route('laundry.index'))
            ->assertForbidden();

        $this->actingAs($viewer)
            ->post(route('laundry.store'), $this->payload())
            ->assertForbidden();

        $this->assertDatabaseCount('laundry_orders', 0);
    }
}
