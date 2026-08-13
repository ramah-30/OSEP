<?php

namespace Tests\Feature\Ai;

use App\Enums\AccountType;
use App\Models\Approval;
use App\Models\Event;
use App\Models\Invoice;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ClientAiTest extends TestCase
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

    /** A client with an event, a pending approval, guests and an outstanding invoice. */
    private function clientWithEvent(User $client, User $planner): Event
    {
        $event = Event::create([
            'planner_id' => $planner->id, 'client_id' => $client->id, 'title' => 'Our Wedding',
            'event_type' => 'Wedding', 'status' => 'planning', 'event_date' => now()->addDays(30),
            'progress' => 40,
        ]);
        $event->guests()->create(['full_name' => 'A', 'email' => 'a@ex.com', 'rsvp_status' => 'confirmed']);
        $event->guests()->create(['full_name' => 'B', 'email' => 'b@ex.com', 'rsvp_status' => 'pending']);

        Approval::create([
            'event_id' => $event->id, 'submitted_by' => $planner->id, 'title' => 'Approve the catering menu',
            'type' => 'vendor', 'status' => 'pending',
        ]);

        Invoice::create([
            'invoice_number' => 'INV-1', 'planner_id' => $planner->id, 'client_id' => $client->id,
            'event_id' => $event->id, 'title' => 'Deposit', 'currency' => 'TZS',
            'issue_date' => now()->subDay(), 'due_date' => now()->addDays(5),
            'subtotal' => 1000000, 'total' => 1000000, 'amount_paid' => 0, 'status' => 'sent',
        ]);

        return $event;
    }

    public function test_client_ai_dashboard_reports_event_approvals_and_balance(): void
    {
        $client = $this->client();
        Sanctum::actingAs($client);
        $this->clientWithEvent($client, $this->planner());

        $res = $this->getJson('/api/v1/client/ai/dashboard')->assertOk();

        $res->assertJsonPath('data.assistant_name', 'OSEP Planning Concierge')
            ->assertJsonPath('data.stats.approvals_pending', 1)
            ->assertJsonPath('data.stats.outstanding_amount', 1000000)
            ->assertJsonPath('data.stats.guests_confirmed', 1);

        $reminder = collect($res->json('data.reminders'))->firstWhere('key', 'approvals_pending');
        $this->assertNotNull($reminder);
        $this->assertSame('high', $reminder['priority']);
    }

    public function test_client_offline_chat_lists_pending_approvals(): void
    {
        $client = $this->client();
        Sanctum::actingAs($client);
        $this->clientWithEvent($client, $this->planner());

        $res = $this->postJson('/api/v1/client/ai/chat', [
            'message' => 'What do I need to approve?',
        ])->assertOk();

        $content = $res->json('data.message.content');
        $this->assertStringContainsString('Approve the catering menu', $content);
        $this->assertSame('client', $res->json('data.message.agent'));
        $this->assertSame('client-local-heuristic', $res->json('data.message.model'));
    }

    public function test_client_offline_chat_reports_outstanding_balance(): void
    {
        $client = $this->client();
        Sanctum::actingAs($client);
        $this->clientWithEvent($client, $this->planner());

        $res = $this->postJson('/api/v1/client/ai/chat', [
            'message' => 'What is my outstanding balance?',
        ])->assertOk();

        $this->assertStringContainsString('Outstanding', $res->json('data.message.content'));
    }

    public function test_a_client_cannot_read_another_clients_conversation(): void
    {
        $mine = $this->client();
        $other = $this->client();

        Sanctum::actingAs($other);
        $theirs = $this->postJson('/api/v1/client/ai/chat', ['message' => 'hi'])->json('data.conversation.id');

        Sanctum::actingAs($mine);
        $this->getJson("/api/v1/client/ai/conversations/{$theirs}")->assertNotFound();
    }

    public function test_planner_cannot_reach_client_ai_routes(): void
    {
        Sanctum::actingAs($this->planner());

        $this->getJson('/api/v1/client/ai/dashboard')->assertForbidden();
    }

    public function test_client_chat_command_proposes_and_approval_approves_an_approval(): void
    {
        $client = $this->client();
        Sanctum::actingAs($client);
        $event = $this->clientWithEvent($client, $this->planner());
        $approval = $event->approvals()->where('title', 'Approve the catering menu')->firstOrFail();

        $res = $this->postJson('/api/v1/client/ai/chat', [
            'message' => 'Approve the catering menu',
        ])->assertOk();

        $res->assertJsonPath('data.message.action.type', 'client_respond_approval')
            ->assertJsonPath('data.message.action.status', 'pending');
        $actionId = $res->json('data.message.action.id');
        $this->assertDatabaseHas('approvals', ['id' => $approval->id, 'status' => 'pending']);

        $this->postJson("/api/v1/client/ai/actions/{$actionId}/approve")
            ->assertOk()
            ->assertJsonPath('data.action.status', 'done');

        $this->assertDatabaseHas('approvals', ['id' => $approval->id, 'status' => 'approved']);
    }

    public function test_client_chat_command_adds_a_guest_on_approval(): void
    {
        $client = $this->client();
        Sanctum::actingAs($client);
        $event = $this->clientWithEvent($client, $this->planner());

        $res = $this->postJson('/api/v1/client/ai/chat', [
            'message' => 'Add guest "Jane Doe" to my list',
        ])->assertOk();

        $res->assertJsonPath('data.message.action.type', 'client_add_guest');
        $actionId = $res->json('data.message.action.id');

        $this->postJson("/api/v1/client/ai/actions/{$actionId}/approve")->assertOk();

        $this->assertDatabaseHas('guests', ['event_id' => $event->id, 'full_name' => 'Jane Doe']);
    }

    public function test_client_question_is_not_treated_as_a_command(): void
    {
        $client = $this->client();
        Sanctum::actingAs($client);
        $this->clientWithEvent($client, $this->planner());

        $res = $this->postJson('/api/v1/client/ai/chat', [
            'message' => 'What do I need to approve?',
        ])->assertOk();

        $this->assertNull($res->json('data.message.action'));
        $this->assertDatabaseCount('ai_actions', 0);
    }

    /** A planner with a profile so they show up in the directory and resolve by name. */
    private function plannerWithProfile(string $company): User
    {
        $planner = $this->planner();
        $planner->plannerProfile()->create([
            'company_name' => $company,
            'specialization' => 'Weddings',
            'location' => 'Dar es Salaam',
            'experience_years' => 5,
            'booking_slug' => \Illuminate\Support\Str::slug($company),
        ]);

        return $planner;
    }

    public function test_concierge_lists_planners_to_find(): void
    {
        $client = $this->client();
        Sanctum::actingAs($client);
        $this->plannerWithProfile('Elegant Events Ltd');

        $res = $this->postJson('/api/v1/client/ai/chat', [
            'message' => 'Find me a planner',
        ])->assertOk();

        $this->assertStringContainsString('Elegant Events Ltd', $res->json('data.message.content'));
        $this->assertNull($res->json('data.message.action'));
    }

    public function test_concierge_reports_a_progress_summary(): void
    {
        $client = $this->client();
        Sanctum::actingAs($client);
        $this->clientWithEvent($client, $this->planner());

        $res = $this->postJson('/api/v1/client/ai/chat', [
            'message' => 'Show my progress summary',
        ])->assertOk();

        $content = $res->json('data.message.content');
        $this->assertStringContainsString('progress', strtolower($content));
        $this->assertStringContainsString('40%', $content); // progress from clientWithEvent
    }

    public function test_client_can_book_a_planner_via_an_approved_action(): void
    {
        $client = $this->client();
        Sanctum::actingAs($client);
        $planner = $this->plannerWithProfile('Elegant Events Ltd');

        $res = $this->postJson('/api/v1/client/ai/chat', [
            'message' => 'Book Elegant Events Ltd',
        ])->assertOk();

        $res->assertJsonPath('data.message.action.type', 'client_book_planner')
            ->assertJsonPath('data.message.action.status', 'pending');
        $actionId = $res->json('data.message.action.id');

        $this->postJson("/api/v1/client/ai/actions/{$actionId}/approve")->assertOk();

        $this->assertDatabaseHas('planner_booking_requests', [
            'planner_id' => $planner->id, 'client_id' => $client->id, 'status' => 'pending',
        ]);
    }

    public function test_generic_book_a_planner_lists_options_without_an_action(): void
    {
        $client = $this->client();
        Sanctum::actingAs($client);
        $this->plannerWithProfile('Elegant Events Ltd');

        $res = $this->postJson('/api/v1/client/ai/chat', [
            'message' => 'Book a planner',
        ])->assertOk();

        $this->assertNull($res->json('data.message.action'));
        $this->assertStringContainsString('Elegant Events Ltd', $res->json('data.message.content'));
    }
}
