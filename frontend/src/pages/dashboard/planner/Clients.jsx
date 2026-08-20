import { useEffect, useState } from 'react'
import { useForm } from 'react-hook-form'
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
        title="Clients"
        description="The clients you're planning events for."
        actions={<Button size="sm" onClick={() => setEditing({})}><Icon name="UserPlus" className="size-4" /> New client</Button>}
      />

      <LoadState loading={loading} error={error} onRetry={reload}>
        {data && (
          data.clients.length ? (
            <Table>
              <THead>
                <TR><TH>Client</TH><TH>Email</TH><TH>Phone</TH><TH>Location</TH><TH className="text-right">Actions</TH></TR>
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
                        <Button size="sm" variant="ghost" to={`/dashboard/planner/messages?to=${c.id}`} aria-label={`Message ${c.full_name}`}>
                          <Icon name="MessageSquare" className="size-4" />
                        </Button>
                        <Button size="sm" variant="ghost" onClick={() => setEditing(c)} aria-label={`Edit ${c.full_name}`}>
                          <Icon name="PenLine" className="size-4" />
                        </Button>
                        <Button
                          size="sm"
                          variant="ghost"
                          className="text-danger hover:bg-danger-soft"
                          onClick={() => { setDeleteError(null); setDeleting(c) }}
                          aria-label={`Delete ${c.full_name}`}
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
            <EmptyState icon="Users" title="No clients yet"
              description="Add a client here, or create one inline while setting up an event."
              action={<Button size="sm" onClick={() => setEditing({})}><Icon name="UserPlus" className="size-4" /> New client</Button>} />
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
        title="Remove client?"
        description={
          deleteError
            ? deleteError
            : `${deleting?.full_name ?? 'This client'} will be removed from your roster and unlinked from your events. This can't be undone.`
        }
        confirmLabel="Remove"
        loading={removing}
      />
    </div>
  )
}

function ClientDrawer({ client, onClose, onSaved }) {
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
    <Drawer open={open} onClose={onClose} title={isEdit ? 'Edit client' : 'New client'}>
      <form onSubmit={submit} className="space-y-4">
        <div className="grid grid-cols-2 gap-4">
          <Field label="First name" error={errors.first_name?.message} {...register('first_name', { required: 'Required' })} />
          <Field label="Last name" error={errors.last_name?.message} {...register('last_name', { required: 'Required' })} />
        </div>
        <Field type="email" label="Email" error={errors.email?.message} {...register('email', { required: 'Required' })} />
        <Field label="Phone" {...register('phone')} />
        <Field label="Location" {...register('location')} />
        <div className="flex justify-end pt-2">
          <Button type="submit" loading={isSubmitting}>{isEdit ? 'Save changes' : 'Create client'}</Button>
        </div>
      </form>
    </Drawer>
  )
}
