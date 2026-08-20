<?php

namespace App\Http\Controllers\Api\V1\Finance\Concerns;

use App\Models\Event;
use App\Services\ActivityLogger;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Http\Request;

/**
 * Shared helpers for the finance controllers: planner-ownership guards, an event
 * lookup that also enforces ownership, and sequential PREFIX-YYYY-###### numbers
 * for the financial documents.
 */
trait HandlesFinance
{
    /** A finance record is the planner's only if the planner_id matches. */
    protected function ensureOwned(Request $request, Model $record): void
    {
        abort_unless((int) $record->getAttribute('planner_id') === $request->user()->id, 404);
    }

    /**
     * Resolve an event the planner owns, or 404. Accepts a nullable id so
     * callers can pass an optional event_id straight through.
     */
    protected function ownedEvent(Request $request, int|string|null $eventId): ?Event
    {
        if ($eventId === null || $eventId === '') {
            return null;
        }

        $event = Event::find($eventId);
        abort_unless($event && $event->planner_id === $request->user()->id, 404);

        return $event;
    }

    /**
     * Next sequential document number, e.g. INV-2026-000042. Counts rows created
     * this year for the given model class.
     *
     * @param  class-string<Model>  $modelClass
     */
    protected function nextNumber(string $prefix, string $modelClass, string $column): string
    {
        $year = now()->year;
        $query = $modelClass::query();

        // Include soft-deleted rows where supported so numbers are never reused.
        if (in_array(SoftDeletes::class, class_uses_recursive($modelClass), true)) {
            $query->withTrashed();
        }

        $count = $query->whereYear('created_at', $year)->count() + 1;

        return sprintf('%s-%d-%06d', $prefix, $year, $count);
    }

    protected function activity(): ActivityLogger
    {
        return app(ActivityLogger::class);
    }

    /**
     * Log a finance action against its event (when the record has one) so it
     * surfaces in the per-event activity feed and the finance audit trail.
     */
    protected function logFinance(?Event $event, Request $request, string $action, string $description, ?Model $subject = null, bool $visibleToClient = false): void
    {
        if ($event) {
            $this->activity()->log($event, $request->user(), $action, $description, $subject, visibleToClient: $visibleToClient);
        }
    }
}
