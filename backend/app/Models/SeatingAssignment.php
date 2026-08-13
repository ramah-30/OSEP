<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SeatingAssignment extends Model
{
    protected $fillable = [
        'venue_object_id',
        'guest_id',
        'seat_number',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'seat_number' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<VenueObject, $this>
     */
    public function object(): BelongsTo
    {
        return $this->belongsTo(VenueObject::class, 'venue_object_id');
    }

    /**
     * @return BelongsTo<Guest, $this>
     */
    public function guest(): BelongsTo
    {
        return $this->belongsTo(Guest::class);
    }
}
