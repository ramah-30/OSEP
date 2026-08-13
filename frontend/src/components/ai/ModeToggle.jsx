import { useEffect, useRef, useState } from 'react'
import Icon from '../ui/Icon'
import Spinner from '../ui/Spinner'
import { api, parseApiError } from '../../lib/api'
import { cn } from '../../lib/cn'

/**
 * The OSEP AI "mode" control. Shows which engine is answering and lets the
 * planner switch between the offline engine and a live hosted model. Live models
 * are only selectable once their API key is configured on the server.
 */
export default function ModeToggle({ base = '/ai', onChange }) {
  const [settings, setSettings] = useState(null)
  const [open, setOpen] = useState(false)
  const [busy, setBusy] = useState(null)
  const [error, setError] = useState(null)
  const ref = useRef(null)

  useEffect(() => {
    api.get(`${base}/settings`).then((r) => setSettings(r.data.data)).catch(() => {})
  }, [base])

  useEffect(() => {
    const onDoc = (e) => { if (ref.current && !ref.current.contains(e.target)) setOpen(false) }
    document.addEventListener('mousedown', onDoc)
    return () => document.removeEventListener('mousedown', onDoc)
  }, [])

  if (!settings) return null

  const live = settings.is_live
  const activeLabel = settings.options.find((o) => o.value === settings.driver)?.label ?? 'Offline engine'

  const choose = async (opt) => {
    if (!opt.configured || opt.value === settings.driver) { setOpen(false); return }
    setBusy(opt.value); setError(null)
    try {
      const r = await api.put(`${base}/settings`, { driver: opt.value })
      setSettings(r.data.data)
      setOpen(false)
      onChange?.(r.data.data)
    } catch (err) {
      setError(parseApiError(err).message)
    } finally {
      setBusy(null)
    }
  }

  return (
    <div className="relative" ref={ref}>
      <button
        type="button"
        onClick={() => setOpen((v) => !v)}
        className={cn(
          'inline-flex items-center gap-1.5 rounded-full border px-3 py-1.5 text-xs font-semibold transition-colors',
          live
            ? 'border-emerald-200 bg-emerald-50 text-emerald-700 hover:bg-emerald-100'
            : 'border-purple-200 bg-purple-50 text-purple-700 hover:bg-purple-100',
        )}
      >
        <span className={cn('size-1.5 rounded-full', live ? 'bg-emerald-500' : 'bg-purple-500')} />
        {live ? `Live · ${activeLabel}` : 'Offline engine'}
        <Icon name="ChevronDown" className="size-3.5 opacity-70" />
      </button>

      {open && (
        <div className="absolute right-0 z-30 mt-2 w-64 rounded-xl border border-line bg-surface p-1.5 shadow-lg">
          <p className="px-2.5 py-1.5 text-[11px] font-semibold uppercase tracking-wide text-muted">Answering engine</p>
          {settings.options.map((opt) => {
            const active = opt.value === settings.driver
            return (
              <button
                key={opt.value}
                type="button"
                disabled={!opt.configured || !!busy}
                onClick={() => choose(opt)}
                className={cn(
                  'flex w-full items-center gap-2 rounded-lg px-2.5 py-2 text-left text-sm transition-colors',
                  active ? 'bg-navy-50 text-navy-800' : 'text-ink hover:bg-canvas',
                  !opt.configured && 'cursor-not-allowed opacity-60',
                )}
              >
                <Icon
                  name={opt.value === 'local' ? 'CircleOff' : 'Sparkles'}
                  className={cn('size-4 shrink-0', active ? 'text-navy-700' : 'text-muted')}
                />
                <span className="min-w-0 flex-1">
                  <span className="block font-medium">{opt.label}</span>
                  {!opt.configured && <span className="block text-[11px] text-muted">Add an API key to enable</span>}
                </span>
                {busy === opt.value ? <Spinner className="size-4" />
                  : active ? <Icon name="Check" className="size-4 text-navy-700" />
                  : !opt.configured ? <Icon name="Lock" className="size-3.5 text-muted" /> : null}
              </button>
            )
          })}
          {error && <p className="px-2.5 py-1.5 text-xs text-danger">{error}</p>}
          <p className="px-2.5 py-1.5 text-[11px] text-muted">
            Live mode sends event context to a hosted model. The offline engine keeps everything on your server.
          </p>
        </div>
      )}
    </div>
  )
}
