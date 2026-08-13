import Card from '../ui/Card'
import Icon from '../ui/Icon'
import Badge from '../ui/Badge'
import EmptyState from '../ui/EmptyState'
import { formatCurrency, formatDate } from '../../lib/format'
import { statusMeta, CONTRACT_STATUS } from '../../lib/marketplace'

/**
 * Shared contract list. `perspective` decides which party's name to emphasise
 * and `renderActions(contract)` supplies the role-specific buttons.
 */
export default function ContractsList({ contracts, perspective = 'planner', renderActions }) {
  if (!contracts?.length) {
    return <EmptyState icon="Handshake" title="No contracts yet" description="Accepted quotations become contracts and show up here." />
  }

  return (
    <div className="space-y-4">
      {contracts.map((c) => {
        const meta = statusMeta(CONTRACT_STATUS, c.status)
        const counterparty = perspective === 'planner' ? c.provider_name : c.planner_name
        return (
          <Card key={c.id} className="p-5">
            <div className="flex flex-wrap items-start justify-between gap-3">
              <div className="flex items-center gap-3">
                <span className="grid size-10 place-items-center rounded-lg bg-canvas text-navy-700"><Icon name="Handshake" className="size-5" /></span>
                <div>
                  <p className="font-bold text-ink">{c.title ?? c.reference}</p>
                  <p className="text-xs text-muted">{c.reference} · {counterparty}</p>
                </div>
              </div>
              <Badge tone={meta.tone}>{meta.label}</Badge>
            </div>

            <div className="mt-4 flex flex-wrap gap-x-6 gap-y-2 text-sm text-muted">
              {c.amount != null && <span className="flex items-center gap-1.5"><Icon name="CircleDollarSign" className="size-4" />{formatCurrency(c.amount)}</span>}
              {c.start_date && <span className="flex items-center gap-1.5"><Icon name="Calendar" className="size-4" />{formatDate(c.start_date)}</span>}
              {c.signed_at && <span className="flex items-center gap-1.5"><Icon name="Check" className="size-4 text-emerald-500" />Signed {formatDate(c.signed_at)}</span>}
              {c.event_title && <span className="flex items-center gap-1.5"><Icon name="CalendarClock" className="size-4" />{c.event_title}</span>}
            </div>

            {c.terms && <p className="mt-3 text-sm text-muted">{c.terms}</p>}

            {renderActions && <div className="mt-4 flex flex-wrap gap-2">{renderActions(c)}</div>}
          </Card>
        )
      })}
    </div>
  )
}
