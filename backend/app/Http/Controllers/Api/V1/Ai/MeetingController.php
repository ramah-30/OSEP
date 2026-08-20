<?php

namespace App\Http\Controllers\Api\V1\Ai;

use App\Http\Controllers\Controller;
use App\Http\Resources\AiMeetingResource;
use App\Models\AiMeeting;
use App\Models\AiMeetingActionItem;
use App\Models\Event;
use App\Models\EventTask;
use App\Services\AI\MeetingProcessor;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * The meeting assistant. The planner captures raw notes; processing produces a
 * grounded summary and structured action items, which can then be pushed into
 * the event's real task board. Everything is scoped to the authenticated planner.
 */
class MeetingController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly MeetingProcessor $processor) {}

    public function index(Request $request): JsonResponse
    {
        $meetings = AiMeeting::where('user_id', $request->user()->id)
            ->with('event:id,title')
            ->withCount([
                'actionItems as action_items_count',
                'actionItems as open_actions_count' => fn ($q) => $q->where('status', 'open'),
            ])
            ->orderByDesc('created_at')
            ->get();

        return $this->success([
            'meetings' => AiMeetingResource::collection($meetings),
        ]);
    }

    public function show(Request $request, AiMeeting $meeting): JsonResponse
    {
        $this->authorizeMeeting($request, $meeting);
        $meeting->load(['event:id,title', 'actionItems']);

        return $this->success([
            'meeting' => new AiMeetingResource($meeting),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validated($request);

        $meeting = AiMeeting::create(array_merge($data, [
            'user_id' => $request->user()->id,
            'status' => 'captured',
        ]));

        return $this->created([
            'meeting' => new AiMeetingResource($meeting->load('event:id,title')),
        ], 'Meeting captured.');
    }

    public function update(Request $request, AiMeeting $meeting): JsonResponse
    {
        $this->authorizeMeeting($request, $meeting);
        $meeting->update($this->validated($request, partial: true));

        return $this->success([
            'meeting' => new AiMeetingResource($meeting->fresh(['event:id,title', 'actionItems'])),
        ], 'Meeting updated.');
    }

    public function destroy(Request $request, AiMeeting $meeting): JsonResponse
    {
        $this->authorizeMeeting($request, $meeting);
        $meeting->delete();

        return $this->success(null, 'Meeting deleted.');
    }

    /**
     * Run the notes through the engine: replace the summary and re-extract the
     * action items. Items already pushed to a task are preserved so re-processing
     * never orphans real tasks.
     */
    public function process(Request $request, AiMeeting $meeting): JsonResponse
    {
        $this->authorizeMeeting($request, $meeting);
        $result = $this->processor->process($request->user(), $meeting);

        DB::transaction(function () use ($meeting, $result, $request) {
            // Keep converted items (they own a real task); regenerate the rest.
            $meeting->actionItems()->whereNull('task_id')->delete();

            $offset = (int) $meeting->actionItems()->max('position');
            foreach ($result['action_items'] as $i => $item) {
                $meeting->actionItems()->create([
                    'user_id' => $request->user()->id,
                    'description' => $item['description'],
                    'owner' => $item['owner'],
                    'due_date' => $item['due_date'],
                    'status' => 'open',
                    'position' => $offset + $i + 1,
                ]);
            }

            $meeting->update([
                'summary' => $result['summary'],
                'status' => 'processed',
                'model' => $result['model'],
                'meta' => ['grounded' => $result['grounded'], 'driver' => config('ai.driver')],
                'processed_at' => now(),
            ]);
        });

        return $this->success([
            'meeting' => new AiMeetingResource($meeting->fresh(['event:id,title', 'actionItems'])),
        ], 'Meeting processed.');
    }

    /** Edit an action item (status, wording, owner, due date). */
    public function updateItem(Request $request, AiMeeting $meeting, AiMeetingActionItem $item): JsonResponse
    {
        $this->authorizeMeeting($request, $meeting);
        abort_unless($item->ai_meeting_id === $meeting->id, 404);

        $data = $request->validate([
            'description' => ['sometimes', 'string', 'max:480'],
            'owner' => ['sometimes', 'nullable', 'string', 'max:120'],
            'due_date' => ['sometimes', 'nullable', 'date'],
            'status' => ['sometimes', 'in:open,done,dismissed'],
        ]);

        $item->update($data);

        return $this->success([
            'meeting' => new AiMeetingResource($meeting->fresh(['event:id,title', 'actionItems'])),
        ], 'Action item updated.');
    }

    /** Push an action item into the event's task board. */
    public function convertItem(Request $request, AiMeeting $meeting, AiMeetingActionItem $item): JsonResponse
    {
        $this->authorizeMeeting($request, $meeting);
        abort_unless($item->ai_meeting_id === $meeting->id, 404);
        abort_if($meeting->event_id === null, 422, 'Link this meeting to an event before pushing tasks.');
        abort_if($item->task_id !== null, 422, 'This item is already on the task board.');

        $task = DB::transaction(function () use ($meeting, $item) {
            $position = (int) EventTask::where('event_id', $meeting->event_id)->max('position') + 1;

            $task = EventTask::create([
                'event_id' => $meeting->event_id,
                'title' => $item->description,
                'description' => "From meeting: {$meeting->title}" . ($item->owner ? " · Owner: {$item->owner}" : ''),
                'priority' => 'medium',
                'status' => 'not_started',
                'due_date' => $item->due_date,
                'position' => $position,
            ]);

            $item->update(['task_id' => $task->id]);

            return $task;
        });

        return $this->success([
            'task_id' => $task->id,
            'meeting' => new AiMeetingResource($meeting->fresh(['event:id,title', 'actionItems'])),
        ], 'Added to the task board.');
    }

    // -----------------------------------------------------------------

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, bool $partial = false): array
    {
        $rule = fn (array $r) => $partial ? array_merge(['sometimes'], $r) : $r;

        $data = $request->validate([
            'title' => $rule(['required', 'string', 'max:150']),
            'notes' => $rule(['required', 'string', 'max:50000']),
            'meeting_type' => ['sometimes', 'in:client,vendor,internal,other'],
            'meeting_date' => ['sometimes', 'nullable', 'date'],
            'attendees' => ['sometimes', 'nullable', 'array'],
            'attendees.*' => ['string', 'max:120'],
            'event_id' => ['sometimes', 'nullable', 'integer'],
        ]);

        if (array_key_exists('event_id', $data)) {
            if (! empty($data['event_id'])) {
                $owns = Event::where('planner_id', $request->user()->id)->whereKey($data['event_id'])->exists();
                abort_unless($owns, 422, 'Invalid event.');
            } else {
                $data['event_id'] = null;
            }
        }

        return $data;
    }

    private function authorizeMeeting(Request $request, AiMeeting $meeting): void
    {
        abort_unless($meeting->user_id === $request->user()->id, 404);
    }
}
