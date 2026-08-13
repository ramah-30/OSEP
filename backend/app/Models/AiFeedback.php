<?php

namespace App\Models;

use App\Enums\FeedbackRating;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiFeedback extends Model
{
    protected $table = 'ai_feedback';

    protected $fillable = [
        'user_id',
        'subject_type',
        'subject_id',
        'event_id',
        'rating',
        'reason',
    ];

    protected function casts(): array
    {
        return [
            'rating' => FeedbackRating::class,
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
