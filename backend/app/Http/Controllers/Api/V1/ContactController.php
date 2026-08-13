<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\ContactRequest;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class ContactController extends Controller
{
    use ApiResponse;

    /**
     * Phase 1 records enquiries to the log. Phase 2 swaps this for a
     * persisted inbox without changing the contract the SPA speaks.
     */
    public function store(ContactRequest $request): JsonResponse
    {
        Log::channel('single')->info('Contact enquiry', $request->validated() + [
            'ip' => $request->ip(),
        ]);

        return $this->success(null, 'Thanks for reaching out. Our team will reply within one business day.');
    }
}
