import Card from './Card'
import Icon from './Icon'
import { cn } from '../../lib/cn'
import { formatCurrency, formatStat } from '../../lib/format'

const ACCENTS = {
  navy: 'bg-navy-50 text-navy-700',
  emerald: 'bg-emerald-50 text-emerald-600',
  purple: 'bg-purple-50 text-purple-600',
}

/**
 * A dashboard metric tile. Reads the `{ label, value, format, icon, accent }`
 * shape the API's dashboard/stats endpoint returns.
 */
export default function StatCard({ label, value, format, icon, accent = 'navy', meta }) {
  return (
    <Card className="p-6">
      <div className="flex items-start justify-between gap-3">
        <div className="min-w-0 flex-1">
          <p className="text-sm font-medium text-muted">{label}</p>
          <p
            className="mt-2 break-words text-2xl font-extrabold leading-tight tracking-tight text-ink tabular-nums"
            title={format === 'currency' ? formatCurrency(value) : undefined}
          >
            {formatStat(value, format)}
          </p>
          {meta != null && (
            <p className="mt-1 flex items-center gap-1 text-sm text-muted">
              <Icon name="Star" className="size-3.5 text-warning" />
              {meta} average
            </p>
          )}
        </div>
        <span className={cn('grid size-11 shrink-0 place-items-center rounded-xl', ACCENTS[accent])}>
          <Icon name={icon} className="size-5" />
        </span>
      </div>
    </Card>
  )
}
