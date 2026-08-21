<?php

namespace App\Http\Controllers\Api\V1\Vendor;

use App\Http\Controllers\Controller;
use App\Http\Resources\VendorResource;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * A vendor managing their own marketplace storefront (the vendor_profiles row
 * plus its services/packages/portfolio). Everything is scoped to the signed-in
 * vendor - there is no id in the route.
 */
class StorefrontController extends Controller
{
    use ApiResponse;

    public function show(Request $request): JsonResponse
    {
        $vendor = $request->user()->load([
            'vendorProfile.marketplaceCategory',
            'vendorServices.category',
            'vendorPackages',
            'vendorPortfolios',
        ]);

        return $this->success(['vendor' => new VendorResource($vendor)]);
    }

    public function update(Request $request): JsonResponse
    {
        $data = $request->validate([
            'business_name' => ['nullable', 'string', 'max:150'],
            'tagline' => ['nullable', 'string', 'max:200'],
            'category' => ['nullable', 'string', 'max:100'],
            'category_id' => ['nullable', 'integer', 'exists:vendor_categories,id'],
            'description' => ['nullable', 'string', 'max:5000'],
            'years_in_business' => ['nullable', 'integer', 'min:0', 'max:200'],
            'location' => ['nullable', 'string', 'max:150'],
            'phone' => ['nullable', 'string', 'max:40'],
            'contact_email' => ['nullable', 'email', 'max:150'],
            'website' => ['nullable', 'string', 'max:200'],
            'social_links' => ['nullable', 'array'],
            'logo_url' => ['nullable', 'string', 'max:500'],
            'cover_image_url' => ['nullable', 'string', 'max:500'],
            'availability_status' => ['nullable', 'string', 'max:30'],
            'response_time_hours' => ['nullable', 'integer', 'min:0'],
        ]);

        $profile = $request->user()->vendorProfile()->updateOrCreate([], $data);

        return $this->success([
            'vendor' => new VendorResource($request->user()->fresh(['vendorProfile.marketplaceCategory'])),
        ], 'Storefront updated.');
    }

    /**
     * The self-serve "email verified" tier bump - the first step of the
     * verification ladder. Higher tiers are granted by an admin.
     */
    public function requestVerification(Request $request): JsonResponse
    {
        $profile = $request->user()->vendorProfile()->firstOrCreate([]);

        if ($profile->verification_level->value === 'unverified') {
            $profile->update(['verification_level' => 'email_verified']);
        }

        return $this->success([
            'vendor' => new VendorResource($request->user()->fresh('vendorProfile')),
        ], 'Email verification confirmed.');
    }
}
