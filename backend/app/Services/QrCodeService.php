<?php

namespace App\Services;

use App\Models\Guest;
use App\Models\QrCode;
use Illuminate\Support\Str;

/**
 * Issues and validates the per-guest check-in credential. The QR image itself is
 * rendered on the client from `token`; here we only mint and snapshot it.
 */
class QrCodeService
{
    /**
     * Return the guest's active QR code, minting one the first time. Idempotent so
     * repeated confirmations don't invalidate a ticket already in a guest's inbox.
     */
    public function ensureFor(Guest $guest): QrCode
    {
        $existing = $guest->qrCode()->first();

        if ($existing && $existing->isActive()) {
            return $existing;
        }

        $guest->loadMissing('event');

        return QrCode::updateOrCreate(
            ['guest_id' => $guest->id],
            [
                'event_id' => $guest->event_id,
                'token' => $this->freshToken(),
                'ticket_type' => $guest->category ?: 'standard',
                'payload' => [
                    'guest_id' => $guest->id,
                    'event_id' => $guest->event_id,
                    'guest_name' => $guest->full_name,
                    'event_code' => $guest->event?->event_code,
                    'ticket_type' => $guest->category ?: 'standard',
                ],
                'issued_at' => now(),
                'revoked_at' => null,
            ],
        );
    }

    private function freshToken(): string
    {
        do {
            $token = 'TCK-'.Str::upper(Str::random(28));
        } while (QrCode::where('token', $token)->exists());

        return $token;
    }
}
