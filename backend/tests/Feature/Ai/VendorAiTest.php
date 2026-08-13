<?php

namespace Tests\Feature\Ai;

use App\Enums\AccountType;
use App\Models\BookingRequest;
use App\Models\Contract;
use App\Models\Quotation;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class VendorAiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RoleSeeder::class, PermissionSeeder::class]);
    }

    private function vendor(): User
    {
        return User::factory()->accountType(AccountType::Vendor)->create();
    }

    private function planner(): User
    {
        return User::factory()->accountType(AccountType::EventPlanner)->create();
    }

    public function test_vendor_ai_dashboard_reports_pipeline_and_reminders(): void
    {
        $vendor = $this->vendor();
        $planner = $this->planner();
        Sanctum::actingAs($vendor);

        // Two pending requests (one old), a booked contract.
        $old = BookingRequest::create([
            'planner_id' => $planner->id, 'vendor_id' => $vendor->id, 'title' => 'Wedding DJ',
            'status' => 'pending', 'budget' => 800000,
        ]);
        $old->forceFill(['created_at' => now()->subDays(4)])->save();
        BookingRequest::create([
            'planner_id' => $planner->id, 'vendor_id' => $vendor->id, 'title' => 'Corporate PA',
            'status' => 'pending', 'budget' => 500000,
        ]);
        Contract::create([
            'planner_id' => $planner->id, 'vendor_id' => $vendor->id, 'title' => 'Gala sound',
            'reference' => 'CTR-1', 'status' => 'active', 'amount' => 1500000,
        ]);

        $res = $this->getJson('/api/v1/marketplace/vendor/ai/dashboard')->assertOk();

        $res->assertJsonPath('data.assistant_name', 'OSEP Vendor Copilot')
            ->assertJsonPath('data.stats.open_requests', 2)
            ->assertJsonPath('data.stats.revenue', 1500000);

        // A stale-request reminder should be present and ranked high.
        $reminder = collect($res->json('data.reminders'))->firstWhere('key', 'requests_open');
        $this->assertNotNull($reminder);
        $this->assertSame('high', $reminder['priority']);
    }

    public function test_vendor_offline_chat_answers_a_business_question(): void
    {
        $vendor = $this->vendor();
        $planner = $this->planner();
        Sanctum::actingAs($vendor);

        // Win rate: 2 accepted of 3 resolved quotes = 67%.
        foreach (['accepted', 'accepted', 'rejected'] as $i => $status) {
            Quotation::create([
                'planner_id' => $planner->id, 'vendor_id' => $vendor->id, 'reference' => "Q-{$i}",
                'total' => 500000, 'status' => $status,
            ]);
        }

        $res = $this->postJson('/api/v1/marketplace/vendor/ai/chat', [
            'message' => 'What is my quotation win rate?',
        ])->assertOk();

        $content = $res->json('data.message.content');
        $this->assertStringContainsString('Win rate: 67%', $content);
        $this->assertSame('vendor', $res->json('data.message.agent'));
        $this->assertSame('vendor-local-heuristic', $res->json('data.message.model'));
    }

    public function test_vendor_chat_persists_a_conversation_scoped_to_the_vendor(): void
    {
        $vendor = $this->vendor();
        Sanctum::actingAs($vendor);

        $res = $this->postJson('/api/v1/marketplace/vendor/ai/chat', ['message' => 'hello'])->assertOk();
        $conversationId = $res->json('data.conversation.id');

        $this->assertDatabaseHas('ai_conversations', ['id' => $conversationId, 'user_id' => $vendor->id]);

        $this->getJson('/api/v1/marketplace/vendor/ai/conversations')
            ->assertOk()
            ->assertJsonPath('data.conversations.0.id', $conversationId);
    }

    public function test_a_vendor_cannot_read_another_vendors_conversation(): void
    {
        $mine = $this->vendor();
        $other = $this->vendor();

        Sanctum::actingAs($other);
        $theirs = $this->postJson('/api/v1/marketplace/vendor/ai/chat', ['message' => 'hi'])->json('data.conversation.id');

        Sanctum::actingAs($mine);
        $this->getJson("/api/v1/marketplace/vendor/ai/conversations/{$theirs}")->assertNotFound();
    }

    public function test_planner_cannot_reach_vendor_ai_routes(): void
    {
        Sanctum::actingAs($this->planner());

        $this->getJson('/api/v1/marketplace/vendor/ai/dashboard')->assertForbidden();
    }

    public function test_vendor_chat_command_proposes_and_approval_accepts_a_request(): void
    {
        $vendor = $this->vendor();
        $planner = $this->planner();
        Sanctum::actingAs($vendor);

        $req = BookingRequest::create([
            'planner_id' => $planner->id, 'vendor_id' => $vendor->id, 'title' => 'Wedding DJ',
            'status' => 'pending', 'budget' => 800000,
        ]);

        // A command comes back as an approval card, nothing changes yet.
        $res = $this->postJson('/api/v1/marketplace/vendor/ai/chat', [
            'message' => 'Accept the booking request for Wedding DJ',
        ])->assertOk();

        $res->assertJsonPath('data.message.action.type', 'vendor_respond_request')
            ->assertJsonPath('data.message.action.status', 'pending');
        $actionId = $res->json('data.message.action.id');
        $this->assertDatabaseHas('booking_requests', ['id' => $req->id, 'status' => 'pending']);

        // Approving runs it.
        $this->postJson("/api/v1/marketplace/vendor/ai/actions/{$actionId}/approve")
            ->assertOk()
            ->assertJsonPath('data.action.status', 'done');

        $this->assertDatabaseHas('booking_requests', ['id' => $req->id, 'status' => 'accepted']);
    }

    public function test_vendor_question_about_accepting_is_not_treated_as_a_command(): void
    {
        $vendor = $this->vendor();
        Sanctum::actingAs($vendor);

        // A question mentioning "accept" and "request" must NOT queue an action.
        $res = $this->postJson('/api/v1/marketplace/vendor/ai/chat', [
            'message' => 'Which requests should I accept?',
        ])->assertOk();

        $this->assertNull($res->json('data.message.action'));
        $this->assertDatabaseCount('ai_actions', 0);
    }
}
