<?php

namespace App\Enums;

/**
 * Lifecycle of a quotation the planner sends to a client. `Viewed` is stamped
 * when the client opens it in the portal; `Expired` once the valid-until date
 * passes without a decision.
 */
enum ClientQuotationStatus: string
{
    case Draft = 'draft';
    case Sent = 'sent';
    case Viewed = 'viewed';
    case Accepted = 'accepted';
    case Rejected = 'rejected';
    case Expired = 'expired';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Sent => 'Sent',
            self::Viewed => 'Viewed',
            self::Accepted => 'Accepted',
            self::Rejected => 'Rejected',
            self::Expired => 'Expired',
        };
    }

    public function isDecided(): bool
    {
        return in_array($this, [self::Accepted, self::Rejected, self::Expired], true);
    }
}
