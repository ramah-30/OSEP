/**
 * A compact area/line chart for time series. `data` is [{ date, value }].
 * Renders a smooth-ish polyline with a soft fill; purely presentational SVG.
 */
export default function LineChart({ data = [], height = 120, color = '#2947c8' }) {
  const width = 520
  const pad = 6
  const max = Math.max(1, ...data.map((d) => d.value))
  const n = data.length

  if (n === 0) {
    return <p className="py-8 text-center text-sm text-muted">No responses yet.</p>
  }

  const x = (i) => (n === 1 ? width / 2 : pad + (i * (width - pad * 2)) / (n - 1))
  const y = (v) => height - pad - (v / max) * (height - pad * 2)

  const points = data.map((d, i) => `${x(i)},${y(d.value)}`).join(' ')
  const area = `${pad},${height - pad} ${points} ${width - pad},${height - pad}`
  const total = data.reduce((s, d) => s + d.value, 0)

  return (
    <div>
      <svg viewBox={`0 0 ${width} ${height}`} className="w-full" preserveAspectRatio="none" style={{ height }}>
        <polygon points={area} fill={color} opacity="0.08" />
        <polyline points={points} fill="none" stroke={color} strokeWidth="2.5" strokeLinejoin="round" strokeLinecap="round" />
      </svg>
      <p className="mt-1 text-center text-xs text-muted">{total} responses over the last 30 days</p>
    </div>
  )
}
