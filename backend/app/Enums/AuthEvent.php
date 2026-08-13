<?php

namespace App\Enums;

/**
 * Authentication events written to auth_audit_logs.
 */
enum AuthEvent: string
{
    case Registered = 'registered';
    case LoginSuccess = 'login_success';
    case LoginFailed = 'login_failed';
    case LoginBlocked = 'login_blocked';
    case Logout = 'logout';
    case PasswordResetRequested = 'password_reset_requested';
    case PasswordReset = 'password_reset';
    case EmailVerified = 'email_verified';
    case VerificationResent = 'verification_resent';
    case GoogleLogin = 'google_login';
    case EmailChanged = 'email_changed';
    case PasswordChanged = 'password_changed';
}
