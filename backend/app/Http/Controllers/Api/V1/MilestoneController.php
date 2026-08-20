<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\MilestoneStatus;
use App\Http\Controllers\Api\V1\Concerns\AuthorizesEventAccess;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMilestoneRequest;
use App\Http\Requests\UpdateMilestoneRequest;
use App\Http\Resources\MilestoneResource;
use App\Models\Event;
use App\Models\EventMilestone;
use App\Services\ActivityLogger;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Timeline milestones for an event.
 */
class MilestoneController extends Controller
{
    use ApiResponse, AuthorizesEventAccess;

    public function __construct(private readonly ActivityLogger $activity) {}

    public function index(Request $request, Event $event): JsonResponse
    {
        $this->ensurePlannerOwns($request, $event);

        return $this->success([
            'milestones' => MilestoneResource::collection($event->milestones()->with('assignee')->get()),
        ]);
    }

    public function store(StoreMilestoneRequest $request, Event $event): JsonResponse
    {
        $this->ensurePlannerOwns($request, $event);

        $data = $request->validated();

        $milestone = $event->milestones()->create([
            ...$data,
            'status' => $data['status'] ?? MilestoneStatus::Pending->value,
            'position' => (int) $event->milestones()->max('position') + 1,
        ]);

        $event->recalculateProgress();
        $this->activity->log($event, $request->user(), 'milestone_created', "added milestone \"{$milestone->name}\"", $milestone);

        return $this->created([
            'milestone' => new MilestoneResource($milestone->load('assignee')),
        ], 'Milestone added.');
    }

    public function update(UpdateMilestoneRequest $request, Event $event, EventMilestone $milestone): JsonResponse
    {
        $this->ensurePlannerOwns($request, $event);
        $this->ensureBelongsToEvent($event, $milestone);

        $milestone->fill($request->validated())->save();
        $event->recalculateProgress();

        return $this->success([
            'milestone' => new MilestoneResource($milestone->load('assignee')),
        ], 'Milestone updated.');
    }

    public function destroy(Request $request, Event $event, EventMilestone $milestone): JsonResponse
    {
        $this->ensurePlannerOwns($request, $event);
        $this->ensureBelongsToEvent($event, $milestone);

        $milestone->delete();
        $event->recalculateProgress();

        return $this->success(null, 'Milestone deleted.');
    }
}
