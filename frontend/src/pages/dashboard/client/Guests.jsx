import { useCallback, useEffect, useMemo, useState } from 'react'
import Card from '../../../components/ui/Card'
import Icon from '../../../components/ui/Icon'
import Badge from '../../../components/ui/Badge'
import Button from '../../../components/ui/Button'
import Modal from '../../../components/ui/Modal'
import { Field } from '../../../components/ui/Field'
import ListboxSelect from '../../../components/ui/ListboxSelect'
import EmptyState from '../../../components/ui/EmptyState'
import PageHeader from '../../../components/ui/PageHeader'
import LoadState from '../../../components/dashboard/LoadState'
import { useResource } from '../../../lib/useResource'
import { api, parseApiError } from '../../../lib/api'

const RSVP_TONE = {
  confirmed: 'emerald',
  attended: 'emerald',
  declined: 'danger',
  maybe: 'amber',
  invited: 'navy',
  pending: 'muted',
}

const RSVP_OPTIONS = [
  { value: 'pending', label: 'Pending' },
  { value: 'confirmed', label: 'Confirmed' },
  { value: 'declined', label: 'Declined' },
  { value: 'maybe', label: 'Maybe' },
]

const BLANK = { full_name: '', email: '', phone: '', category: '', rsvp_status: 'pending', notes: '' }

export default function Guests() {
  // The client's events drive which guest list we're looking at.
  const { data: eventsData, loading: eventsLoading, error: eventsError, reload: reloadEvents } = useResource('/my-events')
  const events = useMemo(() => eventsData?.events ?? [], [eventsData])

  const [eventId, setEventId] = useState(null)
  const activeEventId = eventId ?? events[0]?.id ?? null

  const [state, setState] = useState({ loading: false, error: null, guests: [], summary: null })
  const [modal, setModal] = useState(null) // { mode: 'add' | 'edit', guest }
  const [form, setForm] = useState(BLANK)
  const [saving, setSaving] = useState(false)
  const [formError, setFormError] = useState(null)
  const [removingId, setRemovingId] = useState(null)

  const loadGuests = useCallback(async (id) => {
    if (!id) return
    setState((s) => ({ ...s, loading: true, error: null }))
    try {
      const r = await api.get(`/my-events/${id}/guests`)
      setState({ loading: false, error: null, guests: r.data.data.guests, summary: r.data.data.summary })
    } catch (err) {
      setState((s) => ({ ...s, loading: false, error: parseApiError(err).message }))
    }
  }, [])

  useEffect(() => { loadGuests(activeEventId) }, [activeEventId, loadGuests])

  const openAdd = () => { setForm(BLANK); setFormError(null); setModal({ mode: 'add' }) }
  const openEdit = (guest) => {
    setForm({
      full_name: guest.full_name ?? '', email: guest.email ?? '', phone: guest.phone ?? '',
      category: guest.category ?? '', rsvp_status: guest.rsvp_status ?? 'pending', notes: guest.notes ?? '',
    })
    setFormError(null)
    setModal({ mode: 'edit', guest })
  }

  const setField = (key, v) => setForm((f) => ({ ...f, [key]: v }))

  const save = async () => {
    setSaving(true)
    setFormError(null)
    try {
      if (modal.mode === 'add') {
        await api.post(`/my-events/${activeEventId}/guests`, form)
      } else {
        await api.put(`/my-events/${activeEventId}/guests/${modal.guest.id}`, form)
      }
      setModal(null)
      loadGuests(activeEventId)
    } catch (err) {
      setFormError(parseApiError(err).message)
    } finally {
      setSaving(false)
    }
  }

  const remove = async (guest) => {
    setRemovingId(guest.id)
    try {
      await api.delete(`/my-events/${activeEventId}/guests/${guest.id}`)
      loadGuests(activeEventId)
    } catch { /* keep list */ } finally {
      setRemovingId(null)
    }
  }

  const { guests, summary } = state

  return (
    <div className="space-y-6">
      <PageHeader
        title="Guest List"
        description="Add, edit or remove guests for your event. Your planner is notified of every change."
        actions={activeEventId && <Button onClick={openAdd}><Icon name="Plus" className="size-4" /> Add guest</Button>}
      />

      <LoadState loading={eventsLoading} error={eventsError} onRetry={reloadEvents}>
        {events.length === 0 ? (
          <EmptyState
            icon="Users"
            title="No event yet"
            description="Once your planner sets up your event, you'll be able to build your guest list here."
          />
        ) : (
          <>
            {events.length > 1 && (
              <ListboxSelect
                className="max-w-xs"
                options={events.map((e) => ({ value: String(e.id), label: e.title }))}
                value={String(activeEventId)}
                onChange={(e) => setEventId(Number(e.target.value))}
              />
            )}

            {summary && (
              <div className="grid grid-cols-2 gap-3 sm:grid-cols-4">
                <Stat label="Total" value={summary.total} icon="Users" accent="navy" />
                <Stat label="Confirmed" value={summary.confirmed} icon="CheckCircle2" accent="emerald" />
                <Stat label="Pending" value={summary.pending} icon="Clock" accent="amber" />
                <Stat label="Declined" value={summary.declined} icon="XCircle" accent="danger" />
              </div>
            )}

            <LoadState loading={state.loading} error={state.error} onRetry={() => loadGuests(activeEventId)}>
              {guests.length === 0 ? (
                <EmptyState
                  icon="UserPlus"
                  title="No guests yet"
                  description="Add your first guest to start building the list."
                  action={<Button onClick={openAdd}><Icon name="Plus" className="size-4" /> Add guest</Button>}
                />
              ) : (
                <Card className="divide-y divide-line">
                  {guests.map((g) => (
                    <div key={g.id} className="flex items-center gap-4 p-4">
                      <span className="grid size-10 shrink-0 place-items-center rounded-full bg-navy-50 font-bold text-navy-700">
                        {(g.full_name || '?').charAt(0).toUpperCase()}
                      </span>
                      <div className="min-w-0 flex-1">
                        <p className="truncate font-semibold text-ink">{g.full_name}</p>
                        <p className="truncate text-xs text-muted">
                          {[g.email, g.phone, g.category].filter(Boolean).join(' · ') || 'No contact details'}
                        </p>
                      </div>
                      <Badge tone={RSVP_TONE[g.rsvp_status] ?? 'muted'}>{g.rsvp_status_label}</Badge>
                      <div className="flex items-center gap-1">
                        <button
                          type="button"
                          onClick={() => openEdit(g)}
                          title="Edit"
                          className="grid size-8 place-items-center rounded-lg text-muted transition-colors hover:bg-canvas hover:text-ink"
                        >
                          <Icon name="Pencil" className="size-4" />
                        </button>
                        <button
                          type="button"
                          onClick={() => remove(g)}
                          disabled={removingId === g.id}
                          title="Remove"
                          className="grid size-8 place-items-center rounded-lg text-muted transition-colors hover:bg-danger-50 hover:text-danger disabled:opacity-50"
                        >
                          <Icon name="Trash2" className="size-4" />
                        </button>
                      </div>
                    </div>
                  ))}
                </Card>
              )}
            </LoadState>
          </>
        )}
      </LoadState>

      {modal && (
        <Modal
          open
          onClose={() => setModal(null)}
          title={modal.mode === 'add' ? 'Add guest' : 'Edit guest'}
          description="Your planner will be notified when you save."
          footer={
            <>
              <Button variant="ghost" onClick={() => setModal(null)} disabled={saving}>Cancel</Button>
              <Button onClick={save} loading={saving} disabled={!form.full_name.trim()}>
                <Icon name="Check" className="size-4" /> Save
              </Button>
            </>
          }
        >
          <div className="space-y-4">
            {formError && <p className="text-sm text-danger">{formError}</p>}
            <Field label="Full name" value={form.full_name} onChange={(e) => setField('full_name', e.target.value)} placeholder="e.g. John Doe" />
            <div className="grid gap-4 sm:grid-cols-2">
              <Field label="Email" type="email" value={form.email} onChange={(e) => setField('email', e.target.value)} placeholder="john@example.com" />
              <Field label="Phone" value={form.phone} onChange={(e) => setField('phone', e.target.value)} placeholder="+255…" />
            </div>
            <div className="grid gap-4 sm:grid-cols-2">
              <Field label="Category" value={form.category} onChange={(e) => setField('category', e.target.value)} placeholder="e.g. Family" />
              <div>
                <label className="mb-1.5 block text-sm font-semibold text-ink">RSVP status</label>
                <ListboxSelect options={RSVP_OPTIONS} value={form.rsvp_status} onChange={(e) => setField('rsvp_status', e.target.value)} />
              </div>
            </div>
            <Field label="Notes" value={form.notes} onChange={(e) => setField('notes', e.target.value)} placeholder="Anything the planner should know" />
          </div>
        </Modal>
      )}
    </div>
  )
}

function Stat({ label, value, icon, accent }) {
  const tones = {
    navy: 'bg-navy-50 text-navy-700',
    emerald: 'bg-emerald-50 text-emerald-600',
    amber: 'bg-amber-50 text-warning',
    danger: 'bg-danger-50 text-danger',
  }
  return (
    <Card className="flex items-center gap-3 p-4">
      <span className={`grid size-9 shrink-0 place-items-center rounded-lg ${tones[accent]}`}>
        <Icon name={icon} className="size-4" />
      </span>
      <div>
        <p className="text-lg font-extrabold text-ink">{value}</p>
        <p className="text-xs text-muted">{label}</p>
      </div>
    </Card>
  )
}
