import Icon from '../ui/Icon'
import { cn } from '../../lib/cn'
import { isTable } from '../../lib/venueCatalog'

const TONE = {
  danger: 'bg-danger-soft text-danger',
  warning: 'bg-warning-soft text-warning',
}

/** Live statistics + validation warnings for the current layout. */
export default function StatsPanel({ objects, layout, seats, warnings }) {
  const tables = objects.filter((o) => isTable(o.object_type)).length
  const vipTables = objects.filter((o) => o.object_type === 'vip_table').length
  const danceFloors = objects.filter((o) => o.object_type.startsWith('dance_floor')).length
  const emergencyExits = objects.filter((o) => o.object_type === 'emergency_exit').length

  const stats = [
    { label: 'Planned seats', value: seats },
    { label: 'Capacity', value: layout.max_capacity ?? '—' },
    { label: 'Tables', value: tables },
    { label: 'VIP tables', value: vipTables },
    { label: 'Dance floors', value: danceFloors },
    { label: 'Emergency exits', value: emergencyExits },
  ]

  return (
    <div className="space-y-4">
      <div>
        <p className="mb-2 text-xs font-bold uppercase tracking-wide text-muted">Live statistics</p>
        <div className="grid grid-cols-2 gap-2">
          {stats.map((s) => (
            <div key={s.label} className="rounded-btn border border-line/70 px-2.5 py-2">
              <p className="text-lg font-extrabold text-ink">{s.value}</p>
              <p className="text-[0.7rem] text-muted">{s.label}</p>
            </div>
          ))}
        </div>
      </div>

      <div>
        <p className="mb-2 text-xs font-bold uppercase tracking-wide text-muted">Validation</p>
        {warnings.length ? (
          <ul className="space-y-1.5">
            {warnings.map((w) => (
              <li key={w.type} className={cn('flex items-start gap-2 rounded-btn px-2.5 py-2 text-xs font-medium', TONE[w.tone] ?? TONE.warning)}>
                <Icon name="TriangleAlert" className="mt-px size-3.5 shrink-0" />
                <span>{w.message}</span>
              </li>
            ))}
          </ul>
        ) : (
          <p className="flex items-center gap-2 rounded-btn bg-emerald-50 px-2.5 py-2 text-xs font-medium text-emerald-700">
            <Icon name="CheckCircle2" className="size-3.5" /> No issues detected
          </p>
        )}
      </div>
    </div>
  )
}
