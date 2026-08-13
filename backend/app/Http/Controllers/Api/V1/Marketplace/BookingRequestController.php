<?php

namespace App\Http\Controllers\Api\V1\Marketplace;

use App\Http\Controllers\Api\V1\Marketplace\Concerns\ResolvesProvider;
use App\Http\Controllers\Controller;
use App\Http\Resources\BookingRequestResource;
use App\Models\BookingRequest;
use App\Models\Event;
use App\Models\MarketplaceVenue;
use App\Models\Notification;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Planner-side booking requests to vendors and venues. The vendor's response
 * (accept / decline / ask for more) lives in the Vendor namespace.
 */
class BookingRequestController extends Controller
{
    use ApiResponse, ResolvesProvider;

    public function index(Request $request): JsonResponse
    {
        $requests = BookingRequest::query()
            ->where('planner_id', $request->user()->id)
            ->when($request->query('status'), fn ($q, $s) => $q->where('status', $s))
            ->with(['vendor.vendorProfile', 'venue', 'event'])
            ->withCount('quotations')
            ->latest()->get();

        return $this->success([
            'booking_requests' => BookingRequestResource::collection($requests),
        ]);
    }

    public function show(Request $request, BookingRequest $bookingRequest): JsonResponse
    {
        $this->authorizeOwner($request, $bookingRequest);

        return $this->success([
            'booking_request' => new BookingRequestResource(
                $bookingRequest->load(['vendor.vendorProfile', 'venue', 'event', 'quotations.items'])
            ),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'provider_type' => ['required', Rule::in(['vendor', 'venue'])],
            'provider_id' => ['required', 'integer'],
            'event_id' => ['nullable', 'integer', 'exists:events,id'],
            'title' => ['nullable', 'string', 'max:150'],
            'event_date' => ['nullable', 'date'],
            'guest_count' => ['nullable', 'integer', 'min:0'],
            'budget' => ['nullable', 'numeric', 'min:0'],
            'requirements' => ['nullable', 'string', 'max:5000'],
            'attachments' => ['nullable', 'array'],
        ]);

        $provider = $this->resolveProvider($data['provider_type'], (int) $data['provider_id']);

        // A planner may only attach one of their own events.
        if (! empty($data['event_id'])) {
            abort_unless(
                Event::where('id', $data['event_id'])->where('planner_id', $request->user()->id)->exists(),
                422,
            );
        }

        $booking = BookingRequest::create([
            'planner_id' => $request->user()->id,
            ...$provider,
            'event_id' => $data['event_id'] ?? null,
            'title' => $data['title'] ?? null,
            'event_date' => $data['event_date'] ?? null,
            'guest_count' => $data['guest_count'] ?? null,
            'budget' => $data['budget'] ?? null,
            'requirements' => $data['requirements'] ?? null,
            'attachments' => $data['attachments'] ?? null,
            'status' => 'pending',
        ]);

        $this->notifyProvider($booking, $request->user()->full_name);

        return $this->created([
            'booking_request' => new BookingRequestResource($booking->load(['vendor.vendorProfile', 'venue', 'event'])),
        ], 'Booking request sent.');
    }

    public function withdraw(Request $request, BookingRequest $bookingRequest): JsonResponse
    {
        $this->authorizeOwner($request, $bookingRequest);
        abort_unless($bookingRequest->status->isOpen(), 422, 'This request can no longer be withdrawn.');

        $bookingRequest->update(['status' => 'withdrawn']);

        return $this->success([
            'booking_request' => new BookingRequestResource($bookingRequest),
        ], 'Booking request withdrawn.');
    }

    private function authorizeOwner(Request $request, BookingRequest $bookingRequest): void
    {
        abort_unless($bookingRequest->planner_id === $request->user()->id, 404);
    }

    private function notifyProvider(BookingRequest $booking, string $plannerName): void
    {
        $ownerId = $booking->vendor_id
            ?? MarketplaceVenue::whereKey($booking->venue_id)->value('owner_id');

        if (! $ownerId) {
            return;
        }

        Notification::create([
            'user_id' => $ownerId,
            'type' => 'booking_request',
            'title' => 'New booking request',
            'message' => "{$plannerName} sent you a booking request.",
            'data' => ['booking_request_id' => $booking->id],
        ]);
    }
}
