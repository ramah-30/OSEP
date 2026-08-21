<?php

namespace Database\Seeders;

use App\Enums\AccountType;
use App\Enums\UserStatus;
use App\Models\Accommodation;
use App\Models\BookingRequest;
use App\Models\Contract;
use App\Models\MarketplaceVenue;
use App\Models\MessageThread;
use App\Models\Quotation;
use App\Models\Review;
use App\Models\SavedCollection;
use App\Models\User;
use App\Models\VendorCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * A populated marketplace for demos: vendors across every category, venue
 * listings, packages/portfolios/availability, plus a live transaction trail
 * (booking requests → quotations → a signed contract), reviews, saved lists and
 * a message thread - all hung off the known @osep.test logins. Idempotent.
 *
 * Guarded to non-production in DatabaseSeeder (runs after DemoSeeder).
 */
class MarketplaceSeeder extends Seeder
{
    private const PASSWORD = 'Password123!';

    /** @var array<string, VendorCategory> keyed by slug */
    private array $categories = [];

    public function run(): void
    {
        $this->categories = VendorCategory::all()->keyBy('slug')->all();

        $this->admin();
        $vendors = $this->vendors();
        $venues = $this->venues();
        $this->accommodations();

        $planner = User::where('email', 'planner@osep.test')->first();
        if ($planner) {
            $this->transactions($planner, $vendors, $venues);
            $this->reviews($planner, $vendors, $venues);
            $this->savedLists($planner, $vendors, $venues);
            $this->messages($planner, $vendors['zawadi']);
        }
    }

    private function admin(): void
    {
        $admin = User::updateOrCreate(
            ['email' => 'admin@osep.test'],
            [
                'first_name' => 'Amir', 'last_name' => 'Rashidi',
                'phone' => '+255700000099', 'password' => Hash::make(self::PASSWORD),
                'account_type' => AccountType::Admin, 'country' => 'Tanzania',
                'status' => UserStatus::Active, 'email_verified_at' => now(),
            ],
        );
        $admin->assignRole('admin');
    }

    /**
     * @return array<string, User>
     */
    private function vendors(): array
    {
        // key, email, first, last, business, tagline, category slug, location, years, level, featured, rating, reviews, jobs, response
        $rows = [
            ['zawadi', 'vendor@osep.test', 'Zawadi', 'Mushi', 'Zawadi Photography', 'Timeless documentary photography', 'photographers', 'Dar es Salaam, Tanzania', 9, 'premium_partner', true, 4.85, 39, 47, 2],
            ['neema', 'caterer@osep.test', 'Neema', 'Kimaro', 'Neema Catering Co.', 'Farm-to-table event catering', 'caterers', 'Arusha, Tanzania', 12, 'business_verified', true, 4.70, 51, 63, 3],
            ['blooms', 'decor@osep.test', 'Halima', 'Said', 'Blooms & Co.', 'Statement florals & decor', 'decorators', 'Dar es Salaam, Tanzania', 6, 'business_verified', true, 4.60, 28, 34, 4],
            ['pulse', 'dj@osep.test', 'Kevin', 'Otieno', 'Pulse DJs', 'Reading the room since 2015', 'djs', 'Nairobi, Kenya', 8, 'business_verified', false, 4.75, 44, 120, 1],
            ['baraka', 'mc@osep.test', 'Baraka', 'Mnyika', 'Baraka Events MC', 'Bilingual master of ceremonies', 'mcs', 'Dodoma, Tanzania', 5, 'email_verified', false, 4.50, 19, 61, 2],
            ['groove', 'band@osep.test', 'Coastal', 'Groove', 'Coastal Groove Band', 'Live Afro-fusion for any stage', 'live-bands', 'Zanzibar, Tanzania', 7, 'business_verified', false, 4.65, 22, 40, 6],
            ['glow', 'makeup@osep.test', 'Aisha', 'Juma', 'Glow Studio', 'Bridal & editorial makeup', 'makeup-artists', 'Dar es Salaam, Tanzania', 4, 'business_verified', false, 4.90, 33, 58, 2],
            ['kilimanjaro', 'transport@osep.test', 'Emanuel', 'Massawe', 'Kilimanjaro Rides', 'Executive fleet & bridal cars', 'transportation', 'Moshi, Tanzania', 10, 'business_verified', false, 4.40, 17, 90, 3],
            ['sentinel', 'security@osep.test', 'Grace', 'Mollel', 'Sentinel Security', 'Discreet, licensed event security', 'security-services', 'Dar es Salaam, Tanzania', 11, 'premium_partner', false, 4.55, 26, 210, 1],
            ['grandtents', 'rentals@osep.test', 'Joseph', 'Kileo', 'Grand Tents & Furniture', 'Marquees, seating & staging', 'tent-furniture-rental', 'Dar es Salaam, Tanzania', 14, 'business_verified', true, 4.35, 41, 175, 4],
            ['framestory', 'video@osep.test', 'Naomi', 'Wanjiru', 'Frame Story Films', 'Cinematic event films', 'videographers', 'Nairobi, Kenya', 6, 'business_verified', false, 4.80, 24, 30, 3],
            ['inkwell', 'print@osep.test', 'Peter', 'Sanga', 'Inkwell Press', 'Invitations, signage & print', 'printing-services', 'Arusha, Tanzania', 9, 'email_verified', false, 4.45, 15, 140, 5],
        ];

        $vendors = [];
        foreach ($rows as $r) {
            [$key, $email, $first, $last, $business, $tagline, $slug, $location, $years, $level, $featured, $rating, $reviews, $jobs, $response] = $r;

            $user = User::updateOrCreate(
                ['email' => $email],
                [
                    'first_name' => $first, 'last_name' => $last,
                    'phone' => '+2557'.random_int(10_000_000, 99_999_999),
                    'password' => Hash::make(self::PASSWORD),
                    'account_type' => AccountType::Vendor, 'country' => 'Tanzania',
                    'status' => UserStatus::Active, 'email_verified_at' => now(),
                ],
            );
            $user->assignRole('vendor');

            $category = $this->categories[$slug] ?? null;

            $user->vendorProfile()->updateOrCreate([], [
                'business_name' => $business,
                'tagline' => $tagline,
                'category' => $category?->name ?? $business,
                'category_id' => $category?->id,
                'description' => "{$business} - {$tagline}. Trusted by planners across the region for reliable, high-quality delivery.",
                'years_in_business' => $years,
                'location' => $location,
                'phone' => $user->phone,
                'contact_email' => $email,
                'website' => 'https://'.Str::slug($business).'.example',
                'social_links' => ['instagram' => 'https://instagram.com/'.Str::slug($business)],
                'logo_url' => "https://picsum.photos/seed/{$key}-logo/200/200",
                'cover_image_url' => "https://picsum.photos/seed/{$key}-cover/1200/400",
                'verification_status' => $level === 'unverified' ? 'pending' : 'verified',
                'verification_level' => $level,
                'availability_status' => 'available',
                'profile_views' => random_int(200, 2000),
                'pending_requests' => random_int(0, 6),
                'response_time_hours' => $response,
                'completed_jobs' => $jobs,
                'reviews_count' => $reviews,
                'rating' => $rating,
                'is_featured' => $featured,
                'is_suspended' => false,
            ]);

            $this->storefront($user, $key, $business);
            $vendors[$key] = $user;
        }

        return $vendors;
    }

    private function storefront(User $vendor, string $key, string $business): void
    {
        // Services
        foreach (['Standard package', 'Premium package', 'Consultation'] as $i => $name) {
            $vendor->vendorServices()->updateOrCreate(
                ['name' => $name],
                [
                    'category_id' => $vendor->vendorProfile->category_id,
                    'description' => "{$name} from {$business}.",
                    'is_active' => true, 'sort_order' => $i,
                ],
            );
        }

        // Packages
        $packages = [
            ['Essential', random_int(800, 2500) * 1000, ['Half-day coverage', 'One lead specialist', 'Standard deliverables']],
            ['Signature', random_int(3000, 6000) * 1000, ['Full-day coverage', 'Two specialists', 'Premium deliverables', 'Priority support']],
            ['Luxury', random_int(7000, 12000) * 1000, ['Multi-day coverage', 'Full team', 'Bespoke deliverables', 'Dedicated coordinator']],
        ];
        foreach ($packages as $i => [$name, $price, $inclusions]) {
            $vendor->vendorPackages()->updateOrCreate(
                ['name' => $name],
                [
                    'description' => "The {$name} package from {$business}.",
                    'price' => $price, 'currency' => 'TZS', 'price_unit' => 'per event',
                    'inclusions' => $inclusions,
                    'addons' => [['name' => 'Extra hour', 'price' => 150_000], ['name' => 'Travel (outside city)', 'price' => 300_000]],
                    'terms' => '50% deposit to confirm. Balance due 7 days before the event.',
                    'is_active' => true, 'sort_order' => $i,
                ],
            );
        }

        // Portfolio
        foreach (['Garden Wedding', 'Corporate Gala', 'Milestone Birthday'] as $i => $title) {
            $vendor->vendorPortfolios()->updateOrCreate(
                ['title' => $title],
                [
                    'description' => "A {$title} delivered by {$business}.",
                    'event_type' => $title,
                    'event_date' => now()->subMonths(($i + 1) * 3)->toDateString(),
                    'cover_url' => "https://picsum.photos/seed/{$key}-p{$i}/800/600",
                    'media' => [
                        ['type' => 'image', 'url' => "https://picsum.photos/seed/{$key}-p{$i}a/800/600", 'caption' => 'Setup'],
                        ['type' => 'image', 'url' => "https://picsum.photos/seed/{$key}-p{$i}b/800/600", 'caption' => 'Highlights'],
                    ],
                    'client_feedback' => 'Absolutely wonderful to work with - highly recommended.',
                    'is_case_study' => $i === 1,
                    'sort_order' => $i,
                ],
            );
        }

        // Availability - next 21 days, mostly available with a few blocked.
        foreach (range(0, 20) as $d) {
            $date = now()->addDays($d)->toDateString();
            $status = match (true) {
                $d % 7 === 3 => 'fully_booked',
                $d % 11 === 5 => 'on_leave',
                $d % 5 === 2 => 'reserved',
                default => 'available',
            };
            $vendor->vendorAvailability()->updateOrCreate(['date' => $date], ['status' => $status]);
        }
    }

    /**
     * @return array<string, MarketplaceVenue>
     */
    private function venues(): array
    {
        $owner1 = $this->venueOwner('venues1@osep.test', 'Coastal', 'Venues', 'Coastal Venues Group');
        $owner2 = $this->venueOwner('venues2@osep.test', 'Summit', 'Halls', 'Summit Hospitality');

        $data = [
            ['waterfront', $owner1, 'The Waterfront Pavilion', 'Banquet Hall', 'both', 250, 60, 'Toure Drive, Oyster Bay, Dar es Salaam', 'Dar es Salaam, Tanzania', 8_000_000, true, 'premium_partner', 4.8, 64],
            ['gardens', $owner1, 'Oyster Bay Gardens', 'Garden', 'outdoor', 400, 100, 'Oyster Bay, Dar es Salaam', 'Dar es Salaam, Tanzania', 6_500_000, true, 'business_verified', 4.6, 41],
            ['mlimani', $owner2, 'Mlimani Conference Hall', 'Conference Centre', 'indoor', 600, 150, 'Mlimani City, Dar es Salaam', 'Dar es Salaam, Tanzania', 12_000_000, false, 'business_verified', 4.5, 52],
            ['serengeti', $owner2, 'Serengeti Ballroom', 'Ballroom', 'indoor', 350, 80, 'Serena Hotel, Nairobi', 'Nairobi, Kenya', 15_000_000, true, 'premium_partner', 4.9, 77],
            ['skyline', $owner2, 'Skyline Rooftop', 'Rooftop', 'outdoor', 150, 40, 'City Centre, Arusha', 'Arusha, Tanzania', 4_500_000, false, 'email_verified', 4.3, 18],
        ];

        $venues = [];
        foreach ($data as [$key, $owner, $name, $type, $setting, $capacity, $minCap, $address, $location, $price, $featured, $level, $rating, $reviews]) {
            $venue = MarketplaceVenue::updateOrCreate(
                ['owner_id' => $owner->id, 'name' => $name],
                [
                    'venue_type' => $type,
                    'description' => "{$name} is a {$type} in {$location}, ideal for weddings, conferences and galas.",
                    'setting' => $setting,
                    'capacity' => $capacity, 'min_capacity' => $minCap,
                    'dimensions' => random_int(20, 50).'m x '.random_int(15, 40).'m',
                    'layout_options' => ['Banquet', 'Theatre', 'Cocktail', 'U-Shape'],
                    'setup_time' => '3 hours', 'breakdown_time' => '2 hours',
                    'included_equipment' => ['Tables & chairs', 'PA system', 'Lighting', 'Stage'],
                    'facilities' => ['Restrooms', 'Kitchen', 'Bridal suite', 'Generator backup'],
                    'accessibility' => ['Wheelchair ramp', 'Accessible restrooms'],
                    'restrictions' => 'Music curfew at midnight. No open flames.',
                    'parking_available' => true, 'parking_capacity' => random_int(50, 200),
                    'price' => $price, 'currency' => 'TZS', 'price_unit' => 'per day',
                    'address' => $address, 'location' => $location,
                    'contact_person' => $owner->full_name,
                    'contact_phone' => $owner->phone,
                    'contact_email' => $owner->email,
                    'booking_terms' => '50% deposit to reserve. Fully refundable up to 30 days before.',
                    'cover_image_url' => "https://picsum.photos/seed/{$key}-venue/1200/500",
                    'verification_level' => $level,
                    'is_featured' => $featured, 'is_suspended' => false, 'is_published' => true,
                    'rating' => $rating, 'reviews_count' => $reviews,
                    'profile_views' => random_int(300, 3000),
                ],
            );

            // Gallery
            $venue->images()->delete();
            foreach (range(0, 3) as $i) {
                $venue->images()->create([
                    'url' => "https://picsum.photos/seed/{$key}-img{$i}/900/600",
                    'caption' => ['Main hall', 'Garden view', 'Stage setup', 'Evening ambience'][$i],
                    'sort_order' => $i,
                ]);
            }

            // Availability
            foreach (range(0, 20) as $d) {
                $venue->availability()->updateOrCreate(
                    ['date' => now()->addDays($d)->toDateString()],
                    ['status' => $d % 6 === 4 ? 'reserved' : ($d % 9 === 7 ? 'fully_booked' : 'available')],
                );
            }

            $venues[$key] = $venue;
        }

        return $venues;
    }

    private function venueOwner(string $email, string $first, string $last, string $business): User
    {
        $owner = User::updateOrCreate(
            ['email' => $email],
            [
                'first_name' => $first, 'last_name' => $last,
                'phone' => '+2557'.random_int(10_000_000, 99_999_999),
                'password' => Hash::make(self::PASSWORD),
                'account_type' => AccountType::Vendor, 'country' => 'Tanzania',
                'status' => UserStatus::Active, 'email_verified_at' => now(),
            ],
        );
        $owner->assignRole('vendor');
        $owner->vendorProfile()->updateOrCreate([], [
            'business_name' => $business,
            'category' => 'Venues',
            'location' => 'Tanzania',
            'verification_status' => 'verified',
            'verification_level' => 'business_verified',
            'availability_status' => 'available',
        ]);

        return $owner;
    }

    /**
     * @param  array<string, User>  $vendors
     * @param  array<string, MarketplaceVenue>  $venues
     */
    private function transactions(User $planner, array $vendors, array $venues): void
    {
        $event = $planner->plannedEvents()->first();
        $eventId = $event?->id;

        // A booking request that ran the full course: accepted → quoted → contracted.
        $accepted = BookingRequest::updateOrCreate(
            ['planner_id' => $planner->id, 'vendor_id' => $vendors['zawadi']->id, 'title' => 'Wedding photography coverage'],
            [
                'event_id' => $eventId, 'event_date' => now()->addDays(20)->toDateString(),
                'guest_count' => 180, 'budget' => 6_000_000,
                'requirements' => 'Full-day coverage, two photographers, engagement shoot, edited gallery + highlight film.',
                'status' => 'accepted', 'response_note' => 'Delighted to be considered - we are available.',
                'responded_at' => now()->subDays(2),
            ],
        );

        $quotation = Quotation::updateOrCreate(
            ['booking_request_id' => $accepted->id, 'vendor_id' => $vendors['zawadi']->id],
            [
                'planner_id' => $planner->id, 'event_id' => $eventId,
                'reference' => 'QUO-'.now()->format('Y').'-ZAWADI', 'currency' => 'TZS',
                'timeline' => 'Delivery within 4 weeks of the event',
                'terms' => '50% deposit to confirm. Balance due 7 days before.',
                'notes' => 'Includes a complimentary engagement session.',
                'status' => 'accepted', 'sent_at' => now()->subDays(1), 'decided_at' => now(),
                'expires_at' => now()->addDays(14)->toDateString(),
            ],
        );
        $quotation->items()->delete();
        foreach ([
            ['Full-day coverage (2 photographers)', 1, 4_000_000],
            ['Highlight film', 1, 1_500_000],
            ['Engagement session', 1, 500_000],
        ] as $i => [$desc, $qty, $unit]) {
            $quotation->items()->create(['description' => $desc, 'quantity' => $qty, 'unit_price' => $unit, 'amount' => $qty * $unit, 'sort_order' => $i]);
        }
        $quotation->recalculateTotals();

        Contract::updateOrCreate(
            ['quotation_id' => $quotation->id],
            [
                'booking_request_id' => $accepted->id, 'planner_id' => $planner->id,
                'vendor_id' => $vendors['zawadi']->id, 'event_id' => $eventId,
                'reference' => 'CON-'.now()->format('Y').'-ZAWADI', 'title' => 'Wedding Photography Agreement',
                'status' => 'signed', 'amount' => $quotation->total, 'currency' => 'TZS',
                'terms' => $quotation->terms, 'signed_at' => now(),
                'start_date' => now()->addDays(20)->toDateString(),
            ],
        );

        // A pending request awaiting a response.
        BookingRequest::updateOrCreate(
            ['planner_id' => $planner->id, 'vendor_id' => $vendors['neema']->id, 'title' => 'Plated dinner for 180'],
            [
                'event_id' => $eventId, 'event_date' => now()->addDays(20)->toDateString(),
                'guest_count' => 180, 'budget' => 9_500_000,
                'requirements' => 'Three-course plated dinner, vegetarian and vegan options.',
                'status' => 'pending',
            ],
        );

        // A venue request that came back needing more info.
        BookingRequest::updateOrCreate(
            ['planner_id' => $planner->id, 'venue_id' => $venues['waterfront']->id, 'title' => 'Waterfront Pavilion hire'],
            [
                'event_id' => $eventId, 'event_date' => now()->addDays(20)->toDateString(),
                'guest_count' => 180, 'budget' => 8_000_000,
                'requirements' => 'Ceremony + reception, exclusive use from 10:00.',
                'status' => 'info_requested', 'response_note' => 'Could you confirm the final guest count and catering vendor?',
                'responded_at' => now()->subDay(),
            ],
        );

        // A sent quotation from the caterer still awaiting the planner's decision.
        $catererRequest = BookingRequest::firstWhere(['planner_id' => $planner->id, 'vendor_id' => $vendors['neema']->id]);
        $sent = Quotation::updateOrCreate(
            ['booking_request_id' => $catererRequest?->id, 'vendor_id' => $vendors['neema']->id],
            [
                'planner_id' => $planner->id, 'event_id' => $eventId,
                'reference' => 'QUO-'.now()->format('Y').'-NEEMA', 'currency' => 'TZS',
                'timeline' => 'Menu tasting 2 weeks before', 'terms' => '50% deposit to confirm.',
                'status' => 'sent', 'sent_at' => now(), 'expires_at' => now()->addDays(10)->toDateString(),
            ],
        );
        $sent->items()->delete();
        foreach ([['Plated dinner (180 pax)', 180, 45_000], ['Service staff', 12, 80_000]] as $i => [$desc, $qty, $unit]) {
            $sent->items()->create(['description' => $desc, 'quantity' => $qty, 'unit_price' => $unit, 'amount' => $qty * $unit, 'sort_order' => $i]);
        }
        $sent->recalculateTotals();
    }

    /**
     * @param  array<string, User>  $vendors
     * @param  array<string, MarketplaceVenue>  $venues
     */
    private function reviews(User $planner, array $vendors, array $venues): void
    {
        $review = Review::updateOrCreate(
            ['reviewer_id' => $planner->id, 'vendor_id' => $vendors['zawadi']->id],
            [
                'rating_professionalism' => 5, 'rating_communication' => 5, 'rating_quality' => 5,
                'rating_value' => 4, 'rating_timeliness' => 5, 'overall_rating' => 4.80,
                'title' => 'Exceptional from start to finish',
                'comment' => 'Zawadi captured every moment beautifully and delivered ahead of schedule. Could not recommend more highly.',
                'status' => 'published',
            ],
        );
        $review->replies()->updateOrCreate(
            ['user_id' => $vendors['zawadi']->id],
            ['body' => 'Thank you so much - it was a joy to be part of your day!'],
        );

        Review::updateOrCreate(
            ['reviewer_id' => $planner->id, 'venue_id' => $venues['serengeti']->id],
            [
                'rating_professionalism' => 5, 'rating_communication' => 4, 'rating_quality' => 5,
                'rating_value' => 4, 'rating_timeliness' => 5, 'overall_rating' => 4.60,
                'title' => 'Stunning ballroom', 'comment' => 'The Serengeti Ballroom was the perfect backdrop. Staff were attentive throughout.',
                'status' => 'published',
            ],
        );
    }

    /**
     * @param  array<string, User>  $vendors
     * @param  array<string, MarketplaceVenue>  $venues
     */
    private function savedLists(User $planner, array $vendors, array $venues): void
    {
        $wedding = SavedCollection::updateOrCreate(
            ['planner_id' => $planner->id, 'name' => 'Wedding Vendors'],
            ['description' => 'Shortlist for the Bennett wedding', 'is_default' => true],
        );
        foreach (['zawadi', 'neema', 'blooms', 'glow'] as $key) {
            $wedding->items()->updateOrCreate(['vendor_id' => $vendors[$key]->id, 'venue_id' => null], []);
        }
        $wedding->items()->updateOrCreate(['venue_id' => $venues['waterfront']->id, 'vendor_id' => null], []);

        $luxury = SavedCollection::updateOrCreate(
            ['planner_id' => $planner->id, 'name' => 'Luxury Suppliers'],
            ['description' => 'Premium partners for high-end events'],
        );
        foreach (['sentinel', 'framestory'] as $key) {
            $luxury->items()->updateOrCreate(['vendor_id' => $vendors[$key]->id, 'venue_id' => null], []);
        }
        $luxury->items()->updateOrCreate(['venue_id' => $venues['serengeti']->id, 'vendor_id' => null], []);
    }

    private function messages(User $planner, User $vendor): void
    {
        $thread = MessageThread::updateOrCreate(
            ['planner_id' => $planner->id, 'vendor_id' => $vendor->id, 'venue_id' => null],
            ['subject' => 'Wedding photography', 'last_message_at' => now()],
        );

        $lines = [
            [$planner->id, 'Hi Zawadi! Loved your portfolio. Are you free on the 15th?', now()->subDays(3)],
            [$vendor->id, 'Hi Sarah - yes, the 15th is open. Happy to put together a quote.', now()->subDays(3)->addHours(1)],
            [$planner->id, 'Wonderful. Please include a highlight film in the package.', now()->subDays(2)],
            [$vendor->id, 'Done - I have just sent the quotation across for your review.', now()->subDays(1)],
        ];
        foreach ($lines as [$senderId, $body, $at]) {
            $thread->messages()->updateOrCreate(
                ['sender_id' => $senderId, 'body' => $body],
                ['read_at' => $at->copy()->addHours(2), 'created_at' => $at, 'updated_at' => $at],
            );
        }
        $thread->update(['last_message_at' => now()->subDays(1)]);
    }

    /**
     * Hotels for the honeymoon-booking vertical, each with a few room types
     * (always including a honeymoon-worthy suite). Idempotent on the hotel slug.
     */
    private function accommodations(): void
    {
        $hotels = [
            [
                'name' => 'Zanzibar Pearl Beach Resort & Spa',
                'star_rating' => 5,
                'city' => 'Zanzibar',
                'location' => 'Nungwi, Zanzibar',
                'description' => 'A five-star beachfront sanctuary on the white sands of Nungwi - the classic Tanzanian honeymoon escape, with private plunge pools, a spa and sunset dhow cruises.',
                'amenities' => ['Private beach', 'Infinity pool', 'Spa & wellness', 'Free Wi-Fi', 'Airport transfer', 'Breakfast included', 'Sunset cruises'],
                'cover_image_url' => 'https://images.unsplash.com/photo-1520250497591-112f2f40a3f4?w=1200&q=80',
                'check_in_time' => '14:00', 'check_out_time' => '11:00',
                'contact_email' => 'reservations@zanzibarpearl.example', 'contact_phone' => '+255 24 555 0100',
                'policies' => 'Free cancellation up to 14 days before check-in. Honeymoon package includes a complimentary candlelit dinner.',
                'rooms' => [
                    ['name' => 'Honeymoon Ocean Suite', 'price' => 850000, 'capacity' => 2, 'beds' => '1 King', 'size' => 65, 'total' => 4,
                        'amenities' => ['Private plunge pool', 'Ocean view', 'Outdoor rain shower', 'Butler service'],
                        'desc' => 'A romantic suite steps from the sea, with a private plunge pool and daily rose-petal turndown.',
                        'image' => 'https://images.unsplash.com/photo-1590490360182-c33d57733427?w=900&q=80'],
                    ['name' => 'Deluxe Garden Room', 'price' => 420000, 'capacity' => 2, 'beds' => '1 Queen', 'size' => 38, 'total' => 12,
                        'amenities' => ['Garden view', 'Balcony', 'Minibar'], 'desc' => 'A serene room overlooking the tropical gardens.',
                        'image' => 'https://images.unsplash.com/photo-1611892440504-42a792e24d32?w=900&q=80'],
                    ['name' => 'Beachfront Villa', 'price' => 1450000, 'capacity' => 4, 'beds' => '2 King', 'size' => 120, 'total' => 2,
                        'amenities' => ['Private beach access', 'Two bedrooms', 'Full kitchen', 'Private pool'], 'desc' => 'A spacious villa on the sand for a small wedding party or family.',
                        'image' => 'https://images.unsplash.com/photo-1582719508461-905c673771fd?w=900&q=80'],
                ],
            ],
            [
                'name' => 'Serengeti Sky Luxury Lodge',
                'star_rating' => 5,
                'city' => 'Serengeti',
                'location' => 'Central Serengeti, Tanzania',
                'description' => 'A luxury tented lodge overlooking the endless plains - game drives at dawn, champagne at dusk. An unforgettable safari honeymoon.',
                'amenities' => ['Game drives', 'All-inclusive dining', 'Infinity pool', 'Spa', 'Wi-Fi in lounge', 'Bush dinners'],
                'cover_image_url' => 'https://images.unsplash.com/photo-1516426122078-c23e76319801?w=1200&q=80',
                'check_in_time' => '13:00', 'check_out_time' => '10:00',
                'contact_email' => 'stay@serengetisky.example', 'contact_phone' => '+255 28 555 0180',
                'policies' => 'Rates are full-board and include two game drives daily. Cancellation up to 30 days before arrival.',
                'rooms' => [
                    ['name' => 'Honeymoon Tented Suite', 'price' => 1200000, 'capacity' => 2, 'beds' => '1 King', 'size' => 55, 'total' => 3,
                        'amenities' => ['Private deck', 'Plains view', 'Outdoor bath', 'Personal butler'], 'desc' => 'A canvas suite with an open-air bath and uninterrupted views of the migration.',
                        'image' => 'https://images.unsplash.com/photo-1504280390367-361c6d9f38f4?w=900&q=80'],
                    ['name' => 'Classic Safari Tent', 'price' => 780000, 'capacity' => 2, 'beds' => '1 Queen', 'size' => 40, 'total' => 8,
                        'amenities' => ['En-suite bathroom', 'Private deck', 'Lantern lighting'], 'desc' => 'A comfortable tent with all the essentials for a bush adventure.',
                        'image' => 'https://images.unsplash.com/photo-1445019980597-93fa8acb246c?w=900&q=80'],
                ],
            ],
            [
                'name' => 'Dar Riverside City Hotel',
                'star_rating' => 4,
                'city' => 'Dar es Salaam',
                'location' => 'Masaki, Dar es Salaam',
                'description' => 'A stylish four-star city hotel by the harbour - ideal for a pre-honeymoon night or out-of-town wedding guests.',
                'amenities' => ['Rooftop pool', 'Gym', 'Free Wi-Fi', 'Restaurant', 'Airport shuttle', 'Business centre'],
                'cover_image_url' => 'https://images.unsplash.com/photo-1566073771259-6a8506099945?w=1200&q=80',
                'check_in_time' => '14:00', 'check_out_time' => '12:00',
                'contact_email' => 'book@darriverside.example', 'contact_phone' => '+255 22 555 0140',
                'policies' => 'Free cancellation up to 48 hours before arrival.',
                'rooms' => [
                    ['name' => 'Executive Suite', 'price' => 360000, 'capacity' => 2, 'beds' => '1 King', 'size' => 45, 'total' => 6,
                        'amenities' => ['Harbour view', 'Lounge access', 'Nespresso'], 'desc' => 'A refined suite with skyline views and executive-lounge access.',
                        'image' => 'https://images.unsplash.com/photo-1631049307264-da0ec9d70304?w=900&q=80'],
                    ['name' => 'Standard Double', 'price' => 180000, 'capacity' => 2, 'beds' => '1 Double', 'size' => 28, 'total' => 20,
                        'amenities' => ['City view', 'Work desk'], 'desc' => 'A smart, comfortable room for guests in town for the celebration.',
                        'image' => 'https://images.unsplash.com/photo-1595576508898-0ad5c879a061?w=900&q=80'],
                ],
            ],
        ];

        foreach ($hotels as $data) {
            $rooms = $data['rooms'];
            unset($data['rooms']);

            $hotel = Accommodation::updateOrCreate(
                ['slug' => Str::slug($data['name'])],
                array_merge($data, [
                    'currency' => 'TZS',
                    'price_from' => collect($rooms)->min('price'),
                    'is_published' => true,
                    'is_featured' => $data['star_rating'] === 5,
                ]),
            );

            foreach ($rooms as $room) {
                $hotel->roomTypes()->updateOrCreate(
                    ['name' => $room['name']],
                    [
                        'description' => $room['desc'],
                        'price_per_night' => $room['price'],
                        'currency' => 'TZS',
                        'capacity' => $room['capacity'],
                        'bed_configuration' => $room['beds'],
                        'size_sqm' => $room['size'],
                        'amenities' => $room['amenities'],
                        'image_url' => $room['image'],
                        'total_rooms' => $room['total'],
                        'is_active' => true,
                    ],
                );
            }
        }
    }
}
