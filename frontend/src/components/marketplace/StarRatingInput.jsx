import { useState } from 'react'
import Icon from '../ui/Icon'
import { cn } from '../../lib/cn'

/** Interactive 1–5 star picker for the review form. */
export default function StarRatingInput({ label, value, onChange }) {
  const [hover, setHover] = useState(0)

  return (
    <div className="flex items-center justify-between gap-3">
      <span className="text-sm font-semibold text-ink">{label}</span>
      <span className="flex items-center gap-0.5" onMouseLeave={() => setHover(0)}>
        {[1, 2, 3, 4, 5].map((n) => (
          <button
            key={n}
            type="button"
            onClick={() => onChange(n)}
            onMouseEnter={() => setHover(n)}
            aria-label={`${n} stars`}
          >
            <Icon name="Star" className={cn('size-6 transition-colors', (hover || value) >= n ? 'fill-warning text-warning' : 'fill-line text-line')} />
          </button>
        ))}
      </span>
    </div>
  )
}
