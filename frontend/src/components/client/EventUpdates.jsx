import Card from '../ui/Card'
import Icon from '../ui/Icon'
import EmptyState from '../ui/EmptyState'
import { formatRelative } from '../../lib/format'
import { cn } from '../../lib/cn'

/**
 * The client's "Updates" timeline — the client-visible slice of an event's
 * activity feed (a vendor confirming, a contract signed, a quotation sent).
 * Fed by `event.updates` from /my-events and /dashboard/stats.
 */

const UPDATE_META = {
  vendor_booking_accepted: { icon: 'Store', accent: 'emerald' },
  contract_signed: { icon: 'Handshake', accent: 'purple' },
  quotation_sent: { icon: 'ReceiptText', accent: 'navy' },
}

const ACCENTS = {
  navy: 'bg-navy-50 text-navy-700',
  emerald: 'bg-emerald-50 text-emerald-600',
  purple: 'bg-purple-50 text-purple-600',
}

function updateMeta(action) {
  return UPDATE_META[action] ?? { icon: 'Sparkles', accent: 'navy' }
}

export default function EventUpdates({ updates = [], limit, className }) {
  const items = limit ? updates.slice(0, limit) : updates

  return (
    <Card className={cn('p-6', className)}>
      <div className="flex items-center gap-2">
        <Icon name="Bell" className="size-4 text-navy-600" />
        <h3 className="font-bold text-ink">Updates</h3>
      </div>

      {items.length === 0 ? (
        <EmptyState
          className="py-8"
          icon="Bell"
          title="No updates yet"
          description="You'll see key moments here — like a vendor confirming or a quotation arriving — as your planner moves things forward."
        />
      ) : (
        <ol className="mt-5 space-y-5 border-l border-line pl-5">
          {items.map((u) => {
            const meta = updateMeta(u.action)
            return (
              <li key={u.id} className="relative">
                <span
                  className={cn(
                    'absolute -left-[2.05rem] grid size-7 place-items-center rounded-full ring-4 ring-surface',
                    ACCENTS[meta.accent],
                  )}
                >
                  <Icon name={meta.icon} className="size-3.5" />
                </span>
                <p className="text-sm font-medium text-ink">{u.description}</p>
                <p className="mt-0.5 text-xs text-muted">{formatRelative(u.created_at)}</p>
              </li>
            )
          })}
        </ol>
      )}
    </Card>
  )
}
