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
import { useResource } from '../../../../lib/useResource'
import { api } from '../../../../lib/api'
import { formatCurrency, formatDate } from '../../../../lib/format'
import { EXPENSE_STATUS, FINANCE_CATEGORIES, PAYMENT_METHODS } from '../../../../lib/finance'

export default function Expenses() {
  const { data, loading, error, reload } = useResource('/finance/expenses')
  const { data: config } = useResource('/finance/config')
  const [drawer, setDrawer] = useState({ open: false, editing: null })
  const [removing, setRemoving] = useState(null)
  const [busy, setBusy] = useState(false)

  const expenses = data?.expenses ?? []
  const summary = data?.summary

  async function transition(expense, action, reason) {
    await api.post(`/finance/expenses/${expense.id}/transition`, { action, reason })
    reload()
  }

  async function remove() {
    setBusy(true)
    try {
      await api.delete(`/finance/expenses/${removing.id}`)
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
          <h2 className="text-lg font-extrabold text-ink">Expenses</h2>
          <p className="text-sm text-muted">Record and approve spend across your events.</p>
        </div>
        <Button size="sm" onClick={() => setDrawer({ open: true, editing: null })}>
          <Icon name="Plus" className="size-4" /> New expense
        </Button>
      </div>

      {summary && (
        <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
          <SummaryCard label="Total expenses" value={summary.total} icon="ReceiptText" tone="navy" />
          <SummaryCard label="Paid" value={summary.paid} icon="CheckCircle2" tone="emerald" />
          <SummaryCard label="Pending approval" value={summary.pending} icon="Clock" tone="amber" />
          <SummaryCard label="Records" value={summary.count} icon="ListChecks" money={false} tone="purple" />
        </div>
      )}

      <LoadState loading={loading} error={error} onRetry={reload}>
        {expenses.length ? (
          <Table>
            <THead>
              <TR>
                <TH>Expense #</TH><TH>Event</TH><TH>Category</TH><TH className="text-right">Total</TH>
                <TH>Date</TH><TH>Status</TH><TH />
              </TR>
            </THead>
            <TBody>
              {expenses.map((e) => (
                <TR key={e.id}>
                  <TD className="font-semibold">{e.expense_number}</TD>
                  <TD className="text-muted">{e.event?.title ?? '—'}</TD>
                  <TD>{e.category}</TD>
                  <TD className="text-right tabular-nums">{formatCurrency(e.total, e.currency)}</TD>
                  <TD className="text-muted">{formatDate(e.expense_date)}</TD>
                  <TD><FStatus map={EXPENSE_STATUS} value={e.status} /></TD>
                  <TD>
                    <div className="flex justify-end gap-1">
                      {e.status === 'draft' && <RowAction icon="Send" title="Submit" onClick={() => transition(e, 'submit')} />}
                      {e.status === 'submitted' && <RowAction icon="Check" title="Approve" onClick={() => transition(e, 'approve')} />}
                      {e.status === 'approved' && <RowAction icon="CircleDollarSign" title="Mark paid" onClick={() => transition(e, 'pay')} />}
                      {['draft', 'submitted'].includes(e.status) && (
                        <RowAction icon="PenLine" title="Edit" onClick={() => setDrawer({ open: true, editing: e })} />
                      )}
                      <RowAction icon="Trash2" title="Delete" danger onClick={() => setRemoving(e)} />
                    </div>
                  </TD>
                </TR>
              ))}
            </TBody>
          </Table>
        ) : (
          <EmptyState icon="ReceiptText" title="No expenses yet" description="Log your first expense to start tracking spend."
            action={<Button size="sm" onClick={() => setDrawer({ open: true, editing: null })}><Icon name="Plus" className="size-4" /> New expense</Button>} />
        )}
      </LoadState>

      <ExpenseDrawer
        key={drawer.editing?.id ?? 'new'}
        open={drawer.open}
        editing={drawer.editing}
        config={config}
        onClose={() => setDrawer({ open: false, editing: null })}
        onSaved={() => { setDrawer({ open: false, editing: null }); reload() }}
      />

      <ConfirmDialog open={!!removing} onClose={() => setRemoving(null)} onConfirm={remove}
        title="Delete expense?" confirmLabel="Delete" loading={busy} />
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

function ExpenseDrawer({ open, editing, config, onClose, onSaved }) {
  const events = config?.events ?? []
  const { register, handleSubmit, formState: { errors, isSubmitting } } = useForm({
    defaultValues: editing
      ? {
          event_id: editing.event_id, category: editing.category, description: editing.description,
          amount: editing.amount, tax: editing.tax, payment_method: editing.payment_method ?? '',
          expense_date: editing.expense_date, notes: editing.notes ?? '',
        }
      : { expense_date: new Date().toISOString().slice(0, 10), tax: 0 },
  })

  const submit = handleSubmit(async (values) => {
    const payload = { ...values, amount: Number(values.amount), tax: Number(values.tax || 0) }
    if (!payload.payment_method) delete payload.payment_method
    if (editing) await api.put(`/finance/expenses/${editing.id}`, payload)
    else await api.post('/finance/expenses', payload)
    onSaved()
  })

  return (
    <Drawer open={open} onClose={onClose} title={editing ? 'Edit expense' : 'New expense'}>
      <form onSubmit={submit} className="space-y-4">
        <SelectField label="Event" error={errors.event_id?.message} {...register('event_id', { required: 'Choose an event' })}>
          <option value="">Select an event…</option>
          {events.map((e) => <option key={e.id} value={e.id}>{e.title}</option>)}
        </SelectField>
        <SelectField label="Category" error={errors.category?.message} {...register('category', { required: 'Choose a category' })}>
          <option value="">Select a category…</option>
          {FINANCE_CATEGORIES.map((c) => <option key={c} value={c}>{c}</option>)}
        </SelectField>
        <Field label="Description" error={errors.description?.message} {...register('description', { required: 'A description is required' })} />
        <div className="grid grid-cols-2 gap-4">
          <Field type="number" min="0" step="1000" label="Amount" error={errors.amount?.message} {...register('amount', { required: 'Required' })} />
          <Field type="number" min="0" step="1000" label="Tax" {...register('tax')} />
        </div>
        <div className="grid grid-cols-2 gap-4">
          <SelectField label="Payment method" {...register('payment_method')}>
            <option value="">—</option>
            {PAYMENT_METHODS.map((m) => <option key={m.value} value={m.value}>{m.label}</option>)}
          </SelectField>
          <Field type="date" label="Date" error={errors.expense_date?.message} {...register('expense_date', { required: 'Required' })} />
        </div>
        <Field label="Notes" {...register('notes')} />
        <div className="flex justify-end pt-2">
          <Button type="submit" loading={isSubmitting}>{editing ? 'Save' : 'Record expense'}</Button>
        </div>
      </form>
    </Drawer>
  )
}
