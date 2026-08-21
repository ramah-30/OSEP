<?php

namespace App\Services\AI\Providers;

use App\Services\AI\Contracts\AiProvider;
use App\Services\AI\ScenarioCalculator;
use Illuminate\Support\Str;

/**
 * The offline, data-grounded reasoning engine.
 *
 * It carries no external dependency and never guesses from general knowledge:
 * every answer is assembled from the structured event context handed to it by
 * the Orchestrator (budget figures, timeline, guests, vendors, finance, health
 * score). Intent is inferred from the planner's wording and the reply is built
 * from the real numbers, so the assistant is genuinely useful the moment the
 * platform is installed - no API key, no internet, no cost.
 */
class LocalProvider implements AiProvider
{
    public function name(): string
    {
        return 'local';
    }

    public function chat(string $system, array $messages, array $context = []): array
    {
        $prompt = Str::lower($this->lastUserMessage($messages));
        $event = $context['event'] ?? null;

        $reply = match (true) {
            $this->isScenario($prompt) => $this->scenarioAnswer($context, $prompt),
            $this->mentions($prompt, ['budget', 'cost', 'spend', 'spending', 'expensive', 'money', 'afford']) => $this->budgetAnswer($context),
            $this->mentions($prompt, ['timeline', 'schedule', 'task', 'milestone', 'deadline', 'overdue', 'behind', 'agenda', 'today', 'tomorrow', 'next']) => $this->timelineAnswer($context),
            $this->mentions($prompt, ['vendor', 'supplier', 'photographer', 'caterer', 'dj', 'florist', 'value', 'best']) => $this->vendorAnswer($context),
            $this->mentions($prompt, ['guest', 'rsvp', 'invite', 'invitation', 'attend', 'seat', 'meal', 'respond', 'confirm']) => $this->guestAnswer($context),
            $this->mentions($prompt, ['invoice', 'payment', 'paid', 'unpaid', 'outstanding', 'revenue', 'profit', 'finance', 'financial']) => $this->financeAnswer($context),
            $this->mentions($prompt, ['health', 'score', 'how is', "how's", 'status', 'overview', 'summary', 'summarize', 'summarise', 'where do we', 'on track']) => $this->summaryAnswer($context),
            $this->mentions($prompt, ['risk', 'worry', 'concern', 'problem', 'issue', 'wrong', 'why']) => $this->riskAnswer($context),
            $this->mentions($prompt, ['hello', 'hi ', 'hey', 'help', 'what can you', 'who are you']) || $prompt === 'hi' || $prompt === 'hello' => $this->greeting($event),
            $event => $this->summaryAnswer($context),
            default => $this->greeting($event),
        };

        // Cite the planner's own knowledge base when relevant passages were
        // retrieved for this question.
        $reply .= $this->knowledgeCitations($context);

        return ['content' => $reply, 'model' => 'local-heuristic'];
    }

    /**
     * Append a cited "from your knowledge base" section when the retriever found
     * relevant notes for this question.
     *
     * @param  array<string, mixed>  $context
     */
    private function knowledgeCitations(array $context): string
    {
        $notes = $context['knowledge'] ?? [];
        if (empty($notes)) {
            return '';
        }

        $lines = ["\n\n**📚 From your knowledge base**"];
        foreach ($notes as $note) {
            $tag = ! empty($note['category']) ? " _({$note['category']})_" : '';
            $lines[] = "- **{$note['title']}**{$tag}: {$note['snippet']}";
        }

        return implode("\n", $lines);
    }

    // -----------------------------------------------------------------
    // Intent answers
    // -----------------------------------------------------------------

    private function greeting(?array $event): string
    {
        $name = config('ai.assistant_name', 'OSEP AI');

        if ($event) {
            return "Hi - I'm {$name}, your planning copilot for **{$event['title']}**. "
                . "I can see this event's budget, timeline, guests, vendors and finances. Try asking me:\n\n"
                . "- *Summarize where this event stands*\n"
                . "- *Is the budget on track?*\n"
                . "- *What tasks are overdue?*\n"
                . "- *How are RSVPs looking?*\n"
                . "- *Which vendors still need attention?*";
        }

        return "Hi - I'm {$name}, your event-planning copilot. Pick an event from the context selector and I'll "
            . "ground every answer in its real budget, timeline, guest and vendor data. You can also ask me general "
            . "planning questions like *draft a 12-month wedding timeline* or *what should a corporate budget include*.\n\n"
            . "New here? **Create your first event** and I'll unlock grounded budgets, RSVPs, vendor tracking and what-if "
            . "planning - your AI Dashboard has a setup checklist to walk you through it.";
    }

    private function summaryAnswer(array $context): string
    {
        $event = $context['event'] ?? null;
        if (! $event) {
            return $this->greeting(null);
        }

        $lines = ["Here's where **{$event['title']}** stands today:"];

        $when = $this->countdown($event);
        $lines[] = "\n- 📅 **Timing:** {$when}";

        if (isset($context['budget'])) {
            $b = $context['budget'];
            $flag = $b['over_budget'] ? ' ⚠️ over budget' : '';
            $remaining = max(0, $b['total'] - $b['spent']);
            $lines[] = "- 💰 **Budget:** {$this->money($b['spent'])} spent of {$this->money($b['total'])} allocated "
                . "({$b['utilization_pct']}% used, {$this->money($remaining)} remaining{$flag})";
        }

        if (isset($context['timeline'])) {
            $t = $context['timeline'];
            $overdue = $t['overdue_count'] > 0 ? " - {$t['overdue_count']} overdue" : '';
            $lines[] = "- ✅ **Tasks:** {$t['tasks_done']}/{$t['tasks_total']} done, "
                . "{$t['milestones_done']}/{$t['milestones_total']} milestones{$overdue}";
        }

        if (isset($context['guests'])) {
            $g = $context['guests'];
            $lines[] = "- 👥 **Guests:** {$g['confirmed']} confirmed of {$g['total']} invited "
                . "({$g['confirmation_rate']}% response, {$g['pending']} awaiting reply)";
        }

        if (isset($context['finance']) && $context['finance']['outstanding_amount'] > 0) {
            $f = $context['finance'];
            $lines[] = "- 🧾 **Finance:** {$this->money($f['outstanding_amount'])} still outstanding across "
                . "{$f['invoices_outstanding']} invoice(s)";
        }

        $next = $this->topPriority($context);
        if ($next) {
            $lines[] = "\n**Where I'd focus next:** {$next}";
        }

        return implode("\n", $lines);
    }

    private function budgetAnswer(array $context): string
    {
        $b = $context['budget'] ?? null;
        if (! $b || $b['total'] <= 0) {
            return "I don't see a budget set for this event yet. Add a budget and line items and I'll track "
                . "utilization, flag overspend and suggest savings against real numbers.";
        }

        $lines = ["**Budget health**"];
        $lines[] = "- **Allocated budget:** {$this->money($b['total'])}";
        $lines[] = "- **Amount spent:** {$this->money($b['spent'])} ({$b['utilization_pct']}% of budget)";
        $lines[] = "- **Remaining budget:** {$this->money($b['remaining'])}";

        if ($b['over_budget']) {
            $over = $b['spent'] - $b['total'];
            $lines[] = "\n⚠️ **Over budget by {$this->money($over)}.** To bring this back in line, I'd review the "
                . "largest categories first and renegotiate or trim discretionary line items.";
        } elseif ($b['utilization_pct'] >= 90) {
            $lines[] = "\n⚠️ **Budget is {$b['utilization_pct']}% committed** with only {$this->money($b['remaining'])} left. "
                . "Approach any new spending carefully.";
        } elseif ($b['utilization_pct'] >= 70) {
            $lines[] = "\n✅ You're at {$b['utilization_pct']}% utilization with {$this->money($b['remaining'])} headroom. "
                . "Continue monitoring closely.";
        } else {
            $lines[] = "\n✅ You have comfortable headroom with {$b['utilization_pct']}% spent and "
                . "{$this->money($b['remaining'])} remaining.";
        }

        if (! empty($b['top_categories'])) {
            $lines[] = "\n**Largest categories:**";
            foreach (array_slice($b['top_categories'], 0, 4) as $c) {
                $lines[] = "- {$c['name']}: {$this->money($c['amount'])}";
            }
        }

        $lines[] = $this->benchmarkNote($context);

        return trim(implode("\n", $lines));
    }

    /**
     * When history exists, note the biggest way this event's budget split
     * diverges from the planner's own norm.
     *
     * @param  array<string, mixed>  $context
     */
    private function benchmarkNote(array $context): string
    {
        $benchmark = $context['benchmark'] ?? null;
        if (! $benchmark || empty($benchmark['anomalies'])) {
            return '';
        }

        $a = $benchmark['anomalies'][0];
        $scope = $benchmark['event_type'] ? "your {$benchmark['event_type']}s" : 'your past events';

        return "\n**📈 Vs your history:** {$a['name']} is {$a['event_pct']}% of this budget, but you average "
            . "{$a['benchmark_pct']}% across {$scope} ({$benchmark['sample_events']} event(s)) - worth a look.";
    }

    private function timelineAnswer(array $context): string
    {
        $t = $context['timeline'] ?? null;
        if (! $t) {
            return "There's no timeline data for this event yet. Add milestones and tasks and I'll surface what's "
                . "overdue, what's coming up, and whether you're on track to the event date.";
        }

        $lines = ["**Timeline & tasks**"];
        $lines[] = "- Tasks complete: {$t['tasks_done']}/{$t['tasks_total']}";
        $lines[] = "- Milestones complete: {$t['milestones_done']}/{$t['milestones_total']}";

        if (! empty($t['overdue'])) {
            $lines[] = "\n⚠️ **Overdue ({$t['overdue_count']}):**";
            foreach (array_slice($t['overdue'], 0, 5) as $item) {
                $lines[] = "- {$item['title']}" . ($item['due'] ? " (due {$item['due']})" : '');
            }
        } else {
            $lines[] = "\n✅ Nothing is overdue.";
        }

        if (! empty($t['upcoming'])) {
            $lines[] = "\n**Coming up next:**";
            foreach (array_slice($t['upcoming'], 0, 5) as $item) {
                $lines[] = "- {$item['title']}" . ($item['due'] ? " (due {$item['due']})" : '');
            }
        }

        return implode("\n", $lines);
    }

    private function vendorAnswer(array $context): string
    {
        $v = $context['vendors'] ?? null;
        if (! $v || $v['count'] === 0) {
            return "No vendors are assigned to this event yet. Browse the Marketplace and assign vendors, and I'll "
                . "track their status, contracts and readiness here.";
        }

        $lines = ["**Vendors** - {$v['count']} assigned"];
        foreach (array_slice($v['assigned'], 0, 8) as $vendor) {
            $status = $vendor['status'] ? " - {$vendor['status']}" : '';
            $cost = $vendor['cost'] > 0 ? " ({$this->money($vendor['cost'])})" : '';
            $lines[] = "- **{$vendor['name']}**{$status}{$cost}";
        }

        if ($v['pending'] > 0) {
            $lines[] = "\n⚠️ {$v['pending']} vendor(s) are still unconfirmed - chase these to remove delivery risk.";
        } else {
            $lines[] = "\n✅ All assigned vendors are confirmed.";
        }

        $flags = $context['quote_flags'] ?? [];
        if (! empty($flags)) {
            $lines[] = "\n**📈 Vs your history:**";
            foreach (array_slice($flags, 0, 3) as $q) {
                $lines[] = "- **{$q['name']}** ({$q['service']}) is {$q['delta_pct']}% above your usual "
                    . "{$this->money($q['your_avg'])} - consider negotiating.";
            }
        }

        return implode("\n", $lines);
    }

    private function guestAnswer(array $context): string
    {
        $g = $context['guests'] ?? null;
        if (! $g || $g['total'] === 0) {
            return "There are no guests on this event's list yet. Import or add guests and I'll track RSVPs, "
                . "response rates and meal preferences.";
        }

        $lines = ["**Guests & RSVP**"];
        $lines[] = "- Invited: {$g['total']}";
        $lines[] = "- Confirmed: {$g['confirmed']}";
        $lines[] = "- Declined: {$g['declined']}";
        $lines[] = "- Awaiting reply: {$g['pending']}";
        $lines[] = "- Response rate: {$g['confirmation_rate']}%";

        if ($g['pending'] > 0) {
            $lines[] = "\n💡 {$g['pending']} guest(s) haven't responded. I'd send a reminder wave now - a nudge 3–4 weeks "
                . "out typically recovers a large share of non-responses.";
        }

        if (! empty($g['meal_breakdown'])) {
            $lines[] = "\n**Meal preferences:**";
            foreach ($g['meal_breakdown'] as $meal) {
                $lines[] = "- {$meal['name']}: {$meal['count']}";
            }
        }

        return implode("\n", $lines);
    }

    private function financeAnswer(array $context): string
    {
        $f = $context['finance'] ?? null;
        if (! $f) {
            return "No invoices or payments are recorded for this event yet.";
        }

        $lines = ["**Financials**"];
        $lines[] = "- Invoiced: {$this->money($f['invoiced_total'])} across {$f['invoices_total']} invoice(s)";
        $lines[] = "- Received: {$this->money($f['payments_received'])}";
        $lines[] = "- Outstanding: {$this->money($f['outstanding_amount'])} ({$f['invoices_outstanding']} invoice(s))";

        if ($f['outstanding_amount'] > 0) {
            $lines[] = "\n💡 I'd follow up on the outstanding invoices - send reminders on anything past its due date first.";
        } else {
            $lines[] = "\n✅ Everything invoiced has been collected.";
        }

        return implode("\n", $lines);
    }

    /**
     * True when the question is a "what-if" the ScenarioCalculator can answer:
     * a guest-count change, a per-head cost, or a seating/table query.
     */
    private function isScenario(string $prompt): bool
    {
        // "N more/additional/extra/fewer guests", "add N guests", "seating/tables for N".
        if (preg_match('/\b(?:add|adding|remove|removing|drop|fewer|more|additional|extra|another)\b.*\bguests?\b/', $prompt)
            || preg_match('/\bguests?\b.*\b(?:more|fewer|additional|extra)\b/', $prompt)
            || preg_match('/\b\d[\d,]*\s+(?:more|additional|extra|fewer)\b/', $prompt)
            || preg_match('/\b(?:tables?|seating|seats?)\s+(?:for|of)\s+\d/', $prompt)
            || preg_match('/\bhow many tables?\b/', $prompt)) {
            return true;
        }

        return $this->mentions($prompt, ['what if', 'what-if', 'per head', 'per-head', 'per person', 'per guest', 'per plate', 'cost per', 'head count', 'headcount']);
    }

    /**
     * Answer a what-if using the {@see ScenarioCalculator} against the event's
     * real numbers. Parses the guest delta and table size from the wording.
     *
     * @param  array<string, mixed>  $context
     */
    private function scenarioAnswer(array $context, string $prompt): string
    {
        if (empty($context['event'])) {
            return "Pick an event first and I'll run the numbers - guest-count changes, cost per head, "
                . "tables needed and the catering quantities that follow.";
        }

        $current = ($context['guests']['total'] ?? 0) > 0
            ? (int) $context['guests']['total']
            : (int) ($context['event']['expected_guests'] ?? 0);

        [$delta, $seats] = $this->parseScenario($prompt, $current);

        $result = (new ScenarioCalculator)->forEvent($context, [
            'guests_delta' => $delta,
            'seats_per_table' => $seats,
        ]);

        $base = $result['baseline'];

        if ($base['per_head'] <= 0) {
            return "I can't compute a per-head cost yet - this event has no budget or expected guest count. "
                . "Add a budget (ideally a catering line) and a headcount and I'll model any what-if instantly.";
        }

        $lines = ["**What-if - {$context['event']['title']}**"];
        $lines[] = "- Current basis: **{$this->money($base['per_head'])}/head** ({$base['per_head_basis']}), {$base['current_guests']} guest(s)";

        $p = $result['projection'];
        if ($p) {
            $verb = $delta >= 0 ? 'adding' : 'removing';
            $lines[] = "\nIf **{$verb} " . abs($delta) . " guest(s)** → {$p['new_guests']} total:";
            $sign = $p['added_cost'] >= 0 ? '+' : '−';
            $lines[] = "- Catering impact: **{$sign}{$this->money(abs($p['added_cost']))}** (projected catering {$this->money($p['projected_catering'])})";
            $lines[] = "- Tables at {$base['seats_per_table']}/table: **{$p['tables_needed']}** ("
                . ($p['tables_delta'] >= 0 ? "+{$p['tables_delta']}" : $p['tables_delta']) . ")";

            if ($p['capacity_ok'] === false) {
                $lines[] = "- ⚠️ Venue capacity is {$base['capacity']} - you'd be **{$p['over_capacity_by']} over**.";
            } elseif ($p['capacity_ok'] === true) {
                $lines[] = "- ✅ Fits the venue capacity ({$base['capacity']}).";
            }

            if (! empty($p['meal_rollup'])) {
                $lines[] = "\n**Meal quantities for the caterer:**";
                foreach ($p['meal_rollup'] as $meal) {
                    $lines[] = "- {$meal['name']}: {$meal['count']}";
                }
            }
        } else {
            $lines[] = "\nTell me a change to model - e.g. *what if 20 more guests confirm?* or *how many tables for 180?*";
        }

        return implode("\n", $lines);
    }

    /**
     * Pull a guest delta and seats-per-table out of the wording. "Seating for N"
     * / "how many tables for N" are read as an absolute target and converted to a
     * delta against the current headcount.
     *
     * @return array{0:int, 1:int}
     */
    private function parseScenario(string $prompt, int $current): array
    {
        $seats = 10;
        if (preg_match('/\btables?\s+of\s+(\d+)\b/', $prompt, $m)
            || preg_match('/\b(\d+)\s*(?:seats?|people|pax)\s+per\s+table\b/', $prompt, $m)) {
            $seats = max(1, (int) $m[1]);
        }

        $num = fn (string $s): int => (int) str_replace(',', '', $s);

        // Absolute target: "tables/seating for N", "fit/accommodate N".
        if (preg_match('/\b(?:tables?|seating|seats?)\s+for\s+(\d[\d,]*)/', $prompt, $m)
            || preg_match('/\b(?:fit|accommodate|seat)\s+(\d[\d,]*)/', $prompt, $m)) {
            return [$num($m[1]) - $current, $seats];
        }

        // Removals.
        if (preg_match('/\b(?:remove|removing|drop|dropping|fewer|cancel(?:ling)?)\D{0,20}?(\d[\d,]*)/', $prompt, $m)) {
            return [-$num($m[1]), $seats];
        }

        // Additions.
        if (preg_match('/\b(\d[\d,]*)\s+(?:more|additional|extra)\b/', $prompt, $m)
            || preg_match('/\b(?:add|adding|another)\D{0,20}?(\d[\d,]*)/', $prompt, $m)) {
            return [$num($m[1]), $seats];
        }

        return [0, $seats];
    }

    private function riskAnswer(array $context): string
    {
        $risks = $this->collectRisks($context);

        if (empty($risks)) {
            return "Good news - I'm not seeing material risks right now. Budget, timeline, guests and vendors all look "
                . "healthy. I'll keep watching and flag anything that drifts.";
        }

        $lines = ["Here are the risks I'd watch, most pressing first:"];
        foreach ($risks as $i => $risk) {
            $n = $i + 1;
            $lines[] = "{$n}. {$risk}";
        }

        return implode("\n", $lines);
    }

    // -----------------------------------------------------------------
    // Shared reasoning helpers
    // -----------------------------------------------------------------

    /** The single most valuable next action, or null if all clear. */
    private function topPriority(array $context): ?string
    {
        return $this->collectRisks($context)[0] ?? null;
    }

    /** @return array<int, string> Ordered list of concerns from the data. */
    private function collectRisks(array $context): array
    {
        $risks = [];

        $b = $context['budget'] ?? null;
        if ($b && $b['over_budget']) {
            $over = $b['spent'] - $b['total'];
            $risks[] = "Budget is **{$this->money($over)} over** allocation - review the largest categories.";
        }

        $t = $context['timeline'] ?? null;
        if ($t && $t['overdue_count'] > 0) {
            $risks[] = "{$t['overdue_count']} task(s)/milestone(s) are **overdue** - clear these to keep the plan on track.";
        }

        $f = $context['finance'] ?? null;
        if ($f && $f['outstanding_amount'] > 0) {
            $risks[] = "{$this->money($f['outstanding_amount'])} in invoices is **outstanding** - chase payment.";
        }

        $g = $context['guests'] ?? null;
        if ($g && $g['total'] > 0 && $g['pending'] > 0) {
            $risks[] = "{$g['pending']} guest(s) still haven't RSVP'd - send a reminder.";
        }

        $v = $context['vendors'] ?? null;
        if ($v && $v['pending'] > 0) {
            $risks[] = "{$v['pending']} vendor(s) remain unconfirmed - confirm before the event date.";
        }

        $event = $context['event'] ?? null;
        if ($event && isset($event['days_until']) && $event['days_until'] !== null
            && $event['days_until'] <= 14 && $event['days_until'] >= 0
            && ($event['progress'] ?? 100) < 80) {
            $risks[] = "Only {$event['days_until']} day(s) to go but planning is {$event['progress']}% complete - accelerate open items.";
        }

        return $risks;
    }

    private function countdown(array $event): string
    {
        $days = $event['days_until'] ?? null;
        $date = $event['date'] ?? 'a date not yet set';
        $status = $event['status'] ?? null;

        if ($days === null) {
            return "date not set yet";
        }

        // Check event status to determine if it's past (completed/cancelled) or future
        if (in_array($status, ['completed', 'cancelled', 'archived'])) {
            return "completed on {$date}";
        }

        if ($days < 0) {
            return "was on {$date} (past the scheduled date)";
        }
        if ($days === 0) {
            return "**today** ({$date})";
        }

        return "{$days} day(s) away, on {$date}";
    }

    private function money($amount): string
    {
        return 'TZS ' . number_format((float) $amount, 0);
    }

    private function mentions(string $haystack, array $needles): bool
    {
        foreach ($needles as $needle) {
            if (str_contains($haystack, $needle)) {
                return true;
            }
        }

        return false;
    }

    private function lastUserMessage(array $messages): string
    {
        for ($i = count($messages) - 1; $i >= 0; $i--) {
            if (($messages[$i]['role'] ?? '') === 'user') {
                return (string) ($messages[$i]['content'] ?? '');
            }
        }

        return '';
    }
}
