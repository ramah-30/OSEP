import { cn } from '../../lib/cn'

/**
 * Underlined tab strip. Controlled: pass `tabs` ([{ value, label }]), the active
 * `value`, and an `onChange`.
 */
export default function Tabs({ tabs, value, onChange, className }) {
  return (
    <div className={cn('flex gap-1 overflow-x-auto border-b border-line', className)}>
      {tabs.map((tab) => {
        const active = tab.value === value
        return (
          <button
            key={tab.value}
            type="button"
            onClick={() => onChange(tab.value)}
            className={cn(
              'relative whitespace-nowrap px-4 py-3 text-sm font-semibold transition-colors',
              active ? 'text-navy-800' : 'text-muted hover:text-ink',
            )}
          >
            {tab.label}
            {active && (
              <span className="absolute inset-x-3 -bottom-px h-0.5 rounded-full bg-navy-800" />
            )}
          </button>
        )
      })}
    </div>
  )
}
