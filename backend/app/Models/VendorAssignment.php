<?php

namespace App\Models;

use App\Enums\VendorAssignmentStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VendorAssignment extends Model
{
    protected $table = 'vendors_assigned';

    protected $fillable = [
        'event_id',
        'vendor_id',
        'vendor_name',
        'service',
        'package',
        'price',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'status' => VendorAssignmentStatus::class,
        ];
    }

    /**
     * @return BelongsTo<Event, $this>
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * The registered platform vendor, when the assignment points at one.
     *
     * @return BelongsTo<User, $this>
     */
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'vendor_id');
    }
}
