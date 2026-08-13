import { Link } from 'react-router-dom'
import Card from '../ui/Card'
import Icon from '../ui/Icon'
import Avatar from '../ui/Avatar'
import Badge from '../ui/Badge'
import RatingStars from './RatingStars'
import VerificationBadge from './VerificationBadge'
import { cn } from '../../lib/cn'
import { formatCurrencyCompact } from '../../lib/format'

/**
 * A vendor result card for the directory, discover feed and saved lists. The
 * whole card is a stretched link to the vendor profile; the save heart and
 * compare checkbox sit above it and stop propagation so they still work without
 * triggering navigation. Everything below the overlay is pointer-events-none.
 */
export default function VendorCard({ vendor, to, onSave, saved, onCompare, compareChecked }) {
  const href = to ?? `/dashboard/planner/marketplace/vendors/${vendor.id}`

  return (
    <Card hover className="group relative flex flex-col p-4">
      <Link to={href} aria-label={vendor.business_name} className="absolute inset-0 z-0 rounded-card" />

      {onSave && (
        <button
          type="button"
          onClick={(e) => { e.preventDefault(); e.stopPropagation(); onSave(vendor) }}
          aria-label={saved ? 'Saved' : 'Save vendor'}
          className="absolute right-3 top-3 z-20 grid size-8 place-items-center rounded-full bg-canvas text-navy-800 shadow-sm transition hover:bg-navy-50"
        >
          <Icon name={saved ? 'BookmarkCheck' : 'Bookmark'} className={cn('size-4', saved && 'text-purple-600')} />
        </button>
      )}

      {/* Identity row */}
      <div className="pointer-events-none relative z-10 flex items-center gap-3">
        <Avatar name={vendor.business_name} src={vendor.logo_url} size="md" className="shrink-0 ring-2 ring-line" />
        <div className="min-w-0 flex-1 pr-8">
          <p className="truncate font-bold leading-tight text-ink group-hover:text-navy-800">{vendor.business_name}</p>
          <p className="truncate text-sm text-muted">{vendor.category ?? '—'}</p>
        </div>
      </div>

      {/* Rating */}
      <div className="pointer-events-none relative z-10 mt-3">
        <RatingStars rating={vendor.rating} count={vendor.reviews_count} />
      </div>

      {/* Meta */}
      <div className="pointer-events-none relative z-10 mt-2 space-y-1 text-sm text-muted">
        <p className="flex items-center gap-1.5">
          <Icon name="MapPin" className="size-4 shrink-0" />
          <span className="truncate">{vendor.location ?? '—'}</span>
        </p>
        {vendor.price_from != null && (
          <p className="flex items-center gap-1.5">
            <Icon name="CircleDollarSign" className="size-4 shrink-0" />
            <span className="truncate">from {formatCurrencyCompact(vendor.price_from)}</span>
          </p>
        )}
      </div>

      {/* Footer */}
      <div className="relative z-10 mt-3 flex flex-wrap items-center gap-2 border-t border-line pt-3">
        <span className="pointer-events-none flex flex-wrap items-center gap-1.5">
          <VerificationBadge level={vendor.verification_level} />
          {vendor.is_featured && (
            <Badge tone="purple"><Icon name="Crown" className="size-3.5" /> Featured</Badge>
          )}
        </span>
        {onCompare && (
          <label
            className="z-20 ml-auto flex shrink-0 cursor-pointer items-center gap-1.5 text-xs font-semibold text-muted"
            onClick={(e) => e.stopPropagation()}
          >
            <input
              type="checkbox"
              checked={!!compareChecked}
              onChange={() => onCompare(vendor)}
              onClick={(e) => e.stopPropagation()}
              className="accent-navy-800"
            />
            Compare
          </label>
        )}
      </div>
    </Card>
  )
}
