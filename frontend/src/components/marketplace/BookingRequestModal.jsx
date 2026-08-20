import { useEffect, useState } from 'react'
import Modal from '../ui/Modal'
import Button from '../ui/Button'
import { Field, SelectField } from '../ui/Field'
import Textarea from '../ui/Textarea'
import { api, parseApiError } from '../../lib/api'
import { useResource } from '../../lib/useResource'

/**
 * Sends a booking request to a vendor or venue. `provider` is
 * { type: 'vendor'|'venue', id, name }.
 */
export default function BookingRequestModal({ open, onClose, provider, onSubmitted }) {
  const { data: eventsData } = useResource('/events')
  const [form, setForm] = useState({ title: '', event_id: '', event_date: '', guest_count: '', budget: '', requirements: '' })
  const [saving, setSaving] = useState(false)
  const [error, setError] = useState(null)

  useEffect(() => {
    if (open) {
      setForm({ title: '', event_id: '', event_date: '', guest_count: '', budget: '', requirements: '' })
      setError(null)
    }
  }, [open, provider])

  const set = (patch) => setForm((f) => ({ ...f, ...patch }))

  const submit = async () => {
    setSaving(true)
    setError(null)
    try {
      await api.post('/marketplace/booking-requests', {
        provider_type: provider.type,
        provider_id: provider.id,
        title: form.title || null,
        event_id: form.event_id || null,
        event_date: form.event_date || null,
        guest_count: form.guest_count || null,
        budget: form.budget || null,
        requirements: form.requirements || null,
      })
      onSubmitted?.()
      onClose()
    } catch (err) {
      setError(parseApiError(err).message)
    } finally {
      setSaving(false)
    }
  }

  const events = eventsData?.events ?? []

  return (
    <Modal
      open={open}
      onClose={onClose}
      title={`Request a booking${provider?.name ? ` — ${provider.name}` : ''}`}
      description="Send your event details and requirements. The provider will respond with a decision or a quotation."
      footer={
        <div className="flex justify-end gap-3">
          <Button variant="ghost" onClick={onClose}>Cancel</Button>
          <Button onClick={submit} loading={saving}>Send request</Button>
        </div>
      }
    >
      <div className="space-y-4">
        {error && <p className="rounded-btn bg-danger-soft px-3 py-2 text-sm text-danger">{error}</p>}
        <Field label="Title" value={form.title} onChange={(e) => set({ title: e.target.value })} placeholder="e.g. Wedding photography" />
        <SelectField
          label="Link to event (optional)"
          value={form.event_id}
          onChange={(e) => set({ event_id: e.target.value })}
        >
          <option value="">No linked event</option>
          {events.map((ev) => (
            <option key={ev.id} value={ev.id}>{ev.title}</option>
          ))}
        </SelectField>
        <div className="grid grid-cols-2 gap-3">
          <Field label="Event date" type="date" value={form.event_date} onChange={(e) => set({ event_date: e.target.value })} />
          <Field label="Guest count" type="number" min="0" value={form.guest_count} onChange={(e) => set({ guest_count: e.target.value })} />
        </div>
        <Field label="Budget (TZS)" type="number" min="0" value={form.budget} onChange={(e) => set({ budget: e.target.value })} placeholder="Optional" />
        <Textarea label="Requirements" rows={4} value={form.requirements} onChange={(e) => set({ requirements: e.target.value })} placeholder="Describe what you need…" />
      </div>
    </Modal>
  )
}
