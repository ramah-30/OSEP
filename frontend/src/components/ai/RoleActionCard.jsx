import { useState } from 'react'
import Icon from '../ui/Icon'
import Button from '../ui/Button'
import Badge from '../ui/Badge'

const STATUS_META = {
  done: { tone: 'emerald', label: 'Done', icon: 'CheckCircle2' },
  rejected: { tone: 'muted', label: 'Dismissed', icon: 'X' },
  failed: { tone: 'danger', label: 'Failed', icon: 'TriangleAlert' },
}

/**
 * An inline approval card for a role copilot's proposed action (vendor/client).
 * While pending it offers Approve / Dismiss; once resolved it shows the outcome.
 * The parent owns the API call and passes onResolve(approve: boolean).
 */
export default function RoleActionCard({ action, onResolve }) {
  const [busy, setBusy] = useState(false)
  const pending = action.status === 'pending'
  const meta = STATUS_META[action.status]

  const resolve = async (approve) => {
    if (busy) return
    setBusy(true)
    try { await onResolve(approve) } finally { setBusy(false) }
  }

  return (
    <div className="w-full max-w-[92%] rounded-2xl border border-purple-200 bg-purple-50/50 p-3">
      <p className="flex items-center gap-2 text-sm font-semibold text-ink">
        <Icon name="Sparkles" className="size-4 text-purple-500" /> {action.title}
      </p>
      {action.summary && <p className="mt-1 text-xs text-muted">{action.summary}</p>}

      {pending ? (
        <div className="mt-3 flex items-center gap-2">
          <Button onClick={() => resolve(true)} disabled={busy} className="h-8 px-3 text-xs">Approve &amp; run</Button>
          <button type="button" onClick={() => resolve(false)} disabled={busy}
            className="h-8 rounded-lg px-3 text-xs font-semibold text-muted hover:text-ink disabled:opacity-50">
            Dismiss
          </button>
        </div>
      ) : (
        <div className="mt-2">
          <Badge tone={meta?.tone ?? 'navy'}>
            <Icon name={meta?.icon ?? 'Info'} className="mr-1 inline size-3" />{meta?.label ?? action.status}
          </Badge>
        </div>
      )}
    </div>
  )
}
