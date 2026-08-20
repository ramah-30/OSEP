import Icon from '../ui/Icon'
import Button from '../ui/Button'
import { formatCurrency } from '../../lib/format'
import { rollUp, lineAmount } from '../../lib/finance'

const BLANK = { description: '', quantity: 1, unit_price: 0, tax: 0, discount: 0 }

/**
 * Editable line-item grid shared by the quotation and invoice builders. Fully
 * controlled: the parent owns the `items` array and receives every change.
 */
export default function LineItemsEditor({ items, onChange, currency = 'TZS' }) {
  const update = (i, patch) => onChange(items.map((it, idx) => (idx === i ? { ...it, ...patch } : it)))
  const add = () => onChange([...items, { ...BLANK }])
  const remove = (i) => onChange(items.filter((_, idx) => idx !== i))

  const totals = rollUp(items)
  const grand = totals.subtotal + totals.tax - totals.discount

  return (
    <div className="space-y-3">
      <div className="space-y-2">
        {items.map((it, i) => (
          <div key={i} className="rounded-card border border-line p-3">
            <div className="flex items-start gap-2">
              <input
                value={it.description}
                onChange={(e) => update(i, { description: e.target.value })}
                placeholder="Item description"
                className="h-10 flex-1 rounded-btn border border-line bg-surface px-3 text-sm outline-none focus:border-navy-600"
              />
              <button type="button" onClick={() => remove(i)} title="Remove"
                className="grid size-10 shrink-0 place-items-center rounded-btn text-muted hover:bg-danger-soft hover:text-danger">
                <Icon name="Trash2" className="size-4" />
              </button>
            </div>
            <div className="mt-2 grid grid-cols-2 gap-2 sm:grid-cols-4">
              <NumField label="Qty" value={it.quantity} onChange={(v) => update(i, { quantity: v })} step="1" />
              <NumField label="Unit price" value={it.unit_price} onChange={(v) => update(i, { unit_price: v })} step="1000" />
              <NumField label="Tax" value={it.tax} onChange={(v) => update(i, { tax: v })} step="1000" />
              <NumField label="Discount" value={it.discount} onChange={(v) => update(i, { discount: v })} step="1000" />
            </div>
            <p className="mt-2 text-right text-xs font-semibold text-muted">
              Line total: {formatCurrency(lineAmount(it) + Number(it.tax || 0) - Number(it.discount || 0), currency)}
            </p>
          </div>
        ))}
      </div>

      <Button type="button" variant="secondary" size="sm" onClick={add}>
        <Icon name="Plus" className="size-4" /> Add line
      </Button>

      <div className="space-y-1 rounded-card bg-canvas p-4 text-sm">
        <Row label="Subtotal" value={formatCurrency(totals.subtotal, currency)} />
        <Row label="Tax" value={formatCurrency(totals.tax, currency)} />
        <Row label="Discount" value={`− ${formatCurrency(totals.discount, currency)}`} />
        <div className="mt-1 flex justify-between border-t border-line pt-2 text-base font-extrabold text-ink">
          <span>Total</span><span className="tabular-nums">{formatCurrency(grand, currency)}</span>
        </div>
      </div>
    </div>
  )
}

function NumField({ label, value, onChange, step }) {
  return (
    <label className="block">
      <span className="mb-1 block text-xs font-semibold text-muted">{label}</span>
      <input
        type="number" min="0" step={step} value={value}
        onChange={(e) => onChange(e.target.value === '' ? '' : Number(e.target.value))}
        className="h-10 w-full rounded-btn border border-line bg-surface px-2.5 text-sm tabular-nums outline-none focus:border-navy-600"
      />
    </label>
  )
}

function Row({ label, value }) {
  return (
    <div className="flex justify-between text-muted">
      <span>{label}</span><span className="tabular-nums">{value}</span>
    </div>
  )
}
