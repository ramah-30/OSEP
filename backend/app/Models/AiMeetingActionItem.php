<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiMeetingActionItem extends Model
{
    protected $fillable = [
        'ai_meeting_id',
        'user_id',
        'task_id',
        'description',
        'owner',
        'due_date',
        'status',
        'position',
    ];

    protected function casts(): array
    {
        return [
            'due_date' => 'date',
        ];
    }

    /**
     * @return BelongsTo<AiMeeting, $this>
     */
    public function meeting(): BelongsTo
    {
        return $this->belongsTo(AiMeeting::class, 'ai_meeting_id');
    }

    /**
     * The event task this item was pushed into, if any.
     *
     * @return BelongsTo<EventTask, $this>
     */
    public function task(): BelongsTo
    {
        return $this->belongsTo(EventTask::class, 'task_id');
    }
}
