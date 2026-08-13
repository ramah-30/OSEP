import { useCallback, useEffect, useMemo, useState } from 'react'
import Card from '../../../../components/ui/Card'
import Icon from '../../../../components/ui/Icon'
import Badge from '../../../../components/ui/Badge'
import Button from '../../../../components/ui/Button'
import LoadState from '../../../../components/dashboard/LoadState'
import GenerateDocumentModal from '../../../../components/ai/GenerateDocumentModal'
import { api, parseApiError } from '../../../../lib/api'
import { cn } from '../../../../lib/cn'
import { docCategoryMeta } from '../../../../lib/ai'

const ACCENTS = {
  navy: 'bg-navy-50 text-navy-700',
  emerald: 'bg-emerald-50 text-emerald-600',
  purple: 'bg-purple-50 text-purple-600',
}

export default function AiTemplates() {
  const [templates, setTemplates] = useState([])
  const [events, setEvents] = useState([])
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState(null)
  const [active, setActive] = useState(null) // template being generated

  const load = useCallback(async () => {
    setLoading(true)
    setError(null)
    try {
      const [t, m] = await Promise.all([api.get('/ai/templates'), api.get('/ai/meta')])
      setTemplates(t.data.data.templates)
      setEvents(m.data.data.events ?? [])
    } catch (err) {
      setError(parseApiError(err).message)
    } finally {
      setLoading(false)
    }
  }, [])

  useEffect(() => { load() }, [load])

  const grouped = useMemo(() => {
    const map = new Map()
    for (const t of templates) {
      if (!map.has(t.category)) map.set(t.category, [])
      map.get(t.category).push(t)
    }
    return [...map.entries()]
  }, [templates])

  return (
    <div className="space-y-6">
      <div>
        <h2 className="text-lg font-bold text-ink">Templates</h2>
        <p className="text-sm text-muted">
          Generate polished deliverables — proposals, timelines, emails and more — grounded in your real event data.
        </p>
      </div>

      <LoadState loading={loading} error={error} onRetry={load}>
        {grouped.map(([category, items]) => {
          const meta = docCategoryMeta(category)
          return (
            <section key={category} className="space-y-3">
              <p className="flex items-center gap-2 text-xs font-bold uppercase tracking-wide text-muted">
                <Icon name={meta.icon} className="size-3.5" /> {meta.label}
              </p>
              <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                {items.map((t) => {
                  const m = docCategoryMeta(t.category)
                  return (
                    <Card key={t.id} className="flex flex-col p-4">
                      <div className="flex items-start gap-3">
                        <span className={cn('grid size-10 shrink-0 place-items-center rounded-xl', ACCENTS[m.accent])}>
                          <Icon name={t.icon} className="size-5" />
                        </span>
                        <div className="min-w-0">
                          <p className="font-bold text-ink">{t.name}</p>
                          {t.requires_event && <Badge tone="navy" className="mt-1">Needs an event</Badge>}
                        </div>
                      </div>
                      <p className="mt-2.5 flex-1 text-sm text-muted">{t.description}</p>
                      <Button size="sm" className="mt-3 self-start" onClick={() => setActive(t)}>
                        <Icon name="Wand2" className="size-4" /> Generate
                      </Button>
                    </Card>
                  )
                })}
              </div>
            </section>
          )
        })}
      </LoadState>

      {active && (
        <GenerateDocumentModal
          template={active}
          events={events}
          onClose={() => setActive(null)}
        />
      )}
    </div>
  )
}
