<?php

namespace App\Enums;

/**
 * Lifecycle of a client invoice. `PartiallyPaid` and `Paid` are derived from the
 * payments recorded against it; `Overdue` once the due date passes with a
 * balance outstanding.
 */
enum InvoiceStatus: string
{
    case Draft = 'draft';
    case Sent = 'sent';
    case PartiallyPaid = 'partially_paid';
    case Paid = 'paid';
    case Overdue = 'overdue';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Sent => 'Sent',
            self::PartiallyPaid => 'Partially paid',
            self::Paid => 'Paid',
            self::Overdue => 'Overdue',
            self::Cancelled => 'Cancelled',
        };
    }

    /** Whether a balance is still collectable on this invoice. */
    public function isCollectable(): bool
    {
        return in_array($this, [self::Sent, self::PartiallyPaid, self::Overdue], true);
    }
}
