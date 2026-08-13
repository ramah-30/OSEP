<?php

namespace App\Enums;

/**
 * Lifecycle of a vendor quotation. `Negotiating` covers the back-and-forth after
 * a planner asks for changes; `Expired` is set once the expiry date passes.
 */
enum QuotationStatus: string
{
    case Draft = 'draft';
    case Sent = 'sent';
    case Accepted = 'accepted';
    case Rejected = 'rejected';
    case Negotiating = 'negotiating';
    case Expired = 'expired';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Sent => 'Sent',
            self::Accepted => 'Accepted',
            self::Rejected => 'Rejected',
            self::Negotiating => 'Negotiating',
            self::Expired => 'Expired',
        };
    }

    /** Whether the planner can still act (accept / reject / negotiate) on it. */
    public function isActionable(): bool
    {
        return in_array($this, [self::Sent, self::Negotiating], true);
    }
}
