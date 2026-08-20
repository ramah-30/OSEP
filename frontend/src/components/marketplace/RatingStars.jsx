import Icon from '../ui/Icon'
import { cn } from '../../lib/cn'

/**
 * Five-star rating with a fractional fill, plus an optional review count. Used
 * on every vendor/venue card, storefront header and review row.
 */
export default function RatingStars({ rating, count, size = 'size-4', showValue = true, className }) {
  const value = Number(rating) || 0

  return (
    <span className={cn('inline-flex items-center gap-1', className)}>
      <span className="relative inline-flex">
        {/* empty track */}
        <span className="flex text-line">
          {[0, 1, 2, 3, 4].map((i) => (
            <Icon key={i} name="Star" className={cn(size, 'fill-current')} />
          ))}
        </span>
        {/* filled overlay clipped to the score */}
        <span
          className="absolute inset-0 flex overflow-hidden text-warning"
          style={{ width: `${(value / 5) * 100}%` }}
        >
          {[0, 1, 2, 3, 4].map((i) => (
            <Icon key={i} name="Star" className={cn(size, 'shrink-0 fill-current')} />
          ))}
        </span>
      </span>
      {showValue && value > 0 && <span className="text-sm font-semibold text-ink tabular-nums">{value.toFixed(1)}</span>}
      {count != null && <span className="text-sm text-muted">({count})</span>}
    </span>
  )
}
