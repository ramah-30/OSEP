import { useEffect, useMemo, useState } from 'react'
import { useTranslation } from 'react-i18next'
import PageHeader from '../../../components/ui/PageHeader'
import Card from '../../../components/ui/Card'
import Icon from '../../../components/ui/Icon'
import Avatar from '../../../components/ui/Avatar'
import Badge from '../../../components/ui/Badge'
import EmptyState from '../../../components/ui/EmptyState'
import LoadState from '../../../components/dashboard/LoadState'
import { useResource } from '../../../lib/useResource'

export default function Vendors() {
  const { t } = useTranslation()
  const [q, setQ] = useState('')
  const [debounced, setDebounced] = useState('')

  useEffect(() => {
    const id = setTimeout(() => setDebounced(q), 350)
    return () => clearTimeout(id)
  }, [q])

  const path = useMemo(() => (debounced ? `/vendors?q=${encodeURIComponent(debounced)}` : '/vendors'), [debounced])
  const { data, loading, error, reload } = useResource(path)

  return (
    <div className="space-y-6">
      <PageHeader title={t('vendors.vendorsTitle')} description={t('vendors.vendorsDescription')} />

      <div className="relative max-w-md">
        <Icon name="Search" className="pointer-events-none absolute left-3.5 top-1/2 size-4 -translate-y-1/2 text-muted" />
        <input value={q} onChange={(e) => setQ(e.target.value)} placeholder={t('vendors.searchByBusinessOrCategory')}
          className="h-11 w-full rounded-btn border border-line bg-surface pl-10 pr-4 text-sm text-ink outline-none focus:border-navy-600 focus:shadow-[0_0_0_3px_rgba(41,71,200,0.12)]" />
      </div>

      <LoadState loading={loading} error={error} onRetry={reload}>
        {data && (
          data.vendors.length ? (
            <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
              {data.vendors.map((v) => (
                <Card key={v.id} className="p-5">
                  <div className="flex items-center gap-3">
                    <Avatar name={v.business_name ?? v.full_name} src={v.avatar_url} />
                    <div className="min-w-0">
                      <p className="truncate font-bold text-ink">{v.business_name ?? v.full_name}</p>
                      <p className="text-sm text-muted">{v.category ?? '—'}</p>
                    </div>
                  </div>
                  <div className="mt-4 flex items-center justify-between text-sm text-muted">
                    <span className="flex items-center gap-1.5"><Icon name="MapPin" className="size-4" />{v.location ?? '—'}</span>
                    {v.rating != null && <span className="flex items-center gap-1 font-semibold text-ink"><Icon name="Star" className="size-4 text-warning" />{v.rating}</span>}
                  </div>
                  {v.verification_status === 'verified' && (
                    <Badge tone="emerald" className="mt-3"><Icon name="ShieldCheck" className="size-3.5" /> {t('vendors.verified')}</Badge>
                  )}
                </Card>
              ))}
            </div>
          ) : (
            <EmptyState icon="Store" title={t('vendors.noVendorsFound')} description={t('vendors.tryDifferentSearch')} />
          )
        )}
      </LoadState>
    </div>
  )
}
