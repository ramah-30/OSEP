<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\EventMilestone;
use App\Models\EventTask;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * The planner's global calendar: events, task due-dates and timeline milestones
 * flattened into one list the SPA can drop onto a month/week/day grid.
 */
class CalendarController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $from = $request->query('from');
        $to = $request->query('to');

        $eventIds = $user->plannedEvents()->pluck('id');

        $events = $user->plannedEvents()
            ->whereNotNull('event_date')
            ->when($from, fn ($q) => $q->whereDate('event_date', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('event_date', '<=', $to))
            ->get()
            ->map(fn ($e) => [
                'id' => "event-{$e->id}",
                'type' => 'event',
                'title' => $e->title,
                'date' => $e->event_date?->toDateString(),
                'event_id' => $e->id,
                'event_title' => $e->title,
                'status' => $e->status->value,
            ]);

        $tasks = EventTask::whereIn('event_id', $eventIds)
            ->whereNotNull('due_date')
            ->when($from, fn ($q) => $q->whereDate('due_date', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('due_date', '<=', $to))
            ->with('event:id,title')
            ->get()
            ->map(fn ($t) => [
                'id' => "task-{$t->id}",
                'type' => 'task',
                'title' => $t->title,
                'date' => $t->due_date?->toDateString(),
                'event_id' => $t->event_id,
                'event_title' => $t->event?->title,
                'status' => $t->status->value,
            ]);

        $milestones = EventMilestone::whereIn('event_id', $eventIds)
            ->whereNotNull('due_date')
            ->when($from, fn ($q) => $q->whereDate('due_date', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('due_date', '<=', $to))
            ->with('event:id,title')
            ->get()
            ->map(fn ($m) => [
                'id' => "milestone-{$m->id}",
                'type' => 'milestone',
                'title' => $m->name,
                'date' => $m->due_date?->toDateString(),
                'event_id' => $m->event_id,
                'event_title' => $m->event?->title,
                'status' => $m->status->value,
            ]);

        return $this->success([
            'items' => $events->concat($tasks)->concat($milestones)->values(),
        ]);
    }
}
