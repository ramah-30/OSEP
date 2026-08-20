<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A client's review of the planner who ran their event: one overall 1–5 rating
 * and an optional comment. See {@see \App\Support\PlannerReputation} for how these
 * roll up into a planner's rating and auto-earned trust badge.
 */
class PlannerReview extends Model
{
    protected $fillable = [
        'planner_id',
        'reviewer_id',
        'event_id',
        'rating',
        'comment',
    ];

    protected function casts(): array
    {
        return [
            'rating' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function planner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'planner_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    /**
     * @return BelongsTo<Event, $this>
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }
}
