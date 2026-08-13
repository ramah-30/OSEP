<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\ApprovalStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\ApprovalDecisionRequest;
use App\Http\Resources\ApprovalResource;
use App\Models\Approval;
use App\Models\Notification;
use App\Services\ActivityLogger;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ApprovalController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly ActivityLogger $activity) {}

    public function index(Request $request): JsonResponse
    {
        $event = $request->user()->clientEvent()->first();

        $approvals = $event
            ? $event->approvals()->get()
            : collect();

        return $this->success([
            'approvals' => ApprovalResource::collection($approvals),
        ]);
    }

    public function respond(ApprovalDecisionRequest $request, Approval $approval): JsonResponse
    {
        $this->authorizeClient($request, $approval);

        if (! $approval->isPending()) {
            return $this->error('This item has already been reviewed.', null, 422);
        }

        $status = match ($request->validated()['decision']) {
            'approve' => ApprovalStatus::Approved,
            'reject' => ApprovalStatus::Rejected,
            'request_changes' => ApprovalStatus::ChangesRequested,
        };

        $note = $request->validated()['note'] ?? null;

        $approval->forceFill([
            'status' => $status,
            'client_note' => $note,
            'decided_at' => now(),
        ])->save();

        // Record the decision in the immutable approval history.
        $approval->history()->create([
            'user_id' => $request->user()->id,
            'action' => $status->value,
            'note' => $note,
        ]);

        // Mirror it onto the event's activity feed.
        $this->activity->log(
            $approval->event,
            $request->user(),
            'approval_decision',
            "{$status->label()} \"{$approval->title}\"",
            $approval,
            ['status' => $status->value],
        );

        // Let the planner know a decision landed.
        $this->notifyPlanner($request, $approval, $status);

        return $this->success([
            'approval' => new ApprovalResource($approval),
        ], 'Your decision has been recorded.');
    }

    private function authorizeClient(Request $request, Approval $approval): void
    {
        $approval->loadMissing('event');

        abort_unless($approval->event->client_id === $request->user()->id, 404);
    }

    private function notifyPlanner(Request $request, Approval $approval, ApprovalStatus $status): void
    {
        $event = $approval->event;

        Notification::create([
            'user_id' => $event->planner_id,
            'type' => 'approval_decision',
            'title' => 'Approval '.$status->label(),
            'message' => "{$request->user()->full_name} responded to \"{$approval->title}\" for {$event->title}.",
            'data' => [
                'event_id' => $event->id,
                'approval_id' => $approval->id,
                'status' => $status->value,
            ],
        ]);
    }
}
