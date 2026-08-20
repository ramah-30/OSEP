import { useRef, useState } from 'react'
import { useOutletContext } from 'react-router-dom'
import Button from '../../../../components/ui/Button'
import Icon from '../../../../components/ui/Icon'
import Badge from '../../../../components/ui/Badge'
import Card from '../../../../components/ui/Card'
import Drawer from '../../../../components/ui/Drawer'
import ConfirmDialog from '../../../../components/ui/ConfirmDialog'
import EmptyState from '../../../../components/ui/EmptyState'
import { Field, SelectField } from '../../../../components/ui/Field'
import { api, parseApiError } from '../../../../lib/api'
import { formatDate } from '../../../../lib/format'
import { DOCUMENT_CATEGORY_OPTIONS } from '../../../../lib/eventConstants'

function humanSize(bytes) {
  if (!bytes) return '—'
  const units = ['B', 'KB', 'MB', 'GB']
  let n = bytes
  let i = 0
  while (n >= 1024 && i < units.length - 1) { n /= 1024; i += 1 }
  return `${n.toFixed(n < 10 && i > 0 ? 1 : 0)} ${units[i]}`
}

export default function Documents() {
  const { event, reload } = useOutletContext()
  const documents = event.documents ?? []
  const [uploading, setUploading] = useState(false)
  const [removing, setRemoving] = useState(null)
  const [busy, setBusy] = useState(false)

  async function remove() {
    setBusy(true)
    try {
      await api.delete(`/events/${event.id}/documents/${removing.id}`)
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
          <h2 className="text-lg font-extrabold text-ink">Documents</h2>
          <p className="text-sm text-muted">Contracts, quotations, floor plans and more.</p>
        </div>
        <Button size="sm" onClick={() => setUploading(true)}><Icon name="Upload" className="size-4" /> Upload</Button>
      </div>

      {documents.length ? (
        <div className="grid gap-3 sm:grid-cols-2">
          {documents.map((d) => (
            <Card key={d.id} className="flex items-center gap-4 p-4">
              <span className="grid size-11 shrink-0 place-items-center rounded-xl bg-navy-50 text-navy-700"><Icon name="FileText" className="size-5" /></span>
              <div className="min-w-0 flex-1">
                <p className="truncate font-semibold text-ink">{d.name}</p>
                <p className="mt-0.5 flex items-center gap-2 text-xs text-muted">
                  <Badge tone="muted">{d.category}</Badge>
                  <span>{humanSize(d.size)}</span>
                  {d.version > 1 && <span>v{d.version}</span>}
                  <span>· {formatDate(d.created_at)}</span>
                </p>
              </div>
              <div className="flex shrink-0 gap-1">
                <a href={d.url} target="_blank" rel="noreferrer" className="grid size-9 place-items-center rounded-btn text-muted hover:bg-canvas hover:text-ink"><Icon name="Download" className="size-4" /></a>
                <button type="button" onClick={() => setRemoving(d)} className="grid size-9 place-items-center rounded-btn text-muted hover:bg-danger-soft hover:text-danger"><Icon name="Trash2" className="size-4" /></button>
              </div>
            </Card>
          ))}
        </div>
      ) : (
        <EmptyState icon="FileText" title="No documents yet" description="Upload contracts, quotations and plans to keep everything in one place."
          action={<Button size="sm" onClick={() => setUploading(true)}><Icon name="Upload" className="size-4" /> Upload</Button>} />
      )}

      <UploadDrawer open={uploading} eventId={event.id}
        onClose={() => setUploading(false)}
        onSaved={() => { setUploading(false); reload() }} />

      <ConfirmDialog open={!!removing} onClose={() => setRemoving(null)} onConfirm={remove}
        title="Delete document?" confirmLabel="Delete" loading={busy} />
    </div>
  )
}

function UploadDrawer({ open, eventId, onClose, onSaved }) {
  const fileRef = useRef(null)
  const [file, setFile] = useState(null)
  const [name, setName] = useState('')
  const [category, setCategory] = useState('other')
  const [saving, setSaving] = useState(false)
  const [error, setError] = useState(null)

  async function submit(e) {
    e.preventDefault()
    if (!file) { setError('Choose a file to upload.'); return }
    setSaving(true)
    setError(null)
    try {
      const form = new FormData()
      form.append('file', file)
      form.append('category', category)
      if (name) form.append('name', name)
      await api.post(`/events/${eventId}/documents`, form)
      setFile(null); setName(''); setCategory('other')
      onSaved()
    } catch (err) {
      setError(parseApiError(err).message)
    } finally {
      setSaving(false)
    }
  }

  return (
    <Drawer open={open} onClose={onClose} title="Upload document">
      <form onSubmit={submit} className="space-y-4">
        <button type="button" onClick={() => fileRef.current?.click()}
          className="flex w-full flex-col items-center gap-2 rounded-card border border-dashed border-line bg-canvas/50 px-4 py-8 text-center transition-colors hover:border-navy-300">
          <Icon name="FileUp" className="size-7 text-navy-700" />
          <span className="text-sm font-semibold text-ink">{file ? file.name : 'Choose a file'}</span>
          <span className="text-xs text-muted">PDF, Office, images · up to 10 MB</span>
        </button>
        <input ref={fileRef} type="file" className="hidden" onChange={(e) => setFile(e.target.files?.[0] ?? null)} />

        <Field label="Display name (optional)" value={name} onChange={(e) => setName(e.target.value)} />
        <SelectField label="Category" options={DOCUMENT_CATEGORY_OPTIONS} value={category} onChange={(e) => setCategory(e.target.value)} />

        {error && <p className="text-sm text-danger">{error}</p>}

        <div className="flex justify-end pt-2">
          <Button type="submit" loading={saving}>Upload</Button>
        </div>
      </form>
    </Drawer>
  )
}
