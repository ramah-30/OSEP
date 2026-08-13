import { useCallback, useEffect, useMemo, useState } from 'react'
import { Link } from 'react-router-dom'
import Card from '../../../../components/ui/Card'
import Icon from '../../../../components/ui/Icon'
import Badge from '../../../../components/ui/Badge'
import Button from '../../../../components/ui/Button'
import EmptyState from '../../../../components/ui/EmptyState'
import LoadState from '../../../../components/dashboard/LoadState'
import PromptFormModal from '../../../../components/ai/PromptFormModal'
import RunPromptModal from '../../../../components/ai/RunPromptModal'
import { api, parseApiError } from '../../../../lib/api'
import { cn } from '../../../../lib/cn'
import { humanizeVar } from '../../../../lib/ai'

const BASE = '/dashboard/planner/ai-assistant'

export default function AiPrompts() {
  const [prompts, setPrompts] = useState([])
  const [starters, setStarters] = useState([])
  const [events, setEvents] = useState([])
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState(null)
  const [query, setQuery] = useState('')

  const [formOpen, setFormOpen] = useState(false)
  const [editing, setEditing] = useState(null)   // prompt being edited, or a starter seed, or null
  const [running, setRunning] = useState(null)   // prompt being run

  const load = useCallback(async () => {
    setLoading(true); setError(null)
    try {
      const [p, meta] = await Promise.all([api.get('/ai/prompts'), api.get('/ai/meta')])
      setPrompts(p.data.data.prompts)
      setStarters(p.data.data.starters ?? [])
      setEvents(meta.data.data.events ?? [])
    } catch (err) {
      setError(parseApiError(err).message)
    } finally {
      setLoading(false)
    }
  }, [])

  useEffect(() => { load() }, [load])

  const openNew = () => { setEditing(null); setFormOpen(true) }
  const openEdit = (p) => { setEditing(p); setFormOpen(true) }
  const openStarter = (s) => {
    // Seed the create form with a starter (no id → creates a new owned prompt).
    setEditing({ name: s.name, category: s.category, description: s.description, body: s.body, event_id: null })
    setFormOpen(true)
  }

  const onSaved = () => { setFormOpen(false); load() }

  const remove = async (p) => {
    try {
      await api.delete(`/ai/prompts/${p.id}`)
      setPrompts((list) => list.filter((x) => x.id !== p.id))
    } catch { /* ignore */ }
  }

  const togglePin = async (p) => {
    try {
      await api.put(`/ai/prompts/${p.id}`, { pinned: !p.pinned })
      load()
    } catch { /* ignore */ }
  }

  const filtered = useMemo(() => {
    const q = query.trim().toLowerCase()
    if (!q) return prompts
    return prompts.filter((p) =>
      p.name.toLowerCase().includes(q) || (p.description ?? '').toLowerCase().includes(q) || (p.category ?? '').toLowerCase().includes(q))
  }, [prompts, query])

  // A starter is "added" if a prompt with the same name already exists.
  const takenNames = useMemo(() => new Set(prompts.map((p) => p.name.toLowerCase())), [prompts])
  const availableStarters = starters.filter((s) => !takenNames.has(s.name.toLowerCase()))

  return (
    <div className="space-y-5">
      <div className="flex flex-wrap items-center justify-between gap-3">
        <div>
          <h2 className="text-lg font-bold text-ink">Prompt library</h2>
          <p className="text-sm text-muted">Reusable, versioned prompts you can run against any event's live data.</p>
        </div>
        <Button size="sm" onClick={openNew}><Icon name="Plus" className="size-4" /> New prompt</Button>
      </div>

      <LoadState loading={loading} error={error} onRetry={load}>
        {/* Starters */}
        {availableStarters.length > 0 && (
          <div>
            <p className="mb-2 flex items-center gap-2 text-sm font-bold text-ink">
              <Icon name="Sparkles" className="size-4 text-navy-600" /> Ready-made prompts
            </p>
            <div className="grid gap-2.5 sm:grid-cols-2 lg:grid-cols-3">
              {availableStarters.map((s) => (
                <button
                  key={s.key}
                  type="button"
                  onClick={() => openStarter(s)}
                  className="group flex flex-col rounded-xl border border-dashed border-line bg-canvas/40 p-3.5 text-left transition-colors hover:border-navy-300 hover:bg-surface"
                >
                  <span className="flex items-center gap-1.5 font-semibold text-ink">{s.name}</span>
                  <span className="mt-0.5 line-clamp-2 text-xs text-muted">{s.description}</span>
                  <span className="mt-2 flex items-center gap-1 text-xs font-semibold text-navy-700 opacity-0 transition-opacity group-hover:opacity-100">
                    <Icon name="Plus" className="size-3.5" /> Add to library
                  </span>
                </button>
              ))}
            </div>
          </div>
        )}

        {prompts.length === 0 ? (
          <EmptyState
            icon="Terminal"
            title="No saved prompts yet"
            description="Save the prompts you reach for often. Add {{variables}} for the bits that change, then run them against any event — grounded in its real data."
            action={<Button size="sm" onClick={openNew}><Icon name="Plus" className="size-4" /> Create your first prompt</Button>}
          />
        ) : (
          <>
            <div className="relative max-w-md">
              <Icon name="Search" className="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted" />
              <input
                value={query}
                onChange={(e) => setQuery(e.target.value)}
                placeholder="Search your prompts…"
                className="h-10 w-full rounded-xl border border-line bg-surface pl-9 pr-3 text-sm text-ink outline-none focus:border-navy-300"
              />
            </div>

            {filtered.length === 0 ? (
              <p className="py-8 text-center text-sm text-muted">No prompts match “{query}”.</p>
            ) : (
              <div className="grid gap-3 lg:grid-cols-2">
                {filtered.map((p) => (
                  <Card key={p.id} className="group flex flex-col p-4">
                    <div className="flex items-start justify-between gap-2">
                      <div className="min-w-0">
                        <p className="flex items-center gap-1.5 font-bold text-ink">
                          {p.pinned && <Icon name="Bookmark" className="size-3.5 text-navy-600" />}
                          {p.name}
                        </p>
                        <div className="mt-1 flex flex-wrap items-center gap-1.5">
                          {p.category && <Badge tone="purple">{p.category}</Badge>}
                          <Badge tone="navy">{p.scope === 'event' ? (p.event_title ?? 'Event') : 'General'}</Badge>
                          <span className="text-xs text-muted">· v{p.current_version}</span>
                          {p.usage_count > 0 && <span className="text-xs text-muted">· run {p.usage_count}×</span>}
                        </div>
                      </div>
                      <div className="flex shrink-0 items-center gap-0.5 opacity-0 transition-opacity group-hover:opacity-100">
                        <IconBtn icon="Bookmark" title={p.pinned ? 'Unpin' : 'Pin'} onClick={() => togglePin(p)} />
                        <IconBtn icon="Pencil" title="Edit" onClick={() => openEdit(p)} />
                        <IconBtn icon="Trash2" title="Delete" danger onClick={() => remove(p)} />
                      </div>
                    </div>

                    {p.description && <p className="mt-2 line-clamp-2 text-sm text-muted">{p.description}</p>}

                    {p.variables?.length > 0 && (
                      <div className="mt-2 flex flex-wrap items-center gap-1.5">
                        {p.variables.map((v) => (
                          <span key={v} className="rounded-md bg-navy-50 px-1.5 py-0.5 text-xs font-medium text-navy-700">{humanizeVar(v)}</span>
                        ))}
                      </div>
                    )}

                    <div className="mt-3 flex items-center gap-2 pt-1">
                      <Button size="sm" onClick={() => setRunning(p)}><Icon name="Play" className="size-4" /> Run</Button>
                      <Button size="sm" variant="ghost" to={`${BASE}/prompts/${p.id}`}>
                        <Icon name="History" className="size-4" /> {p.versions_count > 1 ? `${p.versions_count} versions` : 'History'}
                      </Button>
                    </div>
                  </Card>
                ))}
              </div>
            )}
          </>
        )}
      </LoadState>

      {formOpen && (
        <PromptFormModal
          prompt={editing}
          events={events}
          onClose={() => setFormOpen(false)}
          onSaved={onSaved}
        />
      )}
      {running && (
        <RunPromptModal
          prompt={running}
          events={events}
          onClose={() => setRunning(null)}
          onRan={() => load()}
        />
      )}
    </div>
  )
}

function IconBtn({ icon, title, onClick, danger }) {
  return (
    <button
      type="button"
      title={title}
      onClick={onClick}
      className={cn('grid size-7 place-items-center rounded-lg text-muted hover:bg-canvas', danger ? 'hover:text-danger' : 'hover:text-navy-700')}
    >
      <Icon name={icon} className="size-4" />
    </button>
  )
}
