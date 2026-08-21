<?php

namespace App\Enums;

/**
 * Progressive trust tiers a vendor or venue can reach in the marketplace. Higher
 * tiers unlock a more prominent badge; `PremiumPartner` is granted by an admin.
 * This is distinct from {@see VerificationStatus}, which tracks the admin review
 * workflow (pending/verified/rejected) - the level is the outward-facing badge.
 */
enum VerificationLevel: string
{
    case Unverified = 'unverified';
    case EmailVerified = 'email_verified';
    case BusinessVerified = 'business_verified';
    case PremiumPartner = 'premium_partner';

    public function label(): string
    {
        return match ($this) {
            self::Unverified => 'Unverified',
            self::EmailVerified => 'Email verified',
            self::BusinessVerified => 'Business verified',
            self::PremiumPartner => 'Premium partner',
        };
    }

    /** Sort weight - higher is more trusted. */
    public function weight(): int
    {
        return match ($this) {
            self::Unverified => 0,
            self::EmailVerified => 1,
            self::BusinessVerified => 2,
            self::PremiumPartner => 3,
        };
    }

    /** Whether the listing should show a verification badge at all. */
    public function isVerified(): bool
    {
        return $this !== self::Unverified;
    }
}
