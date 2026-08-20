<?php

namespace App\Enums;

/**
 * Moderation state of a planner review. New reviews are `Published` immediately;
 * an admin may `Hidden` one, and abuse reports can hold one as `Pending`.
 */
enum ReviewStatus: string
{
    case Published = 'published';
    case Pending = 'pending';
    case Hidden = 'hidden';

    public function label(): string
    {
        return match ($this) {
            self::Published => 'Published',
            self::Pending => 'Pending review',
            self::Hidden => 'Hidden',
        };
    }

    public function isVisible(): bool
    {
        return $this === self::Published;
    }
}
