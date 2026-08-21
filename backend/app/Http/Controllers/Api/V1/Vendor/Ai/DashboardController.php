<?php

namespace App\Http\Controllers\Api\V1\Vendor\Ai;

use App\Http\Controllers\Controller;
use App\Http\Resources\AiConversationResource;
use App\Models\AiConversation;
use App\Services\AI\AiManager;
use App\Services\AI\Vendor\VendorContextBuilder;
use App\Services\AI\Vendor\VendorReminderEngine;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * The vendor AI dashboard: an at-a-glance snapshot of the business (pipeline,
 * quotations, revenue, rating) plus the copilot's prioritised reminders and
 * recent conversations - the vendor's home for their AI assistant.
 */
class DashboardController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly VendorContextBuilder $contextBuilder,
        private readonly VendorReminderEngine $reminders,
        private readonly AiManager $ai,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $vendor = $request->user();
        $context = $this->contextBuilder->forVendor($vendor);

        $conversations = AiConversation::where('user_id', $vendor->id)
            ->with('latestMessage')
            ->orderByDesc('pinned')
            ->orderByDesc('last_message_at')
            ->limit(5)
            ->get();

        $reminders = $this->reminders->fromContext($context);

        $requests = $context['requests'] ?? [];
        $quotations = $context['quotations'] ?? [];
        $contracts = $context['contracts'] ?? [];
        $reviews = $context['reviews'] ?? [];

        return $this->success([
            'assistant_name' => config('ai.vendor_assistant_name', 'OSEP Vendor Copilot'),
            'driver' => $this->ai->driver(),
            'is_live' => $this->ai->isLive(),
            'stats' => [
                'open_requests' => $requests['open'] ?? 0,
                'open_quotations' => $quotations['open'] ?? 0,
                'win_rate' => $quotations['win_rate'] ?? null,
                'revenue' => $contracts['revenue'] ?? 0,
                'rating' => $reviews['average_rating'] ?? null,
                'reminders' => count($reminders),
            ],
            'reminders' => $reminders,
            'conversations' => AiConversationResource::collection($conversations),
        ]);
    }
}
