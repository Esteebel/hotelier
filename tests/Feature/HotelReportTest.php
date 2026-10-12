<?php

namespace Tests\Feature;

use App\Enums\PermissionName;
use App\Models\HotelBranch;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class HotelReportTest extends TestCase
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
        $this->user->givePermissionTo(PermissionName::ViewReports->value);
    }

    public function test_reports_index_requires_authentication(): void
    {
        $this->get(route('reports.index'))->assertRedirect(route('login'));
    }

    public function test_reports_index_loads_for_authorized_user(): void
    {
        $this->actingAs($this->user)
            ->get(route('reports.index'))
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->component('Reports/Index')
                    ->has('metrics'),
            );
    }

    public function test_reports_index_forbids_user_without_view_reports_permission(): void
    {
        $other = User::factory()->create();

        $this->actingAs($other)
            ->get(route('reports.index'))
            ->assertForbidden();
    }

    public function test_export_streams_csv_for_each_report_type(): void
    {
        $expectations = [
            'reservations' => 'ID,Guest,Room',
            'payments' => 'ID,Reservation,Amount',
            'guests' => 'Name,Email,Phone',
        ];

        foreach ($expectations as $type => $header) {
            $response = $this->actingAs($this->user)
                ->get(route('reports.export', ['type' => $type]))
                ->assertOk();

            $content = $response->streamedContent();

            $this->assertStringContainsString($header, $content, "Missing header for {$type} export.");
            $this->assertStringContainsString(
                "{$type}_report_",
                (string) $response->headers->get('Content-Disposition'),
            );
        }
    }

    public function test_export_forbids_user_without_view_reports_permission(): void
    {
        $other = User::factory()->create();

        $this->actingAs($other)
            ->get(route('reports.export', ['type' => 'reservations']))
            ->assertForbidden();
    }
}
