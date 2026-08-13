<?php

namespace App\Http\Controllers\Api\V1\Marketplace\Concerns;

use Illuminate\Support\Str;

/**
 * Human-readable, collision-checked references for quotations and contracts,
 * e.g. QUO-2026-8F3K2P. The random suffix keeps them unguessable without a
 * running counter.
 */
trait GeneratesReference
{
    /**
     * @param  class-string<\Illuminate\Database\Eloquent\Model>  $model
     */
    protected function generateReference(string $prefix, string $model): string
    {
        do {
            $reference = sprintf('%s-%s-%s', $prefix, now()->format('Y'), Str::upper(Str::random(6)));
        } while ($model::where('reference', $reference)->exists());

        return $reference;
    }
}
