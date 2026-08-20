import { useEffect, useState } from 'react'
import Drawer from '../ui/Drawer'
import Button from '../ui/Button'
import Icon from '../ui/Icon'
import { Field } from '../ui/Field'
import Textarea from '../ui/Textarea'
import { api, parseApiError } from '../../lib/api'
import { formatCurrency } from '../../lib/format'

const blankItem = () => ({ description: '', quantity: 1, unit_price: '' })

/**
 * Drawer for a vendor to build and send a quotation against a booking request.
 * Creates the draft then immediately sends it.
 */
export default function QuotationBuilder({ open, onClose, bookingRequest, onSent }) {
  const [items, setItems] = useState([blankItem()])
  const [meta, setMeta] = useState({ tax: '', timeline: '', terms: '', expires_at: '' })
  const [saving, setSaving] = useState(false)
  const [error, setError] = useState(null)

  useEffect(() => {
    if (open) {
      setItems([blankItem()])
      setMeta({ tax: '', timeline: '', terms: '', expires_at: '' })
      setError(null)
    }
  }, [open])

  const setItem = (i, patch) => setItems((arr) => arr.map((it, idx) => (idx === i ? { ...it, ...patch } : it)))
  const subtotal = items.reduce((sum, it) => sum + (Number(it.quantity) || 0) * (Number(it.unit_price) || 0), 0)
  const total = subtotal + (Number(meta.tax) || 0)

  const submit = async () => {
    setSaving(true)
    setError(null)
    try {
      const payload = {
        booking_request_id: bookingRequest.id,
        items: items
          .filter((it) => it.description && it.unit_price !== '')
          .map((it) => ({ description: it.description, quantity: Number(it.quantity) || 1, unit_price: Number(it.unit_price) || 0 })),
        tax: meta.tax === '' ? 0 : Number(meta.tax),
        timeline: meta.timeline || null,
        terms: meta.terms || null,
        expires_at: meta.expires_at || null,
      }
      const { data } = await api.post('/marketplace/vendor/quotations', payload)
      await api.post(`/marketplace/vendor/quotations/${data.data.quotation.id}/send`)
      onSent?.()
      onClose()
    } catch (err) {
      setError(parseApiError(err).message)
    } finally {
      setSaving(false)
    }
  }

  return (
    <Drawer
      open={open}
      onClose={onClose}
      title="Send a quotation"
      description={bookingRequest ? `For ${bookingRequest.planner_name ?? 'the planner'}` : ''}
      footer={<div className="flex justify-end gap-3"><Button variant="ghost" onClick={onClose}>Cancel</Button><Button onClick={submit} loading={saving}>Send quotation</Button></div>}
    >
      <div className="space-y-4">
        {error && <p className="rounded-btn bg-danger-soft px-3 py-2 text-sm text-danger">{error}</p>}

        <div className="space-y-3">
          {items.map((it, i) => (
            <div key={i} className="rounded-btn border border-line p-3">
              <div className="flex items-center justify-between">
                <p className="text-xs font-bold uppercase tracking-wide text-muted">Item {i + 1}</p>
                {items.length > 1 && <button onClick={() => setItems((arr) => arr.filter((_, idx) => idx !== i))} className="text-muted hover:text-danger"><Icon name="X" className="size-4" /></button>}
              </div>
              <Field className="mt-2" label="Description" value={it.description} onChange={(e) => setItem(i, { description: e.target.value })} />
              <div className="mt-2 grid grid-cols-2 gap-2">
                <Field label="Qty" type="number" min="0" value={it.quantity} onChange={(e) => setItem(i, { quantity: e.target.value })} />
                <Field label="Unit price" type="number" min="0" value={it.unit_price} onChange={(e) => setItem(i, { unit_price: e.target.value })} />
              </div>
            </div>
          ))}
          <Button variant="ghost" size="sm" onClick={() => setItems((arr) => [...arr, blankItem()])}><Icon name="Plus" className="size-4" /> Add line item</Button>
        </div>

        <div className="grid grid-cols-2 gap-3">
          <Field label="Tax (TZS)" type="number" min="0" value={meta.tax} onChange={(e) => setMeta({ ...meta, tax: e.target.value })} />
          <Field label="Valid until" type="date" value={meta.expires_at} onChange={(e) => setMeta({ ...meta, expires_at: e.target.value })} />
        </div>
        <Field label="Timeline" value={meta.timeline} onChange={(e) => setMeta({ ...meta, timeline: e.target.value })} placeholder="e.g. Delivery within 4 weeks" />
        <Textarea label="Terms" value={meta.terms} onChange={(e) => setMeta({ ...meta, terms: e.target.value })} rows={2} />

        <div className="rounded-btn bg-canvas p-3 text-sm">
          <div className="flex justify-between text-muted"><span>Subtotal</span><span className="tabular-nums text-ink">{formatCurrency(subtotal)}</span></div>
          <div className="mt-1 flex justify-between font-bold text-ink"><span>Total</span><span className="tabular-nums text-navy-800">{formatCurrency(total)}</span></div>
        </div>
      </div>
    </Drawer>
  )
}
