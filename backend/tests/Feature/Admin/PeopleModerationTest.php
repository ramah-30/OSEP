<?php

namespace Tests\Feature\Admin;

use App\Enums\AccountType;
use App\Enums\UserStatus;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PeopleModerationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RoleSeeder::class, PermissionSeeder::class]);
    }

    private function admin(): User
    {
        $admin = User::factory()->accountType(AccountType::Admin)->create();
        $admin->assignRole('admin');

        return $admin;
    }

    private function planner(): User
    {
        $planner = User::factory()->accountType(AccountType::EventPlanner)->create();
        $planner->plannerProfile()->create([]);

        return $planner;
    }

    private function client(): User
    {
        $client = User::factory()->accountType(AccountType::Client)->create();
        $client->clientProfile()->create([]);

        return $client;
    }

    public function test_admin_can_list_planners(): void
    {
        $this->planner();
        Sanctum::actingAs($this->admin());

        $this->getJson('/api/v1/admin/planners')
            ->assertOk()
            ->assertJsonStructure(['data' => ['planners' => [['id', 'full_name', 'is_verified', 'is_suspended', 'events_count']], 'meta']]);
    }

    public function test_admin_can_verify_and_unverify_a_planner(): void
    {
        $planner = $this->planner();
        Sanctum::actingAs($this->admin());

        $this->putJson("/api/v1/admin/planners/{$planner->id}/verify", ['verified' => true])
            ->assertOk()
            ->assertJsonPath('data.planner.is_verified', true);

        $this->assertNotNull($planner->plannerProfile->refresh()->verified_at);

        $this->putJson("/api/v1/admin/planners/{$planner->id}/verify", ['verified' => false])
            ->assertOk()
            ->assertJsonPath('data.planner.is_verified', false);

        $this->assertNull($planner->plannerProfile->refresh()->verified_at);
    }

    public function test_admin_can_suspend_a_planner(): void
    {
        $planner = $this->planner();
        Sanctum::actingAs($this->admin());

        $this->putJson("/api/v1/admin/planners/{$planner->id}/suspend", ['suspended' => true])
            ->assertOk()
            ->assertJsonPath('data.planner.is_suspended', true);

        $this->assertSame(UserStatus::Suspended, $planner->refresh()->status);
    }

    public function test_admin_can_list_and_verify_a_client(): void
    {
        $client = $this->client();
        Sanctum::actingAs($this->admin());

        $this->getJson('/api/v1/admin/clients')->assertOk();

        $this->putJson("/api/v1/admin/clients/{$client->id}/verify", ['verified' => true])
            ->assertOk()
            ->assertJsonPath('data.client.is_verified', true);
    }

    public function test_a_planner_endpoint_rejects_a_client_id(): void
    {
        $client = $this->client();
        Sanctum::actingAs($this->admin());

        // The planner route must not moderate a client account.
        $this->putJson("/api/v1/admin/planners/{$client->id}/verify", ['verified' => true])
            ->assertNotFound();
    }

    public function test_non_admins_cannot_reach_people_moderation(): void
    {
        Sanctum::actingAs($this->planner());

        $this->getJson('/api/v1/admin/planners')->assertForbidden();
        $this->getJson('/api/v1/admin/clients')->assertForbidden();
    }
}
