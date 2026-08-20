<?php

namespace App\Models;

use App\Enums\VenueSetting;
use App\Enums\VerificationLevel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class MarketplaceVenue extends Model
{
    protected $fillable = [
        'owner_id',
        'name',
        'slug',
        'venue_type',
        'description',
        'setting',
        'capacity',
        'min_capacity',
        'dimensions',
        'layout_options',
        'setup_time',
        'breakdown_time',
        'included_equipment',
        'facilities',
        'accessibility',
        'restrictions',
        'parking_available',
        'parking_capacity',
        'price',
        'currency',
        'price_unit',
        'address',
        'location',
        'latitude',
        'longitude',
        'contact_person',
        'contact_phone',
        'contact_email',
        'booking_terms',
        'cover_image_url',
        'verification_level',
        'is_featured',
        'is_suspended',
        'is_published',
        'rating',
        'reviews_count',
        'profile_views',
    ];

    protected function casts(): array
    {
        return [
            'setting' => VenueSetting::class,
            'verification_level' => VerificationLevel::class,
            'capacity' => 'integer',
            'min_capacity' => 'integer',
            'layout_options' => 'array',
            'included_equipment' => 'array',
            'facilities' => 'array',
            'accessibility' => 'array',
            'parking_available' => 'boolean',
            'parking_capacity' => 'integer',
            'price' => 'decimal:2',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'is_featured' => 'boolean',
            'is_suspended' => 'boolean',
            'is_published' => 'boolean',
            'rating' => 'decimal:2',
            'reviews_count' => 'integer',
            'profile_views' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (MarketplaceVenue $venue) {
            if (blank($venue->slug)) {
                $venue->slug = Str::slug($venue->name).'-'.Str::lower(Str::random(5));
            }
        });
    }

    /**
     * Listings a planner may see: published and not suspended.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<MarketplaceVenue>  $query
     */
    public function scopePublished($query): void
    {
        $query->where('is_published', true)->where('is_suspended', false);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /**
     * @return HasMany<VenueImage, $this>
     */
    public function images(): HasMany
    {
        return $this->hasMany(VenueImage::class, 'venue_id')->orderBy('sort_order');
    }

    /**
     * @return HasMany<VenueAvailability, $this>
     */
    public function availability(): HasMany
    {
        return $this->hasMany(VenueAvailability::class, 'venue_id');
    }

    /**
     * @return HasMany<Review, $this>
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class, 'venue_id');
    }
}
