<?php

namespace Tests\Feature\Marketplace;

use App\Enums\AccountType;
use App\Models\Accommodation;
use App\Models\AccommodationRoomType;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AccommodationTest extends TestCase
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

    private function hotelWithRoom(array $room = []): AccommodationRoomType
    {
        $hotel = Accommodation::create([
            'name' => 'Zanzibar Pearl Resort', 'city' => 'Zanzibar', 'star_rating' => 5,
            'price_from' => 400000, 'is_published' => true,
        ]);

        return $hotel->roomTypes()->create(array_merge([
            'name' => 'Honeymoon Suite', 'price_per_night' => 500000, 'currency' => 'TZS',
            'capacity' => 2, 'total_rooms' => 1, 'is_active' => true,
        ], $room));
    }

    public function test_browse_lists_published_hotels(): void
    {
        Sanctum::actingAs($this->planner());
        $this->hotelWithRoom();
        Accommodation::create(['name' => 'Hidden Inn', 'is_published' => false, 'price_from' => 100000]);

        $res = $this->getJson('/api/v1/marketplace/accommodations')->assertOk();

        $this->assertCount(1, $res->json('data.accommodations'));
        $res->assertJsonPath('data.accommodations.0.name', 'Zanzibar Pearl Resort');
    }

    public function test_show_returns_hotel_with_room_types(): void
    {
        Sanctum::actingAs($this->planner());
        $room = $this->hotelWithRoom();

        $this->getJson("/api/v1/marketplace/accommodations/{$room->accommodation->slug}")->assertOk()
            ->assertJsonPath('data.accommodation.name', 'Zanzibar Pearl Resort')
            ->assertJsonCount(1, 'data.accommodation.room_types')
            ->assertJsonPath('data.accommodation.room_types.0.name', 'Honeymoon Suite');
    }

    public function test_planner_can_book_a_room_and_total_is_computed(): void
    {
        $planner = $this->planner();
        Sanctum::actingAs($planner);
        $room = $this->hotelWithRoom(['total_rooms' => 3]);

        $res = $this->postJson('/api/v1/marketplace/accommodation-bookings', [
            'room_type_id' => $room->id,
            'check_in' => now()->addDays(10)->toDateString(),
            'check_out' => now()->addDays(13)->toDateString(),
            'rooms' => 1, 'guests' => 2, 'guest_name' => 'Mr & Mrs Carter',
        ])->assertCreated();

        $res->assertJsonPath('data.booking.nights', 3)
            ->assertJsonPath('data.booking.total_price', 1500000) // 500k × 3 nights
            ->assertJsonPath('data.booking.status', 'confirmed');

        $this->assertDatabaseHas('accommodation_bookings', [
            'planner_id' => $planner->id, 'guest_name' => 'Mr & Mrs Carter', 'nights' => 3,
        ]);
    }

    public function test_booking_is_rejected_when_the_room_type_is_sold_out(): void
    {
        $planner = $this->planner();
        Sanctum::actingAs($planner);
        $room = $this->hotelWithRoom(['total_rooms' => 1]);

        $payload = fn ($in, $out) => [
            'room_type_id' => $room->id, 'check_in' => now()->addDays($in)->toDateString(),
            'check_out' => now()->addDays($out)->toDateString(), 'rooms' => 1, 'guests' => 2, 'guest_name' => 'A',
        ];

        $this->postJson('/api/v1/marketplace/accommodation-bookings', $payload(10, 13))->assertCreated();
        // Overlapping nights, only one room in inventory → rejected.
        $this->postJson('/api/v1/marketplace/accommodation-bookings', $payload(11, 14))
            ->assertStatus(422)->assertJsonValidationErrors('rooms');
    }

    public function test_booking_is_rejected_when_guests_exceed_capacity(): void
    {
        Sanctum::actingAs($this->planner());
        $room = $this->hotelWithRoom(['capacity' => 2, 'total_rooms' => 5]);

        $this->postJson('/api/v1/marketplace/accommodation-bookings', [
            'room_type_id' => $room->id, 'check_in' => now()->addDays(5)->toDateString(),
            'check_out' => now()->addDays(7)->toDateString(), 'rooms' => 1, 'guests' => 4, 'guest_name' => 'A',
        ])->assertStatus(422)->assertJsonValidationErrors('guests');
    }

    public function test_planner_can_cancel_their_booking(): void
    {
        $planner = $this->planner();
        Sanctum::actingAs($planner);
        $room = $this->hotelWithRoom(['total_rooms' => 2]);

        $id = $this->postJson('/api/v1/marketplace/accommodation-bookings', [
            'room_type_id' => $room->id, 'check_in' => now()->addDays(5)->toDateString(),
            'check_out' => now()->addDays(6)->toDateString(), 'rooms' => 1, 'guests' => 2, 'guest_name' => 'A',
        ])->json('data.booking.id');

        $this->postJson("/api/v1/marketplace/accommodation-bookings/{$id}/cancel")->assertOk()
            ->assertJsonPath('data.booking.status', 'cancelled');

        // Cancelled inventory is freed - the same dates can be rebooked.
        $this->postJson('/api/v1/marketplace/accommodation-bookings', [
            'room_type_id' => $room->id, 'check_in' => now()->addDays(5)->toDateString(),
            'check_out' => now()->addDays(6)->toDateString(), 'rooms' => 2, 'guests' => 2, 'guest_name' => 'B',
        ])->assertCreated();
    }

    public function test_a_client_cannot_book_accommodation(): void
    {
        Sanctum::actingAs(User::factory()->accountType(AccountType::Client)->create());
        $room = $this->hotelWithRoom();

        $this->postJson('/api/v1/marketplace/accommodation-bookings', [
            'room_type_id' => $room->id, 'check_in' => now()->addDays(5)->toDateString(),
            'check_out' => now()->addDays(6)->toDateString(), 'rooms' => 1, 'guests' => 2, 'guest_name' => 'A',
        ])->assertForbidden();
    }
}
