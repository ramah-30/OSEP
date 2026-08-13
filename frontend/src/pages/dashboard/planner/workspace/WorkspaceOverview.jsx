import { useOutletContext } from 'react-router-dom'
import Card from '../../../../components/ui/Card'
import Badge from '../../../../components/ui/Badge'
import Icon from '../../../../components/ui/Icon'
import ProgressBar from '../../../../components/ui/ProgressBar'
import ActivityFeed from '../../../../components/dashboard/ActivityFeed'
import { AskAiButton, GenerateAiButton } from '../../../../components/ai/InlineAiButtons'
import { formatCurrency, formatDate } from '../../../../lib/format'
import { EVENT_STATUS_TONE, MILESTONE_TONE } from '../../../../lib/eventConstants'

function daysUntil(date) {
  if (!date) return null
  const diff = Math.ceil((new Date(date).getTime() - Date.now()) / 86_400_000)
  return diff
}

function Stat({ icon, label, value, accent = 'navy' }) {
  const bg = { navy: 'bg-navy-50 text-navy-700', emerald: 'bg-emerald-50 text-emerald-600', purple: 'bg-purple-50 text-purple-600' }[accent]
  return (
    <Card className="p-5">
      <div className="flex items-center gap-3">
        <span className={`grid size-10 shrink-0 place-items-center rounded-xl ${bg}`}><Icon name={icon} className="size-5" /></span>
        <div className="min-w-0">
          <p className="text-sm text-muted">{label}</p>
          <p className="truncate text-lg font-extrabold text-ink">{value}</p>
        </div>
      </div>
    </Card>
  )
}

export default function WorkspaceOverview() {
  const { event } = useOutletContext()
  const countdown = daysUntil(event.event_date)
  const confirmedGuests = event.guests?.filter((g) => g.rsvp_status === 'confirmed').length ?? 0
  const upcomingMilestones = (event.milestones ?? []).filter((m) => m.status !== 'completed').slice(0, 5)

  return (
    <div className="space-y-6">
      <div className="flex flex-wrap items-center gap-2 rounded-card border border-dashed border-line bg-canvas/50 p-3">
        <span className="mr-1 flex items-center gap-1.5 text-sm font-semibold text-ink">
          <Icon name="Sparkles" className="size-4 text-purple-500" /> OSEP AI
        </span>
        <AskAiButton eventId={event.id} prompt={`Summarize where ${event.title} stands and the top risks.`} label="Ask AI" variant="ghost" />
        <GenerateAiButton templateKey="client_proposal" eventId={event.id} label="Proposal" variant="ghost" />
        <GenerateAiButton templateKey="planning_timeline" eventId={event.id} label="Timeline" variant="ghost" />
        <GenerateAiButton templateKey="client_update_email" eventId={event.id} label="Client update" variant="ghost" />
      </div>

      <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <Stat icon="TrendingUp" label="Progress" value={`${event.progress}%`} accent="emerald" />
        <Stat
          icon="CalendarClock"
          label="Countdown"
          value={countdown == null ? '—' : countdown > 0 ? `${countdown} days` : countdown === 0 ? 'Today' : 'Passed'}
        />
        <Stat icon="Users" label="Guests confirmed" value={`${confirmedGuests}${event.expected_guests ? ` / ${event.expected_guests}` : ''}`} />
        <Stat icon="Store" label="Vendors" value={event.vendor_assignments?.length ?? 0} accent="purple" />
      </div>

      <div className="grid gap-6 lg:grid-cols-3">
        <Card className="p-6 lg:col-span-2">
          <h2 className="text-sm font-bold uppercase tracking-wide text-muted">Budget summary</h2>
          <div className="mt-4 grid gap-4 sm:grid-cols-3">
            <div>
              <p className="text-sm text-muted">Total budget</p>
              <p className="mt-1 text-xl font-extrabold text-ink">{formatCurrency(event.budget.total)}</p>
            </div>
            <div>
              <p className="text-sm text-muted">Spent</p>
              <p className="mt-1 text-xl font-extrabold text-ink">{formatCurrency(event.budget.spent)}</p>
            </div>
            <div>
              <p className="text-sm text-muted">Remaining</p>
              <p className="mt-1 text-xl font-extrabold text-emerald-600">{formatCurrency(event.budget.remaining)}</p>
            </div>
          </div>
          <ProgressBar
            value={event.budget.total ? (event.budget.spent / event.budget.total) * 100 : 0}
            tone="navy"
            className="mt-4"
          />

          <div className="mt-6 grid gap-3 sm:grid-cols-2">
            <Detail label="Client" value={event.client?.full_name ?? 'No client assigned'} />
            <Detail label="Event type" value={event.event_type} />
            <Detail label="Location" value={event.location ?? '—'} />
            <Detail label="Theme" value={event.theme ?? '—'} />
            <Detail label="Status" value={<Badge tone={EVENT_STATUS_TONE[event.status] ?? 'muted'}>{event.status_label}</Badge>} />
            <Detail label="Time" value={event.start_time ? `${event.start_time}${event.end_time ? ` – ${event.end_time}` : ''}` : '—'} />
          </div>

          {event.description && <p className="mt-6 border-t border-line pt-4 text-sm text-muted">{event.description}</p>}
        </Card>

        <Card className="p-6">
          <h2 className="text-sm font-bold uppercase tracking-wide text-muted">Upcoming milestones</h2>
          {upcomingMilestones.length ? (
            <ul className="mt-4 space-y-3">
              {upcomingMilestones.map((m) => (
                <li key={m.id} className="flex items-start justify-between gap-2">
                  <div className="min-w-0">
                    <p className="truncate text-sm font-semibold text-ink">{m.name}</p>
                    <p className="text-xs text-muted">{formatDate(m.due_date)}</p>
                  </div>
                  <Badge tone={MILESTONE_TONE[m.status] ?? 'muted'}>{m.status_label}</Badge>
                </li>
              ))}
            </ul>
          ) : (
            <p className="mt-4 text-sm text-muted">No open milestones.</p>
          )}

          <h2 className="mt-6 text-sm font-bold uppercase tracking-wide text-muted">Recent activity</h2>
          <div className="mt-4">
            <ActivityFeed activities={(event.activities ?? []).slice(0, 5)} />
          </div>
        </Card>
      </div>
    </div>
  )
}

function Detail({ label, value }) {
  return (
    <div>
      <p className="text-xs font-semibold uppercase tracking-wide text-muted">{label}</p>
      <p className="mt-0.5 text-sm text-ink">{value}</p>
    </div>
  )
}
