<?php

namespace App\Models;

use App\Enums\PaymentDirection;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Payment extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'payment_number',
        'planner_id',
        'event_id',
        'invoice_id',
        'vendor_assigned_id',
        'payment_schedule_id',
        'direction',
        'party_name',
        'method',
        'amount',
        'currency',
        'transaction_ref',
        'reference',
        'status',
        'paid_at',
        'notes',
        'recorded_by',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'direction' => PaymentDirection::class,
            'method' => PaymentMethod::class,
            'status' => PaymentStatus::class,
            'paid_at' => 'date',
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
     * @return BelongsTo<Invoice, $this>
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    /**
     * @return BelongsTo<VendorAssignment, $this>
     */
    public function vendorAssignment(): BelongsTo
    {
        return $this->belongsTo(VendorAssignment::class, 'vendor_assigned_id');
    }

    /**
     * @return BelongsTo<PaymentSchedule, $this>
     */
    public function schedule(): BelongsTo
    {
        return $this->belongsTo(PaymentSchedule::class, 'payment_schedule_id');
    }

    /**
     * @return HasOne<Receipt, $this>
     */
    public function receipt(): HasOne
    {
        return $this->hasOne(Receipt::class);
    }
}
