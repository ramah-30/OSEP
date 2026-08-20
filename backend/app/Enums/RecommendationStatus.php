<?php

namespace App\Enums;

/**
 * Lifecycle of an AI recommendation as the planner triages the feed.
 */
enum RecommendationStatus: string
{
    case Pending = 'pending';
    case Accepted = 'accepted';
    case Dismissed = 'dismissed';

    public function label(): string
    {
        return ucfirst($this->value);
    }
}
