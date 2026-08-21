<?php

namespace Database\Seeders;

use App\Enums\AccountType;
use App\Enums\UserStatus;
use App\Models\User;
use App\Models\VendorCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Nine real Tanzanian wedding MCs (masters of ceremony) as demo vendor accounts,
 * so the marketplace's MCs category shows recognisable hosts.
 *
 * These are real public-facing MCs who advertise for hire under their stage
 * names. As with the other real-vendor seeders, the studio/stage name and public
 * style are real, but login email + phone are demo values (@osep.test /
 * placeholder), NOT their real contact lines, and ratings/jobs are demo metrics.
 * Idempotent (keyed on email), non-production.
 */
class McVendorsSeeder extends Seeder
{
    private const PASSWORD = 'Password123!';

    public function run(): void
    {
        $category = VendorCategory::where('slug', 'mcs')->first();

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
                'category' => $category?->name ?? 'MCs',
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

        $services = ['Wedding MC / hosting', 'Corporate event hosting', 'Bilingual (EN/SW) hosting', 'Programme & run-of-show planning'];
        foreach ($services as $i => $name) {
            $vendor->vendorServices()->updateOrCreate(
                ['name' => $name],
                ['category_id' => $categoryId, 'description' => "{$name} by {$business}.", 'is_active' => true, 'sort_order' => $i],
            );
        }

        $packages = [
            ['Ceremony Host', random_int(800, 1800) * 1000, ['Ceremony hosting', 'Run-of-show coordination', 'One planning call']],
            ['Full Wedding Host', random_int(1800, 3500) * 1000, ['Ceremony + reception hosting', 'Bilingual (EN/SW)', 'Programme design', 'Vendor cueing', 'Two planning sessions']],
            ['Signature Host', random_int(3500, 7000) * 1000, ['Full-day hosting', 'Custom scripting & games', 'Rehearsal attendance', 'Hype & energy segments', 'Backup MC on standby']],
        ];
        foreach ($packages as $i => [$name, $price, $inclusions]) {
            $vendor->vendorPackages()->updateOrCreate(
                ['name' => $name],
                [
                    'description' => "{$name} from {$business}.",
                    'price' => $price, 'currency' => 'TZS', 'price_unit' => 'per event',
                    'inclusions' => $inclusions,
                    'addons' => [['name' => 'Rehearsal day', 'price' => 300_000], ['name' => 'Extra hours', 'price' => 200_000], ['name' => 'Second language segment', 'price' => 250_000]],
                    'terms' => '50% deposit to confirm the date. Final programme locked 3 days before.',
                    'is_active' => true, 'sort_order' => $i,
                ],
            );
        }

        // Portfolio - hosting / stage imagery
        $shots = ['1505236858219-8359eb29e329', '1516280440614-37939bbacd81', '1543007630-9710e4a00a20', '1587825140708-dfaf72ae4b04', '1470229538611-16ba8c7ffbd7', '1493225457124-a3eb161ffa5f'];
        foreach (['Wedding Reception', 'Traditional Send-off', 'Corporate Awards'] as $i => $title) {
            $vendor->vendorPortfolios()->updateOrCreate(
                ['title' => $title],
                [
                    'description' => "{$title} hosted by {$business}.",
                    'event_type' => $title,
                    'event_date' => now()->subMonths(($i + 1) * 2)->toDateString(),
                    'cover_url' => $this->img($shots[($i * 2) % count($shots)], 800, 600),
                    'media' => [
                        ['type' => 'image', 'url' => $this->img($shots[($i * 2) % count($shots)], 800, 600), 'caption' => 'On the mic'],
                        ['type' => 'image', 'url' => $this->img($shots[($i * 2 + 1) % count($shots)], 800, 600), 'caption' => 'Working the room'],
                    ],
                    'client_feedback' => 'Kept the whole programme flowing and had our guests laughing all night - a natural host.',
                    'is_case_study' => $i === 1,
                    'sort_order' => $i,
                ],
            );
        }

        foreach (range(0, 20) as $d) {
            $status = match (true) {
                $d % 7 === 0 => 'fully_booked',
                $d % 11 === 7 => 'on_leave',
                $d % 5 === 3 => 'reserved',
                default => 'available',
            };
            $vendor->vendorAvailability()->updateOrCreate(['date' => now()->addDays($d)->toDateString()], ['status' => $status]);
        }
    }

    /**
     * Nine real Tanzanian wedding MCs (public stage names & styles).
     *
     * @return array<int, array<string, mixed>>
     */
    private function rows(): array
    {
        return [
            [
                'key' => 'garab', 'email' => 'mcgarab@osep.test', 'first' => 'Godfrey', 'last' => 'Rugarabamu',
                'business' => 'MC Gara B', 'tagline' => 'Humour with elegance',
                'location' => 'Dar es Salaam, Tanzania', 'years' => 9, 'level' => 'premium_partner',
                'featured' => true, 'rating' => 4.8, 'reviews' => 54, 'jobs' => 160, 'response' => 2,
                'website' => 'https://mcgarab.example', 'instagram' => 'https://instagram.com/mcgarab',
                'description' => 'Blends humour with elegance for destination weddings and luxury receptions - a polished, charismatic host.',
                'logo' => '1505236858219-8359eb29e329', 'cover' => '1516280440614-37939bbacd81',
            ],
            [
                'key' => 'luvanda', 'email' => 'mcluvanda@osep.test', 'first' => 'Anthony', 'last' => 'Luvanda',
                'business' => 'MC Luvanda', 'tagline' => 'Bilingual, mixed-culture weddings',
                'location' => 'Dar es Salaam, Tanzania', 'years' => 8, 'level' => 'business_verified',
                'featured' => true, 'rating' => 4.75, 'reviews' => 41, 'jobs' => 130, 'response' => 2,
                'website' => 'https://mcluvanda.example', 'instagram' => 'https://instagram.com/mcluvanda',
                'description' => 'Specialist in mixed-culture and bilingual (English & Swahili) weddings, with strong cultural awareness and warmth.',
                'logo' => '1560439514-4e9645039924', 'cover' => '1543007630-9710e4a00a20',
            ],
            [
                'key' => 'drcheni', 'email' => 'mcdrcheni@osep.test', 'first' => 'Mahsein', 'last' => 'Awadhi',
                'business' => 'MC Drcheni', 'tagline' => 'Humour & elegance, Dar es Salaam',
                'location' => 'Dar es Salaam, Tanzania', 'years' => 7, 'level' => 'business_verified',
                'featured' => false, 'rating' => 4.7, 'reviews' => 32, 'jobs' => 98, 'response' => 3,
                'website' => 'https://mcdrcheni.example', 'instagram' => 'https://instagram.com/mcdrcheni',
                'description' => 'Dar es Salaam MC combining humour with elegance for destination and traditional ceremonies.',
                'logo' => '1516280440614-37939bbacd81', 'cover' => '1505236858219-8359eb29e329',
            ],
            [
                'key' => 'linah', 'email' => 'mclinah@osep.test', 'first' => 'Adeline', 'last' => 'Mushi',
                'business' => 'MC Linah', 'tagline' => 'Storytelling, interactive hosting',
                'location' => 'Dar es Salaam, Tanzania', 'years' => 6, 'level' => 'business_verified',
                'featured' => false, 'rating' => 4.7, 'reviews' => 28, 'jobs' => 76, 'response' => 3,
                'website' => 'https://mclinah.example', 'instagram' => 'https://instagram.com/mclinah',
                'description' => 'Storytelling, interactive host creating memorable, personal wedding experiences.',
                'logo' => '1543007630-9710e4a00a20', 'cover' => '1560439514-4e9645039924',
            ],
            [
                'key' => 'motomoto', 'email' => 'mcmotomoto@osep.test', 'first' => 'William', 'last' => 'Narcis',
                'business' => 'MC Motomoto', 'tagline' => 'Sharp wit, impeccable timing',
                'location' => 'Mikocheni, Dar es Salaam', 'years' => 6, 'level' => 'business_verified',
                'featured' => false, 'rating' => 4.7, 'reviews' => 30, 'jobs' => 84, 'response' => 3,
                'website' => 'https://mcmotomoto.example', 'instagram' => 'https://instagram.com/mcmotomoto',
                'description' => 'Mikocheni-based MC known for sharp wit, impeccable timing and a warm stage presence.',
                'logo' => '1478737270239-2f02b77fc618', 'cover' => '1587825140708-dfaf72ae4b04',
            ],
            [
                'key' => 'ndimbo', 'email' => 'mcndimbo@osep.test', 'first' => 'Reuben', 'last' => 'Ndimbo',
                'business' => 'MC Ndimbo', 'tagline' => 'Engagement & smooth event flow',
                'location' => 'Dar es Salaam, Tanzania', 'years' => 7, 'level' => 'business_verified',
                'featured' => false, 'rating' => 4.65, 'reviews' => 24, 'jobs' => 72, 'response' => 3,
                'website' => 'https://mcndimbo.example', 'instagram' => 'https://instagram.com/mcndimbo',
                'description' => 'Engaging, professional MC focused on guest engagement and a smooth, memorable atmosphere.',
                'logo' => '1587825140708-dfaf72ae4b04', 'cover' => '1478737270239-2f02b77fc618',
            ],
            [
                'key' => 'neema', 'email' => 'mcneema@osep.test', 'first' => 'Neema', 'last' => 'Mkotya',
                'business' => 'MC Neema Mkotya', 'tagline' => 'Energy, charisma & cultural warmth',
                'location' => 'Dar es Salaam, Tanzania', 'years' => 5, 'level' => 'business_verified',
                'featured' => false, 'rating' => 4.6, 'reviews' => 20, 'jobs' => 58, 'response' => 4,
                'website' => 'https://mcneema.example', 'instagram' => 'https://instagram.com/mcneemamkotya',
                'description' => 'Brings energy, charisma and cultural awareness to mixed-culture celebrations.',
                'logo' => '1475721027785-f74eccf877e2', 'cover' => '1524368535928-5b5e00ddc76b',
            ],
            [
                'key' => 'warioba', 'email' => 'mcwarioba@osep.test', 'first' => 'Joseph', 'last' => 'Warioba',
                'business' => 'MC Warioba', 'tagline' => 'Traditional to contemporary, seamlessly',
                'location' => 'Dar es Salaam, Tanzania', 'years' => 8, 'level' => 'business_verified',
                'featured' => false, 'rating' => 4.65, 'reviews' => 26, 'jobs' => 80, 'response' => 3,
                'website' => 'https://mcwarioba.example', 'instagram' => 'https://instagram.com/mcwarioba',
                'description' => 'Transitions seamlessly between traditional and contemporary wedding styles.',
                'logo' => '1524368535928-5b5e00ddc76b', 'cover' => '1475721027785-f74eccf877e2',
            ],
            [
                'key' => 'lukinga', 'email' => 'mclukinga@osep.test', 'first' => 'Frank', 'last' => 'Lukinga',
                'business' => 'MC Lukinga', 'tagline' => 'Entertainment with professional flow',
                'location' => 'Sinza, Dar es Salaam', 'years' => 5, 'level' => 'email_verified',
                'featured' => false, 'rating' => 4.55, 'reviews' => 17, 'jobs' => 44, 'response' => 4,
                'website' => 'https://mclukinga.example', 'instagram' => 'https://instagram.com/mclukinga',
                'description' => 'Sinza-based MC balancing entertainment with professional event-flow management.',
                'logo' => '1511578314322-379afb476865', 'cover' => '1470229538611-16ba8c7ffbd7',
            ],
        ];
    }
}
