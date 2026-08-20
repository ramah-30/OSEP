import { CHART_COLORS } from '../../lib/guestConstants'

/**
 * Horizontal bar chart for category / meal breakdowns. `data` is
 * [{ label, value }]; bars scale to the largest value.
 */
export default function BarChart({ data = [], color }) {
  const max = Math.max(1, ...data.map((d) => d.value))

  if (!data.length) {
    return <p className="py-8 text-center text-sm text-muted">No data yet.</p>
  }

  return (
    <ul className="space-y-3">
      {data.map((d, i) => (
        <li key={d.label}>
          <div className="mb-1 flex items-center justify-between text-sm">
            <span className="truncate text-muted">{d.label}</span>
            <span className="font-semibold text-ink tabular-nums">{d.value}</span>
          </div>
          <div className="h-2.5 overflow-hidden rounded-full bg-canvas">
            <div
              className="h-full rounded-full transition-[width] duration-500"
              style={{ width: `${(d.value / max) * 100}%`, background: color ?? CHART_COLORS[i % CHART_COLORS.length] }}
            />
          </div>
        </li>
      ))}
    </ul>
  )
}
