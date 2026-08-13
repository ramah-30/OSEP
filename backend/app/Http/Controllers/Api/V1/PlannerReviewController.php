<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\PlannerReview;
use App\Support\PlannerReputation;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * A planner's own reviews view: their auto-earned trust badge, aggregate rating,
 * the 1–5 star distribution, and the list of reviews clients have left them.
 */
class PlannerReviewController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $planner = $request->user();

        $reviews = PlannerReview::where('planner_id', $planner->id)
            ->with(['reviewer:id,first_name,last_name,avatar_url', 'event:id,title'])
            ->latest()
            ->get();

        // 1–5 star distribution for the little histogram on the page.
        $distribution = collect([5, 4, 3, 2, 1])->mapWithKeys(
            fn ($star) => [$star => $reviews->where('rating', $star)->count()],
        );

        return $this->success([
            'reputation' => PlannerReputation::summary($planner),
            'distribution' => $distribution,
            'reviews' => $reviews->map(fn (PlannerReview $r) => [
                'id' => $r->id,
                'rating' => $r->rating,
                'comment' => $r->comment,
                'event_title' => $r->event?->title,
                'reviewer' => [
                    'full_name' => $r->reviewer?->full_name,
                    'avatar_url' => $r->reviewer?->avatar_url,
                ],
                'created_at' => $r->created_at,
            ]),
        ]);
    }
}
