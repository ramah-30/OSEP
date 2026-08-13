<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClientQuotationItem extends Model
{
    protected $fillable = [
        'client_quotation_id',
        'description',
        'quantity',
        'unit_price',
        'tax',
        'discount',
        'amount',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'unit_price' => 'decimal:2',
            'tax' => 'decimal:2',
            'discount' => 'decimal:2',
            'amount' => 'decimal:2',
            'sort_order' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<ClientQuotation, $this>
     */
    public function quotation(): BelongsTo
    {
        return $this->belongsTo(ClientQuotation::class, 'client_quotation_id');
    }
}
