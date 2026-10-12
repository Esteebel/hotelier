<?php

namespace Tests\Feature;

use App\Enums\PermissionName;
use App\Models\HotelBranch;
use App\Models\MenuItem;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class MenuItemCrudTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private HotelBranch $branch;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionSeeder::class);

        $this->branch = HotelBranch::factory()->create([
            'status' => 'active',
            'is_default' => true,
        ]);

        $this->user = User::factory()->create();
        $this->user->givePermissionTo([
            PermissionName::ManageMenuItems->value,
        ]);
    }

    private function makeItem(array $overrides = []): MenuItem
    {
        return MenuItem::create(array_merge([
            'branch_id' => $this->branch->id,
            'name' => 'Jollof Rice',
            'category' => 'Lunch',
            'description' => 'Classic Nigerian jollof rice.',
            'price' => 5000,
            'is_available' => true,
            'sort_order' => 1,
        ], $overrides));
    }

    public function test_menu_index_requires_authentication(): void
    {
        $this->get(route('menu.index'))->assertRedirect(route('login'));
    }

    public function test_user_with_permission_can_view_menu_listing(): void
    {
        $this->makeItem(['name' => 'Pounded Yam']);

        $this->actingAs($this->user)
            ->get(route('menu.index'))
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->component('Restaurant/Menu/Index')
                    ->has('menuItems.data', 1)
                    ->where('menuItems.data.0.name', 'Pounded Yam'),
            );
    }

    public function test_user_without_permission_cannot_view_menu_listing(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('menu.index'))
            ->assertForbidden();
    }

    public function test_menu_item_can_be_created(): void
    {
        $response = $this->actingAs($this->user)
            ->post(route('menu.store'), [
                'name' => 'Efo Riro',
                'category' => 'Dinner',
                'description' => 'Rich vegetable stew.',
                'price' => 4500,
                'is_available' => 1,
                'sort_order' => 5,
            ]);

        $response->assertRedirect(route('menu.index'));

        $this->assertDatabaseHas('menu_items', [
            'name' => 'Efo Riro',
            'category' => 'Dinner',
            'branch_id' => $this->branch->id,
            'price' => 4500,
            'is_available' => true,
        ]);
    }

    public function test_menu_item_store_requires_valid_category(): void
    {
        $this->actingAs($this->user)
            ->post(route('menu.store'), [
                'name' => 'Bad Item',
                'category' => 'NotACategory',
                'price' => 100,
            ])
            ->assertSessionHasErrors('category');

        $this->assertDatabaseMissing('menu_items', ['name' => 'Bad Item']);
    }

    public function test_menu_item_can_be_updated(): void
    {
        $item = $this->makeItem();

        $this->actingAs($this->user)
            ->put(route('menu.update', $item), [
                'name' => 'Egusi Soup',
                'category' => 'Lunch',
                'description' => 'Melon seed soup.',
                'price' => 6000,
                'is_available' => 0,
                'sort_order' => 2,
            ])
            ->assertRedirect(route('menu.index'));

        $this->assertDatabaseHas('menu_items', [
            'id' => $item->id,
            'name' => 'Egusi Soup',
            'price' => 6000,
            'is_available' => false,
        ]);
    }

    public function test_menu_item_can_be_deleted(): void
    {
        $item = $this->makeItem();

        $this->actingAs($this->user)
            ->delete(route('menu.destroy', $item))
            ->assertRedirect(route('menu.index'));

        $this->assertSoftDeleted('menu_items', ['id' => $item->id]);
    }

    public function test_menu_item_edit_page_renders(): void
    {
        $item = $this->makeItem();

        $this->actingAs($this->user)
            ->get(route('menu.edit', $item))
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->component('Restaurant/Menu/Edit')
                    ->where('menuItem.name', 'Jollof Rice'),
            );
    }

    public function test_menu_create_page_loads_for_authorized_user(): void
    {
        $this->actingAs($this->user)
            ->get(route('menu.create'))
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->component('Restaurant/Menu/Create')
                    ->has('categories'),
            );
    }
}
