<?php

namespace Tests\Feature\Auth;

use App\Enums\AuthEvent;
use App\Models\User;
use App\Notifications\ResetPasswordNotification;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    public function test_it_emails_a_reset_link(): void
    {
        Notification::fake();

        $user = User::factory()->create(['email' => 'planner@example.com']);

        $this->postJson('/api/v1/auth/forgot-password', ['email' => 'planner@example.com'])
            ->assertOk()
            ->assertJsonPath('success', true);

        Notification::assertSentTo($user, ResetPasswordNotification::class);

        $this->assertDatabaseHas('auth_audit_logs', [
            'email' => 'planner@example.com',
            'event' => AuthEvent::PasswordResetRequested->value,
        ]);
    }

    public function test_an_unknown_address_is_rejected_before_any_mail_is_sent(): void
    {
        Notification::fake();

        $this->postJson('/api/v1/auth/forgot-password', ['email' => 'ghost@example.com'])
            ->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('errors.email.0', 'We could not find an account with that email address.');

        Notification::assertNothingSent();

        $this->assertDatabaseHas('auth_audit_logs', [
            'email' => 'ghost@example.com',
            'event' => AuthEvent::PasswordResetRequested->value,
        ]);
    }

    public function test_a_valid_token_updates_the_password_and_revokes_existing_sessions(): void
    {
        $user = User::factory()->create(['email' => 'planner@example.com']);

        $existingToken = $this->postJson('/api/v1/auth/login', [
            'email' => 'planner@example.com',
            'password' => 'Password123!',
        ])->json('data.token');

        $token = null;
        Notification::fake();
        $this->postJson('/api/v1/auth/forgot-password', ['email' => 'planner@example.com']);
        Notification::assertSentTo($user, ResetPasswordNotification::class, function ($notification) use (&$token) {
            $token = $notification->token;

            return true;
        });

        $this->postJson('/api/v1/auth/reset-password', [
            'token' => $token,
            'email' => 'planner@example.com',
            'password' => 'Brand!NewPass9',
            'password_confirmation' => 'Brand!NewPass9',
        ])->assertOk()->assertJsonPath('success', true);

        $this->assertTrue(Hash::check('Brand!NewPass9', $user->fresh()->password));

        // Sessions opened with the old password must not survive the reset.
        $this->withToken($existingToken)->getJson('/api/v1/auth/me')->assertStatus(401);

        $this->postJson('/api/v1/auth/login', [
            'email' => 'planner@example.com',
            'password' => 'Brand!NewPass9',
        ])->assertOk();
    }

    public function test_an_invalid_token_is_rejected(): void
    {
        User::factory()->create(['email' => 'planner@example.com']);

        $this->postJson('/api/v1/auth/reset-password', [
            'token' => 'not-a-real-token',
            'email' => 'planner@example.com',
            'password' => 'Brand!NewPass9',
            'password_confirmation' => 'Brand!NewPass9',
        ])
            ->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonStructure(['errors' => ['token']]);
    }
}
