import PageHeader from '../../../components/ui/PageHeader'
import Card from '../../../components/ui/Card'
import Icon from '../../../components/ui/Icon'
import EmptyState from '../../../components/ui/EmptyState'
import LoadState from '../../../components/dashboard/LoadState'
import RatingStars from '../../../components/marketplace/RatingStars'
import PlannerBadge from '../../../components/marketplace/PlannerBadge'
import { useResource } from '../../../lib/useResource'
import { formatDate } from '../../../lib/format'
import { cn } from '../../../lib/cn'

/**
 * The planner's own reviews view: their auto-earned trust badge, aggregate
 * rating and star distribution, plus the reviews clients have left them.
 */
export default function Reviews() {
  const { data, loading, error, reload } = useResource('/planner/reviews')

  const reputation = data?.reputation
  const distribution = data?.distribution ?? {}
  const reviews = data?.reviews ?? []
  const maxCount = Math.max(1, ...Object.values(distribution))

  return (
    <div className="space-y-6">
      <PageHeader
        title="Reviews"
        description="What your clients say about working with you — and the trust badge you've earned."
      />

      <LoadState loading={loading} error={error} onRetry={reload}>
        {data && (
          <>
            {/* Reputation summary */}
            <div className="grid gap-4 md:grid-cols-3">
              <Card className="flex flex-col items-center justify-center p-6 text-center">
                <p className="text-5xl font-extrabold tracking-tight text-ink tabular-nums">
                  {reputation.rating > 0 ? reputation.rating.toFixed(1) : '—'}
                </p>
                <RatingStars rating={reputation.rating} showValue={false} className="mt-2" />
                <p className="mt-2 text-sm text-muted">
                  {reputation.reviews_count} {reputation.reviews_count === 1 ? 'review' : 'reviews'}
                </p>
              </Card>

              <Card className="flex flex-col items-center justify-center gap-3 p-6 text-center">
                <span className="grid size-12 place-items-center rounded-2xl bg-purple-50 text-purple-600 dark:bg-purple-950 dark:text-purple-300">
                  <Icon name="Award" className="size-6" />
                </span>
                <div>
                  <p className="text-xs font-semibold uppercase tracking-wide text-muted">Your badge</p>
                  <div className="mt-1.5">
                    {reputation.badge.verified ? (
                      <PlannerBadge badge={reputation.badge} size="md" />
                    ) : (
                      <p className="text-sm text-muted">Verify your email to earn a badge</p>
                    )}
                  </div>
                </div>
                <p className="text-xs text-muted">{reputation.completed_events} completed events</p>
              </Card>

              <Card className="p-6">
                <p className="mb-3 text-xs font-semibold uppercase tracking-wide text-muted">Rating breakdown</p>
                <div className="space-y-1.5">
                  {[5, 4, 3, 2, 1].map((star) => {
                    const count = distribution[star] ?? 0
                    return (
                      <div key={star} className="flex items-center gap-2 text-sm">
                        <span className="flex w-6 items-center gap-0.5 text-muted">
                          {star}<Icon name="Star" className="size-3 fill-warning text-warning" />
                        </span>
                        <div className="h-2 flex-1 overflow-hidden rounded-full bg-canvas">
                          <div className="h-full rounded-full bg-warning" style={{ width: `${(count / maxCount) * 100}%` }} />
                        </div>
                        <span className="w-5 text-right tabular-nums text-muted">{count}</span>
                      </div>
                    )
                  })}
                </div>
              </Card>
            </div>

            {/* Review list */}
            {reviews.length === 0 ? (
              <EmptyState
                icon="Star"
                title="No reviews yet"
                description="Once your clients review their events, their feedback will show up here."
              />
            ) : (
              <div className="space-y-4">
                {reviews.map((r) => (
                  <Card key={r.id} className="p-5">
                    <div className="flex items-start gap-3">
                      {r.reviewer.avatar_url ? (
                        <img src={r.reviewer.avatar_url} alt="" className="size-10 shrink-0 rounded-full object-cover" />
                      ) : (
                        <div className="grid size-10 shrink-0 place-items-center rounded-full bg-navy-100 font-bold text-navy-700 dark:bg-navy-900 dark:text-navy-200">
                          {(r.reviewer.full_name ?? '?').charAt(0)}
                        </div>
                      )}
                      <div className="min-w-0 flex-1">
                        <div className="flex flex-wrap items-center justify-between gap-2">
                          <p className="font-semibold text-ink">{r.reviewer.full_name ?? 'Client'}</p>
                          <RatingStars rating={r.rating} showValue={false} size="size-3.5" />
                        </div>
                        <p className="text-xs text-muted">
                          {r.event_title ? `${r.event_title} · ` : ''}{formatDate(r.created_at)}
                        </p>
                        {r.comment && <p className="mt-2 text-sm leading-relaxed text-ink">{r.comment}</p>}
                      </div>
                    </div>
                  </Card>
                ))}
              </div>
            )}
          </>
        )}
      </LoadState>
    </div>
  )
}
