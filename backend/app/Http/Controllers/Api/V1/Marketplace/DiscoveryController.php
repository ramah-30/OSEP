<?php

namespace App\Http\Controllers\Api\V1\Marketplace;

use App\Enums\AccountType;
use App\Http\Controllers\Controller;
use App\Http\Resources\BookingRequestResource;
use App\Http\Resources\ContractResource;
use App\Http\Resources\MarketplaceVenueResource;
use App\Http\Resources\VendorCategoryResource;
use App\Http\Resources\VendorResource;
use App\Models\BookingRequest;
use App\Models\Contract;
use App\Models\MarketplaceVenue;
use App\Models\User;
use App\Models\VendorCategory;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * The marketplace landing ("Discover") feed and the planner's marketplace
 * dashboard. Read-only; the recommendation queries are deliberately simple rules
 * (rating, featured, category match) that Phase 7 AI can later swap out behind
 * the same response shape.
 */
class DiscoveryController extends Controller
{
    use ApiResponse;

    public function discover(): JsonResponse
    {
        $featuredVendors = $this->vendorQuery()
            ->where('vp.is_featured', true)
            ->orderByDesc('vp.rating')
            ->limit(8)->get();

        $topVendors = $this->vendorQuery()
            ->orderByDesc('vp.rating')
            ->limit(8)->get();

        $featuredVenues = MarketplaceVenue::query()
            ->published()->where('is_featured', true)
            ->orderByDesc('rating')->limit(6)->get();

        $topVenues = MarketplaceVenue::query()
            ->published()->orderByDesc('rating')->limit(6)->get();

        $categories = VendorCategory::query()
            ->where('is_active', true)
            ->withCount(['vendors' => fn ($q) => $q->where('is_suspended', false)])
            ->orderBy('sort_order')->get();

        return $this->success([
            'featured_vendors' => VendorResource::collection($featuredVendors),
            'top_vendors' => VendorResource::collection($topVendors),
            'featured_venues' => MarketplaceVenueResource::collection($featuredVenues),
            'top_venues' => MarketplaceVenueResource::collection($topVenues),
            'categories' => VendorCategoryResource::collection($categories),
            'stats' => [
                'vendors' => User::where('account_type', AccountType::Vendor->value)
                    ->whereHas('vendorProfile', fn ($q) => $q->where('is_suspended', false))->count(),
                'venues' => MarketplaceVenue::where('is_published', true)->where('is_suspended', false)->count(),
                'categories' => $categories->count(),
            ],
        ]);
    }

    public function dashboard(Request $request): JsonResponse
    {
        $planner = $request->user();

        $recommended = $this->vendorQuery()
            ->orderByDesc('vp.is_featured')
            ->orderByDesc('vp.rating')
            ->limit(6)->get();

        $pendingRequests = BookingRequest::query()
            ->where('planner_id', $planner->id)
            ->whereIn('status', ['pending', 'info_requested'])
            ->with(['vendor.vendorProfile', 'venue', 'event'])
            ->latest()->limit(10)->get();

        $activeContracts = Contract::query()
            ->where('planner_id', $planner->id)
            ->whereIn('status', ['signed', 'active'])
            ->with(['vendor.vendorProfile', 'venue', 'event'])
            ->latest()->limit(10)->get();

        $upcoming = BookingRequest::query()
            ->where('planner_id', $planner->id)
            ->where('status', 'accepted')
            ->whereNotNull('event_date')
            ->whereDate('event_date', '>=', now()->toDateString())
            ->with(['vendor.vendorProfile', 'venue', 'event'])
            ->orderBy('event_date')->limit(10)->get();

        return $this->success([
            'recommended_vendors' => VendorResource::collection($recommended),
            'pending_requests' => BookingRequestResource::collection($pendingRequests),
            'active_contracts' => ContractResource::collection($activeContracts),
            'upcoming_bookings' => BookingRequestResource::collection($upcoming),
            'stats' => [
                'pending_requests' => BookingRequest::where('planner_id', $planner->id)
                    ->whereIn('status', ['pending', 'info_requested'])->count(),
                'active_contracts' => Contract::where('planner_id', $planner->id)
                    ->whereIn('status', ['signed', 'active'])->count(),
                'saved' => $planner->savedCollections()->withCount('items')->get()->sum('items_count'),
                'quotations' => \App\Models\Quotation::where('planner_id', $planner->id)
                    ->whereIn('status', ['sent', 'negotiating'])->count(),
            ],
        ]);
    }

    private function vendorQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return User::query()
            ->select('users.*')
            ->join('vendor_profiles as vp', 'vp.user_id', '=', 'users.id')
            ->where('users.account_type', AccountType::Vendor->value)
            ->where('vp.is_suspended', false)
            ->with('vendorProfile.marketplaceCategory')
            ->withMin(['vendorPackages as price_from' => fn ($q) => $q->where('is_active', true)], 'price');
    }
}
