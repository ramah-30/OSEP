import { useCallback, useEffect, useMemo, useState } from 'react'
import Card from '../../../../components/ui/Card'
import Icon from '../../../../components/ui/Icon'
import Badge from '../../../../components/ui/Badge'
import Spinner from '../../../../components/ui/Spinner'
import EmptyState from '../../../../components/ui/EmptyState'
import ListboxSelect from '../../../../components/ui/ListboxSelect'
import { api } from '../../../../lib/api'
import { cn } from '../../../../lib/cn'
import { formatCurrency } from '../../../../lib/format'
import { healthTextClass, healthBarClass, healthTone } from '../../../../lib/ai'

export default function AiInsights() {
  const [events, setEvents] = useState([])
  const [eventId, setEventId] = useState('')
  const [analytics, setAnalytics] = useState(null)
  const [insights, setInsights] = useState(null)
  const [loading, setLoading] = useState(false)
  const [metaLoading, setMetaLoading] = useState(true)

  const [feedback, setFeedback] = useState(null)
  const [benchmarks, setBenchmarks] = useState(null)

  useEffect(() => {
    api.get('/ai/meta').then((r) => {
      const evs = r.data.data.events ?? []
      setEvents(evs)
      if (evs.length) setEventId(String(evs[0].id))
    }).finally(() => setMetaLoading(false))
    api.get('/ai/feedback/summary').then((r) => setFeedback(r.data.data)).catch(() => {})
    api.get('/ai/benchmarks').then((r) => setBenchmarks(r.data.data)).catch(() => {})
  }, [])

  const load = useCallback(async (id) => {
    if (!id) return
    setLoading(true)
    try {
      const [a, i] = await Promise.all([
        api.get('/ai/analytics', { params: { event_id: id } }),
        api.get('/ai/insights', { params: { event_id: id } }),
      ])
      setAnalytics(a.data.data)
      setInsights(i.data.data)
    } finally {
      setLoading(false)
    }
  }, [])

  useEffect(() => { if (eventId) load(eventId) }, [eventId, load])

  const eventOptions = useMemo(() => events.map((e) => ({ value: String(e.id), label: e.title })), [events])
  const health = analytics?.health
  const forecasts = analytics?.forecasts ?? []

  if (metaLoading) {
    return <div className="grid min-h-64 place-items-center"><Spinner className="size-7" /></div>
  }

  if (!events.length) {
    return <EmptyState icon="LineChart" title="No events yet" description="Create an event and its AI analytics — health score, forecasts and insights — will appear here." />
  }

  return (
    <div className="space-y-5">
      <div className="flex flex-wrap items-center justify-between gap-3">
        <div>
          <h2 className="text-lg font-bold text-ink">Event analytics</h2>
          <p className="text-sm text-muted">AI health score, forecasts and per-domain insights.</p>
        </div>
        <ListboxSelect heightClass="h-10" className="min-w-[220px]" options={eventOptions} value={eventId} onChange={(e) => setEventId(e.target.value)} />
      </div>

      {feedback && feedback.total > 0 && <FeedbackSummary data={feedback} />}

      {loading || !health ? (
        <div className="grid min-h-64 place-items-center"><Spinner className="size-7" /></div>
      ) : (
        <>
          {/* Health score + breakdown */}
          <div className="grid gap-4 lg:grid-cols-[300px_1fr]">
            <Card className="flex flex-col items-center justify-center p-6 text-center">
              <p className="text-sm font-medium text-muted">Event Health Score</p>
              <p className={cn('mt-2 text-6xl font-extrabold tabular-nums', healthTextClass(health.score))}>{health.score}</p>
              <p className="text-sm text-muted">/ 100</p>
              <Badge tone={healthTone(health.score)} className="mt-3">{health.label}</Badge>
            </Card>
            <Card className="p-5">
              <p className="mb-3 text-sm font-semibold text-ink">Score breakdown</p>
              <div className="space-y-3">
                {(health.breakdown ?? []).map((c) => (
                  <div key={c.key}>
                    <div className="flex items-center justify-between text-sm">
                      <span className="font-medium text-ink">{c.label} <span className="text-xs text-muted">· weight {c.weight}%</span></span>
                      <span className={cn('font-bold tabular-nums', healthTextClass(c.score))}>{c.score}</span>
                    </div>
                    <div className="mt-1.5 h-2 overflow-hidden rounded-full bg-canvas">
                      <div className={cn('h-full rounded-full', healthBarClass(c.score))} style={{ width: `${c.score}%` }} />
                    </div>
                    <p className="mt-1 text-xs text-muted">{c.note}</p>
                  </div>
                ))}
              </div>
            </Card>
          </div>

          {/* Forecasts */}
          {forecasts.length > 0 && (
            <div>
              <p className="mb-2 flex items-center gap-2 text-sm font-semibold text-ink">
                <Icon name="TrendingUp" className="size-4 text-purple-500" /> Predictive forecasts
              </p>
              <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                {forecasts.map((f) => (
                  <Card key={f.key} className="p-5">
                    <div className="flex items-center justify-between">
                      <p className="text-sm font-medium text-muted">{f.label}</p>
                      <Badge tone="purple">{f.confidence}%</Badge>
                    </div>
                    <p className="mt-2 text-2xl font-extrabold tracking-tight text-ink">{f.value}</p>
                    <p className="mt-1 text-xs text-muted">{f.basis}</p>
                  </Card>
                ))}
              </div>
            </div>
          )}

          {/* Domain insights */}
          <div className="grid gap-3 md:grid-cols-2">
            <InsightSection icon="Wallet" title="Budget" show={insights?.budget}>
              {insights?.budget && (
                <>
                  <Row label="Allocated" value={formatCurrency(insights.budget.total)} />
                  <Row label="Committed" value={`${formatCurrency(insights.budget.spent)} (${insights.budget.utilization_pct}%)`} />
                  <Row label="Remaining" value={formatCurrency(insights.budget.remaining)} tone={insights.budget.over_budget ? 'danger' : undefined} />
                </>
              )}
            </InsightSection>

            <InsightSection icon="CalendarClock" title="Timeline" show={insights?.timeline}>
              {insights?.timeline && (
                <>
                  <Row label="Tasks complete" value={`${insights.timeline.tasks_done}/${insights.timeline.tasks_total}`} />
                  <Row label="Milestones complete" value={`${insights.timeline.milestones_done}/${insights.timeline.milestones_total}`} />
                  <Row label="Overdue" value={insights.timeline.overdue_count} tone={insights.timeline.overdue_count ? 'danger' : undefined} />
                </>
              )}
            </InsightSection>

            <InsightSection icon="Users" title="Guests" show={insights?.guests}>
              {insights?.guests && (
                <>
                  <Row label="Invited" value={insights.guests.total} />
                  <Row label="Confirmed" value={insights.guests.confirmed} />
                  <Row label="Response rate" value={`${insights.guests.confirmation_rate}%`} />
                </>
              )}
            </InsightSection>

            <InsightSection icon="Store" title="Vendors" show={insights?.vendors}>
              {insights?.vendors && (
                <>
                  <Row label="Assigned" value={insights.vendors.count} />
                  <Row label="Unconfirmed" value={insights.vendors.pending} tone={insights.vendors.pending ? 'danger' : undefined} />
                </>
              )}
            </InsightSection>

            <InsightSection icon="CircleDollarSign" title="Finance" show={insights?.finance}>
              {insights?.finance && (
                <>
                  <Row label="Invoiced" value={formatCurrency(insights.finance.invoiced_total)} />
                  <Row label="Received" value={formatCurrency(insights.finance.payments_received)} />
                  <Row label="Outstanding" value={formatCurrency(insights.finance.outstanding_amount)} tone={insights.finance.outstanding_amount ? 'danger' : undefined} />
                </>
              )}
            </InsightSection>
          </div>

          {/* What-if calculator */}
          <WhatIfCalculator eventId={eventId} />

          {/* Learned from the planner's own history */}
          <HistoryBenchmarks comparison={insights?.benchmark} quoteFlags={insights?.quote_flags} data={benchmarks} />
        </>
      )}
    </div>
  )
}

/**
 * "Learned from your history": the planner's own delivered events turned into a
 * private benchmark — how this event's budget split compares to their norm, any
 * quotes running above what they usually pay, and per-service vendor scorecards.
 */
function HistoryBenchmarks({ comparison, quoteFlags, data }) {
  const anomalies = comparison?.anomalies ?? []
  const categories = (comparison?.categories ?? []).filter((c) => c.benchmark_pct > 0 || c.event_pct > 0).slice(0, 6)
  const vendors = data?.vendors ?? []
  const flags = quoteFlags ?? []

  const hasAnything = comparison || flags.length > 0 || vendors.length > 0
  if (!hasAnything) {
    return (
      <Card className="p-5">
        <p className="mb-1 flex items-center gap-2 text-sm font-semibold text-ink">
          <Icon name="History" className="size-4 text-purple-500" /> Learned from your history
        </p>
        <p className="text-sm text-muted">
          Once you've delivered a few events, this becomes your private benchmark — typical budget splits, vendor
          scorecards and quote sanity checks drawn from your own track record.
        </p>
      </Card>
    )
  }

  return (
    <Card className="p-5">
      <p className="mb-1 flex items-center gap-2 text-sm font-semibold text-ink">
        <Icon name="History" className="size-4 text-purple-500" /> Learned from your history
      </p>
      <p className="mb-4 text-xs text-muted">
        Benchmarks drawn only from events you've delivered{comparison ? ` · comparing against ${comparison.sample_events} past ${comparison.event_type || 'event'}(s)` : ''}.
      </p>

      {anomalies.length > 0 && (
        <div className="mb-4 space-y-2">
          {anomalies.slice(0, 3).map((a) => (
            <div key={a.name} className="flex items-start gap-2 rounded-xl border border-line bg-canvas p-3 text-sm">
              <Icon name={a.direction === 'over' ? 'TrendingUp' : 'TrendingDown'} className={cn('mt-0.5 size-4 shrink-0', a.direction === 'over' ? 'text-warning' : 'text-navy-600')} />
              <p className="text-ink">
                <span className="font-semibold">{a.name}</span> is <span className="font-semibold tabular-nums">{a.event_pct}%</span> of this budget vs your usual <span className="font-semibold tabular-nums">{a.benchmark_pct}%</span>.
              </p>
            </div>
          ))}
        </div>
      )}

      {flags.length > 0 && (
        <div className="mb-4 space-y-2">
          {flags.slice(0, 3).map((q) => (
            <div key={`${q.service}-${q.name}`} className="flex items-start gap-2 rounded-xl border border-warning/30 bg-warning/5 p-3 text-sm">
              <Icon name="AlertTriangle" className="mt-0.5 size-4 shrink-0 text-warning" />
              <p className="text-ink">
                <span className="font-semibold">{q.name}</span> ({q.service}) is <span className="font-semibold tabular-nums">{q.delta_pct}%</span> above your usual {formatCurrency(q.your_avg)}.
              </p>
            </div>
          ))}
        </div>
      )}

      {categories.length > 0 && (
        <div className="mb-4">
          <p className="mb-2 text-xs font-semibold text-muted">Budget split — this event vs your norm</p>
          <div className="space-y-2.5">
            {categories.map((c) => (
              <div key={c.name}>
                <div className="mb-1 flex items-center justify-between text-xs">
                  <span className="font-medium text-ink">{c.name}</span>
                  <span className="tabular-nums text-muted">this <span className="font-semibold text-ink">{c.event_pct}%</span> · norm {c.benchmark_pct}%</span>
                </div>
                <div className="relative h-2 overflow-hidden rounded-full bg-canvas">
                  <div className="absolute inset-y-0 left-0 rounded-full bg-navy-500/40" style={{ width: `${Math.min(100, c.benchmark_pct)}%` }} />
                  <div className="absolute inset-y-0 left-0 rounded-full bg-purple-500" style={{ width: `${Math.min(100, c.event_pct)}%` }} />
                </div>
              </div>
            ))}
          </div>
        </div>
      )}

      {vendors.length > 0 && (
        <div>
          <p className="mb-2 text-xs font-semibold text-muted">Vendor scorecards</p>
          <div className="grid gap-2 sm:grid-cols-2">
            {vendors.slice(0, 6).map((v) => (
              <div key={v.service} className="rounded-xl border border-line bg-canvas p-3">
                <div className="flex items-center justify-between">
                  <p className="text-sm font-semibold text-ink">{v.service}</p>
                  {v.reliability_pct !== null && (
                    <Badge tone={v.reliability_pct >= 75 ? 'emerald' : v.reliability_pct >= 50 ? 'amber' : 'danger'}>{v.reliability_pct}% reliable</Badge>
                  )}
                </div>
                <p className="mt-1 text-xs text-muted">
                  {v.uses} use(s){v.avg_price > 0 ? ` · avg ${formatCurrency(v.avg_price)}` : ''}{v.top_vendor ? ` · ${v.top_vendor}` : ''}
                </p>
              </div>
            ))}
          </div>
        </div>
      )}
    </Card>
  )
}

/**
 * An offline "what-if" calculator: the planner nudges the guest count (and table
 * size) and instantly sees the catering cost impact, tables needed, a venue
 * capacity check and the per-dish quantities the caterer would need. Every
 * figure is deterministic maths over the event's real budget and guest data.
 */
function WhatIfCalculator({ eventId }) {
  const [delta, setDelta] = useState(20)
  const [seats, setSeats] = useState(10)
  const [data, setData] = useState(null)
  const [loading, setLoading] = useState(false)

  useEffect(() => {
    if (!eventId) return
    const ctrl = new AbortController()
    const t = setTimeout(() => {
      setLoading(true)
      api.get('/ai/scenario', {
        params: { event_id: eventId, guests_delta: delta, seats_per_table: seats },
        signal: ctrl.signal,
      })
        .then((r) => setData(r.data.data))
        .catch(() => {})
        .finally(() => setLoading(false))
    }, 250)
    return () => { clearTimeout(t); ctrl.abort() }
  }, [eventId, delta, seats])

  const base = data?.baseline
  const proj = data?.projection
  const noBasis = base && base.per_head <= 0

  return (
    <Card className="p-5">
      <p className="mb-1 flex items-center gap-2 text-sm font-semibold text-ink">
        <Icon name="Calculator" className="size-4 text-purple-500" /> What-if calculator
      </p>
      <p className="mb-4 text-xs text-muted">Model a guest-count change against this event's real budget and catering — no guesses.</p>

      {noBasis ? (
        <p className="rounded-xl border border-line bg-canvas p-3 text-sm text-muted">
          Add a budget (ideally a catering line) and an expected guest count and the calculator will project costs instantly.
        </p>
      ) : (
        <>
          <div className="grid gap-4 sm:grid-cols-2">
            <label className="block">
              <span className="mb-1.5 flex items-center justify-between text-xs font-medium text-muted">
                <span>Guest change</span>
                <span className={cn('tabular-nums font-bold', delta >= 0 ? 'text-emerald-600' : 'text-danger')}>{delta >= 0 ? `+${delta}` : delta}</span>
              </span>
              <input
                type="range" min={-100} max={200} step={5} value={delta}
                onChange={(e) => setDelta(Number(e.target.value))}
                className="w-full accent-purple-500"
              />
            </label>
            <label className="block">
              <span className="mb-1.5 block text-xs font-medium text-muted">Seats per table</span>
              <input
                type="number" min={1} max={30} value={seats}
                onChange={(e) => setSeats(Math.max(1, Number(e.target.value) || 1))}
                className="h-10 w-full rounded-xl border border-line bg-surface px-3 text-sm text-ink outline-none focus:border-purple-400"
              />
            </label>
          </div>

          {base && (
            <p className="mt-4 text-xs text-muted">
              Basis: <span className="font-semibold text-ink">{formatCurrency(base.per_head)}/head</span> ({base.per_head_basis}) · {base.current_guests} guest(s) now
            </p>
          )}

          {proj && (
            <div className={cn('mt-3 grid gap-3 sm:grid-cols-2 lg:grid-cols-4', loading && 'opacity-60')}>
              <Stat label="New headcount" value={proj.new_guests} sub={`${proj.guests_delta >= 0 ? '+' : ''}${proj.guests_delta}`} />
              <Stat label="Catering impact" value={`${proj.added_cost >= 0 ? '+' : '−'}${formatCurrency(Math.abs(proj.added_cost))}`} sub={`total ${formatCurrency(proj.projected_catering)}`} tone={proj.added_cost > 0 ? 'amber' : 'emerald'} />
              <Stat label="Tables needed" value={proj.tables_needed} sub={`${proj.tables_delta >= 0 ? '+' : ''}${proj.tables_delta} vs now`} />
              <Stat
                label="Venue capacity"
                value={proj.capacity_ok === null ? '—' : proj.capacity_ok ? 'Fits' : 'Over'}
                sub={proj.capacity_ok === false ? `by ${proj.over_capacity_by}` : base?.capacity ? `cap ${base.capacity}` : 'not set'}
                tone={proj.capacity_ok === false ? 'danger' : proj.capacity_ok === true ? 'emerald' : undefined}
              />
            </div>
          )}

          {proj?.meal_rollup?.length > 0 && (
            <div className="mt-4 rounded-xl border border-line bg-canvas p-3">
              <p className="mb-2 text-xs font-semibold text-muted">Meal quantities for the caterer ({proj.new_guests} guests)</p>
              <div className="flex flex-wrap gap-2">
                {proj.meal_rollup.map((m) => (
                  <span key={m.name} className="inline-flex items-center gap-1.5 rounded-lg border border-line bg-surface px-2.5 py-1 text-xs">
                    <span className="text-muted">{m.name}</span>
                    <span className="font-bold tabular-nums text-ink">{m.count}</span>
                  </span>
                ))}
              </div>
            </div>
          )}
        </>
      )}
    </Card>
  )
}

function Stat({ label, value, sub, tone }) {
  const toneClass = tone === 'danger' ? 'text-danger' : tone === 'amber' ? 'text-warning' : tone === 'emerald' ? 'text-emerald-600' : 'text-ink'
  return (
    <div className="rounded-xl border border-line bg-canvas p-3">
      <p className="text-xs text-muted">{label}</p>
      <p className={cn('mt-1 text-xl font-extrabold tracking-tight tabular-nums', toneClass)}>{value}</p>
      {sub && <p className="mt-0.5 text-xs text-muted">{sub}</p>}
    </div>
  )
}

function FeedbackSummary({ data }) {
  const rate = data.positive_rate ?? 0
  const tone = rate >= 75 ? 'text-emerald-600' : rate >= 50 ? 'text-warning' : 'text-danger'
  return (
    <Card className="p-5">
      <p className="mb-3 flex items-center gap-2 text-sm font-semibold text-ink">
        <Icon name="ThumbsUp" className="size-4 text-emerald-500" /> AI quality — from your feedback
      </p>
      <div className="grid gap-4 sm:grid-cols-[auto_1fr] sm:items-center">
        <div className="flex items-center gap-5">
          <div className="text-center">
            <p className={cn('text-3xl font-extrabold tabular-nums', tone)}>{rate}%</p>
            <p className="text-xs text-muted">helpful</p>
          </div>
          <div className="space-y-1 text-sm">
            <p className="flex items-center gap-1.5 text-emerald-600"><Icon name="ThumbsUp" className="size-3.5" /> {data.up} up</p>
            <p className="flex items-center gap-1.5 text-danger"><Icon name="ThumbsDown" className="size-3.5" /> {data.down} down</p>
          </div>
        </div>
        {data.recent_negative?.length > 0 && (
          <div className="rounded-xl border border-line bg-canvas p-3">
            <p className="mb-1.5 text-xs font-semibold text-muted">Recent notes to improve on</p>
            <ul className="space-y-1.5">
              {data.recent_negative.map((n, i) => (
                <li key={i} className="text-xs text-ink">
                  <span className="text-muted">“{n.reason}”</span> — <span className="italic text-muted">{n.subject}</span>
                </li>
              ))}
            </ul>
          </div>
        )}
      </div>
    </Card>
  )
}

function InsightSection({ icon, title, show, children }) {
  return (
    <Card className="p-5">
      <p className="mb-3 flex items-center gap-2 text-sm font-semibold text-ink">
        <Icon name={icon} className="size-4 text-navy-600" /> {title}
      </p>
      {show ? <div className="space-y-2">{children}</div>
        : <p className="text-sm text-muted">No data yet.</p>}
    </Card>
  )
}

function Row({ label, value, tone }) {
  return (
    <div className="flex items-center justify-between text-sm">
      <span className="text-muted">{label}</span>
      <span className={cn('font-semibold tabular-nums', tone === 'danger' ? 'text-danger' : 'text-ink')}>{value}</span>
    </div>
  )
}
