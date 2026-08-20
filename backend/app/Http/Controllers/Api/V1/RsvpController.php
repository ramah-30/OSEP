<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\CommunicationType;
use App\Enums\InvitationStatus;
use App\Enums\RsvpResponse as RsvpResponseEnum;
use App\Http\Controllers\Api\V1\Concerns\AuthorizesEventAccess;
use App\Http\Controllers\Controller;
use App\Http\Requests\PublicRsvpRequest;
use App\Http\Resources\RsvpResponseResource;
use App\Models\Event;
use App\Models\Guest;
use App\Models\Notification;
use App\Services\QrCodeService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RsvpController extends Controller
{
    use ApiResponse, AuthorizesEventAccess;

    // ------------------------------------------------------------------
    // Planner side (authenticated)
    // ------------------------------------------------------------------

    public function index(Request $request, Event $event): JsonResponse
    {
        $this->ensurePlannerOwns($request, $event);

        $responses = $event->rsvpResponses()->with('guest')->limit(500)->get();

        return $this->success([
            'responses' => RsvpResponseResource::collection($responses),
        ]);
    }

    // ------------------------------------------------------------------
    // Public side (no auth — the URL token is the credential)
    // ------------------------------------------------------------------

    public function show(string $token): JsonResponse
    {
        $guest = $this->resolveGuest($token);
        $event = $guest->event;

        $this->markOpened($guest);

        $latest = $guest->rsvpResponses()->first();

        return $this->success([
            'event' => [
                'title' => $event->title,
                'date' => $event->event_date?->toDateString(),
                'start_time' => $event->start_time,
                'end_time' => $event->end_time,
                'venue' => $event->venue,
                'location' => $event->location,
                'theme' => $event->theme,
                'description' => $event->description,
            ],
            'guest' => [
                'first_name' => $guest->first_name,
                'full_name' => $guest->full_name,
                'plus_ones_allowed' => $guest->plus_ones_allowed,
            ],
            'meal_options' => $event->mealOptions()->where('is_active', true)->get()
                ->map(fn ($m) => ['name' => $m->name, 'description' => $m->description])->all(),
            'current_response' => $latest ? [
                'response' => $latest->response->value,
                'additional_guests' => $latest->additional_guests,
                'meal_choice' => $latest->meal_choice,
                'special_requirements' => $latest->special_requirements,
                'message' => $latest->message,
            ] : null,
        ]);
    }

    public function respond(PublicRsvpRequest $request, string $token, QrCodeService $qr): JsonResponse
    {
        $guest = $this->resolveGuest($token);
        $event = $guest->event;
        $data = $request->validated();

        $response = RsvpResponseEnum::from($data['response']);
        $additional = min((int) ($data['additional_guests'] ?? 0), $guest->plus_ones_allowed);

        $record = $event->rsvpResponses()->create([
            'guest_id' => $guest->id,
            'invitation_id' => $guest->invitations()->first()?->id,
            'response' => $response->value,
            'additional_guests' => $additional,
            'meal_choice' => $data['meal_choice'] ?? null,
            'special_requirements' => $data['special_requirements'] ?? null,
            'message' => $data['message'] ?? null,
            'responded_at' => now(),
        ]);

        $guest->forceFill([
            'rsvp_status' => $response->toRsvpStatus()->value,
            'rsvp_responded_at' => now(),
            'meal_preference' => $data['meal_choice'] ?? $guest->meal_preference,
        ])->save();

        // A confirmed guest earns a ticket immediately.
        if ($response === RsvpResponseEnum::Attending) {
            $qr->ensureFor($guest);
        }

        $guest->communicationLogs()->create([
            'event_id' => $event->id,
            'type' => CommunicationType::Rsvp->value,
            'title' => 'RSVP: '.$response->label(),
            'detail' => $data['message'] ?? null,
        ]);

        Notification::create([
            'user_id' => $event->planner_id,
            'type' => 'rsvp_received',
            'title' => 'RSVP received',
            'message' => "{$guest->full_name} responded \"{$response->label()}\" for {$event->title}.",
            'data' => ['event_id' => $event->id, 'guest_id' => $guest->id],
        ]);

        return $this->success([
            'response' => new RsvpResponseResource($record),
            'confirmed' => $response === RsvpResponseEnum::Attending,
        ], 'Thank you — your response has been recorded.');
    }

    private function resolveGuest(string $token): Guest
    {
        return Guest::with('event')->where('rsvp_token', $token)->firstOr(fn () => abort(404, 'Invitation not found.'));
    }

    private function markOpened(Guest $guest): void
    {
        $invitation = $guest->invitations()
            ->whereIn('status', [InvitationStatus::Sent->value, InvitationStatus::Delivered->value])
            ->first();

        if (! $invitation) {
            return;
        }

        $invitation->update(['status' => InvitationStatus::Opened->value, 'opened_at' => now()]);
        $invitation->deliveryLogs()->create([
            'status' => 'opened',
            'channel' => $invitation->channel->value,
            'detail' => 'Guest opened the invitation',
            'occurred_at' => now(),
        ]);

        if ($guest->invitation_status !== InvitationStatus::Opened) {
            $guest->forceFill(['invitation_status' => InvitationStatus::Opened->value])->save();
        }
    }
}
