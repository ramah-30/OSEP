import { useCallback, useEffect, useMemo, useState } from 'react'
import { useNavigate } from 'react-router-dom'
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
import { formatRelative } from '../../../../lib/format'

const BASE = '/dashboard/planner/ai-assistant'
const TYPES = [
  { value: 'client', label: 'Client meeting' },
  { value: 'vendor', label: 'Vendor meeting' },
  { value: 'internal', label: 'Internal / team' },
  { value: 'other', label: 'Other' },
]
const TYPE_TONE = { client: 'navy', vendor: 'purple', internal: 'emerald', other: 'muted' }
const BLANK = { title: '', meeting_type: 'client', event_id: '', meeting_date: '', attendees: '', notes: '' }

export default function AiMeetings() {
  const navigate = useNavigate()
  const [meetings, setMeetings] = useState([])
  const [events, setEvents] = useState([])
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState(null)

  const [open, setOpen] = useState(false)
  const [form, setForm] = useState(BLANK)
  const [saving, setSaving] = useState(false)
  const [processAfter, setProcessAfter] = useState(true)
  const [formError, setFormError] = useState(null)

  const load = useCallback(async () => {
    setLoading(true); setError(null)
    try {
      const [m, meta] = await Promise.all([api.get('/ai/meetings'), api.get('/ai/meta')])
      setMeetings(m.data.data.meetings)
      setEvents(meta.data.data.events ?? [])
    } catch (err) {
      setError(parseApiError(err).message)
    } finally {
      setLoading(false)
    }
  }, [])

  useEffect(() => { load() }, [load])

  const openNew = () => { setForm(BLANK); setProcessAfter(true); setFormError(null); setOpen(true) }

  const save = async () => {
    setSaving(true); setFormError(null)
    const payload = {
      title: form.title.trim(),
      meeting_type: form.meeting_type,
      event_id: form.event_id ? Number(form.event_id) : null,
      meeting_date: form.meeting_date || null,
      attendees: form.attendees.split(',').map((s) => s.trim()).filter(Boolean),
      notes: form.notes.trim(),
    }
    try {
      const res = await api.post('/ai/meetings', payload)
      const id = res.data.data.meeting.id
      if (processAfter) {
        try { await api.post(`/ai/meetings/${id}/process`) } catch { /* still land on the page */ }
      }
      setOpen(false)
      navigate(`${BASE}/meetings/${id}`)
    } catch (err) {
      setFormError(parseApiError(err).message)
    } finally {
      setSaving(false)
    }
  }

  const eventOptions = useMemo(
    () => [{ value: '', label: 'No event (general)' }, ...events.map((e) => ({ value: String(e.id), label: e.title }))],
    [events],
  )

  return (
    <div className="space-y-5">
      <div className="flex flex-wrap items-center justify-between gap-3">
        <div>
          <h2 className="text-lg font-bold text-ink">Meeting assistant</h2>
          <p className="text-sm text-muted">Paste your notes — the copilot writes a summary and pulls out action items you can push to the task board.</p>
        </div>
        <Button size="sm" onClick={openNew}><Icon name="Plus" className="size-4" /> New meeting</Button>
      </div>

      <LoadState loading={loading} error={error} onRetry={load}>
        {meetings.length === 0 ? (
          <EmptyState
            icon="NotebookPen"
            title="No meetings yet"
            description="Capture the notes from a client or vendor meeting. The copilot summarises the discussion, records decisions, and extracts action items grounded in the event."
            action={<Button size="sm" onClick={openNew}><Icon name="Plus" className="size-4" /> Capture your first meeting</Button>}
          />
        ) : (
          <div className="grid gap-3 lg:grid-cols-2">
            {meetings.map((m) => (
              <Card
                key={m.id}
                className="group cursor-pointer p-4 transition-colors hover:border-navy-200"
                onClick={() => navigate(`${BASE}/meetings/${m.id}`)}
              >
                <div className="flex items-start justify-between gap-2">
                  <div className="min-w-0">
                    <p className="font-bold text-ink">{m.title}</p>
                    <div className="mt-1 flex flex-wrap items-center gap-1.5">
                      <Badge tone={TYPE_TONE[m.meeting_type] ?? 'muted'}>{TYPES.find((t) => t.value === m.meeting_type)?.label ?? m.meeting_type}</Badge>
                      <Badge tone="navy">{m.scope === 'event' ? (m.event_title ?? 'Event') : 'General'}</Badge>
                      {m.meeting_date && <span className="text-xs text-muted">· {m.meeting_date}</span>}
                    </div>
                  </div>
                  {m.status === 'processed'
                    ? <Badge tone="emerald"><Icon name="Check" className="mr-1 inline size-3" />Processed</Badge>
                    : <Badge tone="amber">Captured</Badge>}
                </div>
                {m.attendees?.length > 0 && (
                  <p className="mt-2 truncate text-xs text-muted"><Icon name="Users" className="mr-1 inline size-3.5" />{m.attendees.join(', ')}</p>
                )}
                <div className="mt-3 flex items-center gap-3 text-xs text-muted">
                  {m.status === 'processed'
                    ? <span className="flex items-center gap-1 font-medium text-navy-700"><Icon name="ListPlus" className="size-3.5" />{m.action_items_count} action item{m.action_items_count === 1 ? '' : 's'}{m.open_actions_count > 0 ? ` · ${m.open_actions_count} open` : ''}</span>
                    : <span>Not processed yet</span>}
                  <span className="ml-auto">{formatRelative(m.created_at)}</span>
                </div>
              </Card>
            ))}
          </div>
        )}
      </LoadState>

      <Modal open={open} onClose={() => setOpen(false)} title="Capture a meeting">
        <div className="space-y-4">
          <div className="grid gap-4 sm:grid-cols-2">
            <div>
              <label className="mb-1.5 block text-sm font-medium text-ink">Title</label>
              <input
                value={form.title}
                onChange={(e) => setForm((f) => ({ ...f, title: e.target.value }))}
                placeholder="e.g. Client sync — venue walkthrough"
                className="h-11 w-full rounded-xl border border-line bg-surface px-3.5 text-sm text-ink outline-none focus:border-navy-300"
              />
            </div>
            <div>
              <label className="mb-1.5 block text-sm font-medium text-ink">Type</label>
              <ListboxSelect options={TYPES} value={form.meeting_type} onChange={(e) => setForm((f) => ({ ...f, meeting_type: e.target.value }))} />
            </div>
          </div>
          <div className="grid gap-4 sm:grid-cols-2">
            <div>
              <label className="mb-1.5 block text-sm font-medium text-ink">Event</label>
              <ListboxSelect options={eventOptions} value={form.event_id} onChange={(e) => setForm((f) => ({ ...f, event_id: e.target.value }))} />
            </div>
            <div>
              <label className="mb-1.5 block text-sm font-medium text-ink">Date (optional)</label>
              <input
                type="date"
                value={form.meeting_date}
                onChange={(e) => setForm((f) => ({ ...f, meeting_date: e.target.value }))}
                className="h-11 w-full rounded-xl border border-line bg-surface px-3.5 text-sm text-ink outline-none focus:border-navy-300"
              />
            </div>
          </div>
          <div>
            <label className="mb-1.5 block text-sm font-medium text-ink">Attendees (optional)</label>
            <input
              value={form.attendees}
              onChange={(e) => setForm((f) => ({ ...f, attendees: e.target.value }))}
              placeholder="Comma-separated, e.g. Amina, Sarah, DJ Mike"
              className="h-11 w-full rounded-xl border border-line bg-surface px-3.5 text-sm text-ink outline-none focus:border-navy-300"
            />
          </div>
          <div>
            <label className="mb-1.5 block text-sm font-medium text-ink">Notes / transcript</label>
            <textarea
              value={form.notes}
              onChange={(e) => setForm((f) => ({ ...f, notes: e.target.value }))}
              rows={8}
              placeholder={'Paste your raw notes. Tip: lines like "Amina to confirm the guest count" or "Action: send the floor plan" become action items.'}
              className="w-full resize-y rounded-xl border border-line bg-surface px-3.5 py-2.5 text-sm leading-relaxed text-ink outline-none focus:border-navy-300"
            />
          </div>
          <label className="flex items-center gap-2 text-sm text-ink">
            <input type="checkbox" checked={processAfter} onChange={(e) => setProcessAfter(e.target.checked)} className="size-4 rounded border-line text-navy-600" />
            Summarise &amp; extract action items now
          </label>
          {formError && <p className="text-sm text-danger">{formError}</p>}
          <div className="flex justify-end gap-2 pt-1">
            <Button size="sm" variant="ghost" onClick={() => setOpen(false)}>Cancel</Button>
            <Button size="sm" onClick={save} loading={saving} disabled={!form.title.trim() || !form.notes.trim()}>
              {processAfter ? 'Capture & process' : 'Capture'}
            </Button>
          </div>
        </div>
      </Modal>
    </div>
  )
}
