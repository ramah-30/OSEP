<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\AuthorizesEventAccess;
use App\Http\Controllers\Controller;
use App\Http\Requests\UpsertVenueRequest;
use App\Http\Resources\VenueResource;
use App\Models\Event;
use App\Services\ActivityLogger;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * One venue record per event, created or updated in place.
 */
class VenueController extends Controller
{
    use ApiResponse, AuthorizesEventAccess;

    public function __construct(private readonly ActivityLogger $activity) {}

    public function show(Request $request, Event $event): JsonResponse
    {
        $this->ensurePlannerOwns($request, $event);

        $venue = $event->venueDetail;

        return $this->success([
            'venue' => $venue ? new VenueResource($venue) : null,
        ]);
    }

    public function upsert(UpsertVenueRequest $request, Event $event): JsonResponse
    {
        $this->ensurePlannerOwns($request, $event);

        $existed = $event->venueDetail()->exists();

        $venue = $event->venueDetail()->updateOrCreate([], $request->validated());

        $this->activity->log(
            $event,
            $request->user(),
            $existed ? 'venue_updated' : 'venue_added',
            $existed ? "updated the venue details" : "set the venue to \"{$venue->name}\"",
            $venue,
        );

        return $this->success([
            'venue' => new VenueResource($venue),
        ], 'Venue saved.');
    }
}
