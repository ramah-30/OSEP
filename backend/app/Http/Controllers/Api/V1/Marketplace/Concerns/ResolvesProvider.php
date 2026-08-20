<?php

namespace App\Http\Controllers\Api\V1\Marketplace\Concerns;

use App\Enums\AccountType;
use App\Models\MarketplaceVenue;
use App\Models\User;

/**
 * Turns a request's `provider_type` + `provider_id` into the concrete
 * vendor_id / venue_id column pair, validating the target exists and is a real,
 * bookable provider. Used by every planner write action that targets a listing.
 */
trait ResolvesProvider
{
    /**
     * @return array{vendor_id: ?int, venue_id: ?int}
     */
    protected function resolveProvider(string $type, int $id): array
    {
        if ($type === 'venue') {
            $venue = MarketplaceVenue::query()
                ->where('is_suspended', false)
                ->findOrFail($id);

            return ['vendor_id' => null, 'venue_id' => $venue->id];
        }

        $vendor = User::query()
            ->where('account_type', AccountType::Vendor->value)
            ->findOrFail($id);

        return ['vendor_id' => $vendor->id, 'venue_id' => null];
    }
}
