import { cn } from '../../lib/cn'

/**
 * A lightweight table set that stays on-brand and scrolls horizontally on small
 * screens without ever pushing the page sideways.
 */
export function Table({ className, children }) {
  return (
    <div className="overflow-x-auto rounded-card border border-line/80 bg-surface shadow-card">
      <table className={cn('w-full text-left text-sm', className)}>{children}</table>
    </div>
  )
}

export function THead({ children }) {
  return (
    <thead className="border-b border-line bg-canvas/60 text-xs font-semibold uppercase tracking-wide text-muted">
      {children}
    </thead>
  )
}

export function TH({ className, children }) {
  return <th className={cn('px-4 py-3 font-semibold', className)}>{children}</th>
}

export function TBody({ children }) {
  return <tbody className="divide-y divide-line/70">{children}</tbody>
}

export function TR({ className, children, ...props }) {
  return (
    <tr className={cn('transition-colors hover:bg-canvas/50', className)} {...props}>
      {children}
    </tr>
  )
}

export function TD({ className, children, ...props }) {
  return (
    <td className={cn('px-4 py-3 align-middle text-ink', className)} {...props}>
      {children}
    </td>
  )
}
