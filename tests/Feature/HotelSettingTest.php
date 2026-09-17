<?php

namespace Tests\Feature;

use App\Enums\PermissionName;
use App\Models\HotelSetting;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class HotelSettingTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private const SETTINGS = [
        'brand_name' => 'Grand Heritage Hotel',
        'default_currency' => 'USD',
        'check_in_time' => '15:00',
        'check_out_time' => '11:00',
        'timezone' => 'Europe/London',
        'default_page_size' => 25,
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionSeeder::class);

        $this->user = User::factory()->create();
        $this->user->givePermissionTo(PermissionName::ManageSettings->value);
    }

    public function test_setting_index_requires_authentication(): void
    {
        $this->get(route('settings.index'))->assertRedirect(route('login'));
    }

    public function test_defaults_are_returned_when_table_is_empty(): void
    {
        $this->actingAs($this->user)
            ->get(route('settings.index'))
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->component('Settings/Index')
                    ->where('settings.brand_name', 'Hotel Management System')
                    ->where('settings.default_currency', 'NGN')
                    ->where('settings.check_in_time', '14:00')
                    ->where('settings.check_out_time', '12:00')
                    ->where('settings.timezone', 'Africa/Lagos')
                    ->where('settings.default_page_size', 10),
            );
    }

    public function test_user_without_manage_settings_permission_cannot_view_settings(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('settings.index'))
            ->assertForbidden();
    }

    public function test_user_without_manage_settings_permission_cannot_update_settings(): void
    {
        $this->actingAs(User::factory()->create())
            ->put(route('settings.update'), ['settings' => self::SETTINGS])
            ->assertForbidden();
    }

    public function test_settings_values_persist_via_update_or_create(): void
    {
        $this->actingAs($this->user)
            ->put(route('settings.update'), ['settings' => self::SETTINGS])
            ->assertRedirect(route('settings.index'))
            ->assertSessionHas('success');

        foreach (self::SETTINGS as $key => $value) {
            $this->assertDatabaseHas('hotel_settings', ['key' => $key]);
        }

        $this->actingAs($this->user)
            ->get(route('settings.index'))
            ->assertInertia(
                fn (Assert $page) => $page
                    ->component('Settings/Index')
                    ->where('settings.brand_name', 'Grand Heritage Hotel')
                    ->where('settings.default_currency', 'USD')
                    ->where('settings.check_in_time', '15:00')
                    ->where('settings.check_out_time', '11:00')
                    ->where('settings.timezone', 'Europe/London')
                    ->where('settings.default_page_size', 25),
            );
    }

    public function test_repeated_updates_upsert_rather_than_duplicate_rows(): void
    {
        $this->actingAs($this->user)
            ->put(route('settings.update'), ['settings' => self::SETTINGS]);

        $this->actingAs($this->user)
            ->put(route('settings.update'), ['settings' => self::SETTINGS]);

        $this->assertDatabaseCount('hotel_settings', count(self::SETTINGS));

        $brand = HotelSetting::query()->where('key', 'brand_name')->firstOrFail();
        $this->assertSame('Grand Heritage Hotel', $brand->value);
        $this->assertSame(1, HotelSetting::query()->where('key', 'brand_name')->count());
    }

    public function test_invalid_setting_value_is_rejected(): void
    {
        $payload = self::SETTINGS;
        $payload['check_in_time'] = '25:99';

        $this->actingAs($this->user)
            ->put(route('settings.update'), ['settings' => $payload])
            ->assertSessionHasErrors('settings.check_in_time');
    }
}