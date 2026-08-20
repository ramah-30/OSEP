<?php

namespace App\Models;

use App\Enums\ContractPaymentStatus;
use App\Enums\ContractStatus;
use App\Enums\PaymentStatus;
use App\Models\Concerns\BelongsToProvider;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Contract extends Model
{
    use BelongsToProvider;

    /** Mirrors the migration's DB defaults so a freshly created() instance
     * has them in memory too, before any round trip to the database. */
    protected $attributes = [
        'amount_paid' => 0,
        'payment_status' => 'unpaid',
    ];

    protected $fillable = [
        'quotation_id',
        'booking_request_id',
        'planner_id',
        'vendor_id',
        'venue_id',
        'event_id',
        'reference',
        'title',
        'status',
        'amount',
        'amount_paid',
        'payment_status',
        'currency',
        'terms',
        'document_path',
        'start_date',
        'end_date',
        'signed_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => ContractStatus::class,
            'payment_status' => ContractPaymentStatus::class,
            'amount' => 'decimal:2',
            'amount_paid' => 'decimal:2',
            'start_date' => 'date',
            'end_date' => 'date',
            'signed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function planner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'planner_id');
    }

    /**
     * @return BelongsTo<Quotation, $this>
     */
    public function quotation(): BelongsTo
    {
        return $this->belongsTo(Quotation::class);
    }

    /**
     * @return BelongsTo<Event, $this>
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * @return HasMany<Payment, $this>
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class)->latest('paid_at');
    }

    public function balance(): float
    {
        return (float) $this->amount - (float) $this->amount_paid;
    }

    /**
     * Re-derive `amount_paid` from completed outgoing payments and advance
     * `payment_status` accordingly. Deliberately separate from `status` —
     * signing/active/completed is the contract's legal lifecycle, this is
     * just how much of it has been paid.
     */
    public function recalculatePaid(): void
    {
        $paid = (float) $this->payments()
            ->where('direction', 'outgoing')
            ->where('status', PaymentStatus::Completed->value)
            ->sum('amount');

        $this->amount_paid = $paid;

        $this->payment_status = match (true) {
            (float) $this->amount > 0 && $paid >= (float) $this->amount => ContractPaymentStatus::Paid,
            $paid > 0 => ContractPaymentStatus::PartiallyPaid,
            default => ContractPaymentStatus::Unpaid,
        };

        $this->save();
    }
}
