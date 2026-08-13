<?php

namespace Database\Seeders;

use App\Enums\AccountType;
use App\Enums\UserStatus;
use App\Models\User;
use App\Models\VendorCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Nine real Tanzanian catering companies as demo vendor accounts, so the
 * marketplace's Caterers category shows recognisable, believable listings.
 *
 * Real, public details are used — company name, city, description, website /
 * social — but login email and phone are demo values (@osep.test / a placeholder
 * number), NOT the businesses' real contact lines, since these are illustrative
 * demo accounts, not genuine sign-ups. Ratings/job counts are demo metrics.
 * Idempotent (keyed on email) and guarded to non-production in DatabaseSeeder.
 */
class CateringVendorsSeeder extends Seeder
{
    private const PASSWORD = 'Password123!';

    public function run(): void
    {
        $category = VendorCategory::where('slug', 'caterers')->first();

        foreach ($this->rows() as $r) {
            $user = User::updateOrCreate(
                ['email' => $r['email']],
                [
                    'first_name' => $r['first'],
                    'last_name' => $r['last'],
                    'phone' => '+2557' . random_int(10_000_000, 99_999_999),
                    'password' => Hash::make(self::PASSWORD),
                    'account_type' => AccountType::Vendor,
                    'country' => 'Tanzania',
                    'status' => UserStatus::Active,
                    'email_verified_at' => now(),
                ],
            );
            $user->assignRole('vendor');

            $user->vendorProfile()->updateOrCreate([], [
                'business_name' => $r['business'],
                'tagline' => $r['tagline'],
                'category' => $category?->name ?? 'Caterers',
                'category_id' => $category?->id,
                'description' => $r['description'],
                'years_in_business' => $r['years'],
                'location' => $r['location'],
                'phone' => $user->phone,
                'contact_email' => $r['email'],
                'website' => $r['website'],
                'social_links' => array_filter(['instagram' => $r['instagram'] ?? null, 'facebook' => $r['facebook'] ?? null]),
                'logo_url' => $this->cateringLogo($r['key']),
                'cover_image_url' => $r['cover'],
                'verification_status' => 'verified',
                'verification_level' => $r['level'],
                'availability_status' => 'available',
                'profile_views' => random_int(300, 2500),
                'pending_requests' => random_int(0, 6),
                'response_time_hours' => $r['response'],
                'completed_jobs' => $r['jobs'],
                'reviews_count' => $r['reviews'],
                'rating' => $r['rating'],
                'is_featured' => $r['featured'],
                'is_suspended' => false,
            ]);

            $this->storefront($user, $r['key'], $r['business']);
        }
    }

    private function storefront(User $vendor, string $key, string $business): void
    {
        $categoryId = $vendor->vendorProfile->category_id;

        // Catering services
        $services = ['Wedding catering', 'Corporate & conference catering', 'Cocktail receptions', 'Menu tasting'];
        foreach ($services as $i => $name) {
            $vendor->vendorServices()->updateOrCreate(
                ['name' => $name],
                ['category_id' => $categoryId, 'description' => "{$name} by {$business}.", 'is_active' => true, 'sort_order' => $i],
            );
        }

        // Per-guest menu packages
        $packages = [
            ['Classic Buffet', random_int(32, 45) * 1000, 'per guest', ['3 mains + 2 sides', 'Salad bar & dessert', 'Service staff & chafing dishes', 'Soft drinks']],
            ['Premium Plated Dinner', random_int(60, 85) * 1000, 'per guest', ['4-course plated menu', 'Full waited service', 'Table linen & crockery', 'Welcome mocktail']],
            ['Cocktail & Canapés', random_int(38, 52) * 1000, 'per guest', ['Assorted hot & cold canapés', 'Roaming service', 'Mocktail bar', '2-hour reception']],
        ];
        foreach ($packages as $i => [$name, $price, $unit, $inclusions]) {
            $vendor->vendorPackages()->updateOrCreate(
                ['name' => $name],
                [
                    'description' => "{$name} from {$business}.",
                    'price' => $price, 'currency' => 'TZS', 'price_unit' => $unit,
                    'inclusions' => $inclusions,
                    'addons' => [['name' => 'Live cooking station', 'price' => 900_000], ['name' => 'Extra hour of service', 'price' => 250_000]],
                    'terms' => '50% deposit to confirm. Final headcount due 5 days before the event.',
                    'is_active' => true, 'sort_order' => $i,
                ],
            );
        }

        // Portfolio — catering/food imagery
        $food = [
            '1555244162-803834f70033', '1519671482749-fd09be7ccebf', '1467003909585-2f8a72700288',
            '1414235077428-338989a2e8c0', '1530062845289-9109b2c9c868', '1478145046317-39f10e56b5e9',
        ];
        $img = fn (int $n) => 'https://images.unsplash.com/photo-' . $food[$n % count($food)] . '?w=800&h=600&fit=crop&q=80';
        foreach (['Garden Wedding Buffet', 'Corporate Gala Dinner', 'Grand Reception (500 guests)'] as $i => $title) {
            $vendor->vendorPortfolios()->updateOrCreate(
                ['title' => $title],
                [
                    'description' => "{$title} catered by {$business}.",
                    'event_type' => $title,
                    'event_date' => now()->subMonths(($i + 1) * 2)->toDateString(),
                    'cover_url' => $img($i * 2),
                    'media' => [
                        ['type' => 'image', 'url' => $img($i * 2), 'caption' => 'Buffet setup'],
                        ['type' => 'image', 'url' => $img($i * 2 + 1), 'caption' => 'Plated course'],
                    ],
                    'client_feedback' => 'The food was the highlight of the day — guests are still talking about it.',
                    'is_case_study' => $i === 1,
                    'sort_order' => $i,
                ],
            );
        }

        // Availability — next 21 days
        foreach (range(0, 20) as $d) {
            $status = match (true) {
                $d % 7 === 4 => 'fully_booked',
                $d % 11 === 6 => 'on_leave',
                $d % 5 === 3 => 'reserved',
                default => 'available',
            };
            $vendor->vendorAvailability()->updateOrCreate(['date' => now()->addDays($d)->toDateString()], ['status' => $status]);
        }
    }

    /** A distinct catering/food profile picture (square) per vendor. */
    private function cateringLogo(string $key): string
    {
        $photos = [
            'drrec' => '1546069901-ba9599a7e63c',      // colourful salad bowl
            'niccian' => '1498837167922-ddd27525d352',  // laid table spread
            'happening' => '1476224203421-9ac39bcb3327', // seafood platter
            'foodex' => '1467003909585-2f8a72700288',    // chef plating
            'ako' => '1555939594-58d7cb561ad1',          // grilled dish
            'lavicato' => '1519671482749-fd09be7ccebf',  // buffet / party food
            'bibi' => '1504674900247-0877df9cc836',      // home-style cooking
            'dorka' => '1478145046317-39f10e56b5e9',     // plated fine dining
            'ltevents' => '1530062845289-9109b2c9c868',  // canapés
        ];
        $id = $photos[$key] ?? '1414235077428-338989a2e8c0';

        return "https://images.unsplash.com/photo-{$id}?w=400&h=400&fit=crop&q=80";
    }

    /**
     * Nine real Tanzanian catering companies (public details).
     *
     * @return array<int, array<string, mixed>>
     */
    private function rows(): array
    {
        return [
            [
                'key' => 'drrec', 'email' => 'drrec@osep.test', 'first' => 'Daniel', 'last' => 'Rweyemamu',
                'business' => 'DRREC Limited', 'tagline' => 'Catering & full event planning',
                'location' => 'Dar es Salaam, Tanzania', 'years' => 12, 'level' => 'business_verified',
                'featured' => true, 'rating' => 4.7, 'reviews' => 48, 'jobs' => 130, 'response' => 2,
                'website' => 'https://drrec.co.tz',
                'description' => 'Full-service catering and event planning in Dar es Salaam — corporate functions, private parties, kids’ events and weddings — handling food, equipment, staffing, theming and venue hire end to end.',
                'cover' => 'https://images.unsplash.com/photo-1555244162-803834f70033?w=1200&q=80',
            ],
            [
                'key' => 'niccian', 'email' => 'niccian@osep.test', 'first' => 'Nicodemus', 'last' => 'Mbwana',
                'business' => 'Niccian Catering Group', 'tagline' => 'Every celebration, beautifully catered',
                'location' => 'Mikocheni, Dar es Salaam', 'years' => 8, 'level' => 'business_verified',
                'featured' => false, 'rating' => 4.6, 'reviews' => 31, 'jobs' => 88, 'response' => 3,
                'website' => 'https://www.nicciancatering.com', 'instagram' => 'https://instagram.com/nicciancatering',
                'description' => 'Mikocheni-based caterers for weddings, birthdays, graduations, religious gatherings, corporate parties and family celebrations across Dar es Salaam.',
                'cover' => 'https://images.unsplash.com/photo-1519671482749-fd09be7ccebf?w=1200&q=80',
            ],
            [
                'key' => 'happening', 'email' => 'happeningplace@osep.test', 'first' => 'Rajesh', 'last' => 'Patel',
                'business' => 'The Happening Place', 'tagline' => 'Indian · Chinese · Tanzanian outside catering',
                'location' => 'Dar es Salaam, Tanzania', 'years' => 10, 'level' => 'business_verified',
                'featured' => true, 'rating' => 4.65, 'reviews' => 37, 'jobs' => 96, 'response' => 2,
                'website' => 'https://www.happeningplace.co.tz',
                'description' => 'High-standard outside catering specialising in dainty, modern Indian, Chinese and Tanzanian cuisine for weddings, family gatherings and corporate events.',
                'cover' => 'https://images.unsplash.com/photo-1467003909585-2f8a72700288?w=1200&q=80',
            ],
            [
                'key' => 'foodex', 'email' => 'foodex@osep.test', 'first' => 'Ismail', 'last' => 'Hassan',
                'business' => 'Foodex', 'tagline' => 'Two decades of elegant event dining',
                'location' => 'Dar es Salaam, Tanzania', 'years' => 23, 'level' => 'premium_partner',
                'featured' => true, 'rating' => 4.8, 'reviews' => 72, 'jobs' => 240, 'response' => 1,
                'website' => 'https://foodex.co.tz',
                'description' => 'Over two decades of catering and hospitality experience, serving elegant local and international menus for weddings and corporate events across Tanzania.',
                'cover' => 'https://images.unsplash.com/photo-1414235077428-338989a2e8c0?w=1200&q=80',
            ],
            [
                'key' => 'ako', 'email' => 'ako@osep.test', 'first' => 'Alex', 'last' => 'Komba',
                'business' => 'AKO Catering Services', 'tagline' => 'Professional catering with a personal touch',
                'location' => 'Dar es Salaam, Tanzania', 'years' => 7, 'level' => 'email_verified',
                'featured' => false, 'rating' => 4.5, 'reviews' => 21, 'jobs' => 54, 'response' => 4,
                'website' => 'https://akocatering.example',
                'description' => 'Professional, responsive catering with a personal touch for weddings and corporate functions in Dar es Salaam.',
                'cover' => 'https://images.unsplash.com/photo-1530062845289-9109b2c9c868?w=1200&q=80',
            ],
            [
                'key' => 'lavicato', 'email' => 'lavicato@osep.test', 'first' => 'Lameck', 'last' => 'Victor',
                'business' => 'LAVICATO', 'tagline' => 'Mobile events, catering & rentals',
                'location' => 'Arusha, Tanzania', 'years' => 15, 'level' => 'premium_partner',
                'featured' => true, 'rating' => 4.75, 'reviews' => 58, 'jobs' => 170, 'response' => 2,
                'website' => 'https://lavicato.co.tz',
                'description' => '15+ years in the mobile events industry — catering, event management, party rentals, mobile bar and venue hire — from a trusted Arusha team.',
                'cover' => 'https://images.unsplash.com/photo-1478145046317-39f10e56b5e9?w=1200&q=80',
            ],
            [
                'key' => 'bibi', 'email' => 'bibicatering@osep.test', 'first' => 'Bibiana', 'last' => 'Massawe',
                'business' => 'Bibi Catering Services', 'tagline' => 'Indoor & outdoor catering, Arusha',
                'location' => 'Arusha, Tanzania', 'years' => 9, 'level' => 'business_verified',
                'featured' => false, 'rating' => 4.55, 'reviews' => 26, 'jobs' => 70, 'response' => 3,
                'website' => 'https://bibicatering.example', 'facebook' => 'https://www.facebook.com/bibicateringservices',
                'description' => 'Indoor and outdoor catering for weddings, send-offs and barbecues, serving a wide range of dishes across Arusha.',
                'cover' => 'https://images.unsplash.com/photo-1555244162-803834f70033?w=1200&q=80',
            ],
            [
                'key' => 'dorka', 'email' => 'dorkacatering@osep.test', 'first' => 'Dorka', 'last' => 'Mushi',
                'business' => 'Dorka Catering', 'tagline' => 'Beautifully plated, home-style cooking',
                'location' => 'Dar es Salaam, Tanzania', 'years' => 5, 'level' => 'business_verified',
                'featured' => false, 'rating' => 4.85, 'reviews' => 19, 'jobs' => 42, 'response' => 2,
                'website' => 'https://dorkacatering.example', 'instagram' => 'https://instagram.com/dorkacatering_tz',
                'description' => 'Boutique Dar es Salaam caterer known for beautifully plated menus and generous, home-style Tanzanian cooking.',
                'cover' => 'https://images.unsplash.com/photo-1504674900247-0877df9cc836?w=1200&q=80',
            ],
            [
                'key' => 'ltevents', 'email' => 'ltevents@osep.test', 'first' => 'Lucia', 'last' => 'Tarimo',
                'business' => 'LT Events & Weddings', 'tagline' => 'Destination weddings, Stone Town',
                'location' => 'Stone Town, Zanzibar', 'years' => 11, 'level' => 'business_verified',
                'featured' => true, 'rating' => 4.8, 'reviews' => 34, 'jobs' => 61, 'response' => 3,
                'website' => 'https://eventszanzibar.com', 'instagram' => 'https://instagram.com/lteventszanzibar',
                'description' => 'Stone Town event and wedding designers with a decade in luxury hospitality, delivering destination weddings with hand-picked catering and suppliers.',
                'cover' => 'https://images.unsplash.com/photo-1464366400600-7168b8af9bc3?w=1200&q=80',
            ],
        ];
    }
}
