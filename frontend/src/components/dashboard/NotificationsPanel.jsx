import Icon from '../ui/Icon'
import Spinner from '../ui/Spinner'
import { cn } from '../../lib/cn'
import { formatRelative } from '../../lib/format'
import { useNotifications } from '../../context/NotificationsContext'

const TYPE_ICON = {
  approval_decision: 'ClipboardCheck',
  approval_completed: 'CheckCircle2',
  proposal: 'ClipboardList',
  payment_reminder: 'CreditCard',
  planning_update: 'TrendingUp',
  client_request: 'UserPlus',
  vendor_quotation: 'FileText',
}

/** The bell-dropdown contents. Kept presentational; state lives in context. */
export default function NotificationsPanel() {
  const { items, unread, loading, markRead, markAllRead, remove } = useNotifications()

  return (
    <div className="w-80 max-w-[calc(100vw-2rem)]">
      <div className="flex items-center justify-between px-3 py-2">
        <p className="font-bold text-ink">Notifications</p>
        {unread > 0 && (
          <button
            type="button"
            onClick={markAllRead}
            className="text-xs font-semibold text-navy-700 hover:underline"
          >
            Mark all read
          </button>
        )}
      </div>

      <div className="max-h-96 overflow-y-auto">
        {loading ? (
          <div className="grid place-items-center py-10">
            <Spinner className="size-5" />
          </div>
        ) : items.length === 0 ? (
          <p className="px-3 py-10 text-center text-sm text-muted">You're all caught up.</p>
        ) : (
          items.map((n) => (
            <div
              key={n.id}
              className={cn(
                'group flex gap-3 rounded-btn px-3 py-2.5 transition-colors hover:bg-canvas',
                !n.read && 'bg-navy-50/40',
              )}
            >
              <button
                type="button"
                onClick={() => !n.read && markRead(n.id)}
                className="flex min-w-0 flex-1 gap-3 text-left"
              >
                <span className="mt-0.5 grid size-8 shrink-0 place-items-center rounded-lg bg-canvas text-navy-700">
                  <Icon name={TYPE_ICON[n.type] ?? 'Bell'} className="size-4" />
                </span>
                <span className="min-w-0 flex-1">
                  <span className="flex items-center gap-2">
                    <span className="truncate text-sm font-semibold text-ink">{n.title}</span>
                    {!n.read && <span className="size-2 shrink-0 rounded-full bg-navy-600" />}
                  </span>
                  <span className="mt-0.5 line-clamp-2 block text-xs text-muted">{n.message}</span>
                  <span className="mt-1 block text-[0.7rem] text-muted">
                    {formatRelative(n.created_at)}
                  </span>
                </span>
              </button>
              <button
                type="button"
                onClick={() => remove(n.id)}
                title="Delete notification"
                aria-label="Delete notification"
                className="grid size-7 shrink-0 place-items-center self-center rounded-btn text-muted opacity-0 transition-opacity hover:bg-danger-50 hover:text-danger focus-visible:opacity-100 group-hover:opacity-100"
              >
                <Icon name="Trash2" className="size-4" />
              </button>
            </div>
          ))
        )}
      </div>
    </div>
  )
}
