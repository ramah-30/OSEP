import { useState } from 'react'
import { useOutletContext } from 'react-router-dom'
import { useForm } from 'react-hook-form'
import Card from '../../../../components/ui/Card'
import Button from '../../../../components/ui/Button'
import { Field, SelectField } from '../../../../components/ui/Field'
import Textarea from '../../../../components/ui/Textarea'
import Checkbox from '../../../../components/ui/Checkbox'
import { api, applyServerErrors, parseApiError } from '../../../../lib/api'
import { VENUE_SETTING_OPTIONS } from '../../../../lib/eventConstants'

export default function VenueTab() {
  const { event, reload } = useOutletContext()
  const venue = event.venue_detail
  const [saved, setSaved] = useState(false)
  const { register, handleSubmit, setError, formState: { errors, isSubmitting } } = useForm({
    defaultValues: venue
      ? {
          name: venue.name, address: venue.address ?? '', capacity: venue.capacity ?? '', setting: venue.setting ?? '',
          contact_person: venue.contact_person ?? '', contact_phone: venue.contact_phone ?? '',
          parking_available: venue.parking_available ?? false, setup_time: venue.setup_time ?? '',
          breakdown_time: venue.breakdown_time ?? '', notes: venue.notes ?? '',
        }
      : { name: event.venue ?? '', parking_available: false },
  })

  const submit = handleSubmit(async (values) => {
    const payload = Object.fromEntries(Object.entries(values).filter(([, v]) => v !== ''))
    try {
      await api.put(`/events/${event.id}/venue`, payload)
      setSaved(true)
      reload()
      setTimeout(() => setSaved(false), 2500)
    } catch (err) {
      applyServerErrors(parseApiError(err).errors, setError)
    }
  })

  return (
    <div className="max-w-2xl space-y-5">
      <div>
        <h2 className="text-lg font-extrabold text-ink">Venue</h2>
        <p className="text-sm text-muted">Where the event takes place, and the logistics around it.</p>
      </div>

      <Card className="p-6">
        <form onSubmit={submit} className="space-y-4">
          <Field label="Venue name" error={errors.name?.message} {...register('name', { required: 'A venue name is required' })} />
          <Field label="Address" {...register('address')} />
          <div className="grid gap-4 sm:grid-cols-2">
            <Field type="number" min="0" label="Capacity" {...register('capacity')} />
            <SelectField label="Setting" {...register('setting')}>
              <option value="">Not set</option>
              {VENUE_SETTING_OPTIONS.map((s) => <option key={s.value} value={s.value}>{s.label}</option>)}
            </SelectField>
          </div>
          <div className="grid gap-4 sm:grid-cols-2">
            <Field label="Contact person" {...register('contact_person')} />
            <Field label="Contact phone" {...register('contact_phone')} />
          </div>
          <div className="grid gap-4 sm:grid-cols-2">
            <Field type="time" label="Setup time" {...register('setup_time')} />
            <Field type="time" label="Breakdown time" {...register('breakdown_time')} />
          </div>
          <Checkbox label="Parking available" {...register('parking_available')} />
          <Textarea label="Notes" rows={3} {...register('notes')} />
          <div className="flex items-center justify-end gap-3 pt-2">
            {saved && <span className="text-sm font-semibold text-emerald-600">Saved</span>}
            <Button type="submit" loading={isSubmitting}>Save venue</Button>
          </div>
        </form>
      </Card>
    </div>
  )
}
