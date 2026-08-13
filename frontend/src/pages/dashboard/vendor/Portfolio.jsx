import { useState } from 'react'
import PageHeader from '../../../components/ui/PageHeader'
import Card from '../../../components/ui/Card'
import Icon from '../../../components/ui/Icon'
import Badge from '../../../components/ui/Badge'
import Button from '../../../components/ui/Button'
import Drawer from '../../../components/ui/Drawer'
import { Field } from '../../../components/ui/Field'
import Textarea from '../../../components/ui/Textarea'
import EmptyState from '../../../components/ui/EmptyState'
import LoadState from '../../../components/dashboard/LoadState'
import { api } from '../../../lib/api'
import { useResource } from '../../../lib/useResource'
import { formatDate } from '../../../lib/format'

const BLANK = { title: '', description: '', event_type: '', event_date: '', cover_url: '', client_feedback: '', is_case_study: false }

export default function Portfolio() {
  const { data, loading, error, reload } = useResource('/marketplace/vendor/portfolio')
  const [editing, setEditing] = useState(null)
  const [form, setForm] = useState(BLANK)

  const open = (item) => {
    setForm(item ? { ...BLANK, ...item, event_date: item.event_date ?? '' } : BLANK)
    setEditing(item ?? {})
  }

  const save = async () => {
    const payload = { ...form, event_date: form.event_date || null }
    if (editing.id) await api.put(`/marketplace/vendor/portfolio/${editing.id}`, payload)
    else await api.post('/marketplace/vendor/portfolio', payload)
    setEditing(null)
    reload()
  }

  const remove = async (id) => {
    await api.delete(`/marketplace/vendor/portfolio/${id}`)
    reload()
  }

  return (
    <div className="space-y-6">
      <PageHeader title="Portfolio" description="Showcase your best work and case studies." actions={<Button onClick={() => open(null)}><Icon name="Plus" className="size-4" /> Add item</Button>} />

      <LoadState loading={loading} error={error} onRetry={reload}>
        {data && (data.portfolios.length ? (
          <div className="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
            {data.portfolios.map((item) => (
              <Card key={item.id} className="overflow-hidden">
                <div className="relative h-40 w-full bg-canvas">
                  {item.cover_url && <img src={item.cover_url} alt="" className="size-full object-cover" />}
                  <div className="absolute right-2 top-2 flex gap-1">
                    <button onClick={() => open(item)} className="grid size-8 place-items-center rounded-full bg-white/90 text-navy-800 shadow hover:bg-white"><Icon name="Pencil" className="size-4" /></button>
                    <button onClick={() => remove(item.id)} className="grid size-8 place-items-center rounded-full bg-white/90 text-danger shadow hover:bg-white"><Icon name="Trash2" className="size-4" /></button>
                  </div>
                </div>
                <div className="p-4">
                  <div className="flex items-center justify-between">
                    <p className="font-bold text-ink">{item.title}</p>
                    {item.is_case_study && <Badge tone="purple">Case study</Badge>}
                  </div>
                  <p className="text-xs text-muted">{item.event_type} · {formatDate(item.event_date)}</p>
                </div>
              </Card>
            ))}
          </div>
        ) : (
          <EmptyState icon="Image" title="No portfolio items yet" description="Add photos and stories from your best events." action={<Button onClick={() => open(null)}>Add item</Button>} />
        ))}
      </LoadState>

      <Drawer
        open={!!editing}
        onClose={() => setEditing(null)}
        title={editing?.id ? 'Edit portfolio item' : 'Add portfolio item'}
        footer={<div className="flex justify-end gap-3"><Button variant="ghost" onClick={() => setEditing(null)}>Cancel</Button><Button onClick={save}>Save</Button></div>}
      >
        <div className="space-y-4">
          <Field label="Title" value={form.title} onChange={(e) => setForm({ ...form, title: e.target.value })} />
          <div className="grid grid-cols-2 gap-3">
            <Field label="Event type" value={form.event_type} onChange={(e) => setForm({ ...form, event_type: e.target.value })} />
            <Field label="Event date" type="date" value={form.event_date} onChange={(e) => setForm({ ...form, event_date: e.target.value })} />
          </div>
          <Field label="Cover image URL" value={form.cover_url} onChange={(e) => setForm({ ...form, cover_url: e.target.value })} placeholder="https://…" />
          <Textarea label="Description" value={form.description} onChange={(e) => setForm({ ...form, description: e.target.value })} rows={3} />
          <Textarea label="Client feedback" value={form.client_feedback} onChange={(e) => setForm({ ...form, client_feedback: e.target.value })} rows={2} />
          <label className="flex items-center gap-2 text-sm font-medium text-ink">
            <input type="checkbox" checked={form.is_case_study} onChange={(e) => setForm({ ...form, is_case_study: e.target.checked })} className="accent-navy-800" /> Mark as case study
          </label>
        </div>
      </Drawer>
    </div>
  )
}
