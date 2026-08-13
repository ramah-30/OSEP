<?php

namespace App\Enums;

/**
 * A vendor's current openness to new bookings.
 */
enum AvailabilityStatus: string
{
    case Available = 'available';
    case Busy = 'busy';
    case Unavailable = 'unavailable';

    public function label(): string
    {
        return match ($this) {
            self::Available => 'Available',
            self::Busy => 'Busy',
            self::Unavailable => 'Unavailable',
        };
    }
}
