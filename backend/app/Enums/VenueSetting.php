<?php

namespace App\Enums;

/**
 * Whether a marketplace venue is indoor, outdoor, or offers both.
 */
enum VenueSetting: string
{
    case Indoor = 'indoor';
    case Outdoor = 'outdoor';
    case Both = 'both';

    public function label(): string
    {
        return match ($this) {
            self::Indoor => 'Indoor',
            self::Outdoor => 'Outdoor',
            self::Both => 'Indoor & Outdoor',
        };
    }
}
