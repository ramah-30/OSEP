<?php

namespace App\Http\Controllers\Api\V1\Vendor;

use App\Http\Controllers\Api\V1\Vendor\Concerns\ScopesToProvider;
use App\Http\Controllers\Controller;
use App\Http\Resources\ReviewReplyResource;
use App\Http\Resources\ReviewResource;
use App\Models\Review;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * The vendor reading their own reviews and replying to them.
 */
class ReviewController extends Controller
{
    use ApiResponse, ScopesToProvider;

    public function index(Request $request): JsonResponse
    {
        $query = Review::query()->with(['reviewer', 'venue', 'replies.user']);
        $reviews = $this->scopeToProvider($query, $request->user())
            ->latest()->get();

        return $this->success([
            'reviews' => ReviewResource::collection($reviews),
            'summary' => [
                'total' => $reviews->count(),
                'average' => round((float) $reviews->avg('overall_rating'), 2),
                'distribution' => collect(range(5, 1))->mapWithKeys(fn ($star) => [
                    $star => $reviews->filter(fn ($r) => round($r->overall_rating) === (float) $star)->count(),
                ]),
            ],
        ]);
    }

    public function reply(Request $request, Review $review): JsonResponse
    {
        abort_unless($this->ownsRecord($request->user(), $review->vendor_id, $review->venue_id), 404);

        $data = $request->validate([
            'body' => ['required', 'string', 'max:2000'],
        ]);

        $reply = $review->replies()->create([
            'user_id' => $request->user()->id,
            'body' => $data['body'],
        ]);

        return $this->created([
            'reply' => new ReviewReplyResource($reply->load('user')),
        ], 'Reply posted.');
    }
}
