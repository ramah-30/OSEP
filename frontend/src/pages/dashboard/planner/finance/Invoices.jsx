import { useState } from 'react'
import { useForm } from 'react-hook-form'
import Button from '../../../../components/ui/Button'
import Icon from '../../../../components/ui/Icon'
import Drawer from '../../../../components/ui/Drawer'
import ConfirmDialog from '../../../../components/ui/ConfirmDialog'
import EmptyState from '../../../../components/ui/EmptyState'
import { Field, SelectField } from '../../../../components/ui/Field'
import { Table, THead, TH, TBody, TR, TD } from '../../../../components/ui/Table'
import LoadState from '../../../../components/dashboard/LoadState'
import { SummaryCard, FStatus } from '../../../../components/finance/FinanceBits'
import LineItemsEditor from '../../../../components/finance/LineItemsEditor'
import { useResource } from '../../../../lib/useResource'
import { api } from '../../../../lib/api'
import { formatCurrency, formatDate } from '../../../../lib/format'
import { INVOICE_STATUS, PAYMENT_METHODS } from '../../../../lib/finance'

export default function Invoices() {
  const { data, loading, error, reload } = useResource('/finance/invoices')
  const { data: config } = useResource('/finance/config')
  const [drawer, setDrawer] = useState({ open: false, editing: null })
  const [payFor, setPayFor] = useState(null)
  const [removing, setRemoving] = useState(null)
  const [busy, setBusy] = useState(false)

  const invoices = data?.invoices ?? []
  const summary = data?.summary

  async function act(inv, path) {
    await api.post(`/finance/invoices/${inv.id}/${path}`)
    reload()
  }

  async function remove() {
    setBusy(true)
    try {
      await api.delete(`/finance/invoices/${removing.id}`)
      setRemoving(null)
      reload()
    } finally {
      setBusy(false)
    }
  }

  return (
    <div className="space-y-5">
      <div className="flex items-center justify-between gap-3">
        <div>
          <h2 className="text-lg font-extrabold text-ink">Invoices</h2>
          <p className="text-sm text-muted">Bill clients and track what has been paid.</p>
        </div>
        <Button size="sm" onClick={() => setDrawer({ open: true, editing: null })}>
          <Icon name="Plus" className="size-4" /> New invoice
        </Button>
      </div>

      {summary && (
        <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
          <SummaryCard label="Total billed" value={summary.total_billed} icon="ClipboardList" tone="navy" />
          <SummaryCard label="Total paid" value={summary.total_paid} icon="CheckCircle2" tone="emerald" />
          <SummaryCard label="Outstanding" value={summary.outstanding} icon="Clock" tone="amber" />
          <SummaryCard label="Overdue" value={summary.overdue} icon="TriangleAlert" money={false} tone="danger" />
        </div>
      )}

      <LoadState loading={loading} error={error} onRetry={reload}>
        {invoices.length ? (
          <Table>
            <THead>
              <TR>
                <TH>Invoice #</TH><TH>Client</TH><TH className="text-right">Total</TH><TH className="text-right">Balance</TH>
                <TH>Due</TH><TH>Status</TH><TH />
              </TR>
            </THead>
            <TBody>
              {invoices.map((inv) => (
                <TR key={inv.id}>
                  <TD className="font-semibold">{inv.invoice_number}</TD>
                  <TD className="text-muted">{inv.client?.name ?? '—'}</TD>
                  <TD className="text-right tabular-nums">{formatCurrency(inv.total, inv.currency)}</TD>
                  <TD className="text-right tabular-nums">{formatCurrency(inv.balance, inv.currency)}</TD>
                  <TD className="text-muted">{formatDate(inv.due_date)}</TD>
                  <TD><FStatus map={INVOICE_STATUS} value={inv.status} /></TD>
                  <TD>
                    <div className="flex justify-end gap-1">
                      <RowAction icon="Printer" title="Print / PDF" onClick={() => window.open(`/dashboard/planner/finance/print/invoice/${inv.id}`, '_blank')} />
                      {inv.status === 'draft' && <RowAction icon="Send" title="Send" onClick={() => act(inv, 'send')} />}
                      {inv.is_collectable && <RowAction icon="CircleDollarSign" title="Record payment" onClick={() => setPayFor(inv)} />}
                      {['draft', 'sent', 'partially_paid', 'overdue'].includes(inv.status) && (
                        <RowAction icon="PenLine" title="Edit" onClick={() => setDrawer({ open: true, editing: inv })} />
                      )}
                      {inv.status !== 'paid' && inv.status !== 'cancelled' && <RowAction icon="Ban" title="Cancel" onClick={() => act(inv, 'cancel')} />}
                      <RowAction icon="Trash2" title="Delete" danger onClick={() => setRemoving(inv)} />
                    </div>
                  </TD>
                </TR>
              ))}
            </TBody>
          </Table>
        ) : (
          <EmptyState icon="ClipboardList" title="No invoices yet" description="Create an invoice or convert an accepted quotation."
            action={<Button size="sm" onClick={() => setDrawer({ open: true, editing: null })}><Icon name="Plus" className="size-4" /> New invoice</Button>} />
        )}
      </LoadState>

      <InvoiceDrawer
        key={drawer.editing?.id ?? 'new'}
        open={drawer.open}
        editing={drawer.editing}
        config={config}
        onClose={() => setDrawer({ open: false, editing: null })}
        onSaved={() => { setDrawer({ open: false, editing: null }); reload() }}
      />

      <RecordPaymentDrawer
        key={payFor?.id ?? 'pay'}
        invoice={payFor}
        onClose={() => setPayFor(null)}
        onSaved={() => { setPayFor(null); reload() }}
      />

      <ConfirmDialog open={!!removing} onClose={() => setRemoving(null)} onConfirm={remove}
        title="Delete invoice?" confirmLabel="Delete" loading={busy} />
    </div>
  )
}

function RowAction({ icon, title, onClick, danger }) {
  return (
    <button type="button" title={title} onClick={onClick}
      className={`grid size-8 place-items-center rounded-btn text-muted transition-colors ${danger ? 'hover:bg-danger-soft hover:text-danger' : 'hover:bg-canvas hover:text-ink'}`}>
      <Icon name={icon} className="size-4" />
    </button>
  )
}

function InvoiceDrawer({ open, editing, config, onClose, onSaved }) {
  const events = config?.events ?? []
  const clients = config?.clients ?? []
  const [items, setItems] = useState(
    editing?.items?.length
      ? editing.items.map((it) => ({ description: it.description, quantity: it.quantity, unit_price: it.unit_price, tax: it.tax, discount: it.discount }))
      : [{ description: '', quantity: 1, unit_price: 0, tax: 0, discount: 0 }],
  )
  const { register, handleSubmit, formState: { isSubmitting } } = useForm({
    defaultValues: editing
      ? { title: editing.title ?? '', event_id: editing.event_id ?? '', client_id: editing.client_id ?? '', issue_date: editing.issue_date ?? '', due_date: editing.due_date ?? '', payment_terms: editing.payment_terms ?? '', notes: editing.notes ?? '' }
      : { issue_date: new Date().toISOString().slice(0, 10), due_date: new Date(Date.now() + 14 * 864e5).toISOString().slice(0, 10) },
  })

  const submit = handleSubmit(async (values) => {
    const payload = {
      ...values,
      event_id: values.event_id || null,
      client_id: values.client_id || null,
      items: items.filter((it) => it.description.trim()).map((it) => ({
        description: it.description, quantity: Number(it.quantity || 0), unit_price: Number(it.unit_price || 0),
        tax: Number(it.tax || 0), discount: Number(it.discount || 0),
      })),
    }
    if (editing) await api.put(`/finance/invoices/${editing.id}`, payload)
    else await api.post('/finance/invoices', payload)
    onSaved()
  })

  return (
    <Drawer open={open} onClose={onClose} title={editing ? 'Edit invoice' : 'New invoice'}>
      <form onSubmit={submit} className="space-y-4">
        <Field label="Title" placeholder="e.g. Wedding planning services" {...register('title')} />
        <div className="grid grid-cols-2 gap-4">
          <SelectField label="Event" {...register('event_id')}>
            <option value="">—</option>
            {events.map((e) => <option key={e.id} value={e.id}>{e.title}</option>)}
          </SelectField>
          <SelectField label="Client" {...register('client_id')}>
            <option value="">—</option>
            {clients.map((c) => <option key={c.id} value={c.id}>{c.name}</option>)}
          </SelectField>
        </div>
        <div className="grid grid-cols-2 gap-4">
          <Field type="date" label="Issue date" {...register('issue_date')} />
          <Field type="date" label="Due date" {...register('due_date')} />
        </div>

        <div>
          <p className="mb-1.5 text-sm font-semibold text-ink">Line items</p>
          <LineItemsEditor items={items} onChange={setItems} />
        </div>

        <Field label="Payment terms" {...register('payment_terms')} />
        <Field label="Notes" {...register('notes')} />
        <div className="flex justify-end pt-2">
          <Button type="submit" loading={isSubmitting}>{editing ? 'Save' : 'Create invoice'}</Button>
        </div>
      </form>
    </Drawer>
  )
}

function RecordPaymentDrawer({ invoice, onClose, onSaved }) {
  const { register, handleSubmit, formState: { isSubmitting } } = useForm({
    defaultValues: { amount: invoice?.balance ?? 0, method: 'mobile_money', paid_at: new Date().toISOString().slice(0, 10) },
  })
  if (!invoice) return null

  const submit = handleSubmit(async (values) => {
    await api.post('/finance/payments', {
      direction: 'incoming',
      invoice_id: invoice.id,
      event_id: invoice.event_id,
      amount: Number(values.amount),
      method: values.method,
      transaction_ref: values.transaction_ref || null,
      reference: values.reference || null,
      paid_at: values.paid_at,
    })
    onSaved()
  })

  return (
    <Drawer open={!!invoice} onClose={onClose} title={`Record payment · ${invoice.invoice_number}`}
      description={`Balance due ${formatCurrency(invoice.balance, invoice.currency)}`}>
      <form onSubmit={submit} className="space-y-4">
        <Field type="number" min="0" step="1000" label="Amount" {...register('amount', { required: true })} />
        <SelectField label="Method" {...register('method')} options={PAYMENT_METHODS} />
        <Field label="Transaction reference" placeholder="e.g. mobile money confirmation code" {...register('transaction_ref')} />
        <Field type="date" label="Payment date" {...register('paid_at', { required: true })} />
        <Field label="Note" {...register('reference')} />
        <div className="flex justify-end pt-2">
          <Button type="submit" loading={isSubmitting}>Record payment</Button>
        </div>
      </form>
    </Drawer>
  )
}
