import { useLocation } from 'react-router-dom'
import Card from '../../components/ui/Card'
import Icon from '../../components/ui/Icon'
import Button from '../../components/ui/Button'
import PageHeader from '../../components/ui/PageHeader'
import { navFor } from '../../lib/dashboardNav'
import { useAuth } from '../../context/AuthContext'

/**
 * The branded placeholder every not-yet-built sidebar section routes to, so the
 * navigation is complete and the shell feels whole while the deep feature
 * modules land in Phase 3.
 */
export default function ComingSoon() {
  const { user } = useAuth()
  const { pathname } = useLocation()

  const section = pathname.split('/').pop()
  const item = navFor(user.account_type).find((nav) => nav.path === section)
  const label = item?.label ?? 'This module'

  return (
    <div className="space-y-8">
      <PageHeader title={label} description={`${label} is part of the OSEP roadmap.`} />

      <Card className="mx-auto max-w-2xl p-10 text-center">
        <span className="mx-auto grid size-14 place-items-center rounded-2xl bg-purple-50 text-purple-600">
          <Icon name={item?.icon ?? 'Sparkles'} className="size-7" />
        </span>
        <h2 className="mt-5 text-h3 font-extrabold text-ink">Arriving in Phase 3</h2>
        <p className="mx-auto mt-3 max-w-md text-muted">
          Your workspace is reserved and wired up. {label} will slot in here with the rest of the
          event-management suite — no migration, no re-learning the app.
        </p>
        <div className="mt-7 flex justify-center gap-3">
          <Button to={user.dashboard_path} variant="primary">
            <Icon name="LayoutDashboard" className="size-4" />
            Back to dashboard
          </Button>
        </div>
      </Card>
    </div>
  )
}
