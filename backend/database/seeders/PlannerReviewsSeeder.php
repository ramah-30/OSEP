<?php

namespace Database\Seeders;

use App\Enums\AccountType;
use App\Models\PlannerReview;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Gives every planner a fuller set of client reviews (the lighter single-rating
 * PlannerReview flow), drawing from the client reviewer pool. PlannerReputation
 * computes rating/badge live, so no aggregate to recompute. Idempotent — one
 * review per (planner, reviewer) via updateOrCreate. Non-production; runs after
 * DemoReviewersSeeder.
 */
class PlannerReviewsSeeder extends Seeder
{
    /** @var array<int, array{rating:int, comment:string}> */
    private const TEMPLATES = [
        ['rating' => 5, 'comment' => 'Our planner was incredible — every detail handled, zero stress on the day. Would book again in a heartbeat.'],
        ['rating' => 5, 'comment' => 'Professional, responsive and genuinely lovely to work with. They turned our vision into reality.'],
        ['rating' => 5, 'comment' => 'Kept everything on budget and on schedule. Flawless from the first meeting to the send-off.'],
        ['rating' => 5, 'comment' => 'Creative, calm under pressure and wonderfully organised. Our guests are still talking about it.'],
        ['rating' => 4, 'comment' => 'From start to finish we felt completely looked after. A couple of small timing hiccups, but a beautiful day.'],
        ['rating' => 5, 'comment' => 'Communication was excellent throughout and the day ran perfectly. Highly recommend.'],
        ['rating' => 4, 'comment' => 'Great value and dependable. Responses were a little slow early on, but everything came together.'],
        ['rating' => 5, 'comment' => 'They made the whole process feel easy and fun. Truly a safe pair of hands.'],
    ];

    public function run(): void
    {
        $clients = User::where('account_type', AccountType::Client->value)->get();
        if ($clients->isEmpty()) {
            return;
        }

        $planners = User::where('account_type', AccountType::EventPlanner->value)
            ->whereHas('plannerProfile')
            ->get();

        foreach ($planners as $planner) {
            $pool = $clients->shuffle();
            $count = random_int(5, min(9, $pool->count()));

            foreach ($pool->take($count)->values() as $reviewer) {
                $tpl = self::TEMPLATES[array_rand(self::TEMPLATES)];

                PlannerReview::updateOrCreate(
                    ['planner_id' => $planner->id, 'reviewer_id' => $reviewer->id],
                    ['event_id' => null, 'rating' => $tpl['rating'], 'comment' => $tpl['comment']],
                );
            }
        }
    }
}
