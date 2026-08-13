<?php

namespace App\Models;

use App\Enums\RecommendationPriority;
use App\Enums\RecommendationStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiRecommendation extends Model
{
    protected $fillable = [
        'user_id',
        'event_id',
        'category',
        'title',
        'description',
        'priority',
        'confidence',
        'action_label',
        'action_type',
        'action_payload',
        'status',
        'signature',
        'resolved_at',
    ];

    protected function casts(): array
    {
        return [
            'priority' => RecommendationPriority::class,
            'status' => RecommendationStatus::class,
            'confidence' => 'integer',
            'action_payload' => 'array',
            'resolved_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Event, $this>
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }
}
