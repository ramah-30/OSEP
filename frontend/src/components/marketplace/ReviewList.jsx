import Card from '../ui/Card'
import Avatar from '../ui/Avatar'
import Icon from '../ui/Icon'
import RatingStars from './RatingStars'
import EmptyState from '../ui/EmptyState'
import { formatRelative } from '../../lib/format'

const LABELS = {
  professionalism: 'Professionalism',
  communication: 'Communication',
  quality: 'Quality',
  value: 'Value',
  timeliness: 'Timeliness',
}

/** Shared review feed for storefronts and the vendor's own reviews tab. */
export default function ReviewList({ reviews }) {
  if (!reviews?.length) {
    return <EmptyState icon="Star" title="No reviews yet" description="Reviews from completed bookings will appear here." />
  }

  return (
    <div className="space-y-4">
      {reviews.map((r) => (
        <Card key={r.id} className="p-5">
          <div className="flex items-start justify-between gap-3">
            <div className="flex items-center gap-3">
              <Avatar name={r.reviewer_name ?? 'Planner'} />
              <div>
                <p className="font-bold text-ink">{r.reviewer_name ?? 'Planner'}</p>
                <p className="text-xs text-muted">{formatRelative(r.created_at)}</p>
              </div>
            </div>
            <RatingStars rating={r.overall_rating} />
          </div>

          {r.title && <p className="mt-3 font-semibold text-ink">{r.title}</p>}
          {r.comment && <p className="mt-1 text-sm text-muted">{r.comment}</p>}

          <div className="mt-3 flex flex-wrap gap-x-4 gap-y-1 text-xs text-muted">
            {Object.entries(r.ratings ?? {}).filter(([, v]) => v != null).map(([key, v]) => (
              <span key={key} className="flex items-center gap-1">
                {LABELS[key]}: <span className="font-semibold text-ink">{v}</span>
                <Icon name="Star" className="size-3 fill-warning text-warning" />
              </span>
            ))}
          </div>

          {(r.replies ?? []).map((reply) => (
            <div key={reply.id} className="mt-3 rounded-btn bg-canvas p-3">
              <p className="flex items-center gap-1.5 text-xs font-bold text-ink">
                <Icon name="MessageSquare" className="size-3.5" /> {reply.user_name} replied
              </p>
              <p className="mt-1 text-sm text-muted">{reply.body}</p>
            </div>
          ))}
        </Card>
      ))}
    </div>
  )
}
