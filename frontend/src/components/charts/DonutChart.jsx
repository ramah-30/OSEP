import { CHART_COLORS } from '../../lib/guestConstants'

/**
 * A lightweight SVG donut with legend. `data` is [{ label, value, color? }].
 * No chart library — matches the app's hand-built calendar/venue approach.
 */
export default function DonutChart({ data = [], size = 168, thickness = 22 }) {
  const total = data.reduce((sum, d) => sum + d.value, 0)
  const radius = (size - thickness) / 2
  const circumference = 2 * Math.PI * radius
  const center = size / 2

  let offset = 0
  const segments = total > 0
    ? data.filter((d) => d.value > 0).map((d, i) => {
        const fraction = d.value / total
        const seg = {
          color: d.color ?? CHART_COLORS[i % CHART_COLORS.length],
          dash: fraction * circumference,
          gap: circumference - fraction * circumference,
          offset,
        }
        offset -= fraction * circumference
        return seg
      })
    : []

  return (
    <div className="flex flex-wrap items-center gap-6">
      <div className="relative shrink-0" style={{ width: size, height: size }}>
        <svg width={size} height={size} className="-rotate-90">
          <circle cx={center} cy={center} r={radius} fill="none" strokeWidth={thickness} className="stroke-canvas" />
          {segments.map((s, i) => (
            <circle
              key={i}
              cx={center}
              cy={center}
              r={radius}
              fill="none"
              stroke={s.color}
              strokeWidth={thickness}
              strokeDasharray={`${s.dash} ${s.gap}`}
              strokeDashoffset={s.offset}
              strokeLinecap="butt"
            />
          ))}
        </svg>
        <div className="absolute inset-0 grid place-items-center text-center">
          <div>
            <p className="text-2xl font-extrabold text-ink tabular-nums">{total}</p>
            <p className="text-xs text-muted">Total</p>
          </div>
        </div>
      </div>

      <ul className="min-w-0 flex-1 space-y-2">
        {data.map((d, i) => (
          <li key={d.label} className="flex items-center gap-2.5 text-sm">
            <span className="size-3 shrink-0 rounded-sm" style={{ background: d.color ?? CHART_COLORS[i % CHART_COLORS.length] }} />
            <span className="flex-1 truncate text-muted">{d.label}</span>
            <span className="font-semibold text-ink tabular-nums">{d.value}</span>
            <span className="w-10 text-right text-xs text-muted tabular-nums">
              {total > 0 ? Math.round((d.value / total) * 100) : 0}%
            </span>
          </li>
        ))}
      </ul>
    </div>
  )
}
