import { useEffect } from 'react'
import { AnimatePresence, motion } from 'framer-motion'
import Icon from './Icon'

/**
 * Accessible centred dialog: closes on Escape or backdrop click, locks body
 * scroll while open, and animates in/out.
 */
export default function Modal({ open, onClose, title, description, children, footer }) {
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
          className="fixed inset-0 z-50 grid place-items-center p-4"
          initial={{ opacity: 0 }}
          animate={{ opacity: 1 }}
          exit={{ opacity: 0 }}
        >
          <button
            type="button"
            aria-label="Close dialog"
            onClick={onClose}
            className="absolute inset-0 bg-navy-950/40 backdrop-blur-sm"
          />

          <motion.div
            role="dialog"
            aria-modal="true"
            aria-label={title}
            className="relative flex max-h-[calc(100dvh-2rem)] w-full max-w-lg flex-col rounded-card border border-line bg-surface shadow-lift"
            initial={{ opacity: 0, y: 20, scale: 0.98 }}
            animate={{ opacity: 1, y: 0, scale: 1 }}
            exit={{ opacity: 0, y: 10, scale: 0.98 }}
            transition={{ duration: 0.2, ease: [0.16, 1, 0.3, 1] }}
          >
            <div className="flex shrink-0 items-start justify-between gap-4 p-6 pb-0">
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

            <div className="min-h-0 flex-1 overflow-y-auto px-6 py-5">{children}</div>

            {footer && (
              <div className="flex shrink-0 justify-end gap-3 border-t border-line p-6 pt-4">{footer}</div>
            )}
          </motion.div>
        </motion.div>
      )}
    </AnimatePresence>
  )
}
