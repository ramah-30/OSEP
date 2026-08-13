import { useEffect, useState } from 'react'
import { Link, useNavigate } from 'react-router-dom'
import Card from '../../../../components/ui/Card'
import Icon from '../../../../components/ui/Icon'
import Badge from '../../../../components/ui/Badge'
import Button from '../../../../components/ui/Button'
import Spinner from '../../../../components/ui/Spinner'
import EmptyState from '../../../../components/ui/EmptyState'
import { api } from '../../../../lib/api'
import { cn } from '../../../../lib/cn'
import { formatRelative } from '../../../../lib/format'
import { PRIORITY_META, categoryMeta, healthTextClass, healthBarClass, healthTone, recommendationHref } from '../../../../lib/ai'
import { useAiChat } from '../../../../context/AiChatContext'

const BASE = '/dashboard/planner/ai-assistant'

const QUICK_PROMPTS = [
  'Summarize today’s outstanding tasks',
  'Which events are most at risk?',
  'Draft a 12-month wedding timeline',
  'What should a corporate event budget include?',
]

export default function AiDashboard() {
  const navigate = useNavigate()
  const { open: openChat } = useAiChat()
  const [data, setData] = useState(null)
  const [loading, setLoading] = useState(true)

  useEffect(() => {
    api.get('/ai/dashboard').then((r) => setData(r.data.data)).finally(() => setLoading(false))
  }, [])

  if (loading) return <div className="grid min-h-64 place-items-center"><Spinner className="size-7" /></div>
  if (!data) return <EmptyState icon="TriangleAlert" title="Couldn't load the AI dashboard" />

  const stats = data.stats ?? {}
  const recommendations = data.recommendations ?? []
  const health = data.health ?? []
  const forecast = data.forecast ?? { items: [] }
  const conversations = data.conversations ?? []
  const onboarding = data.onboarding

  return (
    <div className="space-y-5">
      {/* Setup checklist — only until the planner is fully onboarded */}
      {onboarding && !onboarding.complete && (
        <OnboardingCard data={onboarding} navigate={navigate} openChat={openChat} />
      )}

      {/* Stat tiles */}
      <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
        <Tile icon="CalendarClock" label="Active events" value={stats.active_events ?? 0} accent="navy" />
        <Tile icon="BellRing" label="Open reminders" value={stats.open_recommendations ?? 0} accent="purple" />
        <Tile icon="Activity" label="Avg health" value={stats.avg_health != null ? `${stats.avg_health}/100` : '—'} accent="emerald" tone={stats.avg_health} />
        <Tile icon="MessagesSquare" label="Conversations" value={stats.conversations ?? 0} accent="navy" />
      </div>

      <div className="grid gap-5 lg:grid-cols-3">
        {/* Recommendations */}
        <Card className="p-5 lg:col-span-2">
          <div className="mb-3 flex items-center justify-between">
            <p className="flex items-center gap-2 font-bold text-ink"><Icon name="BellRing" className="size-4 text-purple-500" /> Today’s reminders</p>
            <Link to={`${BASE}/recommendations`} className="text-sm font-semibold text-navy-700 hover:underline">View all</Link>
          </div>
          {recommendations.length === 0 ? (
            <p className="py-6 text-center text-sm text-muted">No open reminders — everything looks healthy. ✅</p>
          ) : (
            <ul className="divide-y divide-line">
              {recommendations.map((rec) => {
                const cat = categoryMeta(rec.category)
                const prio = PRIORITY_META[rec.priority] ?? PRIORITY_META.medium
                const href = recommendationHref(rec)
                return (
                  <li key={rec.id} className="flex items-start gap-3 py-3 first:pt-0 last:pb-0">
                    <span className="mt-0.5 grid size-8 shrink-0 place-items-center rounded-lg bg-canvas text-navy-700">
                      <Icon name={cat.icon} className="size-4" />
                    </span>
                    <div className="min-w-0 flex-1">
                      <p className="flex flex-wrap items-center gap-2 text-sm font-semibold text-ink">
                        {rec.title} <Badge tone={prio.tone}>{prio.label}</Badge>
                      </p>
                      <p className="truncate text-xs text-muted">{rec.event_title}</p>
                    </div>
                    {href && (
                      <button type="button" onClick={() => navigate(href)} className="shrink-0 text-navy-600 hover:text-navy-800" title={rec.action_label}>
                        <Icon name="ArrowRight" className="size-4" />
                      </button>
                    )}
                  </li>
                )
              })}
            </ul>
          )}
        </Card>

        {/* Health scores */}
        <Card className="p-5">
          <p className="mb-3 flex items-center gap-2 font-bold text-ink"><Icon name="Activity" className="size-4 text-emerald-500" /> Event health</p>
          {health.length === 0 ? (
            <p className="py-6 text-center text-sm text-muted">No active events.</p>
          ) : (
            <div className="space-y-3">
              {health.map((h) => (
                <Link key={h.event_id} to={`${BASE}/insights`} className="block">
                  <div className="flex items-center justify-between text-sm">
                    <span className="truncate pr-2 font-medium text-ink">{h.event_title}</span>
                    <span className={cn('font-bold tabular-nums', healthTextClass(h.score))}>{h.score}</span>
                  </div>
                  <div className="mt-1.5 h-2 overflow-hidden rounded-full bg-canvas">
                    <div className={cn('h-full rounded-full', healthBarClass(h.score))} style={{ width: `${h.score}%` }} />
                  </div>
                </Link>
              ))}
            </div>
          )}
        </Card>
      </div>

      <div className="grid gap-5 lg:grid-cols-3">
        {/* Forecast panel */}
        <Card className="p-5 lg:col-span-2">
          <p className="mb-3 flex items-center gap-2 font-bold text-ink">
            <Icon name="TrendingUp" className="size-4 text-purple-500" /> Forecast
            {forecast.event && <span className="text-sm font-normal text-muted">· {forecast.event.title}</span>}
          </p>
          {forecast.items.length === 0 ? (
            <p className="py-6 text-center text-sm text-muted">Add event data to unlock predictive forecasts.</p>
          ) : (
            <div className="grid gap-3 sm:grid-cols-3">
              {forecast.items.map((f) => (
                <div key={f.key} className="rounded-xl border border-line bg-canvas p-4">
                  <p className="text-xs font-medium text-muted">{f.label}</p>
                  <p className="mt-1 text-xl font-extrabold tracking-tight text-ink">{f.value}</p>
                  <Badge tone="purple" className="mt-2">{f.confidence}% conf.</Badge>
                </div>
              ))}
            </div>
          )}
        </Card>

        {/* Conversation shortcuts — open the floating copilot */}
        <Card className="p-5">
          <div className="mb-3 flex items-center justify-between">
            <p className="flex items-center gap-2 font-bold text-ink"><Icon name="MessagesSquare" className="size-4 text-navy-600" /> Conversations</p>
            <button type="button" onClick={() => openChat()} className="text-sm font-semibold text-navy-700 hover:underline">Open</button>
          </div>
          {conversations.length === 0 ? (
            <button type="button" onClick={() => openChat()} className="w-full py-4 text-center text-sm text-muted hover:text-ink">
              Start a conversation with your copilot.
            </button>
          ) : (
            <ul className="space-y-1">
              {conversations.map((c) => (
                <li key={c.id}>
                  <button type="button" onClick={() => openChat({ conversationId: c.id })} className="flex w-full items-center gap-2 rounded-lg px-2 py-2 text-left hover:bg-canvas">
                    <Icon name={c.event_id ? 'CalendarClock' : 'MessagesSquare'} className="size-4 shrink-0 text-muted" />
                    <span className="min-w-0 flex-1">
                      <span className="block truncate text-sm font-medium text-ink">{c.title}</span>
                      {c.last_message_at && <span className="block text-[11px] text-muted">{formatRelative(c.last_message_at)}</span>}
                    </span>
                  </button>
                </li>
              ))}
            </ul>
          )}
        </Card>
      </div>

      {/* Quick prompts — open the copilot pre-filled */}
      <Card className="p-5">
        <p className="mb-3 flex items-center gap-2 font-bold text-ink"><Icon name="Wand2" className="size-4 text-purple-500" /> Quick prompts</p>
        <div className="flex flex-wrap gap-2">
          {QUICK_PROMPTS.map((p) => (
            <button
              key={p}
              type="button"
              onClick={() => openChat({ prompt: p })}
              className="rounded-full border border-line bg-canvas px-3.5 py-2 text-sm font-medium text-ink transition-colors hover:border-navy-200 hover:bg-navy-50"
            >
              {p}
            </button>
          ))}
        </div>
      </Card>
    </div>
  )
}

/**
 * The setup checklist a new planner sees on the AI Dashboard: overall progress,
 * the next best step called out with a CTA, and every step as a deep link. It
 * disappears once setup is complete.
 */
function OnboardingCard({ data, navigate, openChat }) {
  const go = (step) => {
    if (step.done) return
    if (step.action === 'chat') openChat()
    else if (step.href) navigate(step.href)
  }

  return (
    <Card className="overflow-hidden p-0">
      <div className="flex flex-col gap-4 p-5 sm:flex-row sm:items-center sm:justify-between">
        <div className="flex items-center gap-3">
          <span className="grid size-11 place-items-center rounded-2xl bg-purple-50 text-purple-600">
            <Icon name="Sparkles" className="size-6" />
          </span>
          <div>
            <p className="font-bold text-ink">Let’s get your copilot set up</p>
            <p className="text-sm text-muted">{data.done_count} of {data.total} done — each step unlocks grounded AI help.</p>
          </div>
        </div>
        <div className="flex items-center gap-3 sm:w-56">
          <div className="h-2 flex-1 overflow-hidden rounded-full bg-canvas">
            <div className="h-full rounded-full bg-purple-500 transition-all" style={{ width: `${data.progress}%` }} />
          </div>
          <span className="text-sm font-bold tabular-nums text-ink">{data.progress}%</span>
        </div>
      </div>

      {data.next && (
        <div className="flex flex-col gap-3 border-t border-line bg-canvas/60 p-5 sm:flex-row sm:items-center sm:justify-between">
          <div className="flex items-start gap-3">
            <Icon name="ArrowRight" className="mt-0.5 size-4 shrink-0 text-purple-500" />
            <div>
              <p className="text-sm font-semibold text-ink">Next: {data.next.title}</p>
              <p className="text-xs text-muted">{data.next.description}</p>
            </div>
          </div>
          <Button onClick={() => go(data.next)} className="shrink-0">{data.next.cta}</Button>
        </div>
      )}

      <ul className="divide-y divide-line border-t border-line">
        {data.steps.map((step) => (
          <li key={step.key}>
            <button
              type="button"
              onClick={() => go(step)}
              disabled={step.done}
              className={cn(
                'flex w-full items-center gap-3 px-5 py-3 text-left transition-colors',
                step.done ? 'cursor-default' : 'hover:bg-canvas',
              )}
            >
              <span className={cn(
                'grid size-7 shrink-0 place-items-center rounded-full',
                step.done ? 'bg-emerald-50 text-emerald-600' : 'bg-canvas text-muted',
              )}>
                <Icon name={step.done ? 'Check' : step.icon} className="size-4" />
              </span>
              <span className={cn('flex-1 text-sm font-medium', step.done ? 'text-muted line-through' : 'text-ink')}>
                {step.title}
              </span>
              {!step.done && <Icon name="ArrowRight" className="size-4 shrink-0 text-muted" />}
            </button>
          </li>
        ))}
      </ul>
    </Card>
  )
}

function Tile({ icon, label, value, accent = 'navy', tone }) {
  const accents = { navy: 'bg-navy-50 text-navy-700', emerald: 'bg-emerald-50 text-emerald-600', purple: 'bg-purple-50 text-purple-600' }
  return (
    <Card className="p-5">
      <div className="flex items-start justify-between gap-3">
        <div>
          <p className="text-sm font-medium text-muted">{label}</p>
          <p className={cn('mt-1.5 text-2xl font-extrabold tracking-tight tabular-nums', tone != null ? healthTextClass(tone) : 'text-ink')}>{value}</p>
        </div>
        <span className={cn('grid size-10 shrink-0 place-items-center rounded-xl', accents[accent])}>
          <Icon name={icon} className="size-5" />
        </span>
      </div>
    </Card>
  )
}
