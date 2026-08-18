/**
 * Shared config for the AI Workspace (Phase 7 — AI Planning Engine).
 */

export const AI_SUBNAV = [
  { label: 'Dashboard', to: '', icon: 'LayoutDashboard', end: true },
  { label: 'Reminders', to: 'recommendations', icon: 'BellRing' },
  { label: 'Templates', to: 'templates', icon: 'Wand2' },
  { label: 'Documents', to: 'documents', icon: 'FileText' },
  { label: 'Prompts', to: 'prompts', icon: 'Terminal' },
  { label: 'Automation', to: 'automation', icon: 'Zap' },
]

/** Document/template category → icon + accent + label. */
export const DOC_CATEGORY_META = {
  proposal: { icon: 'FileText', accent: 'navy', label: 'Proposal' },
  timeline: { icon: 'CalendarClock', accent: 'navy', label: 'Timeline' },
  checklist: { icon: 'ListChecks', accent: 'emerald', label: 'Checklist' },
  vendor: { icon: 'Store', accent: 'purple', label: 'Vendor' },
  email: { icon: 'Mail', accent: 'navy', label: 'Email' },
  budget: { icon: 'Wallet', accent: 'emerald', label: 'Budget' },
  speech: { icon: 'Sparkles', accent: 'purple', label: 'Speech' },
  social: { icon: 'Sparkle', accent: 'purple', label: 'Social' },
}

export function docCategoryMeta(category) {
  return DOC_CATEGORY_META[category] ?? { icon: 'FileText', accent: 'navy', label: category }
}

/** Parse {{placeholder}} tokens out of a prompt body, in first-seen order. */
export function extractPromptVariables(body) {
  const found = []
  const re = /\{\{\s*([a-zA-Z0-9_]+)\s*\}\}/g
  let m
  while ((m = re.exec(body || '')) !== null) {
    if (!found.includes(m[1])) found.push(m[1])
  }
  return found
}

/** Turn a variable slug into a readable label ("client_name" → "Client name"). */
export function humanizeVar(name) {
  const s = String(name).replace(/_/g, ' ')
  return s.charAt(0).toUpperCase() + s.slice(1)
}

/** Priority → Badge tone + label ordering. */
export const PRIORITY_META = {
  critical: { tone: 'danger', label: 'Critical' },
  high: { tone: 'amber', label: 'High' },
  medium: { tone: 'navy', label: 'Medium' },
  low: { tone: 'muted', label: 'Low' },
}

/** Recommendation/agent category → icon + accent. */
export const CATEGORY_META = {
  budget: { icon: 'Wallet', accent: 'emerald', label: 'Budget' },
  timeline: { icon: 'CalendarClock', accent: 'navy', label: 'Timeline' },
  planning: { icon: 'ListChecks', accent: 'navy', label: 'Planning' },
  vendor: { icon: 'Store', accent: 'purple', label: 'Vendor' },
  guest: { icon: 'Users', accent: 'navy', label: 'Guest' },
  venue: { icon: 'Building', accent: 'navy', label: 'Venue' },
  financial: { icon: 'CircleDollarSign', accent: 'emerald', label: 'Financial' },
}

export function categoryMeta(category) {
  return CATEGORY_META[category] ?? { icon: 'Sparkles', accent: 'navy', label: category }
}

/** Health score → colour band. */
export function healthTone(score) {
  if (score >= 85) return 'emerald'
  if (score >= 70) return 'navy'
  if (score >= 50) return 'amber'
  return 'danger'
}

/** Tailwind text colour for a health/progress figure. */
export function healthTextClass(score) {
  if (score >= 85) return 'text-emerald-600'
  if (score >= 70) return 'text-navy-700'
  if (score >= 50) return 'text-warning'
  return 'text-danger'
}

/** Tailwind bar colour for a health/progress figure. */
export function healthBarClass(score) {
  if (score >= 85) return 'bg-emerald-500'
  if (score >= 70) return 'bg-navy-600'
  if (score >= 50) return 'bg-warning'
  return 'bg-danger'
}

/**
 * Where a recommendation's "Open …" action should navigate. Most tabs live in
 * the event workspace; finance is a top-level planner hub.
 */
export function recommendationHref(rec) {
  const tab = rec.action_payload?.tab
  if (!tab) return null
  if (tab === 'finance') return '/dashboard/planner/finance'
  if (!rec.event_id) return null
  return `/dashboard/planner/events/${rec.event_id}/${tab === 'vendors' ? 'vendors' : tab}`
}
