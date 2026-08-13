import { cn } from '../../lib/cn'

const TONES = {
  navy: 'bg-navy-50 text-navy-700 border-navy-100',
  emerald: 'bg-emerald-50 text-emerald-700 border-emerald-100',
  purple: 'bg-purple-50 text-purple-700 border-purple-100',
  amber: 'bg-warning-soft text-warning border-warning/20',
  danger: 'bg-danger-soft text-danger border-danger/20',
  muted: 'bg-canvas text-muted border-line',
}

/** Small status pill. `dot` adds a leading indicator for live/status chips. */
export default function Badge({ tone = 'muted', dot = false, className, children }) {
  return (
    <span
      className={cn(
        'inline-flex items-center gap-1.5 rounded-full border px-2.5 py-1 text-xs font-semibold',
        TONES[tone] ?? TONES.muted,
        className,
      )}
    >
      {dot && <span className="size-1.5 rounded-full bg-current" />}
      {children}
    </span>
  )
}
