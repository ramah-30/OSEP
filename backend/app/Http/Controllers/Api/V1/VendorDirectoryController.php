<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\AccountType;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * The registered-vendor directory a planner picks from when assigning vendors.
 * A precursor to the Phase 4 marketplace.
 */
class VendorDirectoryController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $search = $request->query('q');

        $vendors = User::where('account_type', AccountType::Vendor->value)
            ->with('vendorProfile')
            ->when($search, fn ($q) => $q->whereHas('vendorProfile', fn ($p) => $p
                ->where('business_name', 'like', "%{$search}%")
                ->orWhere('category', 'like', "%{$search}%")))
            ->orderBy('first_name')
            ->limit(50)
            ->get()
            ->map(fn ($v) => [
                'id' => $v->id,
                'full_name' => $v->full_name,
                'business_name' => $v->vendorProfile?->business_name,
                'category' => $v->vendorProfile?->category,
                'location' => $v->vendorProfile?->location,
                'avatar_url' => $v->avatar_url ?? $v->vendorProfile?->logo_url,
                'rating' => $v->vendorProfile?->rating !== null ? (float) $v->vendorProfile->rating : null,
                'verification_status' => $v->vendorProfile?->verification_status?->value,
            ]);

        return $this->success(['vendors' => $vendors]);
    }
}
