<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\BookingRequestStatus;
use App\Enums\EventStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\RespondBookingRequestRequest;
use App\Http\Resources\PlannerBookingRequestResource;
use App\Models\Budget;
use App\Models\Event;
use App\Models\Notification;
use App\Models\PlannerBookingRequest;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Planner-side inbox for client booking requests.
 */
class BookingRequestController extends Controller
{
    use ApiResponse;

    /** List all incoming booking requests for the authenticated planner. */
    public function index(Request $request): JsonResponse
    {
        $requests = $request->user()
            ->bookingRequestsAsPlanner()
            ->with(['client', 'event'])
            ->get();

        return $this->success([
            'requests' => PlannerBookingRequestResource::collection($requests),
        ]);
    }

    public function show(Request $request, PlannerBookingRequest $bookingRequest): JsonResponse
    {
        if ($bookingRequest->planner_id !== $request->user()->id) {
            return $this->error('Not found.', 404);
        }

        $bookingRequest->load(['client', 'event']);

        return $this->success(['request' => new PlannerBookingRequestResource($bookingRequest)]);
    }

    /** Accept or decline a pending booking request. */
    public function respond(RespondBookingRequestRequest $request, PlannerBookingRequest $bookingRequest): JsonResponse
    {
        $planner = $request->user();

        if ($bookingRequest->planner_id !== $planner->id) {
            return $this->error('Not found.', 404);
        }

        if ($bookingRequest->status !== BookingRequestStatus::Pending) {
            return $this->error('This request has already been responded to.', 422);
        }

        $data = $request->validated();

        if ($data['decision'] === 'accepted') {
            return $this->accept($planner, $bookingRequest, $data['planner_note'] ?? null, $data['quoted_budget'] ?? null);
        }

        $bookingRequest->update([
            'status'       => BookingRequestStatus::Declined,
            'planner_note' => $data['planner_note'] ?? null,
        ]);

        Notification::create([
            'user_id' => $bookingRequest->client_id,
            'type'    => 'booking_declined',
            'title'   => 'Booking request declined',
            'message' => "{$planner->full_name} was unable to take your request.",
            'data'    => ['request_id' => $bookingRequest->id],
        ]);

        return $this->success(['request' => new PlannerBookingRequestResource($bookingRequest->fresh(['client']))]);
    }

    private function accept(
        \App\Models\User $planner,
        PlannerBookingRequest $bookingRequest,
        ?string $plannerNote,
        ?float $quotedBudget = null,
    ): JsonResponse {
        $client = $bookingRequest->client()->first();

        $label = $bookingRequest->event_type
            ? ucfirst($bookingRequest->event_type)." for {$client->first_name}"
            : "{$client->first_name}'s Event";

        $event = Event::create([
            'planner_id'      => $planner->id,
            'client_id'       => $bookingRequest->client_id,
            'event_code'      => Event::nextCode(),
            'title'           => $label,
            'event_type'      => $bookingRequest->event_type,
            'event_date'      => $bookingRequest->event_date,
            'venue'           => $bookingRequest->venue,
            'location'        => $bookingRequest->location,
            'expected_guests' => $bookingRequest->expected_guests,
            'budget_total'    => $quotedBudget ?? 0,
            'status'          => EventStatus::Planning,
            'source'          => 'booking_accepted',
            'progress'        => 0,
        ]);

        $bookingRequest->update([
            'status'        => BookingRequestStatus::Accepted,
            'planner_note'  => $plannerNote,
            'quoted_budget' => $quotedBudget,
            'event_id'      => $event->id,
        ]);

        // A quote becomes the new event's starting budget, so the workspace
        // isn't empty the moment it appears.
        if ($quotedBudget !== null) {
            Budget::create([
                'event_id' => $event->id,
                'currency' => 'TZS',
                'estimated_total' => $quotedBudget,
            ]);
        }

        Notification::create([
            'user_id' => $bookingRequest->client_id,
            'type'    => 'booking_accepted',
            'title'   => 'Booking accepted!',
            'message' => "{$planner->full_name} accepted your request. Your event is ready.",
            'data'    => [
                'request_id' => $bookingRequest->id,
                'event_id'   => $event->id,
            ],
        ]);

        return $this->success([
            'request' => new PlannerBookingRequestResource($bookingRequest->fresh(['client', 'event'])),
        ]);
    }
}
