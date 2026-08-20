<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityLog extends Model
{
    protected $fillable = [
        'event_id',
        'user_id',
        'action',
        'description',
        'visible_to_client',
        'subject_type',
        'subject_id',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'meta' => 'array',
            'visible_to_client' => 'boolean',
        ];
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<ActivityLog>  $query
     */
    public function scopeVisibleToClient($query): void
    {
        $query->where('visible_to_client', true);
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
