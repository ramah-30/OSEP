<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\AccountType;
use App\Enums\BookingRequestStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBookingRequestRequest;
use App\Http\Resources\PlannerBookingRequestResource;
use App\Models\Notification;
use App\Models\PlannerBookingRequest;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Client-side booking flow: browse, request, and withdraw planner bookings.
 */
class ClientBookingController extends Controller
{
    use ApiResponse;

    /** The client's own booking request history. */
    public function index(Request $request): JsonResponse
    {
        $requests = $request->user()
            ->bookingRequestsAsClient()
            ->with(['planner.plannerProfile', 'event'])
            ->get();

        return $this->success([
            'requests' => PlannerBookingRequestResource::collection($requests),
        ]);
    }

    /** Send a booking request to a planner. */
    public function store(StoreBookingRequestRequest $request): JsonResponse
    {
        $client  = $request->user();
        $data    = $request->validated();
        $planner = User::find($data['planner_id']);

        if (! $planner || $planner->account_type !== AccountType::EventPlanner) {
            return $this->error('Planner not found.', 404);
        }

        // One pending request per client-planner pair at a time.
        $existing = PlannerBookingRequest::where('client_id', $client->id)
            ->where('planner_id', $planner->id)
            ->where('status', BookingRequestStatus::Pending)
            ->exists();

        if ($existing) {
            return $this->error('You already have a pending request with this planner.', 422);
        }

        $bookingRequest = PlannerBookingRequest::create([
            'reference'       => PlannerBookingRequest::nextReference(),
            'planner_id'      => $planner->id,
            'client_id'       => $client->id,
            'event_type'      => $data['event_type'] ?? null,
            'event_date'      => $data['event_date'] ?? null,
            'expected_guests' => $data['expected_guests'] ?? null,
            'proposed_budget' => $data['proposed_budget'] ?? null,
            'venue'           => $data['venue'] ?? null,
            'location'        => $data['location'] ?? null,
            'message'         => $data['message'] ?? null,
            'status'          => BookingRequestStatus::Pending,
        ]);

        Notification::create([
            'user_id' => $planner->id,
            'type'    => 'new_booking_request',
            'title'   => 'New booking request',
            'message' => "{$client->full_name} wants to book your services.",
            'data'    => ['request_id' => $bookingRequest->id],
        ]);

        return $this->created([
            'request' => new PlannerBookingRequestResource($bookingRequest->load(['planner.plannerProfile'])),
        ], 'Booking request sent.');
    }

    /** Withdraw a pending booking request. */
    public function withdraw(Request $request, PlannerBookingRequest $bookingRequest): JsonResponse
    {
        if ($bookingRequest->client_id !== $request->user()->id) {
            return $this->error('Not found.', 404);
        }

        if ($bookingRequest->status !== BookingRequestStatus::Pending) {
            return $this->error('Only pending requests can be withdrawn.', 422);
        }

        $bookingRequest->update(['status' => BookingRequestStatus::Withdrawn]);

        return $this->success(['request' => new PlannerBookingRequestResource($bookingRequest->fresh(['planner.plannerProfile']))]);
    }
}
