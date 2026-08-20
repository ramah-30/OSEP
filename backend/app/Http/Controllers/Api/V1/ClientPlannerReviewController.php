<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Notification;
use App\Models\PlannerReview;
use App\Support\PlannerReputation;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Client-side planner reviews: a client rates the planner who ran one of their
 * events. One review per planner (re-submitting updates it), no reply/moderation.
 */
class ClientPlannerReviewController extends Controller
{
    use ApiResponse;

    /** The client's own submitted planner reviews, keyed by planner for easy lookup. */
    public function index(Request $request): JsonResponse
    {
        $reviews = PlannerReview::where('reviewer_id', $request->user()->id)
            ->latest()
            ->get()
            ->map(fn (PlannerReview $r) => [
                'id' => $r->id,
                'planner_id' => $r->planner_id,
                'event_id' => $r->event_id,
                'rating' => $r->rating,
                'comment' => $r->comment,
                'updated_at' => $r->updated_at,
            ]);

        return $this->success(['reviews' => $reviews]);
    }

    /** Submit (or update) a review of the planner behind one of the client's events. */
    public function store(Request $request): JsonResponse
    {
        $client = $request->user();

        $data = $request->validate([
            'event_id' => ['required', 'integer', 'exists:events,id'],
            'rating' => ['required', 'integer', 'between:1,5'],
            'comment' => ['nullable', 'string', 'max:2000'],
        ]);

        $event = Event::find($data['event_id']);

        // Only the event's own client may review it, and only once it has a planner.
        if (! $event || $event->client_id !== $client->id) {
            return $this->error('Event not found.', null, 404);
        }

        if (! $event->planner_id) {
            return $this->error('This event has no planner to review yet.', null, 422);
        }

        $review = PlannerReview::updateOrCreate(
            ['planner_id' => $event->planner_id, 'reviewer_id' => $client->id],
            ['event_id' => $event->id, 'rating' => $data['rating'], 'comment' => $data['comment'] ?? null],
        );

        // Let the planner know they were reviewed (fresh review only, not silent edits).
        if ($review->wasRecentlyCreated) {
            Notification::create([
                'user_id' => $event->planner_id,
                'type' => 'new_review',
                'title' => 'New client review',
                'message' => "{$client->full_name} left you a {$review->rating}-star review.",
                'data' => ['review_id' => $review->id, 'event_id' => $event->id],
            ]);
        }

        return $this->success([
            'review' => [
                'id' => $review->id,
                'planner_id' => $review->planner_id,
                'event_id' => $review->event_id,
                'rating' => $review->rating,
                'comment' => $review->comment,
            ],
            'reputation' => PlannerReputation::summary($event->planner),
        ], 'Thanks — your review has been saved.');
    }
}
