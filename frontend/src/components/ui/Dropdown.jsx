import { useEffect, useRef, useState } from 'react'
import { AnimatePresence, motion } from 'framer-motion'
import { cn } from '../../lib/cn'

/**
 * Click-to-open popover anchored to a trigger. `trigger` is a render prop that
 * receives { open }; `children` receives { close } so menu items can dismiss it.
 */
export default function Dropdown({ trigger, children, align = 'right', panelClassName }) {
  const [open, setOpen] = useState(false)
  const ref = useRef(null)

  useEffect(() => {
    if (!open) return

    const onClick = (event) => {
      if (ref.current && !ref.current.contains(event.target)) setOpen(false)
    }
    const onKey = (event) => event.key === 'Escape' && setOpen(false)

    document.addEventListener('mousedown', onClick)
    document.addEventListener('keydown', onKey)
    return () => {
      document.removeEventListener('mousedown', onClick)
      document.removeEventListener('keydown', onKey)
    }
  }, [open])

  return (
    <div className="relative" ref={ref}>
      <button type="button" onClick={() => setOpen((v) => !v)} className="block">
        {trigger({ open })}
      </button>

      <AnimatePresence>
        {open && (
          <motion.div
            initial={{ opacity: 0, y: -6, scale: 0.98 }}
            animate={{ opacity: 1, y: 0, scale: 1 }}
            exit={{ opacity: 0, y: -6, scale: 0.98 }}
            transition={{ duration: 0.15, ease: 'easeOut' }}
            className={cn(
              'absolute z-40 mt-2 min-w-56 rounded-card border border-line bg-surface p-1.5 shadow-lift',
              align === 'right' ? 'right-0' : 'left-0',
              panelClassName,
            )}
          >
            {typeof children === 'function' ? children({ close: () => setOpen(false) }) : children}
          </motion.div>
        )}
      </AnimatePresence>
    </div>
  )
}

/** Standard row inside a Dropdown panel. */
export function DropdownItem({ icon: _icon, children, className, ...props }) {
  return (
    <button
      type="button"
      className={cn(
        'flex w-full items-center gap-2.5 rounded-btn px-3 py-2 text-left text-sm font-medium text-ink',
        'transition-colors hover:bg-canvas',
        className,
      )}
      {...props}
    >
      {children}
    </button>
  )
}
