import Card from '../ui/Card'
import Icon from '../ui/Icon'
import ListboxSelect from '../ui/ListboxSelect'
import { cn } from '../../lib/cn'

/**
 * The marketplace filter rail. Controlled: the parent owns `values` and gets a
 * partial patch on every change. `type` toggles the vendor-only vs venue-only
 * controls; the shared ones (location, rating, price, verified) always show.
 */
export default function FilterPanel({ type = 'vendor', values, onChange, categories = [], onReset }) {
  const set = (patch) => onChange(patch)

  const ratingOptions = [
    { value: '', label: 'Any rating' },
    { value: '4.5', label: '4.5+ stars' },
    { value: '4', label: '4.0+ stars' },
    { value: '3.5', label: '3.5+ stars' },
  ]

  return (
    <Card className="sticky top-24 p-5">
      <div className="flex items-center justify-between">
        <p className="flex items-center gap-2 font-bold text-ink">
          <Icon name="SlidersHorizontal" className="size-4" /> Filters
        </p>
        {onReset && (
          <button type="button" onClick={onReset} className="text-xs font-semibold text-navy-700 hover:underline">
            Reset
          </button>
        )}
      </div>

      <div className="mt-4 space-y-5">
        {type === 'vendor' && categories.length > 0 && (
          <Filter label="Category">
            <ListboxSelect
              heightClass="h-10"
              value={values.category_id ?? ''}
              onChange={(e) => set({ category_id: e.target.value })}
            >
              <option value="">All categories</option>
              {categories.map((c) => (
                <option key={c.id} value={c.id}>{c.name}</option>
              ))}
            </ListboxSelect>
          </Filter>
        )}

        <Filter label="Location">
          <div className="relative">
            <Icon name="MapPin" className="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted" />
            <input
              value={values.location ?? ''}
              onChange={(e) => set({ location: e.target.value })}
              placeholder="City or region"
              className="h-10 w-full rounded-btn border border-line bg-surface pl-9 pr-3 text-sm text-ink outline-none focus:border-navy-600"
            />
          </div>
        </Filter>

        <Filter label="Rating">
          <div className="flex flex-wrap gap-2">
            {ratingOptions.map((o) => (
              <button
                key={o.value}
                type="button"
                onClick={() => set({ min_rating: o.value })}
                className={cn(
                  'rounded-full border px-3 py-1 text-xs font-semibold transition',
                  (values.min_rating ?? '') === o.value
                    ? 'border-navy-800 bg-navy-800 text-white'
                    : 'border-line text-muted hover:border-navy-300',
                )}
              >
                {o.label}
              </button>
            ))}
          </div>
        </Filter>

        {type === 'venue' && (
          <>
            <Filter label="Setting">
              <ListboxSelect
                heightClass="h-10"
                value={values.setting ?? ''}
                onChange={(e) => set({ setting: e.target.value })}
              >
                <option value="">Any setting</option>
                <option value="indoor">Indoor</option>
                <option value="outdoor">Outdoor</option>
                <option value="both">Indoor & Outdoor</option>
              </ListboxSelect>
            </Filter>
            <Filter label="Minimum capacity">
              <input
                type="number" min="0"
                value={values.min_capacity ?? ''}
                onChange={(e) => set({ min_capacity: e.target.value })}
                placeholder="e.g. 200"
                className="h-10 w-full rounded-btn border border-line bg-surface px-3 text-sm text-ink outline-none focus:border-navy-600"
              />
            </Filter>
            <label className="flex cursor-pointer items-center gap-2 text-sm font-medium text-ink">
              <input type="checkbox" checked={!!values.parking} onChange={(e) => set({ parking: e.target.checked })} className="accent-navy-800" />
              Parking available
            </label>
          </>
        )}

        <Filter label="Max price (TZS)">
          <input
            type="number" min="0" step="100000"
            value={values.max_price ?? ''}
            onChange={(e) => set({ max_price: e.target.value })}
            placeholder="No limit"
            className="h-10 w-full rounded-btn border border-line bg-surface px-3 text-sm text-ink outline-none focus:border-navy-600"
          />
        </Filter>

        <label className="flex cursor-pointer items-center gap-2 text-sm font-medium text-ink">
          <input type="checkbox" checked={!!values.verified} onChange={(e) => set({ verified: e.target.checked })} className="accent-navy-800" />
          Verified only
        </label>
      </div>
    </Card>
  )
}

function Filter({ label, children }) {
  return (
    <div>
      <p className="mb-2 text-xs font-bold uppercase tracking-wide text-muted">{label}</p>
      {children}
    </div>
  )
}
