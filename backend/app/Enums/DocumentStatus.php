<?php

namespace App\Enums;

/**
 * Whether a generated document is still a working draft or has been finalised
 * by the planner.
 */
enum DocumentStatus: string
{
    case Draft = 'draft';
    case Final = 'final';

    public function label(): string
    {
        return ucfirst($this->value);
    }
}
