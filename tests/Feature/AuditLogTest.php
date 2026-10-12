<?php

namespace Tests\Feature;

use App\Enums\PermissionName;
use App\Models\AuditLog;
use App\Models\HotelBranch;
use App\Models\Room;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AuditLogTest extends TestCase
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
            PermissionName::ViewAuditLogs->value,
        ]);
    }

    public function test_index_lists_logs_with_actor(): void
    {
        AuditLog::create([
            'branch_id' => $this->branch->id,
            'user_id' => $this->user->id,
            'event' => 'created',
            'auditable_type' => Room::class,
            'auditable_id' => 1,
            'old_values' => [],
            'new_values' => ['room_number' => 'A-101'],
        ]);

        $this->actingAs($this->user)
            ->withSession(['active_branch_id' => $this->branch->id])
            ->get(route('audit-logs.index'))
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->component('AuditLogs/Index')
                    ->has('logs.data', 1)
                    ->where('logs.data.0.user.name', $this->user->name)
                    ->where('logs.data.0.event', 'created'),
            );
    }

    public function test_user_without_permission_is_denied(): void
    {
        $viewer = User::factory()->create();

        $this->actingAs($viewer)
            ->get(route('audit-logs.index'))
            ->assertForbidden();
    }
}
