<?php

namespace App\Http\Controllers\Api\V1\Marketplace;

use App\Http\Controllers\Api\V1\Marketplace\Concerns\PaginatesResults;
use App\Http\Controllers\Controller;
use App\Http\Resources\MarketplaceVenueResource;
use App\Models\MarketplaceVenue;
use App\Traits\ApiResponse;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * The public-facing venue directory: search, filter, sort and a single listing.
 */
class VenueController extends Controller
{
    use ApiResponse, PaginatesResults;

    public function index(Request $request): JsonResponse
    {
        $query = MarketplaceVenue::query()
            ->where('is_published', true)
            ->where('is_suspended', false);

        $this->applyFilters($query, $request);
        $this->applySort($query, $request->query('sort'));

        $paginator = $query->paginate((int) $request->integer('per_page', 12))->withQueryString();

        return $this->success([
            'venues' => MarketplaceVenueResource::collection($paginator->getCollection()),
            'meta' => $this->pageMeta($paginator),
        ]);
    }

    public function show(MarketplaceVenue $venue): JsonResponse
    {
        abort_if($venue->is_suspended || ! $venue->is_published, 404);

        $venue->increment('profile_views');
        $venue->load([
            'images',
            'reviews' => fn ($q) => $q->where('status', 'published')->with('reviewer', 'replies.user')->latest()->limit(20),
        ]);

        return $this->success(['venue' => new MarketplaceVenueResource($venue)]);
    }

    private function applyFilters(Builder $query, Request $request): void
    {
        if ($search = $request->query('q')) {
            $query->where(fn (Builder $w) => $w
                ->where('name', 'like', "%{$search}%")
                ->orWhere('venue_type', 'like', "%{$search}%")
                ->orWhere('location', 'like', "%{$search}%"));
        }

        if ($location = $request->query('location')) {
            $query->where('location', 'like', "%{$location}%");
        }

        if ($setting = $request->query('setting')) {
            $query->where('setting', $setting);
        }

        if ($request->filled('min_capacity')) {
            $query->where('capacity', '>=', (int) $request->query('min_capacity'));
        }

        if ($request->filled('max_capacity')) {
            $query->where(fn (Builder $w) => $w
                ->whereNull('min_capacity')
                ->orWhere('min_capacity', '<=', (int) $request->query('max_capacity')));
        }

        if ($request->boolean('parking')) {
            $query->where('parking_available', true);
        }

        if ($request->filled('min_rating')) {
            $query->where('rating', '>=', (float) $request->query('min_rating'));
        }

        if ($request->boolean('verified')) {
            $query->where('verification_level', '!=', 'unverified');
        }

        if ($request->filled('max_price')) {
            $query->where('price', '<=', (float) $request->query('max_price'));
        }
    }

    private function applySort(Builder $query, ?string $sort): void
    {
        $query->orderByDesc('is_featured');

        match ($sort) {
            'price_low' => $query->orderByRaw('price is null, price asc'),
            'price_high' => $query->orderByDesc('price'),
            'capacity' => $query->orderByDesc('capacity'),
            'reviews' => $query->orderByDesc('reviews_count'),
            'newest' => $query->orderByDesc('created_at'),
            default => $query->orderByDesc('rating'),
        };
    }
}
