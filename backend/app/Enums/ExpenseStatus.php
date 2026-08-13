<?php

namespace App\Enums;

/**
 * Lifecycle of an expense as it moves through the approval workflow before it is
 * finally paid.
 */
enum ExpenseStatus: string
{
    case Draft = 'draft';
    case Submitted = 'submitted';
    case Approved = 'approved';
    case Paid = 'paid';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Submitted => 'Submitted',
            self::Approved => 'Approved',
            self::Paid => 'Paid',
            self::Rejected => 'Rejected',
        };
    }

    /** Statuses that still count as money committed but not yet spent. */
    public function isOutstanding(): bool
    {
        return in_array($this, [self::Submitted, self::Approved], true);
    }
}
