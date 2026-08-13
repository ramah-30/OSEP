<?php

namespace App\Enums;

/**
 * Lifecycle of a planner's booking request to a vendor or venue.
 */
enum BookingRequestStatus: string
{
    case Pending = 'pending';
    case Accepted = 'accepted';
    case Declined = 'declined';
    case InfoRequested = 'info_requested';
    case Withdrawn = 'withdrawn';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Accepted => 'Accepted',
            self::Declined => 'Declined',
            self::InfoRequested => 'More info requested',
            self::Withdrawn => 'Withdrawn',
        };
    }

    public function isOpen(): bool
    {
        return in_array($this, [self::Pending, self::InfoRequested], true);
    }
}
