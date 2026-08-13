<?php

namespace App\Http\Controllers\Api\V1\Finance;

use App\Http\Controllers\Controller;
use App\Http\Resources\ActivityResource;
use App\Models\ActivityLog;
use App\Models\Event;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * The finance audit trail: every logged action across the planner's events whose
 * type belongs to the financial workflow (budget / expense / quotation /
 * invoice / payment / refund).
 */
class AuditController extends Controller
{
    use ApiResponse;

    private const PREFIXES = ['budget', 'expense', 'quotation', 'invoice', 'payment', 'refund'];

    public function index(Request $request): JsonResponse
    {
        $eventIds = Event::where('planner_id', $request->user()->id)->pluck('id');

        $logs = ActivityLog::whereIn('event_id', $eventIds)
            ->where(function ($q) {
                foreach (self::PREFIXES as $prefix) {
                    $q->orWhere('action', 'like', "{$prefix}%");
                }
            })
            ->with(['user', 'event'])
            ->latest()
            ->limit(200)
            ->get();

        return $this->success([
            'entries' => $logs->map(fn (ActivityLog $log) => [
                ...(new ActivityResource($log))->resolve($request),
                'event' => $log->event ? ['id' => $log->event->id, 'title' => $log->event->title] : null,
            ]),
        ]);
    }
}
