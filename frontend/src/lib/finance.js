/**
 * Shared constants and helpers for the Phase 6 Financial Management module:
 * the finance hub sub-navigation, status tone maps that mirror the backend
 * enums, and small formatting helpers used across the finance pages.
 */

export const FINANCE_SUBNAV = [
  { label: 'Dashboard', to: '', icon: 'LayoutDashboard', end: true },
  { label: 'Budgets', to: 'budgets', icon: 'Wallet' },
  { label: 'Expenses', to: 'expenses', icon: 'ReceiptText' },
  { label: 'Quotations', to: 'quotations', icon: 'FileText' },
  { label: 'Invoices', to: 'invoices', icon: 'ClipboardList' },
  { label: 'Payments', to: 'payments', icon: 'CreditCard' },
  { label: 'Reports', to: 'reports', icon: 'BarChart3' },
  { label: 'Audit', to: 'audit', icon: 'ListChecks' },
]

export const BUDGET_STATUS = {
  draft: { label: 'Draft', tone: 'muted' },
  pending_approval: { label: 'Pending approval', tone: 'amber' },
  approved: { label: 'Approved', tone: 'emerald' },
  locked: { label: 'Locked', tone: 'navy' },
  archived: { label: 'Archived', tone: 'muted' },
}

export const EXPENSE_STATUS = {
  draft: { label: 'Draft', tone: 'muted' },
  submitted: { label: 'Submitted', tone: 'amber' },
  approved: { label: 'Approved', tone: 'navy' },
  paid: { label: 'Paid', tone: 'emerald' },
  rejected: { label: 'Rejected', tone: 'danger' },
}

export const QUOTATION_STATUS = {
  draft: { label: 'Draft', tone: 'muted' },
  sent: { label: 'Sent', tone: 'navy' },
  viewed: { label: 'Viewed', tone: 'purple' },
  accepted: { label: 'Accepted', tone: 'emerald' },
  rejected: { label: 'Rejected', tone: 'danger' },
  expired: { label: 'Expired', tone: 'muted' },
}

export const INVOICE_STATUS = {
  draft: { label: 'Draft', tone: 'muted' },
  sent: { label: 'Sent', tone: 'navy' },
  partially_paid: { label: 'Partially paid', tone: 'amber' },
  paid: { label: 'Paid', tone: 'emerald' },
  overdue: { label: 'Overdue', tone: 'danger' },
  cancelled: { label: 'Cancelled', tone: 'muted' },
}

export const PAYMENT_STATUS = {
  pending: { label: 'Pending', tone: 'amber' },
  completed: { label: 'Completed', tone: 'emerald' },
  failed: { label: 'Failed', tone: 'danger' },
  refunded: { label: 'Refunded', tone: 'purple' },
}

export const SCHEDULE_STATUS = {
  pending: { label: 'Pending', tone: 'muted' },
  scheduled: { label: 'Scheduled', tone: 'navy' },
  paid: { label: 'Paid', tone: 'emerald' },
  overdue: { label: 'Overdue', tone: 'danger' },
}

export const REFUND_STATUS = {
  requested: { label: 'Requested', tone: 'amber' },
  approved: { label: 'Approved', tone: 'navy' },
  processed: { label: 'Processed', tone: 'emerald' },
  rejected: { label: 'Rejected', tone: 'danger' },
}

export const FINANCE_CATEGORIES = [
  'Venue', 'Catering', 'Decoration', 'Photography', 'Videography',
  'Entertainment', 'Transportation', 'Accommodation', 'Printing',
  'Marketing', 'Equipment Rental', 'Security', 'Staffing', 'Insurance',
  'Miscellaneous',
]

export const PAYMENT_METHODS = [
  { value: 'mobile_money', label: 'Mobile money' },
  { value: 'bank_transfer', label: 'Bank transfer' },
  { value: 'credit_card', label: 'Credit card' },
  { value: 'cash', label: 'Cash' },
  { value: 'other', label: 'Other' },
]

export function statusMeta(map, value) {
  return map[value] ?? { label: value ?? '—', tone: 'muted' }
}

/** A single line item's net amount before its own tax / discount. */
export function lineAmount(item) {
  return Number(item.quantity || 0) * Number(item.unit_price || 0)
}

/** Roll a set of editable line items up into document totals. */
export function rollUp(items) {
  return items.reduce(
    (acc, it) => {
      const amount = lineAmount(it)
      acc.subtotal += amount
      acc.tax += Number(it.tax || 0)
      acc.discount += Number(it.discount || 0)
      return acc
    },
    { subtotal: 0, tax: 0, discount: 0 },
  )
}
