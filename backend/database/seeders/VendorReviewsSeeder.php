<?php

namespace Database\Seeders;

use App\Enums\AccountType;
use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Gives every marketplace vendor a handful of real, published {@see Review}
 * records (five category ratings + comment, some with a vendor reply) from
 * planner/client reviewers, then recomputes each vendor's `rating` and
 * `reviews_count` from the actual reviews so the card aggregate and the
 * storefront review list agree. Idempotent - one review per reviewer per vendor
 * (updateOrCreate). Non-production, runs after all vendor seeders.
 */
class VendorReviewsSeeder extends Seeder
{
    /** @var array<int, array{scores: array<int,int>, title: string, comment: string}> */
    private const TEMPLATES = [
        ['scores' => [5, 5, 5, 5, 5], 'title' => 'Absolutely brilliant', 'comment' => 'From first contact to the big day, everything was seamless. Truly professional and a pleasure to work with.'],
        ['scores' => [5, 5, 5, 4, 5], 'title' => 'Exceeded our expectations', 'comment' => 'They went above and beyond - attentive, creative and completely reliable. Our guests were so impressed.'],
        ['scores' => [5, 4, 5, 5, 5], 'title' => 'Highly recommend', 'comment' => 'Communication was excellent and the quality spoke for itself. We would book again without hesitation.'],
        ['scores' => [5, 5, 4, 5, 5], 'title' => 'Made our day stress-free', 'comment' => 'Calm, organised and genuinely lovely people. They handled every detail so we could just enjoy the day.'],
        ['scores' => [5, 5, 5, 4, 4], 'title' => 'Outstanding quality', 'comment' => 'The end result was even better than we imagined. Worth every shilling.'],
        ['scores' => [4, 5, 5, 4, 5], 'title' => 'Professional and punctual', 'comment' => 'On time, well-prepared and easy to work with throughout. Exactly what you want on the day.'],
        ['scores' => [5, 5, 4, 4, 5], 'title' => 'A joy to work with', 'comment' => 'Friendly, flexible and full of great ideas. They really listened to what we wanted.'],
        ['scores' => [5, 4, 5, 5, 4], 'title' => 'Five stars', 'comment' => 'Flawless delivery and wonderful service. Our families are still talking about it.'],
        ['scores' => [4, 4, 5, 4, 4], 'title' => 'Great, with minor hiccups', 'comment' => 'Really good overall - a couple of small timing issues, but nothing that affected the day.'],
        ['scores' => [4, 4, 4, 5, 4], 'title' => 'Very good value', 'comment' => 'Solid, dependable service and fair pricing. A slightly slow start on replies, but they delivered.'],
    ];

    private const REPLIES = [
        'Thank you so much for the kind words - it was a pleasure working with you!',
        'We truly appreciate this review. Wishing you all the very best!',
        'Thank you! Moments like these are exactly why we love what we do.',
    ];

    public function run(): void
    {
        $reviewers = User::whereIn('account_type', [AccountType::EventPlanner->value, AccountType::Client->value])->get();
        if ($reviewers->isEmpty()) {
            return;
        }

        $vendors = User::where('account_type', AccountType::Vendor->value)
            ->whereHas('vendorProfile')
            ->with('vendorProfile')
            ->get();

        foreach ($vendors as $vendor) {
            $pool = $reviewers->shuffle();
            $count = random_int(4, min(8, $pool->count()));

            foreach ($pool->take($count)->values() as $j => $reviewer) {
                $tpl = self::TEMPLATES[array_rand(self::TEMPLATES)];
                [$p, $c, $q, $v, $t] = $tpl['scores'];

                $review = Review::updateOrCreate(
                    ['reviewer_id' => $reviewer->id, 'vendor_id' => $vendor->id],
                    [
                        'rating_professionalism' => $p, 'rating_communication' => $c, 'rating_quality' => $q,
                        'rating_value' => $v, 'rating_timeliness' => $t,
                        'overall_rating' => Review::averageOf($tpl['scores']),
                        'title' => $tpl['title'], 'comment' => $tpl['comment'],
                        'status' => 'published',
                    ],
                );

                // A vendor reply on the first review, so storefronts show the thread.
                if ($j === 0) {
                    $review->replies()->updateOrCreate(
                        ['user_id' => $vendor->id],
                        ['body' => self::REPLIES[array_rand(self::REPLIES)]],
                    );
                }
            }

            // Recompute the card aggregates from the actual published reviews.
            $published = Review::where('vendor_id', $vendor->id)->where('status', 'published');
            $vendor->vendorProfile->update([
                'reviews_count' => (int) $published->count(),
                'rating' => round((float) $published->avg('overall_rating'), 2),
            ]);
        }
    }
}
