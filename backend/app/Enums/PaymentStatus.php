<?php

namespace App\Enums;

/**
 * State of a recorded payment transaction. Most payments are `Completed` the
 * moment they are logged; `Pending` covers a transfer that is still clearing.
 */
enum PaymentStatus: string
{
    case Pending = 'pending';
    case Completed = 'completed';
    case Failed = 'failed';
    case Refunded = 'refunded';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Completed => 'Completed',
            self::Failed => 'Failed',
            self::Refunded => 'Refunded',
        };
    }
}
