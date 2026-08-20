<?php

namespace App\Models;

use App\Enums\ApprovalStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Approval extends Model
{
    protected $fillable = [
        'event_id',
        'submitted_by',
        'title',
        'type',
        'description',
        'status',
        'client_note',
        'decided_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => ApprovalStatus::class,
            'decided_at' => 'datetime',
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
     * @return BelongsTo<User, $this>
     */
    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    /**
     * @return HasMany<ApprovalHistory, $this>
     */
    public function history(): HasMany
    {
        return $this->hasMany(ApprovalHistory::class)->latest();
    }

    public function isPending(): bool
    {
        return $this->status === ApprovalStatus::Pending;
    }
}
