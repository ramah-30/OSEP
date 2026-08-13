<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class AiConversation extends Model
{
    protected $fillable = [
        'user_id',
        'event_id',
        'title',
        'context_type',
        'folder',
        'pinned',
        'last_message_at',
    ];

    protected function casts(): array
    {
        return [
            'pinned' => 'boolean',
            'last_message_at' => 'datetime',
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

    /**
     * @return HasMany<AiMessage, $this>
     */
    public function messages(): HasMany
    {
        return $this->hasMany(AiMessage::class)->orderBy('created_at');
    }

    /**
     * @return HasOne<AiMessage, $this>
     */
    public function latestMessage(): HasOne
    {
        return $this->hasOne(AiMessage::class)->latestOfMany();
    }
}
