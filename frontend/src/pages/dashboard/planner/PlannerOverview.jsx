import { Link } from 'react-router-dom'
import Card from '../../../components/ui/Card'
import Badge from '../../../components/ui/Badge'
import Icon from '../../../components/ui/Icon'
import ProgressBar from '../../../components/ui/ProgressBar'
import EmptyState from '../../../components/ui/EmptyState'
import StatGrid from '../../../components/dashboard/StatGrid'
import QuickActions from '../../../components/dashboard/QuickActions'
import LoadState from '../../../components/dashboard/LoadState'
import ActivityFeed from '../../../components/dashboard/ActivityFeed'
import { useResource } from '../../../lib/useResource'
import { useAuth } from '../../../context/AuthContext'
import { formatCurrency, formatDate } from '../../../lib/format'
import { EVENT_STATUS_TONE } from '../../../lib/eventConstants'

export default function PlannerOverview() {
  const { user } = useAuth()
  const { data, loading, error, reload } = useResource('/dashboard/stats')

  return (
    <div className="space-y-8">
      <div>
        <h1 className="text-h3 font-extrabold tracking-tight text-ink">
          Hi, {user.first_name} <span className="inline-block">👋</span>
        </h1>
        <p className="mt-1.5 text-muted">Here is your event planning overview.</p>
      </div>

      <LoadState loading={loading} error={error} onRetry={reload}>
        {data && (
          <div className="space-y-8">
            <StatGrid stats={data.stats} />

            <div>
              <h2 className="mb-3 text-sm font-bold uppercase tracking-wide text-muted">
                Quick actions
              </h2>
              <QuickActions
                actions={[
                  { label: 'Create Event', icon: 'Plus', to: `${user.dashboard_path}/events`, accent: 'navy' },
                  { label: 'Add Client', icon: 'UserPlus', to: `${user.dashboard_path}/clients`, accent: 'emerald' },
                  { label: 'Add Vendor', icon: 'Store', to: `${user.dashboard_path}/marketplace`, accent: 'purple' },
                  { label: 'View Calendar', icon: 'Calendar', to: `${user.dashboard_path}/calendar`, accent: 'navy' },
                ]}
              />
            </div>

            <div className="grid gap-8 lg:grid-cols-3">
              <div className="lg:col-span-2">
                <div className="mb-3 flex items-center justify-between">
                  <h2 className="text-sm font-bold uppercase tracking-wide text-muted">
                    Recent events
                  </h2>
                  <Link
                    to={`${user.dashboard_path}/events`}
                    className="text-sm font-semibold text-navy-700 hover:underline"
                  >
                    View all
                  </Link>
                </div>

                {data.recent_events?.length ? (
                  <div className="space-y-3">
                    {data.recent_events.map((event) => (
                      <Card
                        key={event.id}
                        as={Link}
                        to={`${user.dashboard_path}/events/${event.id}`}
                        hover
                        className="block p-5"
                      >
                        <div className="flex flex-wrap items-center justify-between gap-3">
                          <div className="min-w-0">
                            <p className="truncate font-bold text-ink">{event.title}</p>
                            <p className="mt-0.5 flex items-center gap-2 text-sm text-muted">
                              <Icon name="Calendar" className="size-4" />
                              {formatDate(event.event_date)}
                              {event.venue && <span className="truncate">· {event.venue}</span>}
                            </p>
                          </div>
                          <Badge tone={EVENT_STATUS_TONE[event.status] ?? 'muted'} dot>
                            {event.status_label}
                          </Badge>
                        </div>

                        <div className="mt-4 flex items-center gap-4">
                          <ProgressBar value={event.progress} className="flex-1" />
                          <span className="text-sm font-semibold text-ink">{event.progress}%</span>
                          <span className="hidden text-sm text-muted sm:block">
                            {formatCurrency(event.budget.total)}
                          </span>
                        </div>
                      </Card>
                    ))}
                  </div>
                ) : (
                  <EmptyState
                    icon="CalendarClock"
                    title="No events yet"
                    description="Create your first event to start planning."
                  />
                )}
              </div>

              <div>
                <h2 className="mb-3 text-sm font-bold uppercase tracking-wide text-muted">
                  Recent activity
                </h2>
                <Card className="p-5">
                  <ActivityFeed activities={data.recent_activities} showEvent />
                </Card>
              </div>
            </div>
          </div>
        )}
      </LoadState>
    </div>
  )
}
