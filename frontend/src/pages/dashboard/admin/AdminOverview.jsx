import Card from '../../../components/ui/Card'
import Icon from '../../../components/ui/Icon'
import Avatar from '../../../components/ui/Avatar'
import Badge from '../../../components/ui/Badge'
import EmptyState from '../../../components/ui/EmptyState'
import LoadState from '../../../components/dashboard/LoadState'
import RatingStars from '../../../components/marketplace/RatingStars'
import { useResource } from '../../../lib/useResource'
import { formatNumber } from '../../../lib/format'

export default function AdminOverview() {
  const { data, loading, error, reload } = useResource('/admin/marketplace/dashboard')

  return (
    <div className="space-y-8">
      <div>
        <h1 className="text-h3 font-extrabold tracking-tight text-ink">Marketplace administration</h1>
        <p className="mt-1.5 text-muted">Moderate vendors, venues and reviews across the platform.</p>
      </div>

      <LoadState loading={loading} error={error} onRetry={reload}>
        {data && (
          <div className="space-y-8">
            <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
              <Stat icon="Store" label="Vendors" value={data.stats.vendors} sub={`${data.stats.pending_vendors} pending`} />
              <Stat icon="Building" label="Venues" value={data.stats.venues} sub={`${data.stats.suspended_venues} suspended`} />
              <Stat icon="Handshake" label="Contracts" value={data.stats.contracts} />
              <Stat icon="Star" label="Flagged reviews" value={data.stats.flagged_reviews} sub={`${data.stats.reviews} total`} />
            </div>

            <div className="grid gap-6 lg:grid-cols-2">
              <Card className="p-6">
                <h3 className="mb-4 font-bold text-ink">Vendors awaiting verification</h3>
                {data.pending_vendors.length ? (
                  <div className="space-y-3">
                    {data.pending_vendors.map((v) => (
                      <div key={v.id} className="flex items-center gap-3">
                        <Avatar name={v.business_name} src={v.logo_url} />
                        <div className="min-w-0 flex-1">
                          <p className="truncate font-semibold text-ink">{v.business_name}</p>
                          <p className="text-xs text-muted">{v.category}</p>
                        </div>
                        <Badge tone="amber">Pending</Badge>
                      </div>
                    ))}
                  </div>
                ) : <EmptyState icon="BadgeCheck" title="All caught up" description="No vendors are awaiting verification." />}
              </Card>

              <Card className="p-6">
                <h3 className="mb-4 font-bold text-ink">Flagged reviews</h3>
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
                ) : <EmptyState icon="Star" title="Nothing flagged" description="No reviews need moderation." />}
              </Card>
            </div>
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
