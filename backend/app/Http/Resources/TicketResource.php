<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * The digital pass payload for a guest: everything the printable/downloadable
 * ticket and the on-screen QR need. `$this` is the Guest (with event + qrCode).
 *
 * @mixin \App\Models\Guest
 */
class TicketResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $event = $this->event;

        return [
            'guest_id' => $this->id,
            'guest_name' => $this->full_name,
            'ticket_type' => $this->qrCode?->ticket_type ?? ($this->category ?: 'standard'),
            'qr_token' => $this->qrCode?->token,
            'seat_number' => $this->seat_number,
            'checkin_status' => $this->checkin_status->value,
            'event' => [
                'id' => $event?->id,
                'title' => $event?->title,
                'event_code' => $event?->event_code,
                'date' => $event?->event_date?->toDateString(),
                'start_time' => $event?->start_time,
                'end_time' => $event?->end_time,
                'venue' => $event?->venue,
                'location' => $event?->location,
            ],
        ];
    }
}
