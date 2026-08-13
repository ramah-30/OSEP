<?php

namespace App\Enums;

/**
 * State of an installment in a payment schedule (e.g. "30% deposit"). `Overdue`
 * once its due date passes while still unpaid.
 */
enum ScheduleStatus: string
{
    case Pending = 'pending';
    case Scheduled = 'scheduled';
    case Paid = 'paid';
    case Overdue = 'overdue';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Scheduled => 'Scheduled',
            self::Paid => 'Paid',
            self::Overdue => 'Overdue',
        };
    }
}
