import { useCallback, useEffect, useState } from 'react'
import { Link, useParams } from 'react-router-dom'
import Card from '../../../../components/ui/Card'
import Icon from '../../../../components/ui/Icon'
import Badge from '../../../../components/ui/Badge'
import Button from '../../../../components/ui/Button'
import Spinner from '../../../../components/ui/Spinner'
import EmptyState from '../../../../components/ui/EmptyState'
import PromptFormModal from '../../../../components/ai/PromptFormModal'
import RunPromptModal from '../../../../components/ai/RunPromptModal'
import { api, parseApiError } from '../../../../lib/api'
import { cn } from '../../../../lib/cn'
import { formatRelative } from '../../../../lib/format'
import { humanizeVar } from '../../../../lib/ai'

const BASE = '/dashboard/planner/ai-assistant'

export default function AiPrompt() {
  const { id } = useParams()
  const [prompt, setPrompt] = useState(null)
  const [events, setEvents] = useState([])
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState(null)

  const [editOpen, setEditOpen] = useState(false)
  const [runOpen, setRunOpen] = useState(false)
  const [expanded, setExpanded] = useState({})   // version.id → open
  const [rollingBack, setRollingBack] = useState(null)  // version number in flight

  const load = useCallback(async () => {
    setLoading(true); setError(null)
    try {
      const [p, meta] = await Promise.all([api.get(`/ai/prompts/${id}`), api.get('/ai/meta')])
      setPrompt(p.data.data.prompt)
      setEvents(meta.data.data.events ?? [])
    } catch (err) {
      setError(parseApiError(err).message)
    } finally {
      setLoading(false)
    }
  }, [id])

  useEffect(() => { load() }, [load])

  const rollback = async (version) => {
    setRollingBack(version)
    try {
      const r = await api.post(`/ai/prompts/${id}/rollback`, { version })
      setPrompt(r.data.data.prompt)
    } catch (err) {
      setError(parseApiError(err).message)
    } finally {
      setRollingBack(null)
    }
  }

  if (loading) return <div className="grid place-items-center py-20"><Spinner /></div>
  if (error || !prompt) {
    return (
      <EmptyState
        icon="Terminal"
        title="Prompt unavailable"
        description={error ?? 'This prompt could not be found.'}
        action={<Button size="sm" to={`${BASE}/prompts`}>Back to library</Button>}
      />
    )
  }

  const versions = prompt.versions ?? []

  return (
    <div className="space-y-5">
      <div>
        <Link to={`${BASE}/prompts`} className="inline-flex items-center gap-1 text-sm text-muted hover:text-ink">
          <Icon name="ArrowLeft" className="size-4" /> Prompt library
        </Link>
      </div>

      <div className="flex flex-wrap items-start justify-between gap-3">
        <div className="min-w-0">
          <h2 className="text-xl font-bold text-ink">{prompt.name}</h2>
          <div className="mt-1.5 flex flex-wrap items-center gap-1.5">
            {prompt.category && <Badge tone="purple">{prompt.category}</Badge>}
            <Badge tone="navy">{prompt.scope === 'event' ? (prompt.event_title ?? 'Event') : 'General'}</Badge>
            <span className="text-xs text-muted">· v{prompt.current_version}</span>
            {prompt.usage_count > 0 && <span className="text-xs text-muted">· run {prompt.usage_count}×</span>}
          </div>
          {prompt.description && <p className="mt-2 max-w-2xl text-sm text-muted">{prompt.description}</p>}
        </div>
        <div className="flex items-center gap-2">
          <Button size="sm" variant="ghost" onClick={() => setEditOpen(true)}><Icon name="Pencil" className="size-4" /> Edit</Button>
          <Button size="sm" onClick={() => setRunOpen(true)}><Icon name="Play" className="size-4" /> Run</Button>
        </div>
      </div>

      {/* Current body */}
      <Card className="p-4">
        <p className="mb-2 flex items-center gap-2 text-sm font-bold text-ink"><Icon name="Terminal" className="size-4 text-navy-600" /> Current prompt (v{prompt.current_version})</p>
        <pre className="whitespace-pre-wrap rounded-xl bg-canvas p-3.5 text-sm leading-relaxed text-ink">{prompt.body}</pre>
        {prompt.variables?.length > 0 && (
          <div className="mt-2.5 flex flex-wrap items-center gap-1.5">
            <span className="text-xs text-muted">Variables:</span>
            {prompt.variables.map((v) => (
              <span key={v} className="rounded-md bg-navy-50 px-1.5 py-0.5 text-xs font-medium text-navy-700">{humanizeVar(v)}</span>
            ))}
          </div>
        )}
      </Card>

      {/* Version history */}
      <div>
        <p className="mb-2 flex items-center gap-2 text-sm font-bold text-ink"><Icon name="History" className="size-4 text-navy-600" /> Version history</p>
        <Card className="divide-y divide-line p-0">
          {versions.map((v) => {
            const isCurrent = v.version === prompt.current_version
            const isOpen = expanded[v.id]
            return (
              <div key={v.id} className="p-3.5">
                <div className="flex items-center gap-3">
                  <span className={cn('grid size-8 shrink-0 place-items-center rounded-lg text-xs font-bold',
                    isCurrent ? 'bg-navy-600 text-white' : 'bg-canvas text-muted')}>v{v.version}</span>
                  <div className="min-w-0 flex-1">
                    <p className="flex items-center gap-2 text-sm text-ink">
                      <span className="font-semibold">{v.note ?? 'Updated'}</span>
                      {isCurrent && <Badge tone="emerald">Current</Badge>}
                    </p>
                    <p className="text-xs text-muted">
                      {v.author ? `${v.author} · ` : ''}{formatRelative(v.created_at)}
                    </p>
                  </div>
                  <div className="flex shrink-0 items-center gap-0.5">
                    <button
                      type="button"
                      title={isOpen ? 'Hide' : 'View'}
                      onClick={() => setExpanded((s) => ({ ...s, [v.id]: !s[v.id] }))}
                      className="grid size-7 place-items-center rounded-lg text-muted hover:bg-canvas hover:text-navy-700"
                    >
                      <Icon name={isOpen ? 'EyeOff' : 'Eye'} className="size-4" />
                    </button>
                    {!isCurrent && (
                      <button
                        type="button"
                        title={`Roll back to v${v.version}`}
                        disabled={rollingBack !== null}
                        onClick={() => rollback(v.version)}
                        className="grid size-7 place-items-center rounded-lg text-muted hover:bg-canvas hover:text-navy-700 disabled:opacity-40"
                      >
                        <Icon name={rollingBack === v.version ? 'Loader2' : 'RotateCcw'} className={cn('size-4', rollingBack === v.version && 'animate-spin')} />
                      </button>
                    )}
                  </div>
                </div>
                {isOpen && (
                  <pre className="mt-2.5 whitespace-pre-wrap rounded-xl bg-canvas p-3.5 text-sm leading-relaxed text-ink">{v.body}</pre>
                )}
              </div>
            )
          })}
        </Card>
        <p className="mt-2 text-xs text-muted">Rolling back keeps the full history — it adds a new version that restores the older wording.</p>
      </div>

      {editOpen && (
        <PromptFormModal
          prompt={prompt}
          events={events}
          onClose={() => setEditOpen(false)}
          onSaved={() => { setEditOpen(false); load() }}
        />
      )}
      {runOpen && (
        <RunPromptModal prompt={prompt} events={events} onClose={() => setRunOpen(false)} onRan={() => load()} />
      )}
    </div>
  )
}
