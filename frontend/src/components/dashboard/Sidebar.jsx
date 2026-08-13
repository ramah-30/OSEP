import { NavLink } from 'react-router-dom'
import Logo from '../ui/Logo'
import Icon from '../ui/Icon'
import { cn } from '../../lib/cn'
import { hrefFor, navFor } from '../../lib/dashboardNav'
import { usePendingBookings } from '../../lib/usePendingBookings'
import { useAuth } from '../../context/AuthContext'

/**
 * Role-aware navigation rail. The item list comes from lib/dashboardNav so the
 * planner, client and vendor each get exactly the sidebar the spec describes.
 */
export default function Sidebar({ onNavigate }) {
  const { user, logout } = useAuth()
  const items = navFor(user.account_type)
  const pendingBookings = usePendingBookings(user.account_type)

  return (
    <div className="flex h-full flex-col bg-navy-900 text-white/80">
      <div className="flex h-20 shrink-0 items-center px-5">
        <Logo to={user.dashboard_path} variant="light" />
      </div>

      <nav className="no-scrollbar flex-1 space-y-0.5 overflow-y-auto px-3 pb-6">
        {items.map((item) => (
          <NavLink
            key={item.label}
            to={hrefFor(user.account_type, item)}
            end={item.path === ''}
            onClick={onNavigate}
            className={({ isActive }) =>
              cn(
                'group flex items-center gap-3 rounded-btn px-3 py-2 text-sm font-medium transition-colors',
                isActive
                  ? 'bg-white/12 text-white'
                  : 'text-white/70 hover:bg-white/8 hover:text-white',
              )
            }
          >
            {({ isActive }) => (
              <>
                <Icon
                  name={item.icon}
                  className={cn(
                    'size-[18px] shrink-0',
                    item.accent === 'purple' && !isActive && 'text-purple-300',
                  )}
                />
                <span className="flex-1">{item.label}</span>
                {item.badge === 'bookings' && pendingBookings > 0 && (
                  <span className="grid min-w-[1.25rem] place-items-center rounded-full bg-warning px-1.5 py-0.5 text-[0.65rem] font-bold text-white">
                    {pendingBookings}
                  </span>
                )}
                {item.ready === false && (
                  <span className="rounded-full bg-white/10 px-1.5 py-0.5 text-[0.6rem] font-semibold uppercase tracking-wide text-white/50">
                    Soon
                  </span>
                )}
              </>
            )}
          </NavLink>
        ))}
      </nav>

      <div className="shrink-0 border-t border-white/10 p-3">
        <button
          type="button"
          onClick={() => { onNavigate?.(); logout() }}
          className="flex w-full items-center gap-3 rounded-btn px-3 py-2 text-sm font-medium text-white/70 transition-colors hover:bg-white/8 hover:text-white"
        >
          <Icon name="LogOut" className="size-[18px] shrink-0" />
          <span className="flex-1 text-left">Sign out</span>
        </button>
        <p className="px-3 pt-3 text-xs text-white/50">OSEP Event planning Platform</p>
      </div>
    </div>
  )
}
