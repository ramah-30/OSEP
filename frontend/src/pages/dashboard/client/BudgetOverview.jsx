import Card from '../../../components/ui/Card'
import Badge from '../../../components/ui/Badge'
import Icon from '../../../components/ui/Icon'
import ProgressBar from '../../../components/ui/ProgressBar'
import EmptyState from '../../../components/ui/EmptyState'
import PageHeader from '../../../components/ui/PageHeader'
import LoadState from '../../../components/dashboard/LoadState'
import { useResource } from '../../../lib/useResource'
import { formatCurrency } from '../../../lib/format'
import { EVENT_STATUS_TONE as STATUS_TONE } from '../../../lib/eventConstants'

function EventBudget({ event }) {
  const budget = event.budget
  const usedPct = budget?.total ? Math.round((budget.spent / budget.total) * 100) : 0

  return (
    <Card className="p-6 md:p-8">
      <div className="flex flex-wrap items-start justify-between gap-3">
        <div>
          <h2 className="text-lg font-extrabold text-ink">{event.title}</h2>
          <p className="mt-0.5 text-sm text-muted">{event.event_type}</p>
        </div>
        <Badge tone={STATUS_TONE[event.status] ?? 'muted'} dot>
          {event.status_label}
        </Badge>
      </div>

      {!budget || !budget.total ? (
        <p className="mt-6 rounded-xl bg-canvas px-4 py-3 text-sm text-muted">
          No budget has been set for this event yet.
        </p>
      ) : (
        <>
          <div className="mt-6 grid gap-4 sm:grid-cols-3">
            {[
              { label: 'Total approved', value: budget.total, icon: 'Wallet', accent: 'bg-navy-50 text-navy-700' },
              { label: 'Amount spent', value: budget.spent, icon: 'CreditCard', accent: 'bg-purple-50 text-purple-600' },
              { label: 'Remaining', value: budget.remaining, icon: 'TrendingUp', accent: 'bg-emerald-50 text-emerald-600' },
            ].map((b) => (
              <div key={b.label} className="rounded-xl border border-line p-4">
                <div className="flex items-center justify-between">
                  <p className="text-sm text-muted">{b.label}</p>
                  <span className={`grid size-9 place-items-center rounded-lg ${b.accent}`}>
                    <Icon name={b.icon} className="size-4" />
                  </span>
                </div>
                <p className="mt-3 text-xl font-extrabold text-ink">{formatCurrency(b.value)}</p>
              </div>
            ))}
          </div>

          <div className="mt-6">
            <div className="flex items-center justify-between text-sm">
              <span className="font-semibold text-ink">Budget used</span>
              <span className="font-bold text-ink">{usedPct}%</span>
            </div>
            <ProgressBar value={usedPct} tone={usedPct > 90 ? 'purple' : 'navy'} className="mt-3" />
            <p className="mt-3 text-sm text-muted">
              {formatCurrency(budget.spent)} of {formatCurrency(budget.total)} committed.
            </p>
          </div>
        </>
      )}
    </Card>
  )
}

export default function BudgetOverview() {
  const { data, loading, error, reload } = useResource('/my-events')
  const events = data?.events ?? []

  return (
    <div className="space-y-6">
      <PageHeader
        title="Budget Overview"
        description="A read-only view of the approved budget for each of your events. Your planner manages the line items."
      />

      <LoadState loading={loading} error={error} onRetry={reload}>
        {events.length === 0 ? (
          <EmptyState icon="Wallet" title="No budget yet" description="Your budgets appear here once your events are set up." />
        ) : (
          <div className="space-y-6">
            {events.map((event) => (
              <EventBudget key={event.id} event={event} />
            ))}
          </div>
        )}
      </LoadState>
    </div>
  )
}
