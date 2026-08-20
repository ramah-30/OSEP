<?php

namespace App\Http\Controllers\Api\V1\Marketplace\Concerns;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Uniform pagination envelope so every marketplace list endpoint hands the SPA
 * the same `meta` shape alongside its resource collection.
 */
trait PaginatesResults
{
    /**
     * @return array<string, int>
     */
    protected function pageMeta(LengthAwarePaginator $paginator): array
    {
        return [
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
        ];
    }
}
