<?php

namespace App\Http\Controllers\Api\V1\Marketplace;

use App\Http\Controllers\Api\V1\Marketplace\Concerns\RecomputesProviderRating;
use App\Http\Controllers\Api\V1\Marketplace\Concerns\ResolvesProvider;
use App\Http\Controllers\Controller;
use App\Http\Resources\ReviewResource;
use App\Models\MarketplaceVenue;
use App\Models\Notification;
use App\Models\Review;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Planner reviews of vendors and venues. Overall rating is the average of the
 * five category scores; the provider's cached aggregate is refreshed on write.
 */
class ReviewController extends Controller
{
    use ApiResponse, RecomputesProviderRating, ResolvesProvider;

    public function index(Request $request): JsonResponse
    {
        $data = $request->validate([
            'provider_type' => ['required', Rule::in(['vendor', 'venue'])],
            'provider_id' => ['required', 'integer'],
        ]);

        $provider = $this->resolveProvider($data['provider_type'], (int) $data['provider_id']);

        $reviews = Review::query()
            ->where('vendor_id', $provider['vendor_id'])
            ->where('venue_id', $provider['venue_id'])
            ->where('status', 'published')
            ->with(['reviewer', 'replies.user'])
            ->latest()->get();

        return $this->success([
            'reviews' => ReviewResource::collection($reviews),
        ]);
    }

    public function mine(Request $request): JsonResponse
    {
        $reviews = Review::query()
            ->where('reviewer_id', $request->user()->id)
            ->with(['reviewer', 'vendor.vendorProfile', 'venue', 'replies.user'])
            ->latest()->get();

        return $this->success([
            'reviews' => ReviewResource::collection($reviews),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'provider_type' => ['required', Rule::in(['vendor', 'venue'])],
            'provider_id' => ['required', 'integer'],
            'event_id' => ['nullable', 'integer', 'exists:events,id'],
            'contract_id' => ['nullable', 'integer', 'exists:contracts,id'],
            'professionalism' => ['required', 'integer', 'between:1,5'],
            'communication' => ['required', 'integer', 'between:1,5'],
            'quality' => ['required', 'integer', 'between:1,5'],
            'value' => ['required', 'integer', 'between:1,5'],
            'timeliness' => ['required', 'integer', 'between:1,5'],
            'title' => ['nullable', 'string', 'max:150'],
            'comment' => ['nullable', 'string', 'max:3000'],
        ]);

        $provider = $this->resolveProvider($data['provider_type'], (int) $data['provider_id']);

        $scores = [
            $data['professionalism'], $data['communication'], $data['quality'],
            $data['value'], $data['timeliness'],
        ];

        $review = Review::create([
            'reviewer_id' => $request->user()->id,
            ...$provider,
            'event_id' => $data['event_id'] ?? null,
            'contract_id' => $data['contract_id'] ?? null,
            'rating_professionalism' => $data['professionalism'],
            'rating_communication' => $data['communication'],
            'rating_quality' => $data['quality'],
            'rating_value' => $data['value'],
            'rating_timeliness' => $data['timeliness'],
            'overall_rating' => Review::averageOf($scores),
            'title' => $data['title'] ?? null,
            'comment' => $data['comment'] ?? null,
            'status' => 'published',
        ]);

        $this->recomputeRating($provider['vendor_id'], $provider['venue_id']);

        $ownerId = $provider['vendor_id'] ?? MarketplaceVenue::whereKey($provider['venue_id'])->value('owner_id');
        if ($ownerId) {
            Notification::create([
                'user_id' => $ownerId,
                'type' => 'review_received',
                'title' => 'New review',
                'message' => "You received a {$review->overall_rating}★ review.",
                'data' => ['review_id' => $review->id],
            ]);
        }

        return $this->created([
            'review' => new ReviewResource($review->load('reviewer')),
        ], 'Review submitted.');
    }
}
