<?php

namespace App\Enums;

/**
 * Kinds of entry in a guest's communication history.
 */
enum CommunicationType: string
{
    case Invitation = 'invitation';
    case Reminder = 'reminder';
    case Rsvp = 'rsvp';
    case Note = 'note';
    case Checkin = 'checkin';
    case System = 'system';

    public function label(): string
    {
        return match ($this) {
            self::Invitation => 'Invitation',
            self::Reminder => 'Reminder',
            self::Rsvp => 'RSVP',
            self::Note => 'Note',
            self::Checkin => 'Check-in',
            self::System => 'System',
        };
    }
}
