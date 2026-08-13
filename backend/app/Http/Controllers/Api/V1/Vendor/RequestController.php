<?php

namespace App\Http\Controllers\Api\V1\Vendor;

use App\Http\Controllers\Api\V1\Vendor\Concerns\ScopesToProvider;
use App\Http\Controllers\Controller;
use App\Http\Resources\BookingRequestResource;
use App\Models\BookingRequest;
use App\Models\Notification;
use App\Services\ActivityLogger;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * The vendor's inbox of incoming booking requests, and their response.
 */
class RequestController extends Controller
{
    use ApiResponse, ScopesToProvider;

    public function index(Request $request): JsonResponse
    {
        $query = BookingRequest::query()->with(['planner', 'venue', 'event'])->withCount('quotations');
        $requests = $this->scopeToProvider($query, $request->user())
            ->when($request->query('status'), fn ($q, $s) => $q->where('status', $s))
            ->latest()->get();

        return $this->success([
            'booking_requests' => BookingRequestResource::collection($requests),
        ]);
    }

    public function respond(Request $request, BookingRequest $bookingRequest, ActivityLogger $activity): JsonResponse
    {
        abort_unless(
            $this->ownsRecord($request->user(), $bookingRequest->vendor_id, $bookingRequest->venue_id),
            404,
        );
        abort_unless($bookingRequest->status->isOpen(), 422, 'This request has already been actioned.');

        $data = $request->validate([
            'action' => ['required', Rule::in(['accept', 'decline', 'info'])],
            'note' => ['nullable', 'string', 'max:2000'],
        ]);

        $status = ['accept' => 'accepted', 'decline' => 'declined', 'info' => 'info_requested'][$data['action']];

        $bookingRequest->update([
            'status' => $status,
            'response_note' => $data['note'] ?? null,
            'responded_at' => now(),
        ]);

        Notification::create([
            'user_id' => $bookingRequest->planner_id,
            'type' => 'booking_'.$status,
            'title' => 'Booking request '.$bookingRequest->status->label(),
            'message' => $bookingRequest->providerName().' responded to your booking request.',
            'data' => ['booking_request_id' => $bookingRequest->id],
        ]);

        // Let the client know their event just gained a confirmed provider.
        if ($status === 'accepted' && $bookingRequest->event) {
            $activity->log(
                $bookingRequest->event,
                $request->user(),
                'vendor_booking_accepted',
                $bookingRequest->providerName().' confirmed their booking.',
                $bookingRequest,
                visibleToClient: true,
            );
        }

        return $this->success([
            'booking_request' => new BookingRequestResource($bookingRequest->load(['planner', 'venue', 'event'])),
        ], 'Response sent.');
    }
}
