<?php

namespace App\Enums;

/**
 * How an invitation reaches a guest. Email + link + printable + QR are live;
 * SMS and WhatsApp are stubbed for a future integration (they still record a
 * delivery log so the UI is complete).
 */
enum InvitationChannel: string
{
    case Email = 'email';
    case Sms = 'sms';
    case Whatsapp = 'whatsapp';
    case Print = 'print';
    case Qr = 'qr';
    case Link = 'link';

    public function label(): string
    {
        return match ($this) {
            self::Email => 'Email',
            self::Sms => 'SMS',
            self::Whatsapp => 'WhatsApp',
            self::Print => 'Printable',
            self::Qr => 'QR invitation',
            self::Link => 'Shareable link',
        };
    }

    /** Channels not yet wired to a real gateway. */
    public function isFuture(): bool
    {
        return in_array($this, [self::Sms, self::Whatsapp], true);
    }
}
