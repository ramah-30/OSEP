import { useState } from 'react'
import { AnimatePresence, motion } from 'framer-motion'
import { cn } from '../../lib/cn'
import Icon from './Icon'

/**
 * Single-open accordion. Height animates so the list never jumps, and each
 * header is a real button so keyboard and screen-reader users get the same
 * behaviour as everyone else.
 */
export default function Accordion({ items = [], className }) {
  const [openIndex, setOpenIndex] = useState(0)

  return (
    <div className={cn('divide-y divide-line overflow-hidden rounded-card border border-line bg-surface', className)}>
      {items.map((item, index) => {
        const isOpen = openIndex === index

        return (
          <div key={item.question}>
            <h3>
              <button
                type="button"
                onClick={() => setOpenIndex(isOpen ? -1 : index)}
                aria-expanded={isOpen}
                aria-controls={`faq-panel-${index}`}
                className="flex w-full items-center justify-between gap-6 px-5 py-5 text-left transition-colors duration-200 hover:bg-canvas md:px-7"
              >
                <span className="text-[1.0625rem] font-semibold text-ink">{item.question}</span>
                <span
                  className={cn(
                    'grid size-8 shrink-0 place-items-center rounded-full transition-all duration-300',
                    isOpen ? 'rotate-180 bg-navy-800 text-white' : 'bg-canvas text-navy-800',
                  )}
                >
                  <Icon name="ChevronDown" className="size-4" />
                </span>
              </button>
            </h3>

            <AnimatePresence initial={false}>
              {isOpen && (
                <motion.div
                  id={`faq-panel-${index}`}
                  initial={{ height: 0, opacity: 0 }}
                  animate={{ height: 'auto', opacity: 1 }}
                  exit={{ height: 0, opacity: 0 }}
                  transition={{ duration: 0.26, ease: [0.16, 1, 0.3, 1] }}
                  className="overflow-hidden"
                >
                  <p className="px-5 pb-6 pr-14 leading-relaxed text-muted md:px-7 md:pr-20">
                    {item.answer}
                  </p>
                </motion.div>
              )}
            </AnimatePresence>
          </div>
        )
      })}
    </div>
  )
}
