<?php

namespace App\Services\AI\Client;

use App\Enums\AccountType;
use App\Enums\ApprovalStatus;
use App\Enums\BookingRequestStatus;
use App\Models\AiAction;
use App\Models\Approval;
use App\Models\Event;
use App\Models\Notification;
use App\Models\PlannerBookingRequest;
use App\Models\User;
use Illuminate\Support\Carbon;

/**
 * Runs the client concierge's actions - the things it does *for* the client:
 * responding to an approval their planner asked for, and adding a guest to their
 * list. Each type can preview itself without touching anything and execute once
 * the client approves. Every action is re-scoped to the client's own event(s) at
 * execution time, so the concierge can only ever act on what the client owns.
 */
class ClientActionExecutor
{
    /**
     * @return array<string, array{label:string}>
     */
    public static function catalog(): array
    {
        return [
            'client_respond_approval' => ['label' => 'Respond to an approval'],
            'client_add_guest' => ['label' => 'Add a guest'],
            'client_book_planner' => ['label' => 'Book a planner'],
        ];
    }

    public static function label(string $type): string
    {
        return self::catalog()[$type]['label'] ?? $type;
    }

    public static function isKnown(string $type): bool
    {
        return array_key_exists($type, self::catalog());
    }

    /**
     * @param  array<string, mixed>  $params
     * @param  array<string, mixed>  $attributes
     */
    public function queue(User $client, string $type, array $params, array $attributes = []): AiAction
    {
        $preview = $this->preview($client, $type, $params);

        return AiAction::create(array_merge([
            'user_id' => $client->id,
            'source' => 'chat',
            'type' => $type,
            'title' => $preview['title'],
            'summary' => $preview['summary'],
            'params' => $params,
            'status' => AiAction::STATUS_PENDING,
        ], $attributes));
    }

    /**
     * @param  array<string, mixed>  $params
     * @return array{title:string, summary:string, count:int}
     */
    public function preview(User $client, string $type, array $params): array
    {
        return match ($type) {
            'client_respond_approval' => $this->previewRespondApproval($client, $params),
            'client_add_guest' => $this->previewAddGuest($client, $params),
            'client_book_planner' => $this->previewBookPlanner($client, $params),
            default => ['title' => self::label($type), 'summary' => 'Unknown action.', 'count' => 0],
        };
    }

    public function execute(AiAction $action): AiAction
    {
        $client = $action->user()->first();
        $params = $action->params ?? [];

        try {
            $result = match ($action->type) {
                'client_respond_approval' => $this->runRespondApproval($client, $params),
                'client_add_guest' => $this->runAddGuest($client, $params),
                'client_book_planner' => $this->runBookPlanner($client, $params),
                default => throw new \RuntimeException("Unknown action [{$action->type}]."),
            };

            $action->forceFill(['status' => AiAction::STATUS_DONE, 'result' => $result, 'executed_at' => now()])->save();
        } catch (\Throwable $e) {
            $action->forceFill(['status' => AiAction::STATUS_FAILED, 'error' => $e->getMessage(), 'executed_at' => now()])->save();
        }

        return $action;
    }

    // -----------------------------------------------------------------
    // Respond to an approval
    // -----------------------------------------------------------------

    private function previewRespondApproval(User $client, array $params): array
    {
        $decision = $this->decision($params);
        $approval = $this->resolvePendingApproval($client, (string) ($params['hint'] ?? ''));

        if (! $approval) {
            return ['title' => 'Respond to an approval', 'summary' => $this->noApprovalMessage($client, (string) ($params['hint'] ?? '')), 'count' => 0];
        }

        $verb = ['approve' => 'Approve', 'reject' => 'Reject', 'changes' => 'Request changes on'][$decision];
        $note = ! empty($params['note']) ? " with the note “{$params['note']}”" : '';

        return [
            'title' => "{$verb} · {$approval->title}",
            'summary' => "{$verb} “{$approval->title}”{$note}. Your planner will be notified of your decision.",
            'count' => 1,
        ];
    }

    private function runRespondApproval(User $client, array $params): array
    {
        $decision = $this->decision($params);
        $approval = $this->resolvePendingApproval($client, (string) ($params['hint'] ?? ''));
        abort_if($approval === null, 422, 'No matching pending approval was found.');

        $status = match ($decision) {
            'reject' => ApprovalStatus::Rejected,
            'changes' => ApprovalStatus::ChangesRequested,
            default => ApprovalStatus::Approved,
        };

        $approval->update([
            'status' => $status->value,
            'client_note' => $params['note'] ?? null,
            'decided_at' => now(),
        ]);

        return ['approval_id' => $approval->id, 'status' => $status->value,
            'message' => "{$status->label()} “{$approval->title}”."];
    }

    /** A pending approval on one of the client's events, resolved by fuzzy hint. */
    private function resolvePendingApproval(User $client, string $hint): ?Approval
    {
        $eventIds = Event::where('client_id', $client->id)->pluck('id');
        if ($eventIds->isEmpty()) {
            return null;
        }

        $pending = Approval::whereIn('event_id', $eventIds)
            ->where('status', 'pending')
            ->latest()
            ->get();

        $hint = mb_strtolower(trim($hint));
        if ($hint === '') {
            return $pending->count() === 1 ? $pending->first() : null;
        }

        return $pending->first(fn (Approval $a) => str_contains(mb_strtolower((string) $a->title), $hint))
            ?? ($pending->count() === 1 ? $pending->first() : null);
    }

    private function noApprovalMessage(User $client, string $hint): string
    {
        $count = Approval::whereIn('event_id', Event::where('client_id', $client->id)->pluck('id'))
            ->where('status', 'pending')->count();

        if ($count === 0) {
            return "You have no approvals waiting right now.";
        }
        if ($hint !== '') {
            return "I couldn't find a pending approval matching “{$hint}”. You have {$count} waiting - try naming it.";
        }

        return "You have {$count} approvals waiting - tell me which one (e.g. “approve the catering menu”).";
    }

    // -----------------------------------------------------------------
    // Add a guest
    // -----------------------------------------------------------------

    private function previewAddGuest(User $client, array $params): array
    {
        $name = trim((string) ($params['name'] ?? ''));
        $event = $this->primaryEvent($client);

        if (! $event) {
            return ['title' => 'Add a guest', 'summary' => 'You don’t have an event set up yet to add guests to.', 'count' => 0];
        }
        if ($name === '') {
            return ['title' => 'Add a guest', 'summary' => 'What’s the name of the guest you’d like to add?', 'count' => 0];
        }

        $contact = array_filter([$params['email'] ?? null, $params['phone'] ?? null]);
        $detail = $contact ? ' (' . implode(', ', $contact) . ')' : '';

        return [
            'title' => "Add guest · {$name}",
            'summary' => "Add **{$name}**{$detail} to your guest list for {$event->title}.",
            'count' => 1,
        ];
    }

    private function runAddGuest(User $client, array $params): array
    {
        $name = trim((string) ($params['name'] ?? ''));
        abort_if($name === '', 422, 'A guest name is required.');

        $event = $this->primaryEvent($client);
        abort_if($event === null, 422, 'You have no event to add guests to.');

        $guest = $event->guests()->create(array_filter([
            'full_name' => mb_substr($name, 0, 255),
            'email' => $params['email'] ?? null,
            'phone' => $params['phone'] ?? null,
            'rsvp_status' => 'pending',
        ], fn ($v) => $v !== null && $v !== ''));

        return ['guest_id' => $guest->id, 'message' => "Added {$name} to your guest list for {$event->title}."];
    }

    // -----------------------------------------------------------------
    // Book a planner
    // -----------------------------------------------------------------

    private function previewBookPlanner(User $client, array $params): array
    {
        $hint = trim((string) ($params['planner'] ?? ''));
        if ($hint === '') {
            return ['title' => 'Book a planner', 'summary' => 'Which planner would you like to book? Tell me their name or company.', 'count' => 0];
        }

        $planner = $this->resolvePlanner($hint);
        if (! $planner) {
            return ['title' => 'Book a planner', 'summary' => "I couldn't find a planner matching “{$hint}”. Try their exact name or company - ask me to *find a planner* to see the options.", 'count' => 0];
        }

        if ($this->hasPendingRequest($client, $planner)) {
            return ['title' => 'Book a planner', 'summary' => "You already have a booking request awaiting a reply from {$this->plannerLabel($planner)}.", 'count' => 0];
        }

        $eventType = $this->preferredEventType($client, $params);
        $forEvent = $eventType ? " for your {$eventType}" : '';

        return [
            'title' => "Book · {$this->plannerLabel($planner)}",
            'summary' => "Send a booking request to **{$this->plannerLabel($planner)}**{$forEvent}. They'll receive your details and reply with how they can help. Nothing is confirmed until they accept.",
            'count' => 1,
        ];
    }

    private function runBookPlanner(User $client, array $params): array
    {
        $hint = trim((string) ($params['planner'] ?? ''));
        $planner = $this->resolvePlanner($hint);
        abort_if($planner === null, 422, 'No matching planner was found.');
        abort_if($this->hasPendingRequest($client, $planner), 422, 'You already have a pending request with this planner.');

        $request = PlannerBookingRequest::create([
            'reference' => PlannerBookingRequest::nextReference(),
            'planner_id' => $planner->id,
            'client_id' => $client->id,
            'event_type' => $this->preferredEventType($client, $params),
            'message' => $params['message'] ?? null,
            'status' => BookingRequestStatus::Pending,
        ]);

        Notification::create([
            'user_id' => $planner->id,
            'type' => 'new_booking_request',
            'title' => 'New booking request',
            'message' => "{$client->full_name} wants to book your services.",
            'data' => ['request_id' => $request->id],
        ]);

        return ['request_id' => $request->id, 'message' => "Booking request sent to {$this->plannerLabel($planner)}."];
    }

    /** Fuzzy-match a planner by name or company among event-planner accounts. */
    private function resolvePlanner(string $hint): ?User
    {
        $hint = mb_strtolower(trim($hint));
        if ($hint === '') {
            return null;
        }

        $planners = User::where('account_type', AccountType::EventPlanner)
            ->whereHas('plannerProfile')
            ->with('plannerProfile')
            ->get();

        return $planners->first(fn (User $p) => str_contains(mb_strtolower((string) $p->full_name), $hint)
            || str_contains(mb_strtolower((string) ($p->plannerProfile?->company_name ?? '')), $hint));
    }

    private function hasPendingRequest(User $client, User $planner): bool
    {
        return PlannerBookingRequest::where('client_id', $client->id)
            ->where('planner_id', $planner->id)
            ->where('status', BookingRequestStatus::Pending->value)
            ->exists();
    }

    private function plannerLabel(User $planner): string
    {
        return $planner->plannerProfile?->company_name ?: $planner->full_name;
    }

    /** Prefer an explicit param, else the client's existing event type. */
    private function preferredEventType(User $client, array $params): ?string
    {
        if (! empty($params['event_type'])) {
            return (string) $params['event_type'];
        }

        return $this->primaryEvent($client)?->event_type;
    }

    /** The client's nearest upcoming event, else their most recent. */
    private function primaryEvent(User $client): ?Event
    {
        $events = Event::where('client_id', $client->id)->get();
        if ($events->isEmpty()) {
            return null;
        }

        $today = Carbon::today();

        return $events->filter(fn (Event $e) => $e->event_date && $e->event_date->gte($today))
            ->sortBy('event_date')
            ->first()
            ?? $events->sortByDesc('event_date')->first();
    }

    private function decision(array $params): string
    {
        $d = $params['decision'] ?? 'approve';

        return in_array($d, ['approve', 'reject', 'changes'], true) ? $d : 'approve';
    }
}
