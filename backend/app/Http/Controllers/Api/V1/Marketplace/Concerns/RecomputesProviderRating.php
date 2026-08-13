<?php

namespace App\Http\Controllers\Api\V1\Marketplace\Concerns;

use App\Models\MarketplaceVenue;
use App\Models\Review;
use App\Models\VendorProfile;

/**
 * Keeps a provider's cached rating / reviews_count in step with its published
 * reviews, so the card grid can sort and display without an aggregate per row.
 */
trait RecomputesProviderRating
{
    protected function recomputeRating(?int $vendorId, ?int $venueId): void
    {
        $query = Review::query()->where('status', 'published');
        $query = $venueId
            ? $query->where('venue_id', $venueId)
            : $query->where('vendor_id', $vendorId);

        $count = (clone $query)->count();
        $average = $count ? round((clone $query)->avg('overall_rating'), 2) : null;

        if ($venueId) {
            MarketplaceVenue::whereKey($venueId)->update([
                'rating' => $average,
                'reviews_count' => $count,
            ]);

            return;
        }

        VendorProfile::where('user_id', $vendorId)->update([
            'rating' => $average,
            'reviews_count' => $count,
        ]);
    }
}
