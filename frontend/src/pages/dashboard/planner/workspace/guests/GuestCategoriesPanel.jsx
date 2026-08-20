import { useState } from 'react'
import { useForm } from 'react-hook-form'
import Button from '../../../../../components/ui/Button'
import Card from '../../../../../components/ui/Card'
import Badge from '../../../../../components/ui/Badge'
import Icon from '../../../../../components/ui/Icon'
import Modal from '../../../../../components/ui/Modal'
import ConfirmDialog from '../../../../../components/ui/ConfirmDialog'
import { Field, SelectField } from '../../../../../components/ui/Field'
import LoadState from '../../../../../components/dashboard/LoadState'
import { useResource } from '../../../../../lib/useResource'
import { api } from '../../../../../lib/api'
import { CATEGORY_PRIORITY_OPTIONS } from '../../../../../lib/guestConstants'

export default function GuestCategoriesPanel() {
  const { data, loading, error, reload } = useResource('/guest-categories')
  const [modal, setModal] = useState({ open: false, editing: null })
  const [removing, setRemoving] = useState(null)
  const [busy, setBusy] = useState(false)
  const categories = data?.categories ?? []

  async function remove() {
    setBusy(true)
    try { await api.delete(`/guest-categories/${removing.id}`); setRemoving(null); reload() }
    finally { setBusy(false) }
  }

  return (
    <div className="space-y-4">
      <div className="flex items-center justify-between">
        <div>
          <h2 className="text-lg font-extrabold text-ink">Guest Categories</h2>
          <p className="text-sm text-muted">Colour-code and prioritise your guest groups.</p>
        </div>
        <Button size="sm" onClick={() => setModal({ open: true, editing: null })}><Icon name="Plus" className="size-4" /> New category</Button>
      </div>

      <LoadState loading={loading} error={error} onRetry={reload}>
        <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
          {categories.map((c) => (
            <Card key={c.id} className="flex items-start gap-3 p-4">
              <span className="mt-1 size-4 shrink-0 rounded-full" style={{ background: c.color }} />
              <div className="min-w-0 flex-1">
                <div className="flex items-center gap-2">
                  <p className="font-bold text-ink">{c.name}</p>
                  {c.is_default && <Badge tone="muted">Default</Badge>}
                </div>
                <p className="text-xs text-muted">Priority {c.priority} · {c.default_seating_area ?? 'No seating pref'}</p>
              </div>
              {c.is_owned && (
                <div className="flex gap-1">
                  <button type="button" onClick={() => setModal({ open: true, editing: c })} className="grid size-7 place-items-center rounded-btn text-muted hover:bg-canvas hover:text-ink"><Icon name="PenLine" className="size-4" /></button>
                  <button type="button" onClick={() => setRemoving(c)} className="grid size-7 place-items-center rounded-btn text-muted hover:bg-danger-soft hover:text-danger"><Icon name="Trash2" className="size-4" /></button>
                </div>
              )}
            </Card>
          ))}
        </div>
      </LoadState>

      <CategoryModal key={modal.editing?.id ?? 'new'} open={modal.open} editing={modal.editing}
        onClose={() => setModal({ open: false, editing: null })}
        onSaved={() => { setModal({ open: false, editing: null }); reload() }} />

      <ConfirmDialog open={!!removing} onClose={() => setRemoving(null)} onConfirm={remove}
        title="Delete category?" description={removing?.name} confirmLabel="Delete" loading={busy} />
    </div>
  )
}

function CategoryModal({ open, editing, onClose, onSaved }) {
  const { register, handleSubmit, formState: { isSubmitting } } = useForm({
    defaultValues: editing
      ? { name: editing.name, color: editing.color, priority: editing.priority, default_seating_area: editing.default_seating_area ?? '' }
      : { name: '', color: '#2947c8', priority: 3, default_seating_area: '' },
  })

  const submit = handleSubmit(async (values) => {
    if (editing) await api.put(`/guest-categories/${editing.id}`, values)
    else await api.post('/guest-categories', values)
    onSaved()
  })

  return (
    <Modal open={open} onClose={onClose} title={editing ? 'Edit category' : 'New category'}
      footer={<><Button variant="ghost" size="sm" onClick={onClose}>Cancel</Button><Button size="sm" onClick={submit} loading={isSubmitting}>Save</Button></>}>
      <form onSubmit={submit} className="space-y-4">
        <Field label="Name" {...register('name', { required: true })} />
        <div className="grid grid-cols-2 gap-4">
          <div>
            <label className="mb-1.5 block text-sm font-semibold text-ink">Colour</label>
            <input type="color" {...register('color')} className="h-12 w-full cursor-pointer rounded-btn border border-line bg-surface" />
          </div>
          <SelectField label="Priority" options={CATEGORY_PRIORITY_OPTIONS} {...register('priority')} />
        </div>
        <Field label="Default seating area" {...register('default_seating_area')} placeholder="e.g. Front rows" />
      </form>
    </Modal>
  )
}
