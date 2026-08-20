<?php

namespace App\Enums;

/**
 * Lifecycle of a single invitation send. `Delivered` / `Opened` are advanced by
 * the (future) mail webhook or, in dev, simulated by the dispatcher.
 */
enum InvitationStatus: string
{
    case Draft = 'draft';
    case Scheduled = 'scheduled';
    case Sent = 'sent';
    case Delivered = 'delivered';
    case Opened = 'opened';
    case Failed = 'failed';

    public function label(): string
    {
        return ucfirst($this->value);
    }
}
