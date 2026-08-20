<?php

namespace App\Enums;

/**
 * A guest's response to their invitation. `Invited` / `Pending` both mean "no
 * answer yet" (kept apart only so pre-Phase-4 rows using `invited` stay valid);
 * dashboards treat them together as pending. `Maybe` was added for the RSVP
 * portal's tentative option.
 */
enum RsvpStatus: string
{
    case Invited = 'invited';
    case Pending = 'pending';
    case Confirmed = 'confirmed';
    case Declined = 'declined';
    case Maybe = 'maybe';
    case Attended = 'attended';

    public function label(): string
    {
        return match ($this) {
            self::Invited => 'Invited',
            self::Pending => 'Pending',
            self::Confirmed => 'Confirmed',
            self::Declined => 'Declined',
            self::Maybe => 'Maybe',
            self::Attended => 'Attended',
        };
    }

    /** Statuses that count as "awaiting a response". */
    public static function pendingStates(): array
    {
        return [self::Invited->value, self::Pending->value];
    }

    /**
     * A ticket is only issued once the guest has accepted — confirmed (or already
     * checked in as attended). Everyone else has nothing to download yet.
     */
    public function hasTicket(): bool
    {
        return in_array($this, [self::Confirmed, self::Attended], true);
    }
}
