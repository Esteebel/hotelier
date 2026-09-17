<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_requires_authentication(): void
    {
        $this->get(route('dashboard'))->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_view_the_dashboard(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->component('Dashboard')
                    ->has('branches', 0)
                    ->where('activeBranch', null)
                    ->where('auth.user.id', $user->id),
            );
    }

    public function test_dashboard_shares_branch_props_for_the_selector(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('dashboard'));

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->component('Dashboard')
                    ->has('branches', 0)
                    ->where('activeBranch', null),
            );
    }

    public function test_guest_pages_do_not_expose_branch_props(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->component('Auth/Login')
                    ->has('branches', 0)
                    ->where('activeBranch', null),
            );
    }
}