<?php

namespace App\Http\Controllers\Api\V1\Vendor;

use App\Http\Controllers\Api\V1\Vendor\Concerns\ScopesToProvider;
use App\Http\Controllers\Controller;
use App\Http\Resources\BookingRequestResource;
use App\Http\Resources\ContractResource;
use App\Http\Resources\ReviewResource;
use App\Models\BookingRequest;
use App\Models\Contract;
use App\Models\Review;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * The vendor marketplace dashboard: new requests, accepted bookings, revenue and
 * reviews at a glance.
 */
class DashboardController extends Controller
{
    use ApiResponse, ScopesToProvider;

    public function index(Request $request): JsonResponse
    {
        $vendor = $request->user();
        $profile = $vendor->vendorProfile;

        $newRequests = $this->scopeToProvider(
            BookingRequest::query()->with(['planner', 'venue', 'event']), $vendor
        )->whereIn('status', ['pending', 'info_requested'])->latest()->limit(8)->get();

        $accepted = $this->scopeToProvider(
            BookingRequest::query()->with(['planner', 'venue', 'event']), $vendor
        )->where('status', 'accepted')->latest()->limit(8)->get();

        $recentReviews = $this->scopeToProvider(
            Review::query()->with(['reviewer', 'venue']), $vendor
        )->latest()->limit(5)->get();

        $revenue = (float) $this->scopeToProvider(Contract::query(), $vendor)
            ->whereIn('status', ['active', 'completed'])->sum('amount');

        return $this->success([
            'new_requests' => BookingRequestResource::collection($newRequests),
            'accepted_bookings' => BookingRequestResource::collection($accepted),
            'recent_reviews' => ReviewResource::collection($recentReviews),
            'stats' => [
                'new_requests' => $this->scopeToProvider(BookingRequest::query(), $vendor)
                    ->whereIn('status', ['pending', 'info_requested'])->count(),
                'accepted_bookings' => $this->scopeToProvider(BookingRequest::query(), $vendor)
                    ->where('status', 'accepted')->count(),
                'active_contracts' => $this->scopeToProvider(Contract::query(), $vendor)
                    ->whereIn('status', ['signed', 'active'])->count(),
                'revenue' => $revenue,
                'profile_views' => (int) ($profile?->profile_views ?? 0),
                'rating' => $profile?->rating !== null ? (float) $profile->rating : null,
                'reviews_count' => (int) ($profile?->reviews_count ?? 0),
            ],
        ]);
    }
}
