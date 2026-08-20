<?php

namespace App\Enums;

enum UserStatus: string
{
    /** Registered but has not confirmed their email address yet. */
    case Pending = 'pending';

    /** Verified and able to sign in. */
    case Active = 'active';

    /** Blocked by an administrator. Cannot sign in. */
    case Suspended = 'suspended';

    public function label(): string
    {
        return ucfirst($this->value);
    }
}
