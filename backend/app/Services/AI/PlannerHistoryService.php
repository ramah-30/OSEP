<?php

namespace App\Services\AI;

use App\Models\BudgetItem;
use App\Models\Event;
use App\Models\User;
use App\Models\VendorAssignment;
use Illuminate\Support\Collection;

/**
 * Turns the planner's OWN past events into a private benchmark. Because every
 * figure comes from events this planner actually delivered, the guidance is
 * something no generic model could produce — "your weddings run 38% catering",
 * "this quote is 22% above what you normally pay a photographer" — and it works
 * entirely offline.
 *
 * "History" means delivered events (completed or archived); the live event being
 * analysed is always excluded so it never benchmarks against itself.
 */
class PlannerHistoryService
{
    /** Event statuses that count as delivered history. */
    private const DELIVERED = ['completed', 'archived'];

    /** A category deviating from the norm by at least this many points is flagged. */
    private const ANOMALY_POINTS = 12;

    /** A vendor quote at least this far above the planner's norm is flagged. */
    private const QUOTE_OVER_PCT = 25;

    /** Whether the planner has enough delivered history to benchmark against. */
    public function hasHistory(User $user, ?int $excludeEventId = null): bool
    {
        return $this->deliveredEventIds($user, $excludeEventId)->isNotEmpty();
    }

    /**
     * The typical budget category split across the planner's delivered events,
     * optionally narrowed to one event type. Null when there's no usable history.
     *
     * @return array{event_type:?string, sample_events:int, avg_total:float, categories:array<int, array{name:string, pct:int, avg_amount:float}>}|null
     */
    public function budgetBenchmark(User $user, ?string $eventType = null, ?int $excludeEventId = null): ?array
    {
        $eventIds = $this->deliveredEventIds($user, $excludeEventId, $eventType);
        if ($eventIds->isEmpty()) {
            return null;
        }

        $items = BudgetItem::whereIn('event_id', $eventIds)->get(['category', 'estimated_cost', 'actual_cost']);
        if ($items->isEmpty()) {
            return null;
        }

        $byCategory = $items
            ->groupBy(fn (BudgetItem $i) => $i->category ?: 'Uncategorised')
            ->map(fn (Collection $group, string $name) => [
                'name' => $name,
                'amount' => (float) $group->sum(fn (BudgetItem $i) => $this->lineCost($i)),
            ])
            ->filter(fn (array $c) => $c['amount'] > 0)
            ->sortByDesc('amount')
            ->values();

        $total = (float) $byCategory->sum('amount');
        if ($total <= 0) {
            return null;
        }

        $count = $eventIds->count();

        return [
            'event_type' => $eventType,
            'sample_events' => $count,
            'avg_total' => round($total / $count, 2),
            'categories' => $byCategory->map(fn (array $c) => [
                'name' => $c['name'],
                'pct' => (int) round($c['amount'] / $total * 100),
                'avg_amount' => round($c['amount'] / $count, 2),
            ])->all(),
        ];
    }

    /**
     * Compare one live event's budget split to the planner's historical norm.
     * Prefers a same-type benchmark, falling back to all types. Returns the
     * aligned category pair plus any material anomalies.
     *
     * @return array{event_type:?string, sample_events:int, categories:array<int, array{name:string, benchmark_pct:int, event_pct:int}>, anomalies:array<int, array{name:string, benchmark_pct:int, event_pct:int, direction:string}>}|null
     */
    public function compareEvent(User $user, Event $event): ?array
    {
        $benchmark = $this->budgetBenchmark($user, $event->event_type, $event->id)
            ?? $this->budgetBenchmark($user, null, $event->id);
        if ($benchmark === null) {
            return null;
        }

        $eventSplit = $this->eventCategorySplit($event);
        if ($eventSplit === null) {
            return null;
        }

        $benchmarkPct = collect($benchmark['categories'])->pluck('pct', 'name');
        $names = $benchmarkPct->keys()->merge(array_keys($eventSplit))->unique();

        $categories = [];
        $anomalies = [];
        foreach ($names as $name) {
            $bp = (int) ($benchmarkPct[$name] ?? 0);
            $ep = (int) ($eventSplit[$name] ?? 0);
            $categories[] = ['name' => $name, 'benchmark_pct' => $bp, 'event_pct' => $ep];

            if (abs($ep - $bp) >= self::ANOMALY_POINTS) {
                $anomalies[] = [
                    'name' => $name,
                    'benchmark_pct' => $bp,
                    'event_pct' => $ep,
                    'direction' => $ep > $bp ? 'over' : 'under',
                ];
            }
        }

        // Most divergent first.
        usort($anomalies, fn ($a, $b) => abs($b['event_pct'] - $b['benchmark_pct']) <=> abs($a['event_pct'] - $a['benchmark_pct']));

        return [
            'event_type' => $benchmark['event_type'],
            'sample_events' => $benchmark['sample_events'],
            'categories' => $categories,
            'anomalies' => $anomalies,
        ];
    }

    /**
     * Per-service vendor scorecards across ALL the planner's events: how often a
     * service was used, the average price paid, a reliability score and the
     * vendor most often engaged for it.
     *
     * @return array<int, array{service:string, uses:int, avg_price:float, reliability_pct:?int, top_vendor:?string}>
     */
    public function vendorScorecards(User $user): array
    {
        $assignments = $this->plannerAssignments($user);
        if ($assignments->isEmpty()) {
            return [];
        }

        return $assignments
            ->groupBy(fn (VendorAssignment $a) => strtolower(trim((string) ($a->service ?: 'Other'))))
            ->map(function (Collection $group) {
                $priced = $group->filter(fn (VendorAssignment $a) => (float) $a->price > 0);

                return [
                    'service' => ucfirst($group->first()->service ?: 'Other'),
                    'uses' => $group->count(),
                    'avg_price' => $priced->isNotEmpty() ? round((float) $priced->avg('price'), 2) : 0.0,
                    'reliability_pct' => $this->reliability($group),
                    'top_vendor' => $this->topVendor($group),
                ];
            })
            ->sortByDesc('uses')
            ->values()
            ->all();
    }

    /**
     * Sanity-check a single quote for a service against what the planner has
     * historically paid. Null when there isn't enough history for that service.
     *
     * @return array{service:string, your_avg:float, quoted:float, delta_pct:int, verdict:string}|null
     */
    public function quoteSanityCheck(User $user, string $service, float $price, ?int $excludeEventId = null): ?array
    {
        if ($price <= 0 || trim($service) === '') {
            return null;
        }

        $priced = $this->plannerAssignments($user, $excludeEventId)
            ->filter(fn (VendorAssignment $a) => strtolower(trim((string) $a->service)) === strtolower(trim($service)))
            ->filter(fn (VendorAssignment $a) => (float) $a->price > 0);

        if ($priced->count() < 2) {
            return null;
        }

        $avg = (float) $priced->avg('price');
        if ($avg <= 0) {
            return null;
        }

        $deltaPct = (int) round(($price - $avg) / $avg * 100);

        return [
            'service' => ucfirst($service),
            'your_avg' => round($avg, 2),
            'quoted' => round($price, 2),
            'delta_pct' => $deltaPct,
            'verdict' => $deltaPct >= self::QUOTE_OVER_PCT ? 'above'
                : ($deltaPct <= -self::QUOTE_OVER_PCT ? 'below' : 'in_line'),
        ];
    }

    /**
     * Quotes on the live event that sit materially above the planner's norm.
     *
     * @return array<int, array{name:?string, service:string, your_avg:float, quoted:float, delta_pct:int}>
     */
    public function quoteFlags(User $user, Event $event): array
    {
        $event->loadMissing('vendorAssignments');

        $flags = [];
        foreach ($event->vendorAssignments as $assignment) {
            $check = $this->quoteSanityCheck($user, (string) $assignment->service, (float) $assignment->price, $event->id);
            if ($check && $check['verdict'] === 'above') {
                $flags[] = [
                    'name' => $assignment->vendor_name ?: $assignment->service,
                    'service' => $check['service'],
                    'your_avg' => $check['your_avg'],
                    'quoted' => $check['quoted'],
                    'delta_pct' => $check['delta_pct'],
                ];
            }
        }

        return $flags;
    }

    // -----------------------------------------------------------------
    // Internals
    // -----------------------------------------------------------------

    /**
     * The current event's own budget split as category => pct.
     *
     * @return array<string, int>|null
     */
    private function eventCategorySplit(Event $event): ?array
    {
        $event->loadMissing('budgetItems');
        if ($event->budgetItems->isEmpty()) {
            return null;
        }

        $byCategory = $event->budgetItems
            ->groupBy(fn (BudgetItem $i) => $i->category ?: 'Uncategorised')
            ->map(fn (Collection $g) => (float) $g->sum(fn (BudgetItem $i) => $this->lineCost($i)))
            ->filter(fn (float $amount) => $amount > 0);

        $total = (float) $byCategory->sum();
        if ($total <= 0) {
            return null;
        }

        return $byCategory->map(fn (float $amount) => (int) round($amount / $total * 100))->all();
    }

    /** A budget line's cost: its actual once real, otherwise the estimate. */
    private function lineCost(BudgetItem $item): float
    {
        return (float) $item->actual_cost > 0 ? (float) $item->actual_cost : (float) $item->estimated_cost;
    }

    /** Reliability = accepted/completed as a share of resolved (excludes still-pending). */
    private function reliability(Collection $assignments): ?int
    {
        // `status` is cast to a VendorAssignmentStatus enum, so compare on value.
        $status = fn (VendorAssignment $a) => $a->status instanceof \BackedEnum ? $a->status->value : (string) $a->status;

        $good = $assignments->filter(fn (VendorAssignment $a) => in_array($status($a), ['accepted', 'completed'], true))->count();
        $bad = $assignments->filter(fn (VendorAssignment $a) => $status($a) === 'declined')->count();
        $resolved = $good + $bad;

        return $resolved > 0 ? (int) round($good / $resolved * 100) : null;
    }

    /** The vendor name engaged most often for a group of assignments. */
    private function topVendor(Collection $assignments): ?string
    {
        $named = $assignments
            ->map(fn (VendorAssignment $a) => trim((string) $a->vendor_name))
            ->filter(fn (string $n) => $n !== '');

        if ($named->isEmpty()) {
            return null;
        }

        return $named->countBy()->sortDesc()->keys()->first();
    }

    /**
     * All vendor assignments across the planner's events.
     *
     * @return Collection<int, VendorAssignment>
     */
    private function plannerAssignments(User $user, ?int $excludeEventId = null): Collection
    {
        $eventIds = Event::where('planner_id', $user->id)
            ->when($excludeEventId, fn ($q) => $q->where('id', '!=', $excludeEventId))
            ->pluck('id');

        if ($eventIds->isEmpty()) {
            return collect();
        }

        return VendorAssignment::whereIn('event_id', $eventIds)->get();
    }

    /**
     * IDs of the planner's delivered events, optionally of one type and excluding
     * one event.
     *
     * @return Collection<int, int>
     */
    private function deliveredEventIds(User $user, ?int $excludeEventId = null, ?string $eventType = null): Collection
    {
        return Event::where('planner_id', $user->id)
            ->whereIn('status', self::DELIVERED)
            ->when($excludeEventId, fn ($q) => $q->where('id', '!=', $excludeEventId))
            ->when($eventType, fn ($q) => $q->whereRaw('LOWER(event_type) = ?', [strtolower(trim($eventType))]))
            ->pluck('id');
    }
}
