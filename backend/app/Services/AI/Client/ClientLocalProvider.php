<?php

namespace App\Services\AI\Client;

use App\Services\AI\Contracts\AiProvider;
use Illuminate\Support\Str;

/**
 * The offline, data-grounded reasoning engine for the CLIENT concierge. Every
 * answer is assembled from the client's own event data — their event and
 * countdown, guest list, approvals, payments, planner updates and booking
 * requests — in a warm, reassuring tone. No API key, no internet, no cost.
 */
class ClientLocalProvider implements AiProvider
{
    public function name(): string
    {
        return 'local';
    }

    public function chat(string $system, array $messages, array $context = []): array
    {
        $prompt = Str::lower($this->lastUserMessage($messages));

        $reply = match (true) {
            $this->mentions($prompt, ['find a planner', 'find planner', 'find me a planner', 'recommend', 'suggest a planner', 'looking for a planner', 'browse planner', 'which planner', 'best planner', 'available planner', 'a good planner', 'book a planner', 'book planner', 'hire']) => $this->findPlannersAnswer($context),
            $this->mentions($prompt, ['approv', 'sign off', 'decision', 'decide', 'waiting on me']) => $this->approvalsAnswer($context),
            $this->mentions($prompt, ['pay', 'invoice', 'balance', 'owe', 'cost', 'money', 'bill', 'deposit', 'due']) => $this->financeAnswer($context),
            $this->mentions($prompt, ['guest', 'rsvp', 'invite', 'attend', 'headcount', 'confirm']) => $this->guestsAnswer($context),
            $this->mentions($prompt, ['my request', 'my booking', 'request status', 'booking request', 'sent to']) => $this->requestsAnswer($context),
            $this->mentions($prompt, ['update', 'news', 'happening', 'progress', 'latest', 'timeline', 'ready']) => $this->progressAnswer($context),
            $this->mentions($prompt, ['focus', 'priorit', 'todo', 'to do', 'what should', 'next', 'need to', 'attention']) => $this->focusAnswer($context),
            $this->mentions($prompt, ['how is', "how's", 'summary', 'summarize', 'summarise', 'overview', 'status', 'my event', 'my wedding', 'where']) => $this->summaryAnswer($context),
            $this->mentions($prompt, ['hello', 'hi ', 'hey', 'help', 'what can you', 'who are you']) || $prompt === 'hi' || $prompt === 'hello' => $this->greeting($context),
            ! empty($context['event']) => $this->summaryAnswer($context),
            default => $this->greeting($context),
        };

        return ['content' => $reply, 'model' => 'client-local-heuristic'];
    }

    private function greeting(array $context): string
    {
        $name = config('ai.client_assistant_name', 'OSEP Planning Concierge');
        $event = $context['event'] ?? null;

        if ($event) {
            return "Hi — I'm your {$name}, here to help with **{$event['title']}**. I can see your event's progress, "
                . "guest list, approvals, payments and updates from your planner. Try asking me:\n\n"
                . "- *How's my event coming along?*\n"
                . "- *What do I need to approve?*\n"
                . "- *How many guests have confirmed?*\n"
                . "- *What's my outstanding balance?*\n"
                . "- *What should I take care of next?*";
        }

        return "Hi — I'm your {$name}. Once you've booked a planner and your event is set up, I'll keep you on top of "
            . "everything: approvals to give, payments due, RSVPs and the latest updates from your planner. You can start "
            . "by finding a planner and sending a booking request.";
    }

    private function summaryAnswer(array $context): string
    {
        $event = $context['event'] ?? null;
        if (! $event) {
            return $this->greeting($context);
        }

        $lines = ["Here's where **{$event['title']}** stands:"];

        $when = $this->countdown($event);
        $lines[] = "\n- 📅 **When:** {$when}";
        if ($event['planner']) {
            $lines[] = "- 🤝 **Your planner:** {$event['planner']}";
        }
        $lines[] = "- 📈 **Planning progress:** {$event['progress']}%";

        if ($g = $context['guests'] ?? null) {
            if ($g['total'] > 0) {
                $lines[] = "- 👥 **Guests:** {$g['confirmed']} confirmed of {$g['total']} ({$g['pending']} awaiting reply)";
            }
        }
        if ($a = $context['approvals'] ?? null) {
            if ($a['pending'] > 0) {
                $lines[] = "- ✅ **Approvals waiting on you:** {$a['pending']}";
            }
        }
        if ($f = $context['finance'] ?? null) {
            if ($f['outstanding_amount'] > 0) {
                $lines[] = "- 💳 **Outstanding balance:** {$this->money($f['outstanding_amount'])}";
            }
        }

        $focus = $this->topReminder($context);
        if ($focus) {
            $lines[] = "\n**What I'd take care of next:** {$focus['title']} — {$focus['description']}";
        }

        return implode("\n", $lines);
    }

    private function approvalsAnswer(array $context): string
    {
        $a = $context['approvals'] ?? null;
        if (! $a || $a['pending'] === 0) {
            return "You're all caught up — there's nothing waiting for your approval right now. I'll flag anything new "
                . "the moment your planner sends it over.";
        }

        $lines = ["You have **{$a['pending']} approval(s)** waiting for your decision:"];
        foreach ($a['list'] as $item) {
            $type = $item['type'] ? " _({$item['type']})_" : '';
            $ev = $item['event'] ? " — {$item['event']}" : '';
            $lines[] = "- **{$item['title']}**{$type}{$ev}";
        }
        $lines[] = "\nGiving your planner a timely decision keeps everything on schedule.";

        return implode("\n", $lines);
    }

    private function financeAnswer(array $context): string
    {
        $f = $context['finance'] ?? null;
        if (! $f) {
            return "I don't see any invoices on your account yet. When your planner issues one, I'll track what's due "
                . "and when so nothing slips.";
        }

        if ($f['outstanding_amount'] <= 0) {
            return "You're fully paid up — nothing outstanding. 🎉";
        }

        $lines = ['**Your payments**'];
        $lines[] = "- Outstanding: {$this->money($f['outstanding_amount'])} across {$f['invoices_outstanding']} invoice(s)";
        if ($f['next_due_date']) {
            $amt = $f['next_due_amount'] !== null ? " ({$this->money($f['next_due_amount'])})" : '';
            $lines[] = "- Next due: {$f['next_due_date']}{$amt}";
        }
        if ($f['overdue_count'] > 0) {
            $lines[] = "\n⚠️ **{$f['overdue_count']} payment(s) are overdue.** Settling these first avoids any hold-ups with your planning.";
        }

        return implode("\n", $lines);
    }

    private function guestsAnswer(array $context): string
    {
        $g = $context['guests'] ?? null;
        if (! $g || $g['total'] === 0) {
            return "There aren't any guests on your list yet. Add them and I'll track RSVPs and confirmed headcounts for you.";
        }

        $lines = ['**Your guest list**'];
        $lines[] = "- Invited: {$g['total']}";
        $lines[] = "- Confirmed: {$g['confirmed']}";
        $lines[] = "- Declined: {$g['declined']}";
        $lines[] = "- Awaiting reply: {$g['pending']}";
        $lines[] = "- Response rate: {$g['confirmation_rate']}%";

        if ($g['pending'] > 0) {
            $lines[] = "\n💡 A gentle reminder to the {$g['pending']} guest(s) who haven't replied will firm up your final headcount.";
        }

        return implode("\n", $lines);
    }

    private function requestsAnswer(array $context): string
    {
        $r = $context['requests'] ?? null;
        if (! $r || $r['total'] === 0) {
            return "You haven't sent any booking requests yet. Browse planners and send a request describing your event — "
                . "they'll reply with how they can help.";
        }

        $lines = ['**Your booking requests**'];
        $lines[] = "- Sent: {$r['total']}";
        $lines[] = "- Awaiting a reply: {$r['pending']}";
        $lines[] = "- Responded: {$r['responded']}";

        if ($r['accepted'] > 0) {
            $lines[] = "\n🎉 A planner has **accepted** — review their response and take the next step to confirm them.";
        } elseif ($r['pending'] > 0) {
            $lines[] = "\nPlanners usually reply within a few days. I'll let you know as soon as one responds.";
        }

        return implode("\n", $lines);
    }

    private function findPlannersAnswer(array $context): string
    {
        $planners = $context['planners'] ?? null;
        if (empty($planners)) {
            return "I can't see any planners to recommend just yet. New planners join OSEP regularly — check back soon, "
                . "and I'll help you send a booking request the moment you find one you like.";
        }

        // Surface the best-reviewed first so the recommendation is genuinely useful.
        usort($planners, fn ($a, $b) => ($b['rating'] ?? 0) <=> ($a['rating'] ?? 0));

        $lines = ['Here are some planners you could book:'];
        foreach (array_slice($planners, 0, 5) as $p) {
            $name = $p['company'] ?? $p['name'];
            $bits = [];
            if (! empty($p['specialization'])) {
                $bits[] = $p['specialization'];
            }
            if (! empty($p['location'])) {
                $bits[] = "📍 {$p['location']}";
            }
            if (! empty($p['experience_years'])) {
                $bits[] = "{$p['experience_years']} yrs";
            }
            if (($p['reviews_count'] ?? 0) > 0) {
                $bits[] = "⭐ {$p['rating']} ({$p['reviews_count']})";
            }
            $meta = $bits ? ' — ' . implode(' · ', $bits) : '';
            $lines[] = "- **{$name}**{$meta}";
        }

        $lines[] = "\nWant me to book one? Just say **“book {$this->firstPlannerName($planners)}”** and I'll prepare the "
            . "request for your approval before anything is sent.";

        return implode("\n", $lines);
    }

    private function firstPlannerName(array $planners): string
    {
        $first = $planners[0] ?? null;

        return $first ? ($first['company'] ?? $first['name'] ?? 'a planner') : 'a planner';
    }

    private function progressAnswer(array $context): string
    {
        $event = $context['event'] ?? null;
        if (! $event) {
            return $this->greeting($context);
        }

        $lines = ["**{$event['title']} — progress summary**"];
        $lines[] = "- 📈 Planning is **{$event['progress']}%** complete";
        $lines[] = '- 📅 ' . $this->countdown($event);
        if ($event['planner']) {
            $lines[] = "- 🤝 Planner: {$event['planner']}";
        }

        if (($g = $context['guests'] ?? null) && $g['total'] > 0) {
            $lines[] = "- 👥 Guests: {$g['confirmed']} confirmed of {$g['total']} ({$g['pending']} awaiting reply)";
        }
        if (($a = $context['approvals'] ?? null) && $a['pending'] > 0) {
            $lines[] = "- ✅ Approvals waiting on you: {$a['pending']}";
        }
        if (($f = $context['finance'] ?? null) && $f['outstanding_amount'] > 0) {
            $lines[] = "- 💳 Outstanding balance: {$this->money($f['outstanding_amount'])}";
        }

        $u = $context['updates'] ?? null;
        if ($u && ! empty($u['recent'])) {
            $lines[] = "\n**Latest from your planner:**";
            foreach ($u['recent'] as $item) {
                $when = $item['when'] ? " _({$item['when']})_" : '';
                $lines[] = "- {$item['title']}{$when}";
            }
        }

        $focus = $this->topReminder($context);
        if ($focus) {
            $lines[] = "\n**Next up:** {$focus['title']} — {$focus['description']}";
        }

        return implode("\n", $lines);
    }

    private function focusAnswer(array $context): string
    {
        $reminders = $this->reminders($context);

        if (empty($reminders)) {
            return "You're in great shape — no approvals waiting, payments current, and your guest list is on track. "
                . "Just enjoy the run-up to your event!";
        }

        $lines = ["Here's what I'd take care of, most important first:"];
        foreach ($reminders as $i => $reminder) {
            $n = $i + 1;
            $lines[] = "{$n}. **{$reminder['title']}** — {$reminder['description']}";
        }

        return implode("\n", $lines);
    }

    // -----------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------

    /** @return array<int, array<string, mixed>> */
    private function reminders(array $context): array
    {
        return app(ClientReminderEngine::class)->fromContext($context);
    }

    /** @return array<string, mixed>|null */
    private function topReminder(array $context): ?array
    {
        return $this->reminders($context)[0] ?? null;
    }

    private function countdown(array $event): string
    {
        $days = $event['days_until'] ?? null;
        $date = $event['date'] ?? 'a date not yet set';

        if ($days === null) {
            return 'Date not set yet';
        }
        if ($days < 0) {
            return "Took place on {$date}";
        }
        if ($days === 0) {
            return "**Today** ({$date})";
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
