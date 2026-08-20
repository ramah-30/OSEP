import { useState } from 'react'
import PageHeader from '../../../components/ui/PageHeader'
import Card from '../../../components/ui/Card'
import Icon from '../../../components/ui/Icon'
import Badge from '../../../components/ui/Badge'
import Button from '../../../components/ui/Button'
import Drawer from '../../../components/ui/Drawer'
import { Field, SelectField } from '../../../components/ui/Field'
import Textarea from '../../../components/ui/Textarea'
import EmptyState from '../../../components/ui/EmptyState'
import LoadState from '../../../components/dashboard/LoadState'
import VerificationBadge from '../../../components/marketplace/VerificationBadge'
import { api } from '../../../lib/api'
import { useResource } from '../../../lib/useResource'
import { formatCurrency } from '../../../lib/format'
import { SETTING_LABELS } from '../../../lib/marketplace'

const BLANK = {
  name: '', venue_type: '', setting: 'indoor', capacity: '', min_capacity: '', price: '', price_unit: 'per day',
  location: '', address: '', description: '', cover_image_url: '', parking_available: false, is_published: true,
  facilities: '', included_equipment: '', accessibility: '', images: '',
}

const toList = (s) => (s ?? '').split('\n').map((x) => x.trim()).filter(Boolean)
const fromList = (a) => (a ?? []).join('\n')

export default function Venues() {
  const { data, loading, error, reload } = useResource('/marketplace/vendor/venues')
  const [editing, setEditing] = useState(null)
  const [form, setForm] = useState(BLANK)
  const [saving, setSaving] = useState(false)

  const open = (v) => {
    setForm(v ? {
      ...BLANK, ...v,
      capacity: v.capacity ?? '', min_capacity: v.min_capacity ?? '', price: v.price ?? '',
      facilities: fromList(v.facilities), included_equipment: fromList(v.included_equipment),
      accessibility: fromList(v.accessibility), images: fromList((v.images ?? []).map((i) => i.url)),
    } : BLANK)
    setEditing(v ?? {})
  }

  const save = async () => {
    setSaving(true)
    try {
      const payload = {
        name: form.name, venue_type: form.venue_type, setting: form.setting, description: form.description,
        capacity: form.capacity === '' ? null : Number(form.capacity),
        min_capacity: form.min_capacity === '' ? null : Number(form.min_capacity),
        price: form.price === '' ? null : Number(form.price), price_unit: form.price_unit,
        location: form.location, address: form.address, cover_image_url: form.cover_image_url,
        parking_available: form.parking_available, is_published: form.is_published,
        facilities: toList(form.facilities), included_equipment: toList(form.included_equipment),
        accessibility: toList(form.accessibility),
      }
      let venueId = editing.id
      if (venueId) await api.put(`/marketplace/vendor/venues/${venueId}`, payload)
      else venueId = (await api.post('/marketplace/vendor/venues', payload)).data.data.venue.id
      await api.put(`/marketplace/vendor/venues/${venueId}/images`, { images: toList(form.images).map((url) => ({ url })) })
      setEditing(null)
      reload()
    } finally {
      setSaving(false)
    }
  }

  const remove = async (id) => { await api.delete(`/marketplace/vendor/venues/${id}`); reload() }

  const set = (patch) => setForm((f) => ({ ...f, ...patch }))

  return (
    <div className="space-y-6">
      <PageHeader title="My Venues" description="List and manage the venues you rent out on the marketplace." actions={<Button onClick={() => open(null)}><Icon name="Plus" className="size-4" /> Add venue</Button>} />

      <LoadState loading={loading} error={error} onRetry={reload}>
        {data && (data.venues.length ? (
          <div className="grid gap-5 sm:grid-cols-2 xl:grid-cols-3">
            {data.venues.map((v) => (
              <Card key={v.id} className="overflow-hidden">
                <div className="relative h-36 w-full bg-canvas">
                  {v.cover_image_url && <img src={v.cover_image_url} alt="" className="size-full object-cover" />}
                  {!v.is_published && <span className="absolute left-2 top-2"><Badge tone="muted">Unpublished</Badge></span>}
                </div>
                <div className="p-4">
                  <div className="flex items-center justify-between">
                    <p className="font-bold text-ink">{v.name}</p>
                    <div className="flex gap-1">
                      <button onClick={() => open(v)} className="grid size-8 place-items-center rounded-btn text-muted hover:bg-canvas hover:text-ink"><Icon name="Pencil" className="size-4" /></button>
                      <button onClick={() => remove(v.id)} className="grid size-8 place-items-center rounded-btn text-muted hover:bg-danger-soft hover:text-danger"><Icon name="Trash2" className="size-4" /></button>
                    </div>
                  </div>
                  <p className="text-xs text-muted">{v.venue_type} · {SETTING_LABELS[v.setting]}</p>
                  <div className="mt-2 flex items-center justify-between">
                    <span className="text-sm font-semibold text-navy-800">{v.price != null ? formatCurrency(v.price) : 'On request'}</span>
                    <VerificationBadge level={v.verification_level} />
                  </div>
                </div>
              </Card>
            ))}
          </div>
        ) : (
          <EmptyState icon="Building" title="No venues listed yet" description="Add your first venue to start receiving booking requests." action={<Button onClick={() => open(null)}>Add venue</Button>} />
        ))}
      </LoadState>

      <Drawer
        open={!!editing}
        onClose={() => setEditing(null)}
        title={editing?.id ? 'Edit venue' : 'Add venue'}
        footer={<div className="flex justify-end gap-3"><Button variant="ghost" onClick={() => setEditing(null)}>Cancel</Button><Button onClick={save} loading={saving}>Save</Button></div>}
      >
        <div className="space-y-4">
          <Field label="Name" value={form.name} onChange={(e) => set({ name: e.target.value })} />
          <div className="grid grid-cols-2 gap-3">
            <Field label="Type" value={form.venue_type} onChange={(e) => set({ venue_type: e.target.value })} placeholder="Ballroom" />
            <SelectField label="Setting" value={form.setting} onChange={(e) => set({ setting: e.target.value })}>
              <option value="indoor">Indoor</option>
              <option value="outdoor">Outdoor</option>
              <option value="both">Indoor & Outdoor</option>
            </SelectField>
          </div>
          <div className="grid grid-cols-2 gap-3">
            <Field label="Capacity" type="number" value={form.capacity} onChange={(e) => set({ capacity: e.target.value })} />
            <Field label="Min capacity" type="number" value={form.min_capacity} onChange={(e) => set({ min_capacity: e.target.value })} />
          </div>
          <div className="grid grid-cols-2 gap-3">
            <Field label="Price (TZS)" type="number" value={form.price} onChange={(e) => set({ price: e.target.value })} />
            <Field label="Price unit" value={form.price_unit} onChange={(e) => set({ price_unit: e.target.value })} />
          </div>
          <Field label="Location (city)" value={form.location} onChange={(e) => set({ location: e.target.value })} />
          <Field label="Address" value={form.address} onChange={(e) => set({ address: e.target.value })} />
          <Textarea label="Description" value={form.description} onChange={(e) => set({ description: e.target.value })} rows={3} />
          <Field label="Cover image URL" value={form.cover_image_url} onChange={(e) => set({ cover_image_url: e.target.value })} />
          <Textarea label="Gallery image URLs (one per line)" value={form.images} onChange={(e) => set({ images: e.target.value })} rows={3} />
          <Textarea label="Facilities (one per line)" value={form.facilities} onChange={(e) => set({ facilities: e.target.value })} rows={2} />
          <Textarea label="Included equipment (one per line)" value={form.included_equipment} onChange={(e) => set({ included_equipment: e.target.value })} rows={2} />
          <Textarea label="Accessibility (one per line)" value={form.accessibility} onChange={(e) => set({ accessibility: e.target.value })} rows={2} />
          <label className="flex items-center gap-2 text-sm font-medium text-ink">
            <input type="checkbox" checked={form.parking_available} onChange={(e) => set({ parking_available: e.target.checked })} className="accent-navy-800" /> Parking available
          </label>
          <label className="flex items-center gap-2 text-sm font-medium text-ink">
            <input type="checkbox" checked={form.is_published} onChange={(e) => set({ is_published: e.target.checked })} className="accent-navy-800" /> Published (visible to planners)
          </label>
        </div>
      </Drawer>
    </div>
  )
}
