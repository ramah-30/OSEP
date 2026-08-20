<?php

namespace App\Services\AI;

/**
 * The offline, data-grounded document composer — the document-generation
 * counterpart to LocalProvider. It assembles polished Markdown deliverables from
 * the structured event context (budget, timeline, guests, vendors, finance,
 * so the platform can generate real proposals, timelines, emails and
 * more with no API key. When live LLM drivers are configured, DocumentGenerator
 * routes to them instead; this class guarantees the feature works out of the box.
 */
class LocalDocumentComposer
{
    /**
     * @param  array<string, mixed>  $context
     * @param  array<string, mixed>  $inputs
     */
    public function compose(string $key, array $context, array $inputs): string
    {
        return match ($key) {
            'client_proposal' => $this->clientProposal($context, $inputs),
            'planning_timeline' => $this->planningTimeline($context, $inputs),
            'run_of_show' => $this->runOfShow($context, $inputs),
            'vendor_brief' => $this->vendorBrief($context, $inputs),
            'rsvp_reminder_email' => $this->rsvpReminderEmail($context, $inputs),
            'client_update_email' => $this->clientUpdateEmail($context, $inputs),
            'budget_outline' => $this->budgetOutline($context, $inputs),
            'welcome_speech' => $this->welcomeSpeech($context, $inputs),
            'social_announcement' => $this->socialAnnouncement($context, $inputs),
            default => $this->generic($context, $inputs),
        };
    }

    // -----------------------------------------------------------------
    // Composers
    // -----------------------------------------------------------------

    /** @param array<string,mixed> $c @param array<string,mixed> $in */
    private function clientProposal(array $c, array $in): string
    {
        $e = $c['event'] ?? [];
        $title = $e['title'] ?? 'Your Event';
        $client = $this->val($in, 'client_name') ?: 'Valued Client';
        $company = $this->val($in, 'company') ?: 'Your Event Planner';

        $out = [];
        $out[] = "# Event Proposal: {$title}";
        $out[] = "**Prepared for:** {$client}  ";
        $out[] = "**Prepared by:** {$company}  ";
        $out[] = '**Date:** ' . now()->toFormattedDateString();
        $out[] = '';
        $out[] = '## Overview';
        $out[] = $this->eventOverviewSentence($e);
        $out[] = '';

        $out[] = '## What We Will Deliver';
        $out[] = '- End-to-end planning and on-the-day coordination';
        $out[] = '- Vendor sourcing, negotiation and management';
        $out[] = '- Budget tracking and transparent reporting';
        $out[] = '- Guest management, invitations and RSVP tracking';
        $out[] = '';

        if (isset($c['budget']) && $c['budget']['total'] > 0) {
            $b = $c['budget'];
            $out[] = '## Investment Summary';
            $out[] = "| Item | Amount |";
            $out[] = "| --- | --- |";
            $out[] = "| Total budget | {$this->money($b['total'])} |";
            $out[] = "| Committed to date | {$this->money($b['spent'])} ({$b['utilization_pct']}%) |";
            $out[] = "| Remaining | {$this->money($b['remaining'])} |";
            if (! empty($b['top_categories'])) {
                $out[] = '';
                $out[] = '**Largest allocations:**';
                foreach (array_slice($b['top_categories'], 0, 5) as $cat) {
                    $out[] = "- {$cat['name']}: {$this->money($cat['amount'])}";
                }
            }
            $out[] = '';
        }

        if (isset($c['vendors']) && $c['vendors']['count'] > 0) {
            $out[] = '## Vendor Team';
            foreach (array_slice($c['vendors']['assigned'], 0, 8) as $v) {
                $svc = $v['service'] ? " — {$v['service']}" : '';
                $out[] = "- **{$v['name']}**{$svc}";
            }
            $out[] = '';
        }

        $out[] = '## Next Steps';
        $out[] = '1. Review and approve this proposal';
        $out[] = '2. Confirm the budget and priority items';
        $out[] = '3. Sign off so we can secure key vendors and dates';
        $out[] = '';
        $out[] = "We would be delighted to bring **{$title}** to life. Please reach out with any questions.";
        $out[] = '';
        $out[] = "Warm regards,  \n{$company}";

        return $this->join($out);
    }

    /** @param array<string,mixed> $c @param array<string,mixed> $in */
    private function planningTimeline(array $c, array $in): string
    {
        $e = $c['event'] ?? [];
        $title = $e['title'] ?? 'Your Event';
        $t = $c['timeline'] ?? null;

        $out = [];
        $out[] = "# Planning Timeline: {$title}";
        $out[] = $this->timingLine($e);
        $out[] = '';

        // Ground in real milestones/tasks when we have them.
        if ($t && ($t['overdue_count'] > 0 || ! empty($t['upcoming']))) {
            $out[] = '## Where You Stand';
            $out[] = "- Tasks complete: {$t['tasks_done']}/{$t['tasks_total']}";
            $out[] = "- Milestones complete: {$t['milestones_done']}/{$t['milestones_total']}";
            $out[] = '';

            if (! empty($t['overdue'])) {
                $out[] = '## ⚠️ Overdue — Clear These First';
                foreach (array_slice($t['overdue'], 0, 10) as $item) {
                    $due = $item['due'] ? " _(was due {$item['due']})_" : '';
                    $out[] = "- [ ] {$item['title']}{$due}";
                }
                $out[] = '';
            }

            if (! empty($t['upcoming'])) {
                $out[] = '## Coming Up Next';
                foreach (array_slice($t['upcoming'], 0, 12) as $item) {
                    $due = $item['due'] ? " — {$item['due']}" : '';
                    $out[] = "- [ ] {$item['title']}{$due}";
                }
                $out[] = '';
            }

            return $this->join($out);
        }

        // No timeline data — provide a best-practice countdown plan.
        $out[] = '_No milestones are recorded yet, so here is a recommended countdown plan. Add these to your event timeline to track them._';
        $out[] = '';
        foreach ($this->standardTimeline($e['type'] ?? null) as $phase => $items) {
            $out[] = "## {$phase}";
            foreach ($items as $item) {
                $out[] = "- [ ] {$item}";
            }
            $out[] = '';
        }

        return $this->join($out);
    }

    /** @param array<string,mixed> $c @param array<string,mixed> $in */
    private function runOfShow(array $c, array $in): string
    {
        $e = $c['event'] ?? [];
        $title = $e['title'] ?? 'Your Event';
        $start = $this->val($in, 'start_time') ?: 'TBC';

        $out = [];
        $out[] = "# Run of Show: {$title}";
        $out[] = '**Date:** ' . ($e['date'] ?? 'TBC') . "  ";
        $out[] = "**Doors / guest arrival:** {$start}";
        if (! empty($e['location'])) {
            $out[] = "  \n**Venue:** {$e['location']}";
        }
        $out[] = '';

        $out[] = '## Schedule';
        $out[] = '| Time | Segment | Owner |';
        $out[] = '| --- | --- | --- |';
        foreach ($this->runOfShowRows($e['type'] ?? null) as $row) {
            $out[] = "| {$row[0]} | {$row[1]} | {$row[2]} |";
        }
        $out[] = '';

        $out[] = '## Coordination Checklist';
        $out[] = '- [ ] Vendors briefed on arrival times and access';
        $out[] = '- [ ] Point of contact assigned for each vendor';
        $out[] = '- [ ] Emergency / weather contingency confirmed';
        $out[] = '- [ ] Payments and tips prepared';
        $out[] = '- [ ] Timeline shared with the full team';

        if (isset($c['vendors']) && $c['vendors']['count'] > 0) {
            $out[] = '';
            $out[] = '## Vendor Contacts';
            foreach (array_slice($c['vendors']['assigned'], 0, 10) as $v) {
                $out[] = "- **{$v['name']}**" . ($v['service'] ? " — {$v['service']}" : '');
            }
        }

        return $this->join($out);
    }

    /** @param array<string,mixed> $c @param array<string,mixed> $in */
    private function vendorBrief(array $c, array $in): string
    {
        $e = $c['event'] ?? [];
        $title = $e['title'] ?? 'our event';
        $service = $this->val($in, 'service_needed') ?: 'the requested service';

        $guests = $e['expected_guests'] ?? ($c['guests']['total'] ?? null);

        $out = [];
        $out[] = '# Vendor Brief & Request for Quote';
        $out[] = '';
        $out[] = 'Hello,';
        $out[] = '';
        $out[] = "We are planning **{$title}** and are seeking a quote for **{$service}**.";
        $out[] = '';
        $out[] = '## Event Details';
        $out[] = "- **Event:** {$title}" . (! empty($e['type']) ? " ({$e['type']})" : '');
        $out[] = '- **Date:** ' . ($e['date'] ?? 'To be confirmed');
        if (! empty($e['location'])) {
            $out[] = "- **Location:** {$e['location']}";
        }
        if ($guests) {
            $out[] = "- **Expected guests:** {$guests}";
        }
        $out[] = '';
        $out[] = '## What We Need From You';
        $out[] = '1. An itemised quote for the service above';
        $out[] = '2. Confirmation of availability on our date';
        $out[] = '3. Details of what is included (and any add-ons)';
        $out[] = '4. Deposit terms and cancellation policy';
        $out[] = '5. References or a portfolio, if available';
        $out[] = '';
        $out[] = 'Please reply at your earliest convenience so we can move quickly. Thank you!';

        return $this->join($out);
    }

    /** @param array<string,mixed> $c @param array<string,mixed> $in */
    private function rsvpReminderEmail(array $c, array $in): string
    {
        $e = $c['event'] ?? [];
        $g = $c['guests'] ?? null;
        $title = $e['title'] ?? 'our event';
        $replyBy = $this->val($in, 'reply_by') ?: 'as soon as possible';

        $pendingLine = ($g && $g['pending'] > 0)
            ? "We are still waiting to hear from **{$g['pending']}** of our guests"
            : 'We would love to finalise our numbers';

        $out = [];
        $out[] = "**Subject:** A quick reminder — please RSVP for {$title}";
        $out[] = '';
        $out[] = 'Dear Guest,';
        $out[] = '';
        $out[] = "We are so looking forward to celebrating **{$title}**"
            . (! empty($e['date']) ? " on **{$e['date']}**" : '') . '!';
        $out[] = '';
        $out[] = "{$pendingLine}, and your response helps us confirm catering and seating. "
            . "If you have not yet let us know, please RSVP **{$replyBy}**.";
        $out[] = '';
        $out[] = 'It only takes a moment, and it would mean a great deal to us.';
        $out[] = '';
        $out[] = 'With warm wishes,  ';
        $out[] = 'The Host Team';

        if ($g && $g['total'] > 0) {
            $out[] = '';
            $out[] = "> _Planner note: {$g['confirmed']} confirmed, {$g['declined']} declined, "
                . "{$g['pending']} awaiting reply ({$g['confirmation_rate']}% response rate)._";
        }

        return $this->join($out);
    }

    /** @param array<string,mixed> $c @param array<string,mixed> $in */
    private function clientUpdateEmail(array $c, array $in): string
    {
        $e = $c['event'] ?? [];
        $title = $e['title'] ?? 'your event';
        $client = $this->val($in, 'client_name') ?: 'there';

        $out = [];
        $out[] = "**Subject:** Progress update on {$title}";
        $out[] = '';
        $out[] = "Hi {$client},";
        $out[] = '';
        $out[] = "Here is a quick update on where **{$title}** stands.";
        $out[] = '';

        $bullets = [];
        if (isset($c['budget']) && $c['budget']['total'] > 0) {
            $b = $c['budget'];
            $bullets[] = "**Budget:** {$this->money($b['spent'])} committed of {$this->money($b['total'])} ({$b['utilization_pct']}%).";
        }
        if (isset($c['timeline'])) {
            $t = $c['timeline'];
            $overdue = $t['overdue_count'] > 0 ? " ({$t['overdue_count']} need attention)" : '';
            $bullets[] = "**Tasks:** {$t['tasks_done']}/{$t['tasks_total']} complete{$overdue}.";
        }
        if (isset($c['guests']) && $c['guests']['total'] > 0) {
            $gg = $c['guests'];
            $bullets[] = "**Guests:** {$gg['confirmed']} confirmed of {$gg['total']} invited ({$gg['confirmation_rate']}% responded).";
        }
        if (isset($c['vendors']) && $c['vendors']['count'] > 0) {
            $v = $c['vendors'];
            $bullets[] = "**Vendors:** {$v['count']} engaged" . ($v['pending'] > 0 ? ", {$v['pending']} awaiting confirmation" : ', all confirmed') . '.';
        }

        if ($bullets) {
            foreach ($bullets as $b) {
                $out[] = "- {$b}";
            }
        } else {
            $out[] = 'We are in the early stages and building out the plan now.';
        }
        $out[] = '';
        $out[] = 'I will keep you posted as things progress. Do let me know if you have any questions.';
        $out[] = '';
        $out[] = 'Best regards,  ';
        $out[] = 'Your Event Planner';

        return $this->join($out);
    }

    /** @param array<string,mixed> $c @param array<string,mixed> $in */
    private function budgetOutline(array $c, array $in): string
    {
        $e = $c['event'] ?? [];
        $type = strtolower($this->val($in, 'event_type') ?: ($e['type'] ?? 'event'));
        $totalInput = (float) ($this->val($in, 'total_budget') ?: 0);
        $b = $c['budget'] ?? null;
        $total = $totalInput ?: ($b['total'] ?? 0);

        $splits = $this->budgetSplits($type);

        $out = [];
        $out[] = '# Budget Breakdown Guide';
        $out[] = '_A recommended allocation' . ($type !== 'event' ? " for a {$type}" : '') . '. Adjust to your priorities._';
        $out[] = '';
        $out[] = '| Category | Share | Suggested |';
        $out[] = '| --- | --- | --- |';
        foreach ($splits as $name => $pct) {
            $amount = $total > 0 ? ' ' . $this->money($total * $pct / 100) : ' —';
            $out[] = "| {$name} | {$pct}% |{$amount} |";
        }
        $out[] = '';

        if ($b && $b['total'] > 0) {
            $flag = $b['over_budget'] ? ' You are currently **over budget** — trim discretionary categories first.' : '';
            $out[] = '## Against Your Actuals';
            $out[] = "You have allocated {$this->money($b['total'])} and committed {$this->money($b['spent'])} "
                . "({$b['utilization_pct']}%), leaving {$this->money($b['remaining'])}.{$flag}";
            if (! empty($b['top_categories'])) {
                $out[] = '';
                $out[] = '**Your largest categories so far:**';
                foreach (array_slice($b['top_categories'], 0, 5) as $cat) {
                    $out[] = "- {$cat['name']}: {$this->money($cat['amount'])}";
                }
            }
            $out[] = '';
        }

        $out[] = '## Tips';
        $out[] = '- Hold back **5–10%** as a contingency for surprises.';
        $out[] = '- Lock high-demand vendors early to protect pricing.';
        $out[] = '- Track actuals against estimates weekly to catch drift.';

        return $this->join($out);
    }

    /** @param array<string,mixed> $c @param array<string,mixed> $in */
    private function welcomeSpeech(array $c, array $in): string
    {
        $e = $c['event'] ?? [];
        $occasion = $this->val($in, 'occasion') ?: ($e['title'] ?? 'this special occasion');
        $speaker = $this->val($in, 'speaker_name');
        $tone = strtolower($this->val($in, 'tone') ?: 'warm');

        $opener = match ($tone) {
            'formal' => "Distinguished guests, friends and family — welcome.",
            'light-hearted' => "Well, you all showed up — so I suppose I have to give a speech!",
            default => "Good evening, everyone — and welcome.",
        };

        $out = [];
        $out[] = "# Welcome Speech — {$occasion}";
        $out[] = '';
        $out[] = $opener;
        $out[] = '';
        $out[] = "Thank you all for being here to celebrate **{$occasion}**. "
            . 'It means the world to look around this room and see so many of the people who matter most.';
        $out[] = '';
        $out[] = 'Occasions like this remind us that the moments worth celebrating are the ones we share. '
            . 'Every person here has played some part in the story that brings us together tonight.';
        $out[] = '';
        $out[] = 'So let us make the most of this evening — enjoy the food, the company and the memories we are about to make.';
        $out[] = '';
        $out[] = 'Please, raise your glasses with me. Here is to **' . $occasion . '**, to good times, and to the people who make them unforgettable. Cheers!';
        if ($speaker) {
            $out[] = '';
            $out[] = "— {$speaker}";
        }
        $out[] = '';
        $out[] = '> _Tip: personalise the middle section with a specific memory or two — that is what audiences remember._';

        return $this->join($out);
    }

    /** @param array<string,mixed> $c @param array<string,mixed> $in */
    private function socialAnnouncement(array $c, array $in): string
    {
        $e = $c['event'] ?? [];
        $platform = $this->val($in, 'platform') ?: 'Instagram';
        $headline = $this->val($in, 'headline') ?: 'Save the date';
        $title = $e['title'] ?? 'our event';
        $date = $e['date'] ?? null;

        $tags = $this->hashtags($e['type'] ?? null);

        $out = [];
        $out[] = "# {$platform} Announcement";
        $out[] = '';
        $out[] = '**Option 1 — short & punchy**';
        $out[] = '';
        $out[] = "✨ {$headline}! ✨";
        $out[] = "{$title}" . ($date ? " is happening **{$date}**" : ' is on the way') . ' — and you are invited to be part of it. 🎉';
        $out[] = '';
        $out[] = $tags;
        $out[] = '';
        $out[] = '---';
        $out[] = '';
        $out[] = '**Option 2 — warm & personal**';
        $out[] = '';
        $out[] = "We have been dreaming this up for a while, and we cannot wait to finally share it. "
            . "{$title}" . ($date ? " — mark your calendar for {$date}." : ' is coming soon.') . ' More details to follow. 💛';
        $out[] = '';
        $out[] = $tags;

        return $this->join($out);
    }

    /** @param array<string,mixed> $c @param array<string,mixed> $in */
    private function generic(array $c, array $in): string
    {
        $e = $c['event'] ?? [];
        $title = $e['title'] ?? 'Your Event';

        return $this->join([
            "# {$title}",
            '',
            $this->eventOverviewSentence($e),
        ]);
    }

    // -----------------------------------------------------------------
    // Content banks (best-practice fallbacks by event type)
    // -----------------------------------------------------------------

    /** @return array<string, array<int, string>> */
    private function standardTimeline(?string $type): array
    {
        $t = strtolower((string) $type);
        $wedding = str_contains($t, 'wed');

        if ($wedding) {
            return [
                '12+ months out' => ['Set the budget and guest list size', 'Book the venue and lock the date', 'Hire a planner / key vendors'],
                '6–9 months out' => ['Book photographer, caterer and entertainment', 'Order attire', 'Send save-the-dates'],
                '3–4 months out' => ['Finalise the menu and cake', 'Send invitations', 'Arrange transport and accommodation'],
                '1 month out' => ['Confirm final headcount', 'Create the seating plan', 'Confirm timings with all vendors'],
                'Final week' => ['Final vendor confirmations', 'Prepare payments and tips', 'Delegate day-of responsibilities'],
            ];
        }

        return [
            '3+ months out' => ['Confirm objectives, budget and date', 'Secure the venue', 'Book priority vendors'],
            '1–2 months out' => ['Send invitations / open registration', 'Confirm catering and AV', 'Build the run of show'],
            '2 weeks out' => ['Confirm final numbers', 'Brief the team and vendors', 'Prepare signage and materials'],
            'Final week' => ['Confirm all timings', 'Prepare payments', 'Walkthrough and contingency check'],
        ];
    }

    /** @return array<int, array{0:string,1:string,2:string}> */
    private function runOfShowRows(?string $type): array
    {
        return [
            ['—:—', 'Vendor & team arrival, setup begins', 'Coordinator'],
            ['—:—', 'Final walkthrough & sound check', 'Coordinator'],
            ['—:—', 'Guest arrival & welcome', 'Front of house'],
            ['—:—', 'Main programme begins', 'Host / MC'],
            ['—:—', 'Catering service', 'Caterer'],
            ['—:—', 'Speeches / key moments', 'Host / MC'],
            ['—:—', 'Entertainment / dancing', 'DJ / Band'],
            ['—:—', 'Close & guest departure', 'Coordinator'],
            ['—:—', 'Breakdown & vendor load-out', 'All vendors'],
        ];
    }

    /** @return array<string, int> */
    private function budgetSplits(?string $type): array
    {
        $t = strtolower((string) $type);

        if (str_contains($t, 'wed')) {
            return ['Venue & catering' => 45, 'Photography & video' => 12, 'Attire & beauty' => 8, 'Flowers & décor' => 10, 'Entertainment' => 8, 'Stationery & favours' => 4, 'Planning & coordination' => 8, 'Contingency' => 5];
        }
        if (str_contains($t, 'corp') || str_contains($t, 'confer') || str_contains($t, 'business')) {
            return ['Venue & AV' => 35, 'Catering' => 25, 'Speakers & content' => 12, 'Marketing & branding' => 10, 'Staffing' => 8, 'Contingency' => 10];
        }

        return ['Venue' => 30, 'Catering & drinks' => 30, 'Décor & rentals' => 12, 'Entertainment' => 10, 'Photography' => 8, 'Contingency' => 10];
    }

    private function hashtags(?string $type): string
    {
        $t = strtolower((string) $type);
        if (str_contains($t, 'wed')) {
            return '#Wedding #SaveTheDate #LoveStory #WeddingDay #Celebration';
        }
        if (str_contains($t, 'corp') || str_contains($t, 'confer')) {
            return '#Event #Conference #Networking #SaveTheDate #Business';
        }

        return '#Event #SaveTheDate #Celebration #Party #YouAreInvited';
    }

    // -----------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------

    /** @param array<string,mixed> $e */
    private function eventOverviewSentence(array $e): string
    {
        $title = $e['title'] ?? 'This event';
        $type = ! empty($e['type']) ? " {$e['type']}" : '';
        $date = ! empty($e['date']) ? " on **{$e['date']}**" : '';
        $where = ! empty($e['location']) ? " at {$e['location']}" : '';
        $guests = ! empty($e['expected_guests']) ? " for approximately {$e['expected_guests']} guests" : '';

        return trim("**{$title}** is a{$type} event{$date}{$where}{$guests}.");
    }

    /** @param array<string,mixed> $e */
    private function timingLine(array $e): string
    {
        $days = $e['days_until'] ?? null;
        $date = $e['date'] ?? 'a date to be set';

        if ($days === null) {
            return '_Event date not set yet._';
        }
        if ($days < 0) {
            return "_This event took place on {$date}._";
        }
        if ($days === 0) {
            return "**The event is today** ({$date}).";
        }

        return "**{$days} day(s) to go** — the event is on {$date}.";
    }

    /** @param array<string,mixed> $in */
    private function val(array $in, string $key): string
    {
        $v = $in[$key] ?? '';

        return is_scalar($v) ? trim((string) $v) : '';
    }

    private function money($amount): string
    {
        return 'TZS ' . number_format((float) $amount, 0);
    }

    /** @param array<int, string> $lines */
    private function join(array $lines): string
    {
        return implode("\n", $lines);
    }
}
