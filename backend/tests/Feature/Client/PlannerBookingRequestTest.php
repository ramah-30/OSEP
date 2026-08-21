<?php

namespace Tests\Feature\Client;

use App\Enums\AccountType;
use App\Models\Event;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PlannerBookingRequestTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RoleSeeder::class, PermissionSeeder::class]);
    }

    public function test_client_proposes_a_budget_and_the_planners_quote_seeds_the_new_events_budget(): void
    {
        $planner = User::factory()->accountType(AccountType::EventPlanner)->create();
        $client = User::factory()->accountType(AccountType::Client)->create();

        Sanctum::actingAs($client);
        $requestId = $this->postJson('/api/v1/booking-requests', [
            'planner_id' => $planner->id,
            'event_type' => 'wedding',
            'proposed_budget' => 5_000_000,
        ])->assertCreated()
            ->assertJsonPath('data.request.proposed_budget', 5000000)
            ->json('data.request.id');

        Sanctum::actingAs($planner);
        $response = $this->postJson("/api/v1/planner-booking-requests/{$requestId}/respond", [
            'decision' => 'accepted',
            'quoted_budget' => 4_500_000,
        ])->assertOk();

        $response->assertJsonPath('data.request.quoted_budget', 4500000);
        $eventId = $response->json('data.request.event_id');
        $this->assertNotNull($eventId);

        $this->assertDatabaseHas('budgets', [
            'event_id' => $eventId,
            'estimated_total' => 4500000,
        ]);

        // Event.budget_total is the denormalized figure every other screen
        // (dashboard, workspace overview, the client's own Budget tab) reads -
        // it must be kept in sync with the Budget row, not just the row itself.
        $this->assertSame(4500000.0, (float) Event::findOrFail($eventId)->budget_total);

        $this->getJson("/api/v1/events/{$eventId}")
            ->assertJsonPath('data.event.budget.total', 4500000);
    }

    public function test_accepting_without_a_quote_creates_no_budget(): void
    {
        $planner = User::factory()->accountType(AccountType::EventPlanner)->create();
        $client = User::factory()->accountType(AccountType::Client)->create();

        Sanctum::actingAs($client);
        $requestId = $this->postJson('/api/v1/booking-requests', [
            'planner_id' => $planner->id,
            'event_type' => 'wedding',
        ])->assertCreated()->json('data.request.id');

        Sanctum::actingAs($planner);
        $response = $this->postJson("/api/v1/planner-booking-requests/{$requestId}/respond", [
            'decision' => 'accepted',
        ])->assertOk();

        $eventId = $response->json('data.request.event_id');
        $this->assertDatabaseMissing('budgets', ['event_id' => $eventId]);
    }
}
