<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A guest's review of a hotel: one overall 1–5 rating and an optional comment.
 * Deliberately lighter than the vendor {@see Review} (no categories or replies),
 * mirroring {@see PlannerReview}.
 */
class AccommodationReview extends Model
{
    protected $fillable = [
        'accommodation_id',
        'reviewer_id',
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
     * @return BelongsTo<Accommodation, $this>
     */
    public function accommodation(): BelongsTo
    {
        return $this->belongsTo(Accommodation::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }
}
