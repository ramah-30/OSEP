<?php

namespace App\Support;

use App\Enums\EventStatus;
use App\Enums\PlannerBadge;
use App\Models\Event;
use App\Models\PlannerReview;
use App\Models\User;

/**
 * Rolls a planner's reviews and activity up into the numbers shown on their
 * profile, the client directory and the public booking page: an average rating,
 * a review count, completed-event count and the auto-earned trust badge.
 */
class PlannerReputation
{
    /**
     * @return array{rating: float, reviews_count: int, completed_events: int, badge: array{key: string, label: string, tier: int, verified: bool}}
     */
    public static function summary(User $planner): array
    {
        $reviewsCount = PlannerReview::where('planner_id', $planner->id)->count();
        $rating = $reviewsCount > 0
            ? round((float) PlannerReview::where('planner_id', $planner->id)->avg('rating'), 2)
            : 0.0;

        $completedEvents = Event::where('planner_id', $planner->id)
            ->where('status', EventStatus::Completed->value)
            ->count();

        $experienceYears = (int) ($planner->plannerProfile?->experience_years ?? 0);

        $badge = PlannerBadge::derive(
            $planner->email_verified_at !== null,
            $experienceYears,
            $completedEvents,
            $rating,
            $reviewsCount,
        );

        return [
            'rating' => $rating,
            'reviews_count' => $reviewsCount,
            'completed_events' => $completedEvents,
            'badge' => [
                'key' => $badge->value,
                'label' => $badge->label(),
                'tier' => $badge->weight(),
                'verified' => $badge->isVerified(),
            ],
        ];
    }
}
