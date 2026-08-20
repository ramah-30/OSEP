import Card from '../../../../../components/ui/Card'
import StatCard from '../../../../../components/ui/StatCard'
import LoadState from '../../../../../components/dashboard/LoadState'
import DonutChart from '../../../../../components/charts/DonutChart'
import BarChart from '../../../../../components/charts/BarChart'
import LineChart from '../../../../../components/charts/LineChart'
import { useResource } from '../../../../../lib/useResource'
import { CHART_COLORS } from '../../../../../lib/guestConstants'

const RSVP_COLORS = { confirmed: '#10b981', pending: '#94a3b8', maybe: '#f59e0b', declined: '#ef4444' }

export default function GuestDashboardPanel({ eventId }) {
  const { data, loading, error, reload } = useResource(`/events/${eventId}/guests/dashboard`)

  return (
    <LoadState loading={loading} error={error} onRetry={reload}>
      {data && (
        <div className="space-y-6">
          <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
            <StatCard label="Total Guests" value={data.cards.total} icon="Users" accent="navy" />
            <StatCard label="Invitations Sent" value={data.cards.invitations_sent} icon="Mail" accent="navy" />
            <StatCard label="Confirmed" value={data.cards.confirmed} icon="CheckCircle2" accent="emerald" />
            <StatCard label="Pending" value={data.cards.pending} icon="Clock" accent="navy" />
            <StatCard label="Declined" value={data.cards.declined} icon="X" accent="purple" />
          </div>

          <div className="grid gap-6 lg:grid-cols-2">
            <Card className="p-6">
              <h3 className="mb-4 font-bold text-ink">RSVP Status</h3>
              <DonutChart data={data.rsvp_distribution.map((d) => ({ label: d.label, value: d.value, color: RSVP_COLORS[d.key] }))} />
            </Card>

            <Card className="p-6">
              <div className="mb-4 flex items-center justify-between">
                <h3 className="font-bold text-ink">Response rate</h3>
                <span className="text-2xl font-extrabold text-emerald-600 tabular-nums">{data.response_rate}%</span>
              </div>
              <div className="grid grid-cols-2 gap-3 text-sm">
                <Mini label="Attendance forecast" value={data.attendance_forecast} />
                <Mini label="Avg response time" value={data.average_response_hours != null ? `${data.average_response_hours}h` : '—'} />
              </div>
            </Card>

            <Card className="p-6">
              <h3 className="mb-4 font-bold text-ink">Guests by category</h3>
              <BarChart data={data.categories} />
            </Card>

            <Card className="p-6">
              <h3 className="mb-4 font-bold text-ink">Meal preferences</h3>
              <BarChart data={data.meal_preferences} color={CHART_COLORS[3]} />
            </Card>
          </div>

          <Card className="p-6">
            <h3 className="mb-4 font-bold text-ink">Daily RSVP trend</h3>
            <LineChart data={data.daily_trends} />
          </Card>
        </div>
      )}
    </LoadState>
  )
}

function Mini({ label, value }) {
  return (
    <div className="rounded-btn bg-canvas p-3">
      <p className="text-xs text-muted">{label}</p>
      <p className="mt-0.5 text-lg font-extrabold text-ink tabular-nums">{value}</p>
    </div>
  )
}
