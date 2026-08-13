import { useEffect, useMemo, useState } from 'react'
import { useSearchParams } from 'react-router-dom'
import Icon from '../../../../components/ui/Icon'
import EmptyState from '../../../../components/ui/EmptyState'
import Button from '../../../../components/ui/Button'
import LoadState from '../../../../components/dashboard/LoadState'
import VendorCard from '../../../../components/marketplace/VendorCard'
import FilterPanel from '../../../../components/marketplace/FilterPanel'
import CompareDrawer from '../../../../components/marketplace/CompareDrawer'
import ListboxSelect from '../../../../components/ui/ListboxSelect'
import { useResource } from '../../../../lib/useResource'
import { useSaved } from '../../../../lib/useSaved'

const EMPTY = { q: '', category_id: '', location: '', min_rating: '', verified: false, max_price: '', sort: 'rating' }

function buildQuery(values) {
  const params = new URLSearchParams()
  Object.entries(values).forEach(([k, v]) => {
    if (v === '' || v === false || v == null) return
    params.set(k, v === true ? '1' : v)
  })
  return params.toString()
}

export default function VendorsBrowse() {
  const [searchParams] = useSearchParams()
  const [filters, setFilters] = useState({ ...EMPTY, category_id: searchParams.get('category_id') ?? '', q: searchParams.get('q') ?? '' })
  const [debounced, setDebounced] = useState(filters)
  const [compare, setCompare] = useState([])
  const [compareOpen, setCompareOpen] = useState(false)

  useEffect(() => {
    const id = setTimeout(() => setDebounced(filters), 350)
    return () => clearTimeout(id)
  }, [filters])

  const { data: catData } = useResource('/marketplace/categories')
  const path = useMemo(() => `/marketplace/vendors?${buildQuery(debounced)}`, [debounced])
  const { data, loading, error, reload } = useResource(path)

  const { savedKeys, save } = useSaved()

  const patch = (p) => setFilters((f) => ({ ...f, ...p }))
  const toggleCompare = (vendor) =>
    setCompare((c) => (c.find((x) => x.id === vendor.id) ? c.filter((x) => x.id !== vendor.id) : [...c, vendor].slice(0, 4)))

  return (
    <div className="space-y-5">
      <div className="flex flex-col gap-3 sm:flex-row sm:items-center">
        <div className="relative flex-1">
          <Icon name="Search" className="pointer-events-none absolute left-3.5 top-1/2 size-4 -translate-y-1/2 text-muted" />
          <input
            value={filters.q}
            onChange={(e) => patch({ q: e.target.value })}
            placeholder="Search vendors by name or category"
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
          <option value="reviews">Most reviewed</option>
          <option value="price_low">Price: low to high</option>
          <option value="price_high">Price: high to low</option>
          <option value="newest">Newest</option>
        </ListboxSelect>
      </div>

      <div className="grid gap-6 lg:grid-cols-[260px_1fr]">
        <div className="hidden lg:block">
          <FilterPanel type="vendor" values={filters} onChange={patch} categories={catData?.categories ?? []} onReset={() => setFilters(EMPTY)} />
        </div>

        <div>
          <LoadState loading={loading} error={error} onRetry={reload}>
            {data && (data.vendors.length ? (
              <>
                <p className="mb-3 text-sm text-muted">{data.meta?.total ?? data.vendors.length} vendors</p>
                <div className="grid gap-5 sm:grid-cols-2 xl:grid-cols-3">
                  {data.vendors.map((v) => (
                    <VendorCard
                      key={v.id}
                      vendor={v}
                      saved={savedKeys.has(`vendor:${v.id}`)}
                      onSave={() => save(v, 'vendor')}
                      onCompare={toggleCompare}
                      compareChecked={!!compare.find((x) => x.id === v.id)}
                    />
                  ))}
                </div>
              </>
            ) : (
              <EmptyState icon="Store" title="No vendors match your filters" description="Try widening your search or resetting the filters." action={<Button variant="secondary" onClick={() => setFilters(EMPTY)}>Reset filters</Button>} />
            ))}
          </LoadState>
        </div>
      </div>

      <CompareDrawer
        items={compare}
        open={compareOpen}
        onToggle={() => setCompareOpen((o) => !o)}
        onRemove={toggleCompare}
        onClear={() => setCompare([])}
      />
    </div>
  )
}
