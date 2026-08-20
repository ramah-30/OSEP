/**
 * Shared vocabulary for the Guest Management, Invitation & RSVP module, mirroring
 * the backend enums. Badge tones map onto the <Badge> palette.
 */

export const RSVP_TONE = {
  invited: 'muted',
  pending: 'muted',
  confirmed: 'emerald',
  declined: 'danger',
  maybe: 'amber',
  attended: 'navy',
}

export const RSVP_STATUS_OPTIONS = [
  { value: 'pending', label: 'Pending' },
  { value: 'confirmed', label: 'Confirmed' },
  { value: 'declined', label: 'Declined' },
  { value: 'maybe', label: 'Maybe' },
  { value: 'attended', label: 'Attended' },
]

export const INVITATION_TONE = {
  draft: 'muted',
  scheduled: 'navy',
  sent: 'navy',
  delivered: 'emerald',
  opened: 'purple',
  failed: 'danger',
}

export const INVITATION_STATUS_OPTIONS = [
  { value: 'draft', label: 'Draft' },
  { value: 'scheduled', label: 'Scheduled' },
  { value: 'sent', label: 'Sent' },
  { value: 'delivered', label: 'Delivered' },
  { value: 'opened', label: 'Opened' },
  { value: 'failed', label: 'Failed' },
]

export const CHECKIN_TONE = {
  pending: 'muted',
  checked_in: 'emerald',
  no_show: 'danger',
}

export const CHANNEL_OPTIONS = [
  { value: 'whatsapp', label: 'WhatsApp' },
  { value: 'sms', label: 'Message' },
]

/** Channels wired to a gateway (WhatsApp/Message use the per-guest deep link). */
export const LIVE_CHANNELS = ['whatsapp', 'sms', 'link', 'qr', 'print']

/** Channels sent through the device gateway (open the guest's app). */
export const GATEWAY_CHANNELS = ['whatsapp', 'sms']

export const TEMPLATE_TYPE_OPTIONS = [
  { value: 'wedding', label: 'Wedding' },
  { value: 'birthday', label: 'Birthday' },
  { value: 'conference', label: 'Conference' },
  { value: 'corporate', label: 'Corporate Event' },
  { value: 'graduation', label: 'Graduation' },
  { value: 'custom', label: 'Custom' },
]

export const CATEGORY_PRIORITY_OPTIONS = [
  { value: 1, label: '1 — Highest' },
  { value: 2, label: '2 — High' },
  { value: 3, label: '3 — Normal' },
  { value: 4, label: '4 — Low' },
  { value: 5, label: '5 — Lowest' },
]

export const COMM_TYPE_META = {
  invitation: { icon: 'Mail', tone: 'navy' },
  reminder: { icon: 'Bell', tone: 'amber' },
  rsvp: { icon: 'MailCheck', tone: 'emerald' },
  note: { icon: 'PenLine', tone: 'muted' },
  checkin: { icon: 'UserCheck', tone: 'purple' },
  system: { icon: 'Info', tone: 'muted' },
}

/** The sub-sections of the Guest workspace hub (deep-linked via ?view=). */
export const GUEST_VIEWS = [
  { value: 'overview', label: 'Overview', icon: 'LayoutDashboard' },
  { value: 'guests', label: 'Guests', icon: 'Users' },
  { value: 'invitations', label: 'Invitations & RSVP', icon: 'Mail' },
  { value: 'setup', label: 'Setup', icon: 'Settings' },
]

/** A qualitative colour ramp for the charts (matches the Tailwind token hues). */
export const CHART_COLORS = ['#2947c8', '#10b981', '#7c3aed', '#f59e0b', '#ef4444', '#0891b2', '#65a30d', '#be123c']
