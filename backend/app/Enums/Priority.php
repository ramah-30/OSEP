<?php

namespace App\Enums;

/**
 * Shared priority scale for events and tasks.
 */
enum Priority: string
{
    case Low = 'low';
    case Medium = 'medium';
    case High = 'high';
    case Urgent = 'urgent';

    public function label(): string
    {
        return ucfirst($this->value);
    }
}
