<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GuestCategory extends Model
{
    protected $fillable = [
        'created_by',
        'name',
        'color',
        'priority',
        'default_seating_area',
        'is_default',
    ];

    protected function casts(): array
    {
        return [
            'priority' => 'integer',
            'is_default' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
