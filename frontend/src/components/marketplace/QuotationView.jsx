import Badge from '../ui/Badge'
import Icon from '../ui/Icon'
import { formatCurrency, formatDate } from '../../lib/format'
import { statusMeta, QUOTATION_STATUS } from '../../lib/marketplace'

/**
 * Renders a quotation's line items and totals. Read-only; the parent supplies
 * any action buttons via `actions`.
 */
export default function QuotationView({ quotation, actions }) {
  const meta = statusMeta(QUOTATION_STATUS, quotation.status)

  return (
    <div className="rounded-btn border border-line bg-canvas/50 p-4">
      <div className="flex items-center justify-between">
        <div>
          <p className="flex items-center gap-2 font-bold text-ink">
            <Icon name="ReceiptText" className="size-4" /> {quotation.reference}
          </p>
          {quotation.expires_at && <p className="text-xs text-muted">Valid until {formatDate(quotation.expires_at)}</p>}
        </div>
        <Badge tone={meta.tone}>{meta.label}</Badge>
      </div>

      <div className="mt-3 overflow-x-auto">
        <table className="w-full text-sm">
          <tbody>
            {(quotation.items ?? []).map((item) => (
              <tr key={item.id} className="border-b border-line/70">
                <td className="py-1.5 pr-2 text-ink">{item.description}</td>
                <td className="py-1.5 px-2 text-right text-muted tabular-nums">{item.quantity} × {formatCurrency(item.unit_price)}</td>
                <td className="py-1.5 pl-2 text-right font-semibold text-ink tabular-nums">{formatCurrency(item.amount)}</td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>

      <div className="mt-3 space-y-1 text-sm">
        <Row label="Subtotal" value={formatCurrency(quotation.subtotal)} />
        {Number(quotation.tax) > 0 && <Row label="Tax" value={formatCurrency(quotation.tax)} />}
        <Row label="Total" value={formatCurrency(quotation.total)} bold />
      </div>

      {quotation.timeline && <p className="mt-3 text-sm text-muted"><span className="font-semibold text-ink">Timeline:</span> {quotation.timeline}</p>}
      {quotation.terms && <p className="mt-1 text-sm text-muted"><span className="font-semibold text-ink">Terms:</span> {quotation.terms}</p>}
      {quotation.notes && <p className="mt-1 text-sm text-muted">{quotation.notes}</p>}

      {actions && <div className="mt-4 flex flex-wrap gap-2">{actions}</div>}
    </div>
  )
}

function Row({ label, value, bold }) {
  return (
    <div className="flex items-center justify-between">
      <span className={bold ? 'font-bold text-ink' : 'text-muted'}>{label}</span>
      <span className={`tabular-nums ${bold ? 'font-extrabold text-navy-800' : 'text-ink'}`}>{value}</span>
    </div>
  )
}
