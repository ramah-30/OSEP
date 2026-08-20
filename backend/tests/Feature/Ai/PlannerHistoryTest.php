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

class PlannerHistoryTest extends TestCase
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

    /** A delivered wedding with a given catering / venue split. */
    private function deliveredWedding(User $planner, int $catering, int $venue): Event
    {
        $event = Event::create([
            'planner_id' => $planner->id, 'title' => 'Past wedding', 'event_type' => 'Wedding',
            'status' => 'completed',
        ]);
        $event->budgetItems()->create(['category' => 'Catering', 'description' => 'Food', 'estimated_cost' => $catering, 'status' => 'paid', 'actual_cost' => $catering]);
        $event->budgetItems()->create(['category' => 'Venue', 'description' => 'Hall', 'estimated_cost' => $venue, 'status' => 'paid', 'actual_cost' => $venue]);

        return $event;
    }

    public function test_benchmark_reports_typical_category_split_from_delivered_events(): void
    {
        $planner = $this->planner();
        Sanctum::actingAs($planner);

        // Two delivered weddings averaging ~60% catering / 40% venue.
        $this->deliveredWedding($planner, catering: 6_000_000, venue: 4_000_000);
        $this->deliveredWedding($planner, catering: 6_000_000, venue: 4_000_000);

        $res = $this->getJson('/api/v1/ai/benchmarks')->assertOk();

        $res->assertJsonPath('data.has_history', true);
        $categories = collect($res->json('data.budget.categories'))->keyBy('name');
        $this->assertSame(60, $categories['Catering']['pct']);
        $this->assertSame(40, $categories['Venue']['pct']);
        $this->assertSame(2, $res->json('data.budget.sample_events'));
    }

    public function test_insights_flags_a_budget_split_anomaly_against_history(): void
    {
        $planner = $this->planner();
        Sanctum::actingAs($planner);

        // History: catering is normally ~60%.
        $this->deliveredWedding($planner, catering: 6_000_000, venue: 4_000_000);

        // Live event: catering is only 20% — a sharp divergence.
        $live = Event::create([
            'planner_id' => $planner->id, 'title' => 'New wedding', 'event_type' => 'Wedding', 'status' => 'planning',
        ]);
        $live->budgetItems()->create(['category' => 'Catering', 'description' => 'Food', 'estimated_cost' => 2_000_000, 'status' => 'planned']);
        $live->budgetItems()->create(['category' => 'Venue', 'description' => 'Hall', 'estimated_cost' => 8_000_000, 'status' => 'planned']);

        $res = $this->getJson("/api/v1/ai/insights?event_id={$live->id}")->assertOk();

        $anomalies = collect($res->json('data.benchmark.anomalies'))->keyBy('name');
        $this->assertTrue($anomalies->has('Catering'));
        $this->assertSame('under', $anomalies['Catering']['direction']);
        $this->assertSame(20, $anomalies['Catering']['event_pct']);
        $this->assertSame(60, $anomalies['Catering']['benchmark_pct']);
    }

    public function test_quote_flag_detects_a_vendor_price_above_the_planner_norm(): void
    {
        $planner = $this->planner();
        Sanctum::actingAs($planner);

        // Two past photographer bookings around 1,000,000.
        foreach ([1_000_000, 1_000_000] as $i => $price) {
            $past = Event::create(['planner_id' => $planner->id, 'title' => "Past {$i}", 'status' => 'completed']);
            $past->vendorAssignments()->create(['vendor_name' => "Snap {$i}", 'service' => 'Photography', 'price' => $price, 'status' => 'completed']);
        }

        // Live event quotes a photographer at 2,000,000 (+100%).
        $live = Event::create(['planner_id' => $planner->id, 'title' => 'Live', 'status' => 'planning']);
        $live->vendorAssignments()->create(['vendor_name' => 'Pricey Pics', 'service' => 'Photography', 'price' => 2_000_000, 'status' => 'requested']);

        $res = $this->getJson("/api/v1/ai/insights?event_id={$live->id}")->assertOk();

        $flags = $res->json('data.quote_flags');
        $this->assertNotEmpty($flags);
        $this->assertSame('Pricey Pics', $flags[0]['name']);
        $this->assertSame(100, $flags[0]['delta_pct']);
    }

    public function test_vendor_scorecard_computes_reliability_and_top_vendor(): void
    {
        $planner = $this->planner();
        Sanctum::actingAs($planner);

        $e = Event::create(['planner_id' => $planner->id, 'title' => 'E', 'status' => 'completed']);
        // 3 catering bookings: 2 completed (good), 1 declined (bad) → 67% reliable.
        $e->vendorAssignments()->create(['vendor_name' => 'Tasty', 'service' => 'Catering', 'price' => 500_000, 'status' => 'completed']);
        $e->vendorAssignments()->create(['vendor_name' => 'Tasty', 'service' => 'Catering', 'price' => 700_000, 'status' => 'accepted']);
        $e->vendorAssignments()->create(['vendor_name' => 'Bland', 'service' => 'Catering', 'price' => 600_000, 'status' => 'declined']);

        $res = $this->getJson('/api/v1/ai/benchmarks')->assertOk();

        $catering = collect($res->json('data.vendors'))->firstWhere('service', 'Catering');
        $this->assertSame(3, $catering['uses']);
        $this->assertSame(67, $catering['reliability_pct']);
        $this->assertSame('Tasty', $catering['top_vendor']);
    }

    public function test_no_history_returns_empty_benchmarks_gracefully(): void
    {
        $planner = $this->planner();
        Sanctum::actingAs($planner);

        $res = $this->getJson('/api/v1/ai/benchmarks')->assertOk();

        $res->assertJsonPath('data.has_history', false)
            ->assertJsonPath('data.budget', null)
            ->assertJsonPath('data.vendors', []);
    }
}
