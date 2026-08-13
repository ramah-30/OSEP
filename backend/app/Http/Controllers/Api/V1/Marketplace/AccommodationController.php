<?php

namespace App\Http\Controllers\Api\V1\Marketplace;

use App\Http\Controllers\Controller;
use App\Http\Resources\AccommodationResource;
use App\Models\Accommodation;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Browse hotels / accommodation in the marketplace. Open to any authenticated
 * user; the booking write actions live in {@see AccommodationBookingController}.
 */
class AccommodationController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $query = Accommodation::query()->where('is_published', true)
            ->withCount('reviews')
            ->withAvg('reviews', 'rating');

        if ($q = trim((string) $request->query('q'))) {
            $query->where(fn ($sub) => $sub
                ->where('name', 'like', "%{$q}%")
                ->orWhere('city', 'like', "%{$q}%")
                ->orWhere('location', 'like', "%{$q}%")
                ->orWhere('description', 'like', "%{$q}%"));
        }

        if ($city = trim((string) $request->query('city'))) {
            $query->where('city', 'like', "%{$city}%");
        }

        if ($stars = (int) $request->query('min_stars')) {
            $query->where('star_rating', '>=', $stars);
        }

        if ($maxPrice = (float) $request->query('max_price')) {
            $query->where('price_from', '<=', $maxPrice);
        }

        if ($guests = (int) $request->query('guests')) {
            $query->whereHas('roomTypes', fn ($r) => $r->where('is_active', true)->where('capacity', '>=', $guests));
        }

        match ($request->query('sort')) {
            'price_low' => $query->orderBy('price_from'),
            'price_high' => $query->orderByDesc('price_from'),
            'stars' => $query->orderByDesc('star_rating'),
            default => $query->orderByDesc('is_featured')->orderByDesc('star_rating'),
        };

        $accommodations = $query->get();

        return $this->success([
            'accommodations' => AccommodationResource::collection($accommodations),
            'meta' => ['total' => $accommodations->count()],
        ]);
    }

    public function show(Accommodation $accommodation): JsonResponse
    {
        abort_unless($accommodation->is_published, 404);

        $accommodation->increment('profile_views');
        $accommodation->load(['roomTypes' => fn ($r) => $r->where('is_active', true)->orderBy('price_per_night')]);
        $accommodation->loadCount('reviews')->loadAvg('reviews', 'rating');

        $reviews = $accommodation->reviews()
            ->whereNotNull('comment')
            ->with('reviewer:id,first_name,last_name,avatar_url')
            ->limit(10)
            ->get()
            ->map(fn (\App\Models\AccommodationReview $r) => [
                'id' => $r->id,
                'rating' => $r->rating,
                'comment' => $r->comment,
                'reviewer' => [
                    'full_name' => $r->reviewer?->full_name,
                    'avatar_url' => $r->reviewer?->avatar_url,
                ],
                'created_at' => $r->created_at?->toIso8601String(),
            ]);

        return $this->success([
            'accommodation' => new AccommodationResource($accommodation),
            'reviews' => $reviews,
        ]);
    }
}
