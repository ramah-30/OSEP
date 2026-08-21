<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Event;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Writes the per-event activity feed. Every meaningful planner action funnels
 * through here so the Activity Log tab and the dashboard feed stay in sync.
 *
 * When an action is flagged client-visible it does double duty: the row shows up
 * in the client's "Updates" timeline AND the event's client gets a notification
 * ping, so a single call keeps the feed and the bell in lock-step.
 */
class ActivityLogger
{
    public function log(
        Event $event,
        ?User $user,
        string $action,
        string $description,
        ?Model $subject = null,
        array $meta = [],
        bool $visibleToClient = false,
    ): ActivityLog {
        $activity = $event->activities()->create([
            'user_id' => $user?->id,
            'action' => $action,
            'description' => $description,
            'visible_to_client' => $visibleToClient,
            'subject_type' => $subject ? $subject::class : null,
            'subject_id' => $subject?->getKey(),
            'meta' => $meta ?: null,
        ]);

        if ($visibleToClient) {
            $this->pingClient($event, $activity);
        }

        return $activity;
    }

    /**
     * Notify the event's client of a client-visible update - unless the client
     * is the one who triggered it (no self-notifications).
     */
    private function pingClient(Event $event, ActivityLog $activity): void
    {
        if (! $event->client_id || $event->client_id === $activity->user_id) {
            return;
        }

        Notification::create([
            'user_id' => $event->client_id,
            'type' => 'event_update',
            'title' => $event->title,
            'message' => $activity->description,
            'data' => [
                'event_id' => $event->id,
                'activity_id' => $activity->id,
                'action' => $activity->action,
            ],
        ]);
    }
}
