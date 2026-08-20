<?php

namespace Tests\Feature\Workspace;

use App\Enums\AccountType;
use App\Models\Event;
use App\Models\User;
use App\Models\VenueLayout;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class VenueDesignerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RoleSeeder::class, PermissionSeeder::class]);
    }

    private function eventFor(User $planner): Event
    {
        return Event::create(['planner_id' => $planner->id, 'title' => 'Party', 'status' => 'planning']);
    }

    public function test_planner_can_create_and_bulk_save_a_layout(): void
    {
        Sanctum::actingAs($planner = User::factory()->accountType(AccountType::EventPlanner)->create());
        $event = $this->eventFor($planner);

        $created = $this->postJson("/api/v1/events/{$event->id}/venue-layouts", [
            'layout_name' => 'Wedding Layout', 'width' => 40, 'height' => 30, 'max_capacity' => 200,
        ])->assertCreated()->json('data.layout');

        $layoutId = $created['id'];

        $this->putJson("/api/v1/events/{$event->id}/venue-layouts/{$layoutId}", [
            'objects' => [
                ['uid' => 'a', 'object_type' => 'round_table', 'x' => 5, 'y' => 5, 'width' => 3, 'height' => 3, 'layer' => 'furniture'],
                ['uid' => 'b', 'object_type' => 'medium_stage', 'x' => 10, 'y' => 1, 'width' => 8, 'height' => 3, 'layer' => 'stage'],
            ],
        ])->assertOk()->assertJsonCount(2, 'data.layout.objects');

        // Removing one object on the next save deletes it.
        $this->putJson("/api/v1/events/{$event->id}/venue-layouts/{$layoutId}", [
            'objects' => [
                ['uid' => 'a', 'object_type' => 'round_table', 'x' => 6, 'y' => 6, 'width' => 3, 'height' => 3, 'layer' => 'furniture'],
            ],
        ])->assertOk()->assertJsonCount(1, 'data.layout.objects');

        $this->assertDatabaseMissing('venue_objects', ['layout_id' => $layoutId, 'uid' => 'b']);
    }

    public function test_seating_links_a_guest_and_ignores_foreign_guests(): void
    {
        Sanctum::actingAs($planner = User::factory()->accountType(AccountType::EventPlanner)->create());
        $event = $this->eventFor($planner);
        $guest = $event->guests()->create(['full_name' => 'Grace', 'rsvp_status' => 'confirmed']);

        $otherEvent = $this->eventFor($planner);
        $foreign = $otherEvent->guests()->create(['full_name' => 'Outsider', 'rsvp_status' => 'invited']);

        $layout = $event->venueLayouts()->create(['layout_name' => 'L', 'created_by' => $planner->id]);
        $object = $layout->objects()->create(['uid' => 't1', 'object_type' => 'round_table', 'width' => 3, 'height' => 3]);

        $this->putJson("/api/v1/events/{$event->id}/venue-layouts/{$layout->id}/objects/{$object->id}/seating", [
            'seats' => [
                ['seat_number' => 1, 'guest_id' => $guest->id],
                ['seat_number' => 2, 'guest_id' => $foreign->id],
                ['seat_number' => 3, 'notes' => 'Reserved'],
            ],
        ])->assertOk();

        $this->assertDatabaseHas('seating_assignments', ['venue_object_id' => $object->id, 'seat_number' => 1, 'guest_id' => $guest->id]);
        // The foreign guest is dropped to null.
        $this->assertDatabaseHas('seating_assignments', ['venue_object_id' => $object->id, 'seat_number' => 2, 'guest_id' => null]);
        $this->assertDatabaseHas('seating_assignments', ['venue_object_id' => $object->id, 'seat_number' => 3, 'notes' => 'Reserved']);
    }

    public function test_a_planner_cannot_touch_another_planners_layout(): void
    {
        $owner = User::factory()->accountType(AccountType::EventPlanner)->create();
        $event = $this->eventFor($owner);
        $layout = $event->venueLayouts()->create(['layout_name' => 'L', 'created_by' => $owner->id]);

        Sanctum::actingAs(User::factory()->accountType(AccountType::EventPlanner)->create());

        $this->getJson("/api/v1/events/{$event->id}/venue-layouts/{$layout->id}")->assertNotFound();
    }
}
