<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Venue extends Model
{
    protected $fillable = [
        'event_id',
        'name',
        'address',
        'capacity',
        'setting',
        'contact_person',
        'contact_phone',
        'parking_available',
        'setup_time',
        'breakdown_time',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'capacity' => 'integer',
            'parking_available' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Event, $this>
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }
}
