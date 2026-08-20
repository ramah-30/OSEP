import { useEffect, useState } from 'react'
import { useOutletContext } from 'react-router-dom'
import { useForm } from 'react-hook-form'
import Button from '../../../../components/ui/Button'
import Icon from '../../../../components/ui/Icon'
import Badge from '../../../../components/ui/Badge'
import Card from '../../../../components/ui/Card'
import Drawer from '../../../../components/ui/Drawer'
import ConfirmDialog from '../../../../components/ui/ConfirmDialog'
import EmptyState from '../../../../components/ui/EmptyState'
import { Field, SelectField } from '../../../../components/ui/Field'
import Textarea from '../../../../components/ui/Textarea'
import { AskAiButton, GenerateAiButton } from '../../../../components/ai/InlineAiButtons'
import { api } from '../../../../lib/api'
import { formatCurrency } from '../../../../lib/format'
import { VENDOR_STATUS_OPTIONS, VENDOR_STATUS_TONE } from '../../../../lib/eventConstants'

export default function EventVendors() {
  const { event, reload } = useOutletContext()
  const assignments = event.vendor_assignments ?? []
  const [drawer, setDrawer] = useState({ open: false, editing: null })
  const [removing, setRemoving] = useState(null)
  const [busy, setBusy] = useState(false)

  async function remove() {
    setBusy(true)
    try {
      await api.delete(`/events/${event.id}/vendor-assignments/${removing.id}`)
      setRemoving(null)
      reload()
    } finally {
      setBusy(false)
    }
  }

  return (
    <div className="space-y-5">
      <div className="flex items-center justify-between">
        <div>
          <h2 className="text-lg font-extrabold text-ink">Vendors</h2>
          <p className="text-sm text-muted">Coordinate the vendors working on this event.</p>
        </div>
        <div className="flex flex-wrap items-center gap-2">
          <AskAiButton eventId={event.id} prompt={`Which vendors for ${event.title} still need attention?`} label="Ask AI" />
          <GenerateAiButton templateKey="vendor_brief" eventId={event.id} label="Vendor brief" />
          <Button size="sm" onClick={() => setDrawer({ open: true, editing: null })}>
            <Icon name="Plus" className="size-4" /> Assign vendor
          </Button>
        </div>
      </div>

      {assignments.length ? (
        <div className="grid gap-4 md:grid-cols-2">
          {assignments.map((a) => (
            <Card key={a.id} className="p-5">
              <div className="flex items-start justify-between gap-3">
                <div className="min-w-0">
                  <p className="font-bold text-ink">{a.vendor_name}</p>
                  <p className="text-sm text-muted">{a.service}{a.package ? ` · ${a.package}` : ''}</p>
                </div>
                <Badge tone={VENDOR_STATUS_TONE[a.status] ?? 'muted'} dot>{a.status_label}</Badge>
              </div>
              <div className="mt-3 flex items-center justify-between">
                <span className="text-sm font-semibold text-ink">{a.price != null ? formatCurrency(a.price) : '—'}</span>
                <div className="flex gap-1">
                  <button type="button" onClick={() => setDrawer({ open: true, editing: a })} className="grid size-8 place-items-center rounded-btn text-muted hover:bg-canvas hover:text-ink"><Icon name="PenLine" className="size-4" /></button>
                  <button type="button" onClick={() => setRemoving(a)} className="grid size-8 place-items-center rounded-btn text-muted hover:bg-danger-soft hover:text-danger"><Icon name="Trash2" className="size-4" /></button>
                </div>
              </div>
            </Card>
          ))}
        </div>
      ) : (
        <EmptyState icon="Store" title="No vendors assigned" description="Assign vendors from the directory or add one manually."
          action={<Button size="sm" onClick={() => setDrawer({ open: true, editing: null })}><Icon name="Plus" className="size-4" /> Assign vendor</Button>} />
      )}

      <VendorDrawer
        key={drawer.editing?.id ?? 'new'}
        open={drawer.open}
        editing={drawer.editing}
        eventId={event.id}
        onClose={() => setDrawer({ open: false, editing: null })}
        onSaved={() => { setDrawer({ open: false, editing: null }); reload() }}
      />

      <ConfirmDialog open={!!removing} onClose={() => setRemoving(null)} onConfirm={remove}
        title="Remove vendor?" confirmLabel="Remove" loading={busy} />
    </div>
  )
}

function VendorDrawer({ open, editing, eventId, onClose, onSaved }) {
  const [directory, setDirectory] = useState([])
  const { register, handleSubmit, setValue, formState: { errors, isSubmitting } } = useForm({
    defaultValues: editing
      ? { vendor_id: editing.vendor_id ?? '', vendor_name: editing.vendor_name, service: editing.service, package: editing.package ?? '', price: editing.price ?? '', status: editing.status, notes: editing.notes ?? '' }
      : { status: 'requested' },
  })

  useEffect(() => {
    if (open) api.get('/vendors').then((r) => setDirectory(r.data.data.vendors ?? [])).catch(() => {})
  }, [open])

  function pickDirectory(e) {
    const id = e.target.value
    setValue('vendor_id', id)
    const v = directory.find((d) => String(d.id) === id)
    if (v) {
      setValue('vendor_name', v.business_name ?? v.full_name)
      if (v.category) setValue('service', v.category)
    }
  }

  const submit = handleSubmit(async (values) => {
    const payload = Object.fromEntries(Object.entries(values).filter(([, v]) => v !== ''))
    if (editing) {
      await api.put(`/events/${eventId}/vendor-assignments/${editing.id}`, payload)
    } else {
      await api.post(`/events/${eventId}/vendor-assignments`, payload)
    }
    onSaved()
  })

  return (
    <Drawer open={open} onClose={onClose} title={editing ? 'Edit vendor' : 'Assign vendor'}>
      <form onSubmit={submit} className="space-y-4">
        <SelectField label="From the directory (optional)" {...register('vendor_id')} onChange={pickDirectory}>
          <option value="">Off-platform / type manually</option>
          {directory.map((v) => <option key={v.id} value={v.id}>{v.business_name ?? v.full_name}{v.category ? ` · ${v.category}` : ''}</option>)}
        </SelectField>
        <Field label="Vendor name" error={errors.vendor_name?.message} {...register('vendor_name', { required: 'A vendor name is required' })} />
        <Field label="Service" error={errors.service?.message} {...register('service', { required: 'A service is required' })} />
        <Field label="Package" {...register('package')} />
        <div className="grid grid-cols-2 gap-4">
          <Field type="number" min="0" step="1000" label="Price (TZS)" {...register('price')} />
          <SelectField label="Status" options={VENDOR_STATUS_OPTIONS} {...register('status')} />
        </div>
        <Textarea label="Notes" rows={2} {...register('notes')} />
        <div className="flex justify-end pt-2">
          <Button type="submit" loading={isSubmitting}>{editing ? 'Save' : 'Assign vendor'}</Button>
        </div>
      </form>
    </Drawer>
  )
}
