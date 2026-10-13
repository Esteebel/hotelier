<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_page_is_displayed(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get('/profile');

        $response->assertOk();
    }

    public function test_password_can_be_updated_with_correct_current_password(): void
    {
        $user = User::factory()->create(['password' => 'OldSecret123!']);

        $this->actingAs($user)
            ->put(route('password.update'), [
                'current_password' => 'OldSecret123!',
                'password' => 'NewSecret456!',
                'password_confirmation' => 'NewSecret456!',
            ])
            ->assertSessionHasNoErrors();

        $this->assertFalse(
            Auth::attempt(['email' => $user->email, 'password' => 'OldSecret123!']),
        );
        Auth::logout();

        $this->assertTrue(
            Auth::attempt(['email' => $user->email, 'password' => 'NewSecret456!']),
        );
        Auth::logout();
    }

    public function test_password_update_requires_correct_current_password(): void
    {
        $user = User::factory()->create(['password' => 'OldSecret123!']);

        $this->actingAs($user)
            ->put(route('password.update'), [
                'current_password' => 'WrongPassword1!',
                'password' => 'NewSecret456!',
                'password_confirmation' => 'NewSecret456!',
            ])
            ->assertSessionHasErrors('current_password');

        $this->assertTrue(
            Auth::attempt(['email' => $user->email, 'password' => 'OldSecret123!']),
        );
        Auth::logout();
    }

    public function test_profile_information_can_be_updated(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => 'Test User',
                'email' => 'test@example.com',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $user->refresh();

        $this->assertSame('Test User', $user->name);
        $this->assertSame('test@example.com', $user->email);
        $this->assertNull($user->email_verified_at);
    }

    public function test_email_verification_status_is_unchanged_when_the_email_address_is_unchanged(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => 'Test User',
                'email' => $user->email,
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $this->assertNotNull($user->refresh()->email_verified_at);
    }

    public function test_user_can_delete_their_account(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->delete('/profile', [
                'password' => 'password',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/');

        $this->assertGuest();
        $this->assertNull($user->fresh());
    }

    public function test_correct_password_must_be_provided_to_delete_account(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from('/profile')
            ->delete('/profile', [
                'password' => 'wrong-password',
            ]);

        $response
            ->assertSessionHasErrors('password')
            ->assertRedirect('/profile');

        $this->assertNotNull($user->fresh());
    }

    public function test_profile_details_can_be_updated(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->patch(route('profile.update'), [
                'name' => 'Updated Name',
                'email' => 'updated@example.com',
            ])
            ->assertRedirect(route('profile.edit'));

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
        ]);
    }
}
