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

const BLANK = { scope: 'planner', label: '', value: '', event_id: '' }

export default function AiMemory() {
  const [memories, setMemories] = useState([])
  const [events, setEvents] = useState([])
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState(null)

  const [open, setOpen] = useState(false)
  const [editing, setEditing] = useState(null)
  const [form, setForm] = useState(BLANK)
  const [saving, setSaving] = useState(false)
  const [formError, setFormError] = useState(null)

  const load = useCallback(async () => {
    setLoading(true); setError(null)
    try {
      const [m, meta] = await Promise.all([api.get('/ai/memory'), api.get('/ai/meta')])
      setMemories(m.data.data.memories)
      setEvents(meta.data.data.events ?? [])
    } catch (err) {
      setError(parseApiError(err).message)
    } finally {
      setLoading(false)
    }
  }, [])

  useEffect(() => { load() }, [load])

  const openNew = () => { setEditing(null); setForm(BLANK); setFormError(null); setOpen(true) }
  const openEdit = (m) => {
    setEditing(m)
    setForm({ scope: m.scope, label: m.label, value: m.value, event_id: m.event_id ? String(m.event_id) : '' })
    setFormError(null); setOpen(true)
  }

  const save = async () => {
    setSaving(true); setFormError(null)
    const payload = {
      scope: form.scope,
      label: form.label.trim(),
      value: form.value.trim(),
      event_id: form.scope === 'event' && form.event_id ? Number(form.event_id) : null,
    }
    try {
      if (editing) await api.put(`/ai/memory/${editing.id}`, payload)
      else await api.post('/ai/memory', payload)
      setOpen(false)
      load()
    } catch (err) {
      setFormError(parseApiError(err).message)
    } finally {
      setSaving(false)
    }
  }

  const remove = async (m) => {
    try {
      await api.delete(`/ai/memory/${m.id}`)
      setMemories((list) => list.filter((x) => x.id !== m.id))
    } catch { /* ignore */ }
  }

  const togglePin = async (m) => {
    try {
      await api.put(`/ai/memory/${m.id}`, { scope: m.scope, label: m.label, value: m.value, event_id: m.event_id, pinned: !m.pinned })
      load()
    } catch { /* ignore */ }
  }

  const planner = useMemo(() => memories.filter((m) => m.scope === 'planner'), [memories])
  const eventMemories = useMemo(() => memories.filter((m) => m.scope === 'event'), [memories])
  const eventOptions = useMemo(() => events.map((e) => ({ value: String(e.id), label: e.title })), [events])

  return (
    <div className="space-y-5">
      <div className="flex flex-wrap items-center justify-between gap-3">
        <div>
          <h2 className="text-lg font-bold text-ink">AI memory</h2>
          <p className="text-sm text-muted">Preferences and event facts the copilot remembers and applies to its answers.</p>
        </div>
        <Button size="sm" onClick={openNew}><Icon name="Plus" className="size-4" /> Add memory</Button>
      </div>

      <LoadState loading={loading} error={error} onRetry={load}>
        {memories.length === 0 ? (
          <EmptyState
            icon="Bookmark"
            title="No memories yet"
            description="Save preferences (like your report format or preferred vendors) and per-event facts so the copilot personalises every response."
            action={<Button size="sm" onClick={openNew}><Icon name="Plus" className="size-4" /> Add your first memory</Button>}
          />
        ) : (
          <div className="space-y-6">
            <MemoryGroup title="Planner preferences" hint="Reusable across every event" icon="User" items={planner} onEdit={openEdit} onRemove={remove} onPin={togglePin} />
            <MemoryGroup title="Event memory" hint="Facts scoped to a single event" icon="CalendarClock" items={eventMemories} onEdit={openEdit} onRemove={remove} onPin={togglePin} />
          </div>
        )}
      </LoadState>

      <Modal open={open} onClose={() => setOpen(false)} title={editing ? 'Edit memory' : 'Add memory'}>
        <div className="space-y-4">
          <div>
            <label className="mb-1.5 block text-sm font-medium text-ink">Scope</label>
            <ListboxSelect
              options={[{ value: 'planner', label: 'Planner preference (all events)' }, { value: 'event', label: 'Event fact (one event)' }]}
              value={form.scope}
              onChange={(e) => setForm((f) => ({ ...f, scope: e.target.value }))}
            />
          </div>
          {form.scope === 'event' && (
            <div>
              <label className="mb-1.5 block text-sm font-medium text-ink">Event</label>
              <ListboxSelect placeholder="Select an event…" options={eventOptions} value={form.event_id} onChange={(e) => setForm((f) => ({ ...f, event_id: e.target.value }))} />
            </div>
          )}
          <div>
            <label className="mb-1.5 block text-sm font-medium text-ink">Label</label>
            <input
              value={form.label}
              onChange={(e) => setForm((f) => ({ ...f, label: e.target.value }))}
              placeholder="e.g. Preferred report format"
              className="h-11 w-full rounded-xl border border-line bg-surface px-3.5 text-sm text-ink outline-none focus:border-navy-300"
            />
          </div>
          <div>
            <label className="mb-1.5 block text-sm font-medium text-ink">Value</label>
            <textarea
              value={form.value}
              onChange={(e) => setForm((f) => ({ ...f, value: e.target.value }))}
              rows={3}
              placeholder="e.g. One-page executive summary, charts first"
              className="w-full resize-none rounded-xl border border-line bg-surface px-3.5 py-2.5 text-sm text-ink outline-none focus:border-navy-300"
            />
          </div>
          {formError && <p className="text-sm text-danger">{formError}</p>}
          <div className="flex justify-end gap-2 pt-1">
            <Button size="sm" variant="ghost" onClick={() => setOpen(false)}>Cancel</Button>
            <Button size="sm" onClick={save} loading={saving} disabled={!form.label.trim() || !form.value.trim() || (form.scope === 'event' && !form.event_id)}>
              {editing ? 'Save changes' : 'Save memory'}
            </Button>
          </div>
        </div>
      </Modal>
    </div>
  )
}

function MemoryGroup({ title, hint, icon, items, onEdit, onRemove, onPin }) {
  if (items.length === 0) return null
  return (
    <div>
      <div className="mb-2 flex items-center gap-2">
        <Icon name={icon} className="size-4 text-navy-600" />
        <h3 className="text-sm font-bold text-ink">{title}</h3>
        <span className="text-xs text-muted">· {hint}</span>
      </div>
      <div className="grid gap-3 sm:grid-cols-2">
        {items.map((m) => (
          <Card key={m.id} className="group p-4">
            <div className="flex items-start justify-between gap-2">
              <div className="min-w-0">
                <p className="flex items-center gap-1.5 font-semibold text-ink">
                  {m.pinned && <Icon name="Bookmark" className="size-3.5 text-navy-600" />}
                  {m.label}
                </p>
                {m.event_title && <Badge tone="navy" className="mt-1">{m.event_title}</Badge>}
              </div>
              <div className="flex shrink-0 items-center gap-0.5 opacity-0 transition-opacity group-hover:opacity-100">
                <IconBtn icon="Bookmark" title={m.pinned ? 'Unpin' : 'Pin'} onClick={() => onPin(m)} />
                <IconBtn icon="Pencil" title="Edit" onClick={() => onEdit(m)} />
                <IconBtn icon="Trash2" title="Delete" danger onClick={() => onRemove(m)} />
              </div>
            </div>
            <p className="mt-2 text-sm text-muted">{m.value}</p>
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
