<?php

namespace App\Services\AI;

use App\Models\AiConversation;
use App\Models\BudgetItem;
use App\Models\Event;
use App\Models\Guest;
use App\Models\User;
use App\Models\VendorAssignment;

/**
 * The offline onboarding coach. A brand-new planner has no data for the copilot
 * to ground on, so instead of an empty workspace it assesses which setup
 * milestones are done and hands back the next best step — each a concrete, deep
 * link into the product. Pure signal checks over existing data: no model needed.
 */
class OnboardingCoachService
{
    /**
     * Assess the planner's setup and return an ordered, deep-linked checklist.
     *
     * @return array{complete:bool, progress:int, done_count:int, total:int, next:?array<string,mixed>, steps:array<int, array<string,mixed>>}
     */
    public function for(User $user): array
    {
        $eventIds = Event::where('planner_id', $user->id)->pluck('id');
        $hasEvent = $eventIds->isNotEmpty();

        $focus = $hasEvent
            ? Event::where('planner_id', $user->id)->orderByDesc('id')->first()
            : null;
        $eventBase = $focus ? "/dashboard/planner/events/{$focus->id}" : '/dashboard/planner/events';

        $hasBudget = $hasEvent && (
            Event::where('planner_id', $user->id)->where('budget_total', '>', 0)->exists()
            || BudgetItem::whereIn('event_id', $eventIds)->exists()
        );
        $hasGuests = $hasEvent && Guest::whereIn('event_id', $eventIds)->exists();
        $hasVendor = $hasEvent && VendorAssignment::whereIn('event_id', $eventIds)->exists();
        $triedCopilot = AiConversation::where('user_id', $user->id)->exists();

        $steps = [
            $this->step(
                'create_event', 'Create your first event', 'CalendarPlus', $hasEvent,
                'Set up an event so the copilot has real data to plan and reason over.',
                '/dashboard/planner/events', 'New event',
            ),
            $this->step(
                'build_budget', 'Set up a budget', 'Wallet', $hasBudget,
                'Add a budget and line items — this powers spend tracking, forecasts and what-if planning.',
                $hasEvent ? "{$eventBase}/budget" : '/dashboard/planner/events', 'Add budget',
            ),
            $this->step(
                'add_guests', 'Add your guest list', 'Users', $hasGuests,
                'Import or add guests to unlock RSVP tracking, seating and catering headcounts.',
                $hasEvent ? "{$eventBase}/guests" : '/dashboard/planner/events', 'Add guests',
            ),
            $this->step(
                'assign_vendor', 'Assign a vendor', 'Store', $hasVendor,
                'Source and assign vendors so the copilot can track readiness and benchmark their quotes.',
                $hasEvent ? "{$eventBase}/vendors" : '/dashboard/planner/marketplace/vendors', 'Find vendors',
            ),
            $this->step(
                'try_copilot', 'Ask your copilot a question', 'Sparkles', $triedCopilot,
                'Try “summarize where this event stands” or “what if 20 more guests confirm?” — every answer is grounded in your data.',
                null, 'Open copilot', 'chat',
            ),
        ];

        $done = count(array_filter($steps, fn ($s) => $s['done']));
        $total = count($steps);
        $next = null;
        foreach ($steps as $step) {
            if (! $step['done']) {
                $next = $step;
                break;
            }
        }

        return [
            'complete' => $done === $total,
            'progress' => (int) round($done / $total * 100),
            'done_count' => $done,
            'total' => $total,
            'next' => $next,
            'steps' => $steps,
        ];
    }

    /**
     * @return array{key:string, title:string, icon:string, done:bool, description:string, href:?string, cta:string, action:?string}
     */
    private function step(string $key, string $title, string $icon, bool $done, string $description, ?string $href, string $cta, ?string $action = null): array
    {
        return compact('key', 'title', 'icon', 'done', 'description', 'href', 'cta', 'action');
    }
}
