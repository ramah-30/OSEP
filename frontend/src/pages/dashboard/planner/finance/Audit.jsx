import Icon from '../../../../components/ui/Icon'
import Card from '../../../../components/ui/Card'
import EmptyState from '../../../../components/ui/EmptyState'
import LoadState from '../../../../components/dashboard/LoadState'
import { useResource } from '../../../../lib/useResource'
import { formatRelative, formatDate } from '../../../../lib/format'

/** Icon per finance action family, keyed by the action prefix. */
const ICONS = {
  budget: 'Wallet',
  expense: 'ReceiptText',
  quotation: 'FileText',
  invoice: 'ClipboardList',
  payment: 'CreditCard',
  refund: 'CircleDollarSign',
}

function iconFor(action) {
  const prefix = String(action).split('_')[0]
  return ICONS[prefix] ?? 'ListChecks'
}

export default function Audit() {
  const { data, loading, error, reload } = useResource('/finance/audit')
  const entries = data?.entries ?? []

  return (
    <div className="space-y-5">
      <div>
        <h2 className="text-lg font-extrabold text-ink">Audit trail</h2>
        <p className="text-sm text-muted">Every financial action across your events, newest first.</p>
      </div>

      <LoadState loading={loading} error={error} onRetry={reload}>
        {entries.length ? (
          <Card className="divide-y divide-line/70">
            {entries.map((e) => (
              <div key={e.id} className="flex items-start gap-3 p-4">
                <span className="grid size-9 shrink-0 place-items-center rounded-full bg-navy-50 text-navy-700">
                  <Icon name={iconFor(e.action)} className="size-4" />
                </span>
                <div className="min-w-0 flex-1">
                  <p className="text-sm text-ink">
                    <span className="font-semibold">{e.user?.full_name ?? 'Someone'}</span>{' '}
                    <span className="text-muted">{e.description}</span>
                  </p>
                  <p className="mt-0.5 text-xs text-muted">
                    {e.event?.title ? `${e.event.title} · ` : ''}{formatDate(e.created_at)} · {formatRelative(e.created_at)}
                  </p>
                </div>
              </div>
            ))}
          </Card>
        ) : (
          <EmptyState icon="ListChecks" title="No financial activity yet" description="Budget, expense, invoice and payment actions will appear here." />
        )}
      </LoadState>
    </div>
  )
}
