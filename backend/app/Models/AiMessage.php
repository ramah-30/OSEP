<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class AiMessage extends Model
{
    protected $fillable = [
        'ai_conversation_id',
        'role',
        'content',
        'agent',
        'model',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'meta' => 'array',
        ];
    }

    /**
     * @return BelongsTo<AiConversation, $this>
     */
    public function conversation(): BelongsTo
    {
        return $this->belongsTo(AiConversation::class, 'ai_conversation_id');
    }

    /**
     * Planner feedback on this message. A constrained hasMany against the
     * (subject_type, subject_id) pair on the shared ai_feedback table.
     *
     * @return HasMany<AiFeedback, $this>
     */
    public function feedback(): HasMany
    {
        return $this->hasMany(AiFeedback::class, 'subject_id')->where('subject_type', 'message');
    }

    /**
     * A proposed action the copilot attached to this turn, awaiting the planner's
     * approval (or already run). Rendered as an approval card in the chat.
     *
     * @return HasOne<AiAction, $this>
     */
    public function action(): HasOne
    {
        return $this->hasOne(AiAction::class);
    }
}
