<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\InvoiceItem
 */
class InvoiceItemResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'description' => $this->description,
            'quantity' => (float) $this->quantity,
            'unit_price' => (float) $this->unit_price,
            'tax' => (float) $this->tax,
            'discount' => (float) $this->discount,
            'amount' => (float) $this->amount,
            'line_total' => (float) $this->amount + (float) $this->tax - (float) $this->discount,
            'sort_order' => $this->sort_order,
        ];
    }
}
