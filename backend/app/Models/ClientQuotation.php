<?php

namespace App\Models;

use App\Enums\ClientQuotationStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ClientQuotation extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'reference',
        'planner_id',
        'client_id',
        'event_id',
        'title',
        'currency',
        'valid_until',
        'subtotal',
        'tax',
        'discount',
        'total',
        'notes',
        'terms',
        'status',
        'sent_at',
        'viewed_at',
        'decided_at',
    ];

    protected function casts(): array
    {
        return [
            'valid_until' => 'date',
            'subtotal' => 'decimal:2',
            'tax' => 'decimal:2',
            'discount' => 'decimal:2',
            'total' => 'decimal:2',
            'status' => ClientQuotationStatus::class,
            'sent_at' => 'datetime',
            'viewed_at' => 'datetime',
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
     * @return HasMany<ClientQuotationItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(ClientQuotationItem::class)->orderBy('sort_order');
    }

    /** Recompute the header totals from the line items and persist. */
    public function recalculateTotals(): void
    {
        $items = $this->items()->get();

        $this->subtotal = (float) $items->sum('amount');
        $this->tax = (float) $items->sum('tax');
        $this->discount = (float) $items->sum('discount');
        $this->total = $this->subtotal + (float) $this->tax - (float) $this->discount;
        $this->save();
    }
}
