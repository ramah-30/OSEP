import Icon from '../ui/Icon'
import EmptyState from '../ui/EmptyState'
import { cn } from '../../lib/cn'
import { formatRelative } from '../../lib/format'
import { ACTIVITY_META } from '../../lib/eventConstants'
import { useAuth } from '../../context/AuthContext'

const TONE_BG = {
  navy: 'bg-navy-50 text-navy-700',
  emerald: 'bg-emerald-50 text-emerald-600',
  purple: 'bg-purple-50 text-purple-600',
  amber: 'bg-warning-soft text-warning',
  muted: 'bg-canvas text-muted',
}

/**
 * The event activity feed. Each entry pairs an icon with "who did what", plus a
 * relative timestamp. Set `showEvent` to prefix the event name (dashboard feed).
 */
export default function ActivityFeed({ activities = [], showEvent = false }) {
  const { user } = useAuth()

  if (!activities?.length) {
    return <EmptyState icon="ListChecks" title="No activity yet" description="Actions on this event show up here." />
  }

  return (
    <ul className="space-y-4">
      {activities.map((entry) => {
        const meta = ACTIVITY_META[entry.action] ?? { icon: 'Info', tone: 'muted' }
        const isYou = entry.user?.id && entry.user.id === user?.id
        return (
          <li key={entry.id} className="flex gap-3">
            <span className={cn('mt-0.5 grid size-8 shrink-0 place-items-center rounded-full', TONE_BG[meta.tone])}>
              <Icon name={meta.icon} className="size-4" />
            </span>
            <div className="min-w-0 flex-1">
              <p className="text-sm text-ink">
                <span className="font-semibold">{isYou ? 'You' : entry.user?.full_name ?? 'Someone'}</span>{' '}
                {entry.description}
                {showEvent && entry.event_id && (
                  <span className="text-muted"> · event #{entry.event_id}</span>
                )}
              </p>
              <p className="mt-0.5 text-xs text-muted">{formatRelative(entry.created_at)}</p>
            </div>
          </li>
        )
      })}
    </ul>
  )
}
