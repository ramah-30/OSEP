<?php

namespace Database\Seeders;

use App\Enums\AccountType;
use App\Enums\UserStatus;
use App\Models\User;
use App\Models\VendorCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Nine real Tanzanian wedding/event videography studios as demo vendor accounts,
 * so the marketplace's Videographers category shows recognisable listings.
 *
 * Real, public details are used — studio name, city, description, website /
 * social — but login email and phone are demo values (@osep.test / a placeholder
 * number), NOT the studios' real contact lines, since these are illustrative demo
 * accounts, not genuine sign-ups. Ratings/job counts are demo metrics. Idempotent
 * (keyed on email) and guarded to non-production in DatabaseSeeder.
 */
class VideographerVendorsSeeder extends Seeder
{
    private const PASSWORD = 'Password123!';

    public function run(): void
    {
        $category = VendorCategory::where('slug', 'videographers')->first();

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
                'category' => $category?->name ?? 'Videographers',
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

        $services = ['Wedding films', 'Highlight reels', 'Event & corporate video', 'Aerial drone coverage'];
        foreach ($services as $i => $name) {
            $vendor->vendorServices()->updateOrCreate(
                ['name' => $name],
                ['category_id' => $categoryId, 'description' => "{$name} by {$business}.", 'is_active' => true, 'sort_order' => $i],
            );
        }

        $packages = [
            ['Highlight Film', random_int(1500, 3000) * 1000, ['Up to 6 hours filming', 'One cinematographer', '3–5 min highlight film', 'Online delivery']],
            ['Feature Film', random_int(3500, 7000) * 1000, ['Full-day filming', 'Two cinematographers', 'Highlight + 20–30 min feature', 'Drone coverage', 'Online + USB']],
            ['Cinematic Collection', random_int(8000, 16000) * 1000, ['Multi-day filming', 'Full crew', 'Teaser + highlight + feature film', 'Same-day edit option', 'Aerial + gimbal work', 'Licensed soundtrack']],
        ];
        foreach ($packages as $i => [$name, $price, $inclusions]) {
            $vendor->vendorPackages()->updateOrCreate(
                ['name' => $name],
                [
                    'description' => "{$name} from {$business}.",
                    'price' => $price, 'currency' => 'TZS', 'price_unit' => 'per event',
                    'inclusions' => $inclusions,
                    'addons' => [['name' => 'Extra hour', 'price' => 250_000], ['name' => 'Same-day teaser', 'price' => 800_000], ['name' => 'Additional drone day', 'price' => 700_000]],
                    'terms' => '50% deposit to reserve the date. Final film delivered within 4–6 weeks.',
                    'is_active' => true, 'sort_order' => $i,
                ],
            );
        }

        // Portfolio — film/videography imagery
        $shots = ['1579632652768-6cb9dcf85912', '1601506521937-0121a7fc2a6b', '1626814026160-2237a95fc5a0', '1502920917128-1aa500764cbd', '1583939003579-730e3918a45a', '1440404653325-ab127d49abc1'];
        foreach (['Beach Wedding Film', 'Nikah Celebration', 'Corporate Launch'] as $i => $title) {
            $vendor->vendorPortfolios()->updateOrCreate(
                ['title' => $title],
                [
                    'description' => "{$title} filmed by {$business}.",
                    'event_type' => $title,
                    'event_date' => now()->subMonths(($i + 1) * 2)->toDateString(),
                    'cover_url' => $this->img($shots[($i * 2) % count($shots)], 800, 600),
                    'media' => [
                        ['type' => 'image', 'url' => $this->img($shots[($i * 2) % count($shots)], 800, 600), 'caption' => 'On set'],
                        ['type' => 'image', 'url' => $this->img($shots[($i * 2 + 1) % count($shots)], 800, 600), 'caption' => 'Highlight frame'],
                    ],
                    'client_feedback' => 'Watching our film brings the whole day back — beautifully shot and edited.',
                    'is_case_study' => $i === 1,
                    'sort_order' => $i,
                ],
            );
        }

        foreach (range(0, 20) as $d) {
            $status = match (true) {
                $d % 7 === 2 => 'fully_booked',
                $d % 11 === 8 => 'on_leave',
                $d % 5 === 4 => 'reserved',
                default => 'available',
            };
            $vendor->vendorAvailability()->updateOrCreate(['date' => now()->addDays($d)->toDateString()], ['status' => $status]);
        }
    }

    /**
     * Nine real Tanzanian videography studios (public details).
     *
     * @return array<int, array<string, mixed>>
     */
    private function rows(): array
    {
        return [
            [
                'key' => 'storytailors', 'email' => 'storytailors@osep.test', 'first' => 'Sofia', 'last' => 'Tarimo',
                'business' => 'Storytailors', 'tagline' => 'Broadcast-quality event & wedding films',
                'location' => 'Dar es Salaam, Tanzania', 'years' => 10, 'level' => 'premium_partner',
                'featured' => true, 'rating' => 4.85, 'reviews' => 47, 'jobs' => 160, 'response' => 2,
                'website' => 'https://storytailors.tv', 'instagram' => 'https://instagram.com/storytailors',
                'description' => 'Full-service video production company delivering broadcast-quality event, corporate and wedding films across Tanzania.',
                'logo' => '1522673607200-164d1b6ce486', 'cover' => '1516035069371-29a1b244cc32',
            ],
            [
                'key' => 'prism', 'email' => 'prismphotolab@osep.test', 'first' => 'Pritesh', 'last' => 'Shah',
                'business' => 'Prism Photolab', 'tagline' => 'Personalised wedding films & commercials',
                'location' => 'Dar es Salaam, Tanzania', 'years' => 9, 'level' => 'premium_partner',
                'featured' => true, 'rating' => 4.8, 'reviews' => 39, 'jobs' => 110, 'response' => 2,
                'website' => 'https://prismphotolab.example', 'instagram' => 'https://instagram.com/prismphotolab',
                'description' => 'Tanzania wedding and commercial studio crafting personalised wedding videos that reflect each couple’s story.',
                'logo' => '1598899134739-24c46f58b8c0', 'cover' => '1579632652768-6cb9dcf85912',
            ],
            [
                'key' => 'lorenzoslog', 'email' => 'lorenzoslog@osep.test', 'first' => 'Lorenzo', 'last' => 'Slog',
                'business' => 'Lorenzo Slog Films', 'tagline' => 'Documentary-meets-cinematic wedding film',
                'location' => 'Arusha, Tanzania', 'years' => 8, 'level' => 'business_verified',
                'featured' => true, 'rating' => 4.75, 'reviews' => 31, 'jobs' => 74, 'response' => 3,
                'website' => 'https://lorenzoslog.example', 'instagram' => 'https://instagram.com/lorenzoslog',
                'description' => 'Arusha cinematographer blending documentary-style footage with cinematic aesthetics for weddings and films.',
                'logo' => '1626814026160-2237a95fc5a0', 'cover' => '1440404653325-ab127d49abc1',
            ],
            [
                'key' => 'mosfilmz', 'email' => 'mosfilmz@osep.test', 'first' => 'Moses', 'last' => 'Kato',
                'business' => 'Mos Filmz', 'tagline' => 'Polished highlight reels & feature films',
                'location' => 'Dar es Salaam, Tanzania', 'years' => 7, 'level' => 'business_verified',
                'featured' => false, 'rating' => 4.7, 'reviews' => 28, 'jobs' => 82, 'response' => 3,
                'website' => 'https://mosfilmz.example', 'instagram' => 'https://instagram.com/mosfilmz',
                'description' => 'Dar es Salaam wedding filmmakers known for polished highlight reels and full feature films.',
                'logo' => '1518676590629-3dcbd9c5a5c9', 'cover' => '1626814026160-2237a95fc5a0',
            ],
            [
                'key' => 'sibtain', 'email' => 'sibtainfilms@osep.test', 'first' => 'Sibtain', 'last' => 'Mushtaq',
                'business' => 'Sibtain Mushtaq Films', 'tagline' => 'Cinematic coastal wedding films',
                'location' => 'Zanzibar, Tanzania', 'years' => 7, 'level' => 'business_verified',
                'featured' => false, 'rating' => 4.7, 'reviews' => 26, 'jobs' => 60, 'response' => 3,
                'website' => 'https://sibtainmushtaqfilms.example', 'instagram' => 'https://instagram.com/sibtainmushtaqfilms',
                'description' => 'Zanzibar wedding and commercial videographer producing cinematic films along the coast.',
                'logo' => '1516035069371-29a1b244cc32', 'cover' => '1522673607200-164d1b6ce486',
            ],
            [
                'key' => 'mediabox', 'email' => 'mediaboxtz@osep.test', 'first' => 'Baraka', 'last' => 'Mtei',
                'business' => 'Media Box Studios', 'tagline' => 'Destination photo & video, will travel',
                'location' => 'Zanzibar, Tanzania', 'years' => 6, 'level' => 'business_verified',
                'featured' => false, 'rating' => 4.6, 'reviews' => 23, 'jobs' => 55, 'response' => 4,
                'website' => 'https://mediaboxtz.example', 'instagram' => 'https://instagram.com/mediaboxtz',
                'description' => 'Zanzibar-based destination photo and video team, travelling anywhere in Tanzania to film weddings.',
                'logo' => '1567593810070-7a3d471af022', 'cover' => '1598899134739-24c46f58b8c0',
            ],
            [
                'key' => 'siricine', 'email' => 'siricine@osep.test', 'first' => 'Sadick', 'last' => 'Iddi',
                'business' => 'Siri Cine', 'tagline' => 'Modern, emotive event films',
                'location' => 'Dar es Salaam, Tanzania', 'years' => 5, 'level' => 'business_verified',
                'featured' => false, 'rating' => 4.65, 'reviews' => 20, 'jobs' => 48, 'response' => 4,
                'website' => 'https://siricine.example', 'instagram' => 'https://instagram.com/siricine',
                'description' => 'Cinematic wedding and event films with a modern, emotive edit, based in Dar es Salaam.',
                'logo' => '1601506521937-0121a7fc2a6b', 'cover' => '1518676590629-3dcbd9c5a5c9',
            ],
            [
                'key' => 'arrey', 'email' => 'arreyshotit@osep.test', 'first' => 'Arrey', 'last' => 'Mbwilo',
                'business' => 'Arrey Shot It', 'tagline' => 'Energetic, story-driven wedding films',
                'location' => 'Dar es Salaam, Tanzania', 'years' => 6, 'level' => 'business_verified',
                'featured' => false, 'rating' => 4.7, 'reviews' => 25, 'jobs' => 64, 'response' => 3,
                'website' => 'https://arreyshotit.example', 'instagram' => 'https://instagram.com/arreyshotit',
                'description' => 'Creative Dar es Salaam wedding videographer crafting energetic, story-driven highlight films.',
                'logo' => '1579632652768-6cb9dcf85912', 'cover' => '1601506521937-0121a7fc2a6b',
            ],
            [
                'key' => 'asili', 'email' => 'asilivisual@osep.test', 'first' => 'Asha', 'last' => 'Silayo',
                'business' => 'Asili Visual', 'tagline' => 'Authentic wedding & event storytelling',
                'location' => 'Arusha, Tanzania', 'years' => 5, 'level' => 'email_verified',
                'featured' => false, 'rating' => 4.55, 'reviews' => 17, 'jobs' => 40, 'response' => 4,
                'website' => 'https://asilivisual.example', 'instagram' => 'https://instagram.com/asilivisual',
                'description' => 'Creative visual storytellers capturing weddings and events with an authentic, natural feel.',
                'logo' => '1440404653325-ab127d49abc1', 'cover' => '1567593810070-7a3d471af022',
            ],
        ];
    }
}
