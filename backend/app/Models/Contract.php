<?php

namespace App\Models;

use App\Enums\ContractStatus;
use App\Models\Concerns\BelongsToProvider;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Contract extends Model
{
    use BelongsToProvider;

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
            'amount' => 'decimal:2',
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
}
