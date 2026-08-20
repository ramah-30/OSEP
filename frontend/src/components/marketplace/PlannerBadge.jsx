import Icon from '../ui/Icon'
import { cn } from '../../lib/cn'

/**
 * A planner's auto-earned trust badge (verified → established → top-rated).
 * Renders nothing for the unverified tier. Pass the `badge` object the API
 * returns: { key, label, tier, verified }.
 */
const STYLES = {
  verified: { icon: 'ShieldCheck', className: 'bg-navy-50 text-navy-700 dark:bg-navy-900 dark:text-navy-200' },
  established: { icon: 'BadgeCheck', className: 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300' },
  top_rated: { icon: 'Crown', className: 'bg-amber-50 text-amber-700 dark:bg-amber-950 dark:text-amber-300' },
}

export default function PlannerBadge({ badge, size = 'sm', className }) {
  if (!badge || !badge.verified) return null
  const style = STYLES[badge.key] ?? STYLES.verified
  const sm = size === 'sm'

  return (
    <span
      className={cn(
        'inline-flex items-center gap-1 rounded-full font-semibold',
        sm ? 'px-2 py-0.5 text-xs' : 'px-2.5 py-1 text-sm',
        style.className,
        className,
      )}
      title={badge.label}
    >
      <Icon name={style.icon} className={sm ? 'size-3.5' : 'size-4'} />
      {badge.label}
    </span>
  )
}
