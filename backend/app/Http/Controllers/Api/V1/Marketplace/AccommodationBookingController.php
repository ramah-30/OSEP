<?php

namespace App\Http\Controllers\Api\V1\Marketplace;

use App\Enums\AccommodationBookingStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\AccommodationBookingResource;
use App\Models\AccommodationBooking;
use App\Models\AccommodationRoomType;
use App\Models\Event;
use App\Models\Notification;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

/**
 * A planner's hotel room bookings — the honeymoon stays they reserve for clients.
 * Booking snapshots the nightly rate and checks room inventory across overlapping
 * stays so a room type can't be oversold.
 */
class AccommodationBookingController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $bookings = AccommodationBooking::where('planner_id', $request->user()->id)
            ->with(['accommodation', 'roomType', 'client', 'event'])
            ->latest()
            ->get();

        return $this->success([
            'bookings' => AccommodationBookingResource::collection($bookings),
        ]);
    }

    public function show(Request $request, AccommodationBooking $booking): JsonResponse
    {
        abort_unless($booking->planner_id === $request->user()->id, 404);
        $booking->load(['accommodation', 'roomType', 'client', 'event']);

        return $this->success(['booking' => new AccommodationBookingResource($booking)]);
    }

    public function store(Request $request): JsonResponse
    {
        $planner = $request->user();

        $data = $request->validate([
            'room_type_id' => ['required', 'integer', 'exists:accommodation_room_types,id'],
            'check_in' => ['required', 'date', 'after_or_equal:today'],
            'check_out' => ['required', 'date', 'after:check_in'],
            'rooms' => ['required', 'integer', 'min:1', 'max:20'],
            'guests' => ['required', 'integer', 'min:1', 'max:50'],
            'guest_name' => ['required', 'string', 'max:255'],
            'client_id' => ['nullable', 'integer', 'exists:users,id'],
            'event_id' => ['nullable', 'integer', 'exists:events,id'],
            'special_requests' => ['nullable', 'string', 'max:2000'],
        ]);

        /** @var AccommodationRoomType $roomType */
        $roomType = AccommodationRoomType::with('accommodation')->findOrFail($data['room_type_id']);
        abort_if($roomType->accommodation === null || ! $roomType->accommodation->is_published, 404, 'Hotel not found.');

        $checkIn = Carbon::parse($data['check_in'])->startOfDay();
        $checkOut = Carbon::parse($data['check_out'])->startOfDay();
        $nights = (int) $checkIn->diffInDays($checkOut);

        // Guest capacity: total beds across the booked rooms must fit the party.
        if ($data['guests'] > $roomType->capacity * $data['rooms']) {
            throw ValidationException::withMessages([
                'guests' => "{$data['rooms']} × {$roomType->name} sleeps up to " . ($roomType->capacity * $data['rooms']) . " guests.",
            ]);
        }

        // Inventory: rooms already held over any overlapping night.
        $held = $this->roomsHeld($roomType, $checkIn, $checkOut);
        if ($held + $data['rooms'] > $roomType->total_rooms) {
            $free = max(0, $roomType->total_rooms - $held);
            throw ValidationException::withMessages([
                'rooms' => "Only {$free} {$roomType->name} room(s) are available for those dates.",
            ]);
        }

        // Scope client/event to the planner's own where given.
        $clientId = $this->resolveClientId($planner, $data['client_id'] ?? null);
        $eventId = $this->resolveEventId($planner, $data['event_id'] ?? null);

        $booking = AccommodationBooking::create([
            'reference' => AccommodationBooking::nextReference(),
            'accommodation_id' => $roomType->accommodation_id,
            'room_type_id' => $roomType->id,
            'planner_id' => $planner->id,
            'client_id' => $clientId,
            'event_id' => $eventId,
            'guest_name' => $data['guest_name'],
            'check_in' => $checkIn->toDateString(),
            'check_out' => $checkOut->toDateString(),
            'nights' => $nights,
            'rooms' => $data['rooms'],
            'guests' => $data['guests'],
            'price_per_night' => $roomType->price_per_night,
            'total_price' => (float) $roomType->price_per_night * $nights * $data['rooms'],
            'currency' => $roomType->currency,
            'status' => AccommodationBookingStatus::Confirmed,
            'special_requests' => $data['special_requests'] ?? null,
        ]);

        if ($clientId) {
            Notification::create([
                'user_id' => $clientId,
                'type' => 'accommodation_booked',
                'title' => 'Accommodation booked',
                'message' => "Your planner reserved {$roomType->accommodation->name} ({$roomType->name}) for {$nights} night(s).",
                'data' => ['booking_id' => $booking->id],
            ]);
        }

        return $this->created([
            'booking' => new AccommodationBookingResource(
                $booking->load(['accommodation', 'roomType', 'client', 'event'])
            ),
        ], 'Room booked — the reservation is confirmed.');
    }

    public function cancel(Request $request, AccommodationBooking $booking): JsonResponse
    {
        abort_unless($booking->planner_id === $request->user()->id, 404);

        if ($booking->status === AccommodationBookingStatus::Cancelled) {
            return $this->error('This booking is already cancelled.', null, 422);
        }

        $booking->update(['status' => AccommodationBookingStatus::Cancelled]);

        return $this->success([
            'booking' => new AccommodationBookingResource($booking->fresh(['accommodation', 'roomType', 'client', 'event'])),
        ], 'Booking cancelled.');
    }

    /** Rooms of this type held by confirmed bookings overlapping [in, out). */
    private function roomsHeld(AccommodationRoomType $roomType, Carbon $checkIn, Carbon $checkOut): int
    {
        return (int) AccommodationBooking::where('room_type_id', $roomType->id)
            ->where('status', AccommodationBookingStatus::Confirmed->value)
            ->where('check_in', '<', $checkOut->toDateString())
            ->where('check_out', '>', $checkIn->toDateString())
            ->sum('rooms');
    }

    private function resolveClientId(User $planner, ?int $clientId): ?int
    {
        if (! $clientId) {
            return null;
        }

        // Only accept a client the planner actually works with (has an event for).
        $ok = Event::where('planner_id', $planner->id)->where('client_id', $clientId)->exists();

        return $ok ? $clientId : null;
    }

    private function resolveEventId(User $planner, ?int $eventId): ?int
    {
        if (! $eventId) {
            return null;
        }

        return Event::where('planner_id', $planner->id)->whereKey($eventId)->exists() ? $eventId : null;
    }
}
