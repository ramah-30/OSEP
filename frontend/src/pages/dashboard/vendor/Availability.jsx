import { useEffect, useMemo, useState } from 'react'
import PageHeader from '../../../components/ui/PageHeader'
import Card from '../../../components/ui/Card'
import Button from '../../../components/ui/Button'
import Icon from '../../../components/ui/Icon'
import LoadState from '../../../components/dashboard/LoadState'
import { api } from '../../../lib/api'
import { useResource } from '../../../lib/useResource'
import { cn } from '../../../lib/cn'
import { SLOT_STATUS } from '../../../lib/marketplace'

const CYCLE = ['available', 'reserved', 'fully_booked', 'on_leave']
const WEEKDAYS = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun']
const MONTHS = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December']

const pad = (n) => String(n).padStart(2, '0')
const iso = (d) => `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`
const addDays = (d, n) => { const x = new Date(d); x.setDate(x.getDate() + n); return x }
const startOfWeek = (d) => { const x = new Date(d); const day = (x.getDay() + 6) % 7; return addDays(x, -day) } // Monday

export default function Availability() {
  const { data, loading, error, reload } = useResource('/marketplace/vendor/availability')
  const [overrides, setOverrides] = useState({})
  const [saving, setSaving] = useState(false)
  const [cursor, setCursor] = useState(() => new Date())

  useEffect(() => {
    setOverrides({})
  }, [data])

  const baseline = useMemo(() => {
    const map = {}
    ;(data?.availability ?? []).forEach((s) => { map[s.date] = s.status })
    return map
  }, [data])

  // 6-week grid anchored to the visible month, Monday-first (matches the planner calendar).
  const days = useMemo(() => {
    const first = new Date(cursor.getFullYear(), cursor.getMonth(), 1)
    const gridStart = startOfWeek(first)
    return Array.from({ length: 42 }, (_, i) => addDays(gridStart, i))
  }, [cursor])

  const todayIso = iso(new Date())
  const statusOf = (date) => overrides[date] ?? baseline[date] ?? 'available'

  const cycle = (date) => {
    const current = statusOf(date)
    const next = CYCLE[(CYCLE.indexOf(current) + 1) % CYCLE.length]
    setOverrides((o) => ({ ...o, [date]: next }))
  }

  const save = async () => {
    const slots = Object.entries(overrides).map(([date, status]) => ({ date, status }))
    if (!slots.length) return
    setSaving(true)
    try {
      await api.post('/marketplace/vendor/availability', { slots })
      reload()
    } finally {
      setSaving(false)
    }
  }

  const dirty = Object.keys(overrides).length > 0
  const heading = `${MONTHS[cursor.getMonth()]} ${cursor.getFullYear()}`

  return (
    <div className="space-y-6">
      <PageHeader
        title="Availability"
        description="Tap a day to cycle its status. Planners see this before sending a request."
        actions={<Button onClick={save} loading={saving} disabled={!dirty}>Save changes</Button>}
      />

      <div className="flex flex-wrap items-center justify-between gap-3">
        <div className="flex items-center gap-2">
          <button type="button" onClick={() => setCursor((c) => new Date(c.getFullYear(), c.getMonth() - 1, 1))} className="grid size-9 place-items-center rounded-btn border border-line hover:bg-canvas"><Icon name="ChevronLeft" className="size-4" /></button>
          <button type="button" onClick={() => setCursor((c) => new Date(c.getFullYear(), c.getMonth() + 1, 1))} className="grid size-9 place-items-center rounded-btn border border-line hover:bg-canvas"><Icon name="ChevronRight" className="size-4" /></button>
          <Button variant="ghost" size="sm" onClick={() => setCursor(new Date())}>Today</Button>
        </div>
        <h2 className="text-lg font-extrabold text-ink">{heading}</h2>
      </div>

      <div className="flex flex-wrap gap-3">
        {CYCLE.map((s) => (
          <span key={s} className="flex items-center gap-1.5 text-sm text-muted">
            <span className={cn('size-3 rounded-full', SLOT_STATUS[s].dot)} /> {SLOT_STATUS[s].label}
          </span>
        ))}
      </div>

      <LoadState loading={loading} error={error} onRetry={reload}>
        {data && (
          <Card className="overflow-hidden p-0">
            <div className="grid grid-cols-7 border-b border-line bg-canvas/50">
              {WEEKDAYS.map((d) => (
                <div key={d} className="px-2 py-2 text-center text-xs font-bold uppercase tracking-wide text-muted">{d}</div>
              ))}
            </div>
            <div className="grid grid-cols-7">
              {days.map((day) => {
                const date = iso(day)
                const inMonth = day.getMonth() === cursor.getMonth()
                const isPast = date < todayIso
                const isToday = date === todayIso
                const editable = inMonth && !isPast
                const status = statusOf(date)
                const meta = SLOT_STATUS[status]

                return (
                  <button
                    key={date}
                    type="button"
                    disabled={!editable}
                    onClick={() => cycle(date)}
                    className={cn(
                      'flex min-h-24 flex-col border-b border-r border-line/70 p-1.5 text-left transition-colors',
                      !inMonth && 'bg-canvas/40',
                      editable ? 'hover:bg-canvas cursor-pointer' : 'cursor-default',
                      overrides[date] && 'ring-2 ring-inset ring-navy-300',
                    )}
                  >
                    <div className={cn('mb-1 text-right text-xs font-semibold',
                      isToday ? 'text-navy-800' : inMonth ? 'text-ink' : 'text-muted')}>
                      <span className={cn(isToday && 'inline-grid size-5 place-items-center rounded-full bg-navy-800 text-white')}>{day.getDate()}</span>
                    </div>
                    {editable && (
                      <div className="mt-auto flex items-center gap-1.5">
                        <span className={cn('size-2.5 shrink-0 rounded-full', meta.dot)} />
                        <span className="truncate text-[0.7rem] font-medium text-muted">{meta.label}</span>
                      </div>
                    )}
                  </button>
                )
              })}
            </div>
          </Card>
        )}
      </LoadState>
    </div>
  )
}
