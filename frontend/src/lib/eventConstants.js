/**
 * Shared status/priority vocabulary for the event engine, mirroring the backend
 * enums. Keeping the labels and Badge tones in one place stops the workspace
 * tabs from drifting apart.
 */

export const EVENT_STATUS_TONE = {
  draft: 'muted',
  planning: 'navy',
  client_approval: 'amber',
  execution: 'emerald',
  completed: 'emerald',
  archived: 'muted',
  cancelled: 'danger',
}

/** The ordered lifecycle a planner steps an event through. */
export const EVENT_PIPELINE = [
  { value: 'draft', label: 'Draft' },
  { value: 'planning', label: 'Planning' },
  { value: 'client_approval', label: 'Client Approval' },
  { value: 'execution', label: 'Execution' },
  { value: 'completed', label: 'Completed' },
  { value: 'archived', label: 'Archived' },
]

export const PRIORITY_TONE = {
  low: 'muted',
  medium: 'navy',
  high: 'amber',
  urgent: 'danger',
}

export const PRIORITY_OPTIONS = [
  { value: 'low', label: 'Low' },
  { value: 'medium', label: 'Medium' },
  { value: 'high', label: 'High' },
  { value: 'urgent', label: 'Urgent' },
]

/** Kanban columns, in board order. */
export const TASK_COLUMNS = [
  { value: 'not_started', label: 'Not Started' },
  { value: 'in_progress', label: 'In Progress' },
  { value: 'waiting_approval', label: 'Waiting Approval' },
  { value: 'completed', label: 'Completed' },
]

export const TASK_STATUS_TONE = {
  not_started: 'muted',
  in_progress: 'navy',
  waiting_approval: 'amber',
  completed: 'emerald',
  cancelled: 'danger',
}

export const MILESTONE_TONE = {
  pending: 'muted',
  in_progress: 'navy',
  waiting_approval: 'amber',
  completed: 'emerald',
}

export const MILESTONE_STATUS_OPTIONS = [
  { value: 'pending', label: 'Pending' },
  { value: 'in_progress', label: 'In progress' },
  { value: 'waiting_approval', label: 'Waiting approval' },
  { value: 'completed', label: 'Completed' },
]

export const RSVP_TONE = {
  invited: 'muted',
  confirmed: 'emerald',
  declined: 'danger',
  attended: 'navy',
}

export const RSVP_OPTIONS = [
  { value: 'invited', label: 'Invited' },
  { value: 'confirmed', label: 'Confirmed' },
  { value: 'declined', label: 'Declined' },
  { value: 'attended', label: 'Attended' },
]

export const VENDOR_STATUS_TONE = {
  requested: 'amber',
  accepted: 'emerald',
  declined: 'danger',
  completed: 'navy',
}

export const VENDOR_STATUS_OPTIONS = [
  { value: 'requested', label: 'Requested' },
  { value: 'accepted', label: 'Accepted' },
  { value: 'declined', label: 'Declined' },
  { value: 'completed', label: 'Completed' },
]

export const BUDGET_STATUS_TONE = {
  planned: 'muted',
  committed: 'amber',
  paid: 'emerald',
}

export const BUDGET_STATUS_OPTIONS = [
  { value: 'planned', label: 'Planned' },
  { value: 'committed', label: 'Committed' },
  { value: 'paid', label: 'Paid' },
]

export const APPROVAL_TONE = {
  pending: 'amber',
  approved: 'emerald',
  rejected: 'danger',
  changes_requested: 'navy',
}

export const APPROVAL_TYPE_OPTIONS = [
  { value: 'budget', label: 'Budget' },
  { value: 'decoration', label: 'Decoration' },
  { value: 'vendor_selection', label: 'Vendor Selection' },
  { value: 'venue', label: 'Venue' },
  { value: 'event_schedule', label: 'Event Schedule' },
  { value: 'proposal', label: 'Proposal' },
  { value: 'quotation', label: 'Quotation' },
]

export const DOCUMENT_CATEGORY_OPTIONS = [
  { value: 'contract', label: 'Contract' },
  { value: 'quotation', label: 'Quotation' },
  { value: 'invoice', label: 'Invoice' },
  { value: 'floor_plan', label: 'Floor Plan' },
  { value: 'checklist', label: 'Checklist' },
  { value: 'image', label: 'Image' },
  { value: 'other', label: 'Other' },
]

export const VENUE_SETTING_OPTIONS = [
  { value: 'indoor', label: 'Indoor' },
  { value: 'outdoor', label: 'Outdoor' },
  { value: 'mixed', label: 'Indoor & Outdoor' },
]

/** Icon + tone for an activity-feed entry, keyed on its action. */
export const ACTIVITY_META = {
  event_created: { icon: 'PartyPopper', tone: 'navy' },
  event_updated: { icon: 'PenLine', tone: 'muted' },
  status_changed: { icon: 'TrendingUp', tone: 'navy' },
  task_created: { icon: 'ListChecks', tone: 'navy' },
  task_completed: { icon: 'CheckCircle2', tone: 'emerald' },
  milestone_created: { icon: 'CalendarClock', tone: 'navy' },
  vendor_assigned: { icon: 'Store', tone: 'purple' },
  venue_added: { icon: 'MapPin', tone: 'navy' },
  venue_updated: { icon: 'MapPin', tone: 'muted' },
  budget_updated: { icon: 'Wallet', tone: 'emerald' },
  approval_submitted: { icon: 'ClipboardCheck', tone: 'amber' },
  approval_decision: { icon: 'ClipboardCheck', tone: 'emerald' },
  document_uploaded: { icon: 'FileText', tone: 'navy' },
}
