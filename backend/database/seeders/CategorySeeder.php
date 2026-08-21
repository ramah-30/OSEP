<?php

namespace Database\Seeders;

use App\Models\BudgetCategory;
use App\Models\EventCategory;
use App\Models\GuestCategory;
use App\Models\InvitationTemplate;
use Illuminate\Database\Seeder;

/**
 * Global default option catalogues (null owner) for the event, guest and budget
 * pickers, plus the starter invitation-template library. Planners may add their
 * own on top; safe in every environment, so it runs alongside the other
 * reference seeders.
 */
class CategorySeeder extends Seeder
{
    /** @var array<int, string> */
    private array $eventTypes = [
        'Wedding', 'Birthday', 'Corporate Event', 'Conference', 'Seminar', 'Graduation',
        'Baby Shower', 'Concert', 'Festival', 'Exhibition', 'Product Launch', 'Fundraiser',
        'Government Event', 'Religious Event', 'Private Event', 'Other',
    ];

    /**
     * Default guest categories with colour, priority (1 = highest) and a suggested
     * seating area. Mirrors the Phase 4 spec.
     *
     * @var array<int, array{name:string, color:string, priority:int, area:?string}>
     */
    private array $guestCategories = [
        ['name' => 'VIP', 'color' => '#b45309', 'priority' => 1, 'area' => 'Front / Head table'],
        ['name' => 'Family', 'color' => '#be123c', 'priority' => 1, 'area' => 'Front rows'],
        ['name' => 'Friends', 'color' => '#2563eb', 'priority' => 2, 'area' => 'Middle'],
        ['name' => 'Business Guests', 'color' => '#0f766e', 'priority' => 2, 'area' => 'Middle'],
        ['name' => 'Sponsors', 'color' => '#7c3aed', 'priority' => 1, 'area' => 'Front / Reserved'],
        ['name' => 'Speakers', 'color' => '#c2410c', 'priority' => 1, 'area' => 'Stage side'],
        ['name' => 'Media', 'color' => '#0891b2', 'priority' => 3, 'area' => 'Press area'],
        ['name' => 'Staff', 'color' => '#475569', 'priority' => 4, 'area' => 'Back / Service'],
        ['name' => 'Vendors', 'color' => '#65a30d', 'priority' => 4, 'area' => 'Service area'],
        ['name' => 'General Admission', 'color' => '#64748b', 'priority' => 3, 'area' => 'Open seating'],
    ];

    /** @var array<int, string> */
    private array $budgetCategories = [
        'Venue', 'Catering', 'Decoration', 'Photography', 'Entertainment', 'Transportation',
        'Accommodation', 'Printing', 'Security', 'Miscellaneous', 'Other',
    ];

    public function run(): void
    {
        foreach ($this->eventTypes as $name) {
            EventCategory::firstOrCreate(['created_by' => null, 'name' => $name]);
        }

        foreach ($this->guestCategories as $cat) {
            GuestCategory::updateOrCreate(
                ['created_by' => null, 'name' => $cat['name']],
                [
                    'color' => $cat['color'],
                    'priority' => $cat['priority'],
                    'default_seating_area' => $cat['area'],
                    'is_default' => true,
                ],
            );
        }

        foreach ($this->budgetCategories as $name) {
            BudgetCategory::firstOrCreate(['created_by' => null, 'name' => $name]);
        }

        $this->seedTemplates();
    }

    private function seedTemplates(): void
    {
        $templates = [
            [
                'name' => 'Classic Wedding', 'type' => 'wedding',
                'subject' => "You're invited to our wedding",
                'body' => "Dear {{first_name}},\n\nTogether with our families, we joyfully invite you to celebrate our wedding. Your presence would mean the world to us.\n\nWith love.",
                'theme' => ['primary' => '#b45309', 'accent' => '#fcd34d', 'font' => 'serif'],
            ],
            [
                'name' => 'Birthday Bash', 'type' => 'birthday',
                'subject' => "Let's celebrate!",
                'body' => "Hi {{first_name}},\n\nYou're invited to a birthday celebration full of fun, food and good company. Come ready to party!",
                'theme' => ['primary' => '#be123c', 'accent' => '#fda4af', 'font' => 'sans'],
            ],
            [
                'name' => 'Conference Invite', 'type' => 'conference',
                'subject' => 'Your invitation to {{event}}',
                'body' => "Hello {{first_name}},\n\nWe're pleased to invite you to {{event}}. Join industry leaders for a day of insight and networking.",
                'theme' => ['primary' => '#1e3a8a', 'accent' => '#93c5fd', 'font' => 'sans'],
            ],
            [
                'name' => 'Corporate Event', 'type' => 'corporate',
                'subject' => 'You are invited - {{event}}',
                'body' => "Dear {{first_name}},\n\nWe would be honoured to have you join us at {{event}}. Kindly let us know if you can attend.",
                'theme' => ['primary' => '#0f766e', 'accent' => '#5eead4', 'font' => 'sans'],
            ],
            [
                'name' => 'Graduation', 'type' => 'graduation',
                'subject' => 'Come celebrate our graduate',
                'body' => "Hi {{first_name}},\n\nWe're proud to invite you to celebrate this milestone. Your support has meant so much on this journey.",
                'theme' => ['primary' => '#7c3aed', 'accent' => '#c4b5fd', 'font' => 'serif'],
            ],
        ];

        foreach ($templates as $template) {
            InvitationTemplate::updateOrCreate(
                ['created_by' => null, 'name' => $template['name']],
                [...$template, 'is_default' => true],
            );
        }
    }
}
