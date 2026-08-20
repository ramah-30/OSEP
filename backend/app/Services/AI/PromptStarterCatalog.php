<?php

namespace App\Services\AI;

/**
 * A small library of ready-made prompts the planner can add to their own library
 * with one click. These are suggestions only — once added they become editable,
 * versioned templates owned by the planner (see PromptController@store). The
 * {{variables}} are surfaced as fill-in fields when the prompt is run.
 */
class PromptStarterCatalog
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public static function all(): array
    {
        return [
            [
                'key' => 'event_status_briefing',
                'name' => 'Event status briefing',
                'category' => 'Overview',
                'description' => 'A concise status check across budget, timeline, guests and vendors.',
                'body' => "Give me a status briefing for this event. Cover:\n"
                    . "1. Budget: are we on track vs the {{focus_area}} area?\n"
                    . "2. Timeline: any overdue or at-risk tasks\n"
                    . "3. Guests & vendors that still need attention\n\n"
                    . 'End with the top 3 actions I should take this week.',
            ],
            [
                'key' => 'client_check_in_email',
                'name' => 'Client check-in email',
                'category' => 'Client comms',
                'description' => 'A warm progress update email to the client, grounded in real status.',
                'body' => "Draft a friendly check-in email to my client {{client_name}} about their event. "
                    . "Summarise what's on track, mention one thing I need from them, and keep the tone "
                    . 'reassuring and professional. Sign off as {{planner_name}}.',
            ],
            [
                'key' => 'budget_risk_scan',
                'name' => 'Budget risk scan',
                'category' => 'Budget',
                'description' => 'Surface the line items most likely to overrun and how to protect the margin.',
                'body' => "Analyse this event's budget. Identify the categories closest to or over budget, "
                    . "estimate where we'll land, and suggest specific ways to protect a target margin of "
                    . '{{target_margin}}%. Flag anything that needs a decision now.',
            ],
            [
                'key' => 'vendor_shortlist_brief',
                'name' => 'Vendor shortlist brief',
                'category' => 'Vendors',
                'description' => 'Turn a category need into a crisp brief for approaching vendors.',
                'body' => "I need a {{vendor_category}} for this event. Based on the event's details and budget, "
                    . 'write a short brief I can send to candidates: scope, date, headcount, budget range and '
                    . 'the key questions I should ask before booking.',
            ],
            [
                'key' => 'run_of_show_gaps',
                'name' => 'Run-of-show gap check',
                'category' => 'Timeline',
                'description' => 'Pressure-test the day-of schedule for missing transitions and buffers.',
                'body' => "Review the day-of timeline for this event and find the gaps: missing transitions, "
                    . 'tight turnarounds, and moments with no owner. Suggest buffer times and who should be '
                    . 'responsible for each critical handoff.',
            ],
        ];
    }
}
