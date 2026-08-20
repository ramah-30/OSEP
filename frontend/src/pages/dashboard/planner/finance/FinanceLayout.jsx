import { NavLink, Outlet } from 'react-router-dom'
import Icon from '../../../../components/ui/Icon'
import { cn } from '../../../../lib/cn'
import { FINANCE_SUBNAV } from '../../../../lib/finance'

/**
 * The finance hub frame: a sticky sub-navigation strip over the routed section
 * (Dashboard, Budgets, Expenses, …). Mirrors the marketplace hub so the planner
 * sidebar keeps a single "Finance" entry.
 */
export default function FinanceLayout() {
  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-h3 font-extrabold tracking-tight text-ink">Finance</h1>
        <p className="mt-1.5 max-w-2xl text-muted">
          Budgets, expenses, quotations, invoices and payments — the financial health of every event in one place.
        </p>
      </div>

      <div className="no-scrollbar -mx-1 flex gap-1 overflow-x-auto border-b border-line">
        {FINANCE_SUBNAV.map((item) => (
          <NavLink
            key={item.label}
            to={item.to}
            end={item.end}
            className={({ isActive }) =>
              cn(
                'relative flex shrink-0 items-center gap-2 whitespace-nowrap px-3.5 py-3 text-sm font-semibold transition-colors',
                isActive ? 'text-navy-800' : 'text-muted hover:text-ink',
              )
            }
          >
            {({ isActive }) => (
              <>
                <Icon name={item.icon} className="size-4" />
                {item.label}
                {isActive && <span className="absolute inset-x-3 -bottom-px h-0.5 rounded-full bg-navy-800" />}
              </>
            )}
          </NavLink>
        ))}
      </div>

      <Outlet />
    </div>
  )
}
