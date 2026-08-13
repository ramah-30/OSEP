import { useEffect, useMemo, useState } from 'react'
import { Link } from 'react-router-dom'
import Card from '../../../../components/ui/Card'
import Icon from '../../../../components/ui/Icon'
import Badge from '../../../../components/ui/Badge'
import Button from '../../../../components/ui/Button'
import EmptyState from '../../../../components/ui/EmptyState'
import ListboxSelect from '../../../../components/ui/ListboxSelect'
import LoadState from '../../../../components/dashboard/LoadState'
import RatingStars from '../../../../components/marketplace/RatingStars'
import { useResource } from '../../../../lib/useResource'
import { formatCurrency } from '../../../../lib/format'

const EMPTY = { q: '', min_stars: '', guests: '', sort: 'featured' }

function buildQuery(values) {
  const params = new URLSearchParams()
  Object.entries(values).forEach(([k, v]) => {
    if (v === '' || v == null) return
    params.set(k, v)
  })
  return params.toString()
}

function Stars({ count }) {
  if (!count) return null
  return (
    <span className="inline-flex items-center gap-0.5 text-warning">
      {Array.from({ length: count }).map((_, i) => (
        <Icon key={i} name="Star" className="size-3.5 fill-current" />
      ))}
    </span>
  )
}

export default function HotelsBrowse() {
  const [filters, setFilters] = useState(EMPTY)
  const [debounced, setDebounced] = useState(EMPTY)

  useEffect(() => {
    const id = setTimeout(() => setDebounced(filters), 350)
    return () => clearTimeout(id)
  }, [filters])

  const path = useMemo(() => `/marketplace/accommodations?${buildQuery(debounced)}`, [debounced])
  const { data, loading, error, reload } = useResource(path)
  const patch = (p) => setFilters((f) => ({ ...f, ...p }))

  return (
    <div className="space-y-5">
      <div className="flex flex-col gap-2 rounded-2xl bg-purple-50 p-4 dark:bg-purple-950/40 sm:flex-row sm:items-center sm:gap-3">
        <span className="grid size-10 shrink-0 place-items-center rounded-xl bg-purple-100 text-purple-600 dark:bg-purple-900 dark:text-purple-300">
          <Icon name="Hotel" className="size-5" />
        </span>
        <div>
          <p className="font-bold text-ink">Hotels & honeymoon stays</p>
          <p className="text-sm text-muted">Book accommodation for your clients — a honeymoon escape or rooms for out-of-town guests.</p>
        </div>
        <Button to="bookings" variant="secondary" size="sm" className="sm:ml-auto">
          <Icon name="CalendarCheck2" className="size-4" /> My bookings
        </Button>
      </div>

      <div className="flex flex-col gap-3 sm:flex-row sm:items-center">
        <div className="relative flex-1">
          <Icon name="Search" className="pointer-events-none absolute left-3.5 top-1/2 size-4 -translate-y-1/2 text-muted" />
          <input
            value={filters.q}
            onChange={(e) => patch({ q: e.target.value })}
            placeholder="Search hotels by name or city"
            className="h-11 w-full rounded-btn border border-line bg-surface pl-10 pr-4 text-sm text-ink outline-none focus:border-navy-600 focus:shadow-[0_0_0_3px_rgba(41,71,200,0.12)]"
          />
        </div>
        <ListboxSelect className="w-full sm:w-40" heightClass="h-11" value={filters.min_stars} onChange={(e) => patch({ min_stars: e.target.value })}>
          <option value="">Any rating</option>
          <option value="5">5 stars</option>
          <option value="4">4+ stars</option>
          <option value="3">3+ stars</option>
        </ListboxSelect>
        <ListboxSelect className="w-full sm:w-44" heightClass="h-11" value={filters.sort} onChange={(e) => patch({ sort: e.target.value })}>
          <option value="featured">Featured</option>
          <option value="stars">Top rated</option>
          <option value="price_low">Price: low to high</option>
          <option value="price_high">Price: high to low</option>
        </ListboxSelect>
      </div>

      <LoadState loading={loading} error={error} onRetry={reload}>
        {data && (data.accommodations.length ? (
          <>
            <p className="text-sm text-muted">{data.meta?.total ?? data.accommodations.length} hotels</p>
            <div className="grid gap-5 sm:grid-cols-2 xl:grid-cols-3">
              {data.accommodations.map((h) => (
                <Card key={h.id} className="flex flex-col overflow-hidden p-0">
                  <Link to={h.slug} className="block">
                    <div className="relative aspect-[16/10] bg-canvas">
                      {h.cover_image_url && (
                        <img src={h.cover_image_url} alt="" className="h-full w-full object-cover" loading="lazy" />
                      )}
                      {h.is_featured && (
                        <span className="absolute left-3 top-3 rounded-full bg-purple-600 px-2 py-0.5 text-xs font-semibold text-white">Featured</span>
                      )}
                    </div>
                  </Link>
                  <div className="flex flex-1 flex-col p-4">
                    <div className="flex items-center justify-between gap-2">
                      <Stars count={h.star_rating} />
                      {h.city && <Badge tone="muted">{h.city}</Badge>}
                    </div>
                    <Link to={h.slug} className="mt-1 font-bold text-ink hover:text-navy-700">{h.name}</Link>
                    {h.location && (
                      <p className="mt-0.5 flex items-center gap-1 text-xs text-muted">
                        <Icon name="MapPin" className="size-3.5 shrink-0" /><span className="truncate">{h.location}</span>
                      </p>
                    )}
                    {h.reviews_count > 0 && (
                      <RatingStars rating={h.rating} count={h.reviews_count} size="size-3.5" className="mt-1.5" />
                    )}
                    {h.amenities?.length > 0 && (
                      <div className="mt-2 flex flex-wrap gap-1">
                        {h.amenities.slice(0, 3).map((a) => (
                          <span key={a} className="rounded-full bg-canvas px-2 py-0.5 text-[11px] text-muted">{a}</span>
                        ))}
                      </div>
                    )}
                    <div className="mt-4 flex-1" />
                    <div className="flex items-end justify-between">
                      <p className="text-sm text-muted">
                        from <span className="text-lg font-extrabold text-navy-800 dark:text-navy-200">{formatCurrency(h.price_from)}</span><span className="text-xs">/night</span>
                      </p>
                      <Button to={h.slug} size="sm">View rooms</Button>
                    </div>
                  </div>
                </Card>
              ))}
            </div>
          </>
        ) : (
          <EmptyState icon="Hotel" title="No hotels match your search" description="Try a different city or widen your filters." />
        ))}
      </LoadState>
    </div>
  )
}
