import { useState } from 'react'
import { useForm } from 'react-hook-form'
import Button from '../../../../../components/ui/Button'
import Card from '../../../../../components/ui/Card'
import Badge from '../../../../../components/ui/Badge'
import Icon from '../../../../../components/ui/Icon'
import Modal from '../../../../../components/ui/Modal'
import ConfirmDialog from '../../../../../components/ui/ConfirmDialog'
import { Field, SelectField } from '../../../../../components/ui/Field'
import Textarea from '../../../../../components/ui/Textarea'
import LoadState from '../../../../../components/dashboard/LoadState'
import { useResource } from '../../../../../lib/useResource'
import { api } from '../../../../../lib/api'
import { TEMPLATE_TYPE_OPTIONS } from '../../../../../lib/guestConstants'

export default function GuestSettingsPanel({ eventId }) {
  return (
    <div className="space-y-8">
      <MealOptions eventId={eventId} />
      <Templates />
    </div>
  )
}

/* ---------------------------------------------------------------- Meal options */

function MealOptions({ eventId }) {
  const { data, loading, error, reload } = useResource(`/events/${eventId}/meal-options`)
  const [modal, setModal] = useState({ open: false, editing: null })
  const [removing, setRemoving] = useState(null)
  const options = data?.meal_options ?? []

  return (
    <section className="space-y-4">
      <div className="flex items-center justify-between">
        <div>
          <h2 className="text-lg font-extrabold text-ink">Meal Options</h2>
          <p className="text-sm text-muted">The menu choices guests pick from when they RSVP.</p>
        </div>
        <Button size="sm" onClick={() => setModal({ open: true, editing: null })}><Icon name="Utensils" className="size-4" /> Add meal</Button>
      </div>

      <LoadState loading={loading} error={error} onRetry={reload}>
        <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
          {options.map((m) => (
            <Card key={m.id} className="flex items-start justify-between gap-2 p-4">
              <div className="min-w-0">
                <p className="font-bold text-ink">{m.name}</p>
                {m.description && <p className="text-xs text-muted">{m.description}</p>}
                {m.dietary_tags && <Badge tone="emerald" className="mt-1">{m.dietary_tags}</Badge>}
              </div>
              <div className="flex gap-1">
                <button type="button" onClick={() => setModal({ open: true, editing: m })} className="grid size-7 place-items-center rounded-btn text-muted hover:bg-canvas hover:text-ink"><Icon name="PenLine" className="size-4" /></button>
                <button type="button" onClick={() => setRemoving(m)} className="grid size-7 place-items-center rounded-btn text-muted hover:bg-danger-soft hover:text-danger"><Icon name="Trash2" className="size-4" /></button>
              </div>
            </Card>
          ))}
          {!options.length && <p className="text-sm text-muted">No meal options yet.</p>}
        </div>
      </LoadState>

      <MealModal key={modal.editing?.id ?? 'new'} open={modal.open} editing={modal.editing} eventId={eventId}
        onClose={() => setModal({ open: false, editing: null })}
        onSaved={() => { setModal({ open: false, editing: null }); reload() }} />
      <ConfirmDialog open={!!removing} onClose={() => setRemoving(null)}
        onConfirm={async () => { await api.delete(`/events/${eventId}/meal-options/${removing.id}`); setRemoving(null); reload() }}
        title="Remove meal option?" description={removing?.name} confirmLabel="Remove" />
    </section>
  )
}

function MealModal({ open, editing, eventId, onClose, onSaved }) {
  const { register, handleSubmit, formState: { isSubmitting } } = useForm({
    defaultValues: editing ?? { name: '', description: '', dietary_tags: '' },
  })
  const submit = handleSubmit(async (values) => {
    if (editing) await api.put(`/events/${eventId}/meal-options/${editing.id}`, values)
    else await api.post(`/events/${eventId}/meal-options`, values)
    onSaved()
  })
  return (
    <Modal open={open} onClose={onClose} title={editing ? 'Edit meal option' : 'Add meal option'}
      footer={<><Button variant="ghost" size="sm" onClick={onClose}>Cancel</Button><Button size="sm" onClick={submit} loading={isSubmitting}>Save</Button></>}>
      <form onSubmit={submit} className="space-y-4">
        <Field label="Name" {...register('name', { required: true })} />
        <Field label="Description" {...register('description')} />
        <Field label="Dietary tags" {...register('dietary_tags')} placeholder="e.g. vegetarian, gluten-free" />
      </form>
    </Modal>
  )
}

/* ------------------------------------------------------------ Invitation templates */

function Templates() {
  const { data, loading, error, reload } = useResource('/invitation-templates')
  const [modal, setModal] = useState({ open: false, editing: null })
  const [removing, setRemoving] = useState(null)
  const templates = data?.templates ?? []

  async function duplicate(t) {
    await api.post(`/invitation-templates/${t.id}/duplicate`)
    reload()
  }

  return (
    <section className="space-y-4">
      <div className="flex items-center justify-between">
        <div>
          <h2 className="text-lg font-extrabold text-ink">Invitation Templates</h2>
          <p className="text-sm text-muted">Reusable designs. Duplicate a starter to make it your own.</p>
        </div>
        <Button size="sm" onClick={() => setModal({ open: true, editing: null })}><Icon name="Plus" className="size-4" /> New template</Button>
      </div>

      <LoadState loading={loading} error={error} onRetry={reload}>
        <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
          {templates.map((t) => (
            <Card key={t.id} className="flex flex-col gap-2 p-4">
              <div className="h-1.5 w-full rounded-full" style={{ background: t.theme?.primary ?? '#2947c8' }} />
              <div className="flex items-center justify-between gap-2">
                <p className="font-bold text-ink">{t.name}</p>
                {!t.is_owned && <Badge tone="muted">Starter</Badge>}
              </div>
              <p className="text-xs uppercase tracking-wide text-muted">{t.type_label}</p>
              <p className="line-clamp-2 text-sm text-muted">{t.subject}</p>
              <div className="mt-auto flex gap-1 pt-2">
                <Button size="sm" variant="secondary" onClick={() => duplicate(t)}><Icon name="Copy" className="size-4" /></Button>
                {t.is_owned && <>
                  <Button size="sm" variant="ghost" onClick={() => setModal({ open: true, editing: t })}><Icon name="PenLine" className="size-4" /></Button>
                  <Button size="sm" variant="ghost" className="text-danger" onClick={() => setRemoving(t)}><Icon name="Trash2" className="size-4" /></Button>
                </>}
              </div>
            </Card>
          ))}
        </div>
      </LoadState>

      <TemplateModal key={modal.editing?.id ?? 'new'} open={modal.open} editing={modal.editing}
        onClose={() => setModal({ open: false, editing: null })}
        onSaved={() => { setModal({ open: false, editing: null }); reload() }} />
      <ConfirmDialog open={!!removing} onClose={() => setRemoving(null)}
        onConfirm={async () => { await api.delete(`/invitation-templates/${removing.id}`); setRemoving(null); reload() }}
        title="Delete template?" description={removing?.name} confirmLabel="Delete" />
    </section>
  )
}

function TemplateModal({ open, editing, onClose, onSaved }) {
  const { register, handleSubmit, formState: { isSubmitting } } = useForm({
    defaultValues: editing
      ? { name: editing.name, type: editing.type, subject: editing.subject ?? '', body: editing.body ?? '', primary: editing.theme?.primary ?? '#2947c8' }
      : { name: '', type: 'custom', subject: '', body: '', primary: '#2947c8' },
  })
  const submit = handleSubmit(async ({ primary, ...values }) => {
    const payload = { ...values, theme: { primary } }
    if (editing) await api.put(`/invitation-templates/${editing.id}`, payload)
    else await api.post('/invitation-templates', payload)
    onSaved()
  })
  return (
    <Modal open={open} onClose={onClose} title={editing ? 'Edit template' : 'New template'}
      footer={<><Button variant="ghost" size="sm" onClick={onClose}>Cancel</Button><Button size="sm" onClick={submit} loading={isSubmitting}>Save</Button></>}>
      <form onSubmit={submit} className="space-y-4">
        <div className="grid grid-cols-2 gap-4">
          <Field label="Name" {...register('name', { required: true })} />
          <SelectField label="Type" options={TEMPLATE_TYPE_OPTIONS} {...register('type')} />
        </div>
        <Field label="Subject" {...register('subject')} />
        <Textarea label="Message body" rows={5} {...register('body')} placeholder="Use {{first_name}} and {{event}} for personalisation." />
        <div>
          <label className="mb-1.5 block text-sm font-semibold text-ink">Accent colour</label>
          <input type="color" {...register('primary')} className="h-12 w-full cursor-pointer rounded-btn border border-line bg-surface" />
        </div>
      </form>
    </Modal>
  )
}
