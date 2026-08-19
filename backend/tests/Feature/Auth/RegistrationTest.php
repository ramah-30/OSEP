<?php

namespace Tests\Feature\Auth;

use App\Enums\AccountType;
use App\Enums\UserStatus;
use App\Models\User;
use App\Notifications\VerifyEmailNotification;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'first_name' => 'Amara',
            'last_name' => 'Okafor',
            'email' => 'amara@example.com',
            'phone' => '+15551234567',
            'password' => 'Str0ng!Passw0rd',
            'password_confirmation' => 'Str0ng!Passw0rd',
            'account_type' => AccountType::EventPlanner->value,
            'country' => 'Nigeria',
            'terms' => true,
        ], $overrides);
    }

    public function test_it_registers_a_planner_and_attaches_the_matching_role(): void
    {
        Notification::fake();

        $response = $this->postJson('/api/v1/auth/register', $this->payload());

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.user.account_type', 'event_planner')
            ->assertJsonPath('data.user.email_verified', false)
            ->assertJsonPath('data.user.status', 'pending')
            ->assertJsonPath('data.user.dashboard_path', '/dashboard/planner')
            ->assertJsonStructure(['success', 'message', 'data' => ['user', 'token'], 'errors']);

        $user = User::firstWhere('email', 'amara@example.com');

        $this->assertNotNull($user);
        $this->assertTrue($user->hasRole('event_planner'));
        $this->assertSame(UserStatus::Pending, $user->status);
        $this->assertNotSame('Str0ng!Passw0rd', $user->password);

        Notification::assertSentTo($user, VerifyEmailNotification::class);
    }

    public function test_it_registers_vendors_and_clients_with_their_own_roles(): void
    {
        Notification::fake();

        foreach ([AccountType::Vendor, AccountType::Client] as $type) {
            $overrides = [
                'email' => $type->value.'@example.com',
                'account_type' => $type->value,
            ];

            if ($type === AccountType::Vendor) {
                $overrides['category_name'] = 'Photographers';
            }

            $this->postJson('/api/v1/auth/register', $this->payload($overrides))
                ->assertCreated();

            $user = User::firstWhere('email', $type->value.'@example.com');

            $this->assertTrue($user->hasRole($type->value));
        }
    }

    public function test_a_duplicate_email_is_rejected_in_the_standard_envelope(): void
    {
        Notification::fake();

        User::factory()->create(['email' => 'amara@example.com']);

        $this->postJson('/api/v1/auth/register', $this->payload())
            ->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('data', null)
            ->assertJsonStructure(['success', 'message', 'data', 'errors' => ['email']]);
    }

    public function test_it_rejects_weak_passwords_unknown_account_types_and_unaccepted_terms(): void
    {
        $this->postJson('/api/v1/auth/register', $this->payload([
            'password' => 'password',
            'password_confirmation' => 'password',
            'account_type' => 'superuser',
            'terms' => false,
        ]))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['password', 'account_type', 'terms']);
    }

    public function test_the_response_never_leaks_the_password_hash(): void
    {
        Notification::fake();

        $response = $this->postJson('/api/v1/auth/register', $this->payload());

        $this->assertArrayNotHasKey('password', $response->json('data.user'));
    }
}
