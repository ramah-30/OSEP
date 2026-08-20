<?php

namespace Tests\Feature\Workspace;

use App\Enums\AccountType;
use App\Models\Event;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RoleSeeder::class, PermissionSeeder::class]);
    }

    public function test_planner_stats_are_computed_from_their_events(): void
    {
        $planner = User::factory()->accountType(AccountType::EventPlanner)->create();
        $client = User::factory()->accountType(AccountType::Client)->create();

        Event::create([
            'planner_id' => $planner->id, 'client_id' => $client->id, 'title' => 'Gala',
            'status' => 'execution', 'progress' => 40, 'budget_total' => 1000, 'budget_spent' => 200,
        ]);
        Event::create([
            'planner_id' => $planner->id, 'client_id' => $client->id, 'title' => 'Done',
            'status' => 'completed', 'progress' => 100, 'budget_total' => 500, 'budget_spent' => 500,
        ]);

        Sanctum::actingAs($planner);

        $this->getJson('/api/v1/dashboard/stats')
            ->assertOk()
            ->assertJsonPath('data.stats.0.key', 'active_events')
            ->assertJsonPath('data.stats.0.value', 1) // one active (execution)
            ->assertJsonPath('data.stats.1.key', 'completed_events')
            ->assertJsonPath('data.stats.1.value', 1) // completed
            ->assertJsonPath('data.stats.2.key', 'revenue')
            ->assertJsonPath('data.stats.2.value', fn ($v) => (float) $v === 1500.0); // revenue = total budgets
    }

    public function test_client_stats_include_their_event_and_pending_approvals(): void
    {
        $planner = User::factory()->accountType(AccountType::EventPlanner)->create();
        $client = User::factory()->accountType(AccountType::Client)->create();

        $event = Event::create([
            'planner_id' => $planner->id, 'client_id' => $client->id, 'title' => 'Wedding',
            'status' => 'execution', 'progress' => 65, 'budget_total' => 1000, 'budget_spent' => 400,
        ]);
        $event->approvals()->create(['title' => 'Decor', 'type' => 'proposal', 'status' => 'pending']);

        Sanctum::actingAs($client);

        $this->getJson('/api/v1/dashboard/stats')
            ->assertOk()
            ->assertJsonPath('data.event.title', 'Wedding')
            ->assertJsonPath('data.stats.1.value', 65) // progress
            ->assertJsonPath('data.stats.2.value', 1); // pending approvals
    }
}
