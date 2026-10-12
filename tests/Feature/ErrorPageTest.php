<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ErrorPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionSeeder::class);
    }
    public function test_unknown_url_renders_custom_404_page(): void
    {
        $this->get('/definitely-not-a-page')
            ->assertNotFound()
            ->assertSee('Page not found')
            ->assertSee('does not exist');
    }

    public function test_forbidden_route_renders_custom_403_page(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('invoices.index'))
            ->assertForbidden()
            ->assertSee('Access denied');
    }

    public function test_error_pages_do_not_leak_debug_details(): void
    {
        $this->get('/definitely-not-a-page')
            ->assertNotFound()
            ->assertDontSee('Whoops, something went wrong')
            ->assertDontSee('Stack trace');
    }
}
