<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A bookable room type within a hotel, with a nightly rate, guest capacity and a
 * small inventory used to check availability across overlapping stays.
 */
class AccommodationRoomType extends Model
{
    protected $fillable = [
        'accommodation_id', 'name', 'description', 'price_per_night', 'currency',
        'capacity', 'bed_configuration', 'size_sqm', 'amenities', 'image_url',
        'total_rooms', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'price_per_night' => 'decimal:2',
            'capacity' => 'integer',
            'size_sqm' => 'integer',
            'amenities' => 'array',
            'total_rooms' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Accommodation, $this>
     */
    public function accommodation(): BelongsTo
    {
        return $this->belongsTo(Accommodation::class);
    }

    /**
     * @return HasMany<AccommodationBooking, $this>
     */
    public function bookings(): HasMany
    {
        return $this->hasMany(AccommodationBooking::class, 'room_type_id');
    }
}
