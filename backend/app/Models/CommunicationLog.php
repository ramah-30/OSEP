<?php

namespace App\Models;

use App\Enums\CommunicationType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommunicationLog extends Model
{
    protected $fillable = [
        'event_id',
        'guest_id',
        'created_by',
        'type',
        'channel',
        'title',
        'detail',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'type' => CommunicationType::class,
            'meta' => 'array',
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
