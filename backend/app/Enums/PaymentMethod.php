<?php

namespace App\Enums;

/**
 * How a payment was made. Mobile money leads the list because it dominates in
 * the Tanzanian launch market.
 */
enum PaymentMethod: string
{
    case MobileMoney = 'mobile_money';
    case BankTransfer = 'bank_transfer';
    case CreditCard = 'credit_card';
    case Cash = 'cash';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::MobileMoney => 'Mobile money',
            self::BankTransfer => 'Bank transfer',
            self::CreditCard => 'Credit card',
            self::Cash => 'Cash',
            self::Other => 'Other',
        };
    }
}
