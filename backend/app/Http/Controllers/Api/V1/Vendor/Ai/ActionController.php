<?php

namespace App\Http\Controllers\Api\V1\Vendor\Ai;

use App\Http\Controllers\Controller;
use App\Http\Resources\AiActionResource;
use App\Models\AiAction;
use App\Services\AI\Vendor\VendorActionExecutor;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * The vendor copilot's action queue: it proposes actions from chat and the
 * vendor approves or rejects each one here. Approval is the single point where
 * anything is actually performed — nothing changes before it.
 */
class ActionController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly VendorActionExecutor $executor) {}

    public function index(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        $pending = AiAction::where('user_id', $userId)
            ->where('status', AiAction::STATUS_PENDING)
            ->latest()->get();

        $recent = AiAction::where('user_id', $userId)
            ->whereIn('status', [AiAction::STATUS_DONE, AiAction::STATUS_FAILED, AiAction::STATUS_REJECTED])
            ->latest()->limit(20)->get();

        return $this->success([
            'pending' => AiActionResource::collection($pending),
            'recent' => AiActionResource::collection($recent),
        ]);
    }

    /** Approve a pending action — this runs it. */
    public function approve(Request $request, AiAction $action): JsonResponse
    {
        $this->authorizeAction($request, $action);
        abort_unless($action->isPending(), 422, 'This action is no longer pending.');

        $action->forceFill(['approved_at' => now()])->save();
        $this->executor->execute($action);

        $done = $action->status === AiAction::STATUS_DONE;

        return $this->success([
            'action' => new AiActionResource($action->fresh()),
        ], $done ? ($action->result['message'] ?? 'Action completed.') : ('Action failed: ' . $action->error));
    }

    /** Reject a pending action — nothing runs. */
    public function reject(Request $request, AiAction $action): JsonResponse
    {
        $this->authorizeAction($request, $action);
        abort_unless($action->isPending(), 422, 'This action is no longer pending.');

        $action->forceFill(['status' => AiAction::STATUS_REJECTED])->save();

        return $this->success([
            'action' => new AiActionResource($action->fresh()),
        ], 'Action dismissed.');
    }

    /** Only the vendor's own actions, and only vendor action types. */
    private function authorizeAction(Request $request, AiAction $action): void
    {
        abort_unless($action->user_id === $request->user()->id, 404);
        abort_unless(VendorActionExecutor::isKnown($action->type), 404);
    }
}
