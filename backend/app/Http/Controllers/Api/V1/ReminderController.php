<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\InvitationChannel;
use App\Enums\RsvpStatus;
use App\Http\Controllers\Api\V1\Concerns\AuthorizesEventAccess;
use App\Http\Controllers\Controller;
use App\Http\Requests\SendReminderRequest;
use App\Http\Resources\InvitationResource;
use App\Models\Event;
use App\Services\InvitationDispatcher;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Reminders reuse the invitation pipeline (kind = reminder). "Send now" delivers
 * immediately; a `scheduled_for` queues it for the osep:dispatch-reminders
 * command. Targets pending guests, a selection, or everyone.
 */
class ReminderController extends Controller
{
    use ApiResponse, AuthorizesEventAccess;

    public function index(Request $request, Event $event): JsonResponse
    {
        $this->ensurePlannerOwns($request, $event);

        $reminders = $event->invitations()
            ->where('meta->kind', 'reminder')
            ->with('guest')
            ->limit(500)
            ->get();

        return $this->success([
            'reminders' => InvitationResource::collection($reminders),
            'scheduled_count' => $reminders->filter(fn ($r) => $r->status->value === 'scheduled')->count(),
        ]);
    }

    public function send(SendReminderRequest $request, Event $event, InvitationDispatcher $dispatcher): JsonResponse
    {
        $this->ensurePlannerOwns($request, $event);

        $data = $request->validated();
        $target = $data['target'] ?? 'pending';
        $channel = InvitationChannel::tryFrom($data['channel'] ?? '') ?? InvitationChannel::Email;

        $query = $event->guests()->whereNull('archived_at');
        if ($target === 'pending') {
            $query->whereIn('rsvp_status', RsvpStatus::pendingStates());
        } elseif ($target === 'selected') {
            $query->whereIn('id', $data['guest_ids'] ?? []);
        }
        $guests = $query->get();

        $opts = array_filter([
            'kind' => 'reminder',
            'subject' => $data['subject'] ?? null,
            'body' => $data['body'] ?? null,
            'scheduled_for' => $data['scheduled_for'] ?? null,
        ], fn ($v) => $v !== null);
        $opts['kind'] = 'reminder';

        $sent = 0;
        $scheduled = 0;
        foreach ($guests as $guest) {
            $reminder = $dispatcher->send($guest, $channel, null, $request->user(), $opts);
            $reminder->status->value === 'scheduled' ? $scheduled++ : $sent++;
        }

        $message = $scheduled > 0
            ? "{$scheduled} reminders scheduled."
            : "{$sent} reminders sent.";

        return $this->success(['sent' => $sent, 'scheduled' => $scheduled], $message);
    }
}
