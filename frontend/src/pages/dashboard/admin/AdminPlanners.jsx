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

export default function AdminPlanners() {
  const [q, setQ] = useState('')
  const [debounced, setDebounced] = useState('')

  useEffect(() => {
    const id = setTimeout(() => setDebounced(q), 350)
    return () => clearTimeout(id)
  }, [q])

  const path = useMemo(() => `/admin/planners?per_page=30${debounced ? `&q=${encodeURIComponent(debounced)}` : ''}`, [debounced])
  const { data, loading, error, reload } = useResource(path)

  const verify = async (id, verified) => { await api.put(`/admin/planners/${id}/verify`, { verified }); reload() }
  const suspend = async (id, suspended) => { await api.put(`/admin/planners/${id}/suspend`, { suspended }); reload() }

  return (
    <div className="space-y-6">
      <PageHeader title="Planners" description="Verify or suspend event-planner accounts." />

      <div className="relative max-w-md">
        <Icon name="Search" className="pointer-events-none absolute left-3.5 top-1/2 size-4 -translate-y-1/2 text-muted" />
        <input value={q} onChange={(e) => setQ(e.target.value)} placeholder="Search planners" className="h-11 w-full rounded-btn border border-line bg-surface pl-10 pr-4 text-sm text-ink outline-none focus:border-navy-600" />
      </div>

      <LoadState loading={loading} error={error} onRetry={reload}>
        {data && (data.planners.length ? (
          <div className="space-y-3">
            {data.planners.map((p) => (
              <Card key={p.id} className="flex flex-col gap-4 p-4 lg:flex-row lg:items-center">
                <div className="flex flex-1 items-center gap-3">
                  <Avatar name={p.full_name} src={p.avatar_url} />
                  <div className="min-w-0">
                    <p className="flex items-center gap-2 truncate font-bold text-ink">
                      {p.full_name}
                      {p.is_suspended && <Icon name="Ban" className="size-4 text-danger" />}
                    </p>
                    <p className="truncate text-xs text-muted">
                      {p.company_name ?? p.email}
                      {p.specialization ? ` · ${p.specialization}` : ''} · {p.events_count} event{p.events_count === 1 ? '' : 's'}
                    </p>
                  </div>
                  <div className="ml-auto hidden sm:block">
                    <Badge tone={p.is_verified ? 'emerald' : 'muted'} dot>
                      {p.is_verified ? 'Verified' : 'Unverified'}
                    </Badge>
                  </div>
                </div>

                <div className="flex flex-wrap items-center gap-2">
                  <Button size="sm" variant={p.is_verified ? 'ghost' : 'primary'} onClick={() => verify(p.id, !p.is_verified)}>
                    <Icon name={p.is_verified ? 'XCircle' : 'ShieldCheck'} className="size-4" /> {p.is_verified ? 'Unverify' : 'Verify'}
                  </Button>
                  <Button size="sm" variant={p.is_suspended ? 'emerald' : 'danger'} onClick={() => suspend(p.id, !p.is_suspended)}>
                    {p.is_suspended ? 'Reinstate' : 'Suspend'}
                  </Button>
                </div>
              </Card>
            ))}
          </div>
        ) : (
          <EmptyState icon="UserCheck" title="No planners found" />
        ))}
      </LoadState>
    </div>
  )
}
