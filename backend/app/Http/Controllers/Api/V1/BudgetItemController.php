<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\BudgetItemStatus;
use App\Http\Controllers\Api\V1\Concerns\AuthorizesEventAccess;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBudgetItemRequest;
use App\Http\Requests\UpdateBudgetItemRequest;
use App\Http\Resources\BudgetItemResource;
use App\Models\BudgetItem;
use App\Models\Event;
use App\Services\ActivityLogger;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BudgetItemController extends Controller
{
    use ApiResponse, AuthorizesEventAccess;

    public function __construct(private readonly ActivityLogger $activity) {}

    public function index(Request $request, Event $event): JsonResponse
    {
        $this->ensurePlannerOwns($request, $event);

        $items = $event->budgetItems()->with('vendorAssignment')->latest()->get();

        return $this->success([
            'budget_items' => BudgetItemResource::collection($items),
            'summary' => $this->summary($event, $items),
        ]);
    }

    public function store(StoreBudgetItemRequest $request, Event $event): JsonResponse
    {
        $this->ensurePlannerOwns($request, $event);

        $item = $event->budgetItems()->create([
            ...$request->validated(),
            'status' => $request->validated()['status'] ?? BudgetItemStatus::Planned->value,
        ]);

        $event->recalculateBudgetSpent();
        $this->activity->log($event, $request->user(), 'budget_updated', "added a budget line for {$item->category}", $item);

        return $this->created([
            'budget_item' => new BudgetItemResource($item),
            'summary' => $this->summary($event->refresh(), $event->budgetItems()->get()),
        ], 'Budget item added.');
    }

    public function update(UpdateBudgetItemRequest $request, Event $event, BudgetItem $item): JsonResponse
    {
        $this->ensurePlannerOwns($request, $event);
        $this->ensureBelongsToEvent($event, $item);

        $item->fill($request->validated())->save();
        $event->recalculateBudgetSpent();

        return $this->success([
            'budget_item' => new BudgetItemResource($item),
            'summary' => $this->summary($event->refresh(), $event->budgetItems()->get()),
        ], 'Budget item updated.');
    }

    public function destroy(Request $request, Event $event, BudgetItem $item): JsonResponse
    {
        $this->ensurePlannerOwns($request, $event);
        $this->ensureBelongsToEvent($event, $item);

        $item->delete();
        $event->recalculateBudgetSpent();

        return $this->success([
            'summary' => $this->summary($event->refresh(), $event->budgetItems()->get()),
        ], 'Budget item deleted.');
    }

    /**
     * @param  \Illuminate\Support\Collection<int, BudgetItem>  $items
     * @return array<string, float>
     */
    private function summary(Event $event, $items): array
    {
        $estimated = (float) $items->sum('estimated_cost');
        $actual = (float) $items->sum('actual_cost');
        $total = (float) $event->budget_total;

        return [
            'budget_total' => $total,
            'estimated' => $estimated,
            'actual' => $actual,
            'remaining' => $total - $actual,
        ];
    }
}
