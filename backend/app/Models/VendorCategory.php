<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class VendorCategory extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'icon',
        'description',
        'is_custom',
        'is_active',
        'sort_order',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'is_custom' => 'boolean',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (VendorCategory $category) {
            if (blank($category->slug)) {
                $category->slug = Str::slug($category->name);
            }
        });
    }

    /**
     * @return HasMany<VendorProfile, $this>
     */
    public function vendors(): HasMany
    {
        return $this->hasMany(VendorProfile::class, 'category_id');
    }

    /**
     * @return HasMany<VendorService, $this>
     */
    public function services(): HasMany
    {
        return $this->hasMany(VendorService::class, 'category_id');
    }
}
