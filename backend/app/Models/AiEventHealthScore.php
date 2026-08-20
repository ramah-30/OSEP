<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiEventHealthScore extends Model
{
    protected $fillable = [
        'event_id',
        'score',
        'label',
        'breakdown',
        'forecasts',
        'computed_at',
    ];

    protected function casts(): array
    {
        return [
            'score' => 'integer',
            'breakdown' => 'array',
            'forecasts' => 'array',
            'computed_at' => 'datetime',
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
