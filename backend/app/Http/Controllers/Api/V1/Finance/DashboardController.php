<?php

namespace App\Http\Controllers\Api\V1\Finance;

use App\Enums\ExpenseStatus;
use App\Enums\InvoiceStatus;
use App\Enums\PaymentDirection;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Models\Budget;
use App\Models\Event;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Payment;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * The centralised Financial Dashboard: headline figures plus the datasets the
 * front-end renders as charts. Everything is scoped to the planner's events.
 */
class DashboardController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $plannerId = $request->user()->id;

        return $this->success([
            'cards' => $this->cards($plannerId),
            'charts' => [
                'budget_vs_actual' => $this->budgetVsActual($plannerId),
                'monthly_expenses' => $this->monthlyExpenses($plannerId),
                'payment_status' => $this->paymentStatus($plannerId),
                'revenue_by_event' => $this->revenueByEvent($plannerId),
                'expense_categories' => $this->expenseCategories($plannerId),
                'cash_flow' => $this->cashFlow($plannerId),
            ],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function cards(int $plannerId): array
    {
        $eventIds = Event::where('planner_id', $plannerId)->pluck('id');

        $totalBudget = (float) Budget::whereIn('event_id', $eventIds)
            ->get()->sum(fn (Budget $b) => $b->activeTotal());
        $approvedBudget = (float) Budget::whereIn('event_id', $eventIds)
            ->whereIn('status', ['approved', 'locked'])->get()->sum(fn (Budget $b) => $b->activeTotal());

        $totalExpenses = (float) Expense::whereIn('event_id', $eventIds)->sum('total');
        $paidExpenses = (float) Expense::whereIn('event_id', $eventIds)->where('status', ExpenseStatus::Paid->value)->sum('total');

        $invoices = Invoice::where('planner_id', $plannerId)->whereNot('status', InvoiceStatus::Cancelled->value);
        $outstanding = (float) (clone $invoices)->sum(DB::raw('total - amount_paid'));

        $received = (float) Payment::where('planner_id', $plannerId)
            ->where('direction', PaymentDirection::Incoming->value)
            ->where('status', PaymentStatus::Completed->value)->sum('amount');
        $paidOut = (float) Payment::where('planner_id', $plannerId)
            ->where('direction', PaymentDirection::Outgoing->value)
            ->where('status', PaymentStatus::Completed->value)->sum('amount');

        $vendorDue = (float) Expense::whereIn('event_id', $eventIds)
            ->whereIn('status', [ExpenseStatus::Submitted->value, ExpenseStatus::Approved->value])
            ->sum('total');

        return [
            'total_budget' => $totalBudget,
            'approved_budget' => $approvedBudget,
            'total_expenses' => $totalExpenses,
            'paid_expenses' => $paidExpenses,
            'outstanding_payments' => $outstanding,
            'client_payments_received' => $received,
            'vendor_payments_due' => $vendorDue,
            'profit_loss' => $received - $paidOut - $paidExpenses,
            'budget_utilization' => $totalBudget > 0 ? round($totalExpenses / $totalBudget * 100, 1) : 0.0,
        ];
    }

    /**
     * Estimated vs actual per event (top 6 by budget).
     *
     * @return array<int, array<string, mixed>>
     */
    private function budgetVsActual(int $plannerId): array
    {
        return Event::where('planner_id', $plannerId)
            ->with('budgetItems')
            ->get()
            ->map(fn (Event $e) => [
                'label' => $e->title,
                'estimated' => (float) $e->budgetItems->sum('estimated_cost'),
                'actual' => (float) $e->budgetItems->sum('actual_cost'),
            ])
            ->filter(fn ($row) => $row['estimated'] > 0 || $row['actual'] > 0)
            ->sortByDesc('estimated')
            ->take(6)
            ->values()
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function monthlyExpenses(int $plannerId): array
    {
        $eventIds = Event::where('planner_id', $plannerId)->pluck('id');

        $rows = Expense::whereIn('event_id', $eventIds)
            ->where('expense_date', '>=', now()->subMonths(5)->startOfMonth())
            ->selectRaw("DATE_FORMAT(expense_date, '%Y-%m') as ym, SUM(total) as amount")
            ->groupBy('ym')->pluck('amount', 'ym');

        return $this->fillMonths(fn (string $ym) => (float) ($rows[$ym] ?? 0));
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function paymentStatus(int $plannerId): array
    {
        $counts = Invoice::where('planner_id', $plannerId)
            ->selectRaw('status, COUNT(*) as c')->groupBy('status')->pluck('c', 'status');

        return collect(InvoiceStatus::cases())
            ->map(fn (InvoiceStatus $s) => [
                'label' => $s->label(),
                'value' => (int) ($counts[$s->value] ?? 0),
                'key' => $s->value,
            ])
            ->filter(fn ($row) => $row['value'] > 0)
            ->values()->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function revenueByEvent(int $plannerId): array
    {
        return Payment::where('planner_id', $plannerId)
            ->where('direction', PaymentDirection::Incoming->value)
            ->where('status', PaymentStatus::Completed->value)
            ->whereNotNull('event_id')
            ->with('event')
            ->get()
            ->groupBy('event_id')
            ->map(fn ($group) => [
                'label' => $group->first()->event?->title ?? 'Unlinked',
                'value' => (float) $group->sum('amount'),
            ])
            ->sortByDesc('value')->take(6)->values()->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function expenseCategories(int $plannerId): array
    {
        $eventIds = Event::where('planner_id', $plannerId)->pluck('id');

        return Expense::whereIn('event_id', $eventIds)
            ->selectRaw('category, SUM(total) as amount')
            ->groupBy('category')
            ->orderByDesc('amount')
            ->get()
            ->map(fn ($row) => ['label' => $row->category, 'value' => (float) $row->amount])
            ->all();
    }

    /**
     * Incoming vs outgoing money over the last six months.
     *
     * @return array<int, array<string, mixed>>
     */
    private function cashFlow(int $plannerId): array
    {
        $rows = Payment::where('planner_id', $plannerId)
            ->where('status', PaymentStatus::Completed->value)
            ->where('paid_at', '>=', now()->subMonths(5)->startOfMonth())
            ->selectRaw("DATE_FORMAT(paid_at, '%Y-%m') as ym, direction, SUM(amount) as amount")
            ->groupBy('ym', 'direction')->get();

        return $this->fillMonths(function (string $ym) use ($rows) {
            $inflow = (float) $rows->where('ym', $ym)->where('direction', PaymentDirection::Incoming->value)->sum('amount');
            $outflow = (float) $rows->where('ym', $ym)->where('direction', PaymentDirection::Outgoing->value)->sum('amount');

            return ['inflow' => $inflow, 'outflow' => $outflow];
        });
    }

    /**
     * Build a six-month series, calling $value($ym) for each month bucket.
     *
     * @param  callable(string):(float|array<string,float>)  $value
     * @return array<int, array<string, mixed>>
     */
    private function fillMonths(callable $value): array
    {
        $out = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = now()->subMonths($i);
            $ym = $month->format('Y-m');
            $resolved = $value($ym);
            $out[] = is_array($resolved)
                ? ['label' => $month->format('M'), ...$resolved]
                : ['label' => $month->format('M'), 'value' => $resolved];
        }

        return $out;
    }
}
