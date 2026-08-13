import { useState } from 'react'
import { useParams, useNavigate } from 'react-router-dom'
import Card from '../../../../components/ui/Card'
import Icon from '../../../../components/ui/Icon'
import Button from '../../../../components/ui/Button'
import Badge from '../../../../components/ui/Badge'
import LoadState from '../../../../components/dashboard/LoadState'
import RatingStars from '../../../../components/marketplace/RatingStars'
import VerificationBadge from '../../../../components/marketplace/VerificationBadge'
import ReviewList from '../../../../components/marketplace/ReviewList'
import BookingRequestModal from '../../../../components/marketplace/BookingRequestModal'
import ReviewModal from '../../../../components/marketplace/ReviewModal'
import MessageComposeModal from '../../../../components/marketplace/MessageComposeModal'
import { useResource } from '../../../../lib/useResource'
import { formatCurrency } from '../../../../lib/format'
import { SETTING_LABELS } from '../../../../lib/marketplace'

export default function VenueProfile() {
  const { venueId } = useParams()
  const navigate = useNavigate()
  const { data, loading, error, reload } = useResource(`/marketplace/venues/${venueId}`)
  const [modal, setModal] = useState(null)
  const [active, setActive] = useState(0)

  const venue = data?.venue
  const provider = venue ? { type: 'venue', id: venue.id, name: venue.name } : null
  const gallery = venue ? [venue.cover_image_url, ...(venue.images ?? []).map((i) => i.url)].filter(Boolean) : []

  return (
    <div className="space-y-6">
      <button onClick={() => navigate(-1)} className="flex items-center gap-1.5 text-sm font-semibold text-muted hover:text-ink">
        <Icon name="ArrowLeft" className="size-4" /> Back
      </button>

      <LoadState loading={loading} error={error} onRetry={reload}>
        {venue && (
          <div className="grid gap-6 lg:grid-cols-3">
            <div className="space-y-6 lg:col-span-2">
              {/* Gallery */}
              <Card className="overflow-hidden">
                <div className="h-64 w-full bg-canvas sm:h-80">
                  {gallery[active] && <img src={gallery[active]} alt="" className="size-full object-cover" />}
                </div>
                {gallery.length > 1 && (
                  <div className="flex gap-2 overflow-x-auto p-3">
                    {gallery.map((src, i) => (
                      <button key={i} onClick={() => setActive(i)} className={`h-16 w-24 shrink-0 overflow-hidden rounded-btn border-2 ${i === active ? 'border-navy-700' : 'border-transparent'}`}>
                        <img src={src} alt="" className="size-full object-cover" />
                      </button>
                    ))}
                  </div>
                )}
              </Card>

              <Card className="p-6">
                <h3 className="font-bold text-ink">About this venue</h3>
                <p className="mt-2 text-sm text-muted">{venue.description ?? 'No description provided.'}</p>

                <div className="mt-5 grid gap-4 sm:grid-cols-2">
                  <Detail icon="Users2" label="Capacity" value={`${venue.min_capacity ? `${venue.min_capacity}–` : 'Up to '}${venue.capacity ?? '—'} guests`} />
                  <Detail icon="LayoutGrid" label="Setting" value={SETTING_LABELS[venue.setting]} />
                  {venue.dimensions && <Detail icon="Scale" label="Dimensions" value={venue.dimensions} />}
                  {venue.setup_time && <Detail icon="Clock" label="Setup / breakdown" value={`${venue.setup_time} / ${venue.breakdown_time ?? '—'}`} />}
                  <Detail icon="Car" label="Parking" value={venue.parking_available ? `Yes${venue.parking_capacity ? ` (${venue.parking_capacity})` : ''}` : 'No'} />
                </div>

                {venue.facilities?.length > 0 && <Chips title="Facilities" items={venue.facilities} />}
                {venue.included_equipment?.length > 0 && <Chips title="Included equipment" items={venue.included_equipment} />}
                {venue.accessibility?.length > 0 && <Chips title="Accessibility" items={venue.accessibility} />}
                {venue.layout_options?.length > 0 && <Chips title="Layout options" items={venue.layout_options} />}

                {venue.restrictions && (
                  <div className="mt-5 rounded-btn bg-warning-soft p-4">
                    <p className="flex items-center gap-1.5 text-sm font-bold text-warning"><Icon name="Info" className="size-4" /> Restrictions</p>
                    <p className="mt-1 text-sm text-ink">{venue.restrictions}</p>
                  </div>
                )}
              </Card>

              <Card className="p-6">
                <h3 className="font-bold text-ink">Reviews ({venue.reviews_count})</h3>
                <div className="mt-4"><ReviewList reviews={venue.reviews} /></div>
              </Card>
            </div>

            {/* Sticky booking sidebar */}
            <div>
              <Card className="sticky top-24 p-6">
                <div className="flex items-center gap-2">
                  <h2 className="text-xl font-extrabold tracking-tight text-ink">{venue.name}</h2>
                </div>
                <p className="text-sm text-muted">{venue.venue_type}</p>
                <div className="mt-2"><VerificationBadge level={venue.verification_level} /></div>

                <div className="mt-3"><RatingStars rating={venue.rating} count={venue.reviews_count} /></div>

                <p className="mt-4 text-3xl font-extrabold text-navy-800">{venue.price != null ? formatCurrency(venue.price) : 'On request'}</p>
                <p className="text-xs text-muted">{venue.price_unit}</p>

                <p className="mt-3 flex items-center gap-1.5 text-sm text-muted"><Icon name="MapPin" className="size-4" />{venue.address ?? venue.location}</p>

                <div className="mt-5 space-y-2">
                  <Button fullWidth onClick={() => setModal('book')}><Icon name="ClipboardList" className="size-4" /> Request booking</Button>
                  {venue.owner_id && (
                    <Button fullWidth variant="secondary" to={`/dashboard/planner/messages?to=${venue.owner_id}`}><Icon name="MessageSquare" className="size-4" /> Message</Button>
                  )}
                  <Button fullWidth variant="ghost" onClick={() => setModal('review')}><Icon name="Star" className="size-4" /> Leave a review</Button>
                </div>

                {venue.booking_terms && <p className="mt-4 border-t border-line pt-4 text-xs text-muted">{venue.booking_terms}</p>}
              </Card>
            </div>
          </div>
        )}
      </LoadState>

      {provider && (
        <>
          <BookingRequestModal open={modal === 'book'} onClose={() => setModal(null)} provider={provider} onSubmitted={reload} />
          <ReviewModal open={modal === 'review'} onClose={() => setModal(null)} provider={provider} onSubmitted={reload} />
          <MessageComposeModal open={modal === 'message'} onClose={() => setModal(null)} provider={provider} />
        </>
      )}
    </div>
  )
}

function Detail({ icon, label, value }) {
  return (
    <div className="flex items-start gap-2.5">
      <span className="grid size-9 shrink-0 place-items-center rounded-lg bg-canvas text-navy-700"><Icon name={icon} className="size-4" /></span>
      <div>
        <p className="text-xs font-semibold uppercase tracking-wide text-muted">{label}</p>
        <p className="text-sm font-semibold text-ink">{value}</p>
      </div>
    </div>
  )
}

function Chips({ title, items }) {
  return (
    <div className="mt-5">
      <h4 className="text-sm font-bold text-ink">{title}</h4>
      <div className="mt-2 flex flex-wrap gap-2">
        {items.map((it, i) => <Badge key={i} tone="muted">{it}</Badge>)}
      </div>
    </div>
  )
}
