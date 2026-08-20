<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\RsvpStatus;
use App\Http\Controllers\Api\V1\Concerns\AuthorizesEventAccess;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreGuestRequest;
use App\Http\Requests\UpdateGuestRequest;
use App\Http\Resources\GuestResource;
use App\Models\Event;
use App\Models\Guest;
use App\Models\Notification;
use App\Services\ActivityLogger;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * The client's own guest list for their event. A client may list, add, edit and
 * remove guests on the single event assigned to them — nothing else — and every
 * change pings the planner so the two lists never drift apart.
 */
class ClientGuestController extends Controller
{
    use ApiResponse, AuthorizesEventAccess;

    public function __construct(private readonly ActivityLogger $activity) {}

    public function index(Request $request, Event $event): JsonResponse
    {
        $this->ensureClientOwns($request, $event);

        $guests = $event->guests()
            ->whereNull('archived_at')
            ->orderBy('full_name')
            ->get();

        return $this->success([
            'event' => ['id' => $event->id, 'title' => $event->title],
            'guests' => GuestResource::collection($guests),
            'summary' => $this->summary($event),
        ]);
    }

    public function store(StoreGuestRequest $request, Event $event): JsonResponse
    {
        $this->ensureClientOwns($request, $event);

        $guest = $event->guests()->create([
            ...$request->validated(),
            'rsvp_status' => $request->validated()['rsvp_status'] ?? RsvpStatus::Pending->value,
        ]);

        $this->notifyPlanner($request, $event, 'added', $guest);

        return $this->created(['guest' => new GuestResource($guest->refresh())], 'Guest added.');
    }

    public function update(UpdateGuestRequest $request, Event $event, Guest $guest): JsonResponse
    {
        $this->ensureClientOwns($request, $event);
        $this->ensureBelongsToEvent($event, $guest);

        $guest->fill($request->validated())->save();

        $this->notifyPlanner($request, $event, 'updated', $guest);

        return $this->success(['guest' => new GuestResource($guest->refresh())], 'Guest updated.');
    }

    public function destroy(Request $request, Event $event, Guest $guest): JsonResponse
    {
        $this->ensureClientOwns($request, $event);
        $this->ensureBelongsToEvent($event, $guest);

        $name = $guest->full_name;
        $guest->delete();

        $this->notifyPlanner($request, $event, 'removed', $guest, $name);

        return $this->success(null, 'Guest removed.');
    }

    /**
     * Tell the planner their client just changed the guest list, and drop a row
     * on the event's activity feed (planner-facing — not the client's own feed).
     */
    private function notifyPlanner(Request $request, Event $event, string $verb, Guest $guest, ?string $name = null): void
    {
        $client = $request->user();
        $guestName = $name ?? $guest->full_name;

        Notification::create([
            'user_id' => $event->planner_id,
            'type' => 'client_guest_'.$verb,
            'title' => $event->title,
            'message' => "{$client->full_name} {$verb} a guest: {$guestName}.",
            'data' => ['event_id' => $event->id, 'guest_id' => $guest->id],
        ]);

        $this->activity->log(
            $event,
            $client,
            'client_guest_'.$verb,
            "Client {$verb} a guest ({$guestName}).",
            $verb === 'removed' ? null : $guest,
        );
    }

    /**
     * @return array<string, int>
     */
    private function summary(Event $event): array
    {
        $base = $event->guests()->whereNull('archived_at');

        return [
            'total' => (clone $base)->count(),
            'confirmed' => (clone $base)->where('rsvp_status', RsvpStatus::Confirmed->value)->count(),
            'declined' => (clone $base)->where('rsvp_status', RsvpStatus::Declined->value)->count(),
            'pending' => (clone $base)->whereIn('rsvp_status', RsvpStatus::pendingStates())->count(),
        ];
    }
}
