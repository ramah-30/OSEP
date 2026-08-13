<?php

namespace App\Exceptions;

use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * A sign-in attempt that failed for a credential reason.
 *
 * Deliberately not a ValidationException: the message must not attach itself to
 * the email or password field, and it must never say which of the two was
 * wrong. The specific cause is written to auth_audit_logs instead, where it is
 * useful for support without being useful to someone guessing at accounts.
 */
class AuthFailedException extends HttpException
{
    public function __construct(string $message = 'Wrong credentials, try again.')
    {
        parent::__construct(401, $message);
    }
}
