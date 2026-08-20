<?php

namespace App\Models;

use App\Enums\ScheduleStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentSchedule extends Model
{
    protected $fillable = [
        'planner_id',
        'event_id',
        'invoice_id',
        'vendor_assigned_id',
        'label',
        'percentage',
        'amount',
        'currency',
        'due_date',
        'status',
        'paid_at',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'percentage' => 'decimal:2',
            'amount' => 'decimal:2',
            'due_date' => 'date',
            'paid_at' => 'date',
            'status' => ScheduleStatus::class,
            'sort_order' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Invoice, $this>
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    /**
     * @return BelongsTo<Event, $this>
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * @return BelongsTo<VendorAssignment, $this>
     */
    public function vendorAssignment(): BelongsTo
    {
        return $this->belongsTo(VendorAssignment::class, 'vendor_assigned_id');
    }
}
