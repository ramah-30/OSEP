<?php

namespace App\Models;

use App\Enums\BudgetStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Budget extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'event_id',
        'currency',
        'estimated_total',
        'revised_total',
        'final_total',
        'status',
        'notes',
        'approved_by',
        'approved_at',
        'locked_at',
    ];

    protected function casts(): array
    {
        return [
            'estimated_total' => 'decimal:2',
            'revised_total' => 'decimal:2',
            'final_total' => 'decimal:2',
            'status' => BudgetStatus::class,
            'approved_at' => 'datetime',
            'locked_at' => 'datetime',
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
     * @return HasMany<BudgetItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(BudgetItem::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /** The headline figure: the latest stage that has been set. */
    public function activeTotal(): float
    {
        return (float) ($this->final_total ?? $this->revised_total ?? $this->estimated_total);
    }
}
