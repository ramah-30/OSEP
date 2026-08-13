<?php

namespace Tests\Feature\Client;

use App\Enums\AccountType;
use App\Models\Event;
use App\Models\Guest;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ClientGuestTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RoleSeeder::class, PermissionSeeder::class]);
    }

    private function scenario(): array
    {
        $planner = User::factory()->accountType(AccountType::EventPlanner)->create();
        $client = User::factory()->accountType(AccountType::Client)->create();
        $event = Event::create([
            'planner_id' => $planner->id,
            'client_id' => $client->id,
            'title' => "Amina's Wedding",
            'status' => 'planning',
        ]);

        return [$planner, $client, $event];
    }

    public function test_client_can_manage_their_guest_list_and_the_planner_is_notified(): void
    {
        [$planner, $client, $event] = $this->scenario();
        Sanctum::actingAs($client);

        // Add a guest.
        $guestId = $this->postJson("/api/v1/my-events/{$event->id}/guests", [
            'full_name' => 'John Doe', 'email' => 'john@example.com', 'category' => 'Family',
        ])->assertCreated()->json('data.guest.id');

        $this->assertDatabaseHas('guests', ['id' => $guestId, 'event_id' => $event->id, 'full_name' => 'John Doe']);
        $this->assertDatabaseHas('notifications', ['user_id' => $planner->id, 'type' => 'client_guest_added']);

        // List — the client sees their guest.
        $this->getJson("/api/v1/my-events/{$event->id}/guests")
            ->assertOk()
            ->assertJsonPath('data.summary.total', 1)
            ->assertJsonPath('data.guests.0.full_name', 'John Doe');

        // Edit.
        $this->putJson("/api/v1/my-events/{$event->id}/guests/{$guestId}", ['full_name' => 'Johnny Doe'])
            ->assertOk()->assertJsonPath('data.guest.full_name', 'Johnny Doe');
        $this->assertDatabaseHas('notifications', ['user_id' => $planner->id, 'type' => 'client_guest_updated']);

        // Remove.
        $this->deleteJson("/api/v1/my-events/{$event->id}/guests/{$guestId}")->assertOk();
        $this->assertDatabaseMissing('guests', ['id' => $guestId]);
        $this->assertDatabaseHas('notifications', ['user_id' => $planner->id, 'type' => 'client_guest_removed']);
    }

    public function test_a_client_cannot_touch_another_clients_event(): void
    {
        [, , $event] = $this->scenario();
        $outsider = User::factory()->accountType(AccountType::Client)->create();

        Sanctum::actingAs($outsider);

        $this->getJson("/api/v1/my-events/{$event->id}/guests")->assertNotFound();
        $this->postJson("/api/v1/my-events/{$event->id}/guests", ['full_name' => 'Sneaky'])->assertNotFound();

        $this->assertDatabaseMissing('guests', ['full_name' => 'Sneaky']);
    }

    public function test_a_guest_from_another_event_cannot_be_edited_through_this_event(): void
    {
        [, $client, $event] = $this->scenario();
        $otherEvent = Event::create(['planner_id' => $event->planner_id, 'title' => 'Other', 'status' => 'planning']);
        $foreignGuest = $otherEvent->guests()->create(['full_name' => 'Not Yours']);

        Sanctum::actingAs($client);

        $this->putJson("/api/v1/my-events/{$event->id}/guests/{$foreignGuest->id}", ['full_name' => 'Hacked'])
            ->assertNotFound();
        $this->assertDatabaseHas('guests', ['id' => $foreignGuest->id, 'full_name' => 'Not Yours']);
    }

    public function test_a_non_client_cannot_use_the_client_guest_routes(): void
    {
        [$planner, , $event] = $this->scenario();
        Sanctum::actingAs($planner);

        // The role:client middleware blocks the planner from this client-only surface.
        $this->getJson("/api/v1/my-events/{$event->id}/guests")->assertForbidden();
    }
}
