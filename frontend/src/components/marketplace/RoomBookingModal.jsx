import { useEffect, useMemo, useState } from 'react'
import Modal from '../ui/Modal'
import Button from '../ui/Button'
import Icon from '../ui/Icon'
import { Field } from '../ui/Field'
import Textarea from '../ui/Textarea'
import ListboxSelect from '../ui/ListboxSelect'
import { api, parseApiError } from '../../lib/api'
import { formatCurrency } from '../../lib/format'

/** Nights between two ISO dates (0 if invalid / not after). */
function nightsBetween(inDate, outDate) {
  if (!inDate || !outDate) return 0
  const ms = new Date(outDate) - new Date(inDate)
  return ms > 0 ? Math.round(ms / 86_400_000) : 0
}

const today = () => new Date().toISOString().slice(0, 10)

/**
 * Book a hotel room for a client (the honeymoon stay). Picks dates, rooms and
 * guests, optionally attributes it to one of the planner's clients, previews the
 * nightly total, and confirms a reservation.
 */
export default function RoomBookingModal({ open, onClose, hotel, room, onBooked }) {
  const [form, setForm] = useState({ check_in: '', check_out: '', rooms: 1, guests: 2, guest_name: '', client_id: '', special_requests: '' })
  const [clients, setClients] = useState([])
  const [saving, setSaving] = useState(false)
  const [error, setError] = useState(null)
  const [done, setDone] = useState(null)

  useEffect(() => {
    if (open) {
      setForm({ check_in: '', check_out: '', rooms: 1, guests: 2, guest_name: '', client_id: '', special_requests: '' })
      setError(null)
      setDone(null)
      api.get('/clients').then((r) => setClients(r.data.data.clients ?? [])).catch(() => {})
    }
  }, [open])

  const patch = (p) => setForm((f) => ({ ...f, ...p }))

  const nights = useMemo(() => nightsBetween(form.check_in, form.check_out), [form.check_in, form.check_out])
  const total = nights * Number(form.rooms || 1) * (room?.price_per_night ?? 0)

  // When a client is chosen, prefill the reservation name if still empty.
  const chooseClient = (id) => {
    const client = clients.find((c) => String(c.id) === String(id))
    patch({ client_id: id, guest_name: form.guest_name || (client?.full_name ?? '') })
  }

  const submit = async () => {
    setSaving(true)
    setError(null)
    try {
      const r = await api.post('/marketplace/accommodation-bookings', {
        room_type_id: room.id,
        check_in: form.check_in,
        check_out: form.check_out,
        rooms: Number(form.rooms),
        guests: Number(form.guests),
        guest_name: form.guest_name,
        client_id: form.client_id ? Number(form.client_id) : null,
        special_requests: form.special_requests || null,
      })
      setDone(r.data.data.booking)
      onBooked?.()
    } catch (err) {
      setError(parseApiError(err).message)
    } finally {
      setSaving(false)
    }
  }

  const canSubmit = form.check_in && nights > 0 && form.guest_name.trim() && !saving

  return (
    <Modal
      open={open}
      onClose={onClose}
      title={done ? 'Booking confirmed' : `Book · ${room?.name ?? ''}`}
      description={done ? undefined : hotel?.name}
      footer={done ? (
        <div className="flex justify-end"><Button onClick={onClose}>Done</Button></div>
      ) : (
        <div className="flex items-center justify-between gap-3">
          <span className="text-sm text-muted">
            {nights > 0 ? <>{nights} night{nights > 1 ? 's' : ''} · <span className="font-bold text-ink">{formatCurrency(total)}</span></> : 'Select dates'}
          </span>
          <div className="flex gap-2">
            <Button variant="ghost" onClick={onClose}>Cancel</Button>
            <Button onClick={submit} loading={saving} disabled={!canSubmit}>Confirm booking</Button>
          </div>
        </div>
      )}
    >
      {done ? (
        <div className="space-y-3 text-center">
          <div className="mx-auto grid size-14 place-items-center rounded-full bg-emerald-100 dark:bg-emerald-950">
            <Icon name="Check" className="size-7 text-emerald-600 dark:text-emerald-400" />
          </div>
          <p className="text-sm text-muted">
            <span className="font-semibold text-ink">{done.reference}</span> — {done.rooms} × {done.room_type?.name} at {done.accommodation?.name},
            {' '}{done.nights} night{done.nights > 1 ? 's' : ''} for {done.guest_name}.
          </p>
          <p className="text-lg font-extrabold text-ink">{formatCurrency(done.total_price)}</p>
        </div>
      ) : (
        <div className="space-y-4">
          {error && <p className="rounded-btn bg-danger-soft px-3 py-2 text-sm text-danger">{error}</p>}

          <div className="grid gap-4 sm:grid-cols-2">
            <Field label="Check-in" type="date" min={today()} value={form.check_in} onChange={(e) => patch({ check_in: e.target.value })} />
            <Field label="Check-out" type="date" min={form.check_in || today()} value={form.check_out} onChange={(e) => patch({ check_out: e.target.value })} />
            <Field label="Rooms" type="number" min="1" max={room?.total_rooms ?? 20} value={form.rooms} onChange={(e) => patch({ rooms: e.target.value })} />
            <Field label="Guests" type="number" min="1" value={form.guests} onChange={(e) => patch({ guests: e.target.value })} />
          </div>

          <div>
            <label className="mb-1 block text-sm font-semibold text-ink">For client <span className="font-normal text-muted">(optional)</span></label>
            <ListboxSelect heightClass="h-11" value={form.client_id} onChange={(e) => chooseClient(e.target.value)}>
              <option value="">Not linked to a client</option>
              {clients.map((c) => <option key={c.id} value={c.id}>{c.full_name}</option>)}
            </ListboxSelect>
          </div>

          <Field label="Name on reservation" value={form.guest_name} onChange={(e) => patch({ guest_name: e.target.value })} placeholder="e.g. Mr & Mrs Carter" />

          <Textarea label="Special requests (optional)" rows={3} value={form.special_requests} onChange={(e) => patch({ special_requests: e.target.value })} placeholder="Honeymoon package, champagne on arrival, late checkout…" />

          <div className="flex items-center justify-between rounded-btn bg-canvas px-3 py-2 text-sm">
            <span className="text-muted">{formatCurrency(room?.price_per_night ?? 0)}/night × {form.rooms || 1} room{form.rooms > 1 ? 's' : ''}{nights > 0 ? ` × ${nights} night${nights > 1 ? 's' : ''}` : ''}</span>
            <span className="font-extrabold text-ink">{formatCurrency(total)}</span>
          </div>
        </div>
      )}
    </Modal>
  )
}
