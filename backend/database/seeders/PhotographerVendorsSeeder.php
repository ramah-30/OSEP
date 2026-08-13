<?php

namespace Database\Seeders;

use App\Enums\AccountType;
use App\Enums\UserStatus;
use App\Models\User;
use App\Models\VendorCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Nine real Tanzanian wedding/event photography studios as demo vendor accounts,
 * so the marketplace's Photographers category shows recognisable listings.
 *
 * Real, public details are used — studio name, city, description, website /
 * social — but login email and phone are demo values (@osep.test / a placeholder
 * number), NOT the studios' real contact lines, since these are illustrative demo
 * accounts, not genuine sign-ups. Ratings/job counts are demo metrics. Idempotent
 * (keyed on email) and guarded to non-production in DatabaseSeeder.
 */
class PhotographerVendorsSeeder extends Seeder
{
    private const PASSWORD = 'Password123!';

    public function run(): void
    {
        $category = VendorCategory::where('slug', 'photographers')->first();

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
                'category' => $category?->name ?? 'Photographers',
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

            $this->storefront($user, $r['key'], $r['business']);
        }
    }

    /** Build an Unsplash image URL from a photo id. */
    private function img(string $id, int $w, int $h): string
    {
        return "https://images.unsplash.com/photo-{$id}?w={$w}&h={$h}&fit=crop&q=80";
    }

    private function storefront(User $vendor, string $key, string $business): void
    {
        $categoryId = $vendor->vendorProfile->category_id;

        $services = ['Wedding photography', 'Engagement & pre-wedding shoots', 'Event coverage', 'Cinematic videography'];
        foreach ($services as $i => $name) {
            $vendor->vendorServices()->updateOrCreate(
                ['name' => $name],
                ['category_id' => $categoryId, 'description' => "{$name} by {$business}.", 'is_active' => true, 'sort_order' => $i],
            );
        }

        $packages = [
            ['Half-Day Coverage', random_int(1200, 2500) * 1000, ['Up to 5 hours coverage', 'One photographer', '150+ edited images', 'Private online gallery']],
            ['Full-Day Coverage', random_int(3000, 6000) * 1000, ['Up to 10 hours coverage', 'Two photographers', '400+ edited images', 'Online gallery + USB', 'Sneak-peek within 48h']],
            ['Luxury Collection', random_int(7000, 14000) * 1000, ['Full-day + prep coverage', 'Lead + second shooter', 'Cinematic highlight film', 'Fine-art album', 'Complimentary engagement shoot']],
        ];
        foreach ($packages as $i => [$name, $price, $inclusions]) {
            $vendor->vendorPackages()->updateOrCreate(
                ['name' => $name],
                [
                    'description' => "{$name} from {$business}.",
                    'price' => $price, 'currency' => 'TZS', 'price_unit' => 'per event',
                    'inclusions' => $inclusions,
                    'addons' => [['name' => 'Extra hour', 'price' => 200_000], ['name' => 'Drone coverage', 'price' => 600_000], ['name' => 'Second shooter', 'price' => 450_000]],
                    'terms' => '50% deposit to confirm the date. Edited gallery delivered within 3–4 weeks.',
                    'is_active' => true, 'sort_order' => $i,
                ],
            );
        }

        // Portfolio — photography imagery
        $shots = ['1511285560929-80b456fea0bc', '1502920917128-1aa500764cbd', '1583939003579-730e3918a45a', '1522673607200-164d1b6ce486', '1537633552985-df8429e8048b', '1516035069371-29a1b244cc32'];
        foreach (['Beach Wedding', 'Stone Town Engagement', 'Corporate Gala'] as $i => $title) {
            $vendor->vendorPortfolios()->updateOrCreate(
                ['title' => $title],
                [
                    'description' => "{$title} shot by {$business}.",
                    'event_type' => $title,
                    'event_date' => now()->subMonths(($i + 1) * 2)->toDateString(),
                    'cover_url' => $this->img($shots[($i * 2) % count($shots)], 800, 600),
                    'media' => [
                        ['type' => 'image', 'url' => $this->img($shots[($i * 2) % count($shots)], 800, 600), 'caption' => 'Ceremony'],
                        ['type' => 'image', 'url' => $this->img($shots[($i * 2 + 1) % count($shots)], 800, 600), 'caption' => 'Portraits'],
                    ],
                    'client_feedback' => 'Every photo tells the story of our day — we could not be happier with the gallery.',
                    'is_case_study' => $i === 1,
                    'sort_order' => $i,
                ],
            );
        }

        foreach (range(0, 20) as $d) {
            $status = match (true) {
                $d % 7 === 5 => 'fully_booked',
                $d % 11 === 4 => 'on_leave',
                $d % 5 === 1 => 'reserved',
                default => 'available',
            };
            $vendor->vendorAvailability()->updateOrCreate(['date' => now()->addDays($d)->toDateString()], ['status' => $status]);
        }
    }

    /**
     * Nine real Tanzanian photography studios (public details).
     *
     * @return array<int, array<string, mixed>>
     */
    private function rows(): array
    {
        return [
            [
                'key' => 'royal', 'email' => 'royalweddingtz@osep.test', 'first' => 'Emmanuel', 'last' => 'Kessy',
                'business' => 'Royal Wedding Cinema & Photography', 'tagline' => 'Fine-art photography & cinematic film',
                'location' => 'Dar es Salaam, Tanzania', 'years' => 10, 'level' => 'premium_partner',
                'featured' => true, 'rating' => 4.85, 'reviews' => 62, 'jobs' => 180, 'response' => 2,
                'website' => 'https://www.royalweddingtz.com', 'instagram' => 'https://instagram.com/royalweddingtz',
                'description' => 'Dar es Salaam luxury studio pairing fine-art photography with cinematic videography, shooting at Tanzania’s top venues — Serena, Hyatt Regency, Johari Rotana and beyond.',
                'logo' => '1502920917128-1aa500764cbd', 'cover' => '1511285560929-80b456fea0bc',
            ],
            [
                'key' => 'zap', 'email' => 'zap@osep.test', 'first' => 'Zuberi', 'last' => 'Ally',
                'business' => 'ZAP Photography', 'tagline' => '600+ weddings & counting',
                'location' => 'Dar es Salaam, Tanzania', 'years' => 15, 'level' => 'premium_partner',
                'featured' => true, 'rating' => 4.9, 'reviews' => 88, 'jobs' => 620, 'response' => 1,
                'website' => 'https://zapstudioportraits.mypixieset.com', 'instagram' => 'https://instagram.com/zapphotography',
                'description' => 'Tanzania’s leading wedding studio — 15+ years and 600+ weddings — covering weddings, events, portraits and commercial shoots from Dar es Salaam.',
                'logo' => '1606216794074-735e91aa2c92', 'cover' => '1554048612-b6a482bc67e5',
            ],
            [
                'key' => 'silverink', 'email' => 'silverink@osep.test', 'first' => 'Irene', 'last' => 'Mushi',
                'business' => 'Silver Ink Weddings', 'tagline' => 'Modern, luxury wedding stories',
                'location' => 'Dar es Salaam, Tanzania', 'years' => 7, 'level' => 'business_verified',
                'featured' => false, 'rating' => 4.7, 'reviews' => 33, 'jobs' => 78, 'response' => 3,
                'website' => 'https://silverinkweddings.example', 'instagram' => 'https://instagram.com/silverinkweddings',
                'description' => 'Dar es Salaam storytellers capturing modern, luxury wedding stories with a clean, editorial eye.',
                'logo' => '1583939003579-730e3918a45a', 'cover' => '1502920917128-1aa500764cbd',
            ],
            [
                'key' => 'cpstudios', 'email' => 'cpstudios@osep.test', 'first' => 'Clemence', 'last' => 'Eliah',
                'business' => 'CP Studios', 'tagline' => 'Destination luxury weddings',
                'location' => 'Arusha, Tanzania', 'years' => 8, 'level' => 'business_verified',
                'featured' => true, 'rating' => 4.75, 'reviews' => 41, 'jobs' => 96, 'response' => 2,
                'website' => 'https://cpstudios.example', 'instagram' => 'https://instagram.com/cpstudios',
                'description' => 'Destination luxury wedding photography across Zanzibar and Arusha, led by Clemence Eliah.',
                'logo' => '1511285560929-80b456fea0bc', 'cover' => '1583939003579-730e3918a45a',
            ],
            [
                'key' => 'grandtone', 'email' => 'grandtone@osep.test', 'first' => 'Joshua', 'last' => 'Zizrael',
                'business' => 'Grandtone Studios', 'tagline' => 'Joshphix — destination luxury weddings',
                'location' => 'Arusha, Tanzania', 'years' => 9, 'level' => 'business_verified',
                'featured' => false, 'rating' => 4.7, 'reviews' => 29, 'jobs' => 84, 'response' => 3,
                'website' => 'https://grandtonestudios.example', 'instagram' => 'https://instagram.com/joshphix',
                'description' => 'Arusha-based studio specialising in destination luxury weddings, led by Joshua Zizrael (Joshphix).',
                'logo' => '1516035069371-29a1b244cc32', 'cover' => '1522673607200-164d1b6ce486',
            ],
            [
                'key' => 'jafassam', 'email' => 'jafassam@osep.test', 'first' => 'Jafari', 'last' => 'Salum',
                'business' => 'Jafassam Studio', 'tagline' => 'Timeless Zanzibar wedding photography',
                'location' => 'Zanzibar, Tanzania', 'years' => 6, 'level' => 'business_verified',
                'featured' => false, 'rating' => 4.65, 'reviews' => 24, 'jobs' => 52, 'response' => 4,
                'website' => 'https://www.jafassam.com', 'instagram' => 'https://instagram.com/jafassam',
                'description' => 'Zanzibar wedding photographers capturing timeless images for destination couples along the island’s beaches and Stone Town.',
                'logo' => '1522673607200-164d1b6ce486', 'cover' => '1460978812857-470ed1c77af0',
            ],
            [
                'key' => 'principalfocus', 'email' => 'principalfocus@osep.test', 'first' => 'Denis', 'last' => 'Lyamuya',
                'business' => 'Principal Focus Photography', 'tagline' => 'Photography & film, Nat Geo-credited',
                'location' => 'Dar es Salaam, Tanzania', 'years' => 7, 'level' => 'business_verified',
                'featured' => true, 'rating' => 4.6, 'reviews' => 27, 'jobs' => 70, 'response' => 3,
                'website' => 'https://principalfocus.example', 'instagram' => 'https://instagram.com/principalfocus',
                'description' => 'Seasoned Dar es Salaam photographer and videographer Denis Lyamuya, with credits including National Geographic and USAID.',
                'logo' => '1452587925148-ce544e77e70d', 'cover' => '1516035069371-29a1b244cc32',
            ],
            [
                'key' => 'kenlaw', 'email' => 'kenlaw@osep.test', 'first' => 'Kennedy', 'last' => 'Laurent',
                'business' => 'Kenlaw Photography', 'tagline' => 'Fast, polished wedding coverage',
                'location' => 'Dar es Salaam, Tanzania', 'years' => 5, 'level' => 'email_verified',
                'featured' => false, 'rating' => 4.5, 'reviews' => 18, 'jobs' => 46, 'response' => 4,
                'website' => 'https://kenlawphotography.example', 'instagram' => 'https://instagram.com/kenlawphotography',
                'description' => 'Dependable Dar es Salaam wedding and event photography with fast, polished delivery.',
                'logo' => '1500648767791-00dcc994a43e', 'cover' => '1606216794074-735e91aa2c92',
            ],
            [
                'key' => 'luckson', 'email' => 'lucksonrugah@osep.test', 'first' => 'Luckson', 'last' => 'Rugah',
                'business' => 'Luckson Rugah Photography', 'tagline' => 'Emotive documentary weddings',
                'location' => 'Mwanza, Tanzania', 'years' => 6, 'level' => 'business_verified',
                'featured' => false, 'rating' => 4.55, 'reviews' => 22, 'jobs' => 58, 'response' => 3,
                'website' => 'https://lucksonrugah.example', 'instagram' => 'https://instagram.com/lucksonrugah',
                'description' => 'Award-recognised Tanzanian wedding photographer known for emotive, natural documentary coverage.',
                'logo' => '1537633552985-df8429e8048b', 'cover' => '1500648767791-00dcc994a43e',
            ],
        ];
    }
}
