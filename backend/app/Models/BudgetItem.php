<?php

namespace App\Models;

use App\Enums\BudgetItemStatus;
use App\Observers\BudgetItemObserver;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BudgetItem extends Model
{
    protected static function boot(): void
    {
        parent::boot();
        self::observe(BudgetItemObserver::class);
    }

    protected $fillable = [
        'event_id',
        'budget_id',
        'vendor_assigned_id',
        'category',
        'description',
        'estimated_cost',
        'approved_cost',
        'actual_cost',
        'quantity',
        'unit_cost',
        'tax',
        'discount',
        'notes',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'estimated_cost' => 'decimal:2',
            'approved_cost' => 'decimal:2',
            'actual_cost' => 'decimal:2',
            'quantity' => 'decimal:2',
            'unit_cost' => 'decimal:2',
            'tax' => 'decimal:2',
            'discount' => 'decimal:2',
            'status' => BudgetItemStatus::class,
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
     * @return BelongsTo<Budget, $this>
     */
    public function budget(): BelongsTo
    {
        return $this->belongsTo(Budget::class);
    }

    /**
     * @return BelongsTo<VendorAssignment, $this>
     */
    public function vendorAssignment(): BelongsTo
    {
        return $this->belongsTo(VendorAssignment::class, 'vendor_assigned_id');
    }
}
