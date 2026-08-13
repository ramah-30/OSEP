<?php

namespace Tests\Feature\Marketplace;

use App\Enums\AccountType;
use App\Models\ActivityLog;
use App\Models\BookingRequest;
use App\Models\Event;
use App\Models\MarketplaceVenue;
use App\Models\Notification;
use App\Models\User;
use App\Models\VendorCategory;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Database\Seeders\VendorCategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MarketplaceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RoleSeeder::class, PermissionSeeder::class, VendorCategorySeeder::class]);
    }

    private function vendorUser(array $profile = []): User
    {
        $vendor = User::factory()->accountType(AccountType::Vendor)->create();
        $vendor->vendorProfile()->create(array_merge([
            'business_name' => 'Test Vendor Co.',
            'category' => 'Photographers',
            'verification_level' => 'business_verified',
            'availability_status' => 'available',
            'is_suspended' => false,
            'rating' => 4.5,
            'reviews_count' => 10,
        ], $profile));

        return $vendor;
    }

    private function venue(User $owner): MarketplaceVenue
    {
        return $owner->ownedVenues()->create([
            'name' => 'Grand Hall', 'setting' => 'indoor', 'capacity' => 300,
            'price' => 5_000_000, 'location' => 'Dar es Salaam',
            'verification_level' => 'business_verified', 'is_published' => true,
        ]);
    }

    public function test_planner_can_browse_and_view_a_vendor(): void
    {
        $this->vendorUser(['business_name' => 'Bright Photos', 'is_featured' => true]);
        $this->vendorUser(['is_suspended' => true, 'business_name' => 'Hidden Co.']);

        Sanctum::actingAs(User::factory()->accountType(AccountType::EventPlanner)->create());

        $body = $this->getJson('/api/v1/marketplace/vendors')->assertOk()->json('data');
        $this->assertCount(1, $body['vendors']); // suspended excluded
        $this->assertSame('Bright Photos', $body['vendors'][0]['business_name']);

        $vendorId = $body['vendors'][0]['id'];
        $this->getJson("/api/v1/marketplace/vendors/{$vendorId}")
            ->assertOk()
            ->assertJsonPath('data.vendor.business_name', 'Bright Photos');
    }

    public function test_suspended_vendor_storefront_is_hidden(): void
    {
        $vendor = $this->vendorUser(['is_suspended' => true]);
        Sanctum::actingAs(User::factory()->accountType(AccountType::EventPlanner)->create());

        $this->getJson("/api/v1/marketplace/vendors/{$vendor->id}")->assertNotFound();
    }

    public function test_planner_can_browse_venues(): void
    {
        $owner = $this->vendorUser();
        $this->venue($owner);

        Sanctum::actingAs(User::factory()->accountType(AccountType::EventPlanner)->create());

        $this->getJson('/api/v1/marketplace/venues')
            ->assertOk()
            ->assertJsonPath('data.venues.0.name', 'Grand Hall');
    }

    public function test_booking_request_lifecycle_from_request_to_signed_contract(): void
    {
        $vendor = $this->vendorUser();
        $planner = User::factory()->accountType(AccountType::EventPlanner)->create();

        // Planner sends a booking request.
        Sanctum::actingAs($planner);
        $requestId = $this->postJson('/api/v1/marketplace/booking-requests', [
            'provider_type' => 'vendor', 'provider_id' => $vendor->id,
            'title' => 'Coverage', 'guest_count' => 100, 'budget' => 3_000_000,
            'requirements' => 'Full day',
        ])->assertCreated()->json('data.booking_request.id');

        // Vendor sees it and accepts.
        Sanctum::actingAs($vendor);
        $this->getJson('/api/v1/marketplace/vendor/requests')
            ->assertOk()->assertJsonCount(1, 'data.booking_requests');
        $this->postJson("/api/v1/marketplace/vendor/requests/{$requestId}/respond", ['action' => 'accept'])
            ->assertOk()->assertJsonPath('data.booking_request.status', 'accepted');

        // Vendor drafts and sends a quotation.
        $quoteId = $this->postJson('/api/v1/marketplace/vendor/quotations', [
            'booking_request_id' => $requestId,
            'items' => [
                ['description' => 'Coverage', 'quantity' => 1, 'unit_price' => 2_000_000],
                ['description' => 'Album', 'quantity' => 1, 'unit_price' => 500_000],
            ],
            'tax' => 100_000,
        ])->assertCreated()->json('data.quotation.id');

        $this->postJson("/api/v1/marketplace/vendor/quotations/{$quoteId}/send")
            ->assertOk()->assertJsonPath('data.quotation.status', 'sent');

        // Planner accepts → a draft contract is generated.
        Sanctum::actingAs($planner);
        $accept = $this->postJson("/api/v1/marketplace/quotations/{$quoteId}/respond", ['action' => 'accept'])
            ->assertOk();
        $accept->assertJsonPath('data.quotation.status', 'accepted');
        $this->assertEquals(2_600_000, $accept->json('data.quotation.total'));
        $contractId = $accept->json('data.contract.id');
        $this->assertNotNull($contractId);

        // Vendor sends the contract, planner signs it.
        Sanctum::actingAs($vendor);
        $this->postJson("/api/v1/marketplace/vendor/contracts/{$contractId}/transition", ['status' => 'sent'])
            ->assertOk()->assertJsonPath('data.contract.status', 'sent');

        Sanctum::actingAs($planner);
        $this->postJson("/api/v1/marketplace/contracts/{$contractId}/sign")
            ->assertOk()->assertJsonPath('data.contract.status', 'signed');
    }

    public function test_client_is_notified_when_a_vendor_accepts_a_booking_for_their_event(): void
    {
        $vendor = $this->vendorUser();
        $planner = User::factory()->accountType(AccountType::EventPlanner)->create();
        $client = User::factory()->accountType(AccountType::Client)->create();
        $event = Event::create([
            'planner_id' => $planner->id,
            'client_id' => $client->id,
            'title' => "Amina's Wedding",
            'status' => 'planning',
        ]);

        // Planner requests a vendor for the client's event.
        Sanctum::actingAs($planner);
        $requestId = $this->postJson('/api/v1/marketplace/booking-requests', [
            'provider_type' => 'vendor', 'provider_id' => $vendor->id,
            'event_id' => $event->id, 'title' => 'Coverage', 'guest_count' => 100,
        ])->assertCreated()->json('data.booking_request.id');

        // Vendor accepts.
        Sanctum::actingAs($vendor);
        $this->postJson("/api/v1/marketplace/vendor/requests/{$requestId}/respond", ['action' => 'accept'])
            ->assertOk();

        // The client's event timeline gains a visible update...
        $this->assertDatabaseHas('activity_logs', [
            'event_id' => $event->id,
            'action' => 'vendor_booking_accepted',
            'visible_to_client' => true,
        ]);

        // ...and the client is pinged (the planner and vendor are not the target).
        $this->assertDatabaseHas('notifications', [
            'user_id' => $client->id,
            'type' => 'event_update',
        ]);
        $this->assertDatabaseMissing('notifications', [
            'user_id' => $planner->id,
            'type' => 'event_update',
        ]);

        // The client can read the update through their events feed.
        Sanctum::actingAs($client);
        $updates = $this->getJson('/api/v1/my-events')
            ->assertOk()->json('data.events.0.updates');
        $this->assertNotEmpty($updates);
        $this->assertSame('vendor_booking_accepted', $updates[0]['action']);
    }

    public function test_internal_booking_decline_does_not_reach_the_client(): void
    {
        $vendor = $this->vendorUser();
        $planner = User::factory()->accountType(AccountType::EventPlanner)->create();
        $client = User::factory()->accountType(AccountType::Client)->create();
        $event = Event::create([
            'planner_id' => $planner->id, 'client_id' => $client->id,
            'title' => 'Gala', 'status' => 'planning',
        ]);

        Sanctum::actingAs($planner);
        $requestId = $this->postJson('/api/v1/marketplace/booking-requests', [
            'provider_type' => 'vendor', 'provider_id' => $vendor->id,
            'event_id' => $event->id, 'title' => 'Coverage',
        ])->assertCreated()->json('data.booking_request.id');

        Sanctum::actingAs($vendor);
        $this->postJson("/api/v1/marketplace/vendor/requests/{$requestId}/respond", ['action' => 'decline'])
            ->assertOk();

        $this->assertDatabaseMissing('notifications', [
            'user_id' => $client->id, 'type' => 'event_update',
        ]);
        $this->assertSame(0, ActivityLog::where('event_id', $event->id)->where('visible_to_client', true)->count());
    }

    public function test_vendor_cannot_respond_to_another_vendors_request(): void
    {
        $vendorA = $this->vendorUser();
        $vendorB = $this->vendorUser();
        $planner = User::factory()->accountType(AccountType::EventPlanner)->create();

        $request = BookingRequest::create([
            'planner_id' => $planner->id, 'vendor_id' => $vendorA->id, 'status' => 'pending',
        ]);

        Sanctum::actingAs($vendorB);
        $this->postJson("/api/v1/marketplace/vendor/requests/{$request->id}/respond", ['action' => 'accept'])
            ->assertNotFound();
    }

    public function test_review_updates_provider_rating(): void
    {
        $vendor = $this->vendorUser(['rating' => null, 'reviews_count' => 0]);
        $planner = User::factory()->accountType(AccountType::EventPlanner)->create();

        Sanctum::actingAs($planner);
        $this->postJson('/api/v1/marketplace/reviews', [
            'provider_type' => 'vendor', 'provider_id' => $vendor->id,
            'professionalism' => 5, 'communication' => 4, 'quality' => 5, 'value' => 4, 'timeliness' => 5,
            'comment' => 'Great work',
        ])->assertCreated()->assertJsonPath('data.review.overall_rating', 4.6);

        $this->assertEqualsWithDelta(4.6, (float) $vendor->vendorProfile->fresh()->rating, 0.001);
        $this->assertSame(1, $vendor->vendorProfile->fresh()->reviews_count);
    }

    public function test_planner_can_save_a_vendor_to_a_collection(): void
    {
        $vendor = $this->vendorUser();
        $planner = User::factory()->accountType(AccountType::EventPlanner)->create();
        Sanctum::actingAs($planner);

        $collectionId = $this->postJson('/api/v1/marketplace/collections', ['name' => 'Wedding'])
            ->assertCreated()->json('data.collection.id');

        $this->postJson("/api/v1/marketplace/collections/{$collectionId}/items", [
            'provider_type' => 'vendor', 'provider_id' => $vendor->id,
        ])->assertCreated();

        $this->getJson('/api/v1/marketplace/collections')
            ->assertOk()->assertJsonPath('data.collections.0.items_count', 1);
    }

    public function test_messaging_between_planner_and_vendor(): void
    {
        $vendor = $this->vendorUser();
        $planner = User::factory()->accountType(AccountType::EventPlanner)->create();

        Sanctum::actingAs($planner);
        $threadId = $this->postJson('/api/v1/marketplace/messages', [
            'provider_type' => 'vendor', 'provider_id' => $vendor->id,
            'subject' => 'Hello', 'body' => 'Are you available?',
        ])->assertCreated()->json('data.thread.id');

        // Vendor sees the thread with one unread message and replies.
        Sanctum::actingAs($vendor);
        $this->getJson('/api/v1/marketplace/messages')
            ->assertOk()->assertJsonPath('data.threads.0.unread_count', 1);
        $this->postJson("/api/v1/marketplace/messages/{$threadId}", ['body' => 'Yes I am'])
            ->assertCreated();

        // Planner now has an unread reply.
        Sanctum::actingAs($planner);
        $this->getJson('/api/v1/marketplace/messages')
            ->assertOk()->assertJsonPath('data.threads.0.unread_count', 1);
    }

    public function test_admin_can_verify_suspend_and_feature_a_vendor(): void
    {
        $vendor = $this->vendorUser(['verification_level' => 'unverified']);
        Sanctum::actingAs(User::factory()->accountType(AccountType::Admin)->create());

        $this->putJson("/api/v1/admin/marketplace/vendors/{$vendor->id}/verify", ['level' => 'premium_partner'])
            ->assertOk()->assertJsonPath('data.vendor.verification_level', 'premium_partner');

        $this->putJson("/api/v1/admin/marketplace/vendors/{$vendor->id}/suspend", ['suspended' => true])
            ->assertOk();
        $this->assertTrue($vendor->vendorProfile->fresh()->is_suspended);

        $this->putJson("/api/v1/admin/marketplace/vendors/{$vendor->id}/feature", ['featured' => true])
            ->assertOk();
        $this->assertTrue($vendor->vendorProfile->fresh()->is_featured);
    }

    public function test_non_admin_cannot_reach_admin_tools(): void
    {
        Sanctum::actingAs(User::factory()->accountType(AccountType::EventPlanner)->create());
        $this->getJson('/api/v1/admin/marketplace/dashboard')->assertForbidden();
    }

    public function test_admin_can_create_a_custom_category(): void
    {
        Sanctum::actingAs(User::factory()->accountType(AccountType::Admin)->create());

        $this->postJson('/api/v1/admin/marketplace/categories', ['name' => 'Drone Services'])
            ->assertCreated()->assertJsonPath('data.category.is_custom', true);

        $this->assertDatabaseHas('vendor_categories', ['name' => 'Drone Services', 'is_custom' => true]);
    }
}
