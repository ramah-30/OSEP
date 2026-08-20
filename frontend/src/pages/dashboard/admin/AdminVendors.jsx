import { useEffect, useMemo, useState } from 'react'
import PageHeader from '../../../components/ui/PageHeader'
import Card from '../../../components/ui/Card'
import Icon from '../../../components/ui/Icon'
import Avatar from '../../../components/ui/Avatar'
import Button from '../../../components/ui/Button'
import EmptyState from '../../../components/ui/EmptyState'
import ListboxSelect from '../../../components/ui/ListboxSelect'
import LoadState from '../../../components/dashboard/LoadState'
import VerificationBadge from '../../../components/marketplace/VerificationBadge'
import { api } from '../../../lib/api'
import { useResource } from '../../../lib/useResource'

const LEVELS = [
  { value: 'unverified', label: 'Unverified' },
  { value: 'email_verified', label: 'Email verified' },
  { value: 'business_verified', label: 'Business verified' },
  { value: 'premium_partner', label: 'Premium partner' },
]

export default function AdminVendors() {
  const [q, setQ] = useState('')
  const [debounced, setDebounced] = useState('')

  useEffect(() => {
    const id = setTimeout(() => setDebounced(q), 350)
    return () => clearTimeout(id)
  }, [q])

  const path = useMemo(() => `/admin/marketplace/vendors?per_page=30${debounced ? `&q=${encodeURIComponent(debounced)}` : ''}`, [debounced])
  const { data, loading, error, reload } = useResource(path)

  const verify = async (id, level) => { await api.put(`/admin/marketplace/vendors/${id}/verify`, { level }); reload() }
  const suspend = async (id, suspended) => { await api.put(`/admin/marketplace/vendors/${id}/suspend`, { suspended }); reload() }
  const feature = async (id, featured) => { await api.put(`/admin/marketplace/vendors/${id}/feature`, { featured }); reload() }

  return (
    <div className="space-y-6">
      <PageHeader title="Vendors" description="Verify, feature or suspend vendor listings." />

      <div className="relative max-w-md">
        <Icon name="Search" className="pointer-events-none absolute left-3.5 top-1/2 size-4 -translate-y-1/2 text-muted" />
        <input value={q} onChange={(e) => setQ(e.target.value)} placeholder="Search vendors" className="h-11 w-full rounded-btn border border-line bg-surface pl-10 pr-4 text-sm text-ink outline-none focus:border-navy-600" />
      </div>

      <LoadState loading={loading} error={error} onRetry={reload}>
        {data && (data.vendors.length ? (
          <div className="space-y-3">
            {data.vendors.map((v) => (
              <Card key={v.id} className="flex flex-col gap-4 p-4 lg:flex-row lg:items-center">
                <div className="flex flex-1 items-center gap-3">
                  <Avatar name={v.business_name} src={v.logo_url} />
                  <div className="min-w-0">
                    <p className="flex items-center gap-2 truncate font-bold text-ink">
                      {v.business_name}
                      {v.is_suspended && <Icon name="Ban" className="size-4 text-danger" />}
                      {v.is_featured && <Icon name="Crown" className="size-4 text-purple-600" />}
                    </p>
                    <p className="text-xs text-muted">{v.category} · {v.location ?? '—'}</p>
                  </div>
                  <div className="ml-auto hidden sm:block"><VerificationBadge level={v.verification_level} always /></div>
                </div>

                <div className="flex flex-wrap items-center gap-2">
                  <ListboxSelect
                    className="w-40"
                    heightClass="h-9"
                    options={LEVELS}
                    value={v.verification_level}
                    onChange={(e) => verify(v.id, e.target.value)}
                  />
                  <Button size="sm" variant={v.is_featured ? 'secondary' : 'ghost'} onClick={() => feature(v.id, !v.is_featured)}>
                    <Icon name="Crown" className="size-4" /> {v.is_featured ? 'Unfeature' : 'Feature'}
                  </Button>
                  <Button size="sm" variant={v.is_suspended ? 'emerald' : 'danger'} onClick={() => suspend(v.id, !v.is_suspended)}>
                    {v.is_suspended ? 'Reinstate' : 'Suspend'}
                  </Button>
                </div>
              </Card>
            ))}
          </div>
        ) : (
          <EmptyState icon="Store" title="No vendors found" />
        ))}
      </LoadState>
    </div>
  )
}
