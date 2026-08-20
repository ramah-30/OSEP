import { useState } from 'react'
import { useForm } from 'react-hook-form'
import Button from '../../../../components/ui/Button'
import Icon from '../../../../components/ui/Icon'
import Card from '../../../../components/ui/Card'
import Drawer from '../../../../components/ui/Drawer'
import ConfirmDialog from '../../../../components/ui/ConfirmDialog'
import EmptyState from '../../../../components/ui/EmptyState'
import { Field, SelectField } from '../../../../components/ui/Field'
import { Table, THead, TH, TBody, TR, TD } from '../../../../components/ui/Table'
import LoadState from '../../../../components/dashboard/LoadState'
import { SummaryCard, FStatus } from '../../../../components/finance/FinanceBits'
import { useResource } from '../../../../lib/useResource'
import { api } from '../../../../lib/api'
import { formatCurrency } from '../../../../lib/format'
import { BUDGET_STATUS, FINANCE_CATEGORIES } from '../../../../lib/finance'

export default function Budgets() {
  const { data, loading, error, reload } = useResource('/finance/budgets')
  const [openEvent, setOpenEvent] = useState(null)

  const budgets = data?.budgets ?? []

  if (openEvent) {
    return <BudgetDetail eventId={openEvent} onBack={() => { setOpenEvent(null); reload() }} />
  }

  return (
    <div className="space-y-5">
      <div>
        <h2 className="text-lg font-extrabold text-ink">Budgets</h2>
        <p className="text-sm text-muted">One master budget per event — estimated, approved and actual.</p>
      </div>

      <LoadState loading={loading} error={error} onRetry={reload}>
        {budgets.length ? (
          <Table>
            <THead>
              <TR>
                <TH>Event</TH><TH>Status</TH><TH className="text-right">Budget</TH>
                <TH className="text-right">Actual</TH><TH className="text-right">Remaining</TH><TH>Utilisation</TH><TH />
              </TR>
            </THead>
            <TBody>
              {budgets.map((b) => (
                <TR key={b.id}>
                  <TD className="font-semibold">{b.event?.title ?? '—'}</TD>
                  <TD><FStatus map={BUDGET_STATUS} value={b.status} /></TD>
                  <TD className="text-right tabular-nums">{formatCurrency(b.summary.budget_total, b.currency)}</TD>
                  <TD className="text-right tabular-nums">{formatCurrency(b.summary.actual, b.currency)}</TD>
                  <TD className="text-right tabular-nums">{formatCurrency(b.summary.remaining, b.currency)}</TD>
                  <TD>
                    <div className="flex items-center gap-2">
                      <div className="h-2 w-20 overflow-hidden rounded-full bg-canvas">
                        <div className={`h-full rounded-full ${b.summary.utilization > 100 ? 'bg-danger' : 'bg-navy-600'}`}
                          style={{ width: `${Math.min(100, b.summary.utilization)}%` }} />
                      </div>
                      <span className="text-xs tabular-nums text-muted">{b.summary.utilization}%</span>
                    </div>
                  </TD>
                  <TD>
                    <div className="flex justify-end">
                      <Button size="sm" variant="secondary" onClick={() => setOpenEvent(b.event_id)}>Open</Button>
                    </div>
                  </TD>
                </TR>
              ))}
            </TBody>
          </Table>
        ) : (
          <EmptyState icon="Wallet" title="No budgets yet" description="Budgets appear here once your events have budget lines." />
        )}
      </LoadState>
    </div>
  )
}

const WORKFLOW = {
  draft: [{ action: 'submit', label: 'Submit for approval', icon: 'Send' }],
  pending_approval: [
    { action: 'approve', label: 'Approve', icon: 'Check' },
    { action: 'reopen', label: 'Reopen', icon: 'ArrowLeft' },
  ],
  approved: [
    { action: 'lock', label: 'Lock', icon: 'Lock' },
    { action: 'reopen', label: 'Reopen', icon: 'ArrowLeft' },
  ],
  locked: [{ action: 'archive', label: 'Archive', icon: 'Archive' }],
  archived: [{ action: 'reopen', label: 'Reopen', icon: 'ArrowLeft' }],
}

function BudgetDetail({ eventId, onBack }) {
  const { data, loading, error, reload } = useResource(`/finance/budgets/${eventId}`)
  const [drawer, setDrawer] = useState({ open: false, editing: null })
  const [removing, setRemoving] = useState(null)
  const [busy, setBusy] = useState(false)

  const budget = data?.budget
  const summary = data?.summary

  async function transition(action) {
    await api.post(`/finance/budgets/${eventId}/transition`, { action })
    reload()
  }

  async function remove() {
    setBusy(true)
    try {
      await api.delete(`/finance/budgets/${eventId}/items/${removing.id}`)
      setRemoving(null)
      reload()
    } finally {
      setBusy(false)
    }
  }

  return (
    <div className="space-y-5">
      <button onClick={onBack} className="flex items-center gap-1.5 text-sm font-semibold text-muted hover:text-ink">
        <Icon name="ArrowLeft" className="size-4" /> All budgets
      </button>

      <LoadState loading={loading} error={error} onRetry={reload}>
        {budget && (
          <>
            <div className="flex flex-wrap items-center justify-between gap-3">
              <div className="flex items-center gap-3">
                <h2 className="text-lg font-extrabold text-ink">{budget.event?.title ?? 'Budget'}</h2>
                <FStatus map={BUDGET_STATUS} value={budget.status} />
              </div>
              <div className="flex flex-wrap gap-2">
                {(WORKFLOW[budget.status] ?? []).map((w) => (
                  <Button key={w.action} size="sm" variant="secondary" onClick={() => transition(w.action)}>
                    <Icon name={w.icon} className="size-4" /> {w.label}
                  </Button>
                ))}
              </div>
            </div>

            <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
              <SummaryCard label="Budget total" value={summary.budget_total} icon="Wallet" tone="navy" />
              <SummaryCard label="Estimated" value={summary.estimated} icon="ClipboardList" tone="navy" />
              <SummaryCard label="Actual spend" value={summary.actual} icon="ReceiptText" tone="amber" />
              <SummaryCard label="Remaining" value={summary.remaining} icon="TrendingUp" tone={summary.remaining >= 0 ? 'emerald' : 'danger'} />
            </div>

            <MasterForm eventId={eventId} budget={budget} onSaved={reload} />

            <div className="flex items-center justify-between">
              <h3 className="font-bold text-ink">Budget lines</h3>
              {budget.is_editable && (
                <Button size="sm" onClick={() => setDrawer({ open: true, editing: null })}>
                  <Icon name="Plus" className="size-4" /> Add line
                </Button>
              )}
            </div>

            {budget.items?.length ? (
              <Table>
                <THead>
                  <TR>
                    <TH>Category</TH><TH>Description</TH><TH className="text-right">Estimated</TH>
                    <TH className="text-right">Actual</TH><TH className="text-right">Variance</TH><TH />
                  </TR>
                </THead>
                <TBody>
                  {budget.items.map((i) => (
                    <TR key={i.id}>
                      <TD className="font-semibold">{i.category}</TD>
                      <TD className="text-muted">{i.description}</TD>
                      <TD className="text-right tabular-nums">{formatCurrency(i.estimated_cost)}</TD>
                      <TD className="text-right tabular-nums">{formatCurrency(i.actual_cost)}</TD>
                      <TD className={`text-right tabular-nums ${i.variance > 0 ? 'text-danger' : 'text-emerald-600'}`}>
                        {i.variance > 0 ? '+' : ''}{formatCurrency(i.variance)}
                      </TD>
                      <TD>
                        <div className="flex justify-end gap-1">
                          <button type="button" title="Edit" onClick={() => setDrawer({ open: true, editing: i })}
                            className="grid size-8 place-items-center rounded-btn text-muted hover:bg-canvas hover:text-ink"><Icon name="PenLine" className="size-4" /></button>
                          <button type="button" title="Delete" onClick={() => setRemoving(i)}
                            className="grid size-8 place-items-center rounded-btn text-muted hover:bg-danger-soft hover:text-danger"><Icon name="Trash2" className="size-4" /></button>
                        </div>
                      </TD>
                    </TR>
                  ))}
                </TBody>
              </Table>
            ) : (
              <EmptyState icon="Wallet" title="No budget lines" description="Break the budget down by category." />
            )}

            <BudgetItemDrawer
              key={drawer.editing?.id ?? 'new'}
              open={drawer.open}
              editing={drawer.editing}
              eventId={eventId}
              onClose={() => setDrawer({ open: false, editing: null })}
              onSaved={() => { setDrawer({ open: false, editing: null }); reload() }}
            />

            <ConfirmDialog open={!!removing} onClose={() => setRemoving(null)} onConfirm={remove}
              title="Delete budget line?" confirmLabel="Delete" loading={busy} />
          </>
        )}
      </LoadState>
    </div>
  )
}

function MasterForm({ eventId, budget, onSaved }) {
  const { register, handleSubmit, formState: { isSubmitting } } = useForm({
    defaultValues: {
      estimated_total: budget.estimated_total ?? 0,
      revised_total: budget.revised_total ?? '',
      final_total: budget.final_total ?? '',
      notes: budget.notes ?? '',
    },
  })

  const submit = handleSubmit(async (values) => {
    const payload = {
      estimated_total: Number(values.estimated_total || 0),
      revised_total: values.revised_total === '' ? null : Number(values.revised_total),
      final_total: values.final_total === '' ? null : Number(values.final_total),
      notes: values.notes,
    }
    await api.put(`/finance/budgets/${eventId}`, payload)
    onSaved()
  })

  return (
    <Card className="p-5">
      <form onSubmit={submit} className="space-y-4">
        <div className="grid gap-4 sm:grid-cols-3">
          <Field type="number" min="0" step="1000" label="Estimated budget" disabled={!budget.is_editable} {...register('estimated_total')} />
          <Field type="number" min="0" step="1000" label="Revised budget" disabled={!budget.is_editable} {...register('revised_total')} />
          <Field type="number" min="0" step="1000" label="Final budget" disabled={!budget.is_editable} {...register('final_total')} />
        </div>
        <Field label="Notes" disabled={!budget.is_editable} {...register('notes')} />
        {budget.is_editable && (
          <div className="flex justify-end">
            <Button type="submit" size="sm" loading={isSubmitting}>Save budget</Button>
          </div>
        )}
      </form>
    </Card>
  )
}

function BudgetItemDrawer({ open, editing, eventId, onClose, onSaved }) {
  const { register, handleSubmit, formState: { errors, isSubmitting } } = useForm({
    defaultValues: editing
      ? { category: editing.category, description: editing.description, estimated_cost: editing.estimated_cost, approved_cost: editing.approved_cost, actual_cost: editing.actual_cost, notes: editing.notes ?? '' }
      : { estimated_cost: '', actual_cost: 0 },
  })

  const submit = handleSubmit(async (values) => {
    const payload = Object.fromEntries(Object.entries(values).filter(([, v]) => v !== ''))
    if (editing) await api.put(`/finance/budgets/${eventId}/items/${editing.id}`, payload)
    else await api.post(`/finance/budgets/${eventId}/items`, payload)
    onSaved()
  })

  return (
    <Drawer open={open} onClose={onClose} title={editing ? 'Edit budget line' : 'Add budget line'}>
      <form onSubmit={submit} className="space-y-4">
        <SelectField label="Category" error={errors.category?.message} {...register('category', { required: 'Choose a category' })}>
          <option value="">Select a category…</option>
          {FINANCE_CATEGORIES.map((c) => <option key={c} value={c}>{c}</option>)}
        </SelectField>
        <Field label="Description" error={errors.description?.message} {...register('description', { required: 'Required' })} />
        <div className="grid grid-cols-2 gap-4">
          <Field type="number" min="0" step="1000" label="Estimated cost" error={errors.estimated_cost?.message} {...register('estimated_cost', { required: 'Required' })} />
          <Field type="number" min="0" step="1000" label="Approved cost" {...register('approved_cost')} />
        </div>
        <Field type="number" min="0" step="1000" label="Actual cost" {...register('actual_cost')} />
        <Field label="Notes" {...register('notes')} />
        <div className="flex justify-end pt-2">
          <Button type="submit" loading={isSubmitting}>{editing ? 'Save' : 'Add line'}</Button>
        </div>
      </form>
    </Drawer>
  )
}
