<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AiMeeting extends Model
{
    protected $fillable = [
        'user_id',
        'event_id',
        'title',
        'meeting_type',
        'meeting_date',
        'attendees',
        'notes',
        'summary',
        'status',
        'model',
        'meta',
        'processed_at',
    ];

    protected function casts(): array
    {
        return [
            'meeting_date' => 'date',
            'attendees' => 'array',
            'meta' => 'array',
            'processed_at' => 'datetime',
        ];
    }

    /**
     * @return HasMany<AiMeetingActionItem, $this>
     */
    public function actionItems(): HasMany
    {
        return $this->hasMany(AiMeetingActionItem::class)->orderBy('position')->orderBy('id');
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
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
