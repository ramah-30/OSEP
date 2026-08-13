import { Link } from 'react-router-dom'
import Card from '../../../components/ui/Card'
import Badge from '../../../components/ui/Badge'
import Icon from '../../../components/ui/Icon'
import Button from '../../../components/ui/Button'
import ProgressBar from '../../../components/ui/ProgressBar'
import EmptyState from '../../../components/ui/EmptyState'
import StatGrid from '../../../components/dashboard/StatGrid'
import EventUpdates from '../../../components/client/EventUpdates'
import LoadState from '../../../components/dashboard/LoadState'
import { useResource } from '../../../lib/useResource'
import { useAuth } from '../../../context/AuthContext'
import { formatCurrency, formatDate } from '../../../lib/format'

export default function ClientOverview() {
  const { user } = useAuth()
  const { data, loading, error, reload } = useResource('/dashboard/stats')
  const event = data?.event
  const base = user.dashboard_path

  return (
    <div className="space-y-8">
      <div>
        <h1 className="text-h3 font-extrabold tracking-tight text-ink">
          Welcome, {user.first_name}
        </h1>
        <p className="mt-1.5 text-muted">Here's how your event is coming together.</p>
      </div>

      <LoadState loading={loading} error={error} onRetry={reload}>
        {!event ? (
          <EmptyState
            icon="CalendarClock"
            title="No event assigned yet"
            description="Once your planner sets up your event, it will appear right here."
          />
        ) : (
          <div className="space-y-8">
            {/* Event hero */}
            <Card className="overflow-hidden">
              <div className="scrim-navy bg-navy-800 p-6 text-white md:p-8">
                <Badge tone="emerald" dot className="border-white/20 bg-white/15 text-white">
                  {event.status_label}
                </Badge>
                <h2 className="mt-3 text-h3 font-extrabold text-white">{event.title}</h2>
                <div className="mt-4 flex flex-wrap gap-x-8 gap-y-2 text-sm text-white/85">
                  <span className="flex items-center gap-2">
                    <Icon name="Calendar" className="size-4" />
                    {formatDate(event.event_date)}
                  </span>
                  {event.venue && (
                    <span className="flex items-center gap-2">
                      <Icon name="MapPin" className="size-4" />
                      {event.venue}
                    </span>
                  )}
                  {event.planner && (
                    <span className="flex items-center gap-2">
                      <Icon name="User" className="size-4" />
                      {event.planner.company_name ?? event.planner.full_name}
                    </span>
                  )}
                </div>
              </div>

              <div className="p-6 md:p-8">
                <div className="flex items-center justify-between text-sm">
                  <span className="font-semibold text-ink">Planning progress</span>
                  <span className="font-bold text-emerald-600">{event.progress}% complete</span>
                </div>
                <ProgressBar value={event.progress} className="mt-3" />
                <div className="mt-5 flex flex-wrap gap-3">
                  <Button to={`${base}/my-events`} size="sm">
                    View my events
                    <Icon name="ArrowRight" className="size-4" />
                  </Button>
                  <Button to={`${base}/progress`} size="sm" variant="secondary">
                    <Icon name="TrendingUp" className="size-4" />
                    Track progress
                  </Button>
                </div>
              </div>
            </Card>

            <StatGrid stats={data.stats} />

            <EventUpdates updates={event.updates ?? []} limit={6} />

            <div className="grid gap-4 lg:grid-cols-2">
              <Card className="p-6">
                <div className="flex items-center justify-between">
                  <h3 className="font-bold text-ink">Milestones</h3>
                  <Link
                    to={`${base}/progress`}
                    className="text-sm font-semibold text-navy-700 hover:underline"
                  >
                    See all
                  </Link>
                </div>
                <ul className="mt-4 space-y-2.5">
                  {event.milestones?.slice(0, 4).map((m) => (
                    <li key={m.id} className="flex items-center gap-3 text-sm">
                      <Icon
                        name={m.status === 'completed' ? 'CheckCircle2' : 'Clock'}
                        className={m.status === 'completed' ? 'size-4 text-emerald-500' : 'size-4 text-muted'}
                      />
                      <span className="flex-1 text-ink">{m.name}</span>
                      <span className="text-xs text-muted">{m.status_label}</span>
                    </li>
                  ))}
                </ul>
              </Card>

              <Card className="p-6">
                <div className="flex items-center justify-between">
                  <h3 className="font-bold text-ink">Budget snapshot</h3>
                  <Link
                    to={`${base}/budget`}
                    className="text-sm font-semibold text-navy-700 hover:underline"
                  >
                    View details
                  </Link>
                </div>
                {event.budget ? (
                  <ul className="mt-4 space-y-2.5 text-sm">
                    <li className="flex items-center justify-between">
                      <span className="text-muted">Total budget</span>
                      <span className="font-semibold text-ink">{formatCurrency(event.budget.total)}</span>
                    </li>
                    <li className="flex items-center justify-between">
                      <span className="text-muted">Spent</span>
                      <span className="font-semibold text-ink">{formatCurrency(event.budget.spent)}</span>
                    </li>
                    <li className="flex items-center justify-between">
                      <span className="text-muted">Remaining</span>
                      <span className="font-semibold text-emerald-600">{formatCurrency(event.budget.remaining)}</span>
                    </li>
                  </ul>
                ) : (
                  <p className="mt-4 text-sm text-muted">Your budget appears here once it's set.</p>
                )}
              </Card>
            </div>
          </div>
        )}
      </LoadState>
    </div>
  )
}
