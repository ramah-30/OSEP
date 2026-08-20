<?php

namespace App\Enums;

enum TaskStatus: string
{
    case NotStarted = 'not_started';
    case InProgress = 'in_progress';
    case WaitingApproval = 'waiting_approval';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::NotStarted => 'Not started',
            self::InProgress => 'In progress',
            self::WaitingApproval => 'Waiting approval',
            self::Completed => 'Completed',
            self::Cancelled => 'Cancelled',
        };
    }

    /**
     * The Kanban board columns, in order.
     *
     * @return array<int, self>
     */
    public static function board(): array
    {
        return [self::NotStarted, self::InProgress, self::WaitingApproval, self::Completed];
    }
}
