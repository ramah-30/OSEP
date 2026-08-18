import { Navigate, Outlet, useLocation } from 'react-router-dom'
import { useAuth } from '../context/AuthContext'
import Spinner from '../components/ui/Spinner'

function SessionLoading() {
  return (
    <div className="grid min-h-dvh place-items-center bg-canvas text-navy-800">
      <Spinner className="size-8" />
    </div>
  )
}

/** Signed-in users have no business on /login or /register. */
export function GuestRoute() {
  const { isAuthenticated, user, loading } = useAuth()

  if (loading) return <SessionLoading />

  if (isAuthenticated) {
    return <Navigate to={user.email_verified ? user.dashboard_path : '/verify-email'} replace />
  }

  return <Outlet />
}

/** Requires a session; remembers where the user was headed. */
export function ProtectedRoute() {
  const { isAuthenticated, loading } = useAuth()
  const location = useLocation()

  if (loading) return <SessionLoading />

  if (!isAuthenticated) {
    return <Navigate to="/login" state={{ from: location.pathname }} replace />
  }

  return <Outlet />
}

/** Requires a confirmed email on top of a session. */
export function VerifiedRoute() {
  const { isAuthenticated, user, loading } = useAuth()
  const location = useLocation()

  if (loading) return <SessionLoading />

  if (!isAuthenticated) {
    return <Navigate to="/login" state={{ from: location.pathname }} replace />
  }

  if (!user.email_verified) {
    return <Navigate to="/verify-email" replace />
  }

  return <Outlet />
}

/**
 * Keeps a planner out of the vendor dashboard. The server enforces this too —
 * this only stops the UI from showing a screen the API would refuse.
 */
export function RoleRoute({ accountType }) {
  const { user } = useAuth()

  if (user.account_type !== accountType) {
    return <Navigate to={user.dashboard_path} replace />
  }

  return <Outlet />
}

/**
 * Keeps a non-venue vendor off the My Venues page directly, not just out of
 * the sidebar. The server doesn't enforce this — same caveat as RoleRoute.
 */
export function VendorCategoryRoute({ slug }) {
  const { user } = useAuth()

  if (user.vendor_category_slug !== slug) {
    return <Navigate to={user.dashboard_path} replace />
  }

  return <Outlet />
}
