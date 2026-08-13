<?php

namespace App\Models;

use App\Enums\ExpenseStatus;
use App\Enums\PaymentMethod;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Expense extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'expense_number',
        'event_id',
        'vendor_assigned_id',
        'budget_item_id',
        'category',
        'description',
        'amount',
        'tax',
        'total',
        'currency',
        'payment_method',
        'status',
        'expense_date',
        'receipt_path',
        'notes',
        'submitted_by',
        'approved_by',
        'approved_at',
        'rejected_reason',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'tax' => 'decimal:2',
            'total' => 'decimal:2',
            'status' => ExpenseStatus::class,
            'payment_method' => PaymentMethod::class,
            'expense_date' => 'date',
            'approved_at' => 'datetime',
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
     * @return BelongsTo<VendorAssignment, $this>
     */
    public function vendorAssignment(): BelongsTo
    {
        return $this->belongsTo(VendorAssignment::class, 'vendor_assigned_id');
    }

    /**
     * @return BelongsTo<BudgetItem, $this>
     */
    public function budgetItem(): BelongsTo
    {
        return $this->belongsTo(BudgetItem::class);
    }
}
