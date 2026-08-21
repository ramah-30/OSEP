import { useEffect, useState } from 'react'
import { useForm } from 'react-hook-form'
import { useTranslation } from 'react-i18next'
import PageHeader from '../../../components/ui/PageHeader'
import Button from '../../../components/ui/Button'
import Icon from '../../../components/ui/Icon'
import Avatar from '../../../components/ui/Avatar'
import EmptyState from '../../../components/ui/EmptyState'
import Drawer from '../../../components/ui/Drawer'
import ConfirmDialog from '../../../components/ui/ConfirmDialog'
import { Field } from '../../../components/ui/Field'
import { Table, THead, TH, TBody, TR, TD } from '../../../components/ui/Table'
import LoadState from '../../../components/dashboard/LoadState'
import { useResource } from '../../../lib/useResource'
import { api, applyServerErrors, parseApiError } from '../../../lib/api'

export default function Clients() {
  const { t } = useTranslation()
  const { data, loading, error, reload } = useResource('/clients')
  const [editing, setEditing] = useState(null) // null = closed, {} = new, {id,...} = edit
  const [deleting, setDeleting] = useState(null)
  const [removing, setRemoving] = useState(false)
  const [deleteError, setDeleteError] = useState(null)

  async function handleDelete() {
    setRemoving(true)
    setDeleteError(null)
    try {
      await api.delete(`/clients/${deleting.id}`)
      setDeleting(null)
      reload()
    } catch (err) {
      setDeleteError(parseApiError(err).message)
    } finally {
      setRemoving(false)
    }
  }

  return (
    <div className="space-y-6">
      <PageHeader
        title={t('clients.myClients')}
        description={t('clients.clientsDescription')}
        actions={<Button size="sm" onClick={() => setEditing({})}><Icon name="UserPlus" className="size-4" /> {t('clients.newClient')}</Button>}
      />

      <LoadState loading={loading} error={error} onRetry={reload}>
        {data && (
          data.clients.length ? (
            <Table>
              <THead>
                <TR><TH>{t('clients.clientTableHeader')}</TH><TH>{t('clients.emailTableHeader')}</TH><TH>{t('clients.phoneTableHeader')}</TH><TH>{t('clients.locationTableHeader')}</TH><TH className="text-right">{t('clients.actionsTableHeader')}</TH></TR>
              </THead>
              <TBody>
                {data.clients.map((c) => (
                  <TR key={c.id}>
                    <TD>
                      <div className="flex items-center gap-3">
                        <Avatar name={c.full_name} src={c.avatar_url} size="sm" />
                        <span className="font-semibold">{c.full_name}</span>
                      </div>
                    </TD>
                    <TD className="text-muted">{c.email}</TD>
                    <TD className="text-muted">{c.phone ?? '—'}</TD>
                    <TD className="text-muted">{c.location ?? '—'}</TD>
                    <TD>
                      <div className="flex items-center justify-end gap-1">
                        <Button size="sm" variant="ghost" to={`/dashboard/planner/messages?to=${c.id}`} aria-label={`${t('clients.messageClient')} ${c.full_name}`}>
                          <Icon name="MessageSquare" className="size-4" />
                        </Button>
                        <Button size="sm" variant="ghost" onClick={() => setEditing(c)} aria-label={`${t('clients.editClient')} ${c.full_name}`}>
                          <Icon name="PenLine" className="size-4" />
                        </Button>
                        <Button
                          size="sm"
                          variant="ghost"
                          className="text-danger hover:bg-danger-soft"
                          onClick={() => { setDeleteError(null); setDeleting(c) }}
                          aria-label={`${t('common.delete')} ${c.full_name}`}
                        >
                          <Icon name="Trash2" className="size-4" />
                        </Button>
                      </div>
                    </TD>
                  </TR>
                ))}
              </TBody>
            </Table>
          ) : (
            <EmptyState icon="Users" title={t('clients.noClients')}
              description={t('clients.noClientsDesc')}
              action={<Button size="sm" onClick={() => setEditing({})}><Icon name="UserPlus" className="size-4" /> {t('clients.newClient')}</Button>} />
          )
        )}
      </LoadState>

      <ClientDrawer
        client={editing}
        onClose={() => setEditing(null)}
        onSaved={() => { setEditing(null); reload() }}
      />

      <ConfirmDialog
        open={!!deleting}
        onClose={() => setDeleting(null)}
        onConfirm={handleDelete}
        title={t('clients.removeClientTitle')}
        description={
          deleteError
            ? deleteError
            : `${deleting?.full_name ?? t('clients.removeClient')} ${t('clients.removeClientDesc')}`
        }
        confirmLabel={t('clients.removeClientButton')}
        loading={removing}
      />
    </div>
  )
}

function ClientDrawer({ client, onClose, onSaved }) {
  const { t } = useTranslation()
  const open = client !== null
  const isEdit = !!client?.id
  const { register, handleSubmit, reset, setError, formState: { errors, isSubmitting } } = useForm()

  // Refill the form each time the drawer opens for a different client.
  useEffect(() => {
    if (open) {
      reset({
        first_name: client.first_name ?? '',
        last_name: client.last_name ?? '',
        email: client.email ?? '',
        phone: client.phone ?? '',
        location: client.location ?? '',
      })
    }
  }, [open, client, reset])

  const submit = handleSubmit(async (values) => {
    try {
      if (isEdit) {
        await api.put(`/clients/${client.id}`, values)
      } else {
        await api.post('/clients', values)
      }
      onSaved()
    } catch (err) {
      applyServerErrors(parseApiError(err).errors, setError)
    }
  })

  return (
    <Drawer open={open} onClose={onClose} title={isEdit ? t('clients.editClient') : t('clients.newClient')}>
      <form onSubmit={submit} className="space-y-4">
        <div className="grid grid-cols-2 gap-4">
          <Field label={t('clients.firstName')} error={errors.first_name?.message} {...register('first_name', { required: t('clients.required') })} />
          <Field label={t('clients.lastName')} error={errors.last_name?.message} {...register('last_name', { required: t('clients.required') })} />
        </div>
        <Field type="email" label={t('clients.email')} error={errors.email?.message} {...register('email', { required: t('clients.required') })} />
        <Field label={t('clients.phone')} {...register('phone')} />
        <Field label={t('clients.location')} {...register('location')} />
        <div className="flex justify-end pt-2">
          <Button type="submit" loading={isSubmitting}>{isEdit ? t('clients.saveChanges') : t('clients.createClient')}</Button>
        </div>
      </form>
    </Drawer>
  )
}
