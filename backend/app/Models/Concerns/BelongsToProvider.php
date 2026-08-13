<?php

namespace App\Models\Concerns;

use App\Models\MarketplaceVenue;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Shared behaviour for records that target either a vendor (a User) or a
 * marketplace venue, held in the nullable `vendor_id` / `venue_id` column pair.
 * Exactly one is set; helpers resolve the concrete provider without the caller
 * having to branch.
 */
trait BelongsToProvider
{
    /**
     * @return BelongsTo<User, $this>
     */
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'vendor_id');
    }

    /**
     * @return BelongsTo<MarketplaceVenue, $this>
     */
    public function venue(): BelongsTo
    {
        return $this->belongsTo(MarketplaceVenue::class, 'venue_id');
    }

    /** 'vendor' | 'venue' — which kind of provider this record points at. */
    public function providerType(): string
    {
        return $this->venue_id ? 'venue' : 'vendor';
    }

    /** The provider's display name, whichever kind it is. */
    public function providerName(): ?string
    {
        if ($this->venue_id) {
            return $this->venue?->name;
        }

        return $this->vendor?->vendorProfile?->business_name ?? $this->vendor?->full_name;
    }
}
