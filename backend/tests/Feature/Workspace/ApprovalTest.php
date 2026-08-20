<?php

namespace Tests\Feature\Workspace;

use App\Enums\AccountType;
use App\Models\Event;
use App\Models\Notification;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ApprovalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RoleSeeder::class, PermissionSeeder::class]);
    }

    private function seedEvent(): array
    {
        $planner = User::factory()->accountType(AccountType::EventPlanner)->create();
        $client = User::factory()->accountType(AccountType::Client)->create();

        $event = Event::create([
            'planner_id' => $planner->id, 'client_id' => $client->id, 'title' => 'Wedding',
            'status' => 'execution', 'progress' => 50, 'budget_total' => 1000, 'budget_spent' => 300,
        ]);
        $approval = $event->approvals()->create(['title' => 'Decor', 'type' => 'proposal', 'status' => 'pending']);

        return [$planner, $client, $approval];
    }

    public function test_a_client_can_approve_and_the_planner_is_notified(): void
    {
        [$planner, $client, $approval] = $this->seedEvent();

        Sanctum::actingAs($client);

        $this->postJson("/api/v1/approvals/{$approval->id}/respond", ['decision' => 'approve'])
            ->assertOk()
            ->assertJsonPath('data.approval.status', 'approved');

        $this->assertDatabaseHas('notifications', [
            'user_id' => $planner->id,
            'type' => 'approval_decision',
        ]);
    }

    public function test_requesting_changes_requires_a_note(): void
    {
        [, $client, $approval] = $this->seedEvent();

        Sanctum::actingAs($client);

        $this->postJson("/api/v1/approvals/{$approval->id}/respond", ['decision' => 'request_changes'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['note']);
    }

    public function test_a_client_cannot_respond_to_another_clients_approval(): void
    {
        [, , $approval] = $this->seedEvent();

        Sanctum::actingAs(User::factory()->accountType(AccountType::Client)->create());

        $this->postJson("/api/v1/approvals/{$approval->id}/respond", ['decision' => 'approve'])
            ->assertStatus(404);
    }

    public function test_an_already_decided_approval_cannot_be_changed(): void
    {
        [, $client, $approval] = $this->seedEvent();
        $approval->update(['status' => 'approved', 'decided_at' => now()]);

        Sanctum::actingAs($client);

        $this->postJson("/api/v1/approvals/{$approval->id}/respond", ['decision' => 'reject'])
            ->assertStatus(422);
    }
}
