<?php

namespace Database\Seeders;

use App\Enums\BudgetItemStatus;
use App\Enums\BudgetStatus;
use App\Enums\ClientQuotationStatus;
use App\Enums\ExpenseStatus;
use App\Enums\InvoiceStatus;
use App\Enums\PaymentDirection;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\RefundStatus;
use App\Enums\ScheduleStatus;
use App\Models\Budget;
use App\Models\ClientQuotation;
use App\Models\Event;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PaymentSchedule;
use App\Models\Receipt;
use App\Models\Refund;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Phase 6 demo data: gives the planner's events a master budget, a spread of
 * expenses, client quotations, invoices, payments (with receipts), an
 * installment schedule and a refund so the Financial Dashboard is populated.
 */
class FinanceSeeder extends Seeder
{
    public function run(): void
    {
        $planner = User::where('email', 'planner@osep.test')->first();
        if (! $planner) {
            return;
        }

        // Idempotent: bail if this planner already has finance data.
        if (Invoice::where('planner_id', $planner->id)->exists()) {
            return;
        }

        $seq = ['exp' => 0, 'quo' => 0, 'inv' => 0, 'pay' => 0, 'rcp' => 0, 'ref' => 0];
        $number = function (string $key, string $prefix) use (&$seq): string {
            $seq[$key]++;

            return sprintf('%s-%d-%06d', $prefix, now()->year, $seq[$key]);
        };

        $events = Event::where('planner_id', $planner->id)->with(['budgetItems', 'vendorAssignments', 'client'])->get();

        foreach ($events as $index => $event) {
            $this->budgetFor($event);

            $vendors = $event->vendorAssignments;
            $categories = ['Catering', 'Decoration', 'Photography', 'Entertainment', 'Transportation'];

            // A handful of expenses in mixed states.
            foreach ($categories as $i => $category) {
                $amount = (500_000 + $i * 250_000);
                $tax = round($amount * 0.18);
                $status = [ExpenseStatus::Paid, ExpenseStatus::Approved, ExpenseStatus::Submitted, ExpenseStatus::Paid, ExpenseStatus::Draft][$i];

                Expense::create([
                    'expense_number' => $number('exp', 'EXP'),
                    'event_id' => $event->id,
                    'vendor_assigned_id' => $vendors[$i]->id ?? null,
                    'category' => $category,
                    'description' => "$category services",
                    'amount' => $amount,
                    'tax' => $tax,
                    'total' => $amount + $tax,
                    'currency' => 'TZS',
                    'payment_method' => PaymentMethod::MobileMoney->value,
                    'status' => $status->value,
                    'expense_date' => now()->subDays(60 - $i * 12)->toDateString(),
                    'submitted_by' => $planner->id,
                    'approved_by' => in_array($status, [ExpenseStatus::Approved, ExpenseStatus::Paid], true) ? $planner->id : null,
                    'approved_at' => in_array($status, [ExpenseStatus::Approved, ExpenseStatus::Paid], true) ? now()->subDays(50 - $i * 12) : null,
                ]);
            }

            // One quotation → invoice → payments chain per event.
            $quotation = $this->quotation($event, $planner, $number, $index);
            $invoice = $this->invoice($event, $planner, $quotation, $number, $index);
            $this->payments($event, $planner, $invoice, $vendors, $number, $index);
        }
    }

    private function budgetFor(Event $event): void
    {
        $estimated = (float) $event->budgetItems->sum('estimated_cost');
        if ($estimated <= 0) {
            $estimated = (float) ($event->budget_total ?: 20_000_000);
        }

        Budget::updateOrCreate(
            ['event_id' => $event->id],
            [
                'currency' => 'TZS',
                'estimated_total' => $estimated,
                'revised_total' => round($estimated * 1.05),
                'status' => BudgetStatus::Approved->value,
                'approved_at' => now()->subDays(40),
            ]
        );

        // Give the existing line items richer figures.
        foreach ($event->budgetItems as $item) {
            $item->update([
                'approved_cost' => $item->estimated_cost,
                'quantity' => 1,
                'unit_cost' => $item->estimated_cost,
                'status' => $item->actual_cost > 0 ? BudgetItemStatus::Paid->value : BudgetItemStatus::Planned->value,
            ]);
        }
    }

    private function quotation(Event $event, User $planner, callable $number, int $index): ClientQuotation
    {
        $status = [ClientQuotationStatus::Accepted, ClientQuotationStatus::Sent, ClientQuotationStatus::Draft][$index % 3];

        $quotation = ClientQuotation::create([
            'reference' => $number('quo', 'QUO'),
            'planner_id' => $planner->id,
            'client_id' => $event->client_id,
            'event_id' => $event->id,
            'title' => "Event planning services — {$event->title}",
            'currency' => 'TZS',
            'valid_until' => now()->addDays(20)->toDateString(),
            'status' => $status->value,
            'terms' => 'Valid for 20 days. 50% deposit required to confirm the booking.',
            'sent_at' => $status !== ClientQuotationStatus::Draft ? now()->subDays(30) : null,
            'decided_at' => $status === ClientQuotationStatus::Accepted ? now()->subDays(25) : null,
        ]);

        foreach ([
            ['Full event coordination', 1, 4_500_000],
            ['Décor & styling package', 1, 3_200_000],
            ['Photography & videography', 1, 2_800_000],
        ] as $i => [$desc, $qty, $price]) {
            $quotation->items()->create([
                'description' => $desc,
                'quantity' => $qty,
                'unit_price' => $price,
                'tax' => round($price * 0.18),
                'amount' => $qty * $price,
                'sort_order' => $i,
            ]);
        }

        $quotation->recalculateTotals();

        return $quotation;
    }

    private function invoice(Event $event, User $planner, ClientQuotation $quotation, callable $number, int $index): Invoice
    {
        $invoice = Invoice::create([
            'invoice_number' => $number('inv', 'INV'),
            'planner_id' => $planner->id,
            'client_id' => $event->client_id,
            'event_id' => $event->id,
            'client_quotation_id' => $quotation->id,
            'title' => $quotation->title,
            'currency' => 'TZS',
            'issue_date' => now()->subDays(20)->toDateString(),
            'due_date' => now()->addDays(10)->toDateString(),
            'payment_terms' => 'Due within 30 days of issue.',
            'status' => InvoiceStatus::Sent->value,
            'sent_at' => now()->subDays(20),
        ]);

        foreach ($quotation->items as $item) {
            $invoice->items()->create([
                'description' => $item->description,
                'quantity' => $item->quantity,
                'unit_price' => $item->unit_price,
                'tax' => $item->tax,
                'discount' => $item->discount,
                'amount' => $item->amount,
                'sort_order' => $item->sort_order,
            ]);
        }

        $invoice->recalculateTotals();

        // A 40 / 40 / 20 installment plan.
        foreach ([['50% Deposit', 50], ['30% Mid-project', 30], ['20% Before event', 20]] as $i => [$label, $pct]) {
            PaymentSchedule::create([
                'planner_id' => $planner->id,
                'event_id' => $event->id,
                'invoice_id' => $invoice->id,
                'label' => $label,
                'percentage' => $pct,
                'amount' => round($invoice->total * $pct / 100),
                'currency' => 'TZS',
                'due_date' => now()->addDays($i * 15 - 10)->toDateString(),
                'status' => $i === 0 ? ScheduleStatus::Paid->value : ScheduleStatus::Pending->value,
                'paid_at' => $i === 0 ? now()->subDays(15)->toDateString() : null,
                'sort_order' => $i,
            ]);
        }

        return $invoice;
    }

    private function payments(Event $event, User $planner, Invoice $invoice, $vendors, callable $number, int $index): void
    {
        // A client deposit (incoming) with an auto-receipt.
        $deposit = round($invoice->total * 0.5);
        $payment = Payment::create([
            'payment_number' => $number('pay', 'PAY'),
            'planner_id' => $planner->id,
            'event_id' => $event->id,
            'invoice_id' => $invoice->id,
            'direction' => PaymentDirection::Incoming->value,
            'party_name' => $event->client?->full_name,
            'method' => PaymentMethod::BankTransfer->value,
            'amount' => $deposit,
            'currency' => 'TZS',
            'transaction_ref' => 'TXN'.random_int(100000, 999999),
            'reference' => 'Deposit payment',
            'status' => PaymentStatus::Completed->value,
            'paid_at' => now()->subDays(15)->toDateString(),
            'recorded_by' => $planner->id,
        ]);

        Receipt::create([
            'receipt_number' => $number('rcp', 'RCP'),
            'payment_id' => $payment->id,
            'planner_id' => $planner->id,
            'client_id' => $event->client_id,
            'event_id' => $event->id,
            'invoice_id' => $invoice->id,
            'amount' => $deposit,
            'currency' => 'TZS',
            'issued_at' => now()->subDays(15),
        ]);

        $invoice->recalculatePaid();

        // A vendor payout (outgoing).
        if ($vendor = $vendors->first()) {
            Payment::create([
                'payment_number' => $number('pay', 'PAY'),
                'planner_id' => $planner->id,
                'event_id' => $event->id,
                'vendor_assigned_id' => $vendor->id,
                'direction' => PaymentDirection::Outgoing->value,
                'party_name' => $vendor->vendor_name,
                'method' => PaymentMethod::MobileMoney->value,
                'amount' => 1_500_000,
                'currency' => 'TZS',
                'reference' => 'Vendor deposit',
                'status' => PaymentStatus::Completed->value,
                'paid_at' => now()->subDays(10)->toDateString(),
                'recorded_by' => $planner->id,
            ]);
        }

        // A single refund on the first event to exercise the workflow.
        if ($index === 0) {
            Refund::create([
                'refund_number' => $number('ref', 'REF'),
                'planner_id' => $planner->id,
                'payment_id' => $payment->id,
                'invoice_id' => $invoice->id,
                'event_id' => $event->id,
                'client_id' => $event->client_id,
                'reason' => 'Scope reduced — décor downgrade',
                'amount' => 400_000,
                'currency' => 'TZS',
                'status' => RefundStatus::Requested->value,
            ]);
        }
    }
}
