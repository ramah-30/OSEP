import Card from '../../../components/ui/Card'
import Badge from '../../../components/ui/Badge'
import Icon from '../../../components/ui/Icon'
import ProgressBar from '../../../components/ui/ProgressBar'
import EmptyState from '../../../components/ui/EmptyState'
import PageHeader from '../../../components/ui/PageHeader'
import LoadState from '../../../components/dashboard/LoadState'
import EventUpdates from '../../../components/client/EventUpdates'
import { useResource } from '../../../lib/useResource'
import { cn } from '../../../lib/cn'

const MILESTONE = {
  completed: { icon: 'CheckCircle2', tone: 'emerald', ring: 'bg-emerald-500 text-white' },
  in_progress: { icon: 'Clock', tone: 'navy', ring: 'bg-navy-600 text-white' },
  waiting_approval: { icon: 'ClipboardCheck', tone: 'amber', ring: 'bg-warning text-white' },
  pending: { icon: 'Clock', tone: 'muted', ring: 'bg-line text-muted' },
}

function EventProgress({ event }) {
  const milestones = event.milestones ?? []

  return (
    <Card className="p-6 md:p-8">
      <div className="flex items-center justify-between text-sm">
        <span className="font-semibold text-ink">{event.title}</span>
        <span className="font-bold text-emerald-600">{event.progress}% complete</span>
      </div>
      <ProgressBar value={event.progress} className="mt-3" />

      {milestones.length > 0 && (
        <ol className="relative mt-8 space-y-6 border-l border-line pl-6">
          {milestones.map((m) => {
            const cfg = MILESTONE[m.status] ?? MILESTONE.pending
            return (
              <li key={m.id} className="relative">
                <span
                  className={cn(
                    'absolute -left-[2.1rem] grid size-8 place-items-center rounded-full ring-4 ring-surface',
                    cfg.ring,
                  )}
                >
                  <Icon name={cfg.icon} className="size-4" />
                </span>
                <div className="flex flex-wrap items-center justify-between gap-2">
                  <p className="font-semibold text-ink">{m.name}</p>
                  <Badge tone={cfg.tone}>{m.status_label}</Badge>
                </div>
              </li>
            )
          })}
        </ol>
      )}
    </Card>
  )
}

export default function Progress() {
  const { data, loading, error, reload } = useResource('/my-events')
  const events = data?.events ?? []

  return (
    <div className="space-y-6">
      <PageHeader title="Planning Progress" description="Track each milestone toward your big day." />

      <LoadState loading={loading} error={error} onRetry={reload}>
        {events.length === 0 ? (
          <EmptyState icon="TrendingUp" title="Nothing to track yet" description="Milestones appear here once planning begins." />
        ) : (
          <div className="space-y-6">
            {events.map((event) => (
              <div key={event.id} className="grid gap-6 lg:grid-cols-5">
                <div className="lg:col-span-3">
                  <EventProgress event={event} />
                </div>
                <EventUpdates updates={event.updates ?? []} className="lg:col-span-2" />
              </div>
            ))}
          </div>
        )}
      </LoadState>
    </div>
  )
}
