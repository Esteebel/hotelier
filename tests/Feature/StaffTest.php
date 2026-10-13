<?php

namespace Tests\Feature;

use App\Enums\PermissionName;
use App\Models\HotelBranch;
use App\Models\Staff;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class StaffTest extends TestCase
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
            PermissionName::ViewStaff->value,
            PermissionName::ManageStaff->value,
        ]);
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'first_name' => 'Ngozi',
            'last_name' => 'Okafor',
            'position' => 'Front Desk Officer',
            'department' => 'Front Office',
            'status' => 'active',
        ], $overrides);
    }

    public function test_index_lists_created_staff(): void
    {
        $this->actingAs($this->user)
            ->withSession(['active_branch_id' => $this->branch->id])
            ->post(route('staff.store'), $this->payload())
            ->assertRedirect(route('staff.index'));

        $this->actingAs($this->user)
            ->withSession(['active_branch_id' => $this->branch->id])
            ->get(route('staff.index'))
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->component('Staff/Index')
                    ->has('staff.data', 1)
                    ->where('staff.data.0.first_name', 'Ngozi'),
            );
    }

    public function test_store_creates_staff_member(): void
    {
        $this->actingAs($this->user)
            ->withSession(['active_branch_id' => $this->branch->id])
            ->post(route('staff.store'), $this->payload())
            ->assertRedirect(route('staff.index'));

        $this->assertDatabaseHas('staff', [
            'branch_id' => $this->branch->id,
            'first_name' => 'Ngozi',
            'last_name' => 'Okafor',
            'position' => 'Front Desk Officer',
            'status' => 'active',
        ]);
    }

    public function test_store_rejects_missing_required_fields(): void
    {
        $this->actingAs($this->user)
            ->withSession(['active_branch_id' => $this->branch->id])
            ->post(route('staff.store'), ['position' => 'Nowhere'])
            ->assertSessionHasErrors(['first_name', 'last_name', 'status']);

        $this->assertDatabaseCount('staff', 0);
    }

    public function test_user_without_permission_is_denied(): void
    {
        $viewer = User::factory()->create();

        $this->actingAs($viewer)
            ->get(route('staff.index'))
            ->assertForbidden();

        $this->actingAs($viewer)
            ->post(route('staff.store'), $this->payload())
            ->assertForbidden();

        $this->assertDatabaseCount('staff', 0);
    }

    private function makeStaff(array $overrides = [])
    {
        $this->actingAs($this->user)
            ->withSession(['active_branch_id' => $this->branch->id])
            ->post(route('staff.store'), $this->payload($overrides));

        return Staff::firstOrFail();
    }

    public function test_edit_page_loads_for_existing_staff(): void
    {
        $staff = $this->makeStaff();

        $this->actingAs($this->user)
            ->withSession(['active_branch_id' => $this->branch->id])
            ->get(route('staff.edit', $staff))
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->component('Staff/Edit')
                    ->where('staff.id', $staff->id),
            );
    }

    public function test_update_persists_changes(): void
    {
        $staff = $this->makeStaff();

        $this->actingAs($this->user)
            ->withSession(['active_branch_id' => $this->branch->id])
            ->put(route('staff.update', $staff), $this->payload([
                'position' => 'Night Auditor',
                'department' => 'Accounts',
            ]))
            ->assertRedirect(route('staff.index'));

        $this->assertDatabaseHas('staff', [
            'id' => $staff->id,
            'position' => 'Night Auditor',
            'department' => 'Accounts',
        ]);
    }

    public function test_complete_reactivates_inactive_staff(): void
    {
        $staff = $this->makeStaff(['status' => 'inactive']);

        $this->assertSame('inactive', $staff->status);

        $this->actingAs($this->user)
            ->post(route('staff.complete', $staff))
            ->assertRedirect(route('staff.index'));

        $this->assertDatabaseHas('staff', [
            'id' => $staff->id,
            'status' => 'active',
        ]);
    }

    public function test_destroy_removes_staff_member(): void
    {
        $staff = $this->makeStaff();

        $this->actingAs($this->user)
            ->delete(route('staff.destroy', $staff))
            ->assertRedirect(route('staff.index'));

        $this->assertSame(0, Staff::count());
    }

    public function test_create_page_loads_for_authorized_user(): void
    {
        $this->actingAs($this->user)
            ->get(route('staff.create'))
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page->component('Staff/Create'),
            );
    }
}
