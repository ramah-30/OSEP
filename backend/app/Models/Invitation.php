<?php

namespace App\Models;

use App\Enums\InvitationChannel;
use App\Enums\InvitationStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invitation extends Model
{
    protected $fillable = [
        'event_id',
        'guest_id',
        'template_id',
        'created_by',
        'channel',
        'status',
        'subject',
        'body',
        'scheduled_for',
        'sent_at',
        'delivered_at',
        'opened_at',
        'failed_reason',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'channel' => InvitationChannel::class,
            'status' => InvitationStatus::class,
            'scheduled_for' => 'datetime',
            'sent_at' => 'datetime',
            'delivered_at' => 'datetime',
            'opened_at' => 'datetime',
            'meta' => 'array',
        ];
    }

    /**
     * @return BelongsTo<Event, $this>
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * @return BelongsTo<Guest, $this>
     */
    public function guest(): BelongsTo
    {
        return $this->belongsTo(Guest::class);
    }

    /**
     * @return BelongsTo<InvitationTemplate, $this>
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo(InvitationTemplate::class, 'template_id');
    }

    /**
     * @return HasMany<InvitationDeliveryLog, $this>
     */
    public function deliveryLogs(): HasMany
    {
        return $this->hasMany(InvitationDeliveryLog::class)->orderBy('occurred_at');
    }
}
