<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VendorPortfolio extends Model
{
    protected $fillable = [
        'vendor_id',
        'title',
        'description',
        'event_type',
        'event_date',
        'cover_url',
        'media',
        'client_feedback',
        'is_case_study',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'event_date' => 'date',
            'media' => 'array',
            'is_case_study' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'vendor_id');
    }
}
