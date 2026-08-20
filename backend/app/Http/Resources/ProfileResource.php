<?php

namespace App\Http\Resources;

use App\Enums\AccountType;
use App\Models\ClientProfile;
use App\Models\PlannerProfile;
use App\Models\VendorProfile;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A single profile shape whose fields vary by account type. The account type is
 * always included so the SPA can pick the right form without a second lookup.
 *
 * @property-read \App\Models\User $resource
 */
class ProfileResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $user = $this->resource;
        $profile = $user->profile();

        $base = [
            'account_type' => $user->account_type->value,
            'avatar_url' => $user->avatar_url,
        ];

        return match ($user->account_type) {
            AccountType::EventPlanner => [...$base, ...$this->planner($profile)],
            AccountType::Client => [...$base, ...$this->client($profile)],
            AccountType::Vendor => [...$base, ...$this->vendor($profile)],
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function planner(?PlannerProfile $p): array
    {
        return [
            'company_name' => $p?->company_name,
            'experience_years' => $p?->experience_years,
            'specialization' => $p?->specialization,
            'bio' => $p?->bio,
            'location' => $p?->location,
            'website' => $p?->website,
            'booking_slug' => $p?->booking_slug,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function client(?ClientProfile $p): array
    {
        return [
            'preferred_event_types' => $p?->preferred_event_types ?? [],
            'communication_preference' => $p?->communication_preference,
            'location' => $p?->location,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function vendor(?VendorProfile $p): array
    {
        return [
            'business_name' => $p?->business_name,
            'category' => $p?->marketplaceCategory?->name,
            'description' => $p?->description,
            'location' => $p?->location,
            'phone' => $p?->phone,
            'website' => $p?->website,
            'social_links' => $p?->social_links ?? [],
            'logo_url' => $p?->logo_url,
            'verification_status' => $p?->verification_status?->value,
            'verification_status_label' => $p?->verification_status?->label(),
            'availability_status' => $p?->availability_status?->value,
            'availability_status_label' => $p?->availability_status?->label(),
        ];
    }
}
