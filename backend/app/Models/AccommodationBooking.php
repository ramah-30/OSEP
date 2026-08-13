<?php

namespace App\Models;

use App\Enums\AccommodationBookingStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A confirmed hotel room reservation made by a planner for a client (the
 * honeymoon stay). Price is snapshotted so later rate changes don't alter it.
 */
class AccommodationBooking extends Model
{
    protected $fillable = [
        'reference', 'accommodation_id', 'room_type_id', 'planner_id', 'client_id',
        'event_id', 'guest_name', 'check_in', 'check_out', 'nights', 'rooms', 'guests',
        'price_per_night', 'total_price', 'currency', 'status', 'special_requests',
    ];

    protected function casts(): array
    {
        return [
            'check_in' => 'date',
            'check_out' => 'date',
            'nights' => 'integer',
            'rooms' => 'integer',
            'guests' => 'integer',
            'price_per_night' => 'decimal:2',
            'total_price' => 'decimal:2',
            'status' => AccommodationBookingStatus::class,
        ];
    }

    /** ACB-0001, ACB-0002, … */
    public static function nextReference(): string
    {
        $n = static::count() + 1;

        return 'ACB-' . str_pad((string) $n, 4, '0', STR_PAD_LEFT);
    }

    /**
     * @return BelongsTo<Accommodation, $this>
     */
    public function accommodation(): BelongsTo
    {
        return $this->belongsTo(Accommodation::class);
    }

    /**
     * @return BelongsTo<AccommodationRoomType, $this>
     */
    public function roomType(): BelongsTo
    {
        return $this->belongsTo(AccommodationRoomType::class, 'room_type_id');
    }

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
}
