import { useEffect, useState } from 'react'
import { useParams } from 'react-router-dom'
import Spinner from '../../../../components/ui/Spinner'
import { api, parseApiError } from '../../../../lib/api'
import { useAuth } from '../../../../context/AuthContext'
import { formatCurrency, formatDate } from '../../../../lib/format'

const CONFIG = {
  quotation: { url: (id) => `/finance/quotations/${id}`, pick: (d) => d.quotation, title: 'QUOTATION', numberKey: 'reference' },
  invoice: { url: (id) => `/finance/invoices/${id}`, pick: (d) => d.invoice, title: 'INVOICE', numberKey: 'invoice_number' },
  receipt: { url: (id) => `/finance/receipts/${id}`, pick: (d) => d.receipt, title: 'RECEIPT', numberKey: 'receipt_number' },
}

/**
 * A standalone, print-optimised document view (rendered outside the dashboard
 * chrome). Auto-opens the browser print dialog so the planner can "Save as PDF".
 */
export default function FinancePrint() {
  const { kind, id } = useParams()
  const { user } = useAuth()
  const config = CONFIG[kind]
  const [doc, setDoc] = useState(null)
  const [error, setError] = useState(null)

  useEffect(() => {
    if (!config) { setError('Unknown document type.'); return }
    let alive = true
    api.get(config.url(id))
      .then((r) => { if (alive) { setDoc(config.pick(r.data.data)); setTimeout(() => window.print(), 400) } })
      .catch((e) => alive && setError(parseApiError(e).message))
    return () => { alive = false }
  }, [config, id])

  if (error) return <div className="grid min-h-screen place-items-center bg-white text-slate-600">{error}</div>
  if (!doc) return <div className="grid min-h-screen place-items-center bg-white"><Spinner className="size-7" /></div>

  const currency = doc.currency ?? 'TZS'
  const isReceipt = kind === 'receipt'

  return (
    <div className="min-h-screen bg-slate-100 py-8 print:bg-white print:py-0">
      <div className="mx-auto max-w-3xl bg-white p-10 text-slate-800 shadow-lg print:max-w-none print:shadow-none">
        {/* Header */}
        <div className="flex items-start justify-between border-b border-slate-200 pb-6">
          <div>
            <p className="text-2xl font-extrabold tracking-tight text-slate-900">OSEP AI</p>
            <p className="mt-1 text-sm text-slate-500">{user?.full_name}</p>
            {user?.email && <p className="text-sm text-slate-500">{user.email}</p>}
          </div>
          <div className="text-right">
            <p className="text-xl font-extrabold tracking-widest text-slate-400">{config.title}</p>
            <p className="mt-1 font-semibold text-slate-900">{doc[config.numberKey]}</p>
            {doc.status_label && <p className="mt-1 text-sm text-slate-500">{doc.status_label}</p>}
          </div>
        </div>

        {/* Meta */}
        <div className="mt-6 grid grid-cols-2 gap-6 text-sm">
          <div>
            <p className="font-semibold uppercase tracking-wide text-slate-400">Billed to</p>
            <p className="mt-1 font-semibold text-slate-900">{doc.client?.name ?? '—'}</p>
            {doc.client?.email && <p className="text-slate-500">{doc.client.email}</p>}
            {doc.event?.title && <p className="mt-1 text-slate-500">Event: {doc.event.title}</p>}
          </div>
          <div className="text-right">
            {isReceipt ? (
              <>
                <MetaRow label="Issued" value={formatDate(doc.issued_at)} />
                <MetaRow label="Payment" value={doc.payment?.payment_number} />
                <MetaRow label="Method" value={doc.payment?.method_label} />
              </>
            ) : (
              <>
                <MetaRow label="Issue date" value={formatDate(doc.issue_date ?? doc.created_at)} />
                {doc.due_date && <MetaRow label="Due date" value={formatDate(doc.due_date)} />}
                {doc.valid_until && <MetaRow label="Valid until" value={formatDate(doc.valid_until)} />}
              </>
            )}
          </div>
        </div>

        {/* Body */}
        {isReceipt ? (
          <div className="mt-10 rounded-lg bg-slate-50 p-8 text-center">
            <p className="text-sm uppercase tracking-wide text-slate-400">Amount received</p>
            <p className="mt-2 text-4xl font-extrabold text-slate-900">{formatCurrency(doc.amount, currency)}</p>
            {doc.invoice_id && <p className="mt-2 text-sm text-slate-500">Against invoice #{doc.invoice?.invoice_number ?? doc.invoice_id}</p>}
          </div>
        ) : (
          <>
            <table className="mt-8 w-full text-sm">
              <thead>
                <tr className="border-b-2 border-slate-200 text-left text-xs uppercase tracking-wide text-slate-400">
                  <th className="py-2">Description</th>
                  <th className="py-2 text-right">Qty</th>
                  <th className="py-2 text-right">Unit price</th>
                  <th className="py-2 text-right">Tax</th>
                  <th className="py-2 text-right">Amount</th>
                </tr>
              </thead>
              <tbody>
                {(doc.items ?? []).map((it) => (
                  <tr key={it.id} className="border-b border-slate-100">
                    <td className="py-2.5">{it.description}</td>
                    <td className="py-2.5 text-right tabular-nums">{it.quantity}</td>
                    <td className="py-2.5 text-right tabular-nums">{formatCurrency(it.unit_price, currency)}</td>
                    <td className="py-2.5 text-right tabular-nums">{formatCurrency(it.tax, currency)}</td>
                    <td className="py-2.5 text-right tabular-nums">{formatCurrency(it.amount, currency)}</td>
                  </tr>
                ))}
              </tbody>
            </table>

            <div className="mt-6 flex justify-end">
              <div className="w-64 space-y-1.5 text-sm">
                <Total label="Subtotal" value={formatCurrency(doc.subtotal, currency)} />
                <Total label="Tax" value={formatCurrency(doc.tax, currency)} />
                <Total label="Discount" value={`− ${formatCurrency(doc.discount, currency)}`} />
                <div className="flex justify-between border-t-2 border-slate-200 pt-2 text-base font-extrabold text-slate-900">
                  <span>Total</span><span className="tabular-nums">{formatCurrency(doc.total, currency)}</span>
                </div>
                {kind === 'invoice' && (
                  <>
                    <Total label="Paid" value={formatCurrency(doc.amount_paid, currency)} />
                    <div className="flex justify-between font-semibold text-slate-900">
                      <span>Balance due</span><span className="tabular-nums">{formatCurrency(doc.balance, currency)}</span>
                    </div>
                  </>
                )}
              </div>
            </div>
          </>
        )}

        {(doc.notes || doc.terms || doc.payment_terms) && (
          <div className="mt-10 space-y-3 border-t border-slate-200 pt-6 text-sm text-slate-500">
            {doc.payment_terms && <p><span className="font-semibold text-slate-700">Payment terms:</span> {doc.payment_terms}</p>}
            {doc.terms && <p><span className="font-semibold text-slate-700">Terms:</span> {doc.terms}</p>}
            {doc.notes && <p><span className="font-semibold text-slate-700">Notes:</span> {doc.notes}</p>}
          </div>
        )}

        <p className="mt-10 text-center text-xs text-slate-400">Generated by OSEP AI · Thank you for your business.</p>

        {/* Screen-only toolbar (hidden when printing) */}
        <div className="mt-8 flex justify-center gap-3 print:hidden">
          <button onClick={() => window.print()} className="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white">
            Print / Save as PDF
          </button>
          <button onClick={() => window.close()} className="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-600">
            Close
          </button>
        </div>
      </div>
    </div>
  )
}

function MetaRow({ label, value }) {
  if (!value) return null
  return <p className="text-slate-500"><span className="font-semibold text-slate-700">{label}:</span> {value}</p>
}

function Total({ label, value }) {
  return <div className="flex justify-between text-slate-500"><span>{label}</span><span className="tabular-nums">{value}</span></div>
}
