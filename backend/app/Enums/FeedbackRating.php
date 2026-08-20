<?php

namespace App\Enums;

/**
 * A planner's verdict on a piece of AI output.
 */
enum FeedbackRating: string
{
    case Up = 'up';
    case Down = 'down';

    public function label(): string
    {
        return $this === self::Up ? 'Helpful' : 'Not helpful';
    }
}
