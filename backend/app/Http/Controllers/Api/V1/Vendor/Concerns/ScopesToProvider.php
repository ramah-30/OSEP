<?php

namespace App\Http\Controllers\Api\V1\Vendor\Concerns;

use App\Models\MarketplaceVenue;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * Constrains a query on any record carrying the vendor_id / venue_id provider
 * pair to the rows a given vendor owns — either directly (vendor_id) or through
 * a venue they own.
 */
trait ScopesToProvider
{
    /**
     * @template TModel of \Illuminate\Database\Eloquent\Model
     *
     * @param  Builder<TModel>  $query
     * @return Builder<TModel>
     */
    protected function scopeToProvider(Builder $query, User $vendor): Builder
    {
        return $query->where(fn (Builder $q) => $q
            ->where('vendor_id', $vendor->id)
            ->orWhereIn('venue_id', MarketplaceVenue::where('owner_id', $vendor->id)->select('id')));
    }

    /** Whether a provider-owned record belongs to this vendor. */
    protected function ownsRecord(User $vendor, ?int $vendorId, ?int $venueId): bool
    {
        if ($vendorId !== null) {
            return $vendorId === $vendor->id;
        }

        return $venueId !== null
            && MarketplaceVenue::whereKey($venueId)->where('owner_id', $vendor->id)->exists();
    }
}
