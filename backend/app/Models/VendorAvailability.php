<?php

namespace App\Models;

use App\Enums\SlotStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VendorAvailability extends Model
{
    protected $table = 'vendor_availability';

    protected $fillable = [
        'vendor_id',
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
     * @return BelongsTo<User, $this>
     */
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'vendor_id');
    }
}
