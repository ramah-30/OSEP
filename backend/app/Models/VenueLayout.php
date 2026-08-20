<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VenueLayout extends Model
{
    protected $fillable = [
        'event_id',
        'created_by',
        'layout_name',
        'venue_name',
        'venue_type',
        'setting',
        'width',
        'height',
        'unit',
        'max_capacity',
        'entry_points',
        'exit_points',
        'version',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'width' => 'decimal:2',
            'height' => 'decimal:2',
            'max_capacity' => 'integer',
            'entry_points' => 'integer',
            'exit_points' => 'integer',
            'version' => 'integer',
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
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return HasMany<VenueObject, $this>
     */
    public function objects(): HasMany
    {
        return $this->hasMany(VenueObject::class, 'layout_id');
    }
}
