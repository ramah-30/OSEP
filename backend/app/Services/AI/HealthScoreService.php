<?php

namespace App\Services\AI;

use App\Models\AiEventHealthScore;
use App\Models\Event;
use App\Models\User;
use Illuminate\Support\Carbon;

/**
 * Derives an event's Health Score (0–100) from its live data, plus the
 * predictive forecasts shown on the analytics dashboard. The score is a
 * weighted blend of budget, timeline, vendor, guest and finance components,
 * each explained so the planner can see why the number is what it is.
 */
class HealthScoreService
{
    /** Component weights — must sum to 100. */
    private const WEIGHTS = [
        'budget' => 25,
        'timeline' => 25,
        'guests' => 20,
        'vendors' => 15,
        'finance' => 15,
    ];

    public function __construct(private readonly EventContextBuilder $contextBuilder) {}

    /**
     * Return the cached score, recomputing when stale.
     */
    public function for(User $user, Event $event, bool $forceFresh = false): ?AiEventHealthScore
    {
        if (! $this->contextBuilder->authorized($user, $event)) {
            return null;
        }

        $cached = AiEventHealthScore::where('event_id', $event->id)->first();
        $ttl = (int) config('ai.freshness_minutes', 30);

        if (! $forceFresh && $cached && $cached->computed_at->gt(now()->subMinutes($ttl))) {
            return $cached;
        }

        return $this->recompute($user, $event);
    }

    public function recompute(User $user, Event $event): ?AiEventHealthScore
    {
        $context = $this->contextBuilder->forEvent($user, $event);
        if ($context === null) {
            return null;
        }

        [$score, $breakdown] = $this->score($context);
        $forecasts = $this->forecasts($context);

        return AiEventHealthScore::updateOrCreate(
            ['event_id' => $event->id],
            [
                'score' => $score,
                'label' => $this->label($score),
                'breakdown' => $breakdown,
                'forecasts' => $forecasts,
                'computed_at' => now(),
            ],
        );
    }

    /**
     * @param  array<string, mixed>  $context
     * @return array{0: int, 1: array<int, array<string, mixed>>}
     */
    public function score(array $context): array
    {
        $components = [
            'budget' => $this->budgetComponent($context['budget'] ?? null),
            'timeline' => $this->timelineComponent($context['timeline'] ?? null, $context['event'] ?? null),
            'guests' => $this->guestComponent($context['guests'] ?? null),
            'vendors' => $this->vendorComponent($context['vendors'] ?? null),
            'finance' => $this->financeComponent($context['finance'] ?? null),
        ];

        $weighted = 0;
        $breakdown = [];

        foreach ($components as $key => [$pct, $note]) {
            $weight = self::WEIGHTS[$key];
            $weighted += $pct / 100 * $weight;
            $breakdown[] = [
                'key' => $key,
                'label' => ucfirst($key),
                'score' => (int) round($pct),
                'weight' => $weight,
                'note' => $note,
            ];
        }

        return [(int) round($weighted), $breakdown];
    }

    // -----------------------------------------------------------------
    // Components — each returns [0–100 score, explanation]
    // -----------------------------------------------------------------

    /** @return array{0: float, 1: string} */
    private function budgetComponent(?array $b): array
    {
        if (! $b || $b['total'] <= 0) {
            return [60.0, 'No budget set yet.'];
        }
        $util = $b['utilization_pct'];
        if ($b['over_budget']) {
            return [max(0, 100 - ($util - 100) * 2), "Over budget at {$util}% utilization."];
        }
        if ($util >= 90) {
            return [70.0, "High utilization ({$util}%) — little headroom left."];
        }

        return [100.0, "On budget at {$util}% utilization."];
    }

    /** @return array{0: float, 1: string} */
    private function timelineComponent(?array $t, ?array $event): array
    {
        if (! $t || $t['tasks_total'] + $t['milestones_total'] === 0) {
            return [50.0, 'No milestones or tasks defined yet.'];
        }

        $totalItems = $t['tasks_total'] + $t['milestones_total'];
        $doneItems = $t['tasks_done'] + $t['milestones_done'];
        $completion = $doneItems / max(1, $totalItems) * 100;

        $penalty = min(40, $t['overdue_count'] * 10);
        $score = max(0, $completion - $penalty);

        $note = $t['overdue_count'] > 0
            ? "{$t['overdue_count']} item(s) overdue; " . round($completion) . '% complete.'
            : round($completion) . '% complete, nothing overdue.';

        return [$score, $note];
    }

    /** @return array{0: float, 1: string} */
    private function guestComponent(?array $g): array
    {
        if (! $g || $g['total'] === 0) {
            return [55.0, 'No guests added yet.'];
        }

        return [
            (float) $g['confirmation_rate'],
            "{$g['confirmation_rate']}% RSVP response ({$g['pending']} pending).",
        ];
    }

    /** @return array{0: float, 1: string} */
    private function vendorComponent(?array $v): array
    {
        if (! $v || $v['count'] === 0) {
            return [55.0, 'No vendors assigned yet.'];
        }
        $confirmed = $v['count'] - $v['pending'];
        $pct = $confirmed / max(1, $v['count']) * 100;

        return [$pct, "{$confirmed}/{$v['count']} vendors confirmed."];
    }

    /** @return array{0: float, 1: string} */
    private function financeComponent(?array $f): array
    {
        if (! $f || $f['invoices_total'] === 0) {
            return [70.0, 'No invoices raised yet.'];
        }
        if ($f['invoiced_total'] <= 0) {
            return [70.0, 'No billable amounts yet.'];
        }
        $collectedPct = $f['payments_received'] / $f['invoiced_total'] * 100;

        return [
            min(100, $collectedPct),
            round($collectedPct) . '% of invoiced value collected.',
        ];
    }

    // -----------------------------------------------------------------
    // Predictive forecasts
    // -----------------------------------------------------------------

    /**
     * @param  array<string, mixed>  $context
     * @return array<int, array<string, mixed>>
     */
    public function forecasts(array $context): array
    {
        $forecasts = [];
        $g = $context['guests'] ?? null;
        $b = $context['budget'] ?? null;
        $t = $context['timeline'] ?? null;
        $event = $context['event'] ?? null;

        // Expected attendance — confirmed plus a share of the still-pending.
        if ($g && $g['total'] > 0) {
            $expected = $g['confirmed'] + (int) round($g['pending'] * ($g['confirmation_rate'] / 100) * 0.6);
            $forecasts[] = [
                'key' => 'attendance',
                'label' => 'Expected attendance',
                'value' => (string) $expected,
                'confidence' => $g['confirmation_rate'] >= 40 ? 78 : 60,
                'basis' => "{$g['confirmed']} confirmed plus a projected share of {$g['pending']} pending replies.",
            ];
        }

        // Estimated final budget — extrapolate spend against progress.
        if ($b && $b['total'] > 0 && $event) {
            $progress = max(5, (int) ($event['progress'] ?? 0));
            $projected = $progress < 100 ? $b['spent'] / ($progress / 100) : $b['spent'];
            $projected = max($projected, $b['spent']);
            $forecasts[] = [
                'key' => 'final_cost',
                'label' => 'Forecast final cost',
                'value' => 'TZS ' . number_format($projected, 0),
                'confidence' => $progress >= 30 ? 72 : 55,
                'basis' => "Extrapolated from {$b['utilization_pct']}% spend at {$progress}% planning progress.",
            ];
        }

        // Timeline completion probability.
        if ($t && ($t['tasks_total'] + $t['milestones_total']) > 0 && $event) {
            $done = $t['tasks_done'] + $t['milestones_done'];
            $total = $t['tasks_total'] + $t['milestones_total'];
            $base = $done / $total * 100;
            $days = $event['days_until'];
            $prob = $base;
            if ($days !== null && $days >= 0) {
                $prob = $days > 14 ? min(95, $base + 20) : max(20, $base - $t['overdue_count'] * 8);
            }
            $forecasts[] = [
                'key' => 'timeline',
                'label' => 'On-time completion',
                'value' => round($prob) . '%',
                'confidence' => 68,
                'basis' => round($base) . "% of items done" . ($t['overdue_count'] ? ", {$t['overdue_count']} overdue" : '') . '.',
            ];
        }

        return $forecasts;
    }

    public function label(int $score): string
    {
        return match (true) {
            $score >= 85 => 'Excellent',
            $score >= 70 => 'Healthy',
            $score >= 50 => 'Needs attention',
            $score >= 30 => 'At risk',
            default => 'Critical',
        };
    }
}
