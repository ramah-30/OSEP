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

class EventEngineTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RoleSeeder::class, PermissionSeeder::class]);
    }

    private function planner(): User
    {
        return User::factory()->accountType(AccountType::EventPlanner)->create();
    }

    public function test_planner_can_create_an_event_with_a_generated_code(): void
    {
        Sanctum::actingAs($planner = $this->planner());

        $this->postJson('/api/v1/events', [
            'title' => 'Beach Wedding',
            'event_type' => 'Wedding',
            'priority' => 'high',
        ])
            ->assertCreated()
            ->assertJsonPath('data.event.title', 'Beach Wedding')
            ->assertJsonPath('data.event.priority', 'high');

        $this->assertDatabaseHas('events', ['planner_id' => $planner->id, 'title' => 'Beach Wedding']);
        $this->assertNotNull(Event::first()->event_code);
    }

    public function test_a_planner_cannot_view_another_planners_event(): void
    {
        $owner = $this->planner();
        $event = Event::create(['planner_id' => $owner->id, 'title' => 'Private', 'status' => 'planning']);

        Sanctum::actingAs($this->planner());

        $this->getJson("/api/v1/events/{$event->id}")->assertNotFound();
    }

    public function test_clients_cannot_reach_the_planner_event_engine(): void
    {
        Sanctum::actingAs(User::factory()->accountType(AccountType::Client)->create());

        $this->getJson('/api/v1/events')->assertForbidden();
    }

    public function test_completing_tasks_recalculates_progress(): void
    {
        Sanctum::actingAs($planner = $this->planner());
        $event = Event::create(['planner_id' => $planner->id, 'title' => 'Party', 'status' => 'planning']);

        $t1 = $event->tasks()->create(['title' => 'A', 'status' => 'not_started', 'priority' => 'medium', 'position' => 0]);
        $event->tasks()->create(['title' => 'B', 'status' => 'not_started', 'priority' => 'medium', 'position' => 1]);

        $this->putJson("/api/v1/events/{$event->id}/tasks/{$t1->id}", ['status' => 'completed'])
            ->assertOk()
            ->assertJsonPath('data.task.status', 'completed');

        $this->assertSame(50, $event->refresh()->progress);
    }

    public function test_budget_item_updates_spent_from_all_line_items(): void
    {
        Sanctum::actingAs($planner = $this->planner());
        $event = Event::create(['planner_id' => $planner->id, 'title' => 'Party', 'status' => 'planning', 'budget_total' => 1000]);

        $this->postJson("/api/v1/events/{$event->id}/budget-items", [
            'category' => 'Venue', 'description' => 'Hall', 'estimated_cost' => 500, 'actual_cost' => 450, 'status' => 'paid',
        ])->assertCreated();

        // A still-planned line with an actual cost must count too, so the client's
        // budget overview mirrors the planner's "Actual spend" figure.
        $this->postJson("/api/v1/events/{$event->id}/budget-items", [
            'category' => 'Catering', 'description' => 'Buffet', 'estimated_cost' => 300, 'actual_cost' => 200, 'status' => 'planned',
        ])->assertCreated();

        $this->assertSame('650.00', $event->refresh()->budget_spent);
    }

    public function test_submitting_an_approval_records_history_and_notifies_the_client(): void
    {
        Sanctum::actingAs($planner = $this->planner());
        $client = User::factory()->accountType(AccountType::Client)->create();
        $event = Event::create(['planner_id' => $planner->id, 'client_id' => $client->id, 'title' => 'Party', 'status' => 'planning']);

        $this->postJson("/api/v1/events/{$event->id}/approvals", [
            'title' => 'Decor', 'type' => 'decoration', 'description' => 'Blush palette',
        ])->assertCreated();

        $this->assertDatabaseHas('approval_history', ['action' => 'submitted']);
        $this->assertDatabaseHas('notifications', ['user_id' => $client->id, 'type' => 'approval_request']);
    }

    public function test_invalid_status_transition_is_rejected(): void
    {
        Sanctum::actingAs($planner = $this->planner());
        $event = Event::create(['planner_id' => $planner->id, 'title' => 'Party', 'status' => 'draft']);

        // Draft cannot jump straight to Completed.
        $this->putJson("/api/v1/events/{$event->id}/status", ['status' => 'completed'])
            ->assertStatus(422);
    }
}
