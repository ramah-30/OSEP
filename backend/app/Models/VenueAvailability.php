<?php

namespace App\Models;

use App\Enums\SlotStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VenueAvailability extends Model
{
    protected $table = 'venue_availability';

    protected $fillable = [
        'venue_id',
        'date',
        'status',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'status' => SlotStatus::class,
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
