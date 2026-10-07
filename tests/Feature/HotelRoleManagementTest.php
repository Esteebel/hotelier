<?php

namespace Tests\Feature;

use App\Enums\PermissionName;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class HotelRoleManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionSeeder::class);

        $this->user = User::factory()->create();
        $this->user->givePermissionTo(PermissionName::ManageRoles->value);
    }

    public function test_role_index_requires_authentication(): void
    {
        $this->get(route('roles.index'))->assertRedirect(route('login'));
    }

    public function test_user_with_manage_roles_can_view_the_role_listing(): void
    {
        $role = Role::create(['name' => 'custom-viewer']);
        $role->syncPermissions(['view-dashboard', 'view-users']);

        $this->actingAs($this->user)
            ->get(route('roles.index', ['search' => 'custom-viewer']))
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->component('Roles/Index')
                    ->has('roles.data', 1)
                    ->where('roles.data.0.name', 'custom-viewer')
                    ->where('roles.data.0.permissions_count', 2)
                    ->where('roles.data.0.users_count', 0),
            );
    }

    public function test_user_without_permission_cannot_view_roles(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('roles.index'))
            ->assertForbidden();
    }

    public function test_role_form_page_exposes_permission_groups_with_checkbox_shape(): void
    {
        $this->actingAs($this->user)
            ->get(route('roles.create'))
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->component('Roles/Create')
                    ->has('permissionGroups')
                    ->has(
                        'permissionGroups.0',
                        fn (Assert $group) => $group
                            ->has('label')
                            ->has('permissions')
                            ->has(
                                'permissions.0',
                                fn (Assert $permission) => $permission
                                    ->has('name')
                                    ->has('label'),
                            ),
                    ),
            );
    }

    public function test_role_form_page_for_edit_exposes_permission_groups(): void
    {
        $role = Role::create(['name' => 'auditor']);
        $role->syncPermissions(['view-reports']);

        $this->actingAs($this->user)
            ->get(route('roles.edit', $role))
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->component('Roles/Edit')
                    ->has('permissionGroups.0.permissions.0.name')
                    ->where('role.name', 'auditor'),
            );
    }

    public function test_role_can_be_created_with_permissions_synced(): void
    {
        $this->actingAs($this->user)
            ->post(route('roles.store'), [
                'name' => 'auditor',
                'permissions' => ['view-reports', 'view-payments'],
            ])
            ->assertRedirect(route('roles.index'))
            ->assertSessionHas('success');

        $role = Role::findByName('auditor');

        $this->assertSame('web', $role->guard_name);
        $this->assertTrue($role->hasPermissionTo('view-reports'));
        $this->assertTrue($role->hasPermissionTo('view-payments'));
    }

    public function test_role_name_must_be_unique(): void
    {
        $this->actingAs($this->user)
            ->post(route('roles.store'), [
                'name' => 'super-admin',
                'permissions' => [],
            ])
            ->assertSessionHasErrors('name');

        $this->assertSame(1, Role::query()->where('name', 'super-admin')->count());
    }

    public function test_permissions_must_exist_when_creating_a_role(): void
    {
        $this->actingAs($this->user)
            ->post(route('roles.store'), [
                'name' => 'invalid perms role',
                'permissions' => ['does-not-exist'],
            ])
            ->assertSessionHasErrors('permissions.0');

        $this->assertDatabaseMissing('roles', ['name' => 'invalid perms role']);
    }

    public function test_role_can_be_updated_and_permissions_are_synced(): void
    {
        $role = Role::create(['name' => 'night-auditor']);
        $role->syncPermissions(['view-dashboard']);

        $this->actingAs($this->user)
            ->put(route('roles.update', $role), [
                'name' => 'night-auditor-senior',
                'permissions' => ['view-reports', 'manage-payments'],
            ])
            ->assertRedirect(route('roles.index'));

        $this->assertDatabaseHas('roles', ['name' => 'night-auditor-senior']);
        $this->assertDatabaseMissing('roles', ['name' => 'night-auditor']);

        $role->refresh();

        $this->assertTrue($role->hasPermissionTo('view-reports'));
        $this->assertTrue($role->hasPermissionTo('manage-payments'));
        $this->assertFalse($role->hasPermissionTo('view-dashboard'));
    }

    public function test_user_without_manage_roles_cannot_create_role(): void
    {
        $viewer = User::factory()->create();
        $viewer->givePermissionTo(PermissionName::ViewUsers->value);

        $this->actingAs($viewer)
            ->post(route('roles.store'), [
                'name' => 'blocked role',
                'permissions' => [],
            ])
            ->assertForbidden();
    }

    public function test_user_without_manage_roles_cannot_delete_role(): void
    {
        $viewer = User::factory()->create();
        $viewer->givePermissionTo(PermissionName::ViewUsers->value);
        $role = Role::create(['name' => 'unmanaged role']);

        $this->actingAs($viewer)
            ->delete(route('roles.destroy', $role))
            ->assertForbidden();

        $this->assertDatabaseHas('roles', ['name' => 'unmanaged role']);
    }

    public function test_role_with_assigned_users_cannot_be_deleted(): void
    {
        $manager = User::factory()->create();
        $manager->assignRole('manager');

        $this->actingAs($this->user)
            ->delete(route('roles.destroy', Role::findByName('manager')))
            ->assertForbidden();

        $this->assertDatabaseHas('roles', ['name' => 'manager']);
    }

    public function test_super_admin_role_cannot_be_deleted(): void
    {
        $this->actingAs($this->user)
            ->delete(route('roles.destroy', Role::findByName('super-admin')))
            ->assertForbidden();

        $this->assertDatabaseHas('roles', ['name' => 'super-admin']);
    }

    public function test_unassigned_role_can_be_deleted(): void
    {
        $role = Role::create(['name' => 'disposable role']);

        $this->actingAs($this->user)
            ->delete(route('roles.destroy', $role))
            ->assertRedirect(route('roles.index'));

        $this->assertDatabaseMissing('roles', ['name' => 'disposable role']);
    }
}