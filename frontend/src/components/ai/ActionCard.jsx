import { useState } from 'react'
import Icon from '../ui/Icon'
import Spinner from '../ui/Spinner'
import { api, parseApiError } from '../../lib/api'
import { cn } from '../../lib/cn'

const TYPE_META = {
  send_rsvp_reminders: { icon: 'BellRing', label: 'RSVP reminders' },
  send_invitations: { icon: 'Send', label: 'Invitations' },
  create_tasks: { icon: 'ListChecks', label: 'Planning checklist' },
  create_event: { icon: 'CalendarPlus', label: 'New event' },
  add_task: { icon: 'ListPlus', label: 'Add task' },
  update_task: { icon: 'Pencil', label: 'Update task' },
  delete_task: { icon: 'Trash2', label: 'Delete task' },
  add_milestone: { icon: 'Flag', label: 'Add milestone' },
  update_milestone: { icon: 'Pencil', label: 'Update milestone' },
  delete_milestone: { icon: 'Trash2', label: 'Delete milestone' },
  add_budget_item: { icon: 'Wallet', label: 'Add budget item' },
  update_budget_item: { icon: 'Wallet', label: 'Update budget item' },
  delete_budget_item: { icon: 'Trash2', label: 'Delete budget item' },
  design_venue: { icon: 'LayoutGrid', label: 'Venue design' },
}

const STATUS_META = {
  done: { tone: 'emerald', icon: 'CircleCheck', label: 'Done' },
  failed: { tone: 'danger', icon: 'AlertTriangle', label: 'Failed' },
  rejected: { tone: 'muted', icon: 'X', label: 'Dismissed' },
}

/**
 * An approval card for a copilot action. Pending actions show Approve / Dismiss;
 * once resolved it shows the outcome. Nothing runs until Approve is clicked.
 */
export default function ActionCard({ action, onChanged, className }) {
  const [busy, setBusy] = useState(null)
  const [error, setError] = useState(null)
  const meta = TYPE_META[action.type] ?? { icon: 'Sparkles', label: 'Action' }
  const pending = action.status === 'pending'
  const status = STATUS_META[action.status]

  const act = async (verb) => {
    setBusy(verb); setError(null)
    try {
      const r = await api.post(`/ai/actions/${action.id}/${verb}`)
      onChanged?.(r.data.data.action, r.data.message)
    } catch (err) {
      setError(parseApiError(err).message)
      setBusy(null)
    }
  }

  return (
    <div className={cn('rounded-xl border border-line bg-surface p-3', pending && 'border-navy-200 bg-navy-50/40', className)}>
      <div className="flex items-start gap-2.5">
        <span className={cn('grid size-8 shrink-0 place-items-center rounded-lg',
          pending ? 'bg-navy-100 text-navy-700' : 'bg-canvas text-muted')}>
          <Icon name={meta.icon} className="size-4" />
        </span>
        <div className="min-w-0 flex-1">
          <p className="text-sm font-semibold text-ink">{action.title}</p>
          {action.summary && <p className="mt-0.5 text-xs text-muted">{action.summary}</p>}

          {status && (
            <p className={cn('mt-1.5 flex items-center gap-1 text-xs font-medium',
              status.tone === 'emerald' ? 'text-emerald-600' : status.tone === 'danger' ? 'text-danger' : 'text-muted')}>
              <Icon name={status.icon} className="size-3.5" />
              {action.result?.message || action.error || status.label}
            </p>
          )}
        </div>
      </div>

      {error && <p className="mt-2 text-xs text-danger">{error}</p>}

      {pending && (
        <div className="mt-2.5 flex items-center gap-2">
          <button
            type="button"
            onClick={() => act('approve')}
            disabled={!!busy}
            className="inline-flex items-center gap-1.5 rounded-lg bg-navy-800 px-3 py-1.5 text-xs font-semibold text-white transition-colors hover:bg-navy-900 disabled:opacity-50"
          >
            {busy === 'approve' ? <Spinner className="size-3.5" /> : <Icon name="Check" className="size-3.5" />}
            Approve & run
          </button>
          <button
            type="button"
            onClick={() => act('reject')}
            disabled={!!busy}
            className="inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1.5 text-xs font-medium text-muted transition-colors hover:bg-canvas hover:text-ink disabled:opacity-50"
          >
            <Icon name="X" className="size-3.5" /> Dismiss
          </button>
        </div>
      )}
    </div>
  )
}
