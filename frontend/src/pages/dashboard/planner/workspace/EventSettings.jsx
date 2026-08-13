import { useState } from 'react'
import { useNavigate, useOutletContext } from 'react-router-dom'
import Card from '../../../../components/ui/Card'
import Button from '../../../../components/ui/Button'
import Icon from '../../../../components/ui/Icon'
import ConfirmDialog from '../../../../components/ui/ConfirmDialog'
import EventForm from '../../../../components/dashboard/EventForm'
import { api } from '../../../../lib/api'
import { useAuth } from '../../../../context/AuthContext'

export default function EventSettings() {
  const { event, reload } = useOutletContext()
  const { user } = useAuth()
  const navigate = useNavigate()
  const [submitting, setSubmitting] = useState(false)
  const [saved, setSaved] = useState(false)
  const [removing, setRemoving] = useState(false)
  const [busy, setBusy] = useState(false)

  const defaults = {
    title: event.title,
    event_type: event.event_type ?? '',
    client_id: event.client ? String(event.client.id) : '',
    event_date: event.event_date ?? '',
    start_time: event.start_time?.slice(0, 5) ?? '',
    end_time: event.end_time?.slice(0, 5) ?? '',
    venue: event.venue ?? '',
    location: event.location ?? '',
    expected_guests: event.expected_guests ?? '',
    theme: event.theme ?? '',
    priority: event.priority ?? 'medium',
    description: event.description ?? '',
    internal_notes: event.internal_notes ?? '',
    budget_total: event.budget.total ?? '',
  }

  async function handleSave(values) {
    setSubmitting(true)
    try {
      await api.put(`/events/${event.id}`, values)
      setSaved(true)
      reload()
      setTimeout(() => setSaved(false), 2500)
    } finally {
      setSubmitting(false)
    }
  }

  async function handleDelete() {
    setBusy(true)
    try {
      await api.delete(`/events/${event.id}`)
      navigate(`${user.dashboard_path}/events`)
    } finally {
      setBusy(false)
    }
  }

  return (
    <div className="max-w-2xl space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h2 className="text-lg font-extrabold text-ink">Event settings</h2>
          <p className="text-sm text-muted">Update the event details.</p>
        </div>
        {saved && <span className="text-sm font-semibold text-emerald-600">Saved</span>}
      </div>

      <Card className="p-6">
        <EventForm defaultValues={defaults} onSubmit={handleSave} submitting={submitting} submitLabel="Save changes" />
      </Card>

      <Card className="border-danger/30 p-6">
        <h3 className="font-bold text-ink">Danger zone</h3>
        <p className="mt-1 text-sm text-muted">Deleting an event removes its tasks, guests, budget and documents. This can’t be undone.</p>
        <Button variant="danger" size="sm" className="mt-4" onClick={() => setRemoving(true)}>
          <Icon name="Trash2" className="size-4" /> Delete event
        </Button>
      </Card>

      <ConfirmDialog open={removing} onClose={() => setRemoving(false)} onConfirm={handleDelete}
        title="Delete this event?" description={event.title} confirmLabel="Delete event" loading={busy} />
    </div>
  )
}
