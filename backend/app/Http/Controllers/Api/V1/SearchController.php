<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\AccountType;
use App\Http\Controllers\Controller;
use App\Models\EventTask;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Global planner search across their events, clients, tasks and the vendor
 * directory. Returns a small grouped set for a command-palette style dropdown.
 */
class SearchController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $term = trim((string) $request->query('q', ''));

        if (mb_strlen($term) < 2) {
            return $this->success(['events' => [], 'clients' => [], 'tasks' => [], 'vendors' => []]);
        }

        $like = "%{$term}%";
        $eventIds = $user->plannedEvents()->pluck('id');

        $events = $user->plannedEvents()
            ->where(fn ($q) => $q->where('title', 'like', $like)->orWhere('event_code', 'like', $like))
            ->limit(6)->get()
            ->map(fn ($e) => ['id' => $e->id, 'title' => $e->title, 'subtitle' => $e->event_code, 'status' => $e->status->value]);

        $clientIds = $user->plannedEvents()->whereNotNull('client_id')->pluck('client_id')->unique();
        $clients = User::whereIn('id', $clientIds)
            ->where(fn ($q) => $q->where('first_name', 'like', $like)->orWhere('last_name', 'like', $like)->orWhere('email', 'like', $like))
            ->limit(6)->get()
            ->map(fn ($c) => ['id' => $c->id, 'title' => $c->full_name, 'subtitle' => $c->email]);

        $tasks = EventTask::whereIn('event_id', $eventIds)
            ->where('title', 'like', $like)
            ->with('event:id,title')
            ->limit(6)->get()
            ->map(fn ($t) => ['id' => $t->id, 'title' => $t->title, 'subtitle' => $t->event?->title, 'event_id' => $t->event_id]);

        $vendors = User::where('account_type', AccountType::Vendor->value)
            ->whereHas('vendorProfile', fn ($q) => $q->where('business_name', 'like', $like)->orWhere('category', 'like', $like))
            ->with('vendorProfile')
            ->limit(6)->get()
            ->map(fn ($v) => ['id' => $v->id, 'title' => $v->vendorProfile?->business_name ?? $v->full_name, 'subtitle' => $v->vendorProfile?->category]);

        return $this->success([
            'events' => $events,
            'clients' => $clients,
            'tasks' => $tasks,
            'vendors' => $vendors,
        ]);
    }
}
