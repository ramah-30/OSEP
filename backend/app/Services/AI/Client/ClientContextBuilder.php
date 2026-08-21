<?php

namespace App\Services\AI\Client;

use App\Enums\AccountType;
use App\Enums\RsvpStatus;
use App\Models\Approval;
use App\Models\Event;
use App\Models\Invoice;
use App\Models\PlannerBookingRequest;
use App\Models\PlannerReview;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Assembles the client's permission-filtered snapshot for the planning
 * concierge: their event (planner, countdown, progress), the guest list they
 * manage, approvals awaiting their decision, invoices and balances, planner
 * updates and the booking requests they've sent. Everything is scoped to what
 * the client owns - their own events (client_id) and their own invoices - so the
 * concierge only ever sees this client's affairs.
 */
class ClientContextBuilder
{
    /**
     * @return array<string, mixed>
     */
    public function forClient(User $client, ?int $eventId = null): array
    {
        $query = Event::where('client_id', $client->id)
            ->with('planner:id,first_name,last_name');

        if ($eventId !== null) {
            $query->where('id', $eventId);
        }

        $events = $query->get();
        $eventIds = $events->pluck('id')->all();

        return array_filter([
            'event' => $this->primaryEventSummary($events),
            'events_count' => $events->count(),
            'guests' => $this->guestSummary($eventIds),
            'approvals' => $this->approvalSummary($eventIds),
            'finance' => $this->financeSummary($client, $eventIds),
            'updates' => $this->updateSummary($events),
            'requests' => $this->requestSummary($client),
            'planners' => $this->plannerDirectory(),
        ], fn ($v) => $v !== null);
    }

    /**
     * A short directory of planners the client can browse or book, with rating
     * and review count folded in from a single grouped query (no N+1).
     *
     * @return array<int, array<string, mixed>>|null
     */
    private function plannerDirectory(): ?array
    {
        $planners = User::where('account_type', AccountType::EventPlanner)
            ->whereHas('plannerProfile')
            ->with('plannerProfile')
            ->limit(12)
            ->get();

        if ($planners->isEmpty()) {
            return null;
        }

        $ratings = PlannerReview::selectRaw('planner_id, AVG(rating) as avg_rating, COUNT(*) as cnt')
            ->groupBy('planner_id')
            ->get()
            ->keyBy('planner_id');

        return $planners->map(function (User $p) use ($ratings) {
            $profile = $p->plannerProfile;
            $r = $ratings->get($p->id);

            return array_filter([
                'id' => $p->id,
                'name' => $p->full_name,
                'company' => $profile?->company_name,
                'specialization' => $profile?->specialization,
                'location' => $profile?->location,
                'experience_years' => $profile?->experience_years ? (int) $profile->experience_years : null,
                'booking_slug' => $profile?->booking_slug,
                'rating' => $r ? round((float) $r->avg_rating, 1) : null,
                'reviews_count' => $r ? (int) $r->cnt : 0,
            ], fn ($v) => $v !== null && $v !== '');
        })->values()->all();
    }

    /**
     * The event to centre answers on: the nearest upcoming, else the most recent.
     *
     * @param  Collection<int, Event>  $events
     * @return array<string, mixed>|null
     */
    private function primaryEventSummary(Collection $events): ?array
    {
        if ($events->isEmpty()) {
            return null;
        }

        $today = Carbon::today();
        $event = $events->filter(fn (Event $e) => $e->event_date && $e->event_date->gte($today))
            ->sortBy('event_date')
            ->first()
            ?? $events->sortByDesc('event_date')->first();

        $daysUntil = $event->event_date
            ? (int) round($today->diffInDays($event->event_date, false))
            : null;

        $planner = $event->planner;

        return [
            'id' => $event->id,
            'title' => $event->title,
            'type' => $event->event_type,
            'status' => $event->status?->value,
            'date' => $event->event_date?->toFormattedDateString(),
            'days_until' => $daysUntil,
            'progress' => (int) $event->progress,
            'location' => $event->location ?: $event->venue,
            'planner' => $planner ? trim(($planner->first_name ?? '') . ' ' . ($planner->last_name ?? '')) ?: null : null,
        ];
    }

    /**
     * @param  array<int, int>  $eventIds
     * @return array<string, mixed>|null
     */
    private function guestSummary(array $eventIds): ?array
    {
        if (empty($eventIds)) {
            return null;
        }

        $statuses = \App\Models\Guest::whereIn('event_id', $eventIds)
            ->get(['rsvp_status'])
            ->map(fn ($g) => $g->rsvp_status instanceof \BackedEnum ? $g->rsvp_status->value : $g->rsvp_status);
        $total = $statuses->count();
        if ($total === 0) {
            return ['total' => 0, 'confirmed' => 0, 'declined' => 0, 'pending' => 0, 'confirmation_rate' => 0];
        }

        $count = fn (array $wanted) => $statuses->filter(fn ($s) => in_array($s, $wanted, true))->count();
        $confirmed = $count([RsvpStatus::Confirmed->value, RsvpStatus::Attended->value]);
        $declined = $count([RsvpStatus::Declined->value]);
        $responded = $confirmed + $declined + $count([RsvpStatus::Maybe->value]);

        return [
            'total' => $total,
            'confirmed' => $confirmed,
            'declined' => $declined,
            'pending' => $total - $responded,
            'confirmation_rate' => (int) round($responded / $total * 100),
        ];
    }

    /**
     * @param  array<int, int>  $eventIds
     * @return array<string, mixed>|null
     */
    private function approvalSummary(array $eventIds): ?array
    {
        if (empty($eventIds)) {
            return null;
        }

        $pending = Approval::whereIn('event_id', $eventIds)
            ->where('status', 'pending')
            ->with('event:id,title')
            ->latest()
            ->get();

        return [
            'pending' => $pending->count(),
            'list' => $pending->take(6)->map(fn (Approval $a) => [
                'title' => $a->title,
                'type' => $a->type,
                'event' => $a->event?->title,
            ])->values()->all(),
        ];
    }

    /**
     * @param  array<int, int>  $eventIds
     * @return array<string, mixed>|null
     */
    private function financeSummary(User $client, array $eventIds = []): ?array
    {
        $query = Invoice::where('client_id', $client->id);
        if (!empty($eventIds)) {
            $query->whereIn('event_id', $eventIds);
        }
        $invoices = $query->get();
        if ($invoices->isEmpty()) {
            return null;
        }

        $outstanding = $invoices->filter(fn (Invoice $i) => ! in_array($this->status($i), ['paid', 'cancelled', 'draft'], true));
        $today = Carbon::today();

        $overdue = $outstanding->filter(fn (Invoice $i) => $this->status($i) === 'overdue'
            || ($i->due_date && Carbon::parse($i->due_date)->lt($today)));

        $nextDue = $outstanding
            ->filter(fn (Invoice $i) => $i->due_date !== null)
            ->sortBy('due_date')
            ->first();

        return [
            'invoices_total' => $invoices->count(),
            'invoices_outstanding' => $outstanding->count(),
            'outstanding_amount' => (float) $outstanding->sum(fn (Invoice $i) => (float) $i->total - (float) $i->amount_paid),
            'overdue_count' => $overdue->count(),
            'next_due_date' => $nextDue?->due_date ? Carbon::parse($nextDue->due_date)->toFormattedDateString() : null,
            'next_due_amount' => $nextDue ? (float) $nextDue->total - (float) $nextDue->amount_paid : null,
        ];
    }

    /**
     * @param  Collection<int, Event>  $events
     * @return array<string, mixed>|null
     */
    private function updateSummary(Collection $events): ?array
    {
        if ($events->isEmpty()) {
            return null;
        }

        $updates = \App\Models\ActivityLog::whereIn('event_id', $events->pluck('id'))
            ->where('visible_to_client', true)
            ->latest()
            ->limit(5)
            ->get();

        if ($updates->isEmpty()) {
            return null;
        }

        return [
            'count' => $updates->count(),
            'recent' => $updates->map(fn ($u) => [
                'title' => $u->description ?: ($u->action ?: 'Update'),
                'when' => $u->created_at?->diffForHumans(),
            ])->values()->all(),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function requestSummary(User $client): ?array
    {
        $requests = PlannerBookingRequest::where('client_id', $client->id)
            ->with('planner:id,first_name,last_name')
            ->latest()
            ->get();

        if ($requests->isEmpty()) {
            return null;
        }

        $pending = $requests->filter(fn ($r) => $this->status($r) === 'pending');
        $responded = $requests->filter(fn ($r) => in_array($this->status($r), ['accepted', 'declined', 'info_requested'], true));
        $accepted = $requests->filter(fn ($r) => $this->status($r) === 'accepted');

        return [
            'total' => $requests->count(),
            'pending' => $pending->count(),
            'responded' => $responded->count(),
            'accepted' => $accepted->count(),
            'latest_response' => $responded->first() ? [
                'status' => $this->status($responded->first()),
                'planner' => optional($responded->first()->planner)->first_name,
            ] : null,
        ];
    }

    /** Normalise an enum-or-string status to its string value. */
    private function status(object $model): ?string
    {
        $status = $model->status ?? null;
        if ($status instanceof \BackedEnum) {
            return $status->value;
        }

        return $status !== null ? (string) $status : null;
    }
}
