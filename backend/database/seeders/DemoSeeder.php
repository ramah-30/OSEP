<?php

namespace Database\Seeders;

use App\Enums\AccountType;
use App\Enums\ApprovalStatus;
use App\Enums\AvailabilityStatus;
use App\Enums\BudgetItemStatus;
use App\Enums\CheckinStatus;
use App\Enums\CommunicationType;
use App\Enums\EventStatus;
use App\Enums\InvitationChannel;
use App\Enums\InvitationStatus;
use App\Enums\MilestoneStatus;
use App\Enums\Priority;
use App\Enums\RsvpResponse;
use App\Enums\RsvpStatus;
use App\Enums\TaskStatus;
use App\Enums\UserStatus;
use App\Enums\VendorAssignmentStatus;
use App\Enums\VerificationStatus;
use App\Models\Approval;
use App\Models\Event;
use App\Models\EventMilestone;
use App\Models\Notification;
use App\Models\PlannerReview;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * A believable, self-contained demo tenant so the planner workspace and the
 * three dashboards show real relational data on first run. Everything hangs off
 * known @osep.test logins (password: Password123!) and is safe to wipe before
 * going live. Idempotent: keyed on natural columns so re-seeding is a no-op.
 *
 * Guarded to non-production in DatabaseSeeder.
 */
class DemoSeeder extends Seeder
{
    private const PASSWORD = 'Password123!';

    public function run(): void
    {
        $planner = $this->planner();
        [$john, $amina, $daniel] = $this->clients();
        [$zawadi, $neema] = $this->vendors();

        $wedding = $this->wedding($planner, $john, $zawadi, $neema);
        $this->extraPlannerEvents($planner, $amina, $daniel);

        $this->plannerReviews($planner, [$john, $amina, $daniel]);
        $this->notifications($planner, $john, $wedding);
    }

    private function planner(): User
    {
        $planner = $this->user('planner@osep.test', 'Sarah', 'Bennett', AccountType::EventPlanner);

        $planner->plannerProfile()->updateOrCreate([], [
            'company_name' => 'Elegant Events Ltd',
            'experience_years' => 8,
            'specialization' => 'Luxury weddings & corporate galas',
            'bio' => 'Award-winning planning studio crafting unforgettable celebrations across East Africa.',
            'location' => 'Dar es Salaam, Tanzania',
            'website' => 'https://elegantevents.example',
        ]);

        return $planner;
    }

    /**
     * @return array<int, User>
     */
    private function clients(): array
    {
        $john = $this->user('client@osep.test', 'John', 'Carter', AccountType::Client);
        $john->clientProfile()->updateOrCreate([], [
            'preferred_event_types' => ['Wedding', 'Anniversary'],
            'communication_preference' => 'Email',
            'location' => 'Dar es Salaam, Tanzania',
        ]);

        $amina = $this->user('amina@osep.test', 'Amina', 'Hassan', AccountType::Client);
        $amina->clientProfile()->updateOrCreate([], [
            'preferred_event_types' => ['Corporate'],
            'communication_preference' => 'Phone',
            'location' => 'Arusha, Tanzania',
        ]);

        $daniel = $this->user('daniel@osep.test', 'Daniel', 'Okoro', AccountType::Client);
        $daniel->clientProfile()->updateOrCreate([], [
            'preferred_event_types' => ['Conference'],
            'communication_preference' => 'Email',
            'location' => 'Nairobi, Kenya',
        ]);

        return [$john, $amina, $daniel];
    }

    /**
     * @return array<int, User>
     */
    private function vendors(): array
    {
        $vendor = $this->user('vendor@osep.test', 'Zawadi', 'Mushi', AccountType::Vendor);
        $vendor->vendorProfile()->updateOrCreate([], [
            'business_name' => 'Zawadi Photography',
            'category' => 'Photography',
            'description' => 'Timeless wedding and event photography with a documentary eye.',
            'location' => 'Dar es Salaam, Tanzania',
            'phone' => '+255700000001',
            'website' => 'https://zawadiphoto.example',
            'social_links' => ['instagram' => 'https://instagram.com/zawadiphoto'],
            'verification_status' => VerificationStatus::Verified,
            'availability_status' => AvailabilityStatus::Available,
            'profile_views' => 1284,
            'pending_requests' => 5,
            'completed_jobs' => 47,
            'reviews_count' => 39,
            'rating' => 4.85,
        ]);

        $caterer = $this->user('caterer@osep.test', 'Neema', 'Kimaro', AccountType::Vendor);
        $caterer->vendorProfile()->updateOrCreate([], [
            'business_name' => 'Neema Catering Co.',
            'category' => 'Catering',
            'description' => 'Farm-to-table catering for weddings and corporate events.',
            'location' => 'Arusha, Tanzania',
            'verification_status' => VerificationStatus::Verified,
            'availability_status' => AvailabilityStatus::Busy,
            'profile_views' => 862,
            'pending_requests' => 2,
            'completed_jobs' => 63,
            'reviews_count' => 51,
            'rating' => 4.70,
        ]);

        return [$vendor, $caterer];
    }

    private function wedding(User $planner, User $john, User $zawadi, User $neema): Event
    {
        $event = Event::updateOrCreate(
            ['planner_id' => $planner->id, 'title' => "Sarah & John's Wedding"],
            [
                'client_id' => $john->id,
                'event_code' => 'EVT-2026-000001',
                'event_type' => 'Wedding',
                'event_category' => 'Wedding',
                'event_date' => '2026-08-15',
                'start_time' => '15:00',
                'end_time' => '23:00',
                'venue' => 'The Waterfront Pavilion',
                'location' => 'Dar es Salaam, Tanzania',
                'expected_guests' => 180,
                'description' => 'An elegant waterfront wedding with a blush and ivory palette.',
                'theme' => 'Blush & Ivory Garden',
                'priority' => Priority::High->value,
                'internal_notes' => 'Client prefers WhatsApp for quick questions.',
                'status' => EventStatus::Execution->value,
                'progress' => 65,
                'budget_total' => 45_000_000,
                'budget_spent' => 27_500_000,
            ],
        );

        $this->timeline($event, $planner);
        $this->tasks($event, $planner);
        $this->mealOptions($event);
        $this->guests($event, $planner);
        $this->venue($event);
        $this->vendorAssignments($event, $zawadi, $neema);
        $this->budget($event);
        $this->approvals($event, $planner);
        $this->documents($event, $planner);
        $this->activity($event, $planner);
        $this->venueLayout($event, $planner);

        $event->recalculateBudgetSpent();

        return $event;
    }

    private function timeline(Event $event, User $planner): void
    {
        $milestones = [
            ['Book Venue', MilestoneStatus::Completed, '2026-03-01'],
            ['Finalize Guest List', MilestoneStatus::InProgress, '2026-07-30'],
            ['Decoration Approval', MilestoneStatus::WaitingApproval, '2026-07-28'],
            ['Cake Tasting', MilestoneStatus::Pending, '2026-08-02'],
            ['Photography Meeting', MilestoneStatus::Pending, '2026-08-05'],
            ['Venue Setup', MilestoneStatus::Pending, '2026-08-14'],
            ['Event Day', MilestoneStatus::Pending, '2026-08-15'],
        ];

        foreach ($milestones as $i => [$name, $status, $due]) {
            EventMilestone::updateOrCreate(
                ['event_id' => $event->id, 'name' => $name],
                [
                    'status' => $status,
                    'due_date' => $due,
                    'assigned_to' => $planner->id,
                    'position' => $i + 1,
                ],
            );
        }
    }

    private function tasks(Event $event, User $planner): void
    {
        $tasks = [
            ['Confirm final guest count', TaskStatus::NotStarted, Priority::High, '2026-07-29'],
            ['Send invitations', TaskStatus::InProgress, Priority::Medium, '2026-07-27'],
            ['Finalize decoration theme', TaskStatus::WaitingApproval, Priority::High, '2026-07-28'],
            ['Book venue', TaskStatus::Completed, Priority::High, '2026-03-01'],
            ['Confirm catering menu', TaskStatus::Completed, Priority::Medium, '2026-06-10'],
            ['Cake tasting appointment', TaskStatus::NotStarted, Priority::Low, '2026-08-02'],
            ['Photography walkthrough', TaskStatus::InProgress, Priority::Medium, '2026-08-05'],
        ];

        foreach ($tasks as $i => [$title, $status, $priority, $due]) {
            $event->tasks()->updateOrCreate(
                ['title' => $title],
                [
                    'status' => $status->value,
                    'priority' => $priority->value,
                    'due_date' => $due,
                    'assigned_to' => $planner->id,
                    'position' => $i,
                    'completed_at' => $status === TaskStatus::Completed ? now()->subDays(10) : null,
                ],
            );
        }
    }

    private function mealOptions(Event $event): void
    {
        $meals = [
            ['Standard Plated', 'Three-course plated dinner', null],
            ['Vegetarian', 'Seasonal vegetarian menu', 'vegetarian'],
            ['Vegan', 'Fully plant-based menu', 'vegan'],
            ['Seafood-free', 'No shellfish or fish', 'allergy'],
            ['Kids Meal', 'Child-friendly portions', null],
        ];

        foreach ($meals as $i => [$name, $desc, $tags]) {
            $event->mealOptions()->updateOrCreate(
                ['name' => $name],
                ['description' => $desc, 'dietary_tags' => $tags, 'is_active' => true, 'sort' => $i],
            );
        }
    }

    /**
     * A realistic guest list exercising every RSVP / invitation / check-in state,
     * with matching invitations, responses, tickets and communication history.
     */
    private function guests(Event $event, User $planner): void
    {
        // name, category, rsvp, meal, email, phone, plus_ones, invitation, checkin
        $guests = [
            ['Grace', 'Mwakalinga', 'VIP', RsvpStatus::Confirmed, 'Vegetarian', 'grace.m@example.com', '+255711000101', 2, InvitationStatus::Opened, CheckinStatus::CheckedIn],
            ['Peter', 'Sanga', 'Family', RsvpStatus::Confirmed, 'Standard Plated', 'peter.sanga@example.com', '+255711000102', 1, InvitationStatus::Delivered, CheckinStatus::CheckedIn],
            ['Fatma', 'Ally', 'Family', RsvpStatus::Pending, null, 'fatma.ally@example.com', '+255711000103', 0, InvitationStatus::Delivered, CheckinStatus::Pending],
            ['Michael', 'Ndosi', 'Friends', RsvpStatus::Confirmed, 'Seafood-free', 'm.ndosi@example.com', '+255711000104', 1, InvitationStatus::Opened, CheckinStatus::Pending],
            ['Lucy', 'Kimolo', 'Friends', RsvpStatus::Declined, null, 'lucy.k@example.com', '+255711000105', 0, InvitationStatus::Delivered, CheckinStatus::Pending],
            ['James M.', 'Kamau', 'VIP', RsvpStatus::Pending, null, 'jm.kamau@example.com', '+255711000106', 1, InvitationStatus::Sent, CheckinStatus::Pending],
            ['Aisha', 'Rashid', 'Speakers', RsvpStatus::Confirmed, 'Vegan', 'aisha.r@example.com', '+255711000107', 0, InvitationStatus::Opened, CheckinStatus::CheckedIn],
            ['Daily Post', 'Media', 'Media', RsvpStatus::Confirmed, 'Standard Plated', 'desk@dailypost.example', '+255711000108', 0, InvitationStatus::Delivered, CheckinStatus::Pending],
            ['CRDB', 'Bank', 'Sponsors', RsvpStatus::Maybe, null, 'events@crdb.example', '+255711000109', 0, InvitationStatus::Opened, CheckinStatus::Pending],
            ['Brian', 'Otieno', 'Friends', RsvpStatus::Pending, null, 'brian.o@example.com', '+255711000110', 0, InvitationStatus::Draft, CheckinStatus::Pending],
        ];

        foreach ($guests as [$first, $last, $category, $rsvp, $meal, $email, $phone, $plus, $inviteStatus, $checkin]) {
            $responded = in_array($rsvp, [RsvpStatus::Confirmed, RsvpStatus::Declined, RsvpStatus::Maybe], true);

            $guest = $event->guests()->updateOrCreate(
                ['email' => $email],
                [
                    'first_name' => $first,
                    'last_name' => $last,
                    'category' => $category,
                    'phone' => $phone,
                    'plus_ones_allowed' => $plus,
                    'rsvp_status' => $rsvp->value,
                    'invitation_status' => $inviteStatus->value,
                    'checkin_status' => $checkin->value,
                    'meal_preference' => $meal,
                    'rsvp_responded_at' => $responded ? now()->subDays(random_int(2, 12)) : null,
                    'checked_in_at' => $checkin === CheckinStatus::CheckedIn ? now()->subHours(random_int(1, 4)) : null,
                ],
            );

            $this->guestJourney($event, $guest, $planner, $rsvp, $meal, $plus, $inviteStatus, $checkin, $responded);
        }
    }

    private function guestJourney(Event $event, $guest, User $planner, RsvpStatus $rsvp, ?string $meal, int $plus, InvitationStatus $inviteStatus, CheckinStatus $checkin, bool $responded): void
    {
        // Invitation (unless still a draft), with a small delivery trail.
        if ($inviteStatus !== InvitationStatus::Draft) {
            $invitation = $event->invitations()->updateOrCreate(
                ['guest_id' => $guest->id, 'channel' => InvitationChannel::Email->value],
                [
                    'created_by' => $planner->id,
                    'status' => $inviteStatus->value,
                    'subject' => "You're invited to {$event->title}",
                    'sent_at' => now()->subDays(14),
                    'delivered_at' => now()->subDays(14),
                    'opened_at' => $inviteStatus === InvitationStatus::Opened ? now()->subDays(10) : null,
                    'meta' => ['kind' => 'invitation'],
                ],
            );

            foreach (['sent', 'delivered'] as $step) {
                $invitation->deliveryLogs()->firstOrCreate(
                    ['status' => $step],
                    ['channel' => 'email', 'detail' => ucfirst($step), 'occurred_at' => now()->subDays(14)],
                );
            }
            if ($inviteStatus === InvitationStatus::Opened) {
                $invitation->deliveryLogs()->firstOrCreate(
                    ['status' => 'opened'],
                    ['channel' => 'email', 'detail' => 'Guest opened the invitation', 'occurred_at' => now()->subDays(10)],
                );
            }

            $guest->communicationLogs()->firstOrCreate(
                ['type' => CommunicationType::Invitation->value, 'title' => 'Invitation sent'],
                ['event_id' => $event->id, 'created_by' => $planner->id, 'channel' => 'email'],
            );
        }

        // RSVP response.
        if ($responded) {
            $response = match ($rsvp) {
                RsvpStatus::Confirmed => RsvpResponse::Attending,
                RsvpStatus::Declined => RsvpResponse::NotAttending,
                default => RsvpResponse::Maybe,
            };

            $event->rsvpResponses()->updateOrCreate(
                ['guest_id' => $guest->id, 'responded_at' => $guest->rsvp_responded_at],
                [
                    'response' => $response->value,
                    'additional_guests' => $response === RsvpResponse::Attending ? min($plus, 1) : 0,
                    'meal_choice' => $meal,
                ],
            );

            $guest->communicationLogs()->firstOrCreate(
                ['type' => CommunicationType::Rsvp->value, 'title' => 'RSVP: '.$response->label()],
                ['event_id' => $event->id],
            );
        }

        // Ticket for confirmed guests.
        if ($rsvp === RsvpStatus::Confirmed) {
            $guest->qrCode()->updateOrCreate(
                ['guest_id' => $guest->id],
                [
                    'event_id' => $event->id,
                    'token' => 'TCK-DEMO-'.strtoupper(substr(md5($guest->email), 0, 20)),
                    'ticket_type' => $guest->category ?: 'standard',
                    'payload' => ['guest_id' => $guest->id, 'event_id' => $event->id, 'guest_name' => $guest->full_name],
                    'issued_at' => now()->subDays(9),
                ],
            );
        }

        // Check-in.
        if ($checkin === CheckinStatus::CheckedIn) {
            $guest->checkin()->updateOrCreate(
                ['guest_id' => $guest->id],
                [
                    'event_id' => $event->id,
                    'checked_in_by' => $planner->id,
                    'method' => 'qr',
                    'party_size' => 1 + min($plus, 1),
                    'checked_in_at' => $guest->checked_in_at,
                ],
            );

            $guest->communicationLogs()->firstOrCreate(
                ['type' => CommunicationType::Checkin->value, 'title' => 'Checked in'],
                ['event_id' => $event->id, 'created_by' => $planner->id, 'detail' => 'Via QR scan'],
            );
        }
    }

    private function venue(Event $event): void
    {
        $event->venueDetail()->updateOrCreate([], [
            'name' => 'The Waterfront Pavilion',
            'address' => 'Toure Drive, Oyster Bay, Dar es Salaam',
            'capacity' => 250,
            'setting' => 'mixed',
            'contact_person' => 'Rehema Juma',
            'contact_phone' => '+255700111222',
            'parking_available' => true,
            'setup_time' => '10:00',
            'breakdown_time' => '23:30',
            'notes' => 'Generator backup available. Sound curfew at midnight.',
        ]);
    }

    private function vendorAssignments(Event $event, User $zawadi, User $neema): void
    {
        $rows = [
            [$zawadi->id, 'Zawadi Photography', 'Photography', '8-hour coverage · 2 photographers', 6_000_000, VendorAssignmentStatus::Accepted],
            [$neema->id, 'Neema Catering Co.', 'Catering', 'Plated dinner for 180', 9_500_000, VendorAssignmentStatus::Accepted],
            [null, 'Blooms & Co.', 'Florals & Decoration', 'Floral arch + ceiling installation', 3_000_000, VendorAssignmentStatus::Requested],
        ];

        foreach ($rows as [$vendorId, $name, $service, $package, $price, $status]) {
            $event->vendorAssignments()->updateOrCreate(
                ['vendor_name' => $name],
                [
                    'vendor_id' => $vendorId,
                    'service' => $service,
                    'package' => $package,
                    'price' => $price,
                    'status' => $status->value,
                ],
            );
        }
    }

    private function budget(Event $event): void
    {
        $items = [
            ['Venue', 'Waterfront Pavilion hire', 12_000_000, 12_000_000, BudgetItemStatus::Paid],
            ['Catering', 'Plated dinner for 180', 10_000_000, 9_500_000, BudgetItemStatus::Paid],
            ['Photography', '8-hour coverage', 6_000_000, 0, BudgetItemStatus::Planned],
            ['Decoration', 'Floral arch & ceiling install', 7_000_000, 3_000_000, BudgetItemStatus::Committed],
            ['Entertainment', 'Live band & DJ', 4_000_000, 0, BudgetItemStatus::Planned],
            ['Transportation', 'Bridal cars', 2_000_000, 2_000_000, BudgetItemStatus::Paid],
            ['Miscellaneous', 'Stationery & favours', 2_000_000, 1_000_000, BudgetItemStatus::Committed],
        ];

        foreach ($items as [$category, $description, $estimated, $actual, $status]) {
            $event->budgetItems()->updateOrCreate(
                ['description' => $description],
                [
                    'category' => $category,
                    'estimated_cost' => $estimated,
                    'actual_cost' => $actual,
                    'status' => $status->value,
                ],
            );
        }
    }

    private function approvals(Event $event, User $planner): void
    {
        $approvals = [
            ['Decoration Proposal', 'decoration', 'Blush & ivory floral arch with draped ceiling installation.', ApprovalStatus::Pending, null],
            ['Photography Package', 'vendor_selection', '8-hour coverage, two photographers, engagement shoot included.', ApprovalStatus::Pending, null],
            ['Catering Quotation', 'budget', 'Three-course plated dinner for 180 guests.', ApprovalStatus::Approved, now()->subDays(4)],
        ];

        foreach ($approvals as [$title, $type, $description, $status, $decidedAt]) {
            $approval = Approval::updateOrCreate(
                ['event_id' => $event->id, 'title' => $title],
                [
                    'submitted_by' => $planner->id,
                    'type' => $type,
                    'description' => $description,
                    'status' => $status,
                    'decided_at' => $decidedAt,
                ],
            );

            $approval->history()->firstOrCreate(
                ['action' => 'submitted'],
                ['user_id' => $planner->id, 'note' => null],
            );

            if ($status === ApprovalStatus::Approved) {
                $approval->history()->firstOrCreate(
                    ['action' => $status->value],
                    ['user_id' => $event->client_id, 'note' => 'Looks great, approved.'],
                );
            }
        }
    }

    private function documents(Event $event, User $planner): void
    {
        $docs = [
            ['Venue Contract.pdf', 'contract'],
            ['Catering Quotation.pdf', 'quotation'],
            ['Floor Plan.pdf', 'floor_plan'],
            ['Planning Checklist.pdf', 'checklist'],
        ];

        foreach ($docs as [$name, $category]) {
            $event->documents()->updateOrCreate(
                ['name' => $name],
                [
                    'uploaded_by' => $planner->id,
                    'category' => $category,
                    'file_path' => "events/{$event->id}/documents/".str($name)->slug('_'),
                    'mime_type' => 'application/pdf',
                    'size' => random_int(120_000, 900_000),
                    'version' => 1,
                ],
            );
        }
    }

    private function activity(Event $event, User $planner): void
    {
        // Mirrors what the real controllers log for each of these actions
        // (see ActivityLogger call sites), including which ones are
        // client-visible, so the client dashboard's Updates timeline has
        // something real to show out of the box.
        $entries = [
            ['event_created', "created the event \"{$event->title}\"", false],
            ['venue_added', 'set the venue to "The Waterfront Pavilion"', false],
            ['vendor_booking_accepted', 'Zawadi Photography confirmed their booking.', true],
            ['vendor_booking_accepted', 'Neema Catering Co. confirmed their booking.', true],
            ['budget_updated', 'added a budget line for Venue', false],
            ['approval_submitted', 'submitted "Decoration Proposal" for client approval', false],
            ['task_completed', 'completed task "Book venue"', false],
            ['approval_decision', 'Approved "Catering Quotation"', false],
            ['quotation_sent', 'Catering Quotation was sent for approval.', true],
            ['contract_payment', 'Paid TZS 3,000,000.00 towards Zawadi Photography\'s contract.', true],
        ];

        foreach ($entries as $i => [$action, $description, $visibleToClient]) {
            $event->activities()->updateOrCreate(
                ['action' => $action, 'description' => $description],
                [
                    'user_id' => $planner->id,
                    'visible_to_client' => $visibleToClient,
                    'created_at' => now()->subDays(count($entries) - $i),
                    'updated_at' => now()->subDays(count($entries) - $i),
                ],
            );
        }
    }

    private function venueLayout(Event $event, User $planner): void
    {
        $layout = $event->venueLayouts()->updateOrCreate(
            ['layout_name' => 'Reception Layout'],
            [
                'created_by' => $planner->id,
                'venue_name' => 'The Waterfront Pavilion',
                'venue_type' => 'Banquet Hall',
                'setting' => 'mixed',
                'width' => 40,
                'height' => 30,
                'unit' => 'm',
                'max_capacity' => 250,
                'entry_points' => 2,
                'exit_points' => 3,
                'version' => 1,
                'meta' => [
                    'layers' => [
                        ['id' => 'furniture', 'name' => 'Furniture', 'hidden' => false, 'locked' => false],
                        ['id' => 'stage', 'name' => 'Stage', 'hidden' => false, 'locked' => false],
                        ['id' => 'decoration', 'name' => 'Decoration', 'hidden' => false, 'locked' => false],
                        ['id' => 'facilities', 'name' => 'Facilities', 'hidden' => false, 'locked' => false],
                    ],
                    'grid' => true,
                    'snap' => true,
                ],
            ],
        );

        $objects = [
            ['uid' => 'demo-stage', 'object_type' => 'medium_stage', 'object_name' => 'Main Stage', 'x' => 14, 'y' => 1.5, 'width' => 12, 'height' => 4, 'color' => '#7c3aed', 'layer' => 'stage'],
            ['uid' => 'demo-dancefloor', 'object_type' => 'dance_floor_medium', 'object_name' => 'Dance Floor', 'x' => 16, 'y' => 12, 'width' => 8, 'height' => 8, 'color' => '#a78bfa', 'layer' => 'decoration'],
            ['uid' => 'demo-entrance', 'object_type' => 'entrance', 'object_name' => 'Main Entrance', 'x' => 18, 'y' => 28, 'width' => 4, 'height' => 1.5, 'color' => '#10b981', 'layer' => 'facilities'],
            ['uid' => 'demo-bar', 'object_type' => 'bar', 'object_name' => 'Bar', 'x' => 33, 'y' => 13, 'width' => 5, 'height' => 2, 'color' => '#1e3a8a', 'layer' => 'facilities'],
        ];

        // A ring of round tables (10 seats each).
        $positions = [[4, 8], [4, 16], [4, 22], [33, 7], [33, 21], [10, 24], [26, 24]];
        foreach ($positions as $i => [$x, $y]) {
            $objects[] = [
                'uid' => "demo-table-{$i}",
                'object_type' => 'round_table',
                'object_name' => 'Table '.($i + 1),
                'x' => $x, 'y' => $y, 'width' => 3, 'height' => 3,
                'color' => '#ffffff', 'layer' => 'furniture',
                'properties' => ['seats' => 10],
            ];
        }

        foreach ($objects as $o) {
            $layout->objects()->updateOrCreate(
                ['uid' => $o['uid']],
                [
                    'object_type' => $o['object_type'],
                    'object_name' => $o['object_name'],
                    'x_position' => $o['x'],
                    'y_position' => $o['y'],
                    'width' => $o['width'],
                    'height' => $o['height'],
                    'rotation' => 0,
                    'color' => $o['color'],
                    'layer' => $o['layer'],
                    'properties' => $o['properties'] ?? null,
                ],
            );
        }

        // Seat a couple of real guests at the first table.
        $vip = $layout->objects()->where('uid', 'demo-table-0')->first();
        if ($vip) {
            $guests = $event->guests()->limit(2)->get();
            foreach ($guests as $i => $guest) {
                $vip->seating()->updateOrCreate(
                    ['seat_number' => $i + 1],
                    ['guest_id' => $guest->id, 'notes' => null],
                );
            }
            $vip->seating()->updateOrCreate(['seat_number' => 3], ['notes' => 'Reserved']);
        }
    }

    private function extraPlannerEvents(User $planner, User $amina, User $daniel): void
    {
        $gala = Event::updateOrCreate(
            ['planner_id' => $planner->id, 'title' => 'TechCorp Annual Gala'],
            [
                'client_id' => $amina->id,
                'event_code' => 'EVT-2026-000002',
                'event_type' => 'Corporate Event',
                'event_category' => 'Corporate Event',
                'event_date' => '2026-11-20',
                'venue' => 'Mlimani City Conference Centre',
                'location' => 'Dar es Salaam, Tanzania',
                'expected_guests' => 400,
                'priority' => Priority::Medium->value,
                'status' => EventStatus::Planning->value,
                'progress' => 30,
                'budget_total' => 62_000_000,
                'budget_spent' => 12_000_000,
            ],
        );

        foreach (['Confirm keynote speaker' => TaskStatus::InProgress, 'Draft run of show' => TaskStatus::NotStarted] as $title => $status) {
            $gala->tasks()->updateOrCreate(['title' => $title], ['status' => $status->value, 'priority' => Priority::Medium->value, 'assigned_to' => $planner->id]);
        }

        Event::updateOrCreate(
            ['planner_id' => $planner->id, 'title' => 'East Africa Founders Summit'],
            [
                'client_id' => $daniel->id,
                'event_code' => 'EVT-2026-000003',
                'event_type' => 'Conference',
                'event_category' => 'Conference',
                'event_date' => '2026-03-10',
                'venue' => 'Serena Hotel',
                'location' => 'Nairobi, Kenya',
                'expected_guests' => 300,
                'priority' => Priority::Low->value,
                'status' => EventStatus::Completed->value,
                'progress' => 100,
                'budget_total' => 38_000_000,
                'budget_spent' => 36_500_000,
            ],
        );
    }

    /**
     * A few client reviews of the planner so the badge, rating and reviews page
     * have believable data. Each review is tied to that client's own event.
     *
     * @param  array<int, User>  $clients
     */
    private function plannerReviews(User $planner, array $clients): void
    {
        $ratings = [5, 5, 4];
        $comments = [
            'Sarah and her team were flawless - every detail handled, zero stress on the day. Would book again in a heartbeat.',
            'Incredible communication throughout and a beautiful result. Our guests are still talking about it.',
            'Very professional and well organised. A couple of small timing hiccups, but nothing that affected the day.',
        ];

        foreach ($clients as $i => $client) {
            $event = Event::where('planner_id', $planner->id)
                ->where('client_id', $client->id)
                ->first();

            PlannerReview::updateOrCreate(
                ['planner_id' => $planner->id, 'reviewer_id' => $client->id],
                [
                    'event_id' => $event?->id,
                    'rating' => $ratings[$i] ?? 5,
                    'comment' => $comments[$i] ?? null,
                ],
            );
        }
    }

    private function notifications(User $planner, User $john, Event $wedding): void
    {
        $rows = [
            [$planner, 'client_request', 'New client enquiry', 'Amina Hassan requested a proposal for a corporate gala.'],
            [$planner, 'vendor_quotation', 'Vendor quotation received', 'Zawadi Photography submitted a quote for the wedding.'],
            [$planner, 'approval_completed', 'Catering approved', "John Carter approved the catering quotation for {$wedding->title}."],
            [$john, 'proposal', 'Proposal ready for review', 'Your decoration proposal is ready - please review and approve.'],
            [$john, 'payment_reminder', 'Payment reminder', 'A deposit of TZS 5,000,000 is due on 30 July 2026.'],
            [$john, 'planning_update', 'Planning update', 'Catering has been confirmed. You are now 65% complete.'],
        ];

        foreach ($rows as [$user, $type, $title, $message]) {
            Notification::updateOrCreate(
                ['user_id' => $user->id, 'title' => $title],
                ['type' => $type, 'message' => $message, 'data' => ['event_id' => $wedding->id]],
            );
        }
    }

    private function user(string $email, string $first, string $last, AccountType $type): User
    {
        $user = User::updateOrCreate(
            ['email' => $email],
            [
                'first_name' => $first,
                'last_name' => $last,
                'phone' => '+255700000000',
                'password' => Hash::make(self::PASSWORD),
                'account_type' => $type,
                'country' => 'Tanzania',
                'status' => UserStatus::Active,
                'email_verified_at' => now(),
            ],
        );

        $user->assignRole($type->value);

        return $user;
    }
}
