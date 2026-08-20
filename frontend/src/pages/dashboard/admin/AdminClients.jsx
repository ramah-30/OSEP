import { useEffect, useMemo, useState } from 'react'
import PageHeader from '../../../components/ui/PageHeader'
import Card from '../../../components/ui/Card'
import Icon from '../../../components/ui/Icon'
import Avatar from '../../../components/ui/Avatar'
import Badge from '../../../components/ui/Badge'
import Button from '../../../components/ui/Button'
import EmptyState from '../../../components/ui/EmptyState'
import LoadState from '../../../components/dashboard/LoadState'
import { api } from '../../../lib/api'
import { useResource } from '../../../lib/useResource'

export default function AdminClients() {
  const [q, setQ] = useState('')
  const [debounced, setDebounced] = useState('')

  useEffect(() => {
    const id = setTimeout(() => setDebounced(q), 350)
    return () => clearTimeout(id)
  }, [q])

  const path = useMemo(() => `/admin/clients?per_page=30${debounced ? `&q=${encodeURIComponent(debounced)}` : ''}`, [debounced])
  const { data, loading, error, reload } = useResource(path)

  const verify = async (id, verified) => { await api.put(`/admin/clients/${id}/verify`, { verified }); reload() }
  const suspend = async (id, suspended) => { await api.put(`/admin/clients/${id}/suspend`, { suspended }); reload() }

  return (
    <div className="space-y-6">
      <PageHeader title="Clients" description="Verify or suspend client accounts." />

      <div className="relative max-w-md">
        <Icon name="Search" className="pointer-events-none absolute left-3.5 top-1/2 size-4 -translate-y-1/2 text-muted" />
        <input value={q} onChange={(e) => setQ(e.target.value)} placeholder="Search clients" className="h-11 w-full rounded-btn border border-line bg-surface pl-10 pr-4 text-sm text-ink outline-none focus:border-navy-600" />
      </div>

      <LoadState loading={loading} error={error} onRetry={reload}>
        {data && (data.clients.length ? (
          <div className="space-y-3">
            {data.clients.map((c) => (
              <Card key={c.id} className="flex flex-col gap-4 p-4 lg:flex-row lg:items-center">
                <div className="flex flex-1 items-center gap-3">
                  <Avatar name={c.full_name} src={c.avatar_url} />
                  <div className="min-w-0">
                    <p className="flex items-center gap-2 truncate font-bold text-ink">
                      {c.full_name}
                      {c.is_suspended && <Icon name="Ban" className="size-4 text-danger" />}
                    </p>
                    <p className="truncate text-xs text-muted">
                      {c.email}
                      {c.location ? ` · ${c.location}` : ''} · {c.events_count} event{c.events_count === 1 ? '' : 's'}
                    </p>
                  </div>
                  <div className="ml-auto hidden sm:block">
                    <Badge tone={c.is_verified ? 'emerald' : 'muted'} dot>
                      {c.is_verified ? 'Verified' : 'Unverified'}
                    </Badge>
                  </div>
                </div>

                <div className="flex flex-wrap items-center gap-2">
                  <Button size="sm" variant={c.is_verified ? 'ghost' : 'primary'} onClick={() => verify(c.id, !c.is_verified)}>
                    <Icon name={c.is_verified ? 'XCircle' : 'ShieldCheck'} className="size-4" /> {c.is_verified ? 'Unverify' : 'Verify'}
                  </Button>
                  <Button size="sm" variant={c.is_suspended ? 'emerald' : 'danger'} onClick={() => suspend(c.id, !c.is_suspended)}>
                    {c.is_suspended ? 'Reinstate' : 'Suspend'}
                  </Button>
                </div>
              </Card>
            ))}
          </div>
        ) : (
          <EmptyState icon="Users" title="No clients found" />
        ))}
      </LoadState>
    </div>
  )
}
