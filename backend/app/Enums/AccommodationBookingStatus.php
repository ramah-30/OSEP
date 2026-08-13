<?php

namespace App\Enums;

/**
 * Lifecycle of a hotel room reservation. A planner's booking is Confirmed on
 * creation (rooms are held), can be Cancelled, and rolls to Completed once the
 * stay is over.
 */
enum AccommodationBookingStatus: string
{
    case Confirmed = 'confirmed';
    case Cancelled = 'cancelled';
    case Completed = 'completed';

    public function label(): string
    {
        return match ($this) {
            self::Confirmed => 'Confirmed',
            self::Cancelled => 'Cancelled',
            self::Completed => 'Completed',
        };
    }

    /** A cancelled booking no longer holds inventory. */
    public function holdsInventory(): bool
    {
        return $this !== self::Cancelled;
    }
}
