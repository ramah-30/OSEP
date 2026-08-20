<?php

namespace Tests\Feature\Ai;

use App\Enums\AccountType;
use App\Models\Event;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ScenarioTest extends TestCase
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

    /** An event with a catering line, an expected headcount and a venue capacity. */
    private function cateringEvent(User $planner, int $expected = 100, int $capacity = 150): Event
    {
        $event = Event::create([
            'planner_id' => $planner->id,
            'title' => 'Gala',
            'status' => 'planning',
            'expected_guests' => $expected,
            'budget_total' => 10_000_000,
            'budget_spent' => 0,
        ]);
        $event->budgetItems()->create([
            'category' => 'Catering',
            'description' => 'Plated dinner',
            'estimated_cost' => 5_000_000,
            'status' => 'planned',
        ]);
        $event->venueDetail()->create(['name' => 'Grand Hall', 'capacity' => $capacity]);

        return $event;
    }

    public function test_scenario_projects_catering_tables_and_capacity(): void
    {
        $planner = $this->planner();
        Sanctum::actingAs($planner);
        $event = $this->cateringEvent($planner);

        $res = $this->getJson("/api/v1/ai/scenario?event_id={$event->id}&guests_delta=20&seats_per_table=10")
            ->assertOk();

        // 5,000,000 catering ÷ 100 expected = 50,000/head.
        $res->assertJsonPath('data.baseline.per_head', 50000)
            ->assertJsonPath('data.baseline.per_head_basis', 'catering line ÷ current headcount')
            ->assertJsonPath('data.projection.new_guests', 120)
            ->assertJsonPath('data.projection.added_cost', 1000000)
            ->assertJsonPath('data.projection.tables_needed', 12)   // ceil(120/10)
            ->assertJsonPath('data.projection.tables_delta', 2)     // was ceil(100/10)=10
            ->assertJsonPath('data.projection.capacity_ok', true);
    }

    public function test_scenario_flags_going_over_venue_capacity(): void
    {
        $planner = $this->planner();
        Sanctum::actingAs($planner);
        $event = $this->cateringEvent($planner, expected: 100, capacity: 150);

        $this->getJson("/api/v1/ai/scenario?event_id={$event->id}&guests_delta=100")
            ->assertOk()
            ->assertJsonPath('data.projection.new_guests', 200)
            ->assertJsonPath('data.projection.capacity_ok', false)
            ->assertJsonPath('data.projection.over_capacity_by', 50);
    }

    public function test_scenario_rolls_up_meal_quantities_by_current_mix(): void
    {
        $planner = $this->planner();
        Sanctum::actingAs($planner);

        $event = Event::create([
            'planner_id' => $planner->id, 'title' => 'Dinner', 'status' => 'planning',
            'expected_guests' => 3, 'budget_total' => 600_000, 'budget_spent' => 0,
        ]);
        $event->budgetItems()->create(['category' => 'Catering', 'description' => 'Food', 'estimated_cost' => 300_000, 'status' => 'planned']);
        $event->guests()->create(['full_name' => 'A', 'email' => 'a@ex.com', 'rsvp_status' => 'confirmed', 'meal_preference' => 'Chicken']);
        $event->guests()->create(['full_name' => 'B', 'email' => 'b@ex.com', 'rsvp_status' => 'confirmed', 'meal_preference' => 'Chicken']);
        $event->guests()->create(['full_name' => 'C', 'email' => 'c@ex.com', 'rsvp_status' => 'confirmed', 'meal_preference' => 'Vegetarian']);

        // Current headcount = 3 (real list). +3 guests → 6 total.
        $res = $this->getJson("/api/v1/ai/scenario?event_id={$event->id}&guests_delta=3")
            ->assertOk();

        $rollup = collect($res->json('data.projection.meal_rollup'))->keyBy('name');
        $this->assertSame(4, $rollup['Chicken']['count']);      // round(6 * 2/3)
        $this->assertSame(2, $rollup['Vegetarian']['count']);   // remainder
        $this->assertSame(6, $rollup->sum('count'));
    }

    public function test_offline_chat_answers_a_what_if_question(): void
    {
        $planner = $this->planner();
        Sanctum::actingAs($planner);
        $event = $this->cateringEvent($planner);

        $res = $this->postJson('/api/v1/ai/chat', [
            'message' => 'What if 20 more guests confirm?',
            'event_id' => $event->id,
        ])->assertOk();

        $content = $res->json('data.message.content');
        $this->assertStringContainsString('What-if', $content);
        $this->assertStringContainsString('Catering impact', $content);
    }
}
