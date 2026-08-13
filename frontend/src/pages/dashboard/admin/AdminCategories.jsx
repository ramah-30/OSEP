import { useState } from 'react'
import PageHeader from '../../../components/ui/PageHeader'
import Card from '../../../components/ui/Card'
import Icon from '../../../components/ui/Icon'
import Badge from '../../../components/ui/Badge'
import Button from '../../../components/ui/Button'
import Modal from '../../../components/ui/Modal'
import { Field } from '../../../components/ui/Field'
import Textarea from '../../../components/ui/Textarea'
import LoadState from '../../../components/dashboard/LoadState'
import { api, parseApiError } from '../../../lib/api'
import { useResource } from '../../../lib/useResource'

export default function AdminCategories() {
  const { data, loading, error, reload } = useResource('/admin/marketplace/categories')
  const [editing, setEditing] = useState(null)
  const [form, setForm] = useState({ name: '', icon: '', description: '' })
  const [err, setErr] = useState(null)

  const open = (c) => {
    setForm(c ? { name: c.name, icon: c.icon ?? '', description: c.description ?? '' } : { name: '', icon: '', description: '' })
    setErr(null)
    setEditing(c ?? {})
  }

  const save = async () => {
    setErr(null)
    try {
      if (editing.id) await api.put(`/admin/marketplace/categories/${editing.id}`, form)
      else await api.post('/admin/marketplace/categories', form)
      setEditing(null)
      reload()
    } catch (e) {
      setErr(parseApiError(e).message)
    }
  }

  const remove = async (id) => {
    try {
      await api.delete(`/admin/marketplace/categories/${id}`)
      reload()
    } catch (e) {
      alert(parseApiError(e).message)
    }
  }

  return (
    <div className="space-y-6">
      <PageHeader title="Categories" description="Manage the vendor taxonomy. Add custom categories without code changes." actions={<Button onClick={() => open(null)}><Icon name="Plus" className="size-4" /> Add category</Button>} />

      <LoadState loading={loading} error={error} onRetry={reload}>
        {data && (
          <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
            {data.categories.map((c) => (
              <Card key={c.id} className="flex items-center gap-3 p-4">
                <span className="grid size-10 shrink-0 place-items-center rounded-lg bg-navy-50 text-navy-700"><Icon name={c.icon ?? 'Store'} className="size-5" /></span>
                <div className="min-w-0 flex-1">
                  <p className="flex items-center gap-2 truncate font-bold text-ink">{c.name} {c.is_custom && <Badge tone="purple">Custom</Badge>}</p>
                  <p className="text-xs text-muted">{c.vendors_count ?? 0} vendors</p>
                </div>
                <div className="flex gap-1">
                  <button onClick={() => open(c)} className="grid size-8 place-items-center rounded-btn text-muted hover:bg-canvas hover:text-ink"><Icon name="Pencil" className="size-4" /></button>
                  <button onClick={() => remove(c.id)} className="grid size-8 place-items-center rounded-btn text-muted hover:bg-danger-soft hover:text-danger"><Icon name="Trash2" className="size-4" /></button>
                </div>
              </Card>
            ))}
          </div>
        )}
      </LoadState>

      <Modal
        open={!!editing}
        onClose={() => setEditing(null)}
        title={editing?.id ? 'Edit category' : 'Add category'}
        footer={<div className="flex justify-end gap-3"><Button variant="ghost" onClick={() => setEditing(null)}>Cancel</Button><Button onClick={save}>Save</Button></div>}
      >
        <div className="space-y-4">
          {err && <p className="rounded-btn bg-danger-soft px-3 py-2 text-sm text-danger">{err}</p>}
          <Field label="Name" value={form.name} onChange={(e) => setForm({ ...form, name: e.target.value })} />
          <Field label="Icon (lucide name)" value={form.icon} onChange={(e) => setForm({ ...form, icon: e.target.value })} placeholder="e.g. Camera" />
          <Textarea label="Description" value={form.description} onChange={(e) => setForm({ ...form, description: e.target.value })} rows={2} />
        </div>
      </Modal>
    </div>
  )
}
