<?php

namespace App\Models;

use App\Enums\BookingRequestStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlannerBookingRequest extends Model
{
    protected $table = 'planner_booking_requests';

    protected $fillable = [
        'reference',
        'planner_id',
        'client_id',
        'event_type',
        'event_date',
        'expected_guests',
        'venue',
        'location',
        'message',
        'status',
        'planner_note',
        'event_id',
    ];

    protected function casts(): array
    {
        return [
            'event_date' => 'date',
            'status' => BookingRequestStatus::class,
            'expected_guests' => 'integer',
        ];
    }

    // -----------------------------------------------------------------
    // Relationships
    // -----------------------------------------------------------------

    /**
     * @return BelongsTo<User, $this>
     */
    public function planner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'planner_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(User::class, 'client_id');
    }

    /**
     * @return BelongsTo<Event, $this>
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    // -----------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------

    /** BKR-YYYY-#### */
    public static function nextReference(): string
    {
        $year = now()->year;
        $count = static::whereYear('created_at', $year)->count() + 1;

        return sprintf('BKR-%d-%04d', $year, $count);
    }
}
