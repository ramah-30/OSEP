import { useNavigate } from 'react-router-dom'
import Card from '../ui/Card'
import Icon from '../ui/Icon'
import { cn } from '../../lib/cn'

/**
 * A row of primary actions. Each action is { label, icon, to, accent }.
 * Placeholder destinations still navigate — they land on the Phase 3 page.
 */
export default function QuickActions({ actions = [] }) {
  const navigate = useNavigate()

  const accents = {
    navy: 'bg-navy-50 text-navy-700',
    emerald: 'bg-emerald-50 text-emerald-600',
    purple: 'bg-purple-50 text-purple-600',
  }

  return (
    <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
      {actions.map((action) => (
        <Card
          key={action.label}
          as="button"
          hover
          onClick={() => navigate(action.to)}
          className="flex items-center gap-3 p-4 text-left"
        >
          <span
            className={cn(
              'grid size-10 shrink-0 place-items-center rounded-xl',
              accents[action.accent ?? 'navy'],
            )}
          >
            <Icon name={action.icon} className="size-5" />
          </span>
          <span className="font-semibold text-ink">{action.label}</span>
          <Icon name="ChevronRight" className="ml-auto size-4 text-muted" />
        </Card>
      ))}
    </div>
  )
}
