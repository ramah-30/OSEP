<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Enums\AccountType;
use App\Http\Controllers\Controller;
use App\Http\Resources\ReviewResource;
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
        $flaggedReviews = Review::query()
            ->where('status', 'pending')
            ->with(['reviewer', 'vendor.vendorProfile', 'venue'])
            ->latest()->limit(10)->get();

        return $this->success([
            'flagged_reviews' => ReviewResource::collection($flaggedReviews),
            'stats' => [
                'planners' => User::where('account_type', AccountType::EventPlanner->value)->count(),
                'clients' => User::where('account_type', AccountType::Client->value)->count(),
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
