<?php

namespace App\Models;

use App\Enums\InvoiceStatus;
use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Invoice extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'invoice_number',
        'planner_id',
        'client_id',
        'event_id',
        'client_quotation_id',
        'title',
        'currency',
        'issue_date',
        'due_date',
        'subtotal',
        'tax',
        'discount',
        'total',
        'amount_paid',
        'payment_terms',
        'notes',
        'status',
        'sent_at',
    ];

    protected function casts(): array
    {
        return [
            'issue_date' => 'date',
            'due_date' => 'date',
            'subtotal' => 'decimal:2',
            'tax' => 'decimal:2',
            'discount' => 'decimal:2',
            'total' => 'decimal:2',
            'amount_paid' => 'decimal:2',
            'status' => InvoiceStatus::class,
            'sent_at' => 'datetime',
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
     * @return BelongsTo<User, $this>
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(User::class, 'client_id');
    }

    /**
     * @return BelongsTo<Event, $this>
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * @return HasMany<InvoiceItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class)->orderBy('sort_order');
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
        return (float) $this->total - (float) $this->amount_paid;
    }

    /** Recompute header totals from the line items and persist. */
    public function recalculateTotals(): void
    {
        $items = $this->items()->get();

        $this->subtotal = (float) $items->sum('amount');
        $this->tax = (float) $items->sum('tax');
        $this->discount = (float) $items->sum('discount');
        $this->total = $this->subtotal + (float) $this->tax - (float) $this->discount;
        $this->save();
    }

    /**
     * Re-derive `amount_paid` from completed incoming payments and advance the
     * status (partially paid / paid / overdue) accordingly.
     */
    public function recalculatePaid(): void
    {
        $paid = (float) $this->payments()
            ->where('status', PaymentStatus::Completed->value)
            ->sum('amount');

        $this->amount_paid = $paid;

        if (! in_array($this->status, [InvoiceStatus::Draft, InvoiceStatus::Cancelled], true)) {
            if ($paid >= (float) $this->total && (float) $this->total > 0) {
                $this->status = InvoiceStatus::Paid;
            } elseif ($paid > 0) {
                $this->status = InvoiceStatus::PartiallyPaid;
            } elseif ($this->due_date && $this->due_date->isPast()) {
                $this->status = InvoiceStatus::Overdue;
            } else {
                $this->status = InvoiceStatus::Sent;
            }
        }

        $this->save();
    }
}
