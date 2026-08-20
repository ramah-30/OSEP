<?php

namespace Tests\Feature;

use App\Enums\AccountType;
use App\Enums\PlannerBadge;
use App\Models\Event;
use App\Models\PlannerReview;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PlannerReviewTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RoleSeeder::class, PermissionSeeder::class]);
    }

    private function client(): User
    {
        return User::factory()->accountType(AccountType::Client)->create();
    }

    private function planner(): User
    {
        return User::factory()->accountType(AccountType::EventPlanner)->create();
    }

    private function eventFor(User $client, User $planner): Event
    {
        return Event::create([
            'planner_id' => $planner->id, 'client_id' => $client->id, 'title' => 'Our Event',
            'event_type' => 'Wedding', 'status' => 'completed', 'event_date' => now()->subDay(),
        ]);
    }

    public function test_client_can_submit_a_review_of_their_planner(): void
    {
        $client = $this->client();
        $planner = $this->planner();
        $event = $this->eventFor($client, $planner);

        Sanctum::actingAs($client);

        $this->postJson('/api/v1/planner-reviews', [
            'event_id' => $event->id, 'rating' => 5, 'comment' => 'Fantastic work.',
        ])->assertOk()
            ->assertJsonPath('data.review.rating', 5)
            ->assertJsonPath('data.reputation.reviews_count', 1)
            ->assertJsonPath('data.reputation.rating', 5);

        $this->assertDatabaseHas('planner_reviews', [
            'planner_id' => $planner->id, 'reviewer_id' => $client->id, 'rating' => 5,
        ]);
    }

    public function test_resubmitting_updates_the_existing_review(): void
    {
        $client = $this->client();
        $planner = $this->planner();
        $event = $this->eventFor($client, $planner);

        Sanctum::actingAs($client);

        $this->postJson('/api/v1/planner-reviews', ['event_id' => $event->id, 'rating' => 3])->assertOk();
        $this->postJson('/api/v1/planner-reviews', ['event_id' => $event->id, 'rating' => 5])->assertOk();

        $this->assertDatabaseCount('planner_reviews', 1);
        $this->assertDatabaseHas('planner_reviews', ['planner_id' => $planner->id, 'rating' => 5]);
    }

    public function test_a_client_cannot_review_an_event_that_is_not_theirs(): void
    {
        $planner = $this->planner();
        $event = $this->eventFor($this->client(), $planner);

        Sanctum::actingAs($this->client()); // a different client

        $this->postJson('/api/v1/planner-reviews', ['event_id' => $event->id, 'rating' => 5])
            ->assertNotFound();
    }

    public function test_planner_reviews_page_reports_reputation_and_list(): void
    {
        $planner = $this->planner();
        $planner->forceFill(['email_verified_at' => now()])->save();
        $client = $this->client();
        $event = $this->eventFor($client, $planner);

        PlannerReview::create([
            'planner_id' => $planner->id, 'reviewer_id' => $client->id,
            'event_id' => $event->id, 'rating' => 4, 'comment' => 'Great.',
        ]);

        Sanctum::actingAs($planner);

        $this->getJson('/api/v1/planner/reviews')->assertOk()
            ->assertJsonPath('data.reputation.reviews_count', 1)
            ->assertJsonPath('data.reputation.rating', 4)
            ->assertJsonPath('data.reputation.badge.verified', true)
            ->assertJsonCount(1, 'data.reviews')
            ->assertJsonPath('data.distribution.4', 1);
    }

    public function test_public_planner_page_includes_rating_and_reviews(): void
    {
        $planner = $this->planner();
        $planner->plannerProfile()->create(['booking_slug' => 'jane-planner', 'company_name' => 'Jane Co']);
        $client = $this->client();
        $event = $this->eventFor($client, $planner);

        PlannerReview::create([
            'planner_id' => $planner->id, 'reviewer_id' => $client->id,
            'event_id' => $event->id, 'rating' => 5, 'comment' => 'Loved it.',
        ]);

        $this->getJson('/api/v1/planners/jane-planner')->assertOk()
            ->assertJsonPath('data.planner.rating', 5)
            ->assertJsonPath('data.planner.reviews_count', 1)
            ->assertJsonCount(1, 'data.reviews');
    }

    public function test_badge_derivation_tiers(): void
    {
        // Unverified email → no badge.
        $this->assertSame(PlannerBadge::Unverified, PlannerBadge::derive(false, 10, 10, 5.0, 10));
        // Verified but green → Verified.
        $this->assertSame(PlannerBadge::Verified, PlannerBadge::derive(true, 0, 0, 0.0, 0));
        // Experience or a few completed events → Established.
        $this->assertSame(PlannerBadge::Established, PlannerBadge::derive(true, 3, 0, 0.0, 0));
        // Strong reviews + track record → Top-rated.
        $this->assertSame(PlannerBadge::TopRated, PlannerBadge::derive(true, 5, 6, 4.8, 5));
    }
}
