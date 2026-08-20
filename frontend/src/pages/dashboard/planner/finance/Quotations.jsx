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
import { QUOTATION_STATUS } from '../../../../lib/finance'

export default function Quotations() {
  const { data, loading, error, reload } = useResource('/finance/quotations')
  const { data: config } = useResource('/finance/config')
  const [drawer, setDrawer] = useState({ open: false, editing: null })
  const [removing, setRemoving] = useState(null)
  const [busy, setBusy] = useState(false)

  const quotations = data?.quotations ?? []
  const summary = data?.summary

  async function act(q, path, body) {
    await api.post(`/finance/quotations/${q.id}/${path}`, body)
    reload()
  }

  async function remove() {
    setBusy(true)
    try {
      await api.delete(`/finance/quotations/${removing.id}`)
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
          <h2 className="text-lg font-extrabold text-ink">Quotations</h2>
          <p className="text-sm text-muted">Draft, send and track quotes to your clients.</p>
        </div>
        <Button size="sm" onClick={() => setDrawer({ open: true, editing: null })}>
          <Icon name="Plus" className="size-4" /> New quotation
        </Button>
      </div>

      {summary && (
        <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
          <SummaryCard label="Total value" value={summary.total_value} icon="FileText" tone="navy" />
          <SummaryCard label="Accepted value" value={summary.accepted_value} icon="CheckCircle2" tone="emerald" />
          <SummaryCard label="Awaiting decision" value={summary.pending} icon="Clock" money={false} tone="amber" />
          <SummaryCard label="Quotations" value={summary.count} icon="ListChecks" money={false} tone="purple" />
        </div>
      )}

      <LoadState loading={loading} error={error} onRetry={reload}>
        {quotations.length ? (
          <Table>
            <THead>
              <TR>
                <TH>Reference</TH><TH>Client</TH><TH>Event</TH><TH className="text-right">Total</TH>
                <TH>Valid until</TH><TH>Status</TH><TH />
              </TR>
            </THead>
            <TBody>
              {quotations.map((q) => (
                <TR key={q.id}>
                  <TD className="font-semibold">{q.reference}</TD>
                  <TD className="text-muted">{q.client?.name ?? '—'}</TD>
                  <TD className="text-muted">{q.event?.title ?? '—'}</TD>
                  <TD className="text-right tabular-nums">{formatCurrency(q.total, q.currency)}</TD>
                  <TD className="text-muted">{formatDate(q.valid_until)}</TD>
                  <TD><FStatus map={QUOTATION_STATUS} value={q.status} /></TD>
                  <TD>
                    <div className="flex justify-end gap-1">
                      <RowAction icon="Printer" title="Print / PDF" onClick={() => window.open(`/dashboard/planner/finance/print/quotation/${q.id}`, '_blank')} />
                      {q.status === 'draft' && <RowAction icon="Send" title="Send" onClick={() => act(q, 'send')} />}
                      {['sent', 'viewed'].includes(q.status) && <RowAction icon="Check" title="Mark accepted" onClick={() => act(q, 'decide', { status: 'accepted' })} />}
                      {['sent', 'viewed'].includes(q.status) && <RowAction icon="Ban" title="Mark rejected" onClick={() => act(q, 'decide', { status: 'rejected' })} />}
                      {q.status === 'accepted' && <RowAction icon="ClipboardList" title="Convert to invoice" onClick={() => act(q, 'convert')} />}
                      {!q.is_decided && q.status === 'draft' && <RowAction icon="PenLine" title="Edit" onClick={() => setDrawer({ open: true, editing: q })} />}
                      <RowAction icon="Trash2" title="Delete" danger onClick={() => setRemoving(q)} />
                    </div>
                  </TD>
                </TR>
              ))}
            </TBody>
          </Table>
        ) : (
          <EmptyState icon="FileText" title="No quotations yet" description="Create a professional quotation to send to a client."
            action={<Button size="sm" onClick={() => setDrawer({ open: true, editing: null })}><Icon name="Plus" className="size-4" /> New quotation</Button>} />
        )}
      </LoadState>

      <QuotationDrawer
        key={drawer.editing?.id ?? 'new'}
        open={drawer.open}
        editing={drawer.editing}
        config={config}
        onClose={() => setDrawer({ open: false, editing: null })}
        onSaved={() => { setDrawer({ open: false, editing: null }); reload() }}
      />

      <ConfirmDialog open={!!removing} onClose={() => setRemoving(null)} onConfirm={remove}
        title="Delete quotation?" confirmLabel="Delete" loading={busy} />
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

function QuotationDrawer({ open, editing, config, onClose, onSaved }) {
  const events = config?.events ?? []
  const clients = config?.clients ?? []
  const [items, setItems] = useState(
    editing?.items?.length
      ? editing.items.map((it) => ({ description: it.description, quantity: it.quantity, unit_price: it.unit_price, tax: it.tax, discount: it.discount }))
      : [{ description: '', quantity: 1, unit_price: 0, tax: 0, discount: 0 }],
  )
  const { register, handleSubmit, formState: { isSubmitting } } = useForm({
    defaultValues: editing
      ? { title: editing.title ?? '', event_id: editing.event_id ?? '', client_id: editing.client_id ?? '', valid_until: editing.valid_until ?? '', terms: editing.terms ?? '', notes: editing.notes ?? '' }
      : { valid_until: new Date(Date.now() + 20 * 864e5).toISOString().slice(0, 10) },
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
    if (editing) await api.put(`/finance/quotations/${editing.id}`, payload)
    else await api.post('/finance/quotations', payload)
    onSaved()
  })

  return (
    <Drawer open={open} onClose={onClose} title={editing ? 'Edit quotation' : 'New quotation'}>
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
        <Field type="date" label="Valid until" {...register('valid_until')} />

        <div>
          <p className="mb-1.5 text-sm font-semibold text-ink">Line items</p>
          <LineItemsEditor items={items} onChange={setItems} />
        </div>

        <Field label="Terms & conditions" {...register('terms')} />
        <Field label="Notes" {...register('notes')} />
        <div className="flex justify-end pt-2">
          <Button type="submit" loading={isSubmitting}>{editing ? 'Save' : 'Create quotation'}</Button>
        </div>
      </form>
    </Drawer>
  )
}
