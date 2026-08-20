<?php

namespace App\Enums;

/**
 * Whether OSEP has verified a vendor's business. Drives the badge on the vendor
 * profile and marketplace listings.
 */
enum VerificationStatus: string
{
    case Pending = 'pending';
    case Verified = 'verified';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending review',
            self::Verified => 'Verified',
            self::Rejected => 'Rejected',
        };
    }
}
