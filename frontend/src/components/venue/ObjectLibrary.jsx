import { useState } from 'react'
import Icon from '../ui/Icon'
import { cn } from '../../lib/cn'
import { OBJECT_CATEGORIES } from '../../lib/venueCatalog'

/**
 * The left-hand palette. Categories collapse; clicking an item drops it onto the
 * canvas via onAdd.
 */
export default function ObjectLibrary({ onAdd }) {
  const [open, setOpen] = useState(() => ({ tables: true, seating: false }))

  return (
    <div className="flex h-full flex-col">
      <p className="mb-2 px-1 text-xs font-bold uppercase tracking-wide text-muted">Object library</p>
      <div className="flex-1 space-y-1 overflow-y-auto pr-1">
        {OBJECT_CATEGORIES.map((cat) => {
          const isOpen = open[cat.key]
          return (
            <div key={cat.key} className="rounded-btn border border-line/70">
              <button
                type="button"
                onClick={() => setOpen((s) => ({ ...s, [cat.key]: !s[cat.key] }))}
                className="flex w-full items-center gap-2 px-2.5 py-2 text-left text-sm font-semibold text-ink"
              >
                <Icon name={cat.icon} className="size-4 text-navy-700" />
                <span className="flex-1">{cat.label}</span>
                <Icon name={isOpen ? 'ChevronDown' : 'ChevronRight'} className="size-4 text-muted" />
              </button>
              {isOpen && (
                <div className="grid grid-cols-2 gap-1 p-1.5 pt-0">
                  {cat.items.map((item) => (
                    <button
                      key={item.type}
                      type="button"
                      onClick={() => onAdd(item)}
                      title={item.label}
                      className={cn(
                        'flex items-center gap-1.5 rounded-btn border border-line bg-surface px-2 py-1.5 text-left text-[0.72rem] font-medium text-ink',
                        'transition-colors hover:border-navy-300 hover:bg-navy-50',
                      )}
                    >
                      <span className={cn('size-3 shrink-0 border border-black/10', item.shape === 'circle' ? 'rounded-full' : 'rounded-[2px]')} style={{ background: item.color }} />
                      <span className="truncate">{item.label}</span>
                    </button>
                  ))}
                </div>
              )}
            </div>
          )
        })}
      </div>
    </div>
  )
}
