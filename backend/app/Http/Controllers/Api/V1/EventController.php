<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\ApprovalStatus;
use App\Enums\EventStatus;
use App\Http\Controllers\Api\V1\Concerns\AuthorizesEventAccess;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreEventRequest;
use App\Http\Requests\UpdateEventRequest;
use App\Http\Requests\UpdateEventStatusRequest;
use App\Http\Resources\EventResource;
use App\Models\Event;
use App\Models\Notification;
use App\Services\ActivityLogger;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * The planner-facing event engine plus the client's read-only window (myEvent).
 */
class EventController extends Controller
{
    use ApiResponse, AuthorizesEventAccess;

    public function __construct(private readonly ActivityLogger $activity) {}

    // -----------------------------------------------------------------
    // Planner
    // -----------------------------------------------------------------

    public function index(Request $request): JsonResponse
    {
        $query = $request->user()->plannedEvents()
            ->with('client')
            ->withCount(['tasks', 'guests', 'vendorAssignments', 'documents'])
            ->withCount(['approvals as open_approvals_count' => fn ($q) => $q->where('status', ApprovalStatus::Pending->value)]);

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        if ($type = $request->query('event_type')) {
            $query->where('event_type', $type);
        }

        if ($priority = $request->query('priority')) {
            $query->where('priority', $priority);
        }

        if ($clientId = $request->query('client_id')) {
            $query->where('client_id', $clientId);
        }

        if ($search = $request->query('q')) {
            $query->where(fn ($q) => $q
                ->where('title', 'like', "%{$search}%")
                ->orWhere('event_code', 'like', "%{$search}%")
                ->orWhere('location', 'like', "%{$search}%"));
        }

        if ($from = $request->query('from')) {
            $query->whereDate('event_date', '>=', $from);
        }

        if ($to = $request->query('to')) {
            $query->whereDate('event_date', '<=', $to);
        }

        $sort = $request->query('sort', 'event_date');
        $direction = $request->query('direction', 'asc');
        if (in_array($sort, ['event_date', 'title', 'created_at', 'progress', 'status'], true)) {
            $query->orderBy($sort, $direction === 'desc' ? 'desc' : 'asc');
        }

        $events = $query->paginate((int) $request->query('per_page', 15))->withQueryString();

        return $this->success([
            'events' => EventResource::collection($events),
            'meta' => [
                'current_page' => $events->currentPage(),
                'last_page' => $events->lastPage(),
                'per_page' => $events->perPage(),
                'total' => $events->total(),
            ],
        ]);
    }

    public function store(StoreEventRequest $request): JsonResponse
    {
        $data = $request->validated();

        $event = $request->user()->plannedEvents()->create([
            ...$data,
            'event_code' => Event::nextCode(),
            'status' => $data['status'] ?? EventStatus::Planning->value,
            'priority' => $data['priority'] ?? 'medium',
        ]);

        $this->activity->log($event, $request->user(), 'event_created', "created the event \"{$event->title}\"", $event);

        if ($event->client_id) {
            $this->notifyClient($event, 'You have a new event', "{$request->user()->full_name} added you to \"{$event->title}\".");
        }

        return $this->created([
            'event' => new EventResource($event->load('client')),
        ], 'Event created.');
    }

    public function show(Request $request, Event $event): JsonResponse
    {
        $this->ensurePlannerOwns($request, $event);

        $event->load([
            'planner.plannerProfile',
            'client',
            'milestones.assignee',
            'tasks.assignee',
            'tasks.dependencies',
            'guests',
            'venueDetail',
            'vendorAssignments.vendor.vendorProfile',
            'budgetItems.vendorAssignment',
            'approvals.history.user',
            'documents.uploader',
            'activities.user',
        ])->loadCount(['tasks', 'guests', 'vendorAssignments', 'documents']);

        return $this->success([
            'event' => new EventResource($event),
        ]);
    }

    public function update(UpdateEventRequest $request, Event $event): JsonResponse
    {
        $this->ensurePlannerOwns($request, $event);

        $event->fill($request->validated())->save();

        $this->activity->log($event, $request->user(), 'event_updated', "updated event details", $event);

        return $this->success([
            'event' => new EventResource($event->load('client')),
        ], 'Event updated.');
    }

    public function updateStatus(UpdateEventStatusRequest $request, Event $event): JsonResponse
    {
        $this->ensurePlannerOwns($request, $event);

        $target = EventStatus::from($request->validated()['status']);

        if (! $event->status->canTransitionTo($target)) {
            return $this->error("Cannot move an event from {$event->status->label()} to {$target->label()}.", null, 422);
        }

        $from = $event->status;
        $event->forceFill(['status' => $target->value])->save();

        $this->activity->log(
            $event,
            $request->user(),
            'status_changed',
            "moved the event from {$from->label()} to {$target->label()}",
            $event,
            ['from' => $from->value, 'to' => $target->value],
        );

        if ($target === EventStatus::ClientApproval && $event->client_id) {
            $this->notifyClient($event, 'Your review is needed', "\"{$event->title}\" is ready for your approval.");
        }

        return $this->success([
            'event' => new EventResource($event->load('client')),
        ], 'Event status updated.');
    }

    public function destroy(Request $request, Event $event): JsonResponse
    {
        $this->ensurePlannerOwns($request, $event);

        $event->delete();

        return $this->success(null, 'Event deleted.');
    }

    // -----------------------------------------------------------------
    // Client (read-only window onto their event)
    // -----------------------------------------------------------------

    public function myEvent(Request $request): JsonResponse
    {
        $event = $request->user()
            ->clientEvent()
            ->with(['planner.plannerProfile', 'milestones', 'approvals'])
            ->first();

        if (! $event) {
            return $this->success(['event' => null], 'No event has been assigned to you yet.');
        }

        return $this->success(['event' => new EventResource($event)]);
    }

    /**
     * Every event this client owns - populated automatically as planners accept
     * their booking requests. Powers the client's "My Events" and "Progress".
     */
    public function myEvents(Request $request): JsonResponse
    {
        $events = $request->user()
            ->clientEvents()
            ->with(['planner.plannerProfile', 'milestones', 'approvals', 'clientUpdates.user'])
            ->get();

        return $this->success(['events' => EventResource::collection($events)]);
    }

    private function notifyClient(Event $event, string $title, string $message): void
    {
        Notification::create([
            'user_id' => $event->client_id,
            'type' => 'event_update',
            'title' => $title,
            'message' => $message,
            'data' => ['event_id' => $event->id],
        ]);
    }
}
