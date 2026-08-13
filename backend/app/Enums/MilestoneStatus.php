<?php

namespace App\Enums;

enum MilestoneStatus: string
{
    case Pending = 'pending';
    case InProgress = 'in_progress';
    case WaitingApproval = 'waiting_approval';
    case Completed = 'completed';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::InProgress => 'In progress',
            self::WaitingApproval => 'Waiting approval',
            self::Completed => 'Completed',
        };
    }
}
