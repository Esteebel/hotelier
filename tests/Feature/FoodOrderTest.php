<?php

namespace Tests\Feature;

use App\Enums\PermissionName;
use App\Models\FoodOrder;
use App\Models\HotelBranch;
use App\Models\MenuItem;
use App\Models\Room;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class FoodOrderTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private HotelBranch $branch;

    private Room $room;

    private MenuItem $menuItem;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionSeeder::class);

        $this->branch = HotelBranch::factory()->create(['is_default' => true]);
        $this->room = Room::factory()->create(['branch_id' => $this->branch->id]);
        $this->menuItem = MenuItem::create([
            'branch_id' => $this->branch->id,
            'name' => 'Jollof Rice',
            'category' => 'Lunch',
            'description' => 'Classic Nigerian jollof rice.',
            'price' => 5000,
            'is_available' => true,
            'sort_order' => 1,
        ]);

        $this->user = User::factory()->create();
        $this->user->givePermissionTo([
            PermissionName::ManageRestaurantOrders->value,
        ]);
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'order_type' => 'dine_in',
            'room_id' => $this->room->id,
            'items' => [
                ['menu_item_id' => $this->menuItem->id, 'quantity' => 2],
            ],
        ], $overrides);
    }

    public function test_index_lists_created_food_orders(): void
    {
        $this->actingAs($this->user)
            ->withSession(['active_branch_id' => $this->branch->id])
            ->post(route('restaurant.store'), $this->payload())
            ->assertRedirect(route('restaurant.index'));

        $this->actingAs($this->user)
            ->withSession(['active_branch_id' => $this->branch->id])
            ->get(route('restaurant.index'))
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->component('Restaurant/Index')
                    ->has('orders.data', 1)
                    ->where('orders.data.0.order_type', 'dine_in'),
            );
    }

    public function test_store_creates_order_with_composed_total(): void
    {
        $this->actingAs($this->user)
            ->withSession(['active_branch_id' => $this->branch->id])
            ->post(route('restaurant.store'), $this->payload())
            ->assertRedirect(route('restaurant.index'));

        $this->assertDatabaseHas('food_orders', [
            'branch_id' => $this->branch->id,
            'order_type' => 'dine_in',
            'status' => 'pending',
            'total_amount' => 10000,
            'created_by' => $this->user->id,
        ]);
    }

    public function test_store_rejects_order_without_items(): void
    {
        $this->actingAs($this->user)
            ->withSession(['active_branch_id' => $this->branch->id])
            ->post(route('restaurant.store'), $this->payload(['items' => []]))
            ->assertSessionHasErrors('items');

        $this->assertDatabaseCount('food_orders', 0);
    }

    public function test_user_without_permission_is_denied(): void
    {
        $viewer = User::factory()->create();

        $this->actingAs($viewer)
            ->get(route('restaurant.index'))
            ->assertForbidden();

        $this->actingAs($viewer)
            ->post(route('restaurant.store'), $this->payload())
            ->assertForbidden();

        $this->assertDatabaseCount('food_orders', 0);
    }

    private function makeOrder()
    {
        $this->actingAs($this->user)
            ->withSession(['active_branch_id' => $this->branch->id])
            ->post(route('restaurant.store'), $this->payload());

        return FoodOrder::firstOrFail();
    }

    public function test_edit_page_loads_for_existing_order(): void
    {
        $order = $this->makeOrder();

        $this->actingAs($this->user)
            ->withSession(['active_branch_id' => $this->branch->id])
            ->get(route('restaurant.edit', $order))
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->component('Restaurant/Edit')
                    ->where('order.id', $order->id),
            );
    }

    public function test_update_persists_changes(): void
    {
        $order = $this->makeOrder();

        $this->actingAs($this->user)
            ->withSession(['active_branch_id' => $this->branch->id])
            ->put(route('restaurant.update', $order), $this->payload([
                'order_type' => 'takeout',
                'notes' => 'Extra pepper',
            ]))
            ->assertRedirect(route('restaurant.index'));

        $this->assertDatabaseHas('food_orders', [
            'id' => $order->id,
            'order_type' => 'takeout',
            'notes' => 'Extra pepper',
            'total_amount' => 10000,
        ]);
    }

    public function test_complete_marks_order_completed(): void
    {
        $order = $this->makeOrder();

        $this->actingAs($this->user)
            ->post(route('restaurant.complete', $order))
            ->assertRedirect(route('restaurant.index'));

        $this->assertDatabaseHas('food_orders', [
            'id' => $order->id,
            'status' => 'completed',
        ]);
    }

    public function test_destroy_removes_order(): void
    {
        $order = $this->makeOrder();

        $this->actingAs($this->user)
            ->delete(route('restaurant.destroy', $order))
            ->assertRedirect(route('restaurant.index'));

        $this->assertSame(0, FoodOrder::count());
    }
}
