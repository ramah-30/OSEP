import { useEffect, useMemo, useState } from 'react'
import Icon from '../../../../components/ui/Icon'
import EmptyState from '../../../../components/ui/EmptyState'
import Button from '../../../../components/ui/Button'
import LoadState from '../../../../components/dashboard/LoadState'
import VenueCard from '../../../../components/marketplace/VenueCard'
import FilterPanel from '../../../../components/marketplace/FilterPanel'
import ListboxSelect from '../../../../components/ui/ListboxSelect'
import { useResource } from '../../../../lib/useResource'
import { useSaved } from '../../../../lib/useSaved'

const EMPTY = { q: '', location: '', min_rating: '', verified: false, max_price: '', setting: '', min_capacity: '', parking: false, sort: 'rating' }

function buildQuery(values) {
  const params = new URLSearchParams()
  Object.entries(values).forEach(([k, v]) => {
    if (v === '' || v === false || v == null) return
    params.set(k, v === true ? '1' : v)
  })
  return params.toString()
}

export default function VenuesBrowse() {
  const [filters, setFilters] = useState(EMPTY)
  const [debounced, setDebounced] = useState(EMPTY)

  useEffect(() => {
    const id = setTimeout(() => setDebounced(filters), 350)
    return () => clearTimeout(id)
  }, [filters])

  const path = useMemo(() => `/marketplace/venues?${buildQuery(debounced)}`, [debounced])
  const { data, loading, error, reload } = useResource(path)
  const { savedKeys, save } = useSaved()

  const patch = (p) => setFilters((f) => ({ ...f, ...p }))

  return (
    <div className="space-y-5">
      <div className="flex flex-col gap-3 sm:flex-row sm:items-center">
        <div className="relative flex-1">
          <Icon name="Search" className="pointer-events-none absolute left-3.5 top-1/2 size-4 -translate-y-1/2 text-muted" />
          <input
            value={filters.q}
            onChange={(e) => patch({ q: e.target.value })}
            placeholder="Search venues by name, type or location"
            className="h-11 w-full rounded-btn border border-line bg-surface pl-10 pr-4 text-sm text-ink outline-none focus:border-navy-600 focus:shadow-[0_0_0_3px_rgba(41,71,200,0.12)]"
          />
        </div>
        <ListboxSelect
          className="w-full sm:w-56"
          heightClass="h-11"
          value={filters.sort}
          onChange={(e) => patch({ sort: e.target.value })}
        >
          <option value="rating">Top rated</option>
          <option value="capacity">Largest capacity</option>
          <option value="price_low">Price: low to high</option>
          <option value="price_high">Price: high to low</option>
          <option value="reviews">Most reviewed</option>
        </ListboxSelect>
      </div>

      <div className="grid gap-6 lg:grid-cols-[260px_1fr]">
        <div className="hidden lg:block">
          <FilterPanel type="venue" values={filters} onChange={patch} onReset={() => setFilters(EMPTY)} />
        </div>

        <div>
          <LoadState loading={loading} error={error} onRetry={reload}>
            {data && (data.venues.length ? (
              <>
                <p className="mb-3 text-sm text-muted">{data.meta?.total ?? data.venues.length} venues</p>
                <div className="grid gap-5 sm:grid-cols-2 xl:grid-cols-3">
                  {data.venues.map((v) => (
                    <VenueCard key={v.id} venue={v} saved={savedKeys.has(`venue:${v.id}`)} onSave={() => save(v, 'venue')} />
                  ))}
                </div>
              </>
            ) : (
              <EmptyState icon="Building" title="No venues match your filters" description="Try widening your search or resetting the filters." action={<Button variant="secondary" onClick={() => setFilters(EMPTY)}>Reset filters</Button>} />
            ))}
          </LoadState>
        </div>
      </div>
    </div>
  )
}
