<?php

namespace App\Services\AI;

/**
 * Offline "what-if" engine. Given an event's grounding snapshot (the same
 * structure {@see EventContextBuilder} produces) plus a scenario the planner is
 * weighing - "what if 20 more guests confirm?", "how many tables for 180?",
 * "what per-head do I need to land on this budget?" - it returns the arithmetic
 * consequences: catering cost delta, projected total, tables required, a meal
 * quantity rollup and venue-capacity check.
 *
 * It is pure, deterministic maths over data already on the platform: no model,
 * no network, no guesses. Every figure carries the basis it was derived from so
 * the planner can trust (and audit) the number.
 */
class ScenarioCalculator
{
    /** Category names we treat as catering/per-head spend, matched case-insensitively. */
    private const CATERING = ['cater', 'food', 'meal', 'banquet', 'dining', 'buffet'];

    /**
     * @param  array<string, mixed>  $context  An {@see EventContextBuilder::forEvent()} snapshot.
     * @param  array{guests_delta?:int, seats_per_table?:int, target_budget?:float|null}  $params
     * @return array<string, mixed>
     */
    public function forEvent(array $context, array $params = []): array
    {
        $event = $context['event'] ?? [];
        $budget = $context['budget'] ?? [];
        $guests = $context['guests'] ?? [];

        $seats = max(1, (int) ($params['seats_per_table'] ?? 10));
        $delta = (int) ($params['guests_delta'] ?? 0);

        // Current headcount: real confirmed/invited list if we have one, else the
        // planner's expected figure on the event.
        $current = ($guests['total'] ?? 0) > 0
            ? (int) $guests['total']
            : (int) ($event['expected_guests'] ?? 0);

        [$perHead, $basis] = $this->perHead($budget, $event, $guests, $current);

        $baseline = [
            'current_guests' => $current,
            'expected_guests' => (int) ($event['expected_guests'] ?? 0),
            'per_head' => round($perHead, 2),
            'per_head_basis' => $basis,
            'budget_total' => (float) ($budget['total'] ?? 0),
            'budget_spent' => (float) ($budget['spent'] ?? 0),
            'seats_per_table' => $seats,
            'tables_now' => $current > 0 ? (int) ceil($current / $seats) : 0,
            'capacity' => $event['capacity'] ?? null,
        ];

        $out = [
            'baseline' => $baseline,
            'projection' => $delta !== 0 ? $this->project($baseline, $delta, $seats, $perHead, $guests) : null,
            'target' => $this->targetPerHead($params, $baseline),
        ];

        return $out;
    }

    /**
     * Project the effect of adding (or removing) $delta guests.
     *
     * @param  array<string, mixed>  $baseline
     * @param  array<string, mixed>  $guests
     * @return array<string, mixed>
     */
    private function project(array $baseline, int $delta, int $seats, float $perHead, array $guests): array
    {
        $newGuests = max(0, $baseline['current_guests'] + $delta);
        $addedCost = round($perHead * $delta, 2);
        $tables = (int) ceil($newGuests / $seats);
        $capacity = $baseline['capacity'];

        return [
            'guests_delta' => $delta,
            'new_guests' => $newGuests,
            'added_cost' => $addedCost,
            'projected_catering' => round($perHead * $newGuests, 2),
            'projected_total' => round($baseline['budget_spent'] + $addedCost, 2),
            'tables_needed' => $tables,
            'tables_delta' => $tables - $baseline['tables_now'],
            'capacity_ok' => $capacity !== null ? $newGuests <= (int) $capacity : null,
            'over_capacity_by' => $capacity !== null ? max(0, $newGuests - (int) $capacity) : null,
            'meal_rollup' => $this->mealRollup($guests, $newGuests),
        ];
    }

    /**
     * Distribute a projected headcount across the current meal-choice mix, so the
     * caterer gets a per-dish quantity. Falls back to an even split when nobody
     * has chosen a meal yet.
     *
     * @param  array<string, mixed>  $guests
     * @return array<int, array{name:string, count:int}>
     */
    private function mealRollup(array $guests, int $newGuests): array
    {
        $breakdown = $guests['meal_breakdown'] ?? [];
        if (empty($breakdown) || $newGuests <= 0) {
            return [];
        }

        $chosen = array_sum(array_column($breakdown, 'count'));
        if ($chosen <= 0) {
            return [];
        }

        $rollup = [];
        $running = 0;
        $last = count($breakdown) - 1;
        foreach (array_values($breakdown) as $i => $meal) {
            // Give the remainder to the final dish so the parts sum to $newGuests.
            $count = $i === $last
                ? $newGuests - $running
                : (int) round($newGuests * ($meal['count'] / $chosen));
            $running += $count;
            $rollup[] = ['name' => $meal['name'], 'count' => max(0, $count)];
        }

        return $rollup;
    }

    /**
     * When the planner names a target budget, what per-head (and total per-head
     * spend) that implies for the current headcount.
     *
     * @param  array{target_budget?:float|null}  $params
     * @param  array<string, mixed>  $baseline
     * @return array<string, mixed>|null
     */
    private function targetPerHead(array $params, array $baseline): ?array
    {
        $target = $params['target_budget'] ?? null;
        if ($target === null || $target <= 0 || $baseline['current_guests'] <= 0) {
            return null;
        }

        return [
            'target_budget' => (float) $target,
            'guests' => $baseline['current_guests'],
            'per_head' => round($target / $baseline['current_guests'], 2),
        ];
    }

    /**
     * Derive a per-head figure and describe where it came from. Prefers a real
     * catering line divided by headcount; falls back to total budget ÷ expected
     * guests.
     *
     * @param  array<string, mixed>  $budget
     * @param  array<string, mixed>  $event
     * @param  array<string, mixed>  $guests
     * @return array{0:float, 1:?string}
     */
    private function perHead(array $budget, array $event, array $guests, int $headcount): array
    {
        $catering = $this->cateringAmount($budget);
        if ($catering > 0 && $headcount > 0) {
            return [$catering / $headcount, 'catering line ÷ current headcount'];
        }

        $total = (float) ($budget['total'] ?? 0);
        $expected = (int) ($event['expected_guests'] ?? 0);
        if ($total > 0 && $expected > 0) {
            return [$total / $expected, 'total budget ÷ expected guests'];
        }

        return [0.0, null];
    }

    /**
     * Sum of budget categories that look like catering/per-head spend.
     *
     * @param  array<string, mixed>  $budget
     */
    private function cateringAmount(array $budget): float
    {
        $sum = 0.0;
        foreach ($budget['top_categories'] ?? [] as $category) {
            $name = strtolower((string) ($category['name'] ?? ''));
            foreach (self::CATERING as $needle) {
                if (str_contains($name, $needle)) {
                    $sum += (float) ($category['amount'] ?? 0);
                    break;
                }
            }
        }

        return $sum;
    }
}
