<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlannerProfile extends Model
{
    protected $fillable = [
        'user_id',
        'company_name',
        'experience_years',
        'specialization',
        'bio',
        'location',
        'website',
        'booking_slug',
        'verified_at',
    ];

    protected function casts(): array
    {
        return [
            'experience_years' => 'integer',
            'verified_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
