<?php

namespace App\Models;

use App\Enums\AvailabilityStatus;
use App\Enums\VerificationLevel;
use App\Enums\VerificationStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VendorProfile extends Model
{
    protected $fillable = [
        'user_id',
        'business_name',
        'tagline',
        'category',
        'category_id',
        'description',
        'years_in_business',
        'location',
        'phone',
        'contact_email',
        'website',
        'social_links',
        'logo_url',
        'cover_image_url',
        'verification_status',
        'verification_level',
        'availability_status',
        'profile_views',
        'pending_requests',
        'response_time_hours',
        'completed_jobs',
        'reviews_count',
        'rating',
        'is_featured',
        'is_suspended',
    ];

    protected function casts(): array
    {
        return [
            'social_links' => 'array',
            'verification_status' => VerificationStatus::class,
            'verification_level' => VerificationLevel::class,
            'availability_status' => AvailabilityStatus::class,
            'years_in_business' => 'integer',
            'profile_views' => 'integer',
            'pending_requests' => 'integer',
            'response_time_hours' => 'integer',
            'completed_jobs' => 'integer',
            'reviews_count' => 'integer',
            'rating' => 'decimal:2',
            'is_featured' => 'boolean',
            'is_suspended' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<VendorCategory, $this>
     */
    public function marketplaceCategory(): BelongsTo
    {
        return $this->belongsTo(VendorCategory::class, 'category_id');
    }

    /**
     * A vendor's services/packages/portfolio/availability hang off the user, so
     * these convenience relations read through the profile's user_id.
     *
     * @return HasMany<VendorService, $this>
     */
    public function services(): HasMany
    {
        return $this->hasMany(VendorService::class, 'vendor_id', 'user_id');
    }

    /**
     * @return HasMany<VendorPackage, $this>
     */
    public function packages(): HasMany
    {
        return $this->hasMany(VendorPackage::class, 'vendor_id', 'user_id');
    }

    /**
     * @return HasMany<VendorPortfolio, $this>
     */
    public function portfolios(): HasMany
    {
        return $this->hasMany(VendorPortfolio::class, 'vendor_id', 'user_id');
    }

    /**
     * @return HasMany<VendorAvailability, $this>
     */
    public function availability(): HasMany
    {
        return $this->hasMany(VendorAvailability::class, 'vendor_id', 'user_id');
    }
}
