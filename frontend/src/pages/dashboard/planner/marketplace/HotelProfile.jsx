import { useState } from 'react'
import { useParams, Link } from 'react-router-dom'
import Card from '../../../../components/ui/Card'
import Icon from '../../../../components/ui/Icon'
import Badge from '../../../../components/ui/Badge'
import Button from '../../../../components/ui/Button'
import LoadState from '../../../../components/dashboard/LoadState'
import RoomBookingModal from '../../../../components/marketplace/RoomBookingModal'
import RatingStars from '../../../../components/marketplace/RatingStars'
import { useResource } from '../../../../lib/useResource'
import { formatCurrency, formatDate } from '../../../../lib/format'

function Stars({ count }) {
  if (!count) return null
  return (
    <span className="inline-flex items-center gap-0.5 text-warning">
      {Array.from({ length: count }).map((_, i) => <Icon key={i} name="Star" className="size-4 fill-current" />)}
    </span>
  )
}

export default function HotelProfile() {
  const { slug } = useParams()
  const { data, loading, error, reload } = useResource(`/marketplace/accommodations/${slug}`)
  const [room, setRoom] = useState(null)

  const hotel = data?.accommodation
  const reviews = data?.reviews ?? []

  return (
    <div className="space-y-6">
      <Link to=".." className="inline-flex items-center gap-1 text-sm font-semibold text-muted hover:text-ink">
        <Icon name="ArrowLeft" className="size-4" /> Back to hotels
      </Link>

      <LoadState loading={loading} error={error} onRetry={reload}>
        {hotel && (
          <>
            {/* Hero */}
            <Card className="overflow-hidden p-0">
              <div className="relative aspect-[21/9] bg-canvas">
                {hotel.cover_image_url && <img src={hotel.cover_image_url} alt="" className="h-full w-full object-cover" />}
              </div>
              <div className="p-5 md:p-6">
                <div className="flex flex-wrap items-center gap-2">
                  <Stars count={hotel.star_rating} />
                  {hotel.city && <Badge tone="muted">{hotel.city}</Badge>}
                  {hotel.reviews_count > 0 && (
                    <RatingStars rating={hotel.rating} count={hotel.reviews_count} size="size-4" />
                  )}
                </div>
                <h1 className="mt-2 text-h3 font-extrabold tracking-tight text-ink">{hotel.name}</h1>
                {hotel.location && (
                  <p className="mt-1 flex items-center gap-1.5 text-sm text-muted">
                    <Icon name="MapPin" className="size-4" />{hotel.location}
                  </p>
                )}
                {hotel.description && <p className="mt-4 max-w-3xl leading-relaxed text-muted">{hotel.description}</p>}

                <div className="mt-5 flex flex-wrap gap-4 text-sm text-muted">
                  {hotel.check_in_time && <span className="flex items-center gap-1.5"><Icon name="CalendarCheck2" className="size-4" /> Check-in {hotel.check_in_time}</span>}
                  {hotel.check_out_time && <span className="flex items-center gap-1.5"><Icon name="CalendarClock" className="size-4" /> Check-out {hotel.check_out_time}</span>}
                  {hotel.contact_phone && <span className="flex items-center gap-1.5"><Icon name="Phone" className="size-4" /> {hotel.contact_phone}</span>}
                </div>

                {hotel.amenities?.length > 0 && (
                  <div className="mt-5 flex flex-wrap gap-2">
                    {hotel.amenities.map((a) => (
                      <span key={a} className="inline-flex items-center gap-1 rounded-full bg-canvas px-3 py-1 text-xs font-medium text-ink">
                        <Icon name="Check" className="size-3 text-emerald-600" />{a}
                      </span>
                    ))}
                  </div>
                )}
                {hotel.policies && (
                  <p className="mt-5 rounded-btn bg-canvas px-3 py-2 text-xs text-muted"><Icon name="Info" className="mr-1 inline size-3.5" />{hotel.policies}</p>
                )}
              </div>
            </Card>

            {/* Rooms */}
            <div>
              <h2 className="mb-3 flex items-center gap-2 text-lg font-bold text-ink">
                <Icon name="BedDouble" className="size-5 text-navy-600" /> Choose a room
              </h2>
              <div className="space-y-4">
                {(hotel.room_types ?? []).map((r) => (
                  <Card key={r.id} className="flex flex-col gap-4 p-4 sm:flex-row">
                    <div className="aspect-[4/3] w-full shrink-0 overflow-hidden rounded-xl bg-canvas sm:w-52">
                      {r.image_url && <img src={r.image_url} alt="" className="h-full w-full object-cover" loading="lazy" />}
                    </div>
                    <div className="flex min-w-0 flex-1 flex-col">
                      <div className="flex flex-wrap items-start justify-between gap-2">
                        <p className="font-bold text-ink">{r.name}</p>
                        <p className="text-right">
                          <span className="text-lg font-extrabold text-navy-800 dark:text-navy-200">{formatCurrency(r.price_per_night)}</span>
                          <span className="text-xs text-muted">/night</span>
                        </p>
                      </div>
                      {r.description && <p className="mt-1 text-sm text-muted">{r.description}</p>}
                      <div className="mt-2 flex flex-wrap gap-3 text-xs text-muted">
                        <span className="flex items-center gap-1"><Icon name="Users" className="size-3.5" /> Sleeps {r.capacity}</span>
                        {r.bed_configuration && <span className="flex items-center gap-1"><Icon name="BedDouble" className="size-3.5" /> {r.bed_configuration}</span>}
                        {r.size_sqm && <span>{r.size_sqm} m²</span>}
                      </div>
                      {r.amenities?.length > 0 && (
                        <div className="mt-2 flex flex-wrap gap-1">
                          {r.amenities.map((a) => <span key={a} className="rounded-full bg-canvas px-2 py-0.5 text-[11px] text-muted">{a}</span>)}
                        </div>
                      )}
                      <div className="mt-3 flex-1" />
                      <div>
                        <Button size="sm" onClick={() => setRoom(r)}><Icon name="CalendarCheck2" className="size-4" /> Book this room</Button>
                      </div>
                    </div>
                  </Card>
                ))}
                {(hotel.room_types ?? []).length === 0 && (
                  <Card className="p-6 text-center text-sm text-muted">No rooms are listed for this hotel yet.</Card>
                )}
              </div>
            </div>

            {/* Guest reviews */}
            {reviews.length > 0 && (
              <div>
                <h2 className="mb-3 flex items-center gap-2 text-lg font-bold text-ink">
                  <Icon name="Star" className="size-5 fill-warning text-warning" /> Guest reviews
                  {hotel.reviews_count > 0 && (
                    <span className="text-sm font-normal text-muted">
                      · {hotel.rating.toFixed(1)} average from {hotel.reviews_count}
                    </span>
                  )}
                </h2>
                <div className="grid gap-4 sm:grid-cols-2">
                  {reviews.map((r) => (
                    <Card key={r.id} className="p-4">
                      <div className="flex items-center justify-between gap-2">
                        <div className="flex items-center gap-2">
                          {r.reviewer.avatar_url ? (
                            <img src={r.reviewer.avatar_url} alt="" className="size-8 rounded-full object-cover" />
                          ) : (
                            <div className="grid size-8 place-items-center rounded-full bg-navy-100 text-xs font-bold text-navy-700 dark:bg-navy-900 dark:text-navy-200">
                              {(r.reviewer.full_name ?? '?').charAt(0)}
                            </div>
                          )}
                          <span className="text-sm font-semibold text-ink">{r.reviewer.full_name ?? 'Guest'}</span>
                        </div>
                        <RatingStars rating={r.rating} showValue={false} size="size-3.5" />
                      </div>
                      {r.comment && <p className="mt-2 text-sm leading-relaxed text-muted">{r.comment}</p>}
                      <p className="mt-1 text-xs text-muted">{formatDate(r.created_at)}</p>
                    </Card>
                  ))}
                </div>
              </div>
            )}

            <RoomBookingModal open={!!room} onClose={() => setRoom(null)} hotel={hotel} room={room} onBooked={reload} />
          </>
        )}
      </LoadState>
    </div>
  )
}
