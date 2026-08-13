<?php

namespace Tests\Feature\Ai;

use App\Enums\AccountType;
use App\Mail\GuestMessageMail;
use App\Models\AiAction;
use App\Models\Event;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AiActionsTest extends TestCase
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

    public function test_chat_command_queues_a_create_event_action_and_approval_creates_it(): void
    {
        $planner = $this->planner();
        Sanctum::actingAs($planner);

        // A natural-language command should come back as an approval card, not
        // create anything yet.
        $res = $this->postJson('/api/v1/ai/chat', [
            'message' => 'Create a new wedding called Sarah & John on December 12',
        ])->assertOk();

        $res->assertJsonPath('data.message.action.type', 'create_event')
            ->assertJsonPath('data.message.action.status', 'pending');

        $actionId = $res->json('data.message.action.id');
        $this->assertDatabaseHas('ai_actions', ['id' => $actionId, 'status' => 'pending', 'type' => 'create_event']);
        $this->assertDatabaseMissing('events', ['title' => 'Sarah & John']);

        // Approving runs it.
        $this->postJson("/api/v1/ai/actions/{$actionId}/approve")
            ->assertOk()
            ->assertJsonPath('data.action.status', 'done');

        $this->assertDatabaseHas('events', [
            'title' => 'Sarah & John',
            'event_type' => 'Wedding',
            'planner_id' => $planner->id,
        ]);
    }

    public function test_chat_create_event_captures_all_specified_fields(): void
    {
        $planner = $this->planner();
        $client = User::factory()->accountType(AccountType::Client)->create([
            'first_name' => 'Amina', 'last_name' => 'Said', 'email' => 'amina@example.com',
        ]);
        // Put the client in the planner's book via an existing event.
        Event::create(['planner_id' => $planner->id, 'client_id' => $client->id, 'title' => 'Prior', 'status' => 'planning']);

        Sanctum::actingAs($planner);

        $res = $this->postJson('/api/v1/ai/chat', [
            'message' => 'Create a high priority wedding called Aisha & Juma on December 20 '
                . 'from 14:00 to 22:00 for client amina@example.com with 150 guests, '
                . 'budget 5000000, theme Blush and Ivory, description An elegant garden wedding',
        ])->assertOk();

        $actionId = $res->json('data.message.action.id');
        $this->postJson("/api/v1/ai/actions/{$actionId}/approve")
            ->assertOk()
            ->assertJsonPath('data.action.status', 'done');

        $event = Event::where('title', 'Aisha & Juma')->firstOrFail();
        $this->assertSame('high', $event->priority->value);
        $this->assertStringStartsWith('14:00', $event->start_time);
        $this->assertStringStartsWith('22:00', $event->end_time);
        $this->assertSame(150, $event->expected_guests);
        $this->assertSame('5000000.00', (string) $event->budget_total);
        $this->assertSame('Blush and Ivory', $event->theme);
        $this->assertSame('An elegant garden wedding', $event->description);
        $this->assertSame($client->id, $event->client_id);
        $this->assertSame('12-20', $event->event_date->format('m-d'));
    }

    public function test_approving_rsvp_reminders_messages_pending_guests(): void
    {
        Mail::fake();
        $planner = $this->planner();
        $event = Event::create(['planner_id' => $planner->id, 'title' => 'Wedding', 'status' => 'planning']);

        // Two awaiting-RSVP guests (reachable by email) and one already confirmed.
        $event->guests()->create(['full_name' => 'A', 'email' => 'a@example.com', 'rsvp_status' => 'pending']);
        $event->guests()->create(['full_name' => 'B', 'email' => 'b@example.com', 'rsvp_status' => 'invited']);
        $event->guests()->create(['full_name' => 'C', 'email' => 'c@example.com', 'rsvp_status' => 'confirmed']);

        $action = AiAction::create([
            'user_id' => $planner->id,
            'event_id' => $event->id,
            'source' => 'automation',
            'type' => 'send_rsvp_reminders',
            'title' => 'Send RSVP reminders',
            'params' => [],
            'status' => 'pending',
        ]);

        Sanctum::actingAs($planner);
        $this->postJson("/api/v1/ai/actions/{$action->id}/approve")
            ->assertOk()
            ->assertJsonPath('data.action.status', 'done');

        // Only the two pending guests get a reminder.
        Mail::assertSent(GuestMessageMail::class, 2);
        $this->assertDatabaseCount('invitations', 2);
    }

    public function test_chat_can_add_update_and_delete_a_task(): void
    {
        $planner = $this->planner();
        $event = Event::create(['planner_id' => $planner->id, 'title' => 'Wedding', 'status' => 'planning']);
        Sanctum::actingAs($planner);

        $approve = function (string $message) use ($event) {
            $id = $this->postJson('/api/v1/ai/chat', ['message' => $message, 'event_id' => $event->id])
                ->assertOk()->json('data.message.action.id');
            $this->postJson("/api/v1/ai/actions/{$id}/approve")->assertOk()->assertJsonPath('data.action.status', 'done');
        };

        // Add.
        $approve('Add a task called Book the caterer due December 1 priority high');
        $task = $event->tasks()->where('title', 'Book the caterer')->firstOrFail();
        $this->assertSame('high', $task->priority->value);
        $this->assertSame('12-01', $task->due_date->format('m-d'));

        // Update — mark it done.
        $approve('Mark task Book the caterer as done');
        $this->assertSame('completed', $task->fresh()->status->value);

        // Delete.
        $approve('Delete the Book the caterer task');
        $this->assertDatabaseMissing('event_tasks', ['id' => $task->id]);
    }

    public function test_chat_can_add_and_update_a_timeline_milestone(): void
    {
        $planner = $this->planner();
        $event = Event::create(['planner_id' => $planner->id, 'title' => 'Wedding', 'status' => 'planning']);
        Sanctum::actingAs($planner);

        $add = $this->postJson('/api/v1/ai/chat', [
            'message' => 'Add a milestone called Venue confirmed due 2026-09-01', 'event_id' => $event->id,
        ])->assertOk()->json('data.message.action.id');
        $this->postJson("/api/v1/ai/actions/{$add}/approve")->assertOk();

        $milestone = $event->milestones()->where('name', 'Venue confirmed')->firstOrFail();
        $this->assertSame('2026-09-01', $milestone->due_date->format('Y-m-d'));

        $upd = $this->postJson('/api/v1/ai/chat', [
            'message' => 'Mark the Venue confirmed milestone as completed', 'event_id' => $event->id,
        ])->assertOk()->json('data.message.action.id');
        $this->postJson("/api/v1/ai/actions/{$upd}/approve")->assertOk();

        $this->assertSame('completed', $milestone->fresh()->status->value);
    }

    public function test_chat_can_add_update_and_delete_a_budget_item(): void
    {
        $planner = $this->planner();
        $event = Event::create(['planner_id' => $planner->id, 'title' => 'Wedding', 'status' => 'planning']);
        Sanctum::actingAs($planner);

        $approve = function (string $message) use ($event) {
            $id = $this->postJson('/api/v1/ai/chat', ['message' => $message, 'event_id' => $event->id])
                ->assertOk()->json('data.message.action.id');
            $this->postJson("/api/v1/ai/actions/{$id}/approve")->assertOk()->assertJsonPath('data.action.status', 'done');
        };

        $approve('Add a budget item for catering at 5,000,000');
        $item = $event->budgetItems()->where('description', 'catering')->firstOrFail();
        $this->assertSame('5000000.00', (string) $item->estimated_cost);

        $approve('Update the catering budget to 6,500,000');
        $this->assertSame('6500000.00', (string) $item->fresh()->estimated_cost);

        $approve('Mark the catering budget item as paid');
        $this->assertSame('paid', $item->fresh()->status->value);

        $approve('Delete the catering budget item');
        $this->assertDatabaseMissing('budget_items', ['id' => $item->id]);
    }

    public function test_chat_designs_a_venue_for_the_guest_count(): void
    {
        $planner = $this->planner();
        $event = Event::create(['planner_id' => $planner->id, 'title' => 'Wedding', 'status' => 'planning']);
        Sanctum::actingAs($planner);

        $id = $this->postJson('/api/v1/ai/chat', [
            'message' => 'Design the venue for 40 guests', 'event_id' => $event->id,
        ])->assertOk()->json('data.message.action.id');

        $this->postJson("/api/v1/ai/actions/{$id}/approve")->assertOk()->assertJsonPath('data.action.status', 'done');

        $layout = $event->venueLayouts()->firstOrFail();
        // 40 guests / 10 per table = 4 round tables, plus a stage and a dance floor.
        $this->assertSame(4, $layout->objects()->where('object_type', 'round_table')->count());
        $this->assertSame(1, $layout->objects()->where('object_type', 'medium_stage')->count());
        $this->assertSame(40, $layout->max_capacity);
    }

    public function test_updating_a_missing_task_reports_a_clear_failure(): void
    {
        $planner = $this->planner();
        $event = Event::create(['planner_id' => $planner->id, 'title' => 'Wedding', 'status' => 'planning']);
        Sanctum::actingAs($planner);

        $id = $this->postJson('/api/v1/ai/chat', [
            'message' => 'Mark task Nonexistent thing as done', 'event_id' => $event->id,
        ])->assertOk()->json('data.message.action.id');

        $this->postJson("/api/v1/ai/actions/{$id}/approve")
            ->assertOk()
            ->assertJsonPath('data.action.status', 'failed');
    }

    public function test_live_mode_toggle_rejects_an_unconfigured_driver(): void
    {
        Cache::flush();
        $planner = $this->planner();
        Sanctum::actingAs($planner);

        // No Anthropic key configured in the test env → cannot go live.
        config(['ai.providers.anthropic.key' => null]);
        $this->putJson('/api/v1/ai/settings', ['driver' => 'anthropic'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('driver');

        // The offline engine is always selectable.
        $this->putJson('/api/v1/ai/settings', ['driver' => 'local'])
            ->assertOk()
            ->assertJsonPath('data.is_live', false);

        // With a key present, the switch is allowed.
        config(['ai.providers.anthropic.key' => 'test-key']);
        $this->putJson('/api/v1/ai/settings', ['driver' => 'anthropic'])
            ->assertOk()
            ->assertJsonPath('data.is_live', true);
    }

    public function test_another_planner_cannot_approve_someone_elses_action(): void
    {
        $owner = $this->planner();
        $event = Event::create(['planner_id' => $owner->id, 'title' => 'Wedding', 'status' => 'planning']);
        $action = AiAction::create([
            'user_id' => $owner->id, 'event_id' => $event->id, 'source' => 'chat',
            'type' => 'create_tasks', 'title' => 'Checklist', 'params' => [], 'status' => 'pending',
        ]);

        Sanctum::actingAs($this->planner());
        $this->postJson("/api/v1/ai/actions/{$action->id}/approve")->assertNotFound();

        $this->assertDatabaseHas('ai_actions', ['id' => $action->id, 'status' => 'pending']);
    }
}
