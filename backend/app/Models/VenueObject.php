<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VenueObject extends Model
{
    protected $fillable = [
        'layout_id',
        'uid',
        'object_type',
        'object_name',
        'x_position',
        'y_position',
        'width',
        'height',
        'rotation',
        'color',
        'layer',
        'properties',
    ];

    protected function casts(): array
    {
        return [
            'x_position' => 'decimal:2',
            'y_position' => 'decimal:2',
            'width' => 'decimal:2',
            'height' => 'decimal:2',
            'rotation' => 'decimal:2',
            'properties' => 'array',
        ];
    }

    /**
     * @return BelongsTo<VenueLayout, $this>
     */
    public function layout(): BelongsTo
    {
        return $this->belongsTo(VenueLayout::class, 'layout_id');
    }

    /**
     * @return HasMany<SeatingAssignment, $this>
     */
    public function seating(): HasMany
    {
        return $this->hasMany(SeatingAssignment::class)->orderBy('seat_number');
    }
}
