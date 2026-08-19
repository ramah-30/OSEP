<?php

namespace App\Enums;

/**
 * Mobile money network the simulated payment form lets a payer pick from.
 */
enum MobileNetwork: string
{
    case Airtel = 'airtel';
    case MixxByYas = 'mixx_by_yas';
    case Vodacom = 'vodacom';
    case Halotel = 'halotel';

    public function label(): string
    {
        return match ($this) {
            self::Airtel => 'Airtel Money',
            self::MixxByYas => 'Mixx by Yas',
            self::Vodacom => 'Vodacom M-Pesa',
            self::Halotel => 'Halotel',
        };
    }
}
