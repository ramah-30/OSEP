<?php

namespace App\Http\Controllers\Api\V1\Marketplace;

use App\Enums\AccountType;
use App\Http\Controllers\Api\V1\Marketplace\Concerns\PaginatesResults;
use App\Http\Controllers\Controller;
use App\Http\Resources\VendorResource;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * The public-facing vendor directory: search, filter, sort and a single
 * storefront. Any authenticated user can browse; write actions live elsewhere.
 * Filtering/sorting join vendor_profiles so the profile columns can be used
 * directly, while the hydrated models stay User instances.
 */
class VendorController extends Controller
{
    use ApiResponse, PaginatesResults;

    public function index(Request $request): JsonResponse
    {
        $query = $this->baseQuery();
        $this->applyFilters($query, $request);
        $this->applySort($query, $request->query('sort'));

        $paginator = $query->paginate((int) $request->integer('per_page', 12))->withQueryString();

        return $this->success([
            'vendors' => VendorResource::collection($paginator->getCollection()),
            'meta' => $this->pageMeta($paginator),
        ]);
    }

    public function show(Request $request, User $vendor): JsonResponse
    {
        abort_unless($vendor->account_type === AccountType::Vendor, 404);
        abort_if((bool) $vendor->vendorProfile?->is_suspended, 404);

        // A lightweight view counter - richer analytics land later.
        $vendor->vendorProfile?->increment('profile_views');

        $vendor->load([
            'vendorProfile.marketplaceCategory',
            'vendorServices' => fn ($q) => $q->where('is_active', true),
            'vendorServices.category',
            'vendorPackages' => fn ($q) => $q->where('is_active', true),
            'vendorPortfolios',
            'vendorReviews' => fn ($q) => $q->where('status', 'published')->with('reviewer', 'replies.user')->latest()->limit(20),
        ]);

        return $this->success(['vendor' => new VendorResource($vendor)]);
    }

    private function baseQuery(): Builder
    {
        return User::query()
            ->select('users.*')
            ->join('vendor_profiles as vp', 'vp.user_id', '=', 'users.id')
            ->where('users.account_type', AccountType::Vendor->value)
            ->where('vp.is_suspended', false)
            ->with('vendorProfile.marketplaceCategory')
            ->withMin(['vendorPackages as price_from' => fn ($q) => $q->where('is_active', true)], 'price');
    }

    private function applyFilters(Builder $query, Request $request): void
    {
        if ($search = $request->query('q')) {
            $query->where(fn (Builder $w) => $w
                ->where('vp.business_name', 'like', "%{$search}%")
                ->orWhere('vp.category', 'like', "%{$search}%")
                ->orWhere('vp.tagline', 'like', "%{$search}%"));
        }

        if ($categoryId = $request->query('category_id')) {
            $query->where('vp.category_id', $categoryId);
        }

        if ($category = $request->query('category')) {
            $query->where('vp.category', $category);
        }

        if ($location = $request->query('location')) {
            $query->where('vp.location', 'like', "%{$location}%");
        }

        if ($request->filled('min_rating')) {
            $query->where('vp.rating', '>=', (float) $request->query('min_rating'));
        }

        if ($request->boolean('verified')) {
            $query->where('vp.verification_level', '!=', 'unverified');
        }

        if ($availability = $request->query('availability')) {
            $query->where('vp.availability_status', $availability);
        }

        if ($request->filled('max_price')) {
            $query->whereExists(fn ($q) => $q->from('vendor_packages')
                ->whereColumn('vendor_packages.vendor_id', 'users.id')
                ->where('is_active', true)
                ->where('price', '<=', (float) $request->query('max_price')));
        }
    }

    private function applySort(Builder $query, ?string $sort): void
    {
        // Featured vendors always float to the top of every ordering.
        $query->orderByDesc('vp.is_featured');

        match ($sort) {
            'price_low' => $query->orderByRaw('price_from is null, price_from asc'),
            'price_high' => $query->orderByDesc('price_from'),
            'reviews' => $query->orderByDesc('vp.reviews_count'),
            'newest' => $query->orderByDesc('users.created_at'),
            default => $query->orderByDesc('vp.rating'),
        };
    }
}
