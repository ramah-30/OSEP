<?php

namespace App\Services;

use App\Enums\AuthEvent;
use App\Models\AuthAuditLog;
use App\Models\User;
use Illuminate\Http\Request;

/**
 * Writes an immutable trail of authentication activity. Every entry keeps the
 * email even when no user matched, so failed attempts against unknown or
 * deleted accounts are still visible.
 */
class AuthAuditService
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    public function log(
        AuthEvent $event,
        ?User $user = null,
        ?string $email = null,
        ?Request $request = null,
        array $metadata = [],
    ): AuthAuditLog {
        $request ??= request();

        return AuthAuditLog::create([
            'user_id' => $user?->id,
            'email' => $email ?? $user?->email,
            'event' => $event->value,
            'ip_address' => $request?->ip(),
            'user_agent' => substr((string) $request?->userAgent(), 0, 1000),
            'metadata' => $metadata ?: null,
        ]);
    }
}
