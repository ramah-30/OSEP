<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\ApprovalStatus;
use App\Http\Controllers\Api\V1\Concerns\AuthorizesEventAccess;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreApprovalRequest;
use App\Http\Resources\ApprovalResource;
use App\Models\Event;
use App\Models\Notification;
use App\Services\ActivityLogger;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * The planner side of approvals: list an event's approvals and submit new items
 * for the client to review. The client's decision lives in ApprovalController.
 */
class EventApprovalController extends Controller
{
    use ApiResponse, AuthorizesEventAccess;

    public function __construct(private readonly ActivityLogger $activity) {}

    public function index(Request $request, Event $event): JsonResponse
    {
        $this->ensurePlannerOwns($request, $event);

        return $this->success([
            'approvals' => ApprovalResource::collection($event->approvals()->with('history.user')->get()),
        ]);
    }

    public function store(StoreApprovalRequest $request, Event $event): JsonResponse
    {
        $this->ensurePlannerOwns($request, $event);

        $approval = $event->approvals()->create([
            ...$request->validated(),
            'submitted_by' => $request->user()->id,
            'status' => ApprovalStatus::Pending->value,
        ]);

        $approval->history()->create([
            'user_id' => $request->user()->id,
            'action' => 'submitted',
            'note' => null,
        ]);

        $this->activity->log($event, $request->user(), 'approval_submitted', "submitted \"{$approval->title}\" for client approval", $approval);

        if ($event->client_id) {
            Notification::create([
                'user_id' => $event->client_id,
                'type' => 'approval_request',
                'title' => 'Approval requested',
                'message' => "Please review \"{$approval->title}\" for {$event->title}.",
                'data' => ['event_id' => $event->id, 'approval_id' => $approval->id],
            ]);
        }

        return $this->created([
            'approval' => new ApprovalResource($approval->load('history.user')),
        ], 'Submitted for approval.');
    }
}
