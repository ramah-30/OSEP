import { useTranslation } from 'react-i18next'
import Card from '../../../components/ui/Card'
import Badge from '../../../components/ui/Badge'
import Icon from '../../../components/ui/Icon'
import Button from '../../../components/ui/Button'
import EmptyState from '../../../components/ui/EmptyState'
import StatGrid from '../../../components/dashboard/StatGrid'
import LoadState from '../../../components/dashboard/LoadState'
import { useResource } from '../../../lib/useResource'
import { useAuth } from '../../../context/AuthContext'
import { formatDate } from '../../../lib/format'

export default function ClientOverview() {
  const { user } = useAuth()
  const { t } = useTranslation()
  const { data, loading, error, reload } = useResource('/dashboard/stats')
  const event = data?.event
  const base = user.dashboard_path

  return (
    <div className="space-y-8">
      <div>
        <h1 className="text-h3 font-extrabold tracking-tight text-ink">
          {t('dashboard.welcome')}, {user.first_name}
        </h1>
        <p className="mt-1.5 text-muted">{t('dashboard.clientEventOverview')}</p>
      </div>

      <LoadState loading={loading} error={error} onRetry={reload}>
        {!event ? (
          <EmptyState
            icon="CalendarClock"
            title={t('dashboard.noEventAssigned')}
            description={t('dashboard.noEventAssignedDesc')}
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
                <div className="flex flex-wrap gap-3">
                  <Button to={`${base}/my-events`} size="sm">
                    {t('events.viewMyEvents')}
                    <Icon name="ArrowRight" className="size-4" />
                  </Button>
                  <Button to={`${base}/progress`} size="sm" variant="secondary">
                    <Icon name="TrendingUp" className="size-4" />
                    {t('events.trackProgress')}
                  </Button>
                </div>
              </div>
            </Card>

            <StatGrid stats={data.stats} />
          </div>
        )}
      </LoadState>
    </div>
  )
}
