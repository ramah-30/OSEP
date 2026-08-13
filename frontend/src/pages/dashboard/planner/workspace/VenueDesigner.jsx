import { useEffect, useState } from 'react'
import { useOutletContext } from 'react-router-dom'
import { useForm } from 'react-hook-form'
import Button from '../../../../components/ui/Button'
import Icon from '../../../../components/ui/Icon'
import Drawer from '../../../../components/ui/Drawer'
import Modal from '../../../../components/ui/Modal'
import ConfirmDialog from '../../../../components/ui/ConfirmDialog'
import EmptyState from '../../../../components/ui/EmptyState'
import { Field } from '../../../../components/ui/Field'
import LoadState from '../../../../components/dashboard/LoadState'
import Designer from '../../../../components/venue/Designer'
import { useResource } from '../../../../lib/useResource'
import { api } from '../../../../lib/api'
import { cn } from '../../../../lib/cn'

export default function VenueDesigner() {
  const { event } = useOutletContext()
  const { data, loading, error, reload } = useResource(`/events/${event.id}/venue-layouts`)
  const [activeId, setActiveId] = useState(null)
  const [creating, setCreating] = useState(false)
  const [renaming, setRenaming] = useState(false)
  const [removing, setRemoving] = useState(false)
  const [busy, setBusy] = useState(false)

  const layouts = data?.layouts ?? []

  useEffect(() => {
    const ls = data?.layouts ?? []
    if (!ls.length) { setActiveId(null); return }
    if (!activeId || !ls.some((l) => l.id === activeId)) setActiveId(ls[0].id)
  }, [data, activeId])

  const active = layouts.find((l) => l.id === activeId)

  async function createLayout(values) {
    setBusy(true)
    try {
      const r = await api.post(`/events/${event.id}/venue-layouts`, values)
      setCreating(false)
      await reload()
      setActiveId(r.data.data.layout.id)
    } finally {
      setBusy(false)
    }
  }

  async function duplicate() {
    setBusy(true)
    try {
      const r = await api.post(`/events/${event.id}/venue-layouts/${activeId}/duplicate`)
      await reload()
      setActiveId(r.data.data.layout.id)
    } finally {
      setBusy(false)
    }
  }

  async function rename(values) {
    setBusy(true)
    try {
      await api.put(`/events/${event.id}/venue-layouts/${activeId}`, { layout_name: values.layout_name })
      setRenaming(false)
      reload()
    } finally {
      setBusy(false)
    }
  }

  async function remove() {
    setBusy(true)
    try {
      await api.delete(`/events/${event.id}/venue-layouts/${activeId}`)
      setRemoving(false)
      setActiveId(null)
      reload()
    } finally {
      setBusy(false)
    }
  }

  return (
    <div className="space-y-4">
      <div className="flex flex-wrap items-center justify-between gap-3">
        <div>
          <h2 className="text-lg font-extrabold text-ink">Venue Designer</h2>
          <p className="text-sm text-muted">Design the physical layout of the venue.</p>
        </div>
        <Button size="sm" onClick={() => setCreating(true)}><Icon name="Plus" className="size-4" /> New layout</Button>
      </div>

      <LoadState loading={loading} error={error} onRetry={reload}>
        {data && (
          layouts.length ? (
            <>
              {/* Version chips + actions */}
              <div className="flex flex-wrap items-center gap-2">
                {layouts.map((l) => (
                  <button key={l.id} type="button" onClick={() => setActiveId(l.id)}
                    className={cn('rounded-btn border px-3 py-1.5 text-sm font-semibold transition-colors',
                      l.id === activeId ? 'border-navy-600 bg-navy-50 text-navy-800' : 'border-line bg-surface text-muted hover:text-ink')}>
                    {l.layout_name}
                    <span className="ml-1.5 text-xs font-normal text-muted">v{l.version}</span>
                  </button>
                ))}
                {active && (
                  <div className="ml-auto flex items-center gap-1">
                    <Button variant="ghost" size="sm" onClick={() => setRenaming(true)}><Icon name="PenLine" className="size-4" /> Rename</Button>
                    <Button variant="ghost" size="sm" onClick={duplicate} loading={busy}><Icon name="FileText" className="size-4" /> Duplicate</Button>
                    <Button variant="ghost" size="sm" onClick={() => setRemoving(true)} className="text-danger"><Icon name="Trash2" className="size-4" /> Delete</Button>
                  </div>
                )}
              </div>

              {activeId && (
                <LayoutEditor key={activeId} eventId={event.id} layoutId={activeId} guests={event.guests ?? []} onSaved={reload} />
              )}
            </>
          ) : (
            <EmptyState icon="LayoutGrid" title="No layouts yet"
              description="Create a layout to start designing the venue floor plan."
              action={<Button size="sm" onClick={() => setCreating(true)}><Icon name="Plus" className="size-4" /> New layout</Button>} />
          )
        )}
      </LoadState>

      {/* New layout */}
      <Drawer open={creating} onClose={() => setCreating(false)} title="New layout"
        description="Name the layout — every layout starts at the same floor size, which you can adjust later in the designer.">
        <NewLayoutForm onSubmit={createLayout} busy={busy} />
      </Drawer>

      {/* Rename */}
      <RenameModal open={renaming} current={active?.layout_name} onClose={() => setRenaming(false)} onSubmit={rename} busy={busy} />

      <ConfirmDialog open={removing} onClose={() => setRemoving(false)} onConfirm={remove}
        title="Delete layout?" description={active?.layout_name} confirmLabel="Delete" loading={busy} />
    </div>
  )
}

function LayoutEditor({ eventId, layoutId, guests, onSaved }) {
  const { data, loading, error, reload } = useResource(`/events/${eventId}/venue-layouts/${layoutId}`)
  return (
    <LoadState loading={loading} error={error} onRetry={reload}>
      {data?.layout && <Designer layout={data.layout} eventId={eventId} guests={guests} onSaved={onSaved} />}
    </LoadState>
  )
}

// Every new layout starts at this fixed floor size (metres); the planner resizes
// it inside the designer if needed. Width/length aren't asked for on creation.
const DEFAULT_LAYOUT_SIZE = { width: 20, height: 15 }

function NewLayoutForm({ onSubmit, busy }) {
  const { register, handleSubmit, formState: { errors } } = useForm({
    defaultValues: { layout_name: '', venue_name: '', max_capacity: '' },
  })
  const submit = handleSubmit((values) => {
    const merged = { ...values, ...DEFAULT_LAYOUT_SIZE }
    const payload = Object.fromEntries(Object.entries(merged).filter(([, v]) => v !== ''))
    onSubmit(payload)
  })
  return (
    <form onSubmit={submit} className="space-y-4">
      <Field label="Layout name" error={errors.layout_name?.message} {...register('layout_name', { required: 'A name is required' })} />
      <Field label="Venue name" {...register('venue_name')} />
      <Field type="number" min="0" label="Max capacity" {...register('max_capacity')} />
      <div className="flex justify-end pt-2"><Button type="submit" loading={busy}>Create layout</Button></div>
    </form>
  )
}

function RenameModal({ open, current, onClose, onSubmit, busy }) {
  const { register, handleSubmit, reset, formState: { errors } } = useForm({ defaultValues: { layout_name: current ?? '' } })
  useEffect(() => { reset({ layout_name: current ?? '' }) }, [current, reset])
  return (
    <Modal open={open} onClose={onClose} title="Rename layout"
      footer={<><Button variant="ghost" size="sm" onClick={onClose}>Cancel</Button><Button size="sm" onClick={handleSubmit(onSubmit)} loading={busy}>Save</Button></>}>
      <form onSubmit={handleSubmit(onSubmit)}>
        <Field label="Layout name" error={errors.layout_name?.message} {...register('layout_name', { required: 'A name is required' })} />
      </form>
    </Modal>
  )
}
