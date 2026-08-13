import { cn } from '../../lib/cn'
import Icon from './Icon'

const TONES = {
  info: { wrap: 'bg-navy-50 text-navy-900 border-navy-100', icon: 'Info', accent: 'text-navy-700' },
  success: {
    wrap: 'bg-emerald-50 text-emerald-900 border-emerald-100',
    icon: 'CheckCircle2',
    accent: 'text-emerald-600',
  },
  warning: {
    wrap: 'bg-warning-soft text-ink border-warning/20',
    icon: 'TriangleAlert',
    accent: 'text-warning',
  },
  error: {
    wrap: 'bg-danger-soft text-ink border-danger/20',
    icon: 'TriangleAlert',
    accent: 'text-danger',
  },
}

export default function Alert({ tone = 'info', title, children, className }) {
  const config = TONES[tone] ?? TONES.info

  return (
    <div
      role={tone === 'error' ? 'alert' : 'status'}
      className={cn('flex gap-3 rounded-btn border p-4 text-sm', config.wrap, className)}
    >
      <Icon name={config.icon} className={cn('mt-0.5 size-[18px] shrink-0', config.accent)} />
      <div className="space-y-1">
        {title && <p className="font-semibold">{title}</p>}
        {children && <div className="leading-relaxed opacity-90">{children}</div>}
      </div>
    </div>
  )
}
