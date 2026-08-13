import { useMemo, useState } from 'react'
import Button from '../../../../../components/ui/Button'
import Icon from '../../../../../components/ui/Icon'
import Badge from '../../../../../components/ui/Badge'
import Modal from '../../../../../components/ui/Modal'
import ConfirmDialog from '../../../../../components/ui/ConfirmDialog'
import EmptyState from '../../../../../components/ui/EmptyState'
import { Field, SelectField } from '../../../../../components/ui/Field'
import { Table, THead, TH, TBody, TR, TD } from '../../../../../components/ui/Table'
import LoadState from '../../../../../components/dashboard/LoadState'
import { useResource } from '../../../../../lib/useResource'
import { api } from '../../../../../lib/api'
import { cn } from '../../../../../lib/cn'
import { RSVP_TONE, RSVP_STATUS_OPTIONS, INVITATION_STATUS_OPTIONS } from '../../../../../lib/guestConstants'
import Alert from '../../../../../components/ui/Alert'
import { whatsappUrl } from '../../../../../lib/messaging'
import DigitalTicket from '../../../../../components/guests/DigitalTicket'
import Spinner from '../../../../../components/ui/Spinner'
import GuestFormDrawer from './GuestFormDrawer'
import GuestDetailDrawer from './GuestDetailDrawer'
import ImportExportPanel from './ImportExportPanel'

export default function GuestListPanel({ eventId, event }) {
  const [showArchived, setShowArchived] = useState(false)
  const [ioOpen, setIoOpen] = useState(false)
  const { data, loading, error, reload } = useResource(`/events/${eventId}/guests${showArchived ? '?archived=1' : ''}`)
  const { data: catData } = useResource('/guest-categories')
  const categories = catData?.categories ?? []
  const colorFor = useMemo(() => Object.fromEntries(categories.map((c) => [c.name, c.color])), [categories])

  const [filters, setFilters] = useState({ q: '', rsvp: '', invite: '', checkin: '', category: '' })
  const [sort, setSort] = useState('full_name')
  const [selected, setSelected] = useState(new Set())
  const [form, setForm] = useState({ open: false, editing: null })
  const [detailId, setDetailId] = useState(null)
  const [removing, setRemoving] = useState(null)
  const [bulkModal, setBulkModal] = useState(null) // 'category' | 'table'
  const [bulkValue, setBulkValue] = useState('')
  const [busy, setBusy] = useState(false)
  const [ticket, setTicket] = useState({ open: false, loading: false, data: null, guestName: '' })
  const [flash, setFlash] = useState(null)
  const [smsBusyId, setSmsBusyId] = useState(null)

  const guests = data?.guests ?? []

  const filtered = useMemo(() => {
    const q = filters.q.trim().toLowerCase()
    return guests
      .filter((g) => {
        if (q && !`${g.full_name} ${g.email ?? ''} ${g.phone ?? ''}`.toLowerCase().includes(q)) return false
        if (filters.rsvp && g.rsvp_status !== filters.rsvp) return false
        if (filters.invite && g.invitation_status !== filters.invite) return false
        if (filters.checkin && g.checkin_status !== filters.checkin) return false
        if (filters.category && g.category !== filters.category) return false
        return true
      })
      .sort((a, b) => String(a[sort] ?? '').localeCompare(String(b[sort] ?? '')))
  }, [guests, filters, sort])

  const allSelected = filtered.length > 0 && filtered.every((g) => selected.has(g.id))

  function toggle(id) {
    setSelected((prev) => {
      const next = new Set(prev)
      if (next.has(id)) next.delete(id)
      else next.add(id)
      return next
    })
  }
  function toggleAll() {
    setSelected(allSelected ? new Set() : new Set(filtered.map((g) => g.id)))
  }

  async function bulk(action, extra = {}) {
    setBusy(true)
    try {
      await api.post(`/events/${eventId}/guests/bulk-action`, { action, guest_ids: [...selected], ...extra })
      setSelected(new Set()); setBulkModal(null); setBulkValue('')
      reload()
    } finally { setBusy(false) }
  }

  async function remove() {
    setBusy(true)
    try {
      await api.delete(`/events/${eventId}/guests/${removing.id}`)
      setRemoving(null); reload()
    } finally { setBusy(false) }
  }

  async function rowAction(guest, action) {
    if (action === 'edit') setForm({ open: true, editing: guest })
    else if (action === 'delete') setRemoving(guest)
    else if (action === 'archive') { await api.post(`/events/${eventId}/guests/${guest.id}/archive`); reload() }
    else if (action === 'duplicate') { await api.post(`/events/${eventId}/guests/${guest.id}/duplicate`); reload() }
    else if (action === 'whatsapp') { const url = whatsappUrl(guest, event); if (url) window.open(url, '_blank', 'noopener') }
    else if (action === 'message') await sendSms(guest)
    else if (action === 'ticket') openTicket(guest)
  }

  // Send an SMS invitation through the Africa's Talking gateway (server-side).
  async function sendSms(guest) {
    setSmsBusyId(guest.id)
    setFlash(null)
    try {
      const r = await api.post(`/events/${eventId}/invitations/send`, { channel: 'sms', guest_ids: [guest.id] })
      setFlash({ tone: 'success', text: r.data.message ?? `Message sent to ${guest.full_name}.` })
    } catch (err) {
      setFlash({ tone: 'error', text: err.response?.data?.message ?? 'Could not send the message.' })
    } finally {
      setSmsBusyId(null)
      setTimeout(() => setFlash(null), 6000)
    }
  }

  async function openTicket(guest) {
    setTicket({ open: true, loading: true, data: null, guestName: guest.full_name })
    try {
      const r = await api.get(`/events/${eventId}/guests/${guest.id}/ticket`)
      setTicket({ open: true, loading: false, data: r.data.data.ticket, guestName: guest.full_name })
    } catch {
      setTicket({ open: false, loading: false, data: null, guestName: '' })
    }
  }

  return (
    <div className="space-y-4">
      <div className="flex flex-wrap items-center justify-between gap-3">
        <div>
          <h2 className="text-lg font-extrabold text-ink">Guest List</h2>
          <p className="text-sm text-muted">{filtered.length} of {guests.length} guests</p>
        </div>
        <div className="flex items-center gap-2">
          <Button variant={showArchived ? 'secondary' : 'ghost'} size="sm" onClick={() => setShowArchived((v) => !v)}>
            <Icon name="Archive" className="size-4" /> {showArchived ? 'Hiding archived' : 'Show archived'}
          </Button>
          <Button variant="ghost" size="sm" onClick={() => setIoOpen(true)}>
            <Icon name="FileUp" className="size-4" /> Import / Export
          </Button>
          <Button size="sm" onClick={() => setForm({ open: true, editing: null })}>
            <Icon name="UserPlus" className="size-4" /> Add guest
          </Button>
        </div>
      </div>

      {flash && <Alert tone={flash.tone}>{flash.text}</Alert>}

      {/* Filters */}
      <div className="grid gap-2 sm:grid-cols-2 lg:grid-cols-6">
        <Field placeholder="Search name, email, phone" value={filters.q}
          onChange={(e) => setFilters((f) => ({ ...f, q: e.target.value }))} className="lg:col-span-2" />
        <SelectField value={filters.rsvp} onChange={(e) => setFilters((f) => ({ ...f, rsvp: e.target.value }))}>
          <option value="">All RSVP</option>
          {RSVP_STATUS_OPTIONS.map((o) => <option key={o.value} value={o.value}>{o.label}</option>)}
        </SelectField>
        <SelectField value={filters.invite} onChange={(e) => setFilters((f) => ({ ...f, invite: e.target.value }))}>
          <option value="">All invites</option>
          {INVITATION_STATUS_OPTIONS.map((o) => <option key={o.value} value={o.value}>{o.label}</option>)}
        </SelectField>
        <SelectField value={filters.category} onChange={(e) => setFilters((f) => ({ ...f, category: e.target.value }))}>
          <option value="">All categories</option>
          {categories.map((c) => <option key={c.id} value={c.name}>{c.name}</option>)}
        </SelectField>
        <SelectField value={sort} onChange={(e) => setSort(e.target.value)}>
          <option value="full_name">Sort: Name</option>
          <option value="category">Sort: Category</option>
          <option value="rsvp_status">Sort: RSVP</option>
          <option value="invitation_status">Sort: Invitation</option>
        </SelectField>
      </div>

      {/* Bulk bar */}
      {selected.size > 0 && (
        <div className="flex flex-wrap items-center gap-2 rounded-btn border border-navy-100 bg-navy-50 px-3 py-2">
          <span className="text-sm font-semibold text-navy-800">{selected.size} selected</span>
          <div className="ml-auto flex flex-wrap gap-1.5">
            <Button size="sm" variant="secondary" onClick={() => bulk('send_invitations')} loading={busy}><Icon name="Send" className="size-4" /> Invite</Button>
            <Button size="sm" variant="secondary" onClick={() => setBulkModal('category')}><Icon name="Tag" className="size-4" /> Category</Button>
            <Button size="sm" variant="secondary" onClick={() => setBulkModal('table')}><Icon name="LayoutGrid" className="size-4" /> Table</Button>
            <Button size="sm" variant="secondary" onClick={() => bulk('archive')} loading={busy}><Icon name="Archive" className="size-4" /> Archive</Button>
            <Button size="sm" variant="danger" onClick={() => bulk('delete')} loading={busy}><Icon name="Trash2" className="size-4" /> Delete</Button>
          </div>
        </div>
      )}

      <LoadState loading={loading} error={error} onRetry={reload}>
        {data && (filtered.length ? (
          <Table>
            <THead>
              <TR>
                <TH><input type="checkbox" checked={allSelected} onChange={toggleAll} className="size-4 accent-navy-600" /></TH>
                <TH>Name</TH><TH>Category</TH><TH>RSVP</TH><TH>Actions</TH>
              </TR>
            </THead>
            <TBody>
              {filtered.map((g) => (
                <TR key={g.id} className={cn(g.is_archived && 'opacity-60')}>
                  <TD><input type="checkbox" checked={selected.has(g.id)} onChange={() => toggle(g.id)} className="size-4 accent-navy-600" /></TD>
                  <TD>
                    <button type="button" className="font-semibold text-ink hover:text-navy-700" onClick={() => setDetailId(g.id)}>
                      {g.full_name}
                    </button>
                    {g.plus_ones_allowed > 0 && <span className="ml-1 text-xs text-muted">+{g.plus_ones_allowed}</span>}
                  </TD>
                  <TD>
                    {g.category ? (
                      <span className="inline-flex items-center gap-1.5 text-sm text-muted">
                        <span className="size-2.5 rounded-full" style={{ background: colorFor[g.category] ?? '#94a3b8' }} />
                        {g.category}
                      </span>
                    ) : <span className="text-muted">—</span>}
                  </TD>
                  <TD><Badge tone={RSVP_TONE[g.rsvp_status] ?? 'muted'}>{g.rsvp_status_label}</Badge></TD>
                  <TD>
                    <div className="flex items-center gap-0.5">
                      <button
                        type="button"
                        title={g.phone ? 'Invite via WhatsApp' : 'Add a phone number to use WhatsApp'}
                        disabled={!g.phone}
                        onClick={() => rowAction(g, 'whatsapp')}
                        className="grid size-8 place-items-center rounded-btn text-muted transition-colors hover:bg-emerald-50 hover:text-emerald-600 disabled:cursor-not-allowed disabled:opacity-40 disabled:hover:bg-transparent disabled:hover:text-muted"
                      >
                        <Icon name="MessageCircle" className="size-4" />
                      </button>
                      <button
                        type="button"
                        title={g.phone ? 'Send SMS invitation' : 'Add a phone number to send a message'}
                        disabled={!g.phone || smsBusyId === g.id}
                        onClick={() => rowAction(g, 'message')}
                        className="grid size-8 place-items-center rounded-btn text-muted transition-colors hover:bg-navy-50 hover:text-navy-700 disabled:cursor-not-allowed disabled:opacity-40 disabled:hover:bg-transparent disabled:hover:text-muted"
                      >
                        <Icon name={smsBusyId === g.id ? 'Loader2' : 'MessageSquare'} className={cn('size-4', smsBusyId === g.id && 'animate-spin')} />
                      </button>
                      {(() => {
                        const canTicket = ['confirmed', 'attended'].includes(g.rsvp_status)
                        return (
                          <button
                            type="button"
                            title={canTicket ? 'Download ticket' : 'Ticket available once the guest confirms their RSVP'}
                            disabled={!canTicket}
                            onClick={() => rowAction(g, 'ticket')}
                            className="grid size-8 place-items-center rounded-btn text-muted transition-colors hover:bg-canvas hover:text-ink disabled:cursor-not-allowed disabled:opacity-40 disabled:hover:bg-transparent disabled:hover:text-muted"
                          >
                            <Icon name="Download" className="size-4" />
                          </button>
                        )
                      })()}
                      <span className="mx-0.5 h-5 w-px bg-line" />
                      <button
                        type="button"
                        title="Edit"
                        onClick={() => rowAction(g, 'edit')}
                        className="grid size-8 place-items-center rounded-btn text-muted transition-colors hover:bg-canvas hover:text-ink"
                      >
                        <Icon name="PenLine" className="size-4" />
                      </button>
                      <button
                        type="button"
                        title="Duplicate"
                        onClick={() => rowAction(g, 'duplicate')}
                        className="grid size-8 place-items-center rounded-btn text-muted transition-colors hover:bg-canvas hover:text-ink"
                      >
                        <Icon name="Copy" className="size-4" />
                      </button>
                      <button
                        type="button"
                        title={g.is_archived ? 'Restore' : 'Archive'}
                        onClick={() => rowAction(g, 'archive')}
                        className="grid size-8 place-items-center rounded-btn text-muted transition-colors hover:bg-canvas hover:text-ink"
                      >
                        <Icon name={g.is_archived ? 'ArchiveRestore' : 'Archive'} className="size-4" />
                      </button>
                      <button
                        type="button"
                        title="Delete"
                        onClick={() => rowAction(g, 'delete')}
                        className="grid size-8 place-items-center rounded-btn text-muted transition-colors hover:bg-danger-50 hover:text-danger"
                      >
                        <Icon name="Trash2" className="size-4" />
                      </button>
                    </div>
                  </TD>
                </TR>
              ))}
            </TBody>
          </Table>
        ) : (
          <EmptyState icon="Users" title="No guests match" description="Adjust the filters or add a guest."
            action={<Button size="sm" onClick={() => setForm({ open: true, editing: null })}><Icon name="UserPlus" className="size-4" /> Add guest</Button>} />
        ))}
      </LoadState>

      <GuestFormDrawer key={form.editing?.id ?? 'new'} open={form.open} editing={form.editing} eventId={eventId}
        categories={categories} onClose={() => setForm({ open: false, editing: null })}
        onSaved={() => { setForm({ open: false, editing: null }); reload() }} />

      <GuestDetailDrawer open={!!detailId} guestId={detailId} eventId={eventId}
        onClose={() => setDetailId(null)} />

      <ConfirmDialog open={!!removing} onClose={() => setRemoving(null)} onConfirm={remove}
        title="Remove guest?" description={removing?.full_name} confirmLabel="Remove" loading={busy} />

      <Modal open={!!bulkModal} onClose={() => setBulkModal(null)}
        title={bulkModal === 'category' ? 'Set category' : 'Assign table'}
        footer={<>
          <Button variant="ghost" size="sm" onClick={() => setBulkModal(null)}>Cancel</Button>
          <Button size="sm" loading={busy}
            onClick={() => bulk(bulkModal === 'category' ? 'update_category' : 'assign_table', bulkModal === 'category' ? { category: bulkValue } : { seat_number: bulkValue })}>
            Apply to {selected.size}
          </Button>
        </>}>
        {bulkModal === 'category' ? (
          <SelectField label="Category" value={bulkValue} onChange={(e) => setBulkValue(e.target.value)}>
            <option value="">No category</option>
            {categories.map((c) => <option key={c.id} value={c.name}>{c.name}</option>)}
          </SelectField>
        ) : (
          <Field label="Table / seat" value={bulkValue} onChange={(e) => setBulkValue(e.target.value)} placeholder="e.g. Table 4" />
        )}
      </Modal>

      <Modal open={ioOpen} onClose={() => { setIoOpen(false); reload() }} title="Import & Export">
        <ImportExportPanel eventId={eventId} event={event} hideHeader />
      </Modal>

      <Modal open={ticket.open} onClose={() => setTicket({ open: false, loading: false, data: null, guestName: '' })}
        title={`Ticket — ${ticket.guestName}`}>
        {ticket.loading ? (
          <div className="grid place-items-center py-12"><Spinner className="size-6" /></div>
        ) : ticket.data ? (
          <DigitalTicket ticket={ticket.data} />
        ) : (
          <p className="py-8 text-center text-sm text-muted">Could not load the ticket.</p>
        )}
      </Modal>
    </div>
  )
}
