<?php

namespace App\Models;

use App\Enums\QuotationStatus;
use App\Models\Concerns\BelongsToProvider;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Quotation extends Model
{
    use BelongsToProvider;

    protected $fillable = [
        'booking_request_id',
        'planner_id',
        'vendor_id',
        'venue_id',
        'event_id',
        'reference',
        'subtotal',
        'tax',
        'total',
        'currency',
        'timeline',
        'terms',
        'notes',
        'status',
        'expires_at',
        'sent_at',
        'decided_at',
    ];

    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2',
            'tax' => 'decimal:2',
            'total' => 'decimal:2',
            'status' => QuotationStatus::class,
            'expires_at' => 'date',
            'sent_at' => 'datetime',
            'decided_at' => 'datetime',
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
     * @return BelongsTo<BookingRequest, $this>
     */
    public function bookingRequest(): BelongsTo
    {
        return $this->belongsTo(BookingRequest::class);
    }

    /**
     * @return BelongsTo<Event, $this>
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * @return HasMany<QuotationItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(QuotationItem::class)->orderBy('sort_order');
    }

    /** Recompute subtotal/total from the line items. */
    public function recalculateTotals(): void
    {
        $subtotal = (float) $this->items()->sum('amount');
        $this->subtotal = $subtotal;
        $this->total = $subtotal + (float) $this->tax;
        $this->save();
    }
}
