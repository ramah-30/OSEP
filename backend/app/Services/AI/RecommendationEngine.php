<?php

namespace App\Services\AI;

use App\Enums\RecommendationStatus;
use App\Models\AiRecommendation;
use App\Models\Event;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Continuously analyses an event's live data and turns it into actionable
 * recommendation cards. Every candidate carries a stable signature, so
 * re-running the analysis refreshes the existing card rather than duplicating
 * it, and cards for issues the planner has since resolved fall away on their
 * own. Cards the planner accepted or dismissed are never resurfaced.
 */
class RecommendationEngine
{
    public function __construct(
        private readonly EventContextBuilder $contextBuilder,
        private readonly PlannerHistoryService $history,
    ) {}

    /**
     * Regenerate recommendations for one event and return the fresh pending set.
     *
     * @return Collection<int, AiRecommendation>
     */
    public function syncForEvent(User $user, Event $event): Collection
    {
        $context = $this->contextBuilder->forEvent($user, $event);
        if ($context === null) {
            return collect();
        }

        // Fold in the planner's own historical benchmarks so recommendations can
        // reason against how this planner usually works, not just absolutes.
        $context['benchmark'] = $this->history->compareEvent($user, $event);
        $context['quote_flags'] = $this->history->quoteFlags($user, $event);

        $candidates = $this->analyse($context);
        $signatures = $candidates->pluck('signature')->all();

        // Drop pending cards whose underlying issue is no longer present.
        AiRecommendation::where('event_id', $event->id)
            ->where('status', RecommendationStatus::Pending->value)
            ->when($signatures, fn ($q) => $q->whereNotIn('signature', $signatures))
            ->delete();

        foreach ($candidates as $candidate) {
            $existing = AiRecommendation::where('event_id', $event->id)
                ->where('signature', $candidate['signature'])
                ->first();

            // Leave anything the planner has already triaged untouched.
            if ($existing && $existing->status !== RecommendationStatus::Pending) {
                continue;
            }

            AiRecommendation::updateOrCreate(
                ['event_id' => $event->id, 'signature' => $candidate['signature']],
                array_merge($candidate, [
                    'user_id' => $user->id,
                    'status' => RecommendationStatus::Pending->value,
                ]),
            );
        }

        return AiRecommendation::where('event_id', $event->id)
            ->where('status', RecommendationStatus::Pending->value)
            ->orderByRaw($this->priorityOrder())
            ->orderByDesc('confidence')
            ->get();
    }

    /**
     * Turn a context snapshot into candidate recommendations.
     *
     * @param  array<string, mixed>  $context
     * @return Collection<int, array<string, mixed>>
     */
    private function analyse(array $context): Collection
    {
        $out = collect();
        $event = $context['event'] ?? [];
        $eventId = $event['id'] ?? 0;

        $push = function (array $r) use (&$out, $eventId) {
            $r['signature'] = md5($eventId . '|' . $r['category'] . '|' . $r['key']);
            unset($r['key']);
            $out->push($r);
        };

        // ---- Budget -------------------------------------------------
        $b = $context['budget'] ?? null;
        if ($b && $b['over_budget']) {
            $over = $b['spent'] - $b['total'];
            $push([
                'key' => 'overspend', 'category' => 'budget', 'priority' => 'critical', 'confidence' => 95,
                'title' => 'Budget exceeded',
                'description' => 'Committed spend is TZS ' . number_format($over, 0) . ' over the allocated budget ('
                    . $b['utilization_pct'] . '% used). Review the largest categories and renegotiate or trim.',
                'action_label' => 'Open budget', 'action_type' => 'navigate', 'action_payload' => ['tab' => 'budget'],
            ]);
        } elseif ($b && $b['total'] > 0 && $b['utilization_pct'] >= 90 && ! $b['over_budget']) {
            $push([
                'key' => 'budget_tight', 'category' => 'budget', 'priority' => 'high', 'confidence' => 82,
                'title' => 'Budget nearly exhausted',
                'description' => $b['utilization_pct'] . '% of the budget is committed with only TZS '
                    . number_format($b['remaining'], 0) . ' left. Approve new spend deliberately.',
                'action_label' => 'Open budget', 'action_type' => 'navigate', 'action_payload' => ['tab' => 'budget'],
            ]);
        }
        if ($b && $b['total'] <= 0) {
            $push([
                'key' => 'no_budget', 'category' => 'budget', 'priority' => 'medium', 'confidence' => 70,
                'title' => 'No budget set',
                'description' => 'This event has no budget yet. Add a budget and line items so spend can be tracked and forecast.',
                'action_label' => 'Set up budget', 'action_type' => 'navigate', 'action_payload' => ['tab' => 'budget'],
            ]);
        }

        // ---- Timeline -----------------------------------------------
        $t = $context['timeline'] ?? null;
        if ($t && $t['overdue_count'] > 0) {
            $push([
                'key' => 'overdue', 'category' => 'timeline', 'priority' => $t['overdue_count'] >= 3 ? 'high' : 'medium',
                'confidence' => 90,
                'title' => $t['overdue_count'] . ' overdue item(s)',
                'description' => $t['overdue_count'] . ' task(s)/milestone(s) are past their due date. Clear or reschedule them to keep the plan on track.',
                'action_label' => 'Open timeline', 'action_type' => 'navigate', 'action_payload' => ['tab' => 'timeline'],
            ]);
        }
        if ($t && $t['milestones_total'] === 0) {
            $push([
                'key' => 'no_milestones', 'category' => 'planning', 'priority' => 'medium', 'confidence' => 75,
                'title' => 'No milestones defined',
                'description' => 'Add planning milestones so progress and the critical path can be tracked toward the event date.',
                'action_label' => 'Add milestones', 'action_type' => 'navigate', 'action_payload' => ['tab' => 'timeline'],
            ]);
        }

        // Late-stage, low-progress warning.
        if (isset($event['days_until']) && $event['days_until'] !== null
            && $event['days_until'] <= 14 && $event['days_until'] >= 0 && ($event['progress'] ?? 100) < 80) {
            $push([
                'key' => 'crunch', 'category' => 'planning', 'priority' => 'high', 'confidence' => 80,
                'title' => 'Event approaching, planning behind',
                'description' => 'Only ' . $event['days_until'] . ' day(s) to go but planning is ' . $event['progress']
                    . '% complete. Prioritise the remaining critical tasks.',
                'action_label' => 'Open tasks', 'action_type' => 'navigate', 'action_payload' => ['tab' => 'tasks'],
            ]);
        }

        // ---- Guests -------------------------------------------------
        $g = $context['guests'] ?? null;
        if ($g && $g['total'] > 0 && $g['pending'] > 0 && $g['confirmation_rate'] < 90) {
            $push([
                'key' => 'rsvp', 'category' => 'guest', 'priority' => $g['pending'] >= 10 ? 'high' : 'medium',
                'confidence' => 85,
                'title' => $g['pending'] . ' guest(s) awaiting RSVP',
                'description' => 'Response rate is ' . $g['confirmation_rate'] . '%. Send a reminder wave to recover non-responses before final headcount.',
                'action_label' => 'Manage guests', 'action_type' => 'navigate', 'action_payload' => ['tab' => 'guests'],
            ]);
        }

        // ---- Vendors ------------------------------------------------
        $v = $context['vendors'] ?? null;
        if ($v && $v['count'] === 0) {
            $push([
                'key' => 'no_vendors', 'category' => 'vendor', 'priority' => 'medium', 'confidence' => 68,
                'title' => 'No vendors assigned',
                'description' => 'This event has no vendors yet. Browse the marketplace to source and assign the services you need.',
                'action_label' => 'Open marketplace', 'action_type' => 'navigate', 'action_payload' => ['tab' => 'vendors'],
            ]);
        } elseif ($v && $v['pending'] > 0) {
            $push([
                'key' => 'vendor_unconfirmed', 'category' => 'vendor', 'priority' => 'medium', 'confidence' => 80,
                'title' => $v['pending'] . ' vendor(s) unconfirmed',
                'description' => $v['pending'] . ' assigned vendor(s) have not confirmed. Follow up to remove delivery risk.',
                'action_label' => 'Open vendors', 'action_type' => 'navigate', 'action_payload' => ['tab' => 'vendors'],
            ]);
        }

        // ---- Financial ----------------------------------------------
        $f = $context['finance'] ?? null;
        if ($f && $f['outstanding_amount'] > 0) {
            $push([
                'key' => 'outstanding_invoices', 'category' => 'financial',
                'priority' => $f['outstanding_amount'] > 0 ? 'high' : 'medium', 'confidence' => 88,
                'title' => 'Outstanding invoices',
                'description' => 'TZS ' . number_format($f['outstanding_amount'], 0) . ' across ' . $f['invoices_outstanding']
                    . ' invoice(s) is still unpaid. Send reminders on anything past due.',
                'action_label' => 'Open finance', 'action_type' => 'navigate', 'action_payload' => ['tab' => 'finance'],
            ]);
        }

        // ---- Learned from the planner's own history -----------------
        // Budget split that diverges sharply from how this planner usually
        // allocates for this kind of event.
        $benchmark = $context['benchmark'] ?? null;
        if ($benchmark && ! empty($benchmark['anomalies'])) {
            $a = $benchmark['anomalies'][0];
            $scope = $benchmark['event_type'] ? "your {$benchmark['event_type']}s" : 'your past events';
            $push([
                'key' => 'budget_split_' . $a['direction'] . '_' . md5($a['name']),
                'category' => 'budget', 'priority' => 'medium', 'confidence' => 72,
                'title' => "{$a['name']} looks {$a['direction']} vs your norm",
                'description' => "{$a['name']} is {$a['event_pct']}% of this budget, but averages {$a['benchmark_pct']}% across "
                    . "{$scope} ({$benchmark['sample_events']} event(s)). Worth a sanity check before committing.",
                'action_label' => 'Open budget', 'action_type' => 'navigate', 'action_payload' => ['tab' => 'budget'],
            ]);
        }

        // A vendor quote sitting well above what this planner normally pays.
        $flags = $context['quote_flags'] ?? [];
        if (! empty($flags)) {
            $q = $flags[0];
            $push([
                'key' => 'quote_high_' . md5($q['service'] . '|' . $q['name']),
                'category' => 'vendor', 'priority' => 'medium', 'confidence' => 75,
                'title' => "{$q['service']} quote above your norm",
                'description' => "{$q['name']} is quoting TZS " . number_format($q['quoted'], 0) . " — {$q['delta_pct']}% above your "
                    . "usual TZS " . number_format($q['your_avg'], 0) . " for {$q['service']}. Consider negotiating or comparing.",
                'action_label' => 'Open vendors', 'action_type' => 'navigate', 'action_payload' => ['tab' => 'vendors'],
            ]);
        }

        return $out;
    }

    /** SQL fragment ordering critical → low. */
    private function priorityOrder(): string
    {
        return "FIELD(priority, 'critical', 'high', 'medium', 'low')";
    }
}
