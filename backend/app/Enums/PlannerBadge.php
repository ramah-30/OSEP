<?php

namespace App\Enums;

/**
 * A planner's outward-facing trust badge. Unlike the vendor {@see VerificationLevel}
 * (which an admin grants), a planner's badge is auto-earned from real activity —
 * a verified email, experience, completed events and client reviews — so it needs
 * no moderation step. Higher tiers read as more established.
 */
enum PlannerBadge: string
{
    case Unverified = 'unverified';
    case Verified = 'verified';       // email confirmed
    case Established = 'established';  // verified + a track record
    case TopRated = 'top_rated';      // established + strongly-reviewed

    /**
     * Derive the badge from a planner's real signals. Evaluated highest-first so a
     * planner always lands on the best tier they qualify for.
     */
    public static function derive(
        bool $emailVerified,
        int $experienceYears,
        int $completedEvents,
        float $rating,
        int $reviewsCount,
    ): self {
        if (! $emailVerified) {
            return self::Unverified;
        }

        if ($reviewsCount >= 3 && $rating >= 4.5 && $completedEvents >= 5) {
            return self::TopRated;
        }

        if ($experienceYears >= 2 || $completedEvents >= 3) {
            return self::Established;
        }

        return self::Verified;
    }

    public function label(): string
    {
        return match ($this) {
            self::Unverified => 'Unverified',
            self::Verified => 'Verified planner',
            self::Established => 'Established planner',
            self::TopRated => 'Top-rated planner',
        };
    }

    /** Sort weight — higher is more established. */
    public function weight(): int
    {
        return match ($this) {
            self::Unverified => 0,
            self::Verified => 1,
            self::Established => 2,
            self::TopRated => 3,
        };
    }

    /** Whether the profile should show a badge at all. */
    public function isVerified(): bool
    {
        return $this !== self::Unverified;
    }
}
