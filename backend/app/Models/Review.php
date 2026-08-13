<?php

namespace App\Models;

use App\Enums\ReviewStatus;
use App\Models\Concerns\BelongsToProvider;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Review extends Model
{
    use BelongsToProvider;

    /** The five scored categories a planner rates. */
    public const CATEGORIES = [
        'rating_professionalism',
        'rating_communication',
        'rating_quality',
        'rating_value',
        'rating_timeliness',
    ];

    protected $fillable = [
        'reviewer_id',
        'vendor_id',
        'venue_id',
        'event_id',
        'contract_id',
        'rating_professionalism',
        'rating_communication',
        'rating_quality',
        'rating_value',
        'rating_timeliness',
        'overall_rating',
        'title',
        'comment',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'rating_professionalism' => 'integer',
            'rating_communication' => 'integer',
            'rating_quality' => 'integer',
            'rating_value' => 'integer',
            'rating_timeliness' => 'integer',
            'overall_rating' => 'decimal:2',
            'status' => ReviewStatus::class,
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    /**
     * @return HasMany<ReviewReply, $this>
     */
    public function replies(): HasMany
    {
        return $this->hasMany(ReviewReply::class);
    }

    /** Average of the supplied category scores, on a 1–5 scale. */
    public static function averageOf(array $scores): float
    {
        $values = array_filter($scores, fn ($v) => $v !== null && $v !== '');

        return count($values) ? round(array_sum($values) / count($values), 2) : 0.0;
    }
}
