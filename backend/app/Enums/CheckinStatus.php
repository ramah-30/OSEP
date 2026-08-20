<?php

namespace App\Enums;

/**
 * A guest's arrival state at the event.
 */
enum CheckinStatus: string
{
    case Pending = 'pending';
    case CheckedIn = 'checked_in';
    case NoShow = 'no_show';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Not arrived',
            self::CheckedIn => 'Checked in',
            self::NoShow => 'No show',
        };
    }
}
