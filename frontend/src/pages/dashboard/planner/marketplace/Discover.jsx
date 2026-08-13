import { useState } from 'react'
import { useNavigate } from 'react-router-dom'
import Icon from '../../../../components/ui/Icon'
import Card from '../../../../components/ui/Card'
import LoadState from '../../../../components/dashboard/LoadState'
import VendorCard from '../../../../components/marketplace/VendorCard'
import VenueCard from '../../../../components/marketplace/VenueCard'
import { useResource } from '../../../../lib/useResource'
import { useSaved } from '../../../../lib/useSaved'
import { formatNumber } from '../../../../lib/format'

export default function Discover() {
  const { data, loading, error, reload } = useResource('/marketplace/discover')
  const { savedKeys, save } = useSaved()
  const navigate = useNavigate()
  const [q, setQ] = useState('')

  const base = '/dashboard/planner/marketplace'
  const search = (e) => {
    e.preventDefault()
    navigate(`${base}/vendors?q=${encodeURIComponent(q)}`)
  }

  return (
    <div className="space-y-8">
      {/* Hero search */}
      <Card className="relative overflow-hidden bg-navy-900 p-6 text-white">
        <div className="absolute -right-16 -top-16 size-56 rounded-full bg-white/5" />
        <div className="absolute -bottom-20 right-24 size-48 rounded-full bg-white/5" />
        <div className="relative max-w-xl">
          <h2 className="text-xl font-extrabold tracking-tight">Find the perfect team for your event</h2>
          <p className="mt-1 text-sm text-white/70">Search verified vendors and venues across every category.</p>
          <form onSubmit={search} className="mt-4 flex max-w-sm gap-2">
            <div className="relative flex-1">
              <Icon name="Search" className="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-navy-900/50" />
              <input
                value={q}
                onChange={(e) => setQ(e.target.value)}
                placeholder="Search vendors & venues"
                className="h-9 w-full rounded-btn bg-white pl-9 pr-3 text-sm text-navy-950 outline-none"
              />
            </div>
            <button type="submit" className="h-9 shrink-0 rounded-btn bg-white/15 px-3.5 text-sm font-semibold text-white backdrop-blur hover:bg-white/25">Search</button>
          </form>
          {data?.stats && (
            <div className="mt-4 flex gap-6 text-sm">
              <span><span className="font-extrabold">{formatNumber(data.stats.vendors)}</span> <span className="text-white/60">vendors</span></span>
              <span><span className="font-extrabold">{formatNumber(data.stats.venues)}</span> <span className="text-white/60">venues</span></span>
              <span><span className="font-extrabold">{formatNumber(data.stats.categories)}</span> <span className="text-white/60">categories</span></span>
            </div>
          )}
        </div>
      </Card>

      <LoadState loading={loading} error={error} onRetry={reload}>
        {data && (
          <div className="space-y-8">
            {/* Category chips */}
            <section>
              <div className="flex flex-wrap gap-2">
                {data.categories.slice(0, 12).map((c) => (
                  <button
                    key={c.id}
                    onClick={() => navigate(`${base}/vendors?category_id=${c.id}`)}
                    className="flex items-center gap-2 rounded-full border border-line bg-surface px-3.5 py-2 text-sm font-semibold text-ink transition hover:border-navy-300 hover:bg-navy-50"
                  >
                    <Icon name={c.icon ?? 'Store'} className="size-4 text-navy-700" />
                    {c.name}
                    <span className="text-xs text-muted">{c.vendors_count ?? 0}</span>
                  </button>
                ))}
              </div>
            </section>

            <Row title="Featured vendors" icon="Crown" to={`${base}/vendors`} navigate={navigate}>
              {(data.featured_vendors.length ? data.featured_vendors : data.top_vendors).slice(0, 6).map((v) => (
                <VendorCard key={v.id} vendor={v} saved={savedKeys.has(`vendor:${v.id}`)} onSave={() => save(v, 'vendor')} />
              ))}
            </Row>

            <Row title="Featured venues" icon="Building" to={`${base}/venues`} navigate={navigate}>
              {(data.featured_venues.length ? data.featured_venues : data.top_venues).slice(0, 6).map((v) => (
                <VenueCard key={v.id} venue={v} saved={savedKeys.has(`venue:${v.id}`)} onSave={() => save(v, 'venue')} />
              ))}
            </Row>

            <Row title="Top rated vendors" icon="Star" to={`${base}/vendors`} navigate={navigate}>
              {data.top_vendors.slice(0, 3).map((v) => (
                <VendorCard key={v.id} vendor={v} saved={savedKeys.has(`vendor:${v.id}`)} onSave={() => save(v, 'vendor')} />
              ))}
            </Row>
          </div>
        )}
      </LoadState>
    </div>
  )
}

function Row({ title, icon, to, navigate, children }) {
  const items = Array.isArray(children) ? children.filter(Boolean) : [children]
  if (!items.length) return null

  return (
    <section>
      <div className="mb-3 flex items-center justify-between">
        <h3 className="flex items-center gap-2 text-lg font-bold text-ink">
          <Icon name={icon} className="size-5 text-navy-700" /> {title}
        </h3>
        <button onClick={() => navigate(to)} className="flex items-center gap-1 text-sm font-semibold text-navy-700 hover:underline">
          View all <Icon name="ArrowRight" className="size-4" />
        </button>
      </div>
      <div className="grid gap-5 sm:grid-cols-2 xl:grid-cols-3">{items}</div>
    </section>
  )
}
