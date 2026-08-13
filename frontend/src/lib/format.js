/**
 * Presentation helpers shared across the dashboards. Currency defaults to TZS
 * because the platform launches in Tanzania; pass a different code when needed.
 */

export function formatCurrency(value, currency = 'TZS') {
  const amount = Number(value ?? 0)

  try {
    return new Intl.NumberFormat('en-US', {
      style: 'currency',
      currency,
      maximumFractionDigits: 0,
    }).format(amount)
  } catch {
    return `${currency} ${amount.toLocaleString()}`
  }
}

export function formatNumber(value) {
  return Number(value ?? 0).toLocaleString('en-US')
}

/** Compact money for tight spaces, e.g. "TZS 145M". */
export function formatCurrencyCompact(value, currency = 'TZS') {
  const amount = Number(value ?? 0)

  try {
    return new Intl.NumberFormat('en-US', {
      style: 'currency',
      currency,
      notation: 'compact',
      maximumFractionDigits: 1,
    }).format(amount)
  } catch {
    return formatCurrency(amount, currency)
  }
}

export function formatDate(value, options = { day: 'numeric', month: 'long', year: 'numeric' }) {
  if (!value) return '—'

  const date = new Date(value)
  if (Number.isNaN(date.getTime())) return '—'

  return new Intl.DateTimeFormat('en-GB', options).format(date)
}

/** "in 3 weeks", "5 days ago" — a compact relative label for cards and lists. */
export function formatRelative(value) {
  if (!value) return ''

  const date = new Date(value)
  if (Number.isNaN(date.getTime())) return ''

  const diffMs = date.getTime() - Date.now()
  const diffDays = Math.round(diffMs / 86_400_000)
  const rtf = new Intl.RelativeTimeFormat('en', { numeric: 'auto' })

  if (Math.abs(diffDays) >= 30) return rtf.format(Math.round(diffDays / 30), 'month')
  if (Math.abs(diffDays) >= 1) return rtf.format(diffDays, 'day')

  const diffHours = Math.round(diffMs / 3_600_000)
  if (Math.abs(diffHours) >= 1) return rtf.format(diffHours, 'hour')

  return rtf.format(Math.round(diffMs / 60_000), 'minute')
}

/**
 * Format a dashboard stat value according to the `format` hint the API sends.
 */
export function formatStat(value, format) {
  switch (format) {
    case 'currency':
      // Compact so a large amount never overflows a narrow stat tile.
      return formatCurrencyCompact(value)
    case 'percent':
      return `${Number(value ?? 0)}%`
    default:
      return formatNumber(value)
  }
}
