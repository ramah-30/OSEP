<?php

namespace App\Enums;

/**
 * Lifecycle of a marketplace contract, generated once a quotation is accepted.
 */
enum ContractStatus: string
{
    case Draft = 'draft';
    case Sent = 'sent';
    case Signed = 'signed';
    case Active = 'active';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Sent => 'Sent',
            self::Signed => 'Signed',
            self::Active => 'Active',
            self::Completed => 'Completed',
            self::Cancelled => 'Cancelled',
        };
    }

    public function isOpen(): bool
    {
        return ! in_array($this, [self::Completed, self::Cancelled], true);
    }
}
