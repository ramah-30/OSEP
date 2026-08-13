<?php

namespace App\Services\AI\Vendor;

use App\Services\AI\Contracts\AiProvider;
use Illuminate\Support\Str;

/**
 * The offline, data-grounded reasoning engine for the VENDOR copilot. Mirrors
 * the planner's offline provider, but every intent and answer is about running a
 * marketplace business: the booking pipeline, quotations and win rate, contracts
 * and revenue, reviews and rating, availability and storefront readiness.
 *
 * Answers are assembled purely from the vendor's own structured data — no API
 * key, no internet, no cost — so the copilot is useful the moment it's opened.
 */
class VendorLocalProvider implements AiProvider
{
    public function name(): string
    {
        return 'local';
    }

    public function chat(string $system, array $messages, array $context = []): array
    {
        $prompt = Str::lower($this->lastUserMessage($messages));

        $reply = match (true) {
            $this->mentions($prompt, ['request', 'booking', 'lead', 'enquir', 'inquir', 'pipeline']) => $this->requestsAnswer($context),
            $this->mentions($prompt, ['quote', 'quotation', 'win rate', 'proposal', 'expir']) => $this->quotationsAnswer($context),
            $this->mentions($prompt, ['revenue', 'contract', 'earnings', 'income', 'money', 'paid', 'finance', 'sales']) => $this->contractsAnswer($context),
            $this->mentions($prompt, ['review', 'rating', 'star', 'reputation', 'feedback']) => $this->reviewsAnswer($context),
            $this->mentions($prompt, ['availab', 'calendar', 'free', 'booked', 'date']) => $this->availabilityAnswer($context),
            $this->mentions($prompt, ['storefront', 'profile', 'listing', 'portfolio', 'package', 'service', 'photo']) => $this->storefrontAnswer($context),
            $this->mentions($prompt, ['focus', 'priorit', 'todo', 'to do', 'what should', 'next', 'attention', 'risk', 'important']) => $this->focusAnswer($context),
            $this->mentions($prompt, ['how is', "how's", 'summary', 'summarize', 'summarise', 'overview', 'status', 'business', 'doing']) => $this->summaryAnswer($context),
            $this->mentions($prompt, ['hello', 'hi ', 'hey', 'help', 'what can you', 'who are you']) || $prompt === 'hi' || $prompt === 'hello' => $this->greeting($context),
            ! empty($context['vendor']) => $this->summaryAnswer($context),
            default => $this->greeting($context),
        };

        return ['content' => $reply, 'model' => 'vendor-local-heuristic'];
    }

    private function greeting(array $context): string
    {
        $name = config('ai.vendor_assistant_name', 'OSEP Vendor Copilot');
        $business = $context['vendor']['business_name'] ?? null;
        $who = $business ? " for **{$business}**" : '';

        return "Hi — I'm {$name}, your marketplace business copilot{$who}. I can see your booking requests, "
            . "quotations, contracts, reviews and availability. Try asking me:\n\n"
            . "- *How's my business doing?*\n"
            . "- *Which requests need a reply?*\n"
            . "- *What's my quotation win rate?*\n"
            . "- *How much revenue have I booked?*\n"
            . "- *What should I focus on today?*";
    }

    private function summaryAnswer(array $context): string
    {
        $v = $context['vendor'] ?? null;
        $lines = ['Here\'s where your business stands' . ($v && $v['business_name'] ? " — **{$v['business_name']}**" : '') . ':'];

        if ($r = $context['requests'] ?? null) {
            $lines[] = "\n- 📥 **Requests:** {$r['open']} awaiting reply, {$r['accepted']} accepted";
        }
        if ($q = $context['quotations'] ?? null) {
            $win = $q['win_rate'] !== null ? ", {$q['win_rate']}% win rate" : '';
            $lines[] = "- 📝 **Quotations:** {$q['open']} open{$win}";
        }
        if ($c = $context['contracts'] ?? null) {
            $lines[] = "- 💰 **Revenue booked:** {$this->money($c['revenue'])} ({$c['active']} active contract(s))";
        }
        if ($rev = $context['reviews'] ?? null) {
            $rating = $rev['average_rating'] !== null ? "{$rev['average_rating']}★ over {$rev['total']} review(s)" : 'no reviews yet';
            $lines[] = "- ⭐ **Rating:** {$rating}";
        }

        $focus = $this->topReminder($context);
        if ($focus) {
            $lines[] = "\n**Where I'd focus next:** {$focus['title']} — {$focus['description']}";
        }

        return implode("\n", $lines);
    }

    private function requestsAnswer(array $context): string
    {
        $r = $context['requests'] ?? null;
        if (! $r || $r['total'] === 0) {
            return "You have no booking requests yet. A complete storefront with packages and portfolio photos is the "
                . "best way to start attracting planners — ask me *how do I improve my storefront?*";
        }

        $lines = ['**Booking pipeline**'];
        $lines[] = "- Awaiting your reply: {$r['open']} ({$r['pending']} new, {$r['info_requested']} info-requested)";
        $lines[] = "- Accepted: {$r['accepted']}";
        $lines[] = "- Declined: {$r['declined']}";

        if ($r['open'] > 0 && $r['oldest_pending_days'] !== null) {
            $lines[] = "\n⏱️ Your oldest open request is **{$r['oldest_pending_days']} day(s)** old. Planners often book the "
                . "first solid vendor to reply — respond promptly to lift your win rate.";
        }

        if (! empty($r['open_list'])) {
            $lines[] = "\n**Awaiting reply:**";
            foreach ($r['open_list'] as $item) {
                $meta = array_filter([$item['planner'], $item['event_date'], $item['budget'] > 0 ? $this->money($item['budget']) : null]);
                $suffix = $meta ? ' — ' . implode(' · ', $meta) : '';
                $lines[] = "- **{$item['title']}**{$suffix}";
            }
        }

        return implode("\n", $lines);
    }

    private function quotationsAnswer(array $context): string
    {
        $q = $context['quotations'] ?? null;
        if (! $q || $q['total'] === 0) {
            return "You haven't sent any quotations yet. When a booking request comes in, send a clear, itemised quote "
                . "quickly — speed and clarity are what win marketplace work.";
        }

        $lines = ['**Quotations**'];
        $lines[] = "- Open (sent / negotiating): {$q['open']}";
        $lines[] = "- Accepted: {$q['accepted']}";
        $lines[] = "- Rejected: {$q['rejected']}";
        if ($q['win_rate'] !== null) {
            $lines[] = "- **Win rate: {$q['win_rate']}%**";
        }

        if ($q['expiring_soon'] > 0) {
            $lines[] = "\n⚠️ **{$q['expiring_soon']} quotation(s) expire within 7 days.** Follow up before they lapse:";
            foreach ($q['expiring_list'] as $item) {
                $lines[] = "- {$item['reference']} ({$this->money($item['total'])}) — expires {$item['expires']}";
            }
        }

        return implode("\n", $lines);
    }

    private function contractsAnswer(array $context): string
    {
        $c = $context['contracts'] ?? null;
        if (! $c || $c['total'] === 0) {
            return "No contracts yet. Once a planner accepts a quotation you can turn it into a contract — that's where "
                . "quoted work becomes secured revenue.";
        }

        $lines = ['**Contracts & revenue**'];
        $lines[] = "- Revenue (active + completed): {$this->money($c['revenue'])}";
        $lines[] = "- Active contracts: {$c['active']}";
        $lines[] = "- Completed: {$c['completed']}";
        $lines[] = "- Pipeline (not yet active): {$this->money($c['pipeline_value'])}";

        if ($c['awaiting_signature'] > 0) {
            $lines[] = "\n✍️ **{$c['awaiting_signature']} contract(s) await signature** — that's revenue not yet locked in. Chase the signatures.";
        }

        return implode("\n", $lines);
    }

    private function reviewsAnswer(array $context): string
    {
        $rev = $context['reviews'] ?? null;
        if (! $rev || $rev['total'] === 0) {
            return "You have no published reviews yet. After completing a booking, a happy planner's review is the "
                . "single strongest signal to future clients — it's worth asking for one.";
        }

        $lines = ['**Reviews & rating**'];
        $lines[] = "- Average rating: {$rev['average_rating']}★ over {$rev['total']} review(s)";
        $lines[] = "- Awaiting your reply: {$rev['unreplied']}" . ($rev['unreplied_negative'] > 0 ? " ({$rev['unreplied_negative']} critical)" : '');

        if ($rev['unreplied_negative'] > 0) {
            $lines[] = "\n💬 Reply calmly and professionally to the critical review(s) first — a measured public response "
                . "reassures future planners more than the rating alone worries them.";
        } elseif ($rev['unreplied'] > 0) {
            $lines[] = "\n💬 Thanking reviewers publicly signals an engaged, professional vendor.";
        } else {
            $lines[] = "\n✅ Every review has a reply — great engagement.";
        }

        return implode("\n", $lines);
    }

    private function availabilityAnswer(array $context): string
    {
        $a = $context['availability'] ?? null;
        if (! $a || ! $a['has_calendar']) {
            return "Your availability calendar is empty. Planners filter vendors by open dates, so marking when you're "
                . "free is one of the fastest ways to surface in more searches.";
        }

        $lines = ['**Availability (next 60 days)**'];
        $lines[] = "- Open dates: {$a['available_upcoming']}";
        $lines[] = "- Busy / unavailable: {$a['busy_upcoming']}";
        $lines[] = '- Next open date: ' . ($a['next_available'] ?? 'none marked');

        if ($a['available_upcoming'] === 0) {
            $lines[] = "\n⚠️ You have no open dates in the next 60 days — if that's not right, update your calendar so planners can book you.";
        }

        return implode("\n", $lines);
    }

    private function storefrontAnswer(array $context): string
    {
        $s = $context['storefront'] ?? null;
        if (! $s) {
            return "I can't see your storefront details yet.";
        }

        $lines = ['**Storefront readiness**'];
        $lines[] = "- Services: {$s['services']}";
        $lines[] = "- Packages: {$s['packages']}";
        $lines[] = "- Portfolio images: {$s['portfolio']}";

        if (! empty($s['missing'])) {
            $missing = implode(', ', $s['missing']);
            $lines[] = "\n📋 To convert more of the planners who view you, add: **{$missing}**. Complete storefronts with "
                . "photos and clear packages win far more enquiries.";
        } else {
            $lines[] = "\n✅ Your storefront is complete — services, packages, portfolio, description and logo are all in place.";
        }

        return implode("\n", $lines);
    }

    private function focusAnswer(array $context): string
    {
        $reminders = $this->reminders($context);

        if (empty($reminders)) {
            return "You're in good shape — no requests waiting, quotes current, reviews answered and your storefront is "
                . "complete. Keep your availability up to date and the enquiries flowing.";
        }

        $lines = ['Here\'s what I\'d focus on, most important first:'];
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
        return app(VendorReminderEngine::class)->fromContext($context);
    }

    /** @return array<string, mixed>|null */
    private function topReminder(array $context): ?array
    {
        return $this->reminders($context)[0] ?? null;
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
