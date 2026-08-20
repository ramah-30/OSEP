import { useMemo, useState } from 'react'
import { useForm } from 'react-hook-form'
import Button from '../../../../components/ui/Button'
import Icon from '../../../../components/ui/Icon'
import Drawer from '../../../../components/ui/Drawer'
import ConfirmDialog from '../../../../components/ui/ConfirmDialog'
import EmptyState from '../../../../components/ui/EmptyState'
import Tabs from '../../../../components/ui/Tabs'
import Badge from '../../../../components/ui/Badge'
import { Field, SelectField } from '../../../../components/ui/Field'
import { Table, THead, TH, TBody, TR, TD } from '../../../../components/ui/Table'
import LoadState from '../../../../components/dashboard/LoadState'
import { SummaryCard, FStatus } from '../../../../components/finance/FinanceBits'
import { useResource } from '../../../../lib/useResource'
import { api } from '../../../../lib/api'
import { formatCurrency, formatDate } from '../../../../lib/format'
import { PAYMENT_STATUS, PAYMENT_METHODS } from '../../../../lib/finance'

export default function Payments() {
  const { data, loading, error, reload } = useResource('/finance/payments')
  const { data: config } = useResource('/finance/config')
  const [tab, setTab] = useState('all')
  const [drawerOpen, setDrawerOpen] = useState(false)
  const [removing, setRemoving] = useState(null)
  const [busy, setBusy] = useState(false)

  const payments = data?.payments ?? []
  const summary = data?.summary

  const filtered = useMemo(
    () => (tab === 'all' ? payments : payments.filter((p) => p.direction === tab)),
    [payments, tab],
  )

  async function remove() {
    setBusy(true)
    try {
      await api.delete(`/finance/payments/${removing.id}`)
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
          <h2 className="text-lg font-extrabold text-ink">Payments</h2>
          <p className="text-sm text-muted">Money in from clients and out to vendors.</p>
        </div>
        <Button size="sm" onClick={() => setDrawerOpen(true)}>
          <Icon name="Plus" className="size-4" /> Record payment
        </Button>
      </div>

      {summary && (
        <div className="grid gap-4 sm:grid-cols-2">
          <SummaryCard label="Received from clients" value={summary.received} icon="CircleDollarSign" tone="emerald" />
          <SummaryCard label="Paid to vendors" value={summary.paid_out} icon="Handshake" tone="purple" />
        </div>
      )}

      <Tabs
        value={tab}
        onChange={setTab}
        tabs={[
          { value: 'all', label: 'All' },
          { value: 'incoming', label: 'Client payments' },
          { value: 'outgoing', label: 'Vendor payments' },
        ]}
      />

      <LoadState loading={loading} error={error} onRetry={reload}>
        {filtered.length ? (
          <Table>
            <THead>
              <TR>
                <TH>Payment #</TH><TH>Direction</TH><TH>Party</TH><TH>Method</TH>
                <TH className="text-right">Amount</TH><TH>Date</TH><TH>Status</TH><TH />
              </TR>
            </THead>
            <TBody>
              {filtered.map((p) => (
                <TR key={p.id}>
                  <TD className="font-semibold">{p.payment_number}</TD>
                  <TD>
                    <Badge tone={p.direction === 'incoming' ? 'emerald' : 'purple'}>
                      {p.direction === 'incoming' ? 'In' : 'Out'}
                    </Badge>
                  </TD>
                  <TD className="text-muted">{p.party_name ?? p.vendor?.name ?? p.invoice?.invoice_number ?? '—'}</TD>
                  <TD className="text-muted">{p.method_label}</TD>
                  <TD className="text-right tabular-nums">{formatCurrency(p.amount, p.currency)}</TD>
                  <TD className="text-muted">{formatDate(p.paid_at)}</TD>
                  <TD><FStatus map={PAYMENT_STATUS} value={p.status} /></TD>
                  <TD>
                    <div className="flex justify-end gap-1">
                      {p.receipt && (
                        <RowAction icon="ReceiptText" title="Print receipt"
                          onClick={() => window.open(`/dashboard/planner/finance/print/receipt/${p.receipt.id}`, '_blank')} />
                      )}
                      <RowAction icon="Trash2" title="Delete" danger onClick={() => setRemoving(p)} />
                    </div>
                  </TD>
                </TR>
              ))}
            </TBody>
          </Table>
        ) : (
          <EmptyState icon="CreditCard" title="No payments recorded" description="Record a client or vendor payment to see it here."
            action={<Button size="sm" onClick={() => setDrawerOpen(true)}><Icon name="Plus" className="size-4" /> Record payment</Button>} />
        )}
      </LoadState>

      <PaymentDrawer
        open={drawerOpen}
        config={config}
        onClose={() => setDrawerOpen(false)}
        onSaved={() => { setDrawerOpen(false); reload() }}
      />

      <ConfirmDialog open={!!removing} onClose={() => setRemoving(null)} onConfirm={remove}
        title="Delete payment?" description="Any receipt is removed and invoice balances are recalculated." confirmLabel="Delete" loading={busy} />
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

function PaymentDrawer({ open, config, onClose, onSaved }) {
  const events = config?.events ?? []
  const { register, handleSubmit, watch, reset, formState: { isSubmitting } } = useForm({
    defaultValues: { direction: 'incoming', method: 'mobile_money', paid_at: new Date().toISOString().slice(0, 10) },
  })
  const direction = watch('direction')

  const submit = handleSubmit(async (values) => {
    await api.post('/finance/payments', {
      direction: values.direction,
      event_id: values.event_id || null,
      party_name: values.party_name || null,
      amount: Number(values.amount),
      method: values.method,
      transaction_ref: values.transaction_ref || null,
      reference: values.reference || null,
      paid_at: values.paid_at,
    })
    reset()
    onSaved()
  })

  return (
    <Drawer open={open} onClose={onClose} title="Record payment">
      <form onSubmit={submit} className="space-y-4">
        <SelectField label="Direction" {...register('direction')}>
          <option value="incoming">Client payment (in)</option>
          <option value="outgoing">Vendor payment (out)</option>
        </SelectField>
        <SelectField label="Event" {...register('event_id')}>
          <option value="">—</option>
          {events.map((e) => <option key={e.id} value={e.id}>{e.title}</option>)}
        </SelectField>
        <Field label={direction === 'incoming' ? 'Payer' : 'Payee'} placeholder="Name" {...register('party_name')} />
        <div className="grid grid-cols-2 gap-4">
          <Field type="number" min="0" step="1000" label="Amount" {...register('amount', { required: true })} />
          <SelectField label="Method" {...register('method')} options={PAYMENT_METHODS} />
        </div>
        <Field label="Transaction reference" {...register('transaction_ref')} />
        <Field type="date" label="Payment date" {...register('paid_at', { required: true })} />
        <Field label="Note" {...register('reference')} />
        <div className="flex justify-end pt-2">
          <Button type="submit" loading={isSubmitting}>Record payment</Button>
        </div>
      </form>
    </Drawer>
  )
}
