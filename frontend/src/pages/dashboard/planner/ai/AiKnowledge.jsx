import { useCallback, useEffect, useMemo, useState } from 'react'
import Card from '../../../../components/ui/Card'
import Icon from '../../../../components/ui/Icon'
import Badge from '../../../../components/ui/Badge'
import Button from '../../../../components/ui/Button'
import Modal from '../../../../components/ui/Modal'
import EmptyState from '../../../../components/ui/EmptyState'
import LoadState from '../../../../components/dashboard/LoadState'
import ListboxSelect from '../../../../components/ui/ListboxSelect'
import { api, parseApiError } from '../../../../lib/api'
import { cn } from '../../../../lib/cn'

const BLANK = { title: '', content: '', category: '', event_id: '' }

export default function AiKnowledge() {
  const [documents, setDocuments] = useState([])
  const [events, setEvents] = useState([])
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState(null)
  const [query, setQuery] = useState('')

  const [open, setOpen] = useState(false)
  const [editing, setEditing] = useState(null)
  const [form, setForm] = useState(BLANK)
  const [saving, setSaving] = useState(false)
  const [formError, setFormError] = useState(null)

  const load = useCallback(async () => {
    setLoading(true); setError(null)
    try {
      const [k, meta] = await Promise.all([api.get('/ai/knowledge'), api.get('/ai/meta')])
      setDocuments(k.data.data.documents)
      setEvents(meta.data.data.events ?? [])
    } catch (err) {
      setError(parseApiError(err).message)
    } finally {
      setLoading(false)
    }
  }, [])

  useEffect(() => { load() }, [load])

  const openNew = () => { setEditing(null); setForm(BLANK); setFormError(null); setOpen(true) }
  const openEdit = (d) => {
    setEditing(d)
    setForm({ title: d.title, content: d.content, category: d.category ?? '', event_id: d.event_id ? String(d.event_id) : '' })
    setFormError(null); setOpen(true)
  }

  const save = async () => {
    setSaving(true); setFormError(null)
    const payload = {
      title: form.title.trim(),
      content: form.content.trim(),
      category: form.category.trim() || null,
      event_id: form.event_id ? Number(form.event_id) : null,
    }
    try {
      if (editing) await api.put(`/ai/knowledge/${editing.id}`, payload)
      else await api.post('/ai/knowledge', payload)
      setOpen(false)
      load()
    } catch (err) {
      setFormError(parseApiError(err).message)
    } finally {
      setSaving(false)
    }
  }

  const remove = async (d) => {
    try {
      await api.delete(`/ai/knowledge/${d.id}`)
      setDocuments((list) => list.filter((x) => x.id !== d.id))
    } catch { /* ignore */ }
  }

  const togglePin = async (d) => {
    try {
      await api.put(`/ai/knowledge/${d.id}`, { pinned: !d.pinned })
      load()
    } catch { /* ignore */ }
  }

  const filtered = useMemo(() => {
    const q = query.trim().toLowerCase()
    if (!q) return documents
    return documents.filter((d) =>
      d.title.toLowerCase().includes(q) || d.content.toLowerCase().includes(q) || (d.category ?? '').toLowerCase().includes(q))
  }, [documents, query])

  const global = filtered.filter((d) => d.scope === 'global')
  const eventDocs = filtered.filter((d) => d.scope === 'event')
  const eventOptions = useMemo(() => events.map((e) => ({ value: String(e.id), label: e.title })), [events])

  return (
    <div className="space-y-5">
      <div className="flex flex-wrap items-center justify-between gap-3">
        <div>
          <h2 className="text-lg font-bold text-ink">Knowledge base</h2>
          <p className="text-sm text-muted">Notes, policies and playbooks the copilot retrieves and cites in its answers.</p>
        </div>
        <Button size="sm" onClick={openNew}><Icon name="Plus" className="size-4" /> Add note</Button>
      </div>

      <LoadState loading={loading} error={error} onRetry={load}>
        {documents.length === 0 ? (
          <EmptyState
            icon="BookOpen"
            title="No knowledge yet"
            description="Add your planning notes, house policies or preferred vendors. The copilot will retrieve the relevant ones and cite them when it answers."
            action={<Button size="sm" onClick={openNew}><Icon name="Plus" className="size-4" /> Add your first note</Button>}
          />
        ) : (
          <>
            <div className="relative max-w-md">
              <Icon name="Search" className="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted" />
              <input
                value={query}
                onChange={(e) => setQuery(e.target.value)}
                placeholder="Search your knowledge base…"
                className="h-10 w-full rounded-xl border border-line bg-surface pl-9 pr-3 text-sm text-ink outline-none focus:border-navy-300"
              />
            </div>

            {filtered.length === 0 ? (
              <p className="py-8 text-center text-sm text-muted">No notes match “{query}”.</p>
            ) : (
              <div className="space-y-6">
                <KnowledgeGroup title="Global notes" hint="Applied across every event" icon="Globe2" items={global} onEdit={openEdit} onRemove={remove} onPin={togglePin} />
                <KnowledgeGroup title="Event notes" hint="Scoped to a single event" icon="CalendarClock" items={eventDocs} onEdit={openEdit} onRemove={remove} onPin={togglePin} />
              </div>
            )}
          </>
        )}
      </LoadState>

      <Modal open={open} onClose={() => setOpen(false)} title={editing ? 'Edit note' : 'Add knowledge note'}>
        <div className="space-y-4">
          <div>
            <label className="mb-1.5 block text-sm font-medium text-ink">Title</label>
            <input
              value={form.title}
              onChange={(e) => setForm((f) => ({ ...f, title: e.target.value }))}
              placeholder="e.g. Preferred catering vendors"
              className="h-11 w-full rounded-xl border border-line bg-surface px-3.5 text-sm text-ink outline-none focus:border-navy-300"
            />
          </div>
          <div className="grid gap-4 sm:grid-cols-2">
            <div>
              <label className="mb-1.5 block text-sm font-medium text-ink">Category (optional)</label>
              <input
                value={form.category}
                onChange={(e) => setForm((f) => ({ ...f, category: e.target.value }))}
                placeholder="e.g. Vendor notes"
                className="h-11 w-full rounded-xl border border-line bg-surface px-3.5 text-sm text-ink outline-none focus:border-navy-300"
              />
            </div>
            <div>
              <label className="mb-1.5 block text-sm font-medium text-ink">Scope</label>
              <ListboxSelect
                placeholder="Global (all events)"
                options={[{ value: '', label: 'Global (all events)' }, ...eventOptions]}
                value={form.event_id}
                onChange={(e) => setForm((f) => ({ ...f, event_id: e.target.value }))}
              />
            </div>
          </div>
          <div>
            <label className="mb-1.5 block text-sm font-medium text-ink">Content</label>
            <textarea
              value={form.content}
              onChange={(e) => setForm((f) => ({ ...f, content: e.target.value }))}
              rows={7}
              placeholder="Write the note the copilot should draw on…"
              className="w-full resize-y rounded-xl border border-line bg-surface px-3.5 py-2.5 text-sm leading-relaxed text-ink outline-none focus:border-navy-300"
            />
          </div>
          {formError && <p className="text-sm text-danger">{formError}</p>}
          <div className="flex justify-end gap-2 pt-1">
            <Button size="sm" variant="ghost" onClick={() => setOpen(false)}>Cancel</Button>
            <Button size="sm" onClick={save} loading={saving} disabled={!form.title.trim() || !form.content.trim()}>
              {editing ? 'Save changes' : 'Save note'}
            </Button>
          </div>
        </div>
      </Modal>
    </div>
  )
}

function KnowledgeGroup({ title, hint, icon, items, onEdit, onRemove, onPin }) {
  if (items.length === 0) return null
  return (
    <div>
      <div className="mb-2 flex items-center gap-2">
        <Icon name={icon} className="size-4 text-navy-600" />
        <h3 className="text-sm font-bold text-ink">{title}</h3>
        <span className="text-xs text-muted">· {hint}</span>
      </div>
      <div className="grid gap-3 sm:grid-cols-2">
        {items.map((d) => (
          <Card key={d.id} className="group p-4">
            <div className="flex items-start justify-between gap-2">
              <div className="min-w-0">
                <p className="flex items-center gap-1.5 font-semibold text-ink">
                  {d.pinned && <Icon name="Bookmark" className="size-3.5 text-navy-600" />}
                  {d.title}
                </p>
                <div className="mt-1 flex flex-wrap items-center gap-1.5">
                  {d.category && <Badge tone="purple">{d.category}</Badge>}
                  {d.event_title && <Badge tone="navy">{d.event_title}</Badge>}
                </div>
              </div>
              <div className="flex shrink-0 items-center gap-0.5 opacity-0 transition-opacity group-hover:opacity-100">
                <IconBtn icon="Bookmark" title={d.pinned ? 'Unpin' : 'Pin'} onClick={() => onPin(d)} />
                <IconBtn icon="Pencil" title="Edit" onClick={() => onEdit(d)} />
                <IconBtn icon="Trash2" title="Delete" danger onClick={() => onRemove(d)} />
              </div>
            </div>
            <p className="mt-2 line-clamp-4 whitespace-pre-wrap text-sm text-muted">{d.content}</p>
          </Card>
        ))}
      </div>
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
