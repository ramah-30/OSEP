import { useState } from 'react'
import { useOutletContext } from 'react-router-dom'
import { useForm } from 'react-hook-form'
import { useTranslation } from 'react-i18next'
import Button from '../../../../components/ui/Button'
import Icon from '../../../../components/ui/Icon'
import Badge from '../../../../components/ui/Badge'
import Drawer from '../../../../components/ui/Drawer'
import EmptyState from '../../../../components/ui/EmptyState'
import ConfirmDialog from '../../../../components/ui/ConfirmDialog'
import { Field, SelectField } from '../../../../components/ui/Field'
import Textarea from '../../../../components/ui/Textarea'
import { AskAiButton, GenerateAiButton } from '../../../../components/ai/InlineAiButtons'
import { api } from '../../../../lib/api'
import { formatDate } from '../../../../lib/format'
import { MILESTONE_STATUS_OPTIONS, MILESTONE_TONE } from '../../../../lib/eventConstants'

export default function Timeline() {
  const { t } = useTranslation()
  const { event, reload } = useOutletContext()
  const [drawer, setDrawer] = useState({ open: false, editing: null })
  const [removing, setRemoving] = useState(null)
  const [busy, setBusy] = useState(false)
  const milestones = event.milestones ?? []

  function openCreate() {
    setDrawer({ open: true, editing: null })
  }
  function openEdit(m) {
    setDrawer({ open: true, editing: m })
  }

  async function remove() {
    setBusy(true)
    try {
      await api.delete(`/events/${event.id}/milestones/${removing.id}`)
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
          <h2 className="text-lg font-extrabold text-ink">{t('timeline.timeline')}</h2>
          <p className="text-sm text-muted">{t('timeline.timelineDescription')}</p>
        </div>
        <div className="flex flex-wrap items-center gap-2">
          <AskAiButton eventId={event.id} prompt={`What's overdue or coming up for ${event.title}?`} label={t('approvals.askAI')} />
          <GenerateAiButton templateKey="planning_timeline" eventId={event.id} label={t('timeline.timeline')} />
          <GenerateAiButton templateKey="run_of_show" eventId={event.id} label={t('timeline.runOfShow')} />
          <Button size="sm" onClick={openCreate}><Icon name="Plus" className="size-4" /> {t('timeline.addMilestone')}</Button>
        </div>
      </div>

      {milestones.length ? (
        <ol className="relative space-y-4 border-l border-line pl-6">
          {milestones.map((m) => (
            <li key={m.id} className="relative">
              <span className="absolute -left-[27px] top-1.5 grid size-4 place-items-center rounded-full border-2 border-surface bg-navy-600" />
              <div className="rounded-card border border-line/80 bg-surface p-4 shadow-card">
                <div className="flex flex-wrap items-start justify-between gap-2">
                  <div className="min-w-0">
                    <p className="font-bold text-ink">{m.name}</p>
                    {m.description && <p className="mt-0.5 text-sm text-muted">{m.description}</p>}
                    {m.due_date && (
                      <p className="mt-1 flex items-center gap-1.5 text-xs text-muted">
                        <Icon name="Calendar" className="size-3.5" /> {formatDate(m.due_date)}
                      </p>
                    )}
                  </div>
                  <div className="flex items-center gap-2">
                    <Badge tone={MILESTONE_TONE[m.status] ?? 'muted'}>{m.status_label}</Badge>
                    <button type="button" onClick={() => openEdit(m)} className="grid size-8 place-items-center rounded-btn text-muted hover:bg-canvas hover:text-ink" aria-label={t('timeline.editMilestone')}>
                      <Icon name="PenLine" className="size-4" />
                    </button>
                    <button type="button" onClick={() => setRemoving(m)} className="grid size-8 place-items-center rounded-btn text-muted hover:bg-danger-soft hover:text-danger" aria-label={t('budget.delete')}>
                      <Icon name="Trash2" className="size-4" />
                    </button>
                  </div>
                </div>
              </div>
            </li>
          ))}
        </ol>
      ) : (
        <EmptyState icon="CalendarClock" title={t('timeline.noMilestonesYet')} description={t('timeline.mapOutPlan')}
          action={<Button size="sm" onClick={openCreate}><Icon name="Plus" className="size-4" /> {t('timeline.addMilestone')}</Button>} />
      )}

      <MilestoneDrawer
        key={drawer.editing?.id ?? 'new'}
        open={drawer.open}
        editing={drawer.editing}
        eventId={event.id}
        onClose={() => setDrawer({ open: false, editing: null })}
        onSaved={() => { setDrawer({ open: false, editing: null }); reload() }}
      />

      <ConfirmDialog open={!!removing} onClose={() => setRemoving(null)} onConfirm={remove}
        title={t('timeline.deleteMilestone')} confirmLabel={t('budget.delete')} loading={busy} />
    </div>
  )
}

function MilestoneDrawer({ open, editing, eventId, onClose, onSaved }) {
  const { t } = useTranslation()
  const { register, handleSubmit, formState: { errors, isSubmitting } } = useForm({
    defaultValues: editing
      ? { name: editing.name, description: editing.description ?? '', status: editing.status, due_date: editing.due_date ?? '' }
      : { status: 'pending' },
  })

  const submit = handleSubmit(async (values) => {
    const payload = Object.fromEntries(Object.entries(values).filter(([, v]) => v !== ''))
    if (editing) {
      await api.put(`/events/${eventId}/milestones/${editing.id}`, payload)
    } else {
      await api.post(`/events/${eventId}/milestones`, payload)
    }
    onSaved()
  })

  return (
    <Drawer open={open} onClose={onClose} title={editing ? t('timeline.editMilestone') : t('timeline.addMilestone')}>
      <form onSubmit={submit} className="space-y-4">
        <Field label={t('timeline.milestoneName')} error={errors.name?.message} {...register('name', { required: 'A name is required' })} />
        <Textarea label={t('approvals.description')} rows={3} {...register('description')} />
        <SelectField label={t('timeline.milestoneStatus')} options={MILESTONE_STATUS_OPTIONS} {...register('status')} />
        <Field type="date" label={t('timeline.milestoneDueDate')} {...register('due_date')} />
        <div className="flex justify-end pt-2">
          <Button type="submit" loading={isSubmitting}>{editing ? t('common.save') : t('timeline.addMilestone')}</Button>
        </div>
      </form>
    </Drawer>
  )
}
