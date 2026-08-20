import { useMemo, useState } from 'react'
import Button from '../ui/Button'
import Modal from '../ui/Modal'
import ListboxSelect from '../ui/ListboxSelect'
import { api, parseApiError } from '../../lib/api'
import { extractPromptVariables, humanizeVar } from '../../lib/ai'

/**
 * Create or edit a prompt-library template. Editing the body appends a new
 * version server-side; a short "note" captures the changelog for that version.
 */
export default function PromptFormModal({ prompt, events = [], onClose, onSaved }) {
  // A seed object without an id (e.g. a starter) still opens in "create" mode.
  const editing = Boolean(prompt?.id)
  const [form, setForm] = useState(() => ({
    name: prompt?.name ?? '',
    category: prompt?.category ?? '',
    description: prompt?.description ?? '',
    event_id: prompt?.event_id ? String(prompt.event_id) : '',
    body: prompt?.body ?? '',
    note: '',
  }))
  const [saving, setSaving] = useState(false)
  const [error, setError] = useState(null)

  const vars = useMemo(() => extractPromptVariables(form.body), [form.body])
  const bodyChanged = editing && form.body !== (prompt?.body ?? '')
  const eventOptions = useMemo(
    () => [{ value: '', label: 'General — usable on any event' }, ...events.map((e) => ({ value: String(e.id), label: e.title }))],
    [events],
  )

  const save = async () => {
    setSaving(true); setError(null)
    const payload = {
      name: form.name.trim(),
      body: form.body.trim(),
      category: form.category.trim() || null,
      description: form.description.trim() || null,
      event_id: form.event_id ? Number(form.event_id) : null,
    }
    if (editing && bodyChanged && form.note.trim()) payload.note = form.note.trim()
    try {
      const res = editing ? await api.put(`/ai/prompts/${prompt.id}`, payload) : await api.post('/ai/prompts', payload)
      onSaved?.(res.data.data.prompt)
    } catch (err) {
      setError(parseApiError(err).message)
    } finally {
      setSaving(false)
    }
  }

  return (
    <Modal open onClose={onClose} title={editing ? 'Edit prompt' : 'New prompt'}>
      <div className="space-y-4">
        <div className="grid gap-4 sm:grid-cols-2">
          <div>
            <label className="mb-1.5 block text-sm font-medium text-ink">Name</label>
            <input
              value={form.name}
              onChange={(e) => setForm((f) => ({ ...f, name: e.target.value }))}
              placeholder="e.g. Client check-in email"
              className="h-11 w-full rounded-xl border border-line bg-surface px-3.5 text-sm text-ink outline-none focus:border-navy-300"
            />
          </div>
          <div>
            <label className="mb-1.5 block text-sm font-medium text-ink">Category (optional)</label>
            <input
              value={form.category}
              onChange={(e) => setForm((f) => ({ ...f, category: e.target.value }))}
              placeholder="e.g. Client comms"
              className="h-11 w-full rounded-xl border border-line bg-surface px-3.5 text-sm text-ink outline-none focus:border-navy-300"
            />
          </div>
        </div>

        <div>
          <label className="mb-1.5 block text-sm font-medium text-ink">Description (optional)</label>
          <input
            value={form.description}
            onChange={(e) => setForm((f) => ({ ...f, description: e.target.value }))}
            placeholder="What this prompt is for"
            className="h-11 w-full rounded-xl border border-line bg-surface px-3.5 text-sm text-ink outline-none focus:border-navy-300"
          />
        </div>

        <div>
          <label className="mb-1.5 block text-sm font-medium text-ink">Default event</label>
          <ListboxSelect options={eventOptions} value={form.event_id} onChange={(e) => setForm((f) => ({ ...f, event_id: e.target.value }))} />
        </div>

        <div>
          <label className="mb-1.5 flex items-center justify-between text-sm font-medium text-ink">
            <span>Prompt</span>
            <span className="text-xs font-normal text-muted">Use <code className="rounded bg-canvas px-1">{'{{variable}}'}</code> for fill-in fields</span>
          </label>
          <textarea
            value={form.body}
            onChange={(e) => setForm((f) => ({ ...f, body: e.target.value }))}
            rows={7}
            placeholder="Draft a friendly check-in email to {{client_name}} about their event…"
            className="w-full resize-y rounded-xl border border-line bg-surface px-3.5 py-2.5 text-sm leading-relaxed text-ink outline-none focus:border-navy-300"
          />
          {vars.length > 0 && (
            <div className="mt-2 flex flex-wrap items-center gap-1.5">
              <span className="text-xs text-muted">Variables:</span>
              {vars.map((v) => (
                <span key={v} className="rounded-md bg-navy-50 px-1.5 py-0.5 text-xs font-medium text-navy-700">{humanizeVar(v)}</span>
              ))}
            </div>
          )}
        </div>

        {bodyChanged && (
          <div>
            <label className="mb-1.5 block text-sm font-medium text-ink">What changed? (optional)</label>
            <input
              value={form.note}
              onChange={(e) => setForm((f) => ({ ...f, note: e.target.value }))}
              placeholder="e.g. Warmer tone, added a call to action"
              className="h-11 w-full rounded-xl border border-line bg-surface px-3.5 text-sm text-ink outline-none focus:border-navy-300"
            />
            <p className="mt-1 text-xs text-muted">Saving the edited prompt creates a new version you can roll back to.</p>
          </div>
        )}

        {error && <p className="text-sm text-danger">{error}</p>}
        <div className="flex justify-end gap-2 pt-1">
          <Button size="sm" variant="ghost" onClick={onClose}>Cancel</Button>
          <Button size="sm" onClick={save} loading={saving} disabled={!form.name.trim() || !form.body.trim()}>
            {editing ? 'Save prompt' : 'Save to library'}
          </Button>
        </div>
      </div>
    </Modal>
  )
}
