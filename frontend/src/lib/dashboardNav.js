/**
 * Sidebar navigation per account type, taken straight from the Phase 2 spec.
 * `path` is relative to the role's dashboard base (empty string = the overview).
 * `ready: false` marks a section that routes to the Phase 3 placeholder.
 */

const PLANNER_NAV = [
  { label: 'Dashboard', path: '', icon: 'LayoutDashboard', ready: true },
  { label: 'Events', path: 'events', icon: 'CalendarClock', ready: true },
  { label: 'Clients', path: 'clients', icon: 'Users', ready: true },
  { label: 'Booking Requests', path: 'booking-requests', icon: 'ClipboardList', ready: true, badge: 'bookings' },
  { label: 'Marketplace', path: 'marketplace', icon: 'Store', ready: true },
  { label: 'Finance', path: 'finance', icon: 'Wallet', ready: true },
  { label: 'Calendar', path: 'calendar', icon: 'Calendar', ready: true },
  { label: 'Reviews', path: 'reviews', icon: 'Star', ready: true },
  { label: 'Messages', path: 'messages', icon: 'MessageSquare', ready: true },
  { label: 'AI Assistant', path: 'ai-assistant', icon: 'Sparkles', accent: 'purple', ready: true },
  { label: 'Profile', path: 'profile', icon: 'User', ready: true },
  { label: 'Settings', path: 'settings', icon: 'Settings', ready: true },
]

const CLIENT_NAV = [
  { label: 'Dashboard', path: '', icon: 'LayoutDashboard', ready: true },
  { label: 'My Events', path: 'my-events', icon: 'CalendarClock', ready: true },
  { label: 'Find a Planner', path: 'find-planner', icon: 'Search', ready: true },
  { label: 'Booking Requests', path: 'booking-requests', icon: 'Send', ready: true },
  { label: 'Progress', path: 'progress', icon: 'TrendingUp', ready: true },
  { label: 'Guest List', path: 'guests', icon: 'Users', ready: true },
  { label: 'Budget Overview', path: 'budget', icon: 'Wallet', ready: true },
  { label: 'Messages', path: 'messages', icon: 'MessageSquare', ready: true },
  { label: 'Payments', path: 'payments', icon: 'CreditCard' },
  { label: 'Profile', path: 'profile', icon: 'User', ready: true },
  { label: 'Settings', path: 'settings', icon: 'Settings', ready: true },
]

const VENDOR_NAV = [
  { label: 'Dashboard', path: '', icon: 'LayoutDashboard', ready: true },
  { label: 'Business Profile', path: 'business-profile', icon: 'Building2', ready: true },
  { label: 'Services', path: 'services', icon: 'Package', ready: true },
  { label: 'Portfolio', path: 'portfolio', icon: 'Image', ready: true },
  { label: 'Availability', path: 'availability', icon: 'CalendarClock', ready: true },
  {
    label: 'My Venues',
    path: 'venues',
    icon: 'Building',
    ready: true,
    visibleFor: (user) => user.vendor_category_slug === 'venues',
  },
  { label: 'Requests', path: 'requests', icon: 'ClipboardList', ready: true },
  { label: 'Quotations', path: 'quotations', icon: 'FileText', ready: true },
  { label: 'Contracts', path: 'contracts', icon: 'Handshake', ready: true },
  { label: 'Reviews', path: 'reviews', icon: 'Star', ready: true },
  { label: 'Messages', path: 'messages', icon: 'MessageSquare', ready: true },
  { label: 'Analytics', path: 'analytics', icon: 'BarChart3', ready: true },
  { label: 'Settings', path: 'settings', icon: 'Settings', ready: true },
]

const ADMIN_NAV = [
  { label: 'Overview', path: '', icon: 'LayoutDashboard', ready: true },
  { label: 'Vendors', path: 'vendors', icon: 'Store', ready: true },
  { label: 'Venues', path: 'venues', icon: 'Building', ready: true },
  { label: 'Categories', path: 'categories', icon: 'Tag', ready: true },
  { label: 'Reviews', path: 'reviews', icon: 'Star', ready: true },
  { label: 'Settings', path: 'settings', icon: 'Settings', ready: true },
]

const NAV_BY_TYPE = {
  event_planner: PLANNER_NAV,
  client: CLIENT_NAV,
  vendor: VENDOR_NAV,
  admin: ADMIN_NAV,
}

const BASE_BY_TYPE = {
  event_planner: '/dashboard/planner',
  client: '/dashboard/client',
  vendor: '/dashboard/vendor',
  admin: '/dashboard/admin',
}

export function navFor(accountType, user) {
  const items = NAV_BY_TYPE[accountType] ?? []
  return items.filter((item) => !item.visibleFor || item.visibleFor(user))
}

export function baseFor(accountType) {
  return BASE_BY_TYPE[accountType] ?? '/'
}

/** Absolute href for a nav item under the given account type's base. */
export function hrefFor(accountType, item) {
  const base = baseFor(accountType)
  return item.path ? `${base}/${item.path}` : base
}
