import { useEffect, useState } from 'react'
import { useOutletContext } from 'react-router-dom'
import { useForm } from 'react-hook-form'
import Button from '../../../../components/ui/Button'
import Icon from '../../../../components/ui/Icon'
import Badge from '../../../../components/ui/Badge'
import Card from '../../../../components/ui/Card'
import Drawer from '../../../../components/ui/Drawer'
import ConfirmDialog from '../../../../components/ui/ConfirmDialog'
import EmptyState from '../../../../components/ui/EmptyState'
import { Field, SelectField } from '../../../../components/ui/Field'
import { Table, THead, TH, TBody, TR, TD } from '../../../../components/ui/Table'
import { AskAiButton, GenerateAiButton } from '../../../../components/ai/InlineAiButtons'
import { api } from '../../../../lib/api'
import { formatCurrency } from '../../../../lib/format'
import { BUDGET_STATUS_OPTIONS, BUDGET_STATUS_TONE } from '../../../../lib/eventConstants'

export default function Budget() {
  const { event, reload } = useOutletContext()
  const items = event.budget_items ?? []
  const [drawer, setDrawer] = useState({ open: false, editing: null })
  const [removing, setRemoving] = useState(null)
  const [busy, setBusy] = useState(false)

  const estimated = items.reduce((s, i) => s + Number(i.estimated_cost), 0)
  const actual = items.reduce((s, i) => s + Number(i.actual_cost), 0)
  const total = Number(event.budget.total)

  async function remove() {
    setBusy(true)
    try {
      await api.delete(`/events/${event.id}/budget-items/${removing.id}`)
      setRemoving(null)
      reload()
    } finally {
      setBusy(false)
    }
  }

  return (
    <div className="space-y-5">
      <div className="flex items-center justify-between">
        <div>
          <h2 className="text-lg font-extrabold text-ink">Budget</h2>
          <p className="text-sm text-muted">Track estimated vs. actual spend across categories.</p>
        </div>
        <div className="flex flex-wrap items-center gap-2">
          <AskAiButton eventId={event.id} prompt={`Is the budget for ${event.title} on track? Where can I save?`} label="Ask AI" />
          <GenerateAiButton templateKey="budget_outline" eventId={event.id} label="Budget guide" />
          <Button size="sm" onClick={() => setDrawer({ open: true, editing: null })}>
            <Icon name="Plus" className="size-4" /> Add item
          </Button>
        </div>
      </div>

      <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <Summary label="Total budget" value={total} />
        <Summary label="Estimated" value={estimated} />
        <Summary label="Actual spend" value={actual} />
        <Summary label="Remaining" value={total - actual} tone="emerald" />
      </div>

      {items.length ? (
        <Table>
          <THead>
            <TR>
              <TH>Category</TH><TH>Description</TH><TH className="text-right">Estimated</TH>
              <TH className="text-right">Actual</TH><TH>Status</TH><TH />
            </TR>
          </THead>
          <TBody>
            {items.map((i) => (
              <TR key={i.id}>
                <TD className="font-semibold">{i.category}</TD>
                <TD className="text-muted">{i.description}</TD>
                <TD className="text-right tabular-nums">{formatCurrency(i.estimated_cost)}</TD>
                <TD className="text-right tabular-nums">{formatCurrency(i.actual_cost)}</TD>
                <TD><Badge tone={BUDGET_STATUS_TONE[i.status] ?? 'muted'}>{i.status_label}</Badge></TD>
                <TD>
                  <div className="flex justify-end gap-1">
                    <button type="button" onClick={() => setDrawer({ open: true, editing: i })} className="grid size-8 place-items-center rounded-btn text-muted hover:bg-canvas hover:text-ink"><Icon name="PenLine" className="size-4" /></button>
                    <button type="button" onClick={() => setRemoving(i)} className="grid size-8 place-items-center rounded-btn text-muted hover:bg-danger-soft hover:text-danger"><Icon name="Trash2" className="size-4" /></button>
                  </div>
                </TD>
              </TR>
            ))}
          </TBody>
        </Table>
      ) : (
        <EmptyState icon="Wallet" title="No budget items yet" description="Break the budget down by category to track spend."
          action={<Button size="sm" onClick={() => setDrawer({ open: true, editing: null })}><Icon name="Plus" className="size-4" /> Add item</Button>} />
      )}

      <BudgetDrawer
        key={drawer.editing?.id ?? 'new'}
        open={drawer.open}
        editing={drawer.editing}
        eventId={event.id}
        onClose={() => setDrawer({ open: false, editing: null })}
        onSaved={() => { setDrawer({ open: false, editing: null }); reload() }}
      />

      <ConfirmDialog open={!!removing} onClose={() => setRemoving(null)} onConfirm={remove}
        title="Delete budget item?" confirmLabel="Delete" loading={busy} />
    </div>
  )
}

function Summary({ label, value, tone }) {
  return (
    <Card className="p-5">
      <p className="text-sm text-muted">{label}</p>
      <p className={`mt-1 text-xl font-extrabold tabular-nums ${tone === 'emerald' ? 'text-emerald-600' : 'text-ink'}`}>{formatCurrency(value)}</p>
    </Card>
  )
}

function BudgetDrawer({ open, editing, eventId, onClose, onSaved }) {
  const [categories, setCategories] = useState([])
  const { register, handleSubmit, formState: { errors, isSubmitting } } = useForm({
    defaultValues: editing
      ? { category: editing.category, description: editing.description, estimated_cost: editing.estimated_cost, actual_cost: editing.actual_cost, status: editing.status }
      : { status: 'planned', estimated_cost: '', actual_cost: '' },
  })

  useEffect(() => {
    if (open) api.get('/categories').then((r) => setCategories(r.data.data.budget ?? [])).catch(() => {})
  }, [open])

  const submit = handleSubmit(async (values) => {
    const payload = Object.fromEntries(Object.entries(values).filter(([, v]) => v !== ''))
    if (editing) {
      await api.put(`/events/${eventId}/budget-items/${editing.id}`, payload)
    } else {
      await api.post(`/events/${eventId}/budget-items`, payload)
    }
    onSaved()
  })

  return (
    <Drawer open={open} onClose={onClose} title={editing ? 'Edit budget item' : 'Add budget item'}>
      <form onSubmit={submit} className="space-y-4">
        <SelectField label="Category" error={errors.category?.message} {...register('category', { required: 'Choose a category' })}>
          <option value="">Select a category…</option>
          {categories.map((c) => <option key={c} value={c}>{c}</option>)}
        </SelectField>
        <Field label="Description" error={errors.description?.message} {...register('description', { required: 'A description is required' })} />
        <div className="grid grid-cols-2 gap-4">
          <Field type="number" min="0" step="1000" label="Estimated cost" {...register('estimated_cost', { required: true })} />
          <Field type="number" min="0" step="1000" label="Actual cost" {...register('actual_cost')} />
        </div>
        <SelectField label="Status" options={BUDGET_STATUS_OPTIONS} {...register('status')} />
        <div className="flex justify-end pt-2">
          <Button type="submit" loading={isSubmitting}>{editing ? 'Save' : 'Add item'}</Button>
        </div>
      </form>
    </Drawer>
  )
}
