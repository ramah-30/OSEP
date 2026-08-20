<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A selectable meal on the RSVP page. Table name is `guest_meal_preferences`
 * (per the Phase 4 spec); the model is named for what a row actually is.
 */
class MealOption extends Model
{
    protected $table = 'guest_meal_preferences';

    protected $fillable = [
        'event_id',
        'name',
        'description',
        'dietary_tags',
        'is_active',
        'sort',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort' => 'integer',
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
