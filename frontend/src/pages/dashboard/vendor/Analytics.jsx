import PageHeader from '../../../components/ui/PageHeader'
import Card from '../../../components/ui/Card'
import Icon from '../../../components/ui/Icon'
import LoadState from '../../../components/dashboard/LoadState'
import LineChart from '../../../components/charts/LineChart'
import BarChart from '../../../components/charts/BarChart'
import { useResource } from '../../../lib/useResource'
import { formatCurrency, formatNumber } from '../../../lib/format'

export default function Analytics() {
  const { data, loading, error, reload } = useResource('/marketplace/vendor/analytics')

  return (
    <div className="space-y-6">
      <PageHeader title="Analytics" description="How your business is performing on the marketplace." />

      <LoadState loading={loading} error={error} onRetry={reload}>
        {data && (
          <div className="space-y-6">
            <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
              <Tile icon="Percent" accent="emerald" label="Conversion rate" value={`${data.conversion_rate}%`} />
              <Tile icon="Timer" accent="navy" label="Avg response time" value={data.avg_response_hours != null ? `${data.avg_response_hours}h` : '—'} />
              <Tile icon="ClipboardList" accent="navy" label="Total requests" value={formatNumber(data.totals.requests)} />
              <Tile icon="CircleDollarSign" accent="purple" label="Revenue" value={formatCurrency(data.totals.revenue)} />
            </div>

            <div className="grid gap-6 lg:grid-cols-2">
              <Card className="p-6">
                <h3 className="mb-4 font-bold text-ink">Booking requests (6 months)</h3>
                <LineChart data={data.booking_trends.map((m) => ({ date: m.month, value: m.requests }))} />
              </Card>
              <Card className="p-6">
                <h3 className="mb-4 font-bold text-ink">Revenue by month</h3>
                <LineChart data={data.revenue_by_month.map((m) => ({ date: m.month, value: m.revenue }))} color="#16a34a" />
              </Card>
              <Card className="p-6">
                <h3 className="mb-4 font-bold text-ink">Most popular services</h3>
                {data.popular_services.length ? (
                  <BarChart data={data.popular_services.map((s) => ({ label: s.name, value: s.count }))} />
                ) : (
                  <p className="text-sm text-muted">No quoted services yet.</p>
                )}
              </Card>
              <Card className="p-6">
                <h3 className="mb-4 font-bold text-ink">Review distribution</h3>
                <BarChart data={data.review_distribution.map((r) => ({ label: `${r.stars}★`, value: r.count }))} color="#eab308" />
              </Card>
            </div>
          </div>
        )}
      </LoadState>
    </div>
  )
}

function Tile({ icon, label, value, accent }) {
  const accents = { navy: 'bg-navy-50 text-navy-700', emerald: 'bg-emerald-50 text-emerald-600', purple: 'bg-purple-50 text-purple-600' }
  return (
    <Card className="flex items-center gap-3 p-5">
      <span className={`grid size-11 shrink-0 place-items-center rounded-xl ${accents[accent]}`}><Icon name={icon} className="size-5" /></span>
      <div>
        <p className="text-sm text-muted">{label}</p>
        <p className="text-xl font-extrabold text-ink">{value}</p>
      </div>
    </Card>
  )
}
