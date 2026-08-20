import { Link, useNavigate } from 'react-router-dom'
import Icon from '../ui/Icon'
import Avatar from '../ui/Avatar'
import Dropdown, { DropdownItem } from '../ui/Dropdown'
import NotificationsPanel from './NotificationsPanel'
import { baseFor } from '../../lib/dashboardNav'
import { useAuth } from '../../context/AuthContext'
import { useNotifications } from '../../context/NotificationsContext'

/**
 * Sticky header for the workspace: mobile menu button, the current page title,
 * the notifications bell and the user menu.
 */
export default function Topbar({ title, onOpenSidebar }) {
  const { user, logout } = useAuth()
  const { unread } = useNotifications()
  const navigate = useNavigate()
  const base = baseFor(user.account_type)

  const settingsPath = `${base}/settings`
  const profilePath =
    user.account_type === 'vendor' ? `${base}/business-profile` : `${base}/profile`

  return (
    <header className="sticky top-0 z-30 flex h-16 items-center gap-3 border-b border-line bg-surface/90 px-4 backdrop-blur md:px-6">
      <button
        type="button"
        onClick={onOpenSidebar}
        className="grid size-10 place-items-center rounded-btn text-muted transition-colors hover:bg-canvas hover:text-ink lg:hidden"
        aria-label="Open navigation"
      >
        <Icon name="Menu" className="size-5" />
      </button>

      <h1 className="truncate text-lg font-bold text-ink">{title}</h1>

      <div className="flex-1" />

      {/* Notifications */}
      <Dropdown
        align="right"
        panelClassName="p-2"
        trigger={() => (
          <span className="relative grid size-10 place-items-center rounded-btn text-muted transition-colors hover:bg-canvas hover:text-ink">
            <Icon name="Bell" className="size-5" />
            {unread > 0 && (
              <span className="absolute right-1.5 top-1.5 grid min-w-4 place-items-center rounded-full bg-danger px-1 text-[0.6rem] font-bold text-white">
                {unread > 9 ? '9+' : unread}
              </span>
            )}
          </span>
        )}
      >
        <NotificationsPanel />
      </Dropdown>

      {/* User menu */}
      <Dropdown
        align="right"
        trigger={() => (
          <span className="flex items-center gap-2 rounded-full py-1 pl-1 pr-2 transition-colors hover:bg-canvas">
            <Avatar src={user.avatar_url} name={user.full_name} initials={user.initials} size="sm" />
            <span className="hidden text-left sm:block">
              <span className="block max-w-32 truncate text-sm font-semibold leading-tight text-ink">
                {user.full_name}
              </span>
              <span className="block text-xs leading-tight text-muted">
                {user.account_type_label}
              </span>
            </span>
            <Icon name="ChevronDown" className="hidden size-4 text-muted sm:block" />
          </span>
        )}
      >
        {({ close }) => (
          <div>
            <div className="border-b border-line px-3 py-2">
              <p className="truncate text-sm font-semibold text-ink">{user.full_name}</p>
              <p className="truncate text-xs text-muted">{user.email}</p>
            </div>
            <div className="py-1">
              <DropdownItem
                onClick={() => {
                  close()
                  navigate(profilePath)
                }}
              >
                <Icon name="User" className="size-4 text-muted" />
                Profile
              </DropdownItem>
              <DropdownItem
                onClick={() => {
                  close()
                  navigate(settingsPath)
                }}
              >
                <Icon name="Settings" className="size-4 text-muted" />
                Settings
              </DropdownItem>
              <Link
                to="/"
                onClick={close}
                className="flex items-center gap-2.5 rounded-btn px-3 py-2 text-sm font-medium text-ink transition-colors hover:bg-canvas"
              >
                <Icon name="Globe2" className="size-4 text-muted" />
                Back to site
              </Link>
            </div>
            <div className="border-t border-line pt-1">
              <DropdownItem
                onClick={() => {
                  close()
                  logout()
                }}
                className="text-danger hover:bg-danger-soft"
              >
                <Icon name="LogOut" className="size-4" />
                Sign out
              </DropdownItem>
            </div>
          </div>
        )}
      </Dropdown>
    </header>
  )
}
