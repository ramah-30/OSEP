import { useState } from 'react'
import PageHeader from '../../../components/ui/PageHeader'
import Card from '../../../components/ui/Card'
import Icon from '../../../components/ui/Icon'
import Badge from '../../../components/ui/Badge'
import Button from '../../../components/ui/Button'
import Tabs from '../../../components/ui/Tabs'
import Drawer from '../../../components/ui/Drawer'
import { Field } from '../../../components/ui/Field'
import Textarea from '../../../components/ui/Textarea'
import EmptyState from '../../../components/ui/EmptyState'
import LoadState from '../../../components/dashboard/LoadState'
import { api } from '../../../lib/api'
import { useResource } from '../../../lib/useResource'
import { formatCurrency } from '../../../lib/format'

export default function Services() {
  const [tab, setTab] = useState('services')

  return (
    <div className="space-y-6">
      <PageHeader title="Services & Packages" description="Manage the services you offer and the priced packages planners can request." />
      <Tabs value={tab} onChange={setTab} tabs={[{ value: 'services', label: 'Services' }, { value: 'packages', label: 'Packages' }]} />
      {tab === 'services' ? <ServicesSection /> : <PackagesSection />}
    </div>
  )
}

function ServicesSection() {
  const { data, loading, error, reload } = useResource('/marketplace/vendor/services')
  const [editing, setEditing] = useState(null)
  const [form, setForm] = useState({ name: '', description: '', is_active: true })

  const open = (svc) => {
    setForm(svc ? { name: svc.name, description: svc.description ?? '', is_active: svc.is_active } : { name: '', description: '', is_active: true })
    setEditing(svc ?? {})
  }

  const save = async () => {
    if (editing.id) await api.put(`/marketplace/vendor/services/${editing.id}`, form)
    else await api.post('/marketplace/vendor/services', form)
    setEditing(null)
    reload()
  }

  const remove = async (id) => {
    await api.delete(`/marketplace/vendor/services/${id}`)
    reload()
  }

  return (
    <div className="space-y-4">
      <div className="flex justify-end"><Button size="sm" onClick={() => open(null)}><Icon name="Plus" className="size-4" /> Add service</Button></div>
      <LoadState loading={loading} error={error} onRetry={reload}>
        {data && (data.services.length ? (
          <div className="grid gap-4 md:grid-cols-2">
            {data.services.map((s) => (
              <Card key={s.id} className="flex items-start justify-between gap-3 p-5">
                <div>
                  <p className="font-bold text-ink">{s.name} {!s.is_active && <Badge tone="muted">Hidden</Badge>}</p>
                  {s.description && <p className="mt-1 text-sm text-muted">{s.description}</p>}
                </div>
                <div className="flex shrink-0 gap-1">
                  <button onClick={() => open(s)} className="grid size-8 place-items-center rounded-btn text-muted hover:bg-canvas hover:text-ink"><Icon name="Pencil" className="size-4" /></button>
                  <button onClick={() => remove(s.id)} className="grid size-8 place-items-center rounded-btn text-muted hover:bg-danger-soft hover:text-danger"><Icon name="Trash2" className="size-4" /></button>
                </div>
              </Card>
            ))}
          </div>
        ) : (
          <EmptyState icon="Package" title="No services yet" description="Add the services your business offers." action={<Button onClick={() => open(null)}>Add service</Button>} />
        ))}
      </LoadState>

      <Drawer
        open={!!editing}
        onClose={() => setEditing(null)}
        title={editing?.id ? 'Edit service' : 'Add service'}
        footer={<div className="flex justify-end gap-3"><Button variant="ghost" onClick={() => setEditing(null)}>Cancel</Button><Button onClick={save}>Save</Button></div>}
      >
        <div className="space-y-4">
          <Field label="Name" value={form.name} onChange={(e) => setForm({ ...form, name: e.target.value })} />
          <Textarea label="Description" value={form.description} onChange={(e) => setForm({ ...form, description: e.target.value })} />
          <label className="flex items-center gap-2 text-sm font-medium text-ink">
            <input type="checkbox" checked={form.is_active} onChange={(e) => setForm({ ...form, is_active: e.target.checked })} className="accent-navy-800" /> Visible on storefront
          </label>
        </div>
      </Drawer>
    </div>
  )
}

function PackagesSection() {
  const { data, loading, error, reload } = useResource('/marketplace/vendor/packages')
  const [editing, setEditing] = useState(null)
  const [form, setForm] = useState({ name: '', description: '', price: '', price_unit: 'per event', inclusions: '', terms: '', is_active: true })

  const open = (pkg) => {
    setForm(pkg
      ? { name: pkg.name, description: pkg.description ?? '', price: pkg.price ?? '', price_unit: pkg.price_unit ?? 'per event', inclusions: (pkg.inclusions ?? []).join('\n'), terms: pkg.terms ?? '', is_active: pkg.is_active }
      : { name: '', description: '', price: '', price_unit: 'per event', inclusions: '', terms: '', is_active: true })
    setEditing(pkg ?? {})
  }

  const save = async () => {
    const payload = {
      ...form,
      price: form.price === '' ? null : form.price,
      inclusions: form.inclusions.split('\n').map((s) => s.trim()).filter(Boolean),
    }
    if (editing.id) await api.put(`/marketplace/vendor/packages/${editing.id}`, payload)
    else await api.post('/marketplace/vendor/packages', payload)
    setEditing(null)
    reload()
  }

  const remove = async (id) => {
    await api.delete(`/marketplace/vendor/packages/${id}`)
    reload()
  }

  return (
    <div className="space-y-4">
      <div className="flex justify-end"><Button size="sm" onClick={() => open(null)}><Icon name="Plus" className="size-4" /> Add package</Button></div>
      <LoadState loading={loading} error={error} onRetry={reload}>
        {data && (data.packages.length ? (
          <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            {data.packages.map((p) => (
              <Card key={p.id} className="flex flex-col p-5">
                <div className="flex items-start justify-between">
                  <p className="font-bold text-ink">{p.name}</p>
                  <div className="flex gap-1">
                    <button onClick={() => open(p)} className="grid size-8 place-items-center rounded-btn text-muted hover:bg-canvas hover:text-ink"><Icon name="Pencil" className="size-4" /></button>
                    <button onClick={() => remove(p.id)} className="grid size-8 place-items-center rounded-btn text-muted hover:bg-danger-soft hover:text-danger"><Icon name="Trash2" className="size-4" /></button>
                  </div>
                </div>
                <p className="mt-1 text-xl font-extrabold text-navy-800">{p.price != null ? formatCurrency(p.price) : 'On request'}</p>
                <p className="text-xs text-muted">{p.price_unit}</p>
                <ul className="mt-2 flex-1 space-y-1 text-sm">
                  {(p.inclusions ?? []).map((inc, i) => <li key={i} className="flex items-start gap-1.5 text-ink"><Icon name="Check" className="mt-0.5 size-3.5 text-emerald-500" />{inc}</li>)}
                </ul>
              </Card>
            ))}
          </div>
        ) : (
          <EmptyState icon="Package" title="No packages yet" description="Create priced packages planners can request." action={<Button onClick={() => open(null)}>Add package</Button>} />
        ))}
      </LoadState>

      <Drawer
        open={!!editing}
        onClose={() => setEditing(null)}
        title={editing?.id ? 'Edit package' : 'Add package'}
        footer={<div className="flex justify-end gap-3"><Button variant="ghost" onClick={() => setEditing(null)}>Cancel</Button><Button onClick={save}>Save</Button></div>}
      >
        <div className="space-y-4">
          <Field label="Name" value={form.name} onChange={(e) => setForm({ ...form, name: e.target.value })} />
          <Textarea label="Description" value={form.description} onChange={(e) => setForm({ ...form, description: e.target.value })} rows={3} />
          <div className="grid grid-cols-2 gap-3">
            <Field label="Price (TZS)" type="number" value={form.price} onChange={(e) => setForm({ ...form, price: e.target.value })} />
            <Field label="Price unit" value={form.price_unit} onChange={(e) => setForm({ ...form, price_unit: e.target.value })} />
          </div>
          <Textarea label="Inclusions (one per line)" value={form.inclusions} onChange={(e) => setForm({ ...form, inclusions: e.target.value })} rows={4} />
          <Textarea label="Terms" value={form.terms} onChange={(e) => setForm({ ...form, terms: e.target.value })} rows={2} />
          <label className="flex items-center gap-2 text-sm font-medium text-ink">
            <input type="checkbox" checked={form.is_active} onChange={(e) => setForm({ ...form, is_active: e.target.checked })} className="accent-navy-800" /> Visible on storefront
          </label>
        </div>
      </Drawer>
    </div>
  )
}
