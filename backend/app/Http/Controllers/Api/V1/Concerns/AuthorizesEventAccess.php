<?php

namespace App\Http\Controllers\Api\V1\Concerns;

use App\Models\Event;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

/**
 * Shared ownership guards for the planner event workspace. A planner may only
 * touch their own events, and any nested record must belong to the event in the
 * route — otherwise we 404 rather than leak that the id exists.
 */
trait AuthorizesEventAccess
{
    protected function ensurePlannerOwns(Request $request, Event $event): void
    {
        abort_unless($event->planner_id === $request->user()->id, 404);
    }

    /**
     * A client may only reach the event that has been assigned to them.
     */
    protected function ensureClientOwns(Request $request, Event $event): void
    {
        abort_unless($event->client_id === $request->user()->id, 404);
    }

    /**
     * Assert that $child (a model carrying an event_id) belongs to $event.
     */
    protected function ensureBelongsToEvent(Event $event, Model $child): void
    {
        abort_unless((int) $child->getAttribute('event_id') === $event->id, 404);
    }
}
