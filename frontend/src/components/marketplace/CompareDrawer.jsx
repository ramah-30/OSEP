import { motion } from 'framer-motion'
import Icon from '../ui/Icon'
import Avatar from '../ui/Avatar'
import Button from '../ui/Button'
import RatingStars from './RatingStars'
import VerificationBadge from './VerificationBadge'
import Modal from '../ui/Modal'
import { formatCurrency } from '../../lib/format'

/**
 * The compare tray that docks at the bottom once a planner ticks two or more
 * vendors, plus the side-by-side comparison modal it opens.
 */
export default function CompareDrawer({ items, onRemove, onClear, open, onToggle }) {
  if (!items.length) return null

  return (
    <>
      <div className="pointer-events-none fixed inset-x-0 bottom-0 z-40 flex justify-center px-4 pb-4">
        <motion.div
          initial={{ y: 80, opacity: 0 }}
          animate={{ y: 0, opacity: 1 }}
          className="pointer-events-auto flex w-full max-w-3xl items-center gap-3 rounded-card border border-line bg-surface p-3 shadow-lift"
        >
          <span className="flex items-center gap-2 pl-2 text-sm font-bold text-ink">
            <Icon name="Scale" className="size-4" /> Compare
          </span>
          <div className="flex flex-1 items-center gap-2 overflow-x-auto">
            {items.map((v) => (
              <span key={v.id} className="flex shrink-0 items-center gap-1.5 rounded-full bg-canvas py-1 pl-1 pr-2 text-xs font-semibold text-ink">
                <Avatar name={v.business_name} src={v.logo_url} size="sm" />
                <span className="max-w-28 truncate">{v.business_name}</span>
                <button type="button" onClick={() => onRemove(v)} aria-label="Remove"><Icon name="X" className="size-3.5 text-muted" /></button>
              </span>
            ))}
          </div>
          <button type="button" onClick={onClear} className="text-xs font-semibold text-muted hover:text-ink">Clear</button>
          <Button size="sm" disabled={items.length < 2} onClick={onToggle}>Compare {items.length}</Button>
        </motion.div>
      </div>

      <Modal open={open} onClose={onToggle} title="Compare vendors">
        <CompareTable items={items} />
      </Modal>
    </>
  )
}

function CompareTable({ items }) {
  const rows = [
    ['Rating', (v) => <RatingStars rating={v.rating} count={v.reviews_count} />],
    ['From', (v) => (v.price_from != null ? formatCurrency(v.price_from) : '—')],
    ['Verification', (v) => <VerificationBadge level={v.verification_level} always />],
    ['Completed jobs', (v) => v.completed_jobs ?? '—'],
    ['Response time', (v) => (v.response_time_hours != null ? `~${v.response_time_hours}h` : '—')],
    ['Location', (v) => v.location ?? '—'],
  ]

  return (
    <div className="overflow-x-auto">
      <table className="w-full border-collapse text-sm">
        <thead>
          <tr>
            <th className="p-2" />
            {items.map((v) => (
              <th key={v.id} className="min-w-36 p-2 text-left align-bottom">
                <Avatar name={v.business_name} src={v.logo_url} />
                <p className="mt-1 font-bold text-ink">{v.business_name}</p>
                <p className="text-xs font-normal text-muted">{v.category}</p>
              </th>
            ))}
          </tr>
        </thead>
        <tbody>
          {rows.map(([label, render]) => (
            <tr key={label} className="border-t border-line">
              <td className="whitespace-nowrap p-2 text-xs font-bold uppercase tracking-wide text-muted">{label}</td>
              {items.map((v) => (
                <td key={v.id} className="p-2 text-ink">{render(v)}</td>
              ))}
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  )
}
