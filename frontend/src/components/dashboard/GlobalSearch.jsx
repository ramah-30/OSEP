import { useEffect, useRef, useState } from 'react'
import { useNavigate } from 'react-router-dom'
import { AnimatePresence, motion } from 'framer-motion'
import Icon from '../ui/Icon'
import { api } from '../../lib/api'
import { baseFor } from '../../lib/dashboardNav'
import { useAuth } from '../../context/AuthContext'

const GROUPS = [
  { key: 'events', label: 'Events', icon: 'CalendarClock' },
  { key: 'clients', label: 'Clients', icon: 'Users' },
  { key: 'tasks', label: 'Tasks', icon: 'ListChecks' },
  { key: 'vendors', label: 'Vendors', icon: 'Store' },
]

/**
 * Planner command-palette search across events, clients, tasks and vendors.
 * Debounced; results are grouped and each row deep-links into the workspace.
 */
export default function GlobalSearch() {
  const { user } = useAuth()
  const navigate = useNavigate()
  const base = baseFor(user.account_type)
  const [q, setQ] = useState('')
  const [results, setResults] = useState(null)
  const [open, setOpen] = useState(false)
  const ref = useRef(null)

  useEffect(() => {
    if (q.trim().length < 2) { setResults(null); return }
    const id = setTimeout(async () => {
      try {
        const r = await api.get(`/search?q=${encodeURIComponent(q.trim())}`)
        setResults(r.data.data)
        setOpen(true)
      } catch { /* ignore */ }
    }, 300)
    return () => clearTimeout(id)
  }, [q])

  useEffect(() => {
    const onClick = (e) => { if (ref.current && !ref.current.contains(e.target)) setOpen(false) }
    document.addEventListener('mousedown', onClick)
    return () => document.removeEventListener('mousedown', onClick)
  }, [])

  function go(to) {
    setOpen(false)
    setQ('')
    navigate(to)
  }

  function targetFor(group, item) {
    if (group === 'events') return `${base}/events/${item.id}`
    if (group === 'tasks') return `${base}/events/${item.event_id}/tasks`
    if (group === 'clients') return `${base}/clients`
    return `${base}/vendors`
  }

  const hasResults = results && GROUPS.some((g) => results[g.key]?.length)

  return (
    <div ref={ref} className="relative hidden w-full md:block md:max-w-md">
      <Icon name="Search" className="pointer-events-none absolute left-3.5 top-1/2 size-4 -translate-y-1/2 text-muted" />
      <input
        value={q}
        onChange={(e) => setQ(e.target.value)}
        onFocus={() => results && setOpen(true)}
        placeholder="Search events, clients, tasks…"
        className="h-10 w-full rounded-btn border border-line bg-canvas/60 pl-10 pr-4 text-sm text-ink outline-none focus:border-navy-600 focus:bg-surface focus:shadow-[0_0_0_3px_rgba(41,71,200,0.12)]"
      />

      <AnimatePresence>
        {open && q.trim().length >= 2 && (
          <motion.div
            initial={{ opacity: 0, y: -6 }}
            animate={{ opacity: 1, y: 0 }}
            exit={{ opacity: 0, y: -6 }}
            transition={{ duration: 0.15 }}
            className="absolute left-0 right-0 z-40 mt-2 max-h-96 overflow-y-auto rounded-card border border-line bg-surface p-2 shadow-lift"
          >
            {hasResults ? (
              GROUPS.map((group) => {
                const items = results[group.key] ?? []
                if (!items.length) return null
                return (
                  <div key={group.key} className="mb-1 last:mb-0">
                    <p className="px-2 py-1 text-[0.65rem] font-bold uppercase tracking-wide text-muted">{group.label}</p>
                    {items.map((item) => (
                      <button key={`${group.key}-${item.id}`} type="button" onClick={() => go(targetFor(group.key, item))}
                        className="flex w-full items-center gap-2.5 rounded-btn px-2 py-2 text-left text-sm hover:bg-canvas">
                        <Icon name={group.icon} className="size-4 shrink-0 text-muted" />
                        <span className="min-w-0 flex-1">
                          <span className="block truncate font-medium text-ink">{item.title}</span>
                          {item.subtitle && <span className="block truncate text-xs text-muted">{item.subtitle}</span>}
                        </span>
                      </button>
                    ))}
                  </div>
                )
              })
            ) : (
              <p className="px-3 py-6 text-center text-sm text-muted">No matches for “{q}”.</p>
            )}
          </motion.div>
        )}
      </AnimatePresence>
    </div>
  )
}
