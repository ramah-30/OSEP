import { useCallback, useEffect, useState } from 'react'
import { useNavigate } from 'react-router-dom'
import Card from '../../../../components/ui/Card'
import Icon from '../../../../components/ui/Icon'
import Badge from '../../../../components/ui/Badge'
import Button from '../../../../components/ui/Button'
import EmptyState from '../../../../components/ui/EmptyState'
import LoadState from '../../../../components/dashboard/LoadState'
import { api, parseApiError } from '../../../../lib/api'
import { cn } from '../../../../lib/cn'
import { PRIORITY_META, categoryMeta, recommendationHref } from '../../../../lib/ai'

const ACCENTS = {
  navy: 'bg-navy-50 text-navy-700',
  emerald: 'bg-emerald-50 text-emerald-600',
  purple: 'bg-purple-50 text-purple-600',
}

export default function AiRecommendations() {
  const navigate = useNavigate()
  const [data, setData] = useState(null)
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState(null)
  const [busy, setBusy] = useState(null)

  const load = useCallback(async () => {
    setLoading(true)
    setError(null)
    try {
      const r = await api.get('/ai/recommendations')
      setData(r.data.data)
    } catch (err) {
      setError(parseApiError(err).message)
    } finally {
      setLoading(false)
    }
  }, [])

  useEffect(() => { load() }, [load])

  const act = async (rec, kind) => {
    setBusy(rec.id)
    try {
      if (kind === 'dismiss') {
        await api.put(`/ai/recommendations/${rec.id}/dismiss`)
        setData((d) => ({ ...d, recommendations: d.recommendations.filter((r) => r.id !== rec.id) }))
      } else {
        const r = await api.post(`/ai/recommendations/${rec.id}/apply`)
        setData((d) => ({ ...d, recommendations: d.recommendations.filter((x) => x.id !== rec.id) }))
        const href = recommendationHref({ ...rec, ...r.data.data })
        if (href) navigate(href)
      }
    } catch (err) {
      setError(parseApiError(err).message)
    } finally {
      setBusy(null)
    }
  }

  const recommendations = data?.recommendations ?? []

  return (
    <div className="space-y-5">
      <div className="flex flex-wrap items-center justify-between gap-3">
        <div>
          <h2 className="text-lg font-bold text-ink">Reminders</h2>
          <p className="text-sm text-muted">
            Continuously generated from your active events. {recommendations.length} open.
          </p>
        </div>
        <Button size="sm" variant="secondary" onClick={load}>
          <Icon name="Loader2" className="size-4" /> Refresh
        </Button>
      </div>

      <LoadState loading={loading} error={error} onRetry={load}>
        {recommendations.length === 0 ? (
          <EmptyState
            icon="CheckCircle2"
            title="All clear"
            description="No open reminders — your active events look healthy. New reminders appear here as your data changes."
          />
        ) : (
          <div className="space-y-3">
            {recommendations.map((rec) => {
              const cat = categoryMeta(rec.category)
              const prio = PRIORITY_META[rec.priority] ?? PRIORITY_META.medium
              return (
                <Card key={rec.id} className="p-4 sm:p-5">
                  <div className="flex gap-4">
                    <span className={cn('grid size-11 shrink-0 place-items-center rounded-xl', ACCENTS[cat.accent])}>
                      <Icon name={cat.icon} className="size-5" />
                    </span>
                    <div className="min-w-0 flex-1">
                      <div className="flex flex-wrap items-center gap-2">
                        <h3 className="font-bold text-ink">{rec.title}</h3>
                        <Badge tone={prio.tone}>{prio.label}</Badge>
                        {rec.event_title && <span className="text-xs text-muted">· {rec.event_title}</span>}
                      </div>
                      <p className="mt-1 text-sm text-muted">{rec.description}</p>
                      <div className="mt-3 flex flex-wrap items-center gap-2">
                        {rec.action_label && recommendationHref(rec) && (
                          <Button size="sm" onClick={() => act(rec, 'apply')} loading={busy === rec.id}>
                            {rec.action_label}
                            <Icon name="ArrowRight" className="size-4" />
                          </Button>
                        )}
                        {(!rec.action_label || !recommendationHref(rec)) && (
                          <Button size="sm" onClick={() => act(rec, 'apply')} loading={busy === rec.id}>
                            <Icon name="Check" className="size-4" /> Accept
                          </Button>
                        )}
                        <Button size="sm" variant="ghost" onClick={() => act(rec, 'dismiss')} disabled={busy === rec.id}>
                          Dismiss
                        </Button>
                        <span className="ml-auto flex items-center gap-1 text-xs text-muted">
                          <Icon name="Sparkles" className="size-3" /> {rec.confidence}% confidence
                        </span>
                      </div>
                    </div>
                  </div>
                </Card>
              )
            })}
          </div>
        )}
      </LoadState>
    </div>
  )
}
