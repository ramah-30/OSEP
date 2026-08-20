<?php

namespace Tests\Feature\Auth;

use App\Enums\AuthEvent;
use App\Models\AuthAuditLog;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    public function test_a_verified_user_can_sign_in_and_receives_a_bearer_token(): void
    {
        $user = User::factory()->create(['email' => 'planner@example.com']);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'planner@example.com',
            'password' => 'Password123!',
            'remember' => true,
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.token_type', 'Bearer')
            ->assertJsonPath('data.user.email', 'planner@example.com');

        $this->assertNotEmpty($response->json('data.token'));
        $this->assertDatabaseHas('auth_audit_logs', [
            'user_id' => $user->id,
            'event' => AuthEvent::LoginSuccess->value,
        ]);
    }

    private const GENERIC_FAILURE = 'Wrong credentials, try again.';

    public function test_bad_credentials_return_a_generic_401_and_record_a_failed_attempt(): void
    {
        User::factory()->create(['email' => 'planner@example.com']);

        $this->postJson('/api/v1/auth/login', [
            'email' => 'planner@example.com',
            'password' => 'WrongPassword1!',
        ])
            ->assertStatus(401)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', self::GENERIC_FAILURE)
            // No field errors: the message belongs above the form, not under a
            // field, and must not point at the email or the password.
            ->assertJsonPath('errors', null);

        $this->assertDatabaseHas('auth_audit_logs', [
            'email' => 'planner@example.com',
            'event' => AuthEvent::LoginFailed->value,
        ]);
    }

    public function test_an_unknown_email_and_a_wrong_password_are_indistinguishable(): void
    {
        User::factory()->create(['email' => 'planner@example.com']);

        $wrongPassword = $this->postJson('/api/v1/auth/login', [
            'email' => 'planner@example.com',
            'password' => 'WrongPassword1!',
        ]);

        $unknownEmail = $this->postJson('/api/v1/auth/login', [
            'email' => 'nobody@example.com',
            'password' => 'WrongPassword1!',
        ]);

        $this->assertSame($wrongPassword->status(), $unknownEmail->status());
        $this->assertSame($wrongPassword->json('message'), $unknownEmail->json('message'));
        $this->assertSame($wrongPassword->json('errors'), $unknownEmail->json('errors'));

        // The distinction survives where it is actually useful.
        $this->assertDatabaseHas('auth_audit_logs', [
            'email' => 'nobody@example.com',
            'event' => AuthEvent::LoginFailed->value,
        ]);
    }

    public function test_an_unknown_email_is_still_audited(): void
    {
        $this->postJson('/api/v1/auth/login', [
            'email' => 'ghost@example.com',
            'password' => 'WrongPassword1!',
        ])->assertStatus(401);

        $log = AuthAuditLog::firstWhere('email', 'ghost@example.com');

        $this->assertNotNull($log);
        $this->assertNull($log->user_id);
        $this->assertSame(AuthEvent::LoginFailed->value, $log->event);
    }

    public function test_a_suspended_account_cannot_sign_in(): void
    {
        User::factory()->suspended()->create(['email' => 'blocked@example.com']);

        $this->postJson('/api/v1/auth/login', [
            'email' => 'blocked@example.com',
            'password' => 'Password123!',
        ])
            ->assertStatus(401)
            ->assertJsonPath('message', 'This account has been suspended. Please contact support.');

        $this->assertDatabaseHas('auth_audit_logs', [
            'email' => 'blocked@example.com',
            'event' => AuthEvent::LoginBlocked->value,
        ]);
    }

    public function test_an_account_with_no_password_set_gets_the_same_generic_answer(): void
    {
        User::factory()->create([
            'email' => 'no-password@example.com',
            'password' => null,
        ]);

        $this->postJson('/api/v1/auth/login', [
            'email' => 'no-password@example.com',
            'password' => 'Password123!',
        ])
            ->assertStatus(401)
            ->assertJsonPath('message', self::GENERIC_FAILURE);

        $log = \App\Models\AuthAuditLog::firstWhere('email', 'no-password@example.com');

        $this->assertSame('password_not_set', $log->metadata['reason']);
    }

    public function test_the_sixth_attempt_within_a_minute_is_throttled(): void
    {
        User::factory()->create(['email' => 'planner@example.com']);

        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/v1/auth/login', [
                'email' => 'planner@example.com',
                'password' => 'WrongPassword1!',
            ])->assertStatus(401);
        }

        $this->postJson('/api/v1/auth/login', [
            'email' => 'planner@example.com',
            'password' => 'WrongPassword1!',
        ])
            ->assertStatus(429)
            ->assertJsonPath('success', false);
    }

    public function test_me_requires_a_token_and_returns_the_user_with_roles(): void
    {
        $this->getJson('/api/v1/auth/me')
            ->assertStatus(401)
            ->assertJsonPath('success', false);

        $user = User::factory()->create(['email' => 'planner@example.com']);
        $user->assignRole('event_planner');

        $token = $this->postJson('/api/v1/auth/login', [
            'email' => 'planner@example.com',
            'password' => 'Password123!',
        ])->json('data.token');

        $this->withToken($token)->getJson('/api/v1/auth/me')
            ->assertOk()
            ->assertJsonPath('data.user.email', 'planner@example.com')
            ->assertJsonPath('data.user.roles', ['event_planner']);
    }

    public function test_logout_revokes_only_the_current_token(): void
    {
        User::factory()->create(['email' => 'planner@example.com']);

        $token = $this->postJson('/api/v1/auth/login', [
            'email' => 'planner@example.com',
            'password' => 'Password123!',
        ])->json('data.token');

        $this->withToken($token)->postJson('/api/v1/auth/logout')->assertOk();

        $this->assertDatabaseCount('personal_access_tokens', 0);

        // The guard caches the user it resolved during the logout call; a real
        // request would start clean, so drop the cache before re-checking.
        $this->app['auth']->forgetGuards();

        $this->withToken($token)->getJson('/api/v1/auth/me')->assertStatus(401);
    }
}
