import { useState } from 'react'
import { Link } from 'react-router-dom'
import Card from '../../../../components/ui/Card'
import Icon from '../../../../components/ui/Icon'
import Badge from '../../../../components/ui/Badge'
import Button from '../../../../components/ui/Button'
import EmptyState from '../../../../components/ui/EmptyState'
import LoadState from '../../../../components/dashboard/LoadState'
import { useResource } from '../../../../lib/useResource'
import { api } from '../../../../lib/api'
import { formatCurrency, formatDate } from '../../../../lib/format'
import { ACCOMMODATION_BOOKING_STATUS, statusMeta } from '../../../../lib/marketplace'

export default function AccommodationBookings() {
  const { data, loading, error, reload } = useResource('/marketplace/accommodation-bookings')
  const [busy, setBusy] = useState(null)
  const bookings = data?.bookings ?? []

  const cancel = async (b) => {
    setBusy(b.id)
    try {
      await api.post(`/marketplace/accommodation-bookings/${b.id}/cancel`)
      reload()
    } catch { /* ignore */ } finally {
      setBusy(null)
    }
  }

  return (
    <div className="space-y-5">
      <div className="flex items-center justify-between">
        <Link to=".." className="inline-flex items-center gap-1 text-sm font-semibold text-muted hover:text-ink">
          <Icon name="ArrowLeft" className="size-4" /> Back to hotels
        </Link>
      </div>

      <LoadState loading={loading} error={error} onRetry={reload}>
        {bookings.length === 0 ? (
          <EmptyState
            icon="BedDouble"
            title="No accommodation bookings yet"
            description="Browse hotels and book a honeymoon stay or rooms for your client's guests."
            action={<Button to="..">Browse hotels</Button>}
          />
        ) : (
          <div className="space-y-4">
            {bookings.map((b) => {
              const meta = statusMeta(ACCOMMODATION_BOOKING_STATUS, b.status)
              return (
                <Card key={b.id} className="flex flex-col gap-4 p-4 sm:flex-row sm:items-center">
                  <div className="aspect-[4/3] w-full shrink-0 overflow-hidden rounded-xl bg-canvas sm:size-24">
                    {b.accommodation?.cover_image_url && <img src={b.accommodation.cover_image_url} alt="" className="h-full w-full object-cover" loading="lazy" />}
                  </div>
                  <div className="min-w-0 flex-1">
                    <div className="flex flex-wrap items-center gap-2">
                      <p className="font-bold text-ink">{b.accommodation?.name}</p>
                      <Badge tone={meta.tone} dot>{meta.label}</Badge>
                    </div>
                    <p className="text-sm text-muted">{b.room_type?.name} · {b.reference}</p>
                    <div className="mt-1 flex flex-wrap gap-x-4 gap-y-1 text-xs text-muted">
                      <span className="flex items-center gap-1"><Icon name="CalendarCheck2" className="size-3.5" /> {formatDate(b.check_in)} → {formatDate(b.check_out)} ({b.nights} night{b.nights > 1 ? 's' : ''})</span>
                      <span className="flex items-center gap-1"><Icon name="Users" className="size-3.5" /> {b.guests} guest{b.guests > 1 ? 's' : ''}, {b.rooms} room{b.rooms > 1 ? 's' : ''}</span>
                      <span>For {b.guest_name}{b.client?.full_name ? ` (${b.client.full_name})` : ''}</span>
                    </div>
                    {b.special_requests && <p className="mt-1 text-xs italic text-muted">“{b.special_requests}”</p>}
                  </div>
                  <div className="flex items-center justify-between gap-3 sm:flex-col sm:items-end">
                    <p className="text-lg font-extrabold text-ink">{formatCurrency(b.total_price)}</p>
                    {b.status === 'confirmed' && (
                      <button
                        type="button"
                        onClick={() => cancel(b)}
                        disabled={busy === b.id}
                        className="text-xs font-semibold text-danger hover:underline disabled:opacity-50"
                      >
                        Cancel booking
                      </button>
                    )}
                  </div>
                </Card>
              )
            })}
          </div>
        )}
      </LoadState>
    </div>
  )
}
