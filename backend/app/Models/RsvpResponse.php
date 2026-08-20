<?php

namespace App\Models;

use App\Enums\RsvpResponse as RsvpResponseEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RsvpResponse extends Model
{
    protected $fillable = [
        'event_id',
        'guest_id',
        'invitation_id',
        'response',
        'additional_guests',
        'meal_choice',
        'special_requirements',
        'message',
        'responded_at',
    ];

    protected function casts(): array
    {
        return [
            'response' => RsvpResponseEnum::class,
            'additional_guests' => 'integer',
            'responded_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Event, $this>
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * @return BelongsTo<Guest, $this>
     */
    public function guest(): BelongsTo
    {
        return $this->belongsTo(Guest::class);
    }
}
