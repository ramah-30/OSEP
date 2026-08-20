import { useEffect, useState } from 'react'
import { NavLink, Outlet } from 'react-router-dom'
import Icon from '../../../../components/ui/Icon'
import ModeToggle from '../../../../components/ai/ModeToggle'
import { cn } from '../../../../lib/cn'
import { api } from '../../../../lib/api'
import { AI_SUBNAV } from '../../../../lib/ai'

/**
 * The AI Workspace frame: an identity header (assistant name + which engine is
 * answering) over a sticky sub-navigation strip and the routed section. Keeps a
 * single "AI Assistant" entry in the planner sidebar.
 */
export default function AiLayout() {
  const [meta, setMeta] = useState(null)

  useEffect(() => {
    api.get('/ai/meta').then((r) => setMeta(r.data.data)).catch(() => {})
  }, [])

  const name = meta?.assistant_name ?? 'OSEP AI'

  return (
    <div className="space-y-6">
      <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div className="flex items-center gap-3">
          <span className="grid size-11 place-items-center rounded-2xl bg-purple-50 text-purple-600">
            <Icon name="Sparkles" className="size-6" />
          </span>
          <div>
            <h1 className="flex items-center gap-2 text-h3 font-extrabold tracking-tight text-ink">
              {name}
            </h1>
            <p className="mt-0.5 text-sm text-muted">
              Your event-planning copilot — grounded in your real event data.
            </p>
          </div>
        </div>
        <ModeToggle onChange={() => api.get('/ai/meta').then((r) => setMeta(r.data.data)).catch(() => {})} />
      </div>

      <div className="no-scrollbar -mx-1 flex gap-1 overflow-x-auto border-b border-line">
        {AI_SUBNAV.map((item) => (
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
