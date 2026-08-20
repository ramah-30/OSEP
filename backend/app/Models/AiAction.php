<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A queued action the copilot wants to take (or has taken) on the planner's
 * behalf. Nothing outbound runs until the planner approves; the row records the
 * proposal, the approval, and the outcome.
 */
class AiAction extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_DONE = 'done';
    public const STATUS_FAILED = 'failed';
    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'user_id',
        'event_id',
        'ai_conversation_id',
        'ai_message_id',
        'source',
        'type',
        'title',
        'summary',
        'params',
        'status',
        'result',
        'error',
        'approved_at',
        'executed_at',
    ];

    protected function casts(): array
    {
        return [
            'params' => 'array',
            'result' => 'array',
            'approved_at' => 'datetime',
            'executed_at' => 'datetime',
        ];
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
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
