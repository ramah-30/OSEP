<?php

namespace Database\Seeders;

use App\Enums\AccountType;
use App\Enums\UserStatus;
use App\Models\PlannerReview;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * A directory of extra planner demo accounts so "Find a Planner", the public
 * booking pages and the client concierge's recommendations show a realistic
 * spread of choices. Each is a full login (@osep.test / Password123!) with a
 * complete profile; a handful carry client reviews so ratings and auto-earned
 * badges vary. Idempotent — keyed on email / profile — and safe to re-run.
 *
 * Guarded to non-production in DatabaseSeeder (runs after DemoSeeder).
 */
class PlannerDirectorySeeder extends Seeder
{
    private const PASSWORD = 'Password123!';

    public function run(): void
    {
        $reviewers = User::whereIn('email', ['client@osep.test', 'amina@osep.test', 'daniel@osep.test'])
            ->get()
            ->values();

        foreach ($this->planners() as $data) {
            $planner = User::updateOrCreate(
                ['email' => $data['email']],
                [
                    'first_name' => $data['first_name'],
                    'last_name' => $data['last_name'],
                    'phone' => $data['phone'],
                    'password' => Hash::make(self::PASSWORD),
                    'account_type' => AccountType::EventPlanner,
                    'country' => 'Tanzania',
                    'status' => UserStatus::Active,
                    'email_verified_at' => now(),
                ],
            );
            $planner->assignRole(AccountType::EventPlanner->value);

            $planner->plannerProfile()->updateOrCreate([], [
                'company_name' => $data['company_name'],
                'experience_years' => $data['experience_years'],
                'specialization' => $data['specialization'],
                'bio' => $data['bio'],
                'location' => $data['location'],
                'website' => $data['website'],
                'booking_slug' => Str::slug($data['company_name']),
            ]);

            $this->seedReviews($planner, $reviewers, $data['reviews'] ?? []);
        }
    }

    /**
     * @param  \Illuminate\Support\Collection<int, User>  $reviewers
     * @param  array<int, int>  $ratings
     */
    private function seedReviews(User $planner, $reviewers, array $ratings): void
    {
        foreach ($ratings as $i => $rating) {
            $reviewer = $reviewers[$i] ?? null;
            if (! $reviewer) {
                break;
            }

            PlannerReview::updateOrCreate(
                ['planner_id' => $planner->id, 'reviewer_id' => $reviewer->id],
                ['event_id' => null, 'rating' => $rating, 'comment' => $this->comment($rating)],
            );
        }
    }

    private function comment(int $rating): string
    {
        return match (true) {
            $rating >= 5 => 'Absolutely outstanding — organised, calm under pressure and a joy to work with. Highly recommend.',
            $rating === 4 => 'Great experience overall. Professional, responsive and the day came together beautifully.',
            default => 'Solid, dependable planning. A few small things to iron out, but the event went well.',
        };
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function planners(): array
    {
        return [
            [
                'email' => 'grace.mushi@osep.test', 'first_name' => 'Grace', 'last_name' => 'Mushi',
                'phone' => '+255 754 100 201', 'company_name' => 'Tanzanite Weddings & Events',
                'experience_years' => 12, 'specialization' => 'Luxury weddings & private celebrations',
                'location' => 'Arusha, Tanzania', 'website' => 'https://tanzaniteweddings.example',
                'bio' => 'Grace crafts elegant, deeply personal weddings across northern Tanzania, blending Kilimanjaro backdrops with flawless five-star service. Twelve years and 300+ celebrations later, her studio is a byword for calm, considered luxury.',
                'reviews' => [5, 5, 4],
            ],
            [
                'email' => 'david.kimaro@osep.test', 'first_name' => 'David', 'last_name' => 'Kimaro',
                'phone' => '+255 754 100 202', 'company_name' => 'Summit Corporate Events',
                'experience_years' => 9, 'specialization' => 'Corporate events, conferences & galas',
                'location' => 'Dar es Salaam, Tanzania', 'website' => 'https://summitevents.example',
                'bio' => 'David runs large-scale corporate programmes — product launches, AGMs and awards galas — with military precision. Fortune-500 clients trust Summit for run-of-show discipline, AV mastery and on-budget delivery.',
                'reviews' => [5, 4],
            ],
            [
                'email' => 'neema.laizer@osep.test', 'first_name' => 'Neema', 'last_name' => 'Laizer',
                'phone' => '+255 754 100 203', 'company_name' => 'Serengeti Celebrations',
                'experience_years' => 7, 'specialization' => 'Destination weddings & safari experiences',
                'location' => 'Arusha, Tanzania', 'website' => 'https://serengeticelebrations.example',
                'bio' => 'Neema specialises in unforgettable destination weddings — bush ceremonies, crater-rim receptions and safari honeymoons. She handles logistics end to end so couples simply arrive and celebrate.',
                'reviews' => [5, 5, 5],
            ],
            [
                'email' => 'joseph.mrema@osep.test', 'first_name' => 'Joseph', 'last_name' => 'Mrema',
                'phone' => '+255 754 100 204', 'company_name' => 'Coastal Occasions',
                'experience_years' => 6, 'specialization' => 'Beach weddings & coastal events',
                'location' => 'Zanzibar, Tanzania', 'website' => 'https://coastaloccasions.example',
                'bio' => 'From barefoot beach ceremonies to sunset dhow receptions, Joseph brings the Zanzibar coastline to life. His island network of venues, caterers and musicians makes seamless seaside events effortless.',
                'reviews' => [4, 5],
            ],
            [
                'email' => 'amani.kessy@osep.test', 'first_name' => 'Amani', 'last_name' => 'Kessy',
                'phone' => '+255 754 100 205', 'company_name' => 'Bloom Events Co.',
                'experience_years' => 4, 'specialization' => 'Birthdays, showers & social events',
                'location' => 'Mwanza, Tanzania', 'website' => 'https://bloomevents.example',
                'bio' => 'Amani brings colour and joy to milestone celebrations — birthdays, baby showers and anniversaries — with playful themes, standout décor and a warm, hands-on style clients adore.',
                'reviews' => [4],
            ],
            [
                'email' => 'faith.mollel@osep.test', 'first_name' => 'Faith', 'last_name' => 'Mollel',
                'phone' => '+255 754 100 206', 'company_name' => 'Regal Affairs',
                'experience_years' => 10, 'specialization' => 'Luxury & cultural weddings',
                'location' => 'Moshi, Tanzania', 'website' => 'https://regalaffairs.example',
                'bio' => 'Faith honours tradition while delivering modern luxury — from send-off ceremonies to grand receptions. A decade of cultural weddings has made Regal Affairs the name families pass down.',
                'reviews' => [5, 4, 5],
            ],
            [
                'email' => 'baraka.shirima@osep.test', 'first_name' => 'Baraka', 'last_name' => 'Shirima',
                'phone' => '+255 754 100 207', 'company_name' => 'Peak Productions',
                'experience_years' => 8, 'specialization' => 'Concerts, festivals & large-scale productions',
                'location' => 'Dar es Salaam, Tanzania', 'website' => 'https://peakproductions.example',
                'bio' => 'Baraka produces concerts and festivals for thousands — staging, security, ticketing and artist liaison under one roof. If it needs a stage and a crowd, Peak Productions makes it happen safely and on time.',
                'reviews' => [4],
            ],
            [
                'email' => 'zainab.juma@osep.test', 'first_name' => 'Zainab', 'last_name' => 'Juma',
                'phone' => '+255 754 100 208', 'company_name' => 'Pearl Planning',
                'experience_years' => 5, 'specialization' => 'Weddings & anniversaries',
                'location' => 'Zanzibar, Tanzania', 'website' => 'https://pearlplanning.example',
                'bio' => 'Zainab designs intimate, detail-rich weddings and anniversary celebrations with an island heart. Her boutique approach means every couple gets her full, personal attention from first call to farewell.',
                'reviews' => [5, 5],
            ],
            [
                'email' => 'emmanuel.massawe@osep.test', 'first_name' => 'Emmanuel', 'last_name' => 'Massawe',
                'phone' => '+255 754 100 209', 'company_name' => 'Kilimanjaro Events',
                'experience_years' => 11, 'specialization' => 'Corporate events & exhibitions',
                'location' => 'Moshi, Tanzania', 'website' => 'https://kilimanjaroevents.example',
                'bio' => 'Emmanuel delivers polished conferences, trade exhibitions and corporate retreats across the northern circuit. Eleven years of stakeholder wrangling and logistics have made Kilimanjaro Events a safe pair of hands for any brief.',
                'reviews' => [5, 4, 4],
            ],
        ];
    }
}
