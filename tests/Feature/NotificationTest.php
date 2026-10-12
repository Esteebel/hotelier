<?php

namespace Tests\Feature;

use App\Enums\PermissionName;
use App\Models\HotelBranch;
use App\Models\Notification;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class NotificationTest extends TestCase
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
            PermissionName::ViewNotifications->value,
            PermissionName::ManageNotifications->value,
        ]);
    }

    public function test_index_lists_notifications_with_recipient(): void
    {
        Notification::create([
            'notification_number' => 'NTF-0001',
            'branch_id' => $this->branch->id,
            'user_id' => $this->user->id,
            'title' => 'System maintenance',
            'message' => 'Tonight at 11pm.',
            'type' => 'info',
            'status' => 'unread',
        ]);

        $this->actingAs($this->user)
            ->withSession(['active_branch_id' => $this->branch->id])
            ->get(route('notifications.index'))
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->component('Notifications/Index')
                    ->has('notifications.data', 1)
                    ->where('notifications.data.0.user.name', $this->user->name),
            );
    }

    public function test_store_creates_notification(): void
    {
        $this->actingAs($this->user)
            ->withSession(['active_branch_id' => $this->branch->id])
            ->post(route('notifications.store'), [
                'title' => 'Fire drill',
                'message' => 'Assembly at the car park.',
                'type' => 'alert',
            ])
            ->assertRedirect(route('notifications.index'));

        $this->assertDatabaseHas('notifications', [
            'branch_id' => $this->branch->id,
            'title' => 'Fire drill',
            'type' => 'alert',
            'status' => 'unread',
        ]);
    }

    public function test_store_rejects_missing_title(): void
    {
        $this->actingAs($this->user)
            ->withSession(['active_branch_id' => $this->branch->id])
            ->post(route('notifications.store'), [
                'message' => 'No title',
                'type' => 'info',
            ])
            ->assertSessionHasErrors('title');

        $this->assertDatabaseCount('notifications', 0);
    }

    public function test_user_without_permission_is_denied(): void
    {
        $viewer = User::factory()->create();

        $this->actingAs($viewer)
            ->get(route('notifications.index'))
            ->assertForbidden();

        $this->actingAs($viewer)
            ->post(route('notifications.store'), [
                'title' => 'Nope',
                'type' => 'info',
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('notifications', 0);
    }
}
