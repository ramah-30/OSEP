import { useEffect, useState } from 'react'
import { useForm } from 'react-hook-form'
import { Field, SelectField } from '../ui/Field'
import Textarea from '../ui/Textarea'
import Button from '../ui/Button'
import { api, applyServerErrors, parseApiError } from '../../lib/api'
import { PRIORITY_OPTIONS } from '../../lib/eventConstants'

/**
 * Create/edit form for an event. The platform is wedding-only, so the event type
 * is fixed to "Wedding" rather than chosen. Venue and location are hidden on
 * creation (`showVenueLocation`) since they're set later, once a venue is booked.
 * Clients are added on the dedicated Clients page, then picked here.
 */
export default function EventForm({
  defaultValues = {},
  onSubmit,
  submitting,
  submitLabel = 'Create event',
  showVenueLocation = true,
}) {
  const {
    register,
    handleSubmit,
    setError,
    formState: { errors },
  } = useForm({
    defaultValues: {
      priority: 'medium',
      event_type: 'Wedding',
      ...defaultValues,
    },
  })

  const [clients, setClients] = useState([])

  useEffect(() => {
    api.get('/clients').then((r) => setClients(r.data.data.clients ?? [])).catch(() => {})
  }, [])

  const submit = handleSubmit(async (values) => {
    // Drop empty strings so nullable columns stay null.
    const payload = Object.fromEntries(
      Object.entries(values).filter(([, v]) => v !== '' && v !== undefined),
    )
    try {
      await onSubmit(payload)
    } catch (err) {
      applyServerErrors(parseApiError(err).errors, setError)
      throw err
    }
  })

  return (
    <form onSubmit={submit} className="space-y-4" id="event-form">
      {/* Wedding-only platform: the type is fixed, carried through on submit. */}
      <input type="hidden" {...register('event_type')} />

      <Field label="Event name" placeholder="e.g. Sarah & John's Wedding" error={errors.title?.message}
        {...register('title', { required: 'An event name is required' })} />

      <div className="grid gap-4 sm:grid-cols-2">
        <SelectField label="Priority" options={PRIORITY_OPTIONS} {...register('priority')} />

        <SelectField label="Client" error={errors.client_id?.message} {...register('client_id')}>
          <option value="">No client yet</option>
          {clients.map((c) => <option key={c.id} value={c.id}>{c.full_name} · {c.email}</option>)}
        </SelectField>
      </div>

      <div className="grid gap-4 sm:grid-cols-3">
        <Field type="date" label="Event date" {...register('event_date')} />
        <Field type="time" label="Start time" {...register('start_time')} />
        <Field type="time" label="End time" {...register('end_time')} />
      </div>

      {showVenueLocation && (
        <div className="grid gap-4 sm:grid-cols-2">
          <Field label="Venue" placeholder="Venue name" {...register('venue')} />
          <Field label="Location" placeholder="City, country" {...register('location')} />
        </div>
      )}

      <div className="grid gap-4 sm:grid-cols-2">
        <Field type="number" min="0" label="Expected guests" {...register('expected_guests')} />
        <Field type="number" min="0" step="1000" label="Total budget (TZS)" {...register('budget_total')} />
      </div>

      <Field label="Theme (optional)" placeholder="e.g. Blush & Ivory Garden" {...register('theme')} />

      <Textarea label="Description" rows={3} placeholder="A short description of the event"
        {...register('description')} />

      <div className="flex justify-end pt-2">
        <Button type="submit" loading={submitting}>{submitLabel}</Button>
      </div>
    </form>
  )
}
