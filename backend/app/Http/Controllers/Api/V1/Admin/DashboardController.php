<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Enums\AccountType;
use App\Http\Controllers\Controller;
use App\Http\Resources\ReviewResource;
use App\Http\Resources\VendorResource;
use App\Models\BookingRequest;
use App\Models\Contract;
use App\Models\MarketplaceVenue;
use App\Models\Review;
use App\Models\User;
use App\Models\VendorProfile;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;

/**
 * Platform-level marketplace overview and the moderation queues an admin works
 * through.
 */
class DashboardController extends Controller
{
    use ApiResponse;

    public function index(): JsonResponse
    {
        $pendingVendors = User::query()
            ->select('users.*')
            ->join('vendor_profiles as vp', 'vp.user_id', '=', 'users.id')
            ->where('users.account_type', AccountType::Vendor->value)
            ->where('vp.verification_status', 'pending')
            ->with('vendorProfile.marketplaceCategory')
            ->latest('users.created_at')->limit(10)->get();

        $flaggedReviews = Review::query()
            ->where('status', 'pending')
            ->with(['reviewer', 'vendor.vendorProfile', 'venue'])
            ->latest()->limit(10)->get();

        return $this->success([
            'pending_vendors' => VendorResource::collection($pendingVendors),
            'flagged_reviews' => ReviewResource::collection($flaggedReviews),
            'stats' => [
                'vendors' => VendorProfile::count(),
                'pending_vendors' => VendorProfile::where('verification_status', 'pending')->count(),
                'suspended_vendors' => VendorProfile::where('is_suspended', true)->count(),
                'venues' => MarketplaceVenue::count(),
                'suspended_venues' => MarketplaceVenue::where('is_suspended', true)->count(),
                'booking_requests' => BookingRequest::count(),
                'contracts' => Contract::count(),
                'reviews' => Review::count(),
                'flagged_reviews' => Review::where('status', 'pending')->count(),
            ],
        ]);
    }
}
