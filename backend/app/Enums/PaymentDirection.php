<?php

namespace App\Enums;

/**
 * Which way money moves: `Incoming` is a client paying the planner (against an
 * invoice); `Outgoing` is the planner paying a vendor.
 */
enum PaymentDirection: string
{
    case Incoming = 'incoming';
    case Outgoing = 'outgoing';

    public function label(): string
    {
        return match ($this) {
            self::Incoming => 'Client payment',
            self::Outgoing => 'Vendor payment',
        };
    }
}
