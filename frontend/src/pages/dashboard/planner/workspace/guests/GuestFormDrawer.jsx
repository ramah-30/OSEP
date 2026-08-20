import { useForm } from 'react-hook-form'
import Button from '../../../../../components/ui/Button'
import Drawer from '../../../../../components/ui/Drawer'
import { Field, SelectField } from '../../../../../components/ui/Field'
import Textarea from '../../../../../components/ui/Textarea'
import { api, applyServerErrors, parseApiError } from '../../../../../lib/api'
import { RSVP_STATUS_OPTIONS } from '../../../../../lib/guestConstants'

/**
 * Add / edit a guest profile. `categories` are the rich guest categories for the
 * picker; `editing` is a guest object or null.
 */
export default function GuestFormDrawer({ open, editing, eventId, categories = [], onClose, onSaved }) {
  const { register, handleSubmit, setError, formState: { errors, isSubmitting } } = useForm({
    defaultValues: editing
      ? {
          first_name: editing.first_name ?? '', last_name: editing.last_name ?? '',
          email: editing.email ?? '', phone: editing.phone ?? '', gender: editing.gender ?? '',
          category: editing.category ?? '', rsvp_status: editing.rsvp_status,
          plus_ones_allowed: editing.plus_ones_allowed ?? 0, meal_preference: editing.meal_preference ?? '',
          dietary_restrictions: editing.dietary_restrictions ?? '',
          accessibility_requirements: editing.accessibility_requirements ?? '', notes: editing.notes ?? '',
        }
      : { rsvp_status: 'pending', plus_ones_allowed: 0 },
  })

  const submit = handleSubmit(async (values) => {
    const payload = Object.fromEntries(Object.entries(values).filter(([, v]) => v !== ''))
    try {
      if (editing) await api.put(`/events/${eventId}/guests/${editing.id}`, payload)
      else await api.post(`/events/${eventId}/guests`, payload)
      onSaved()
    } catch (err) {
      applyServerErrors(parseApiError(err).errors, setError)
    }
  })

  return (
    <Drawer open={open} onClose={onClose} title={editing ? 'Edit guest' : 'Add guest'}
      description="Full guest profile — contact, category and preferences.">
      <form onSubmit={submit} className="space-y-4">
        <div className="grid grid-cols-2 gap-4">
          <Field label="First name" error={errors.first_name?.message} {...register('first_name', { required: 'Required' })} />
          <Field label="Last name" {...register('last_name')} />
        </div>
        <div className="grid grid-cols-2 gap-4">
          <Field type="email" label="Email" error={errors.email?.message} {...register('email')} />
          <Field label="Phone" {...register('phone')} />
        </div>
        <div className="grid grid-cols-2 gap-4">
          <SelectField label="Category" {...register('category')}>
            <option value="">No category</option>
            {categories.map((c) => <option key={c.id} value={c.name}>{c.name}</option>)}
          </SelectField>
          <SelectField label="Gender" {...register('gender')}>
            <option value="">Prefer not to say</option>
            <option value="Female">Female</option>
            <option value="Male">Male</option>
            <option value="Other">Other</option>
          </SelectField>
        </div>
        <div className="grid grid-cols-2 gap-4">
          <SelectField label="RSVP" options={RSVP_STATUS_OPTIONS} {...register('rsvp_status')} />
          <Field type="number" min="0" max="20" label="Plus-ones allowed" {...register('plus_ones_allowed')} />
        </div>
        <Field label="Meal preference" {...register('meal_preference')} />
        <Textarea label="Dietary restrictions" rows={2} {...register('dietary_restrictions')} />
        <Textarea label="Accessibility requirements" rows={2} {...register('accessibility_requirements')} />
        <Textarea label="Notes" rows={2} {...register('notes')} />
        <div className="flex justify-end pt-2">
          <Button type="submit" loading={isSubmitting}>{editing ? 'Save guest' : 'Add guest'}</Button>
        </div>
      </form>
    </Drawer>
  )
}
