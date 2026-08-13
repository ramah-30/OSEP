import { useEffect, useState } from 'react'
import Drawer from '../../../../../components/ui/Drawer'
import Button from '../../../../../components/ui/Button'
import Badge from '../../../../../components/ui/Badge'
import Icon from '../../../../../components/ui/Icon'
import Tabs from '../../../../../components/ui/Tabs'
import { Field } from '../../../../../components/ui/Field'
import Spinner from '../../../../../components/ui/Spinner'
import DigitalTicket from '../../../../../components/guests/DigitalTicket'
import { api } from '../../../../../lib/api'
import { formatRelative } from '../../../../../lib/format'
import { RSVP_TONE, INVITATION_TONE, CHECKIN_TONE, COMM_TYPE_META } from '../../../../../lib/guestConstants'

const TABS = [
  { value: 'profile', label: 'Profile' },
  { value: 'invitations', label: 'Invitations' },
  { value: 'timeline', label: 'Communication' },
  { value: 'ticket', label: 'Ticket' },
]

export default function GuestDetailDrawer({ open, guestId, eventId, onClose }) {
  const [tab, setTab] = useState('profile')
  const [data, setData] = useState(null)
  const [ticket, setTicket] = useState(null)
  const [ticketError, setTicketError] = useState(null)
  const [loading, setLoading] = useState(false)
  const [note, setNote] = useState('')
  const [busy, setBusy] = useState(false)

  useEffect(() => {
    if (!open || !guestId) return
    setTab('profile'); setData(null); setTicket(null); setTicketError(null)
    setLoading(true)
    api.get(`/events/${eventId}/guests/${guestId}/history`)
      .then((r) => setData(r.data.data))
      .finally(() => setLoading(false))
  }, [open, guestId, eventId])

  async function loadTicket() {
    setTicketError(null)
    try {
      const r = await api.get(`/events/${eventId}/guests/${guestId}/ticket`)
      setTicket(r.data.data.ticket)
    } catch (err) {
      setTicketError(err.response?.data?.message ?? 'Ticket is not available yet.')
    }
  }

  useEffect(() => {
    if (tab === 'ticket' && !ticket && !ticketError && open) loadTicket()
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [tab])

  const guest = data?.guest
  const rsvpLink = guest ? `${window.location.origin}/rsvp/${guest.rsvp_token}` : ''

  async function addNote() {
    if (!note.trim()) return
    setBusy(true)
    try {
      await api.post(`/events/${eventId}/guests/${guestId}/notes`, { title: note })
      setNote('')
      const r = await api.get(`/events/${eventId}/guests/${guestId}/history`)
      setData(r.data.data)
    } finally { setBusy(false) }
  }

  return (
    <Drawer open={open} onClose={onClose} title={guest?.full_name ?? 'Guest'}
      description={guest ? `${guest.category ?? 'Uncategorised'} · ${guest.email ?? guest.phone ?? 'no contact'}` : ''}>
      {loading || !guest ? (
        <div className="grid place-items-center py-16"><Spinner className="size-7" /></div>
      ) : (
        <div className="space-y-4">
          <div className="flex flex-wrap gap-2">
            <Badge tone={RSVP_TONE[guest.rsvp_status] ?? 'muted'}>{guest.rsvp_status_label}</Badge>
            <Badge tone={INVITATION_TONE[guest.invitation_status] ?? 'muted'}>{guest.invitation_status_label}</Badge>
            <Badge tone={CHECKIN_TONE[guest.checkin_status] ?? 'muted'}>{guest.checkin_status_label}</Badge>
          </div>

          <Tabs tabs={TABS} value={tab} onChange={setTab} />

          {tab === 'profile' && (
            <div className="space-y-3">
              <Row label="Email" value={guest.email} />
              <Row label="Phone" value={guest.phone} />
              <Row label="Gender" value={guest.gender} />
              <Row label="Plus-ones allowed" value={guest.plus_ones_allowed} />
              <Row label="Meal preference" value={guest.meal_preference} />
              <Row label="Dietary" value={guest.dietary_restrictions} />
              <Row label="Accessibility" value={guest.accessibility_requirements} />
              <Row label="Seat" value={guest.seat_number} />
              <Row label="Notes" value={guest.notes} />
              <div className="rounded-btn border border-line bg-canvas p-3">
                <p className="mb-1 text-xs font-semibold text-muted">Public RSVP link</p>
                <div className="flex items-center gap-2">
                  <code className="flex-1 truncate rounded bg-surface px-2 py-1 text-xs text-ink">{rsvpLink}</code>
                  <Button size="sm" variant="secondary" onClick={() => navigator.clipboard?.writeText(rsvpLink)}>
                    <Icon name="Copy" className="size-4" />
                  </Button>
                </div>
              </div>
            </div>
          )}

          {tab === 'invitations' && (
            <div className="space-y-2">
              {guest.invitations?.length ? guest.invitations.map((inv) => (
                <div key={inv.id} className="rounded-btn border border-line p-3">
                  <div className="flex items-center justify-between">
                    <span className="text-sm font-semibold text-ink">{inv.channel_label}</span>
                    <Badge tone={INVITATION_TONE[inv.status] ?? 'muted'}>{inv.status_label}</Badge>
                  </div>
                  <p className="mt-1 text-xs text-muted">{inv.subject}</p>
                  <p className="mt-1 text-xs text-muted">{inv.sent_at ? `Sent ${formatRelative(inv.sent_at)}` : 'Not sent'}</p>
                </div>
              )) : <Empty text="No invitations sent yet." />}
            </div>
          )}

          {tab === 'timeline' && (
            <div className="space-y-3">
              <div className="flex gap-2">
                <Field className="flex-1" placeholder="Add a note…" value={note} onChange={(e) => setNote(e.target.value)} />
                <Button size="sm" onClick={addNote} loading={busy}>Add</Button>
              </div>
              {data.logs?.length ? (
                <ul className="space-y-3">
                  {data.logs.map((log) => {
                    const meta = COMM_TYPE_META[log.type] ?? COMM_TYPE_META.system
                    return (
                      <li key={log.id} className="flex gap-3">
                        <span className="mt-0.5 grid size-8 shrink-0 place-items-center rounded-full bg-canvas">
                          <Icon name={meta.icon} className="size-4 text-muted" />
                        </span>
                        <div className="min-w-0">
                          <p className="text-sm font-semibold text-ink">{log.title}</p>
                          {log.detail && <p className="text-xs text-muted">{log.detail}</p>}
                          <p className="text-xs text-muted">{formatRelative(log.created_at)}</p>
                        </div>
                      </li>
                    )
                  })}
                </ul>
              ) : <Empty text="No communication history yet." />}
            </div>
          )}

          {tab === 'ticket' && (
            ticketError ? (
              <div className="grid place-items-center gap-2 py-12 text-center">
                <span className="grid size-12 place-items-center rounded-2xl bg-canvas text-muted">
                  <Icon name="Ticket" className="size-6" />
                </span>
                <p className="text-sm font-semibold text-ink">No ticket yet</p>
                <p className="max-w-xs text-xs text-muted">{ticketError}</p>
              </div>
            ) : ticket ? <DigitalTicket ticket={ticket} />
              : <div className="grid place-items-center py-12"><Spinner className="size-7" /></div>
          )}
        </div>
      )}
    </Drawer>
  )
}

function Row({ label, value }) {
  return (
    <div className="flex justify-between gap-4 border-b border-line py-2 text-sm">
      <span className="text-muted">{label}</span>
      <span className="text-right font-medium text-ink">{value || '—'}</span>
    </div>
  )
}

function Empty({ text }) {
  return <p className="py-8 text-center text-sm text-muted">{text}</p>
}
