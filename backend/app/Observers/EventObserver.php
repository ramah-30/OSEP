<?php

namespace App\Observers;

use App\Models\AiEventHealthScore;
use App\Models\Event;

/**
 * When event budget_total is updated, invalidate the cached AI health score
 * so AI responses reflect the new budget immediately.
 */
class EventObserver
{
    public function updating(Event $event): void
    {
        // Only invalidate if budget_total changed
        if ($event->isDirty('budget_total')) {
            AiEventHealthScore::where('event_id', $event->id)->delete();
        }
    }
}
