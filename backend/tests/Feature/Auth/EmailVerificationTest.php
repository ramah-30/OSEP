<?php

namespace Tests\Feature\Auth;

use App\Enums\AuthEvent;
use App\Enums\UserStatus;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    private function signedUrlFor(User $user, ?string $hash = null): string
    {
        return URL::temporarySignedRoute('verification.verify', now()->addHour(), [
            'id' => $user->id,
            'hash' => $hash ?? sha1($user->email),
        ]);
    }

    public function test_a_signed_link_verifies_the_email_and_activates_the_account(): void
    {
        $user = User::factory()->unverified()->create();

        $this->get($this->signedUrlFor($user))
            ->assertRedirectContains('/verify-email/callback?status=verified');

        $user->refresh();

        $this->assertNotNull($user->email_verified_at);
        $this->assertSame(UserStatus::Active, $user->status);
        $this->assertDatabaseHas('auth_audit_logs', [
            'user_id' => $user->id,
            'event' => AuthEvent::EmailVerified->value,
        ]);
    }

    public function test_a_tampered_hash_does_not_verify_the_account(): void
    {
        $user = User::factory()->unverified()->create();

        $this->get($this->signedUrlFor($user, sha1('someone-else@example.com')))
            ->assertRedirectContains('status=invalid');

        $this->assertNull($user->fresh()->email_verified_at);
    }

    public function test_an_unsigned_link_is_rejected(): void
    {
        $user = User::factory()->unverified()->create();

        $this->getJson("/api/v1/auth/verify-email/{$user->id}/".sha1($user->email))
            ->assertStatus(403);

        $this->assertNull($user->fresh()->email_verified_at);
    }

    public function test_verifying_twice_reports_already_verified(): void
    {
        $user = User::factory()->create();

        $this->get($this->signedUrlFor($user))
            ->assertRedirectContains('status=already-verified');
    }

    public function test_resend_answers_identically_for_unknown_addresses(): void
    {
        $user = User::factory()->unverified()->create(['email' => 'pending@example.com']);

        $known = $this->postJson('/api/v1/auth/resend-verification', ['email' => 'pending@example.com']);
        $unknown = $this->postJson('/api/v1/auth/resend-verification', ['email' => 'ghost@example.com']);

        $known->assertOk();
        $unknown->assertOk();
        $this->assertSame($known->json('message'), $unknown->json('message'));
    }
}
