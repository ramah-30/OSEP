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

class OnboardingCoachTest extends TestCase
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

    public function test_new_planner_sees_the_full_checklist_with_zero_progress(): void
    {
        Sanctum::actingAs($this->planner());

        $res = $this->getJson('/api/v1/ai/dashboard')->assertOk();

        $res->assertJsonPath('data.onboarding.complete', false)
            ->assertJsonPath('data.onboarding.progress', 0)
            ->assertJsonPath('data.onboarding.done_count', 0)
            ->assertJsonPath('data.onboarding.total', 5)
            // The first uncompleted step is to create an event.
            ->assertJsonPath('data.onboarding.next.key', 'create_event')
            ->assertJsonPath('data.onboarding.next.href', '/dashboard/planner/events');
    }

    public function test_creating_an_event_completes_the_first_step_and_advances_the_next(): void
    {
        $planner = $this->planner();
        Sanctum::actingAs($planner);

        $event = Event::create([
            'planner_id' => $planner->id, 'title' => 'Gala', 'status' => 'planning', 'budget_total' => 0,
        ]);

        $res = $this->getJson('/api/v1/ai/dashboard')->assertOk();

        $steps = collect($res->json('data.onboarding.steps'))->keyBy('key');
        $this->assertTrue($steps['create_event']['done']);
        $this->assertFalse($steps['build_budget']['done']);

        // Budget step now deep-links into the event that exists.
        $res->assertJsonPath('data.onboarding.next.key', 'build_budget')
            ->assertJsonPath('data.onboarding.next.href', "/dashboard/planner/events/{$event->id}/budget")
            ->assertJsonPath('data.onboarding.done_count', 1)
            ->assertJsonPath('data.onboarding.progress', 20);
    }

    public function test_checklist_completes_once_every_signal_is_present(): void
    {
        $planner = $this->planner();
        Sanctum::actingAs($planner);

        $event = Event::create([
            'planner_id' => $planner->id, 'title' => 'Gala', 'status' => 'planning', 'budget_total' => 5_000_000,
        ]);
        $event->guests()->create(['full_name' => 'A', 'email' => 'a@ex.com', 'rsvp_status' => 'pending']);
        $event->vendorAssignments()->create(['vendor_name' => 'Snap', 'service' => 'Photography', 'price' => 100, 'status' => 'requested']);
        // Having chatted with the copilot leaves a conversation behind.
        $this->postJson('/api/v1/ai/chat', ['message' => 'hello', 'event_id' => $event->id])->assertOk();

        $res = $this->getJson('/api/v1/ai/dashboard')->assertOk();

        $res->assertJsonPath('data.onboarding.complete', true)
            ->assertJsonPath('data.onboarding.progress', 100)
            ->assertJsonPath('data.onboarding.next', null);
    }
}
