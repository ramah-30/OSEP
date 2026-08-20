<?php

namespace Tests\Feature\Workspace;

use App\Enums\AccountType;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RoleSeeder::class, PermissionSeeder::class]);
    }

    public function test_a_user_can_update_account_details(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->putJson('/api/v1/settings/account', [
            'first_name' => 'Sarah',
            'last_name' => 'Bennett',
            'phone' => '+255700000000',
        ])->assertOk()->assertJsonPath('data.user.first_name', 'Sarah');
    }

    public function test_password_change_requires_the_correct_current_password(): void
    {
        $user = User::factory()->create(['password' => Hash::make('Password123!')]);
        Sanctum::actingAs($user);

        $this->putJson('/api/v1/settings/password', [
            'current_password' => 'wrong-password',
            'password' => 'NewStr0ng!Pass',
            'password_confirmation' => 'NewStr0ng!Pass',
        ])->assertStatus(422)->assertJsonValidationErrors(['current_password']);
    }

    public function test_a_user_can_change_their_password(): void
    {
        $user = User::factory()->create(['password' => Hash::make('Password123!')]);
        Sanctum::actingAs($user);

        $this->putJson('/api/v1/settings/password', [
            'current_password' => 'Password123!',
            'password' => 'NewStr0ng!Pass',
            'password_confirmation' => 'NewStr0ng!Pass',
        ])->assertOk();

        $this->assertTrue(Hash::check('NewStr0ng!Pass', $user->refresh()->password));
    }

    public function test_changing_email_resets_verification(): void
    {
        $user = User::factory()->create(['password' => Hash::make('Password123!')]);
        Sanctum::actingAs($user);

        $this->putJson('/api/v1/settings/email', [
            'email' => 'new@example.com',
            'current_password' => 'Password123!',
        ])->assertOk()->assertJsonPath('data.user.email_verified', false);

        $this->assertSame('new@example.com', $user->refresh()->email);
        $this->assertNull($user->email_verified_at);
    }

    public function test_a_user_can_save_preferences(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->putJson('/api/v1/settings/preferences', [
            'locale' => 'sw',
            'timezone' => 'Africa/Dar_es_Salaam',
            'theme' => 'dark',
        ])->assertOk()->assertJsonPath('data.user.preferences.theme', 'dark');
    }

    public function test_a_user_can_delete_their_own_account_with_the_confirmation_phrase(): void
    {
        $user = User::factory()->accountType(AccountType::Client)->create();
        Sanctum::actingAs($user);

        $this->deleteJson('/api/v1/settings/account', [
            'confirmation' => 'delete my account',
        ])->assertOk();

        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }

    public function test_deleting_an_account_requires_the_exact_confirmation_phrase(): void
    {
        $user = User::factory()->accountType(AccountType::Client)->create();
        Sanctum::actingAs($user);

        $this->deleteJson('/api/v1/settings/account', [
            'confirmation' => 'delete',
        ])->assertStatus(422)->assertJsonValidationErrors(['confirmation']);

        $this->assertDatabaseHas('users', ['id' => $user->id]);
    }

    public function test_admins_cannot_delete_their_account_from_settings(): void
    {
        $admin = User::factory()->accountType(AccountType::Admin)->create();
        Sanctum::actingAs($admin);

        $this->deleteJson('/api/v1/settings/account', [
            'confirmation' => 'delete my account',
        ])->assertStatus(403);

        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }
}
