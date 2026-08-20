<?php

namespace App\Enums;

/**
 * Lifecycle of a refund request, from raised through to money returned.
 */
enum RefundStatus: string
{
    case Requested = 'requested';
    case Approved = 'approved';
    case Processed = 'processed';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Requested => 'Requested',
            self::Approved => 'Approved',
            self::Processed => 'Processed',
            self::Rejected => 'Rejected',
        };
    }
}
