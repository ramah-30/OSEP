<?php

namespace Database\Seeders;

use App\Enums\AccountType;
use App\Enums\UserStatus;
use App\Models\User;
use App\Models\VendorCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Nine real Tanzanian décor & florist businesses as demo vendor accounts, so the
 * marketplace's Decorators category shows recognisable listings.
 *
 * Real, public details are used - business name, city, description, website /
 * social - but login email and phone are demo values (@osep.test / a placeholder
 * number), NOT the businesses' real contact lines, since these are illustrative
 * demo accounts, not genuine sign-ups. Ratings/job counts are demo metrics.
 * Idempotent (keyed on email) and guarded to non-production in DatabaseSeeder.
 */
class DecorVendorsSeeder extends Seeder
{
    private const PASSWORD = 'Password123!';

    public function run(): void
    {
        $category = VendorCategory::where('slug', 'decorators')->first();

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
                'category' => $category?->name ?? 'Decorators',
                'category_id' => $category?->id,
                'description' => $r['description'],
                'years_in_business' => $r['years'],
                'location' => $r['location'],
                'phone' => $user->phone,
                'contact_email' => $r['email'],
                'website' => $r['website'],
                'social_links' => array_filter(['instagram' => $r['instagram'] ?? null, 'facebook' => $r['facebook'] ?? null]),
                'logo_url' => $this->img($r['logo'], 400, 400),
                'cover_image_url' => $this->img($r['cover'], 1200, 400),
                'verification_status' => 'verified',
                'verification_level' => $r['level'],
                'availability_status' => 'available',
                'profile_views' => random_int(300, 2600),
                'pending_requests' => random_int(0, 6),
                'response_time_hours' => $r['response'],
                'completed_jobs' => $r['jobs'],
                'reviews_count' => $r['reviews'],
                'rating' => $r['rating'],
                'is_featured' => $r['featured'],
                'is_suspended' => false,
            ]);

            $this->storefront($user, $r['business']);
        }
    }

    /** Build an Unsplash image URL from a photo id. */
    private function img(string $id, int $w, int $h): string
    {
        return "https://images.unsplash.com/photo-{$id}?w={$w}&h={$h}&fit=crop&q=80";
    }

    private function storefront(User $vendor, string $business): void
    {
        $categoryId = $vendor->vendorProfile->category_id;

        $services = ['Wedding décor & styling', 'Floral arrangements', 'Stage & backdrop design', 'Lighting & draping'];
        foreach ($services as $i => $name) {
            $vendor->vendorServices()->updateOrCreate(
                ['name' => $name],
                ['category_id' => $categoryId, 'description' => "{$name} by {$business}.", 'is_active' => true, 'sort_order' => $i],
            );
        }

        $packages = [
            ['Simple Setup', random_int(1500, 3500) * 1000, ['Head table & backdrop', 'Aisle & entrance florals', 'Chair dressing (up to 100)', 'Setup & teardown']],
            ['Grand Setup', random_int(4000, 9000) * 1000, ['Full stage & backdrop', 'Fresh floral centrepieces', 'Draping & uplighting', 'Chair dressing (up to 300)', 'Walkway & entrance décor']],
            ['Luxury Setup', random_int(10000, 20000) * 1000, ['Bespoke themed design', 'Premium fresh florals throughout', 'Custom stage build', 'Full draping, lighting & props', 'Lounge & photo areas', 'Dedicated stylist on the day']],
        ];
        foreach ($packages as $i => [$name, $price, $inclusions]) {
            $vendor->vendorPackages()->updateOrCreate(
                ['name' => $name],
                [
                    'description' => "{$name} from {$business}.",
                    'price' => $price, 'currency' => 'TZS', 'price_unit' => 'per event',
                    'inclusions' => $inclusions,
                    'addons' => [['name' => 'Fresh flower upgrade', 'price' => 700_000], ['name' => 'Extra draping bay', 'price' => 300_000], ['name' => 'Lounge furniture set', 'price' => 900_000]],
                    'terms' => '50% deposit to reserve the date. Setup access required the day before.',
                    'is_active' => true, 'sort_order' => $i,
                ],
            );
        }

        // Portfolio - décor / floral imagery
        $shots = ['1519225421980-715cb0215aed', '1523438885200-e635ba2c371e', '1508610048659-a06b669e3321', '1490750967868-88aa4486c946', '1465495976277-4387d4b0b4c6', '1478146059778-26028b07395a'];
        foreach (['Floral Wedding Stage', 'Garden Reception', 'Traditional Send-off'] as $i => $title) {
            $vendor->vendorPortfolios()->updateOrCreate(
                ['title' => $title],
                [
                    'description' => "{$title} styled by {$business}.",
                    'event_type' => $title,
                    'event_date' => now()->subMonths(($i + 1) * 2)->toDateString(),
                    'cover_url' => $this->img($shots[($i * 2) % count($shots)], 800, 600),
                    'media' => [
                        ['type' => 'image', 'url' => $this->img($shots[($i * 2) % count($shots)], 800, 600), 'caption' => 'Stage & backdrop'],
                        ['type' => 'image', 'url' => $this->img($shots[($i * 2 + 1) % count($shots)], 800, 600), 'caption' => 'Floral details'],
                    ],
                    'client_feedback' => 'The room was breathtaking - every flower and drape was exactly as we dreamed.',
                    'is_case_study' => $i === 1,
                    'sort_order' => $i,
                ],
            );
        }

        foreach (range(0, 20) as $d) {
            $status = match (true) {
                $d % 7 === 1 => 'fully_booked',
                $d % 11 === 9 => 'on_leave',
                $d % 5 === 0 => 'reserved',
                default => 'available',
            };
            $vendor->vendorAvailability()->updateOrCreate(['date' => now()->addDays($d)->toDateString()], ['status' => $status]);
        }
    }

    /**
     * Nine real Tanzanian décor & florist businesses (public details).
     *
     * @return array<int, array<string, mixed>>
     */
    private function rows(): array
    {
        return [
            [
                'key' => 'viva', 'email' => 'vivaflowers@osep.test', 'first' => 'Vivian', 'last' => 'Mushi',
                'business' => 'Viva Flowers & Decor', 'tagline' => 'Passionate florists for every occasion',
                'location' => 'Mikocheni, Dar es Salaam', 'years' => 8, 'level' => 'business_verified',
                'featured' => true, 'rating' => 4.7, 'reviews' => 44, 'jobs' => 120, 'response' => 2,
                'website' => 'https://vivaflowers.example', 'instagram' => 'https://instagram.com/vivaflowersdecor',
                'description' => 'A team of passionate Mikocheni florists offering wedding decoration and fresh floral arrangements for every occasion.',
                'logo' => '1465495976277-4387d4b0b4c6', 'cover' => '1478146059778-26028b07395a',
            ],
            [
                'key' => 'kevins', 'email' => 'kevinsevents@osep.test', 'first' => 'Kevin', 'last' => 'Mtei',
                'business' => 'Kevins Events', 'tagline' => 'A decade of grand wedding setups',
                'location' => 'Dar es Salaam, Tanzania', 'years' => 11, 'level' => 'premium_partner',
                'featured' => true, 'rating' => 4.8, 'reviews' => 58, 'jobs' => 190, 'response' => 2,
                'website' => 'https://kevinsevents.example', 'instagram' => 'https://instagram.com/kevinsevents',
                'description' => 'A decade of destination weddings from Dar es Salaam - from intimate ceremonies to grand, fully-dressed receptions.',
                'logo' => '1523438885200-e635ba2c371e', 'cover' => '1508610048659-a06b669e3321',
            ],
            [
                'key' => 'sinyati', 'email' => 'sinyatidecor@osep.test', 'first' => 'Sinyati', 'last' => 'Loibanguti',
                'business' => 'Sinyati Decor', 'tagline' => 'Magical, fairy-tale settings',
                'location' => 'Dar es Salaam, Tanzania', 'years' => 6, 'level' => 'business_verified',
                'featured' => true, 'rating' => 4.75, 'reviews' => 36, 'jobs' => 88, 'response' => 3,
                'website' => 'https://sinyatidecor.example', 'instagram' => 'https://instagram.com/sinyatidecor',
                'description' => 'Transforming spaces into magical, fairy-tale settings for weddings and celebrations across Dar es Salaam.',
                'logo' => '1508610048659-a06b669e3321', 'cover' => '1519225421980-715cb0215aed',
            ],
            [
                'key' => 'leyla', 'email' => 'leyladecor@osep.test', 'first' => 'Laylat', 'last' => 'Mgazzer',
                'business' => 'Leyla Events and Decor', 'tagline' => 'Signature elegant styling',
                'location' => 'Dar es Salaam, Tanzania', 'years' => 7, 'level' => 'business_verified',
                'featured' => false, 'rating' => 4.7, 'reviews' => 33, 'jobs' => 79, 'response' => 3,
                'website' => 'https://leyladecor.example', 'instagram' => 'https://instagram.com/leylaeventsdecor',
                'description' => 'Dar es Salaam décor house, founded by Laylat Mgazzer, styling simple and grand weddings with a signature elegant touch.',
                'logo' => '1490750967868-88aa4486c946', 'cover' => '1465495976277-4387d4b0b4c6',
            ],
            [
                'key' => 'creativesets', 'email' => 'creativesets@osep.test', 'first' => 'Hugo', 'last' => 'Domingo',
                'business' => 'Creative Sets & Decor', 'tagline' => 'Sculptural stage & reception design',
                'location' => 'Dar es Salaam, Tanzania', 'years' => 9, 'level' => 'business_verified',
                'featured' => false, 'rating' => 4.7, 'reviews' => 30, 'jobs' => 92, 'response' => 3,
                'website' => 'https://creativesetsdecor.example', 'instagram' => 'https://instagram.com/hugodomingoevents',
                'description' => 'Hugo Domingo’s Dar es Salaam studio, known for elegant, sculptural stage and reception setups.',
                'logo' => '1487530811176-3780de880c2d', 'cover' => '1490750967868-88aa4486c946',
            ],
            [
                'key' => 'accent', 'email' => 'accentplanners@osep.test', 'first' => 'Anita', 'last' => 'Massawe',
                'business' => 'Accent Planners', 'tagline' => 'Traditional, white & reception décor',
                'location' => 'Arusha, Tanzania', 'years' => 8, 'level' => 'business_verified',
                'featured' => false, 'rating' => 4.65, 'reviews' => 27, 'jobs' => 70, 'response' => 3,
                'website' => 'https://accentplanners.example', 'instagram' => 'https://instagram.com/accentplanners',
                'description' => 'Décor and styling across Dar es Salaam, Zanzibar and Arusha - traditional, white and reception celebrations.',
                'logo' => '1478146059778-26028b07395a', 'cover' => '1519167758481-83f550bb49b3',
            ],
            [
                'key' => 'efweddings', 'email' => 'efweddings@osep.test', 'first' => 'Mellania', 'last' => 'Nkundi',
                'business' => 'EF Weddings', 'tagline' => 'Stone Town destination décor',
                'location' => 'Stone Town, Zanzibar', 'years' => 6, 'level' => 'business_verified',
                'featured' => true, 'rating' => 4.7, 'reviews' => 25, 'jobs' => 54, 'response' => 3,
                'website' => 'https://efweddings.example', 'instagram' => 'https://instagram.com/efweddingstz',
                'description' => 'Stone Town décor and design studio, founded by Mellania Nkundi, specialising in destination weddings.',
                'logo' => '1533616688419-b7a585564566', 'cover' => '1487530811176-3780de880c2d',
            ],
            [
                'key' => 'pips', 'email' => 'pipsevents@osep.test', 'first' => 'Pili', 'last' => 'Msigwa',
                'business' => 'Pips Events', 'tagline' => 'Simple to grand, beautifully done',
                'location' => 'Masaki, Dar es Salaam', 'years' => 4, 'level' => 'business_verified',
                'featured' => false, 'rating' => 4.6, 'reviews' => 19, 'jobs' => 46, 'response' => 4,
                'website' => 'https://pipsevents.example', 'instagram' => 'https://instagram.com/pipsevents',
                'description' => 'Masaki-based décor studio creating both simple and grand wedding setups, with a love for destination celebrations.',
                'logo' => '1519225421980-715cb0215aed', 'cover' => '1523438885200-e635ba2c371e',
            ],
            [
                'key' => 'zukin', 'email' => 'zukinevents@osep.test', 'first' => 'Zubeda', 'last' => 'Kinabo',
                'business' => 'Zukin Events', 'tagline' => 'Colour & character for every stage',
                'location' => 'Dar es Salaam, Tanzania', 'years' => 5, 'level' => 'email_verified',
                'featured' => false, 'rating' => 4.55, 'reviews' => 16, 'jobs' => 38, 'response' => 4,
                'website' => 'https://zukinevents.example', 'instagram' => 'https://instagram.com/zukinevents',
                'description' => 'Creative Dar es Salaam décor team dressing stages, tables and aisles with colour and character.',
                'logo' => '1478476868527-002ae3f3e159', 'cover' => '1464047736614-af63643285bf',
            ],
        ];
    }
}
