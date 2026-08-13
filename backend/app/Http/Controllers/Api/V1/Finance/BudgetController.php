<?php

namespace App\Http\Controllers\Api\V1\Finance;

use App\Enums\BudgetItemStatus;
use App\Enums\BudgetStatus;
use App\Http\Controllers\Api\V1\Concerns\AuthorizesEventAccess;
use App\Http\Controllers\Controller;
use App\Http\Resources\BudgetItemResource;
use App\Http\Resources\BudgetResource;
use App\Models\Budget;
use App\Models\BudgetItem;
use App\Models\Event;
use App\Services\ActivityLogger;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BudgetController extends Controller
{
    use ApiResponse, AuthorizesEventAccess;

    public function __construct(private readonly ActivityLogger $activity) {}

    /** Every budget across the planner's events, with rolled-up figures. */
    public function index(Request $request): JsonResponse
    {
        $budgets = Budget::whereHas('event', fn ($q) => $q->where('planner_id', $request->user()->id))
            ->with(['event', 'items'])
            ->latest()
            ->get();

        return $this->success([
            'budgets' => $budgets->map(fn (Budget $b) => [
                ...(new BudgetResource($b->loadMissing('event')))->resolve($request),
                'summary' => $this->summary($b),
            ]),
        ]);
    }

    /** The master budget for one event (created on demand), with line items. */
    public function show(Request $request, Event $event): JsonResponse
    {
        $this->ensurePlannerOwns($request, $event);

        $budget = $this->resolveBudget($event);
        $budget->load(['items' => fn ($q) => $q->latest(), 'event']);

        return $this->success([
            'budget' => new BudgetResource($budget),
            'summary' => $this->summary($budget),
        ]);
    }

    /** Create or update the master budget figures / notes. */
    public function upsert(Request $request, Event $event): JsonResponse
    {
        $this->ensurePlannerOwns($request, $event);

        $data = $request->validate([
            'currency' => ['nullable', 'string', 'size:3'],
            'estimated_total' => ['nullable', 'numeric', 'min:0'],
            'revised_total' => ['nullable', 'numeric', 'min:0'],
            'final_total' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $budget = $this->resolveBudget($event);
        abort_if(! $budget->status->isEditable() && $request->has('estimated_total'), 422, 'This budget is locked and can no longer be edited.');

        $budget->fill($data)->save();
        $this->syncEventTotal($event, $budget);

        $this->activity->log($event, $request->user(), 'budget_updated', 'updated the master budget', $budget);

        return $this->success([
            'budget' => new BudgetResource($budget->load(['items', 'event'])),
            'summary' => $this->summary($budget),
        ], 'Budget saved.');
    }

    /** Move the budget through its approval workflow. */
    public function transition(Request $request, Event $event): JsonResponse
    {
        $this->ensurePlannerOwns($request, $event);

        $data = $request->validate([
            'action' => ['required', Rule::in(['submit', 'approve', 'lock', 'archive', 'reopen'])],
        ]);

        $budget = $this->resolveBudget($event);

        [$status, $extra, $verb] = match ($data['action']) {
            'submit' => [BudgetStatus::PendingApproval, [], 'submitted the budget for approval'],
            'approve' => [BudgetStatus::Approved, ['approved_by' => $request->user()->id, 'approved_at' => now()], 'approved the budget'],
            'lock' => [BudgetStatus::Locked, ['locked_at' => now()], 'locked the budget'],
            'archive' => [BudgetStatus::Archived, [], 'archived the budget'],
            'reopen' => [BudgetStatus::Draft, ['approved_by' => null, 'approved_at' => null, 'locked_at' => null], 'reopened the budget'],
        };

        $budget->fill(['status' => $status->value, ...$extra])->save();
        $this->activity->log($event, $request->user(), 'budget_updated', $verb, $budget);

        return $this->success([
            'budget' => new BudgetResource($budget->load(['items', 'event'])),
            'summary' => $this->summary($budget),
        ], 'Budget '.$status->label().'.');
    }

    public function storeItem(Request $request, Event $event): JsonResponse
    {
        $this->ensurePlannerOwns($request, $event);
        $budget = $this->resolveBudget($event);

        $item = $budget->items()->create([
            ...$this->validateItem($request),
            'event_id' => $event->id,
        ]);

        $event->recalculateBudgetSpent();
        $this->activity->log($event, $request->user(), 'budget_updated', "added budget line \"{$item->category}\"", $item);

        return $this->created([
            'item' => new BudgetItemResource($item),
            'summary' => $this->summary($budget->refresh()),
        ], 'Budget line added.');
    }

    public function updateItem(Request $request, Event $event, BudgetItem $item): JsonResponse
    {
        $this->ensurePlannerOwns($request, $event);
        $this->ensureBelongsToEvent($event, $item);

        $item->fill($this->validateItem($request))->save();
        $event->recalculateBudgetSpent();

        return $this->success([
            'item' => new BudgetItemResource($item),
            'summary' => $this->summary($this->resolveBudget($event)->refresh()),
        ], 'Budget line updated.');
    }

    public function destroyItem(Request $request, Event $event, BudgetItem $item): JsonResponse
    {
        $this->ensurePlannerOwns($request, $event);
        $this->ensureBelongsToEvent($event, $item);

        $item->delete();
        $event->recalculateBudgetSpent();

        return $this->success([
            'summary' => $this->summary($this->resolveBudget($event)->refresh()),
        ], 'Budget line deleted.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validateItem(Request $request): array
    {
        return $request->validate([
            'category' => ['required', 'string', 'max:100'],
            'description' => ['required', 'string', 'max:255'],
            'estimated_cost' => ['required', 'numeric', 'min:0'],
            'approved_cost' => ['nullable', 'numeric', 'min:0'],
            'actual_cost' => ['nullable', 'numeric', 'min:0'],
            'quantity' => ['nullable', 'numeric', 'min:0'],
            'unit_cost' => ['nullable', 'numeric', 'min:0'],
            'tax' => ['nullable', 'numeric', 'min:0'],
            'discount' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'status' => ['nullable', Rule::enum(BudgetItemStatus::class)],
            'vendor_assigned_id' => ['nullable', 'integer', Rule::exists('vendors_assigned', 'id')->where('event_id', $request->route('event')->id)],
        ]);
    }

    private function resolveBudget(Event $event): Budget
    {
        return $event->budget()->firstOrCreate([], [
            'currency' => 'TZS',
            'estimated_total' => $event->budget_total ?? 0,
            'status' => BudgetStatus::Draft->value,
        ]);
    }

    /** Mirror the active budget total onto the event's stored figure. */
    private function syncEventTotal(Event $event, Budget $budget): void
    {
        $event->forceFill(['budget_total' => $budget->activeTotal()])->save();
    }

    /**
     * @return array<string, float>
     */
    private function summary(Budget $budget): array
    {
        $items = $budget->relationLoaded('items') ? $budget->items : $budget->items()->get();

        $estimated = (float) $items->sum('estimated_cost');
        $approved = (float) $items->sum('approved_cost');
        $actual = (float) $items->sum('actual_cost');
        $total = $budget->activeTotal();

        return [
            'budget_total' => $total,
            'estimated' => $estimated,
            'approved' => $approved,
            'actual' => $actual,
            'variance' => $actual - $estimated,
            'remaining' => $total - $actual,
            'utilization' => $total > 0 ? round($actual / $total * 100, 1) : 0.0,
        ];
    }
}
