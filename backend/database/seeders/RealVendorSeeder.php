<?php

namespace Database\Seeders;

use App\Enums\AccountType;
use App\Enums\UserStatus;
use App\Models\User;
use App\Models\VendorCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Shared base for the "real Tanzanian vendor" category seeders (live bands,
 * makeup, transport, security, tents, printing, …). Each concrete subclass
 * supplies a category slug, a verified Unsplash image pool, the 9 businesses, and
 * the category's storefront shape (services / packages / portfolio). Logins are
 * `<key>@osep.test` / Password123!; real public business details but demo
 * contact + illustrative metrics, matching the earlier standalone seeders.
 * Idempotent (keyed on email); guarded to non-production in DatabaseSeeder.
 */
abstract class RealVendorSeeder extends Seeder
{
    protected const PASSWORD = 'Password123!';

    abstract protected function categorySlug(): string;

    abstract protected function categoryFallbackName(): string;

    /** @return array<int, string> Unsplash photo ids (pre-verified reachable) */
    abstract protected function imagePool(): array;

    /** @return array<int, array<string, mixed>> */
    abstract protected function rows(): array;

    /** @return array<int, string> */
    abstract protected function services(): array;

    /** @return array<int, array{0:string,1:int,2:int,3:string,4:array<int,string>}> [name, minThousands, maxThousands, unit, inclusions] */
    abstract protected function packages(): array;

    /** @return array<int, array{name:string, price:int}> */
    abstract protected function addons(): array;

    /** @return array<int, string> three portfolio titles */
    abstract protected function portfolioTitles(): array;

    /** @return array{0:string,1:string} two media captions */
    abstract protected function portfolioCaptions(): array;

    abstract protected function portfolioFeedback(): string;

    protected function packageTerms(): string
    {
        return '50% deposit to confirm the date. Balance due before the event.';
    }

    public function run(): void
    {
        $category = VendorCategory::where('slug', $this->categorySlug())->first();
        $pool = array_values($this->imagePool());
        $n = max(1, count($pool));

        foreach (array_values($this->rows()) as $i => $r) {
            $user = User::updateOrCreate(
                ['email' => $r['email']],
                [
                    'first_name' => $r['first'], 'last_name' => $r['last'],
                    'phone' => '+2557' . random_int(10_000_000, 99_999_999),
                    'password' => Hash::make(self::PASSWORD),
                    'account_type' => AccountType::Vendor, 'country' => 'Tanzania',
                    'status' => UserStatus::Active, 'email_verified_at' => now(),
                ],
            );
            $user->assignRole('vendor');

            $user->vendorProfile()->updateOrCreate([], [
                'business_name' => $r['business'],
                'tagline' => $r['tagline'],
                'category' => $category?->name ?? $this->categoryFallbackName(),
                'category_id' => $category?->id,
                'description' => $r['description'],
                'years_in_business' => $r['years'],
                'location' => $r['location'],
                'phone' => $user->phone,
                'contact_email' => $r['email'],
                'website' => $r['website'],
                'social_links' => array_filter(['instagram' => $r['instagram'] ?? null, 'facebook' => $r['facebook'] ?? null]),
                'logo_url' => $this->img($pool[$i % $n], 400, 400),
                'cover_image_url' => $this->img($pool[($i + intdiv($n, 2)) % $n], 1200, 400),
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

            $this->storefront($user, $r['business'], $pool);
        }
    }

    protected function img(string $id, int $w, int $h): string
    {
        return "https://images.unsplash.com/photo-{$id}?w={$w}&h={$h}&fit=crop&q=80";
    }

    private function storefront(User $vendor, string $business, array $pool): void
    {
        $categoryId = $vendor->vendorProfile->category_id;
        $n = max(1, count($pool));

        foreach ($this->services() as $i => $name) {
            $vendor->vendorServices()->updateOrCreate(
                ['name' => $name],
                ['category_id' => $categoryId, 'description' => "{$name} by {$business}.", 'is_active' => true, 'sort_order' => $i],
            );
        }

        $addons = $this->addons();
        foreach ($this->packages() as $i => [$name, $minK, $maxK, $unit, $inclusions]) {
            $vendor->vendorPackages()->updateOrCreate(
                ['name' => $name],
                [
                    'description' => "{$name} from {$business}.",
                    'price' => random_int($minK, $maxK) * 1000,
                    'currency' => 'TZS', 'price_unit' => $unit,
                    'inclusions' => $inclusions,
                    'addons' => $addons,
                    'terms' => $this->packageTerms(),
                    'is_active' => true, 'sort_order' => $i,
                ],
            );
        }

        [$capA, $capB] = $this->portfolioCaptions();
        foreach ($this->portfolioTitles() as $i => $title) {
            $vendor->vendorPortfolios()->updateOrCreate(
                ['title' => $title],
                [
                    'description' => "{$title} — {$business}.",
                    'event_type' => $title,
                    'event_date' => now()->subMonths(($i + 1) * 2)->toDateString(),
                    'cover_url' => $this->img($pool[($i * 2) % $n], 800, 600),
                    'media' => [
                        ['type' => 'image', 'url' => $this->img($pool[($i * 2) % $n], 800, 600), 'caption' => $capA],
                        ['type' => 'image', 'url' => $this->img($pool[($i * 2 + 1) % $n], 800, 600), 'caption' => $capB],
                    ],
                    'client_feedback' => $this->portfolioFeedback(),
                    'is_case_study' => $i === 1,
                    'sort_order' => $i,
                ],
            );
        }

        foreach (range(0, 20) as $d) {
            $status = match (true) {
                $d % 7 === 3 => 'fully_booked',
                $d % 11 === 5 => 'on_leave',
                $d % 5 === 2 => 'reserved',
                default => 'available',
            };
            $vendor->vendorAvailability()->updateOrCreate(['date' => now()->addDays($d)->toDateString()], ['status' => $status]);
        }
    }
}
