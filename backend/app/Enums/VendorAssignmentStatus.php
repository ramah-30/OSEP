<?php

namespace App\Enums;

enum VendorAssignmentStatus: string
{
    case Requested = 'requested';
    case Accepted = 'accepted';
    case Declined = 'declined';
    case Completed = 'completed';

    public function label(): string
    {
        return ucfirst($this->value);
    }
}
