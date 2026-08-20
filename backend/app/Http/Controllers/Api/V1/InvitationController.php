<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\InvitationChannel;
use App\Http\Controllers\Api\V1\Concerns\AuthorizesEventAccess;
use App\Http\Controllers\Controller;
use App\Http\Requests\SendInvitationRequest;
use App\Http\Resources\InvitationResource;
use App\Models\Event;
use App\Models\Invitation;
use App\Models\InvitationTemplate;
use App\Services\InvitationDispatcher;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InvitationController extends Controller
{
    use ApiResponse, AuthorizesEventAccess;

    public function index(Request $request, Event $event): JsonResponse
    {
        $this->ensurePlannerOwns($request, $event);

        $query = $event->invitations()->with('guest');

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }
        if ($channel = $request->query('channel')) {
            $query->where('channel', $channel);
        }

        $invitations = $query->limit(500)->get();

        return $this->success([
            'invitations' => InvitationResource::collection($invitations),
            'summary' => $this->summary($event),
        ]);
    }

    public function show(Request $request, Event $event, Invitation $invitation): JsonResponse
    {
        $this->ensurePlannerOwns($request, $event);
        $this->ensureBelongsToEvent($event, $invitation);

        return $this->success([
            'invitation' => new InvitationResource($invitation->load(['guest', 'deliveryLogs'])),
        ]);
    }

    public function send(SendInvitationRequest $request, Event $event, InvitationDispatcher $dispatcher): JsonResponse
    {
        $this->ensurePlannerOwns($request, $event);

        $data = $request->validated();
        $channel = InvitationChannel::tryFrom($data['channel'] ?? '') ?? InvitationChannel::Email;
        $template = isset($data['template_id'])
            ? InvitationTemplate::find($data['template_id'])
            : null;

        $guests = ($data['all'] ?? false)
            ? $event->guests()->whereNull('archived_at')->get()
            : $event->guests()->whereIn('id', $data['guest_ids'] ?? [])->get();

        $opts = array_filter([
            'subject' => $data['subject'] ?? null,
            'body' => $data['body'] ?? null,
            'scheduled_for' => $data['scheduled_for'] ?? null,
        ], fn ($v) => $v !== null);

        $sent = 0;
        $scheduled = 0;
        $failed = 0;
        foreach ($guests as $guest) {
            $invitation = $dispatcher->send($guest, $channel, $template, $request->user(), $opts);
            match ($invitation->status->value) {
                'scheduled' => $scheduled++,
                'failed' => $failed++,
                default => $sent++,
            };
        }

        $message = $scheduled > 0
            ? "{$scheduled} invitations scheduled."
            : "{$sent} invitations sent".($failed > 0 ? ", {$failed} failed." : '.');

        return $this->success(['summary' => $this->summary($event), 'sent' => $sent, 'scheduled' => $scheduled, 'failed' => $failed], $message);
    }

    public function resend(Request $request, Event $event, Invitation $invitation, InvitationDispatcher $dispatcher): JsonResponse
    {
        $this->ensurePlannerOwns($request, $event);
        $this->ensureBelongsToEvent($event, $invitation);

        $guest = $invitation->guest;
        $fresh = $dispatcher->send($guest, $invitation->channel, $invitation->template, $request->user(), [
            'subject' => $invitation->subject,
        ]);

        return $this->success(['invitation' => new InvitationResource($fresh->load('guest'))], 'Invitation resent.');
    }

    /**
     * @return array<string, int>
     */
    private function summary(Event $event): array
    {
        $invitations = $event->invitations();

        return [
            'total' => (clone $invitations)->count(),
            'draft' => (clone $invitations)->where('status', 'draft')->count(),
            'scheduled' => (clone $invitations)->where('status', 'scheduled')->count(),
            'sent' => (clone $invitations)->where('status', 'sent')->count(),
            'delivered' => (clone $invitations)->where('status', 'delivered')->count(),
            'opened' => (clone $invitations)->where('status', 'opened')->count(),
            'failed' => (clone $invitations)->where('status', 'failed')->count(),
        ];
    }
}
