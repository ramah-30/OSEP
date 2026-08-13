<?php

namespace App\Enums;

/**
 * A guest's choice on the public RSVP page. Maps onto a {@see RsvpStatus} when
 * the response is recorded.
 */
enum RsvpResponse: string
{
    case Attending = 'attending';
    case NotAttending = 'not_attending';
    case Maybe = 'maybe';

    public function label(): string
    {
        return match ($this) {
            self::Attending => 'Attending',
            self::NotAttending => 'Not attending',
            self::Maybe => 'Maybe',
        };
    }

    public function toRsvpStatus(): RsvpStatus
    {
        return match ($this) {
            self::Attending => RsvpStatus::Confirmed,
            self::NotAttending => RsvpStatus::Declined,
            self::Maybe => RsvpStatus::Maybe,
        };
    }
}
