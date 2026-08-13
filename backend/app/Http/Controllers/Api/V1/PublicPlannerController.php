<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\AccountType;
use App\Http\Controllers\Controller;
use App\Models\PlannerProfile;
use App\Models\PlannerReview;
use App\Models\User;
use App\Support\PlannerReputation;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Surfaces planner data for the booking flow.
 * index() is authenticated (client); show() is public (throttled).
 */
class PublicPlannerController extends Controller
{
    use ApiResponse;

    /** Browseable planner list for authenticated clients — returns every planner in the system. */
    public function index(Request $request): JsonResponse
    {
        $q = $request->query('q');

        $planners = User::where('account_type', AccountType::EventPlanner)
            ->whereHas('plannerProfile')
            ->with('plannerProfile')
            ->when($q, function ($query) use ($q) {
                $query->where(function ($sub) use ($q) {
                    $sub->where('full_name', 'like', "%{$q}%")
                        ->orWhereHas('plannerProfile', fn ($p) =>
                            $p->where('company_name', 'like', "%{$q}%")
                              ->orWhere('specialization', 'like', "%{$q}%")
                              ->orWhere('location', 'like', "%{$q}%")
                        );
                });
            })
            ->get()
            ->map(function (User $u) {
                $p = $u->plannerProfile;
                $reputation = PlannerReputation::summary($u);
                return [
                    'id'               => $u->id,
                    'full_name'        => $u->full_name,
                    'avatar_url'       => $u->avatar_url,
                    'company_name'     => $p?->company_name,
                    'specialization'   => $p?->specialization,
                    'bio'              => $p?->bio,
                    'location'         => $p?->location,
                    'experience_years' => $p?->experience_years,
                    'booking_slug'     => $p?->booking_slug,
                    'rating'           => $reputation['rating'],
                    'reviews_count'    => $reputation['reviews_count'],
                    'badge'            => $reputation['badge'],
                ];
            });

        return $this->success(['planners' => $planners]);
    }

    public function show(string $slug): JsonResponse
    {
        $profile = PlannerProfile::where('booking_slug', $slug)
            ->with('user')
            ->first();

        if (! $profile) {
            return $this->error('Planner not found.', 404);
        }

        $user = $profile->user;
        $reputation = PlannerReputation::summary($user);

        $reviews = PlannerReview::where('planner_id', $user->id)
            ->whereNotNull('comment')
            ->with('reviewer:id,first_name,last_name,avatar_url')
            ->latest()
            ->limit(8)
            ->get()
            ->map(fn (PlannerReview $r) => [
                'id'         => $r->id,
                'rating'     => $r->rating,
                'comment'    => $r->comment,
                'reviewer'   => [
                    'full_name'  => $r->reviewer?->full_name,
                    'avatar_url' => $r->reviewer?->avatar_url,
                ],
                'created_at' => $r->created_at,
            ]);

        return $this->success([
            'planner' => [
                'id'               => $user->id,
                'full_name'        => $user->full_name,
                'avatar_url'       => $user->avatar_url,
                'company_name'     => $profile->company_name,
                'specialization'   => $profile->specialization,
                'bio'              => $profile->bio,
                'location'         => $profile->location,
                'experience_years' => $profile->experience_years,
                'booking_slug'     => $profile->booking_slug,
                'rating'           => $reputation['rating'],
                'reviews_count'    => $reputation['reviews_count'],
                'completed_events' => $reputation['completed_events'],
                'badge'            => $reputation['badge'],
            ],
            'reviews' => $reviews,
        ]);
    }
}
