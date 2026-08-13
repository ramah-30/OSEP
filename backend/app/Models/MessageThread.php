<?php

namespace App\Models;

use App\Models\Concerns\BelongsToProvider;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MessageThread extends Model
{
    use BelongsToProvider;

    protected $fillable = [
        'planner_id',
        'vendor_id',
        'venue_id',
        'event_id',
        'booking_request_id',
        'subject',
        'last_message_at',
    ];

    protected function casts(): array
    {
        return [
            'last_message_at' => 'datetime',
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
     * @return BelongsTo<Event, $this>
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * @return HasMany<MarketplaceMessage, $this>
     */
    public function messages(): HasMany
    {
        return $this->hasMany(MarketplaceMessage::class, 'thread_id')->orderBy('created_at');
    }
}
