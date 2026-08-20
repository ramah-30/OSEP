<?php

namespace App\Services\AI;

/**
 * The built-in AI template library. Each entry is a reusable blueprint the
 * copilot fills with real event data. Definitions live here (not in a migration)
 * so the catalog can evolve freely; TemplateController upserts them into
 * ai_templates by `key` on demand, and DocumentGenerator uses `key` to select
 * both the offline composer and the live-LLM instruction.
 *
 * @phpstan-type TemplateDef array{key:string,category:string,name:string,description:string,icon:string,requires_event:bool,variables:array,body_template:string,sort_order:int}
 */
class DocumentTemplateCatalog
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function all(): array
    {
        return [
            [
                'key' => 'client_proposal',
                'category' => 'proposal',
                'name' => 'Client Event Proposal',
                'description' => 'A polished proposal for a client — scope, plan, budget summary and next steps, drawn from the event.',
                'icon' => 'FileText',
                'requires_event' => true,
                'variables' => [
                    ['key' => 'client_name', 'label' => 'Client name', 'type' => 'text', 'required' => false, 'placeholder' => 'e.g. The Bennett family'],
                    ['key' => 'company', 'label' => 'Your company / signature', 'type' => 'text', 'required' => false, 'placeholder' => 'e.g. OSEP Events'],
                ],
                'sort_order' => 10,
            ],
            [
                'key' => 'planning_timeline',
                'category' => 'timeline',
                'name' => 'Planning Timeline',
                'description' => 'A countdown planning schedule to the event date — grounded in your milestones, or a best-practice plan if none exist yet.',
                'icon' => 'CalendarClock',
                'requires_event' => true,
                'variables' => [],
                'sort_order' => 20,
            ],
            [
                'key' => 'run_of_show',
                'category' => 'checklist',
                'name' => 'Run of Show (Day-Of Schedule)',
                'description' => 'A minute-by-minute day-of schedule and coordination checklist for the event.',
                'icon' => 'ListChecks',
                'requires_event' => true,
                'variables' => [
                    ['key' => 'start_time', 'label' => 'Guest arrival time', 'type' => 'text', 'required' => false, 'placeholder' => 'e.g. 4:00 PM'],
                ],
                'sort_order' => 30,
            ],
            [
                'key' => 'vendor_brief',
                'category' => 'vendor',
                'name' => 'Vendor Brief / RFQ',
                'description' => 'A brief to send a vendor requesting a quote — event details, requirements and what to include.',
                'icon' => 'Store',
                'requires_event' => true,
                'variables' => [
                    ['key' => 'service_needed', 'label' => 'Service needed', 'type' => 'text', 'required' => true, 'placeholder' => 'e.g. Catering for 120 guests'],
                ],
                'sort_order' => 40,
            ],
            [
                'key' => 'rsvp_reminder_email',
                'category' => 'email',
                'name' => 'RSVP Reminder Email',
                'description' => 'A warm reminder email to guests who have not yet responded, referencing real RSVP numbers and the deadline.',
                'icon' => 'Mail',
                'requires_event' => true,
                'variables' => [
                    ['key' => 'reply_by', 'label' => 'Reply-by date', 'type' => 'text', 'required' => false, 'placeholder' => 'e.g. two weeks before'],
                ],
                'sort_order' => 50,
            ],
            [
                'key' => 'client_update_email',
                'category' => 'email',
                'name' => 'Client Status Update Email',
                'description' => 'A concise progress update to the client — where the event stands across budget, tasks, guests and vendors.',
                'icon' => 'MailCheck',
                'requires_event' => true,
                'variables' => [
                    ['key' => 'client_name', 'label' => 'Client name', 'type' => 'text', 'required' => false, 'placeholder' => 'e.g. Sarah'],
                ],
                'sort_order' => 60,
            ],
            [
                'key' => 'budget_outline',
                'category' => 'budget',
                'name' => 'Budget Breakdown Guide',
                'description' => 'A recommended budget allocation for the event — measured against your real figures where a budget exists.',
                'icon' => 'Wallet',
                'requires_event' => false,
                'variables' => [
                    ['key' => 'event_type', 'label' => 'Event type', 'type' => 'text', 'required' => false, 'placeholder' => 'e.g. Wedding'],
                    ['key' => 'total_budget', 'label' => 'Total budget (TZS)', 'type' => 'number', 'required' => false, 'placeholder' => 'e.g. 20000000'],
                ],
                'sort_order' => 70,
            ],
            [
                'key' => 'welcome_speech',
                'category' => 'speech',
                'name' => 'Welcome Speech / Toast',
                'description' => 'A heartfelt welcome speech or toast, personalised to the occasion and the event.',
                'icon' => 'Sparkles',
                'requires_event' => false,
                'variables' => [
                    ['key' => 'occasion', 'label' => 'Occasion', 'type' => 'text', 'required' => true, 'placeholder' => 'e.g. 50th birthday celebration'],
                    ['key' => 'speaker_name', 'label' => 'Speaker name', 'type' => 'text', 'required' => false, 'placeholder' => 'e.g. James'],
                    ['key' => 'tone', 'label' => 'Tone', 'type' => 'select', 'required' => false, 'options' => ['Warm', 'Formal', 'Light-hearted'], 'placeholder' => 'Warm'],
                ],
                'sort_order' => 80,
            ],
            [
                'key' => 'social_announcement',
                'category' => 'social',
                'name' => 'Social Media Announcement',
                'description' => 'Ready-to-post announcement copy with hashtags to build anticipation for the event.',
                'icon' => 'Sparkle',
                'requires_event' => false,
                'variables' => [
                    ['key' => 'platform', 'label' => 'Platform', 'type' => 'select', 'required' => false, 'options' => ['Instagram', 'Facebook', 'LinkedIn', 'X / Twitter'], 'placeholder' => 'Instagram'],
                    ['key' => 'headline', 'label' => 'What are you announcing?', 'type' => 'text', 'required' => true, 'placeholder' => 'e.g. Save the date'],
                ],
                'sort_order' => 90,
            ],
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function find(string $key): ?array
    {
        foreach ($this->all() as $def) {
            if ($def['key'] === $key) {
                return $def;
            }
        }

        return null;
    }
}
