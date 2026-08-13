import { cn } from '../../lib/cn'
import { formatCurrencyCompact, formatNumber } from '../../lib/format'

/**
 * Lightweight, dependency-free charts for the Financial Dashboard. Everything is
 * plain CSS / inline SVG so nothing is added to the bundle and it all reflows
 * responsively.
 */

const SERIES_COLORS = [
  { bar: 'bg-navy-600', text: 'text-navy-600', stroke: '#2947c8' },
  { bar: 'bg-emerald-500', text: 'text-emerald-600', stroke: '#10b981' },
  { bar: 'bg-purple-500', text: 'text-purple-600', stroke: '#a855f7' },
  { bar: 'bg-warning', text: 'text-warning', stroke: '#f59e0b' },
  { bar: 'bg-navy-300', text: 'text-navy-400', stroke: '#93a4e8' },
  { bar: 'bg-danger', text: 'text-danger', stroke: '#ef4444' },
]

function money(v) {
  return formatCurrencyCompact(v)
}

/** Empty-state filler so a chart card never renders a bare box. */
function NoData({ label = 'No data yet' }) {
  return <div className="grid h-40 place-items-center text-sm text-muted">{label}</div>
}

/**
 * Grouped vertical bars. `series` is [{ key, label }]; each `data` row carries a
 * `label` plus a numeric value under every series key.
 */
export function GroupedBars({ data, series, format = money }) {
  if (!data?.length) return <NoData />

  const max = Math.max(
    1,
    ...data.flatMap((row) => series.map((s) => Number(row[s.key] || 0))),
  )

  return (
    <div>
      <div className="flex h-48 items-end gap-3">
        {data.map((row) => (
          <div key={row.label} className="flex flex-1 flex-col items-center gap-2">
            <div className="flex h-40 w-full items-end justify-center gap-1">
              {series.map((s, si) => {
                const value = Number(row[s.key] || 0)
                return (
                  <div
                    key={s.key}
                    title={`${s.label}: ${format(value)}`}
                    className={cn('w-full max-w-6 rounded-t transition-all', SERIES_COLORS[si % SERIES_COLORS.length].bar)}
                    style={{ height: `${Math.max(2, (value / max) * 100)}%` }}
                  />
                )
              })}
            </div>
            <span className="line-clamp-1 max-w-full text-xs text-muted" title={row.label}>{row.label}</span>
          </div>
        ))}
      </div>
      {series.length > 1 && (
        <div className="mt-4 flex flex-wrap justify-center gap-4">
          {series.map((s, si) => (
            <span key={s.key} className="flex items-center gap-1.5 text-xs font-semibold text-muted">
              <span className={cn('size-2.5 rounded-full', SERIES_COLORS[si % SERIES_COLORS.length].bar)} />
              {s.label}
            </span>
          ))}
        </div>
      )}
    </div>
  )
}

/** Horizontal ranked bars for breakdowns (revenue by event, categories). */
export function HorizontalBars({ data, format = money }) {
  if (!data?.length) return <NoData />

  const max = Math.max(1, ...data.map((d) => Number(d.value || 0)))

  return (
    <div className="space-y-3">
      {data.map((row, i) => (
        <div key={row.label}>
          <div className="mb-1 flex items-center justify-between gap-3 text-sm">
            <span className="line-clamp-1 text-ink" title={row.label}>{row.label}</span>
            <span className="shrink-0 font-semibold tabular-nums text-muted">{format(row.value)}</span>
          </div>
          <div className="h-2.5 w-full overflow-hidden rounded-full bg-canvas">
            <div
              className={cn('h-full rounded-full', SERIES_COLORS[i % SERIES_COLORS.length].bar)}
              style={{ width: `${Math.max(3, (Number(row.value) / max) * 100)}%` }}
            />
          </div>
        </div>
      ))}
    </div>
  )
}

/** Donut chart with a legend. `data` is [{ label, value }]. */
export function Donut({ data, format = (v) => formatNumber(v), size = 168 }) {
  const rows = (data ?? []).filter((d) => Number(d.value) > 0)
  if (!rows.length) return <NoData />

  const total = rows.reduce((s, d) => s + Number(d.value), 0)
  const radius = 60
  const circumference = 2 * Math.PI * radius
  let offset = 0

  return (
    <div className="flex flex-wrap items-center justify-center gap-6">
      <svg viewBox="0 0 160 160" width={size} height={size} className="shrink-0 -rotate-90">
        <circle cx="80" cy="80" r={radius} fill="none" strokeWidth="20" className="stroke-canvas" />
        {rows.map((row, i) => {
          const fraction = Number(row.value) / total
          const dash = fraction * circumference
          const seg = (
            <circle
              key={row.label}
              cx="80"
              cy="80"
              r={radius}
              fill="none"
              strokeWidth="20"
              stroke={SERIES_COLORS[i % SERIES_COLORS.length].stroke}
              strokeDasharray={`${dash} ${circumference - dash}`}
              strokeDashoffset={-offset}
            />
          )
          offset += dash
          return seg
        })}
      </svg>
      <ul className="space-y-2">
        {rows.map((row, i) => (
          <li key={row.label} className="flex items-center gap-2 text-sm">
            <span className="size-3 rounded-sm" style={{ background: SERIES_COLORS[i % SERIES_COLORS.length].stroke }} />
            <span className="text-ink">{row.label}</span>
            <span className="ml-auto pl-3 font-semibold tabular-nums text-muted">{format(row.value)}</span>
          </li>
        ))}
      </ul>
    </div>
  )
}

/** Two-series line/area chart for the cash-flow timeline. */
export function LineChart({ data, series }) {
  if (!data?.length) return <NoData />

  const w = 520
  const h = 180
  const pad = 8
  const max = Math.max(
    1,
    ...data.flatMap((row) => series.map((s) => Number(row[s.key] || 0))),
  )
  const stepX = (w - pad * 2) / Math.max(1, data.length - 1)
  const y = (v) => h - pad - (Number(v) / max) * (h - pad * 2)
  const x = (i) => pad + i * stepX

  return (
    <div>
      <svg viewBox={`0 0 ${w} ${h}`} className="h-44 w-full">
        {series.map((s, si) => {
          const color = SERIES_COLORS[si % SERIES_COLORS.length].stroke
          const points = data.map((row, i) => `${x(i)},${y(row[s.key])}`).join(' ')
          const area = `${pad},${h - pad} ${points} ${x(data.length - 1)},${h - pad}`
          return (
            <g key={s.key}>
              <polygon points={area} fill={color} opacity="0.08" />
              <polyline points={points} fill="none" stroke={color} strokeWidth="2.5" strokeLinejoin="round" strokeLinecap="round" />
              {data.map((row, i) => (
                <circle key={i} cx={x(i)} cy={y(row[s.key])} r="3" fill={color} />
              ))}
            </g>
          )
        })}
      </svg>
      <div className="mt-2 flex justify-between px-1 text-xs text-muted">
        {data.map((row) => <span key={row.label}>{row.label}</span>)}
      </div>
      <div className="mt-3 flex flex-wrap justify-center gap-4">
        {series.map((s, si) => (
          <span key={s.key} className="flex items-center gap-1.5 text-xs font-semibold text-muted">
            <span className="size-2.5 rounded-full" style={{ background: SERIES_COLORS[si % SERIES_COLORS.length].stroke }} />
            {s.label}
          </span>
        ))}
      </div>
    </div>
  )
}
