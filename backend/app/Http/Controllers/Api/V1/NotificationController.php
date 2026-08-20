<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\NotificationResource;
use App\Models\Notification;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $notifications = $user->notifications()->paginate(15);
        $unread = $user->notifications()->whereNull('read_at')->count();

        return $this->success([
            'notifications' => NotificationResource::collection($notifications->items()),
            'unread_count' => $unread,
            'meta' => [
                'current_page' => $notifications->currentPage(),
                'last_page' => $notifications->lastPage(),
                'total' => $notifications->total(),
            ],
        ]);
    }

    public function markRead(Request $request, Notification $notification): JsonResponse
    {
        $this->authorizeOwnership($request, $notification);

        $notification->markRead();

        return $this->success([
            'notification' => new NotificationResource($notification),
            'unread_count' => $request->user()->notifications()->whereNull('read_at')->count(),
        ], 'Notification marked as read.');
    }

    public function markAllRead(Request $request): JsonResponse
    {
        $request->user()->notifications()->whereNull('read_at')->update(['read_at' => now()]);

        return $this->success(['unread_count' => 0], 'All notifications marked as read.');
    }

    public function destroy(Request $request, Notification $notification): JsonResponse
    {
        $this->authorizeOwnership($request, $notification);

        $notification->delete();

        return $this->success([
            'unread_count' => $request->user()->notifications()->whereNull('read_at')->count(),
        ], 'Notification removed.');
    }

    private function authorizeOwnership(Request $request, Notification $notification): void
    {
        abort_unless($notification->user_id === $request->user()->id, 404);
    }
}
