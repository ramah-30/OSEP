<?php

namespace App\Enums;

/**
 * A single day's status on a vendor's or venue's availability calendar. Planners
 * read this before sending a booking request.
 */
enum SlotStatus: string
{
    case Available = 'available';
    case Reserved = 'reserved';
    case FullyBooked = 'fully_booked';
    case OnLeave = 'on_leave';

    public function label(): string
    {
        return match ($this) {
            self::Available => 'Available',
            self::Reserved => 'Reserved',
            self::FullyBooked => 'Fully booked',
            self::OnLeave => 'On leave',
        };
    }

    /** Whether a planner may still request this date. */
    public function isBookable(): bool
    {
        return $this === self::Available;
    }
}
