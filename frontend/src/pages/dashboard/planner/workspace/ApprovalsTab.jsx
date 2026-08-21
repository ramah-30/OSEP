import { useState } from 'react'
import { useOutletContext } from 'react-router-dom'
import { useForm } from 'react-hook-form'
import { useTranslation } from 'react-i18next'
import Button from '../../../../components/ui/Button'
import Icon from '../../../../components/ui/Icon'
import Badge from '../../../../components/ui/Badge'
import Card from '../../../../components/ui/Card'
import Drawer from '../../../../components/ui/Drawer'
import EmptyState from '../../../../components/ui/EmptyState'
import { Field, SelectField } from '../../../../components/ui/Field'
import Textarea from '../../../../components/ui/Textarea'
import { api } from '../../../../lib/api'
import { formatRelative } from '../../../../lib/format'
import { APPROVAL_TONE, APPROVAL_TYPE_OPTIONS } from '../../../../lib/eventConstants'

export default function ApprovalsTab() {
  const { t } = useTranslation()
  const { event, reload } = useOutletContext()
  const approvals = event.approvals ?? []
  const [creating, setCreating] = useState(false)

  return (
    <div className="space-y-5">
      <div className="flex items-center justify-between">
        <div>
          <h2 className="text-lg font-extrabold text-ink">{t('approvals.approvalsTitle')}</h2>
          <p className="text-sm text-muted">{t('approvals.approvalsDescription')}</p>
        </div>
        <Button size="sm" onClick={() => setCreating(true)} disabled={!event.client}>
          <Icon name="Plus" className="size-4" /> {t('approvals.requestApproval')}
        </Button>
      </div>

      {!event.client && (
        <p className="rounded-btn border border-line bg-canvas/60 px-4 py-3 text-sm text-muted">
          {t('approvals.assignClientFirst')}
        </p>
      )}

      {approvals.length ? (
        <div className="space-y-3">
          {approvals.map((a) => (
            <Card key={a.id} className="p-5">
              <div className="flex flex-wrap items-start justify-between gap-3">
                <div className="min-w-0">
                  <p className="font-bold text-ink">{a.title}</p>
                  <p className="mt-0.5 text-xs font-semibold uppercase tracking-wide text-muted">{a.type.replace(/_/g, ' ')}</p>
                  {a.description && <p className="mt-2 text-sm text-muted">{a.description}</p>}
                </div>
                <Badge tone={APPROVAL_TONE[a.status] ?? 'muted'} dot>{a.status_label}</Badge>
              </div>

              {a.client_note && (
                <p className="mt-3 rounded-btn bg-canvas/70 px-3 py-2 text-sm text-ink">
                  <span className="font-semibold">{t('approvals.clientNote')}:</span> {a.client_note}
                </p>
              )}

              {a.history?.length > 0 && (
                <ul className="mt-3 space-y-1.5 border-t border-line pt-3">
                  {a.history.map((h) => (
                    <li key={h.id} className="flex items-center gap-2 text-xs text-muted">
                      <Icon name="Clock" className="size-3.5" />
                      <span className="font-semibold capitalize">{h.action.replace(/_/g, ' ')}</span>
                      {h.user && <span>· {h.user.full_name}</span>}
                      <span>· {formatRelative(h.created_at)}</span>
                    </li>
                  ))}
                </ul>
              )}
            </Card>
          ))}
        </div>
      ) : (
        <EmptyState icon="ClipboardCheck" title={t('approvals.noApprovalsYet')} description={t('approvals.noApprovalsDesc')} />
      )}

      <ApprovalDrawer open={creating} eventId={event.id}
        onClose={() => setCreating(false)}
        onSaved={() => { setCreating(false); reload() }} />
    </div>
  )
}

function ApprovalDrawer({ open, eventId, onClose, onSaved }) {
  const { t } = useTranslation()
  const { register, handleSubmit, formState: { errors, isSubmitting } } = useForm({ defaultValues: { type: 'proposal' } })

  const submit = handleSubmit(async (values) => {
    await api.post(`/events/${eventId}/approvals`, values)
    onSaved()
  })

  return (
    <Drawer open={open} onClose={onClose} title={t('approvals.requestApproval')}>
      <form onSubmit={submit} className="space-y-4">
        <Field label={t('approvals.title')} error={errors.title?.message} {...register('title', { required: 'A title is required' })} />
        <SelectField label={t('approvals.type')} options={APPROVAL_TYPE_OPTIONS} {...register('type')} />
        <Textarea label={t('approvals.description')} rows={4} {...register('description')} />
        <div className="flex justify-end pt-2">
          <Button type="submit" loading={isSubmitting}>{t('approvals.sendToClient')}</Button>
        </div>
      </form>
    </Drawer>
  )
}
