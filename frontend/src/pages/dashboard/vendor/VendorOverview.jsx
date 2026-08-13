import Card from '../../../components/ui/Card'
import Badge from '../../../components/ui/Badge'
import Icon from '../../../components/ui/Icon'
import Avatar from '../../../components/ui/Avatar'
import StatGrid from '../../../components/dashboard/StatGrid'
import QuickActions from '../../../components/dashboard/QuickActions'
import LoadState from '../../../components/dashboard/LoadState'
import { useResource } from '../../../lib/useResource'
import { useAuth } from '../../../context/AuthContext'

const VERIFY_TONE = { verified: 'emerald', pending: 'amber', rejected: 'danger' }
const AVAIL_TONE = { available: 'emerald', busy: 'amber', unavailable: 'muted' }

export default function VendorOverview() {
  const { user } = useAuth()
  const { data, loading, error, reload } = useResource('/dashboard/stats')
  const business = data?.business
  const base = user.dashboard_path

  return (
    <div className="space-y-8">
      <div>
        <h1 className="text-h3 font-extrabold tracking-tight text-ink">
          Hi, {user.first_name} <span className="inline-block">👋</span>
        </h1>
        <p className="mt-1.5 text-muted">Here's how your business is performing on OSEP.</p>
      </div>

      <LoadState loading={loading} error={error} onRetry={reload}>
        {data && (
          <div className="space-y-8">
            {/* Business header */}
            <Card className="flex flex-col gap-4 p-6 sm:flex-row sm:items-center sm:justify-between">
              <div className="flex items-center gap-4">
                <Avatar src={business?.logo_url} name={business?.business_name ?? user.full_name} size="lg" />
                <div>
                  <p className="text-lg font-extrabold text-ink">
                    {business?.business_name ?? 'Your business'}
                  </p>
                  <p className="text-sm text-muted">{business?.category ?? 'Add your service category'}</p>
                </div>
              </div>
              <div className="flex flex-wrap gap-2">
                <Badge tone={VERIFY_TONE[business?.verification_status] ?? 'muted'} dot>
                  {business?.verification_status_label ?? 'Unverified'}
                </Badge>
                <Badge tone={AVAIL_TONE[business?.availability_status] ?? 'muted'} dot>
                  {business?.availability_status_label ?? 'Availability'}
                </Badge>
              </div>
            </Card>

            <StatGrid stats={data.stats} />

            <div>
              <h2 className="mb-3 text-sm font-bold uppercase tracking-wide text-muted">
                Quick actions
              </h2>
              <QuickActions
                actions={[
                  { label: 'Edit Business Profile', icon: 'Building2', to: `${base}/business-profile`, accent: 'navy' },
                  { label: 'Manage Services', icon: 'Package', to: `${base}/services`, accent: 'emerald' },
                  { label: 'Update Portfolio', icon: 'Image', to: `${base}/portfolio`, accent: 'purple' },
                  { label: 'View Requests', icon: 'ClipboardList', to: `${base}/requests`, accent: 'navy' },
                ]}
              />
            </div>

            <Card className="flex items-start gap-3 p-6">
              <span className="grid size-10 shrink-0 place-items-center rounded-xl bg-purple-50 text-purple-600">
                <Icon name="Sparkles" className="size-5" />
              </span>
              <div>
                <p className="font-bold text-ink">Grow your bookings</p>
                <p className="mt-0.5 text-sm text-muted">
                  Vendors with a complete profile, portfolio and clear availability get more booking
                  requests. Services, portfolio and booking tools arrive in Phase 3.
                </p>
              </div>
            </Card>
          </div>
        )}
      </LoadState>
    </div>
  )
}
