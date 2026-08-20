import { cn } from '../../lib/cn'

const TONES = {
  navy: 'bg-navy-600',
  emerald: 'bg-emerald-500',
  purple: 'bg-purple-600',
}

/** Slim percentage bar used for planning progress and budget usage. */
export default function ProgressBar({ value = 0, tone = 'emerald', className }) {
  const pct = Math.max(0, Math.min(100, Number(value) || 0))

  return (
    <div className={cn('h-2 w-full overflow-hidden rounded-full bg-line', className)}>
      <div
        className={cn('h-full rounded-full transition-[width] duration-500', TONES[tone])}
        style={{ width: `${pct}%` }}
      />
    </div>
  )
}
