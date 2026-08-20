import { useResource } from '../../../../lib/useResource'
import LoadState from '../../../../components/dashboard/LoadState'
import { SummaryCard, ChartCard } from '../../../../components/finance/FinanceBits'
import { GroupedBars, HorizontalBars, Donut, LineChart } from '../../../../components/finance/Charts'
import { formatNumber } from '../../../../lib/format'

export default function FinanceDashboard() {
  const { data, loading, error, reload } = useResource('/finance/dashboard')

  return (
    <LoadState loading={loading} error={error} onRetry={reload}>
      {data && (
        <div className="space-y-6">
          {/* Summary cards */}
          <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <SummaryCard label="Total budget" value={data.cards.total_budget} icon="Wallet" tone="navy" />
            <SummaryCard label="Approved budget" value={data.cards.approved_budget} icon="ClipboardCheck" tone="navy" />
            <SummaryCard label="Total expenses" value={data.cards.total_expenses} icon="ReceiptText" tone="amber" />
            <SummaryCard label="Paid expenses" value={data.cards.paid_expenses} icon="CheckCircle2" tone="emerald" />
            <SummaryCard label="Client payments received" value={data.cards.client_payments_received} icon="CircleDollarSign" tone="emerald" />
            <SummaryCard label="Outstanding payments" value={data.cards.outstanding_payments} icon="Clock" tone="amber" />
            <SummaryCard label="Vendor payments due" value={data.cards.vendor_payments_due} icon="Handshake" tone="purple" />
            <SummaryCard
              label="Profit / loss"
              value={data.cards.profit_loss}
              icon="TrendingUp"
              tone={data.cards.profit_loss >= 0 ? 'emerald' : 'danger'}
              hint={`Budget utilisation ${data.cards.budget_utilization}%`}
            />
          </div>

          {/* Charts */}
          <div className="grid gap-5 lg:grid-cols-2">
            <ChartCard title="Budget vs actual" icon="BarChart3">
              <GroupedBars
                data={data.charts.budget_vs_actual}
                series={[{ key: 'estimated', label: 'Estimated' }, { key: 'actual', label: 'Actual' }]}
              />
            </ChartCard>

            <ChartCard title="Cash flow (6 months)" icon="LineChart">
              <LineChart
                data={data.charts.cash_flow}
                series={[{ key: 'inflow', label: 'Inflow' }, { key: 'outflow', label: 'Outflow' }]}
              />
            </ChartCard>

            <ChartCard title="Monthly expenses" icon="BarChart3">
              <GroupedBars data={data.charts.monthly_expenses} series={[{ key: 'value', label: 'Expenses' }]} />
            </ChartCard>

            <ChartCard title="Payment status" icon="PieChart">
              <Donut data={data.charts.payment_status} format={(v) => `${formatNumber(v)} invoices`} />
            </ChartCard>

            <ChartCard title="Revenue by event" icon="TrendingUp">
              <HorizontalBars data={data.charts.revenue_by_event} />
            </ChartCard>

            <ChartCard title="Expense categories" icon="PieChart">
              <HorizontalBars data={data.charts.expense_categories} />
            </ChartCard>
          </div>
        </div>
      )}
    </LoadState>
  )
}
