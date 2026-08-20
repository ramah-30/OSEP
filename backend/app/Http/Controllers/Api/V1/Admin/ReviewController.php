<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\V1\Marketplace\Concerns\RecomputesProviderRating;
use App\Http\Controllers\Controller;
use App\Http\Resources\ReviewResource;
use App\Models\Review;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Admin review moderation: hide abusive reviews or restore them. Toggling
 * visibility refreshes the provider's cached rating.
 */
class ReviewController extends Controller
{
    use ApiResponse, RecomputesProviderRating;

    public function index(Request $request): JsonResponse
    {
        $reviews = Review::query()
            ->when($request->query('status'), fn ($q, $s) => $q->where('status', $s))
            ->with(['reviewer', 'vendor.vendorProfile', 'venue', 'replies.user'])
            ->latest()->limit(100)->get();

        return $this->success([
            'reviews' => ReviewResource::collection($reviews),
        ]);
    }

    public function moderate(Request $request, Review $review): JsonResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(['published', 'pending', 'hidden'])],
        ]);

        $review->update(['status' => $data['status']]);
        $this->recomputeRating($review->vendor_id, $review->venue_id);

        return $this->success([
            'review' => new ReviewResource($review->load('reviewer')),
        ], 'Review moderated.');
    }
}
