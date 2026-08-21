<?php

namespace Tests\Feature\Workspace;

use App\Enums\AccountType;
use App\Models\Event;
use App\Models\Guest;
use App\Models\Invitation;
use App\Models\QrCode;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class GuestManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RoleSeeder::class, PermissionSeeder::class]);
        Mail::fake();
    }

    private function planner(): User
    {
        return User::factory()->accountType(AccountType::EventPlanner)->create();
    }

    private function event(User $planner): Event
    {
        return Event::create([
            'planner_id' => $planner->id,
            'title' => 'Gala',
            'status' => 'planning',
            'event_date' => '2026-09-01',
            'venue' => 'Hall',
        ]);
    }

    public function test_creating_a_guest_derives_full_name_and_a_token(): void
    {
        Sanctum::actingAs($planner = $this->planner());
        $event = $this->event($planner);

        $this->postJson("/api/v1/events/{$event->id}/guests", [
            'first_name' => 'Grace',
            'last_name' => 'Mwakalinga',
            'email' => 'grace@example.com',
            'category' => 'VIP',
        ])
            ->assertCreated()
            ->assertJsonPath('data.guest.full_name', 'Grace Mwakalinga')
            ->assertJsonPath('data.guest.rsvp_status', 'pending');

        $guest = Guest::first();
        $this->assertNotNull($guest->rsvp_token);
    }

    public function test_sending_an_invitation_delivers_and_records_the_trail(): void
    {
        Sanctum::actingAs($planner = $this->planner());
        $event = $this->event($planner);
        $guest = $event->guests()->create(['full_name' => 'Peter Sanga', 'email' => 'peter@example.com']);

        $this->postJson("/api/v1/events/{$event->id}/invitations/send", [
            'guest_ids' => [$guest->id],
            'channel' => 'email',
        ])->assertOk()->assertJsonPath('data.sent', 1);

        Mail::assertSent(\App\Mail\GuestMessageMail::class);
        $this->assertDatabaseHas('invitations', ['guest_id' => $guest->id, 'status' => 'delivered']);
        $this->assertDatabaseHas('invitation_delivery_logs', ['status' => 'sent']);
        $this->assertSame('delivered', $guest->refresh()->invitation_status->value);
    }

    public function test_email_invitation_without_address_is_marked_failed(): void
    {
        Sanctum::actingAs($planner = $this->planner());
        $event = $this->event($planner);
        $guest = $event->guests()->create(['full_name' => 'No Email']);

        $this->postJson("/api/v1/events/{$event->id}/invitations/send", [
            'guest_ids' => [$guest->id],
            'channel' => 'email',
        ])->assertOk()->assertJsonPath('data.failed', 1);

        $this->assertDatabaseHas('invitations', ['guest_id' => $guest->id, 'status' => 'failed']);
        $this->assertDatabaseHas('notifications', ['user_id' => $planner->id, 'type' => 'invitation_failed']);
    }

    public function test_ticket_is_withheld_until_the_guest_confirms(): void
    {
        Sanctum::actingAs($planner = $this->planner());
        $event = $this->event($planner);
        $guest = $event->guests()->create(['full_name' => 'Not Yet', 'rsvp_status' => 'pending']);

        // No RSVP yet - no ticket.
        $this->getJson("/api/v1/events/{$event->id}/guests/{$guest->id}/ticket")->assertStatus(422);
        $this->assertDatabaseMissing('qr_codes', ['guest_id' => $guest->id]);

        // Once confirmed, the ticket is minted and returned.
        $guest->update(['rsvp_status' => 'confirmed']);
        $this->getJson("/api/v1/events/{$event->id}/guests/{$guest->id}/ticket")
            ->assertOk()
            ->assertJsonPath('data.ticket.guest_id', $guest->id);
        $this->assertDatabaseHas('qr_codes', ['guest_id' => $guest->id]);
    }

    public function test_sms_invitation_sends_through_the_africas_talking_gateway(): void
    {
        config()->set('services.africastalking.api_key', 'test-key');
        config()->set('services.africastalking.username', 'sandbox');
        config()->set('services.africastalking.sandbox', true);

        Http::fake([
            '*africastalking.com*' => Http::response([
                'SMSMessageData' => [
                    'Message' => 'Sent to 1/1',
                    'Recipients' => [[
                        'number' => '+255700000000', 'status' => 'Success',
                        'messageId' => 'ATXid_123', 'cost' => 'TZS 1.00',
                    ]],
                ],
            ], 200),
        ]);

        Sanctum::actingAs($planner = $this->planner());
        $event = $this->event($planner);
        $guest = $event->guests()->create(['full_name' => 'Amina', 'phone' => '+255700000000']);

        $this->postJson("/api/v1/events/{$event->id}/invitations/send", [
            'guest_ids' => [$guest->id],
            'channel' => 'sms',
        ])->assertOk()->assertJsonPath('data.sent', 1);

        Http::assertSent(fn ($request) => str_contains($request->url(), 'africastalking.com')
            && $request['to'] === '+255700000000');

        $this->assertDatabaseHas('invitations', ['guest_id' => $guest->id, 'channel' => 'sms', 'status' => 'delivered']);
    }

    public function test_sms_invitation_without_a_configured_gateway_is_marked_failed(): void
    {
        config()->set('services.africastalking.api_key', null);

        Sanctum::actingAs($planner = $this->planner());
        $event = $this->event($planner);
        $guest = $event->guests()->create(['full_name' => 'Amina', 'phone' => '+255700000000']);

        $this->postJson("/api/v1/events/{$event->id}/invitations/send", [
            'guest_ids' => [$guest->id],
            'channel' => 'sms',
        ])->assertOk()->assertJsonPath('data.failed', 1);

        $this->assertDatabaseHas('invitations', ['guest_id' => $guest->id, 'channel' => 'sms', 'status' => 'failed']);
    }

    public function test_public_rsvp_records_response_issues_ticket_and_notifies(): void
    {
        $planner = $this->planner();
        $event = $this->event($planner);
        $guest = $event->guests()->create(['full_name' => 'Aisha Rashid', 'email' => 'aisha@example.com', 'plus_ones_allowed' => 2]);

        // Public - no auth.
        $this->getJson("/api/v1/rsvp/{$guest->rsvp_token}")
            ->assertOk()
            ->assertJsonPath('data.guest.full_name', 'Aisha Rashid');

        $this->postJson("/api/v1/rsvp/{$guest->rsvp_token}", [
            'response' => 'attending',
            'additional_guests' => 5, // over the limit - should clamp to 2
            'meal_choice' => 'Vegan',
        ])->assertOk()->assertJsonPath('data.confirmed', true);

        $guest->refresh();
        $this->assertSame('confirmed', $guest->rsvp_status->value);
        $this->assertDatabaseHas('rsvp_responses', ['guest_id' => $guest->id, 'additional_guests' => 2]);
        $this->assertDatabaseHas('qr_codes', ['guest_id' => $guest->id]);
        $this->assertDatabaseHas('notifications', ['user_id' => $planner->id, 'type' => 'rsvp_received']);
    }

    public function test_checkin_by_token_works_once_and_rejects_duplicates(): void
    {
        Sanctum::actingAs($planner = $this->planner());
        $event = $this->event($planner);
        $guest = $event->guests()->create(['full_name' => 'Grace M', 'rsvp_status' => 'confirmed']);
        $qr = QrCode::create([
            'event_id' => $event->id, 'guest_id' => $guest->id, 'token' => 'TCK-DEMO-TOKEN', 'issued_at' => now(),
        ]);

        $this->postJson("/api/v1/events/{$event->id}/checkins", ['token' => $qr->token])
            ->assertCreated();

        $this->assertSame('checked_in', $guest->refresh()->checkin_status->value);
        $this->assertDatabaseHas('checkins', ['guest_id' => $guest->id, 'method' => 'qr']);

        // Second scan is rejected.
        $this->postJson("/api/v1/events/{$event->id}/checkins", ['token' => $qr->token])
            ->assertStatus(409);
    }

    public function test_checkin_rejects_a_ticket_from_another_event(): void
    {
        Sanctum::actingAs($planner = $this->planner());
        $event = $this->event($planner);
        $other = $this->event($planner);
        $foreignGuest = $other->guests()->create(['full_name' => 'Outsider']);
        $qr = QrCode::create(['event_id' => $other->id, 'guest_id' => $foreignGuest->id, 'token' => 'TCK-OTHER', 'issued_at' => now()]);

        $this->postJson("/api/v1/events/{$event->id}/checkins", ['token' => $qr->token])
            ->assertStatus(422);
    }

    public function test_csv_import_detects_duplicates_and_invalid_rows(): void
    {
        Sanctum::actingAs($planner = $this->planner());
        $event = $this->event($planner);
        $event->guests()->create(['full_name' => 'Existing Person', 'email' => 'dupe@example.com']);

        $csv = "name,email,category\n"
            ."Alice Wonder,alice@example.com,Friends\n"
            ."Existing Person,dupe@example.com,VIP\n"   // duplicate email
            ."Bad Row,not-an-email,Family\n"            // invalid email
            ."Bob Builder,bob@example.com,Family\n";

        $file = \Illuminate\Http\Testing\File::createWithContent('guests.csv', $csv);

        $this->postJson("/api/v1/events/{$event->id}/guests/import", ['file' => $file])
            ->assertOk()
            ->assertJsonPath('data.imported', 2)
            ->assertJsonCount(1, 'data.duplicates')
            ->assertJsonCount(1, 'data.errors');

        $this->assertDatabaseHas('guests', ['event_id' => $event->id, 'full_name' => 'Alice Wonder']);
    }

    public function test_bulk_send_invitations_action(): void
    {
        Sanctum::actingAs($planner = $this->planner());
        $event = $this->event($planner);
        $a = $event->guests()->create(['full_name' => 'A', 'email' => 'a@example.com']);
        $b = $event->guests()->create(['full_name' => 'B', 'email' => 'b@example.com']);

        $this->postJson("/api/v1/events/{$event->id}/guests/bulk-action", [
            'action' => 'send_invitations',
            'guest_ids' => [$a->id, $b->id],
        ])->assertOk();

        $this->assertSame(2, Invitation::count());
    }

    public function test_scheduled_reminder_is_dispatched_by_the_command(): void
    {
        Sanctum::actingAs($planner = $this->planner());
        $event = $this->event($planner);
        $guest = $event->guests()->create(['full_name' => 'Late Responder', 'email' => 'late@example.com']);

        $this->postJson("/api/v1/events/{$event->id}/reminders/send", [
            'target' => 'selected',
            'guest_ids' => [$guest->id],
            'channel' => 'email',
            'scheduled_for' => now()->addDay()->toIso8601String(),
        ])->assertOk()->assertJsonPath('data.scheduled', 1);

        // Move the schedule into the past and run the dispatcher.
        Invitation::query()->update(['scheduled_for' => now()->subMinute()]);
        $this->artisan('osep:dispatch-reminders')->assertSuccessful();

        $this->assertDatabaseHas('invitations', ['guest_id' => $guest->id, 'status' => 'delivered']);
    }

    public function test_guest_dashboard_returns_expected_shape(): void
    {
        Sanctum::actingAs($planner = $this->planner());
        $event = $this->event($planner);
        $event->guests()->create(['full_name' => 'C1', 'rsvp_status' => 'confirmed', 'invitation_status' => 'delivered']);
        $event->guests()->create(['full_name' => 'P1', 'rsvp_status' => 'pending', 'invitation_status' => 'delivered']);

        $this->getJson("/api/v1/events/{$event->id}/guests/dashboard")
            ->assertOk()
            ->assertJsonPath('data.cards.total', 2)
            ->assertJsonPath('data.cards.confirmed', 1)
            ->assertJsonStructure(['data' => ['cards', 'rsvp_distribution', 'categories', 'daily_trends', 'checkin']]);
    }

    public function test_another_planner_cannot_reach_the_guest_list(): void
    {
        $owner = $this->planner();
        $event = $this->event($owner);

        Sanctum::actingAs($this->planner());
        $this->getJson("/api/v1/events/{$event->id}/guests")->assertNotFound();
    }

    public function test_clients_are_forbidden_from_guest_endpoints(): void
    {
        $owner = $this->planner();
        $event = $this->event($owner);

        Sanctum::actingAs(User::factory()->accountType(AccountType::Client)->create());
        $this->getJson("/api/v1/events/{$event->id}/guests")->assertForbidden();
    }
}
