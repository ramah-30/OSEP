import { useTranslation } from 'react-i18next'
import Card from '../../../components/ui/Card'
import Icon from '../../../components/ui/Icon'
import EmptyState from '../../../components/ui/EmptyState'
import LoadState from '../../../components/dashboard/LoadState'
import RatingStars from '../../../components/marketplace/RatingStars'
import { useResource } from '../../../lib/useResource'
import { formatNumber } from '../../../lib/format'

export default function AdminOverview() {
  const { t } = useTranslation()
  const { data, loading, error, reload } = useResource('/admin/marketplace/dashboard')

  return (
    <div className="space-y-8">
      <div>
        <h1 className="text-h3 font-extrabold tracking-tight text-ink">{t('dashboard.marketplaceAdmin')}</h1>
        <p className="mt-1.5 text-muted">{t('dashboard.marketplaceModerate')}</p>
      </div>

      <LoadState loading={loading} error={error} onRetry={reload}>
        {data && (
          <div className="space-y-8">
            <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
              <Stat icon="UserCheck" label={t('admin.planners')} value={data.stats.planners} t={t} />
              <Stat icon="Users" label={t('admin.clients')} value={data.stats.clients} t={t} />
              <Stat icon="Store" label={t('admin.vendors')} value={data.stats.vendors} sub={`${data.stats.pending_vendors} ${t('status.pending').toLowerCase()}`} t={t} />
              <Stat icon="Building" label={t('admin.venues')} value={data.stats.venues} sub={`${data.stats.suspended_venues} ${t('status.suspended').toLowerCase()}`} t={t} />
              <Stat icon="Handshake" label={t('admin.contracts')} value={data.stats.contracts} t={t} />
              <Stat icon="Star" label={t('admin.flaggedReviews')} value={data.stats.flagged_reviews} sub={`${data.stats.reviews} ${t('common.total')}`} t={t} />
            </div>

            <Card className="p-6">
              <h3 className="mb-4 font-bold text-ink">{t('admin.flaggedReviews')}</h3>
              {data.flagged_reviews.length ? (
                <div className="space-y-3">
                  {data.flagged_reviews.map((r) => (
                    <div key={r.id} className="rounded-btn border border-line p-3">
                      <div className="flex items-center justify-between">
                        <p className="text-sm font-semibold text-ink">{r.reviewer_name}</p>
                        <RatingStars rating={r.overall_rating} showValue={false} />
                      </div>
                      {r.comment && <p className="mt-1 text-sm text-muted line-clamp-2">{r.comment}</p>}
                    </div>
                  ))}
                </div>
              ) : <EmptyState icon="Star" title={t('admin.noFlaggedReviews')} description={t('admin.noReviewsModeration')} />}
            </Card>
          </div>
        )}
      </LoadState>
    </div>
  )
}

function Stat({ icon, label, value, sub }) {
  return (
    <Card className="p-5">
      <div className="flex items-center justify-between">
        <p className="text-sm text-muted">{label}</p>
        <Icon name={icon} className="size-5 text-navy-600" />
      </div>
      <p className="mt-2 text-2xl font-extrabold text-ink">{formatNumber(value)}</p>
      {sub && <p className="mt-0.5 text-xs text-muted">{sub}</p>}
    </Card>
  )
}
