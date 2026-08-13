<?php

namespace App\Enums;

/**
 * Lifecycle of an event's master budget. `Locked` freezes the figures once the
 * client has approved them; `Archived` retires a superseded budget.
 */
enum BudgetStatus: string
{
    case Draft = 'draft';
    case PendingApproval = 'pending_approval';
    case Approved = 'approved';
    case Locked = 'locked';
    case Archived = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::PendingApproval => 'Pending approval',
            self::Approved => 'Approved',
            self::Locked => 'Locked',
            self::Archived => 'Archived',
        };
    }

    /** Whether budget lines may still be edited. */
    public function isEditable(): bool
    {
        return in_array($this, [self::Draft, self::PendingApproval], true);
    }
}
