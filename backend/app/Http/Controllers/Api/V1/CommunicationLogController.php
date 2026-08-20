<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\AuthorizesEventAccess;
use App\Http\Controllers\Controller;
use App\Http\Resources\CommunicationLogResource;
use App\Models\Event;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CommunicationLogController extends Controller
{
    use ApiResponse, AuthorizesEventAccess;

    public function index(Request $request, Event $event): JsonResponse
    {
        $this->ensurePlannerOwns($request, $event);

        $query = $event->communicationLogs()->with('guest');

        if ($type = $request->query('type')) {
            $query->where('type', $type);
        }
        if ($guestId = $request->query('guest_id')) {
            $query->where('guest_id', $guestId);
        }

        return $this->success([
            'logs' => CommunicationLogResource::collection($query->limit(500)->get()),
        ]);
    }
}
