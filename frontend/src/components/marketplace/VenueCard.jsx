import { Link } from 'react-router-dom'
import Card from '../ui/Card'
import Icon from '../ui/Icon'
import RatingStars from './RatingStars'
import VerificationBadge from './VerificationBadge'
import { cn } from '../../lib/cn'
import { formatCurrencyCompact } from '../../lib/format'
import { SETTING_LABELS } from '../../lib/marketplace'

/** A venue result card mirroring VendorCard for a consistent grid. The whole
 *  card links to the venue detail; the save button stops propagation. */
export default function VenueCard({ venue, to, onSave, saved }) {
  const href = to ?? `/dashboard/planner/marketplace/venues/${venue.id}`

  return (
    <Card hover className="group relative flex flex-col overflow-hidden">
      <Link to={href} aria-label={venue.name} className="absolute inset-0 z-0" />

      {venue.is_featured && (
        <span className="pointer-events-none absolute left-3 top-3 z-10 inline-flex items-center gap-1 rounded-full bg-purple-600 px-2.5 py-1 text-[0.7rem] font-bold text-white shadow">
          <Icon name="Crown" className="size-3.5" /> Featured
        </span>
      )}

      {onSave && (
        <button
          type="button"
          onClick={(e) => { e.preventDefault(); e.stopPropagation(); onSave(venue) }}
          aria-label={saved ? 'Saved' : 'Save venue'}
          className="absolute right-3 top-3 z-20 grid size-9 place-items-center rounded-full bg-white/90 text-navy-800 shadow backdrop-blur transition hover:bg-white"
        >
          <Icon name={saved ? 'BookmarkCheck' : 'Bookmark'} className={cn('size-4', saved && 'text-purple-600')} />
        </button>
      )}

      <div className="pointer-events-none relative z-10 h-40 w-full overflow-hidden bg-canvas">
        {venue.cover_image_url ? (
          <img src={venue.cover_image_url} alt="" className="size-full object-cover transition duration-500 group-hover:scale-105" />
        ) : (
          <div className="size-full bg-gradient-to-br from-emerald-100 to-navy-50" />
        )}
        {venue.price != null && (
          <span className="absolute bottom-3 left-3 rounded-full bg-navy-950/70 px-2.5 py-1 text-xs font-semibold text-white backdrop-blur">
            {formatCurrencyCompact(venue.price)} {venue.price_unit ?? ''}
          </span>
        )}
      </div>

      <div className="pointer-events-none relative z-10 flex flex-1 flex-col p-5">
        <p className="truncate text-lg font-bold text-ink group-hover:text-navy-800">{venue.name}</p>
        <p className="text-sm text-muted">{venue.venue_type ?? SETTING_LABELS[venue.setting]}</p>

        <div className="mt-2">
          <RatingStars rating={venue.rating} count={venue.reviews_count} />
        </div>

        <div className="mt-3 flex flex-wrap items-center gap-x-4 gap-y-1.5 text-sm text-muted">
          <span className="flex items-center gap-1.5"><Icon name="Users2" className="size-4" />{venue.capacity ?? '—'} guests</span>
          <span className="flex items-center gap-1.5"><Icon name="MapPin" className="size-4" /><span className="truncate">{venue.location ?? '—'}</span></span>
        </div>

        <div className="mt-4 flex items-center justify-between gap-2 border-t border-line pt-3">
          <VerificationBadge level={venue.verification_level} />
          <span className="text-xs font-semibold text-muted">{SETTING_LABELS[venue.setting]}</span>
        </div>
      </div>
    </Card>
  )
}
