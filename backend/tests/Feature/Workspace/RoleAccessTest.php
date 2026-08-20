<?php

namespace Tests\Feature\Workspace;

use App\Enums\AccountType;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class RoleAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RoleSeeder::class, PermissionSeeder::class]);
    }

    public function test_a_planner_cannot_reach_the_client_only_routes(): void
    {
        Sanctum::actingAs(User::factory()->accountType(AccountType::EventPlanner)->create());

        $this->getJson('/api/v1/my-event')
            ->assertStatus(403)
            ->assertJsonPath('success', false);
    }

    public function test_a_client_can_reach_the_client_routes(): void
    {
        Sanctum::actingAs(User::factory()->accountType(AccountType::Client)->create());

        $this->getJson('/api/v1/my-event')->assertOk();
    }

    public function test_the_workspace_requires_authentication(): void
    {
        $this->getJson('/api/v1/dashboard/stats')->assertStatus(401);
    }
}
