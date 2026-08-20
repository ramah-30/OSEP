import { useMemo, useState } from 'react'
import { Link } from 'react-router-dom'
import PageHeader from '../../../components/ui/PageHeader'
import Button from '../../../components/ui/Button'
import Icon from '../../../components/ui/Icon'
import Card from '../../../components/ui/Card'
import LoadState from '../../../components/dashboard/LoadState'
import { useResource } from '../../../lib/useResource'
import { useAuth } from '../../../context/AuthContext'
import { cn } from '../../../lib/cn'

const WEEKDAYS = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun']
const MONTHS = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December']
const TYPE_TONE = {
  event: 'bg-navy-100 text-navy-800',
  task: 'bg-warning-soft text-warning',
  milestone: 'bg-purple-100 text-purple-700',
}

const pad = (n) => String(n).padStart(2, '0')
const iso = (d) => `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`
const addDays = (d, n) => { const x = new Date(d); x.setDate(x.getDate() + n); return x }
const startOfWeek = (d) => { const x = new Date(d); const day = (x.getDay() + 6) % 7; return addDays(x, -day) } // Monday

export default function CalendarPage() {
  const { user } = useAuth()
  const [view, setView] = useState('month')
  const [cursor, setCursor] = useState(() => new Date())

  const range = useMemo(() => {
    if (view === 'day') return { from: cursor, to: cursor }
    if (view === 'week') { const s = startOfWeek(cursor); return { from: s, to: addDays(s, 6) } }
    const first = new Date(cursor.getFullYear(), cursor.getMonth(), 1)
    const gridStart = startOfWeek(first)
    return { from: gridStart, to: addDays(gridStart, 41) }
  }, [view, cursor])

  const path = `/calendar?from=${iso(range.from)}&to=${iso(range.to)}`
  const { data, loading, error, reload } = useResource(path)

  const byDate = useMemo(() => {
    const map = {}
    for (const item of data?.items ?? []) (map[item.date] ??= []).push(item)
    return map
  }, [data])

  function shift(dir) {
    if (view === 'day') setCursor((c) => addDays(c, dir))
    else if (view === 'week') setCursor((c) => addDays(c, dir * 7))
    else setCursor((c) => new Date(c.getFullYear(), c.getMonth() + dir, 1))
  }

  const heading = view === 'month'
    ? `${MONTHS[cursor.getMonth()]} ${cursor.getFullYear()}`
    : view === 'week'
      ? `Week of ${range.from.getDate()} ${MONTHS[range.from.getMonth()]}`
      : `${cursor.getDate()} ${MONTHS[cursor.getMonth()]} ${cursor.getFullYear()}`

  return (
    <div className="space-y-6">
      <PageHeader
        title="Calendar"
        description="Events, deadlines and milestones across everything you're planning."
        actions={
          <div className="flex items-center gap-1 rounded-btn border border-line bg-surface p-1">
            {['month', 'week', 'day'].map((v) => (
              <button key={v} type="button" onClick={() => setView(v)}
                className={cn('rounded-[9px] px-3 py-1.5 text-sm font-semibold capitalize transition-colors',
                  view === v ? 'bg-navy-800 text-white' : 'text-muted hover:text-ink')}>
                {v}
              </button>
            ))}
          </div>
        }
      />

      <div className="flex items-center justify-between">
        <div className="flex items-center gap-2">
          <button type="button" onClick={() => shift(-1)} className="grid size-9 place-items-center rounded-btn border border-line hover:bg-canvas"><Icon name="ChevronLeft" className="size-4" /></button>
          <button type="button" onClick={() => shift(1)} className="grid size-9 place-items-center rounded-btn border border-line hover:bg-canvas"><Icon name="ChevronRight" className="size-4" /></button>
          <Button variant="ghost" size="sm" onClick={() => setCursor(new Date())}>Today</Button>
        </div>
        <h2 className="text-lg font-extrabold text-ink">{heading}</h2>
      </div>

      <LoadState loading={loading} error={error} onRetry={reload}>
        {data && (
          <>
            {view === 'month' && <MonthGrid cursor={cursor} range={range} byDate={byDate} base={user.dashboard_path} />}
            {view === 'week' && <AgendaList days={7} start={range.from} byDate={byDate} base={user.dashboard_path} />}
            {view === 'day' && <AgendaList days={1} start={range.from} byDate={byDate} base={user.dashboard_path} />}
          </>
        )}
      </LoadState>
    </div>
  )
}

function EventPill({ item, base }) {
  return (
    <Link to={`${base}/events/${item.event_id}`}
      className={cn('block truncate rounded px-1.5 py-0.5 text-[0.7rem] font-semibold', TYPE_TONE[item.type] ?? 'bg-canvas text-muted')}>
      {item.title}
    </Link>
  )
}

function MonthGrid({ cursor, range, byDate, base }) {
  const days = Array.from({ length: 42 }, (_, i) => addDays(range.from, i))
  const todayIso = iso(new Date())
  return (
    <Card className="overflow-hidden p-0">
      <div className="grid grid-cols-7 border-b border-line bg-canvas/50">
        {WEEKDAYS.map((d) => <div key={d} className="px-2 py-2 text-center text-xs font-bold uppercase tracking-wide text-muted">{d}</div>)}
      </div>
      <div className="grid grid-cols-7">
        {days.map((day) => {
          const key = iso(day)
          const items = byDate[key] ?? []
          const inMonth = day.getMonth() === cursor.getMonth()
          return (
            <div key={key} className={cn('min-h-24 border-b border-r border-line/70 p-1.5', !inMonth && 'bg-canvas/40')}>
              <div className={cn('mb-1 text-right text-xs font-semibold',
                key === todayIso ? 'text-navy-800' : inMonth ? 'text-ink' : 'text-muted')}>
                <span className={cn(key === todayIso && 'inline-grid size-5 place-items-center rounded-full bg-navy-800 text-white')}>{day.getDate()}</span>
              </div>
              <div className="space-y-1">
                {items.slice(0, 3).map((item) => <EventPill key={item.id} item={item} base={base} />)}
                {items.length > 3 && <p className="px-1 text-[0.65rem] text-muted">+{items.length - 3} more</p>}
              </div>
            </div>
          )
        })}
      </div>
    </Card>
  )
}

function AgendaList({ days, start, byDate, base }) {
  const list = Array.from({ length: days }, (_, i) => addDays(start, i))
  const todayIso = iso(new Date())
  return (
    <div className="space-y-3">
      {list.map((day) => {
        const key = iso(day)
        const items = byDate[key] ?? []
        return (
          <Card key={key} className="p-4">
            <p className={cn('mb-2 text-sm font-bold', key === todayIso ? 'text-navy-800' : 'text-ink')}>
              {WEEKDAYS[(day.getDay() + 6) % 7]} · {day.getDate()} {MONTHS[day.getMonth()]}
            </p>
            {items.length ? (
              <ul className="space-y-1.5">
                {items.map((item) => (
                  <li key={item.id}>
                    <Link to={`${base}/events/${item.event_id}`} className="flex items-center gap-2 rounded-btn px-2 py-1.5 text-sm hover:bg-canvas">
                      <span className={cn('rounded px-1.5 py-0.5 text-[0.65rem] font-bold uppercase', TYPE_TONE[item.type])}>{item.type}</span>
                      <span className="font-semibold text-ink">{item.title}</span>
                      {item.event_title && <span className="text-muted">· {item.event_title}</span>}
                    </Link>
                  </li>
                ))}
              </ul>
            ) : (
              <p className="text-sm text-muted">Nothing scheduled.</p>
            )}
          </Card>
        )
      })}
    </div>
  )
}
