<?php

namespace App\Http\Controllers\Api\V1\Finance;

use App\Enums\ExpenseStatus;
use App\Enums\InvoiceStatus;
use App\Enums\PaymentDirection;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Payment;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * On-demand financial reports. Each returns a title, column definitions and rows
 * so the SPA can render a table and export it to CSV / print to PDF.
 */
class ReportController extends Controller
{
    use ApiResponse;

    public function show(Request $request, string $type): JsonResponse
    {
        $plannerId = $request->user()->id;
        $eventIds = Event::where('planner_id', $plannerId)->pluck('id');

        $report = match ($type) {
            'budget' => $this->budget($eventIds),
            'expense' => $this->expense($eventIds),
            'revenue' => $this->revenue($plannerId),
            'vendor-payments' => $this->vendorPayments($plannerId),
            'outstanding' => $this->outstanding($plannerId),
            'profit-loss' => $this->profitLoss($plannerId, $eventIds),
            'event-summary' => $this->eventSummary($plannerId),
            default => abort(404, 'Unknown report type.'),
        };

        return $this->success(['report' => $report]);
    }

    /**
     * @param  Collection<int, int>  $eventIds
     * @return array<string, mixed>
     */
    private function budget($eventIds): array
    {
        $rows = Event::whereIn('id', $eventIds)->with('budgetItems')->get()
            ->map(fn (Event $e) => [
                'event' => $e->title,
                'estimated' => (float) $e->budgetItems->sum('estimated_cost'),
                'approved' => (float) $e->budgetItems->sum('approved_cost'),
                'actual' => (float) $e->budgetItems->sum('actual_cost'),
                'variance' => (float) $e->budgetItems->sum('actual_cost') - (float) $e->budgetItems->sum('estimated_cost'),
            ])->values()->all();

        return [
            'title' => 'Budget Report',
            'columns' => [
                ['key' => 'event', 'label' => 'Event'],
                ['key' => 'estimated', 'label' => 'Estimated', 'money' => true],
                ['key' => 'approved', 'label' => 'Approved', 'money' => true],
                ['key' => 'actual', 'label' => 'Actual', 'money' => true],
                ['key' => 'variance', 'label' => 'Variance', 'money' => true],
            ],
            'rows' => $rows,
        ];
    }

    private function expense($eventIds): array
    {
        $rows = Expense::whereIn('event_id', $eventIds)->with('event')->latest('expense_date')->get()
            ->map(fn (Expense $e) => [
                'number' => $e->expense_number,
                'date' => $e->expense_date?->toDateString(),
                'event' => $e->event?->title,
                'category' => $e->category,
                'status' => $e->status->label(),
                'total' => (float) $e->total,
            ])->all();

        return [
            'title' => 'Expense Report',
            'columns' => [
                ['key' => 'number', 'label' => 'Expense #'],
                ['key' => 'date', 'label' => 'Date'],
                ['key' => 'event', 'label' => 'Event'],
                ['key' => 'category', 'label' => 'Category'],
                ['key' => 'status', 'label' => 'Status'],
                ['key' => 'total', 'label' => 'Total', 'money' => true],
            ],
            'rows' => $rows,
        ];
    }

    private function revenue(int $plannerId): array
    {
        $rows = Payment::where('planner_id', $plannerId)
            ->where('direction', PaymentDirection::Incoming->value)
            ->where('status', PaymentStatus::Completed->value)
            ->with(['event', 'invoice'])->latest('paid_at')->get()
            ->map(fn (Payment $p) => [
                'number' => $p->payment_number,
                'date' => $p->paid_at?->toDateString(),
                'event' => $p->event?->title,
                'invoice' => $p->invoice?->invoice_number,
                'method' => $p->method->label(),
                'amount' => (float) $p->amount,
            ])->all();

        return [
            'title' => 'Revenue Report',
            'columns' => [
                ['key' => 'number', 'label' => 'Payment #'],
                ['key' => 'date', 'label' => 'Date'],
                ['key' => 'event', 'label' => 'Event'],
                ['key' => 'invoice', 'label' => 'Invoice'],
                ['key' => 'method', 'label' => 'Method'],
                ['key' => 'amount', 'label' => 'Amount', 'money' => true],
            ],
            'rows' => $rows,
        ];
    }

    private function vendorPayments(int $plannerId): array
    {
        $rows = Payment::where('planner_id', $plannerId)
            ->where('direction', PaymentDirection::Outgoing->value)
            ->with(['event', 'vendorAssignment'])->latest('paid_at')->get()
            ->map(fn (Payment $p) => [
                'number' => $p->payment_number,
                'date' => $p->paid_at?->toDateString(),
                'event' => $p->event?->title,
                'vendor' => $p->vendorAssignment?->vendor_name ?? $p->party_name,
                'status' => $p->status->label(),
                'amount' => (float) $p->amount,
            ])->all();

        return [
            'title' => 'Vendor Payment Report',
            'columns' => [
                ['key' => 'number', 'label' => 'Payment #'],
                ['key' => 'date', 'label' => 'Date'],
                ['key' => 'event', 'label' => 'Event'],
                ['key' => 'vendor', 'label' => 'Vendor'],
                ['key' => 'status', 'label' => 'Status'],
                ['key' => 'amount', 'label' => 'Amount', 'money' => true],
            ],
            'rows' => $rows,
        ];
    }

    private function outstanding(int $plannerId): array
    {
        $rows = Invoice::where('planner_id', $plannerId)
            ->whereIn('status', [InvoiceStatus::Sent->value, InvoiceStatus::PartiallyPaid->value, InvoiceStatus::Overdue->value])
            ->with(['client', 'event'])->get()
            ->map(fn (Invoice $i) => [
                'number' => $i->invoice_number,
                'client' => $i->client?->full_name,
                'event' => $i->event?->title,
                'due_date' => $i->due_date?->toDateString(),
                'status' => $i->status->label(),
                'balance' => $i->balance(),
            ])->all();

        return [
            'title' => 'Outstanding Payments',
            'columns' => [
                ['key' => 'number', 'label' => 'Invoice #'],
                ['key' => 'client', 'label' => 'Client'],
                ['key' => 'event', 'label' => 'Event'],
                ['key' => 'due_date', 'label' => 'Due date'],
                ['key' => 'status', 'label' => 'Status'],
                ['key' => 'balance', 'label' => 'Balance', 'money' => true],
            ],
            'rows' => $rows,
        ];
    }

    private function profitLoss(int $plannerId, $eventIds): array
    {
        $revenue = (float) Payment::where('planner_id', $plannerId)
            ->where('direction', PaymentDirection::Incoming->value)
            ->where('status', PaymentStatus::Completed->value)->sum('amount');
        $vendorCost = (float) Payment::where('planner_id', $plannerId)
            ->where('direction', PaymentDirection::Outgoing->value)
            ->where('status', PaymentStatus::Completed->value)->sum('amount');
        $expenses = (float) Expense::whereIn('event_id', $eventIds)->where('status', ExpenseStatus::Paid->value)->sum('total');

        return [
            'title' => 'Profit & Loss',
            'columns' => [
                ['key' => 'line', 'label' => 'Line'],
                ['key' => 'amount', 'label' => 'Amount', 'money' => true],
            ],
            'rows' => [
                ['line' => 'Revenue (client payments)', 'amount' => $revenue],
                ['line' => 'Vendor payments', 'amount' => -$vendorCost],
                ['line' => 'Paid expenses', 'amount' => -$expenses],
                ['line' => 'Net profit / loss', 'amount' => $revenue - $vendorCost - $expenses],
            ],
        ];
    }

    private function eventSummary(int $plannerId): array
    {
        $rows = Event::where('planner_id', $plannerId)->with(['budgetItems', 'expenses', 'invoices', 'payments'])->get()
            ->map(function (Event $e) {
                $received = (float) $e->payments->where('direction', PaymentDirection::Incoming)->where('status', PaymentStatus::Completed)->sum('amount');
                $spent = (float) $e->expenses->where('status', ExpenseStatus::Paid)->sum('total');

                return [
                    'event' => $e->title,
                    'budget' => (float) $e->budgetItems->sum('estimated_cost'),
                    'expenses' => (float) $e->expenses->sum('total'),
                    'invoiced' => (float) $e->invoices->sum('total'),
                    'received' => $received,
                    'net' => $received - $spent,
                ];
            })->values()->all();

        return [
            'title' => 'Event Financial Summary',
            'columns' => [
                ['key' => 'event', 'label' => 'Event'],
                ['key' => 'budget', 'label' => 'Budget', 'money' => true],
                ['key' => 'expenses', 'label' => 'Expenses', 'money' => true],
                ['key' => 'invoiced', 'label' => 'Invoiced', 'money' => true],
                ['key' => 'received', 'label' => 'Received', 'money' => true],
                ['key' => 'net', 'label' => 'Net', 'money' => true],
            ],
            'rows' => $rows,
        ];
    }
}
