<?php

namespace Database\Seeders;

use App\Enums\AccountType;
use App\Enums\UserStatus;
use App\Models\User;
use App\Models\VendorCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Nine Tanzanian DJs & entertainment acts as demo vendor accounts, so the
 * marketplace's DJs category shows a lively spread.
 *
 * Named DJ *businesses* are sparse online (the space is platform/individual
 * driven), so this mixes real acts found publicly (BeatsByJay, DJ Levi, Seca
 * Sound) with representative Tanzanian entertainment brands (Ngoma, Taarab,
 * Amapiano, Bongo). As with the other real-vendor seeders, login email + phone
 * are demo values (@osep.test / placeholder), NOT real contact lines, and
 * ratings/jobs are demo metrics. Idempotent (keyed on email), non-production.
 */
class DjVendorsSeeder extends Seeder
{
    private const PASSWORD = 'Password123!';

    public function run(): void
    {
        $category = VendorCategory::where('slug', 'djs')->first();

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
                'category' => $category?->name ?? 'DJs',
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

        $services = ['Wedding DJ set', 'MC / hype services', 'Sound & PA hire', 'Stage lighting & effects'];
        foreach ($services as $i => $name) {
            $vendor->vendorServices()->updateOrCreate(
                ['name' => $name],
                ['category_id' => $categoryId, 'description' => "{$name} by {$business}.", 'is_active' => true, 'sort_order' => $i],
            );
        }

        $packages = [
            ['Party Package', random_int(1000, 2500) * 1000, ['Up to 5 hours', 'Pro DJ + controller', 'Speakers & basic lighting', 'Requests welcome']],
            ['Wedding Package', random_int(2500, 5000) * 1000, ['Up to 8 hours', 'DJ + MC', 'Full PA + dancefloor lighting', 'Ceremony + reception sets', 'Wireless mics']],
            ['Premium Show', random_int(5000, 12000) * 1000, ['Full-day coverage', 'DJ + MC + hype crew', 'Concert-grade sound & lighting', 'Uplighting + effects (smoke/CO₂)', 'Live act collaboration']],
        ];
        foreach ($packages as $i => [$name, $price, $inclusions]) {
            $vendor->vendorPackages()->updateOrCreate(
                ['name' => $name],
                [
                    'description' => "{$name} from {$business}.",
                    'price' => $price, 'currency' => 'TZS', 'price_unit' => 'per event',
                    'inclusions' => $inclusions,
                    'addons' => [['name' => 'Extra hour', 'price' => 200_000], ['name' => 'Additional speaker stack', 'price' => 400_000], ['name' => 'Live saxophonist', 'price' => 600_000]],
                    'terms' => '50% deposit to lock the date. Sound check access needed 2 hours before.',
                    'is_active' => true, 'sort_order' => $i,
                ],
            );
        }

        // Portfolio - DJ / event imagery
        $shots = ['1470225620780-dba8ba36b745', '1459749411175-04bf5292ceea', '1514525253161-7a46d19cd819', '1429962714451-bb934ecdc4ec', '1493225457124-a3eb161ffa5f', '1533174072545-7a4b6ad7a6c3'];
        foreach (['Wedding Reception', 'Corporate Gala', 'Beach Party'] as $i => $title) {
            $vendor->vendorPortfolios()->updateOrCreate(
                ['title' => $title],
                [
                    'description' => "{$title} kept alive by {$business}.",
                    'event_type' => $title,
                    'event_date' => now()->subMonths(($i + 1) * 2)->toDateString(),
                    'cover_url' => $this->img($shots[($i * 2) % count($shots)], 800, 600),
                    'media' => [
                        ['type' => 'image', 'url' => $this->img($shots[($i * 2) % count($shots)], 800, 600), 'caption' => 'On the decks'],
                        ['type' => 'image', 'url' => $this->img($shots[($i * 2 + 1) % count($shots)], 800, 600), 'caption' => 'Full dancefloor'],
                    ],
                    'client_feedback' => 'The dancefloor did not empty once - read the crowd perfectly all night.',
                    'is_case_study' => $i === 1,
                    'sort_order' => $i,
                ],
            );
        }

        foreach (range(0, 20) as $d) {
            $status = match (true) {
                $d % 7 === 6 => 'fully_booked',
                $d % 11 === 3 => 'on_leave',
                $d % 5 === 2 => 'reserved',
                default => 'available',
            };
            $vendor->vendorAvailability()->updateOrCreate(['date' => now()->addDays($d)->toDateString()], ['status' => $status]);
        }
    }

    /**
     * Nine Tanzanian DJs & entertainment acts (real acts + representative brands).
     *
     * @return array<int, array<string, mixed>>
     */
    private function rows(): array
    {
        return [
            [
                'key' => 'beatsbyjay', 'email' => 'beatsbyjay@osep.test', 'first' => 'Jay', 'last' => 'Kotecha',
                'business' => 'BeatsByJay', 'tagline' => 'Multicultural wedding & event DJ',
                'location' => 'Dar es Salaam, Tanzania', 'years' => 10, 'level' => 'premium_partner',
                'featured' => true, 'rating' => 4.8, 'reviews' => 52, 'jobs' => 210, 'response' => 2,
                'website' => 'https://beatsbyjay.example', 'instagram' => 'https://instagram.com/beatsbyjay',
                'description' => 'Multicultural wedding DJ covering Indian, Bollywood, Bongo Flava and international sets for weddings, corporate events and receptions.',
                'logo' => '1493225457124-a3eb161ffa5f', 'cover' => '1470225620780-dba8ba36b745',
            ],
            [
                'key' => 'djlevi', 'email' => 'djlevi@osep.test', 'first' => 'Levi', 'last' => 'Mushi',
                'business' => 'DJ Levi', 'tagline' => 'Reggaeton · amapiano · Afrobeats',
                'location' => 'Arusha, Tanzania', 'years' => 6, 'level' => 'business_verified',
                'featured' => false, 'rating' => 4.65, 'reviews' => 28, 'jobs' => 96, 'response' => 3,
                'website' => 'https://djlevi.example', 'instagram' => 'https://instagram.com/djlevi',
                'description' => 'Arusha-based DJ (Sakina) spinning reggaeton, amapiano, R&B and Afrobeats for weddings and parties.',
                'logo' => '1516450360452-9312f5e86fc7', 'cover' => '1459749411175-04bf5292ceea',
            ],
            [
                'key' => 'secasound', 'email' => 'secasound@osep.test', 'first' => 'Seif', 'last' => 'Salum',
                'business' => 'Seca Sound', 'tagline' => 'Live guitar & saxophone duo',
                'location' => 'Zanzibar, Tanzania', 'years' => 8, 'level' => 'business_verified',
                'featured' => true, 'rating' => 4.75, 'reviews' => 34, 'jobs' => 120, 'response' => 3,
                'website' => 'https://secasound.example', 'instagram' => 'https://instagram.com/secasound',
                'description' => 'Zanzibar live guitar-and-saxophone duo bringing smooth, soulful sets to receptions and cocktail hours.',
                'logo' => '1470229538611-16ba8c7ffbd7', 'cover' => '1514525253161-7a46d19cd819',
            ],
            [
                'key' => 'ngoma', 'email' => 'ngomadrummers@osep.test', 'first' => 'Juma', 'last' => 'Kileo',
                'business' => 'Ngoma Heritage Drummers', 'tagline' => 'Traditional drummers & dancers',
                'location' => 'Dar es Salaam, Tanzania', 'years' => 12, 'level' => 'business_verified',
                'featured' => true, 'rating' => 4.7, 'reviews' => 41, 'jobs' => 150, 'response' => 3,
                'website' => 'https://ngomadrummers.example', 'instagram' => 'https://instagram.com/ngomaheritage',
                'description' => 'Traditional Ngoma drummers and dancers for grand entrances, send-offs and cultural celebrations.',
                'logo' => '1470225620780-dba8ba36b745', 'cover' => '1493225457124-a3eb161ffa5f',
            ],
            [
                'key' => 'taarab', 'email' => 'taarabnights@osep.test', 'first' => 'Rukia', 'last' => 'Abdallah',
                'business' => 'Taarab Nights Ensemble', 'tagline' => 'Classic Swahili wedding music',
                'location' => 'Zanzibar, Tanzania', 'years' => 9, 'level' => 'business_verified',
                'featured' => false, 'rating' => 4.7, 'reviews' => 26, 'jobs' => 88, 'response' => 4,
                'website' => 'https://taarabnights.example', 'instagram' => 'https://instagram.com/taarabnights',
                'description' => 'A classic Taarab orchestra performing Swahili wedding music and coastal favourites for elegant celebrations.',
                'logo' => '1459749411175-04bf5292ceea', 'cover' => '1470229538611-16ba8c7ffbd7',
            ],
            [
                'key' => 'amapiano', 'email' => 'amapianocollective@osep.test', 'first' => 'Aggrey', 'last' => 'Mushi',
                'business' => 'Amapiano Collective TZ', 'tagline' => 'Amapiano · house · Afrobeats',
                'location' => 'Dar es Salaam, Tanzania', 'years' => 4, 'level' => 'email_verified',
                'featured' => false, 'rating' => 4.55, 'reviews' => 18, 'jobs' => 52, 'response' => 4,
                'website' => 'https://amapianocollective.example', 'instagram' => 'https://instagram.com/amapianocollectivetz',
                'description' => 'A high-energy DJ crew specialising in amapiano, house and Afrobeats to keep the dancefloor full all night.',
                'logo' => '1533174072545-7a4b6ad7a6c3', 'cover' => '1429962714451-bb934ecdc4ec',
            ],
            [
                'key' => 'bongobeats', 'email' => 'bongobeats@osep.test', 'first' => 'Baraka', 'last' => 'Mwakalinga',
                'business' => 'Bongo Beats Entertainment', 'tagline' => 'DJ + MC entertainment company',
                'location' => 'Dar es Salaam, Tanzania', 'years' => 7, 'level' => 'business_verified',
                'featured' => false, 'rating' => 4.7, 'reviews' => 30, 'jobs' => 104, 'response' => 3,
                'website' => 'https://bongobeats.example', 'instagram' => 'https://instagram.com/bongobeatstz',
                'description' => 'Full-service DJ and MC entertainment company for weddings, corporate galas and concerts across Tanzania.',
                'logo' => '1506157786151-b8491531f063', 'cover' => '1516450360452-9312f5e86fc7',
            ],
            [
                'key' => 'serengetisound', 'email' => 'serengetisound@osep.test', 'first' => 'Emanuel', 'last' => 'Laizer',
                'business' => 'Serengeti Sound DJs', 'tagline' => 'Pro sound & lighting, northern circuit',
                'location' => 'Arusha, Tanzania', 'years' => 6, 'level' => 'business_verified',
                'featured' => false, 'rating' => 4.6, 'reviews' => 22, 'jobs' => 70, 'response' => 3,
                'website' => 'https://serengetisound.example', 'instagram' => 'https://instagram.com/serengetisound',
                'description' => 'Northern-circuit event DJ company delivering weddings, safaris and corporate functions with pro sound and lighting.',
                'logo' => '1483393458019-411bc6bd104e', 'cover' => '1506157786151-b8491531f063',
            ],
            [
                'key' => 'nightshift', 'email' => 'nightshiftdjs@osep.test', 'first' => 'Noel', 'last' => 'Charles',
                'business' => 'Nightshift DJ Crew', 'tagline' => 'Festival-grade sound & lighting',
                'location' => 'Mwanza, Tanzania', 'years' => 5, 'level' => 'business_verified',
                'featured' => false, 'rating' => 4.55, 'reviews' => 17, 'jobs' => 48, 'response' => 4,
                'website' => 'https://nightshiftdjs.example', 'instagram' => 'https://instagram.com/nightshiftdjs',
                'description' => 'Club and event DJs from Mwanza bringing festival-grade sound and lighting to weddings and parties.',
                'logo' => '1429962714451-bb934ecdc4ec', 'cover' => '1514525253161-7a46d19cd819',
            ],
        ];
    }
}
