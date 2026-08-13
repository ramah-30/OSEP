<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VenueImage extends Model
{
    protected $fillable = [
        'venue_id',
        'url',
        'caption',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<MarketplaceVenue, $this>
     */
    public function venue(): BelongsTo
    {
        return $this->belongsTo(MarketplaceVenue::class, 'venue_id');
    }
}
