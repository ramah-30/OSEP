<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * A hotel / accommodation listing in the marketplace. Planners browse these and
 * book a room for a client (e.g. a honeymoon). Bookable rooms are its
 * {@see AccommodationRoomType} children.
 */
class Accommodation extends Model
{
    protected $fillable = [
        'owner_id', 'name', 'slug', 'description', 'star_rating', 'city', 'location',
        'address', 'amenities', 'cover_image_url', 'gallery', 'currency', 'price_from',
        'check_in_time', 'check_out_time', 'policies', 'contact_phone', 'contact_email',
        'is_featured', 'is_published', 'profile_views',
    ];

    protected function casts(): array
    {
        return [
            'amenities' => 'array',
            'gallery' => 'array',
            'star_rating' => 'integer',
            'price_from' => 'decimal:2',
            'is_featured' => 'boolean',
            'is_published' => 'boolean',
            'profile_views' => 'integer',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    protected static function booted(): void
    {
        static::creating(function (Accommodation $a) {
            if (empty($a->slug)) {
                $a->slug = static::uniqueSlug($a->name);
            }
        });
    }

    public static function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'hotel';
        $slug = $base;
        $i = 1;
        while (static::where('slug', $slug)->exists()) {
            $slug = $base . '-' . (++$i);
        }

        return $slug;
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /**
     * @return HasMany<AccommodationRoomType, $this>
     */
    public function roomTypes(): HasMany
    {
        return $this->hasMany(AccommodationRoomType::class);
    }

    /**
     * @return HasMany<AccommodationBooking, $this>
     */
    public function bookings(): HasMany
    {
        return $this->hasMany(AccommodationBooking::class);
    }

    /**
     * @return HasMany<AccommodationReview, $this>
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(AccommodationReview::class)->latest();
    }
}
