import { useEffect } from 'react'
import { AnimatePresence, motion } from 'framer-motion'
import Icon from './Icon'

/**
 * A right-hand slide-over panel for create/edit forms that are richer than a
 * centred Modal comfortably holds. Closes on Escape or backdrop click and locks
 * body scroll while open.
 */
export default function Drawer({ open, onClose, title, description, children, footer }) {
  useEffect(() => {
    if (!open) return

    const onKey = (event) => event.key === 'Escape' && onClose?.()
    document.addEventListener('keydown', onKey)
    document.body.style.overflow = 'hidden'

    return () => {
      document.removeEventListener('keydown', onKey)
      document.body.style.overflow = ''
    }
  }, [open, onClose])

  return (
    <AnimatePresence>
      {open && (
        <motion.div
          className="fixed inset-0 z-50 flex justify-end"
          initial={{ opacity: 0 }}
          animate={{ opacity: 1 }}
          exit={{ opacity: 0 }}
        >
          <button
            type="button"
            aria-label="Close panel"
            onClick={onClose}
            className="absolute inset-0 bg-navy-950/40 backdrop-blur-sm"
          />

          <motion.aside
            role="dialog"
            aria-modal="true"
            aria-label={title}
            className="relative flex h-full w-full max-w-md flex-col border-l border-line bg-surface shadow-lift"
            initial={{ x: '100%' }}
            animate={{ x: 0 }}
            exit={{ x: '100%' }}
            transition={{ duration: 0.28, ease: [0.16, 1, 0.3, 1] }}
          >
            <div className="flex items-start justify-between gap-4 border-b border-line px-6 py-5">
              <div>
                {title && <h2 className="text-lg font-extrabold text-ink">{title}</h2>}
                {description && <p className="mt-1 text-sm text-muted">{description}</p>}
              </div>
              <button
                type="button"
                onClick={onClose}
                className="grid size-9 shrink-0 place-items-center rounded-btn text-muted transition-colors hover:bg-canvas hover:text-ink"
              >
                <Icon name="X" className="size-5" />
              </button>
            </div>

            <div className="flex-1 overflow-y-auto px-6 py-5">{children}</div>

            {footer && (
              <div className="flex justify-end gap-3 border-t border-line px-6 py-4">{footer}</div>
            )}
          </motion.aside>
        </motion.div>
      )}
    </AnimatePresence>
  )
}
