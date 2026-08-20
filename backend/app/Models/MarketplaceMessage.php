<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketplaceMessage extends Model
{
    protected $fillable = [
        'thread_id',
        'sender_id',
        'body',
        'attachments',
        'quotation_id',
        'read_at',
    ];

    protected function casts(): array
    {
        return [
            'attachments' => 'array',
            'read_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<MessageThread, $this>
     */
    public function thread(): BelongsTo
    {
        return $this->belongsTo(MessageThread::class, 'thread_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    /**
     * @return BelongsTo<Quotation, $this>
     */
    public function quotation(): BelongsTo
    {
        return $this->belongsTo(Quotation::class);
    }
}
