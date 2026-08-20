import { useEffect, useMemo, useState } from 'react'
import PageHeader from '../../../components/ui/PageHeader'
import Card from '../../../components/ui/Card'
import Icon from '../../../components/ui/Icon'
import Button from '../../../components/ui/Button'
import EmptyState from '../../../components/ui/EmptyState'
import ListboxSelect from '../../../components/ui/ListboxSelect'
import LoadState from '../../../components/dashboard/LoadState'
import VerificationBadge from '../../../components/marketplace/VerificationBadge'
import { api } from '../../../lib/api'
import { useResource } from '../../../lib/useResource'
import { formatCurrency } from '../../../lib/format'

const LEVELS = [
  { value: 'unverified', label: 'Unverified' },
  { value: 'email_verified', label: 'Email verified' },
  { value: 'business_verified', label: 'Business verified' },
  { value: 'premium_partner', label: 'Premium partner' },
]

export default function AdminVenues() {
  const [q, setQ] = useState('')
  const [debounced, setDebounced] = useState('')

  useEffect(() => {
    const id = setTimeout(() => setDebounced(q), 350)
    return () => clearTimeout(id)
  }, [q])

  const path = useMemo(() => `/admin/marketplace/venues?per_page=30${debounced ? `&q=${encodeURIComponent(debounced)}` : ''}`, [debounced])
  const { data, loading, error, reload } = useResource(path)

  const verify = async (id, level) => { await api.put(`/admin/marketplace/venues/${id}/verify`, { level }); reload() }
  const suspend = async (id, suspended) => { await api.put(`/admin/marketplace/venues/${id}/suspend`, { suspended }); reload() }
  const feature = async (id, featured) => { await api.put(`/admin/marketplace/venues/${id}/feature`, { featured }); reload() }

  return (
    <div className="space-y-6">
      <PageHeader title="Venues" description="Verify, feature or suspend venue listings." />

      <div className="relative max-w-md">
        <Icon name="Search" className="pointer-events-none absolute left-3.5 top-1/2 size-4 -translate-y-1/2 text-muted" />
        <input value={q} onChange={(e) => setQ(e.target.value)} placeholder="Search venues" className="h-11 w-full rounded-btn border border-line bg-surface pl-10 pr-4 text-sm text-ink outline-none focus:border-navy-600" />
      </div>

      <LoadState loading={loading} error={error} onRetry={reload}>
        {data && (data.venues.length ? (
          <div className="space-y-3">
            {data.venues.map((v) => (
              <Card key={v.id} className="flex flex-col gap-4 p-4 lg:flex-row lg:items-center">
                <div className="flex flex-1 items-center gap-3">
                  <span className="grid size-11 shrink-0 place-items-center rounded-lg bg-canvas text-navy-700"><Icon name="Building" className="size-5" /></span>
                  <div className="min-w-0">
                    <p className="flex items-center gap-2 truncate font-bold text-ink">
                      {v.name}
                      {v.is_suspended && <Icon name="Ban" className="size-4 text-danger" />}
                      {v.is_featured && <Icon name="Crown" className="size-4 text-purple-600" />}
                    </p>
                    <p className="text-xs text-muted">{v.venue_type} · {v.location ?? '—'} · {v.price != null ? formatCurrency(v.price) : '—'}</p>
                  </div>
                  <div className="ml-auto hidden sm:block"><VerificationBadge level={v.verification_level} always /></div>
                </div>

                <div className="flex flex-wrap items-center gap-2">
                  <ListboxSelect className="w-40" heightClass="h-9" options={LEVELS} value={v.verification_level} onChange={(e) => verify(v.id, e.target.value)} />
                  <Button size="sm" variant={v.is_featured ? 'secondary' : 'ghost'} onClick={() => feature(v.id, !v.is_featured)}><Icon name="Crown" className="size-4" /> {v.is_featured ? 'Unfeature' : 'Feature'}</Button>
                  <Button size="sm" variant={v.is_suspended ? 'emerald' : 'danger'} onClick={() => suspend(v.id, !v.is_suspended)}>{v.is_suspended ? 'Reinstate' : 'Suspend'}</Button>
                </div>
              </Card>
            ))}
          </div>
        ) : (
          <EmptyState icon="Building" title="No venues found" />
        ))}
      </LoadState>
    </div>
  )
}
