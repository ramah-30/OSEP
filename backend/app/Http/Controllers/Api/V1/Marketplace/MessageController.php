<?php

namespace App\Http\Controllers\Api\V1\Marketplace;

use App\Http\Controllers\Api\V1\Marketplace\Concerns\ResolvesProvider;
use App\Http\Controllers\Controller;
use App\Http\Resources\MarketplaceMessageResource;
use App\Http\Resources\MessageThreadResource;
use App\Models\MarketplaceVenue;
use App\Models\MessageThread;
use App\Models\Notification;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Secure messaging between planners and vendors/venues. Shared by both sides -
 * a user may only touch a thread they participate in (the planner, the vendor,
 * or the venue's owner).
 */
class MessageController extends Controller
{
    use ApiResponse, ResolvesProvider;

    public function index(Request $request): JsonResponse
    {
        $threads = $this->participatingThreads($request)
            ->with(['planner', 'vendor.vendorProfile', 'venue', 'event'])
            ->withCount(['messages as unread_count' => fn ($q) => $q
                ->whereNull('read_at')->where('sender_id', '!=', $request->user()->id)])
            ->orderByDesc('last_message_at')
            ->get();

        return $this->success([
            'threads' => MessageThreadResource::collection($threads),
        ]);
    }

    public function show(Request $request, MessageThread $thread): JsonResponse
    {
        $this->authorizeParticipant($request, $thread);

        // Mark the other party's messages as read.
        $thread->messages()->whereNull('read_at')
            ->where('sender_id', '!=', $request->user()->id)
            ->update(['read_at' => now()]);

        $thread->load(['planner', 'vendor.vendorProfile', 'venue', 'event', 'messages.sender', 'messages.quotation.items']);

        return $this->success([
            'thread' => new MessageThreadResource($thread),
        ]);
    }

    /** Planner starts (or reuses) a conversation with a provider. */
    public function start(Request $request): JsonResponse
    {
        $data = $request->validate([
            'provider_type' => ['required', Rule::in(['vendor', 'venue'])],
            'provider_id' => ['required', 'integer'],
            'subject' => ['nullable', 'string', 'max:150'],
            'event_id' => ['nullable', 'integer', 'exists:events,id'],
            'booking_request_id' => ['nullable', 'integer', 'exists:booking_requests,id'],
            'body' => ['required', 'string', 'max:5000'],
        ]);

        $provider = $this->resolveProvider($data['provider_type'], (int) $data['provider_id']);

        $thread = MessageThread::firstOrCreate(
            [
                'planner_id' => $request->user()->id,
                'vendor_id' => $provider['vendor_id'],
                'venue_id' => $provider['venue_id'],
            ],
            [
                'subject' => $data['subject'] ?? null,
                'event_id' => $data['event_id'] ?? null,
                'booking_request_id' => $data['booking_request_id'] ?? null,
            ],
        );

        $this->appendMessage($thread, $request->user()->id, $data['body']);

        $thread->load(['planner', 'vendor.vendorProfile', 'venue', 'event', 'messages.sender']);

        return $this->created([
            'thread' => new MessageThreadResource($thread),
        ], 'Message sent.');
    }

    public function send(Request $request, MessageThread $thread): JsonResponse
    {
        $this->authorizeParticipant($request, $thread);

        $data = $request->validate([
            'body' => ['required_without:attachments', 'nullable', 'string', 'max:5000'],
            'attachments' => ['nullable', 'array'],
        ]);

        $message = $this->appendMessage($thread, $request->user()->id, $data['body'] ?? null, $data['attachments'] ?? null);

        return $this->created([
            'message' => new MarketplaceMessageResource($message->load('sender')),
        ], 'Message sent.');
    }

    private function appendMessage(MessageThread $thread, int $senderId, ?string $body, ?array $attachments = null)
    {
        $message = $thread->messages()->create([
            'sender_id' => $senderId,
            'body' => $body,
            'attachments' => $attachments,
        ]);

        $thread->update(['last_message_at' => now()]);
        $this->notifyCounterparty($thread, $senderId);

        return $message;
    }

    private function notifyCounterparty(MessageThread $thread, int $senderId): void
    {
        $recipientId = $senderId === $thread->planner_id
            ? ($thread->vendor_id ?? MarketplaceVenue::whereKey($thread->venue_id)->value('owner_id'))
            : $thread->planner_id;

        if (! $recipientId) {
            return;
        }

        Notification::create([
            'user_id' => $recipientId,
            'type' => 'message',
            'title' => 'New message',
            'message' => 'You have a new marketplace message.',
            'data' => ['thread_id' => $thread->id],
        ]);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Builder<MessageThread>
     */
    private function participatingThreads(Request $request)
    {
        $userId = $request->user()->id;

        return MessageThread::query()->where(fn ($q) => $q
            ->where('planner_id', $userId)
            ->orWhere('vendor_id', $userId)
            ->orWhereIn('venue_id', MarketplaceVenue::where('owner_id', $userId)->select('id')));
    }

    private function authorizeParticipant(Request $request, MessageThread $thread): void
    {
        $userId = $request->user()->id;

        $isParticipant = $thread->planner_id === $userId
            || $thread->vendor_id === $userId
            || ($thread->venue_id && MarketplaceVenue::whereKey($thread->venue_id)->where('owner_id', $userId)->exists());

        abort_unless($isParticipant, 404);
    }
}
