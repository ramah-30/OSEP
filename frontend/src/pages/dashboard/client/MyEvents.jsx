import { useMemo, useState } from 'react'
import Card from '../../../components/ui/Card'
import Badge from '../../../components/ui/Badge'
import Button from '../../../components/ui/Button'
import Icon from '../../../components/ui/Icon'
import ProgressBar from '../../../components/ui/ProgressBar'
import EmptyState from '../../../components/ui/EmptyState'
import PageHeader from '../../../components/ui/PageHeader'
import LoadState from '../../../components/dashboard/LoadState'
import PlannerReviewModal from '../../../components/marketplace/PlannerReviewModal'
import { useResource } from '../../../lib/useResource'
import { useAuth } from '../../../context/AuthContext'
import { formatCurrency, formatDate } from '../../../lib/format'
import { EVENT_STATUS_TONE as STATUS_TONE } from '../../../lib/eventConstants'

function Detail({ icon, label, value }) {
  return (
    <div className="flex items-start gap-3">
      <span className="grid size-10 shrink-0 place-items-center rounded-xl bg-canvas text-navy-700">
        <Icon name={icon} className="size-5" />
      </span>
      <div>
        <p className="text-xs font-semibold uppercase tracking-wide text-muted">{label}</p>
        <p className="mt-0.5 font-semibold text-ink">{value ?? '—'}</p>
      </div>
    </div>
  )
}

function EventCard({ event, base, myReview, onRate }) {
  return (
    <Card className="p-6 md:p-8">
      <div className="flex flex-wrap items-start justify-between gap-3">
        <div>
          <h2 className="text-h3 font-extrabold text-ink">{event.title}</h2>
          <p className="mt-1 text-muted">{event.event_type}</p>
        </div>
        <div className="flex items-center gap-2">
          {event.planner?.id && (
            <Button size="sm" variant="secondary" onClick={() => onRate(event)}>
              <Icon name="Star" className="size-4" /> {myReview ? 'Edit your review' : 'Rate planner'}
            </Button>
          )}
          {event.planner?.id && (
            <Button to={`${base}/messages?to=${event.planner.id}`} size="sm" variant="secondary">
              <Icon name="MessageSquare" className="size-4" /> Message planner
            </Button>
          )}
          <Badge tone={STATUS_TONE[event.status] ?? 'muted'} dot>
            {event.status_label}
          </Badge>
        </div>
      </div>

      <div className="mt-6 grid gap-5 sm:grid-cols-2">
        <Detail icon="Calendar" label="Date" value={formatDate(event.event_date)} />
        <Detail icon="MapPin" label="Venue" value={event.venue} />
        <Detail icon="Building2" label="Location" value={event.location} />
        <Detail
          icon="User"
          label="Planner"
          value={event.planner?.company_name ?? event.planner?.full_name}
        />
      </div>

      <div className="mt-8">
        <div className="flex items-center justify-between text-sm">
          <span className="font-semibold text-ink">Overall progress</span>
          <span className="font-bold text-emerald-600">{event.progress}%</span>
        </div>
        <ProgressBar value={event.progress} className="mt-2" />
      </div>

      <div className="mt-6 grid gap-4 sm:grid-cols-3">
        {[
          { label: 'Total budget', value: event.budget.total },
          { label: 'Spent', value: event.budget.spent },
          { label: 'Remaining', value: event.budget.remaining },
        ].map((b) => (
          <div key={b.label} className="rounded-xl bg-canvas p-4">
            <p className="text-sm text-muted">{b.label}</p>
            <p className="mt-1 text-lg font-extrabold text-ink">{formatCurrency(b.value)}</p>
          </div>
        ))}
      </div>
    </Card>
  )
}

export default function MyEvents() {
  const { user } = useAuth()
  const { data, loading, error, reload } = useResource('/my-events')
  const { data: reviewData, reload: reloadReviews } = useResource('/planner-reviews')
  const events = data?.events ?? []

  const [rating, setRating] = useState(null) // the event currently being reviewed

  // Map the client's existing reviews by planner id for quick lookup.
  const reviewsByPlanner = useMemo(() => {
    const map = {}
    for (const r of reviewData?.reviews ?? []) map[r.planner_id] = r
    return map
  }, [reviewData])

  return (
    <div className="space-y-6">
      <PageHeader
        title="My Events"
        description="Every event your planners are working on for you."
      />

      <LoadState loading={loading} error={error} onRetry={reload}>
        {events.length === 0 ? (
          <EmptyState
            icon="CalendarClock"
            title="No events yet"
            description="Once a planner accepts your booking request, your event will appear here automatically."
          />
        ) : (
          <div className="space-y-6">
            {events.map((event) => (
              <EventCard
                key={event.id}
                event={event}
                base={user.dashboard_path}
                myReview={event.planner?.id ? reviewsByPlanner[event.planner.id] : null}
                onRate={setRating}
              />
            ))}
          </div>
        )}
      </LoadState>

      <PlannerReviewModal
        open={!!rating}
        onClose={() => setRating(null)}
        event={rating}
        existing={rating?.planner?.id ? reviewsByPlanner[rating.planner.id] : null}
        onSubmitted={reloadReviews}
      />
    </div>
  )
}
