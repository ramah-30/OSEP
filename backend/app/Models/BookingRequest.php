<?php

namespace App\Models;

use App\Enums\BookingRequestStatus;
use App\Models\Concerns\BelongsToProvider;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BookingRequest extends Model
{
    use BelongsToProvider;

    protected $fillable = [
        'planner_id',
        'vendor_id',
        'venue_id',
        'event_id',
        'title',
        'event_date',
        'guest_count',
        'budget',
        'requirements',
        'attachments',
        'status',
        'response_note',
        'responded_at',
    ];

    protected function casts(): array
    {
        return [
            'event_date' => 'date',
            'guest_count' => 'integer',
            'budget' => 'decimal:2',
            'attachments' => 'array',
            'status' => BookingRequestStatus::class,
            'responded_at' => 'datetime',
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
     * @return HasMany<Quotation, $this>
     */
    public function quotations(): HasMany
    {
        return $this->hasMany(Quotation::class);
    }
}
