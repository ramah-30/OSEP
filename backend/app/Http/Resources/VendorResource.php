<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A marketplace vendor — a vendor-role User plus its storefront profile. Compact
 * fields power the card grid and compare drawer; the heavier relations are only
 * serialised when eager-loaded for the storefront page.
 *
 * @mixin \App\Models\User
 */
class VendorResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $profile = $this->vendorProfile;

        return [
            'id' => $this->id,
            'type' => 'vendor',
            'full_name' => $this->full_name,
            'business_name' => $profile?->business_name ?? $this->full_name,
            'tagline' => $profile?->tagline,
            'category' => $profile?->category,
            'category_id' => $profile?->category_id,
            'description' => $profile?->description,
            'years_in_business' => $profile?->years_in_business,
            'location' => $profile?->location,
            'logo_url' => $profile?->logo_url ?? $this->avatar_url,
            'cover_image_url' => $profile?->cover_image_url,
            'rating' => $profile?->rating !== null ? (float) $profile->rating : null,
            'reviews_count' => (int) ($profile?->reviews_count ?? 0),
            'completed_jobs' => (int) ($profile?->completed_jobs ?? 0),
            'response_time_hours' => $profile?->response_time_hours,
            'availability_status' => $profile?->availability_status?->value,
            'availability_label' => $profile?->availability_status?->label(),
            'verification_level' => $profile?->verification_level?->value,
            'verification_label' => $profile?->verification_level?->label(),
            'is_verified' => (bool) $profile?->verification_level?->isVerified(),
            'is_featured' => (bool) $profile?->is_featured,
            'is_suspended' => (bool) $profile?->is_suspended,
            'price_from' => $this->when(isset($this->price_from), fn () => (float) $this->price_from),

            // Contact — only exposed on the full storefront.
            'phone' => $this->when((bool) $request->route('vendor'), fn () => $profile?->phone),
            'contact_email' => $this->when((bool) $request->route('vendor'), fn () => $profile?->contact_email),
            'website' => $this->when((bool) $request->route('vendor'), fn () => $profile?->website),
            'social_links' => $this->when((bool) $request->route('vendor'), fn () => $profile?->social_links),

            // Rich relations (storefront page).
            'services' => VendorServiceResource::collection($this->whenLoaded('vendorServices')),
            'packages' => VendorPackageResource::collection($this->whenLoaded('vendorPackages')),
            'portfolios' => VendorPortfolioResource::collection($this->whenLoaded('vendorPortfolios')),
            'reviews' => ReviewResource::collection($this->whenLoaded('vendorReviews')),
        ];
    }
}
