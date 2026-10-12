<?php

namespace Tests\Feature;

use App\Enums\PermissionName;
use App\Models\HotelBranch;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class HotelUserManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionSeeder::class);

        $this->user = User::factory()->create();
        $this->user->givePermissionTo([
            PermissionName::ViewUsers->value,
            PermissionName::ManageUsers->value,
        ]);
    }

    public function test_user_index_requires_authentication(): void
    {
        $this->get(route('users.index'))->assertRedirect(route('login'));
    }

    public function test_user_with_view_permission_can_view_the_user_listing(): void
    {
        $target = User::factory()->create(['name' => 'Ada Lovelace']);
        $target->assignRole('super-admin');

        $this->actingAs($this->user)
            ->get(route('users.index', ['search' => 'Ada']))
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->component('Users/Index')
                    ->has('users.data', 1)
                    ->has('roles')
                    ->where('users.data.0.name', 'Ada Lovelace')
                    ->where('users.data.0.status', 'active')
                    ->where('users.data.0.status_label', 'Active')
                    ->where('users.data.0.roles.0', 'super-admin'),
            );
    }

    public function test_user_listing_can_be_filtered_by_role_and_status(): void
    {
        $manager = User::factory()->create(['name' => 'Manager User']);
        $manager->assignRole('manager');
        User::factory()->create(['status' => 'inactive']);

        $this->actingAs($this->user)
            ->get(route('users.index', ['role' => 'manager']))
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->component('Users/Index')
                    ->has('users.data', 1)
                    ->where('users.data.0.name', 'Manager User'),
            );
    }

    public function test_user_without_permission_cannot_view_users(): void
    {
        $plain = User::factory()->create();

        $this->actingAs($plain)
            ->get(route('users.index'))
            ->assertForbidden();
    }

    public function test_user_can_be_created_with_roles_and_branches_synced(): void
    {
        $branch = HotelBranch::factory()->create();

        $this->actingAs($this->user)
            ->post(route('users.store'), [
                'name' => 'Grace Hopper',
                'email' => 'grace@example.com',
                'password' => 'password',
                'password_confirmation' => 'password',
                'status' => 'active',
                'roles' => ['manager', 'receptionist'],
                'branches' => [$branch->id],
            ])
            ->assertRedirect(route('users.index'))
            ->assertSessionHas('success');

        $created = User::query()->where('email', 'grace@example.com')->firstOrFail();

        $this->assertSame('active', $created->status->value);
        $this->assertTrue($created->hasRole('manager'));
        $this->assertTrue($created->hasRole('receptionist'));
        $this->assertTrue($created->branches()->whereKey($branch->id)->exists());
    }

    public function test_user_created_without_password_can_sign_in_with_default_password(): void
    {
        $this->actingAs($this->user)
            ->post(route('users.store'), [
                'name' => 'Front Desk',
                'email' => 'frontdesk@example.com',
                'status' => 'active',
                'roles' => ['staff'],
            ])
            ->assertRedirect(route('users.index'))
            ->assertSessionHas('success');

        $this->assertStringContainsString(
            (string) config('app.default_user_password'),
            (string) session('success'),
        );

        $this->assertTrue(
            Auth::attempt([
                'email' => 'frontdesk@example.com',
                'password' => (string) config('app.default_user_password'),
            ]),
        );
        Auth::logout();
    }

    public function test_custom_password_takes_precedence_over_default(): void
    {
        $this->actingAs($this->user)
            ->post(route('users.store'), [
                'name' => 'Custom Password User',
                'email' => 'custompw@example.com',
                'password' => 'MySecret123!',
                'password_confirmation' => 'MySecret123!',
                'status' => 'active',
                'roles' => ['staff'],
            ])
            ->assertRedirect(route('users.index'));

        $this->assertStringNotContainsString(
            'default password',
            (string) session('success'),
        );

        $this->assertFalse(
            Auth::attempt([
                'email' => 'custompw@example.com',
                'password' => (string) config('app.default_user_password'),
            ]),
        );

        $this->assertTrue(
            Auth::attempt(['email' => 'custompw@example.com', 'password' => 'MySecret123!']),
        );
        Auth::logout();
    }

    public function test_user_create_page_exposes_default_password(): void
    {
        $this->actingAs($this->user)
            ->get(route('users.create'))
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->component('Users/Create')
                    ->where('defaultPassword', config('app.default_user_password')),
            );
    }

    public function test_email_is_validated_as_unique_on_create(): void
    {
        $this->actingAs($this->user)
            ->post(route('users.store'), [
                'name' => 'Duplicate User',
                'email' => $this->user->email,
                'password' => 'password',
                'password_confirmation' => 'password',
                'status' => 'active',
                'roles' => ['staff'],
            ])
            ->assertSessionHasErrors('email');
    }

    public function test_user_can_be_updated_with_roles_and_branches_synced(): void
    {
        $target = User::factory()->create();
        $branch = HotelBranch::factory()->create();

        $this->actingAs($this->user)
            ->put(route('users.update', $target), [
                'name' => 'Renamed User',
                'email' => 'renamed@example.com',
                'status' => 'inactive',
                'roles' => ['accountant'],
                'branches' => [$branch->id],
            ])
            ->assertRedirect(route('users.index'));

        $target->refresh();

        $this->assertSame('Renamed User', $target->name);
        $this->assertSame('inactive', $target->status->value);
        $this->assertTrue($target->hasRole('accountant'));
        $this->assertTrue($target->branches()->whereKey($branch->id)->exists());
    }

    public function test_user_without_manage_permission_cannot_create_user(): void
    {
        $viewOnly = User::factory()->create();
        $viewOnly->givePermissionTo(PermissionName::ViewUsers->value);

        $this->actingAs($viewOnly)
            ->post(route('users.store'), [
                'name' => 'Blocked User',
                'email' => 'blocked@example.com',
                'password' => 'password',
                'password_confirmation' => 'password',
                'status' => 'active',
                'roles' => ['staff'],
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('users', ['email' => 'blocked@example.com']);
    }

    public function test_user_without_manage_permission_cannot_update_user(): void
    {
        $viewOnly = User::factory()->create();
        $viewOnly->givePermissionTo(PermissionName::ViewUsers->value);
        $target = User::factory()->create();

        $this->actingAs($viewOnly)
            ->put(route('users.update', $target), [
                'name' => 'Hacked User',
                'email' => $target->email,
                'status' => 'active',
                'roles' => ['staff'],
            ])
            ->assertForbidden();
    }

    public function test_user_without_manage_permission_cannot_delete_user(): void
    {
        $viewOnly = User::factory()->create();
        $viewOnly->givePermissionTo(PermissionName::ViewUsers->value);
        $target = User::factory()->create();

        $this->actingAs($viewOnly)
            ->delete(route('users.destroy', $target))
            ->assertForbidden();

        $this->assertDatabaseHas('users', ['id' => $target->id]);
    }

    public function test_authorized_user_can_delete_a_regular_user(): void
    {
        $target = User::factory()->create();

        $this->actingAs($this->user)
            ->delete(route('users.destroy', $target))
            ->assertRedirect(route('users.index'));

        $this->assertDatabaseMissing('users', ['id' => $target->id]);
    }

    public function test_user_cannot_delete_themselves(): void
    {
        $this->actingAs($this->user)
            ->delete(route('users.destroy', $this->user))
            ->assertForbidden();

        $this->assertDatabaseHas('users', ['id' => $this->user->id]);
    }

    public function test_super_admin_user_cannot_be_deleted(): void
    {
        $super = User::factory()->create();
        $super->assignRole('super-admin');

        $this->actingAs($this->user)
            ->delete(route('users.destroy', $super))
            ->assertForbidden();

        $this->assertDatabaseHas('users', ['id' => $super->id]);
    }

    public function test_super_admin_user_cannot_be_edited_by_non_super_admin(): void
    {
        $super = User::factory()->create();
        $super->assignRole('super-admin');

        $this->actingAs($this->user)
            ->put(route('users.update', $super), [
                'name' => 'Hacked Super Admin',
                'email' => $super->email,
                'status' => 'active',
                'roles' => ['super-admin'],
            ])
            ->assertForbidden();
    }

    public function test_user_edit_page_loads(): void
    {
        $target = User::factory()->create(['name' => 'Edit Target']);

        $this->actingAs($this->user)
            ->get(route('users.edit', $target))
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->component('Users/Edit')
                    ->where('user.id', $target->id),
            );
    }
}
