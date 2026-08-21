<?php

namespace Database\Seeders;

use App\Enums\AccountType;
use App\Models\Accommodation;
use App\Models\AccommodationReview;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Gives every hotel a set of guest reviews (the lighter single-rating flow),
 * drawn from the client reviewer pool. Ratings/counts are computed live from
 * these on the browse/profile endpoints. Idempotent - one review per (hotel,
 * reviewer). Non-production; runs after the hotels and reviewer pool exist.
 */
class AccommodationReviewsSeeder extends Seeder
{
    /** @var array<int, array{rating:int, comment:string}> */
    private const TEMPLATES = [
        ['rating' => 5, 'comment' => 'A dream stay - impeccable service, beautiful rooms and the most romantic setting. The perfect honeymoon.'],
        ['rating' => 5, 'comment' => 'Stunning property and warm, attentive staff. We honestly did not want to leave.'],
        ['rating' => 5, 'comment' => 'The suite exceeded every expectation - spotless, luxurious and worth every shilling.'],
        ['rating' => 5, 'comment' => 'Breakfast was incredible and the views unforgettable. We will absolutely be back.'],
        ['rating' => 4, 'comment' => 'Lovely hotel and a great location. Check-in took a little while, but the stay was wonderful.'],
        ['rating' => 5, 'comment' => 'The honeymoon package was so thoughtful - rose petals, champagne and a private dinner. Magical.'],
        ['rating' => 4, 'comment' => 'Comfortable rooms and friendly staff. Wi-Fi was a little patchy, but everything else was great.'],
        ['rating' => 5, 'comment' => 'From the pool to the spa, everything was flawless. Genuinely five-star.'],
    ];

    public function run(): void
    {
        $clients = User::where('account_type', AccountType::Client->value)->get();
        if ($clients->isEmpty()) {
            return;
        }

        foreach (Accommodation::all() as $hotel) {
            $pool = $clients->shuffle();
            $count = random_int(5, min(9, $pool->count()));

            foreach ($pool->take($count)->values() as $reviewer) {
                $tpl = self::TEMPLATES[array_rand(self::TEMPLATES)];

                AccommodationReview::updateOrCreate(
                    ['accommodation_id' => $hotel->id, 'reviewer_id' => $reviewer->id],
                    ['rating' => $tpl['rating'], 'comment' => $tpl['comment']],
                );
            }
        }
    }
}
