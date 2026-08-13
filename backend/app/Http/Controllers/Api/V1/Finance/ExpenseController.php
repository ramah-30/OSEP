<?php

namespace App\Http\Controllers\Api\V1\Finance;

use App\Enums\ExpenseStatus;
use App\Enums\PaymentMethod;
use App\Http\Controllers\Api\V1\Finance\Concerns\HandlesFinance;
use App\Http\Controllers\Controller;
use App\Http\Resources\ExpenseResource;
use App\Models\Expense;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ExpenseController extends Controller
{
    use ApiResponse, HandlesFinance;

    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'event_id' => ['nullable', 'integer'],
            'status' => ['nullable', Rule::enum(ExpenseStatus::class)],
            'category' => ['nullable', 'string'],
            'search' => ['nullable', 'string'],
        ]);

        $expenses = Expense::query()
            ->whereHas('event', fn ($q) => $q->where('planner_id', $request->user()->id))
            ->when($filters['event_id'] ?? null, fn ($q, $id) => $q->where('event_id', $id))
            ->when($filters['status'] ?? null, fn ($q, $s) => $q->where('status', $s))
            ->when($filters['category'] ?? null, fn ($q, $c) => $q->where('category', $c))
            ->when($filters['search'] ?? null, fn ($q, $s) => $q->where(fn ($w) => $w
                ->where('description', 'like', "%{$s}%")
                ->orWhere('expense_number', 'like', "%{$s}%")))
            ->with(['event', 'vendorAssignment'])
            ->latest()
            ->get();

        return $this->success([
            'expenses' => ExpenseResource::collection($expenses),
            'summary' => $this->summary($request),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validateExpense($request);
        $event = $this->ownedEvent($request, $data['event_id']);

        $expense = Expense::create([
            ...$data,
            'expense_number' => $this->nextNumber('EXP', Expense::class, 'expense_number'),
            'total' => (float) $data['amount'] + (float) ($data['tax'] ?? 0),
            'status' => $data['status'] ?? ExpenseStatus::Draft->value,
            'submitted_by' => $request->user()->id,
        ]);

        $this->logFinance($event, $request, 'expense_added', "logged expense {$expense->expense_number}", $expense);

        return $this->created([
            'expense' => new ExpenseResource($expense->load(['event', 'vendorAssignment'])),
        ], 'Expense recorded.');
    }

    public function show(Request $request, Expense $expense): JsonResponse
    {
        $this->ownedEvent($request, $expense->event_id);

        return $this->success([
            'expense' => new ExpenseResource($expense->load(['event', 'vendorAssignment', 'budgetItem'])),
        ]);
    }

    public function update(Request $request, Expense $expense): JsonResponse
    {
        $this->ownedEvent($request, $expense->event_id);
        abort_if($expense->status === ExpenseStatus::Paid, 422, 'A paid expense can no longer be edited.');

        $data = $this->validateExpense($request);
        $this->ownedEvent($request, $data['event_id']);

        $expense->fill([
            ...$data,
            'total' => (float) $data['amount'] + (float) ($data['tax'] ?? 0),
        ])->save();

        return $this->success([
            'expense' => new ExpenseResource($expense->load(['event', 'vendorAssignment'])),
        ], 'Expense updated.');
    }

    public function destroy(Request $request, Expense $expense): JsonResponse
    {
        $this->ownedEvent($request, $expense->event_id);
        $expense->delete();

        return $this->success(null, 'Expense deleted.');
    }

    /** Advance the expense through submit → approve → paid, or reject it. */
    public function transition(Request $request, Expense $expense): JsonResponse
    {
        $event = $this->ownedEvent($request, $expense->event_id);

        $data = $request->validate([
            'action' => ['required', Rule::in(['submit', 'approve', 'reject', 'pay'])],
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        switch ($data['action']) {
            case 'submit':
                $expense->update(['status' => ExpenseStatus::Submitted->value]);
                $verb = 'submitted for approval';
                break;
            case 'approve':
                $expense->update(['status' => ExpenseStatus::Approved->value, 'approved_by' => $request->user()->id, 'approved_at' => now()]);
                $verb = 'approved';
                break;
            case 'reject':
                $expense->update(['status' => ExpenseStatus::Rejected->value, 'rejected_reason' => $data['reason'] ?? null]);
                $verb = 'rejected';
                break;
            case 'pay':
                $expense->update(['status' => ExpenseStatus::Paid->value]);
                $verb = 'marked paid';
                break;
        }

        $this->logFinance($event, $request, 'expense_'.$data['action'], "expense {$expense->expense_number} {$verb}", $expense);

        return $this->success([
            'expense' => new ExpenseResource($expense->load(['event', 'vendorAssignment'])),
        ], "Expense {$verb}.");
    }

    /**
     * @return array<string, mixed>
     */
    private function validateExpense(Request $request): array
    {
        return $request->validate([
            'event_id' => ['required', 'integer'],
            'vendor_assigned_id' => ['nullable', 'integer', 'exists:vendors_assigned,id'],
            'budget_item_id' => ['nullable', 'integer', 'exists:budget_items,id'],
            'category' => ['required', 'string', 'max:100'],
            'description' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0'],
            'tax' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'size:3'],
            'payment_method' => ['nullable', Rule::enum(PaymentMethod::class)],
            'status' => ['nullable', Rule::enum(ExpenseStatus::class)],
            'expense_date' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);
    }

    /**
     * @return array<string, float|int>
     */
    private function summary(Request $request): array
    {
        $base = Expense::whereHas('event', fn ($q) => $q->where('planner_id', $request->user()->id));

        return [
            'total' => (float) (clone $base)->sum('total'),
            'paid' => (float) (clone $base)->where('status', ExpenseStatus::Paid->value)->sum('total'),
            'pending' => (float) (clone $base)->whereIn('status', [ExpenseStatus::Submitted->value, ExpenseStatus::Approved->value])->sum('total'),
            'count' => (clone $base)->count(),
        ];
    }
}
