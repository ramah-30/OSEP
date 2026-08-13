<?php

namespace App\Services\AI;

use App\Enums\BudgetItemStatus;
use App\Enums\InvoiceStatus;
use App\Enums\MilestoneStatus;
use App\Enums\RsvpStatus;
use App\Enums\TaskStatus;
use App\Enums\VendorAssignmentStatus;
use App\Models\Event;
use App\Models\User;
use Illuminate\Support\Carbon;

/**
 * Assembles the structured, permission-filtered snapshot of an event that every
 * AI service reasons over. Nothing here reaches beyond what the requesting user
 * is allowed to see: an event is only ever built for the planner who owns it.
 */
class EventContextBuilder
{
    /**
     * Build the grounding context for an event, or null if the user may not
     * see it. Pass $full=false for a lightweight snapshot (dashboards).
     *
     * @return array<string, mixed>|null
     */
    public function forEvent(User $user, Event $event, bool $full = true): ?array
    {
        if (! $this->authorized($user, $event)) {
            return null;
        }

        $event->loadMissing([
            'milestones', 'tasks', 'guests', 'vendorAssignments',
            'budgetItems', 'invoices', 'mealOptions', 'venueDetail',
        ]);

        return array_filter([
            'event' => $this->eventSummary($event),
            'budget' => $this->budgetSummary($event),
            'timeline' => $this->timelineSummary($event, $full),
            'guests' => $this->guestSummary($event),
            'vendors' => $this->vendorSummary($event),
            'finance' => $this->financeSummary($event),
        ], fn ($v) => $v !== null);
    }

    /** Only the planner who owns the event may ground the AI on it. */
    public function authorized(User $user, Event $event): bool
    {
        return $event->planner_id === $user->id;
    }

    /**
     * @return array<string, mixed>
     */
    private function eventSummary(Event $event): array
    {
        $daysUntil = $event->event_date
            ? (int) round(Carbon::today()->diffInDays($event->event_date, false))
            : null;

        return [
            'id' => $event->id,
            'title' => $event->title,
            'type' => $event->event_type,
            'status' => $event->status?->value,
            'date' => $event->event_date?->toFormattedDateString(),
            'days_until' => $daysUntil,
            'progress' => (int) $event->progress,
            'location' => $event->location ?: $event->venue,
            'expected_guests' => (int) $event->expected_guests,
            'capacity' => $event->venueDetail?->capacity !== null ? (int) $event->venueDetail->capacity : null,
            'theme' => $event->theme,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function budgetSummary(Event $event): array
    {
        $total = (float) $event->budget_total;
        $spent = (float) $event->budget_spent;
        $remaining = $total - $spent;

        $categories = $event->budgetItems
            ->groupBy('category')
            ->map(fn ($items, $name) => [
                'name' => $name ?: 'Uncategorised',
                // Prefer the actual cost only once it's a real figure — the column
                // defaults to 0.00, so a planned line must fall through to its
                // estimate rather than count as zero.
                'amount' => (float) $items->sum(fn ($i) => (float) ((float) $i->actual_cost > 0 ? $i->actual_cost : $i->estimated_cost)),
            ])
            ->sortByDesc('amount')
            ->values()
            ->take(6)
            ->all();

        return [
            'total' => $total,
            'spent' => $spent,
            'remaining' => $remaining,
            'utilization_pct' => $total > 0 ? (int) round($spent / $total * 100) : 0,
            'over_budget' => $spent > $total && $total > 0,
            'top_categories' => $categories,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function timelineSummary(Event $event, bool $full): array
    {
        $today = Carbon::today();

        $tasksDone = $event->tasks->where('status', TaskStatus::Completed)->count();
        $milestonesDone = $event->milestones->where('status', MilestoneStatus::Completed)->count();

        $overdue = collect();
        $upcoming = collect();

        foreach ($event->tasks as $task) {
            if ($task->status === TaskStatus::Completed || $task->status === TaskStatus::Cancelled) {
                continue;
            }
            $due = $task->due_date ? Carbon::parse($task->due_date) : null;
            $row = ['title' => $task->title, 'due' => $due?->toFormattedDateString(), 'sort' => $due?->timestamp ?? PHP_INT_MAX];
            if ($due && $due->lt($today)) {
                $overdue->push($row);
            } else {
                $upcoming->push($row);
            }
        }

        foreach ($event->milestones as $milestone) {
            if ($milestone->status === MilestoneStatus::Completed) {
                continue;
            }
            $due = $milestone->due_date ? Carbon::parse($milestone->due_date) : null;
            $row = ['title' => $milestone->name, 'due' => $due?->toFormattedDateString(), 'sort' => $due?->timestamp ?? PHP_INT_MAX];
            if ($due && $due->lt($today)) {
                $overdue->push($row);
            } else {
                $upcoming->push($row);
            }
        }

        return [
            'tasks_total' => $event->tasks->count(),
            'tasks_done' => $tasksDone,
            'milestones_total' => $event->milestones->count(),
            'milestones_done' => $milestonesDone,
            'overdue_count' => $overdue->count(),
            'overdue' => $full ? $overdue->sortBy('sort')->map(fn ($r) => ['title' => $r['title'], 'due' => $r['due']])->values()->all() : [],
            'upcoming' => $full ? $upcoming->sortBy('sort')->take(6)->map(fn ($r) => ['title' => $r['title'], 'due' => $r['due']])->values()->all() : [],
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function guestSummary(Event $event): ?array
    {
        $guests = $event->guests;
        $total = $guests->count();

        if ($total === 0) {
            return ['total' => 0, 'confirmed' => 0, 'declined' => 0, 'pending' => 0, 'confirmation_rate' => 0, 'meal_breakdown' => []];
        }

        // `rsvp_status` is cast to a RsvpStatus enum, so compare on the enum value
        // (a collection where() would test the enum object against a string).
        $statusVal = fn ($g) => $g->rsvp_status instanceof \BackedEnum ? $g->rsvp_status->value : $g->rsvp_status;
        $countIn = fn (array $wanted) => $guests->filter(fn ($g) => in_array($statusVal($g), $wanted, true))->count();

        $confirmed = $countIn([RsvpStatus::Confirmed->value, RsvpStatus::Attended->value]);
        $declined = $countIn([RsvpStatus::Declined->value]);
        $responded = $confirmed + $declined + $countIn([RsvpStatus::Maybe->value]);

        $meals = $guests->whereNotNull('meal_preference')
            ->groupBy('meal_preference')
            ->map(fn ($g, $name) => ['name' => $name, 'count' => $g->count()])
            ->values()
            ->all();

        return [
            'total' => $total,
            'confirmed' => $confirmed,
            'declined' => $declined,
            'pending' => $total - $responded,
            'confirmation_rate' => (int) round($responded / $total * 100),
            'meal_breakdown' => $meals,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function vendorSummary(Event $event): ?array
    {
        $assignments = $event->vendorAssignments;
        $count = $assignments->count();

        if ($count === 0) {
            return ['count' => 0, 'assigned' => [], 'pending' => 0];
        }

        $pending = $assignments->whereIn('status', [
            VendorAssignmentStatus::Requested->value,
        ])->count();

        return [
            'count' => $count,
            'pending' => $pending,
            'assigned' => $assignments->map(fn ($a) => [
                'name' => $a->vendor_name ?: $a->service,
                'service' => $a->service,
                'status' => $a->status instanceof \BackedEnum ? $a->status->value : $a->status,
                'cost' => (float) $a->price,
            ])->values()->all(),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function financeSummary(Event $event): ?array
    {
        $invoices = $event->invoices;

        if ($invoices->isEmpty()) {
            return null;
        }

        $outstanding = $invoices->whereNotIn('status', [
            InvoiceStatus::Paid->value, InvoiceStatus::Cancelled->value, InvoiceStatus::Draft->value,
        ]);

        return [
            'invoices_total' => $invoices->count(),
            'invoiced_total' => (float) $invoices->whereNotIn('status', [InvoiceStatus::Cancelled->value])->sum('total'),
            'payments_received' => (float) $invoices->sum('amount_paid'),
            'invoices_outstanding' => $outstanding->count(),
            'outstanding_amount' => (float) $outstanding->sum(fn ($i) => (float) $i->total - (float) $i->amount_paid),
        ];
    }
}
