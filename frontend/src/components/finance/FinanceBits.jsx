import Card from '../ui/Card'
import Badge from '../ui/Badge'
import Icon from '../ui/Icon'
import { cn } from '../../lib/cn'
import { formatCurrency } from '../../lib/format'
import { statusMeta } from '../../lib/finance'

/** A headline figure tile for the finance dashboard. */
export function SummaryCard({ label, value, icon, tone = 'navy', money = true, hint }) {
  const tones = {
    navy: 'bg-navy-50 text-navy-700',
    emerald: 'bg-emerald-50 text-emerald-600',
    purple: 'bg-purple-50 text-purple-600',
    amber: 'bg-warning-soft text-warning',
    danger: 'bg-danger-soft text-danger',
  }

  return (
    <Card className="p-5">
      <div className="flex items-start justify-between gap-3">
        <div className="min-w-0">
          <p className="truncate text-sm text-muted">{label}</p>
          <p className="mt-1 text-xl font-extrabold tabular-nums text-ink">
            {money ? formatCurrency(value) : value}
          </p>
          {hint && <p className="mt-0.5 text-xs text-muted">{hint}</p>}
        </div>
        {icon && (
          <span className={cn('grid size-10 shrink-0 place-items-center rounded-btn', tones[tone])}>
            <Icon name={icon} className="size-5" />
          </span>
        )}
      </div>
    </Card>
  )
}

/** A titled container for a chart. */
export function ChartCard({ title, icon, action, children, className }) {
  return (
    <Card className={cn('flex flex-col p-5', className)}>
      <div className="mb-4 flex items-center justify-between gap-3">
        <h3 className="flex items-center gap-2 text-sm font-bold text-ink">
          {icon && <Icon name={icon} className="size-4 text-navy-700" />} {title}
        </h3>
        {action}
      </div>
      <div className="flex-1">{children}</div>
    </Card>
  )
}

/** Status pill that reads its label/tone from a finance status map. */
export function FStatus({ map, value }) {
  const meta = statusMeta(map, value)
  return <Badge tone={meta.tone}>{meta.label}</Badge>
}
