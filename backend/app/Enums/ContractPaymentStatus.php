<?php

namespace App\Enums;

/**
 * A contract's payment progress - tracked separately from its legal `status`
 * (draft/sent/signed/active/completed). See Contract::recalculatePaid().
 */
enum ContractPaymentStatus: string
{
    case Unpaid = 'unpaid';
    case PartiallyPaid = 'partially_paid';
    case Paid = 'paid';

    public function label(): string
    {
        return match ($this) {
            self::Unpaid => 'Unpaid',
            self::PartiallyPaid => 'Partially paid',
            self::Paid => 'Paid',
        };
    }
}
