<?php

namespace App\Enums;

/**
 * Urgency of an AI recommendation, used for ordering the feed and colouring
 * the priority badge.
 */
enum RecommendationPriority: string
{
    case Low = 'low';
    case Medium = 'medium';
    case High = 'high';
    case Critical = 'critical';

    public function label(): string
    {
        return ucfirst($this->value);
    }

    /** Sort weight — higher surfaces first. */
    public function weight(): int
    {
        return match ($this) {
            self::Critical => 4,
            self::High => 3,
            self::Medium => 2,
            self::Low => 1,
        };
    }
}
