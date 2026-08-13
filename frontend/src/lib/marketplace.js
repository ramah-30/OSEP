/**
 * Shared marketplace presentation constants: verification tiers, status tones,
 * category icon fallbacks and the planner sub-navigation. Keeping these in one
 * place means the vendor cards, admin tables and vendor storefront all agree.
 */

export const MARKETPLACE_SUBNAV = [
  { label: 'Discover', to: '', icon: 'Sparkles', end: true },
  { label: 'Vendors', to: 'vendors', icon: 'Store' },
  { label: 'Venues', to: 'venues', icon: 'Building' },
  { label: 'Hotels', to: 'hotels', icon: 'BedDouble' },
  { label: 'Saved', to: 'saved', icon: 'Bookmark' },
  { label: 'Booking Requests', to: 'booking-requests', icon: 'ClipboardList' },
  { label: 'Contracts', to: 'contracts', icon: 'Handshake' },
]

export const ACCOMMODATION_BOOKING_STATUS = {
  confirmed: { label: 'Confirmed', tone: 'emerald' },
  cancelled: { label: 'Cancelled', tone: 'danger' },
  completed: { label: 'Completed', tone: 'navy' },
}

export const VERIFICATION = {
  unverified: { label: 'Unverified', tone: 'muted', icon: 'ShieldCheck' },
  email_verified: { label: 'Email verified', tone: 'navy', icon: 'MailCheck' },
  business_verified: { label: 'Business verified', tone: 'emerald', icon: 'BadgeCheck' },
  premium_partner: { label: 'Premium partner', tone: 'purple', icon: 'Crown' },
}

export function verificationMeta(level) {
  return VERIFICATION[level] ?? VERIFICATION.unverified
}

export const BOOKING_STATUS = {
  pending: { label: 'Pending', tone: 'amber' },
  accepted: { label: 'Accepted', tone: 'emerald' },
  declined: { label: 'Declined', tone: 'danger' },
  info_requested: { label: 'More info requested', tone: 'navy' },
  withdrawn: { label: 'Withdrawn', tone: 'muted' },
}

export const QUOTATION_STATUS = {
  draft: { label: 'Draft', tone: 'muted' },
  sent: { label: 'Sent', tone: 'navy' },
  accepted: { label: 'Accepted', tone: 'emerald' },
  rejected: { label: 'Rejected', tone: 'danger' },
  negotiating: { label: 'Negotiating', tone: 'amber' },
  expired: { label: 'Expired', tone: 'muted' },
}

export const CONTRACT_STATUS = {
  draft: { label: 'Draft', tone: 'muted' },
  sent: { label: 'Sent', tone: 'navy' },
  signed: { label: 'Signed', tone: 'purple' },
  active: { label: 'Active', tone: 'emerald' },
  completed: { label: 'Completed', tone: 'navy' },
  cancelled: { label: 'Cancelled', tone: 'danger' },
}

export const SLOT_STATUS = {
  available: { label: 'Available', tone: 'emerald', dot: 'bg-emerald-500' },
  reserved: { label: 'Reserved', tone: 'amber', dot: 'bg-warning' },
  fully_booked: { label: 'Fully booked', tone: 'danger', dot: 'bg-danger' },
  on_leave: { label: 'On leave', tone: 'muted', dot: 'bg-muted' },
}

export const SETTING_LABELS = {
  indoor: 'Indoor',
  outdoor: 'Outdoor',
  both: 'Indoor & Outdoor',
}

/** Fallback lucide icon for a category when the API row carries none. */
export function categoryIcon(category) {
  return category?.icon ?? 'Store'
}

export function statusMeta(map, value) {
  return map[value] ?? { label: value ?? '—', tone: 'muted' }
}
