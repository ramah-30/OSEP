<?php

namespace App\Enums;

enum ApprovalStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case ChangesRequested = 'changes_requested';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Awaiting your review',
            self::Approved => 'Approved',
            self::Rejected => 'Rejected',
            self::ChangesRequested => 'Changes requested',
        };
    }
}
