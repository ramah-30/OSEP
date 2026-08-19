import { useState } from 'react'
import Card from '../../../components/ui/Card'
import Badge from '../../../components/ui/Badge'
import Icon from '../../../components/ui/Icon'
import Button from '../../../components/ui/Button'
import EmptyState from '../../../components/ui/EmptyState'
import PageHeader from '../../../components/ui/PageHeader'
import LoadState from '../../../components/dashboard/LoadState'
import PaySimulationDrawer from '../../../components/payments/PaySimulationDrawer'
import { api } from '../../../lib/api'
import { useResource } from '../../../lib/useResource'
import { formatCurrency, formatDate } from '../../../lib/format'
import { statusMeta } from '../../../lib/marketplace'

const INVOICE_STATUS = {
  draft: { label: 'Draft', tone: 'muted' },
  sent: { label: 'Awaiting payment', tone: 'navy' },
  partially_paid: { label: 'Partially paid', tone: 'amber' },
  paid: { label: 'Paid', tone: 'emerald' },
  overdue: { label: 'Overdue', tone: 'danger' },
  cancelled: { label: 'Cancelled', tone: 'muted' },
}

export default function Payments() {
  const { data, loading, error, reload } = useResource('/invoices')
  const [paying, setPaying] = useState(null)

  return (
    <div className="space-y-6">
      <PageHeader title="Payments" description="Invoices your planner has sent you." />

      <LoadState loading={loading} error={error} onRetry={reload}>
        {data && (!data.invoices?.length ? (
          <EmptyState icon="CreditCard" title="No invoices yet" description="Invoices your planner sends will show up here." />
        ) : (
          <div className="space-y-4">
            {data.invoices.map((invoice) => {
              const meta = statusMeta(INVOICE_STATUS, invoice.status)
              return (
                <Card key={invoice.id} className="p-5">
                  <div className="flex flex-wrap items-start justify-between gap-3">
                    <div className="flex items-center gap-3">
                      <span className="grid size-10 place-items-center rounded-lg bg-canvas text-navy-700">
                        <Icon name="FileText" className="size-5" />
                      </span>
                      <div>
                        <p className="font-bold text-ink">{invoice.title ?? invoice.invoice_number}</p>
                        <p className="text-xs text-muted">{invoice.invoice_number} · {invoice.event?.title}</p>
                      </div>
                    </div>
                    <Badge tone={meta.tone}>{meta.label}</Badge>
                  </div>

                  <div className="mt-4 flex flex-wrap gap-x-6 gap-y-2 text-sm text-muted">
                    <span className="flex items-center gap-1.5">
                      <Icon name="CircleDollarSign" className="size-4" />
                      {formatCurrency(invoice.total, invoice.currency)}
                      {invoice.balance > 0 && <span className="text-xs">({formatCurrency(invoice.balance, invoice.currency)} due)</span>}
                    </span>
                    {invoice.due_date && (
                      <span className="flex items-center gap-1.5"><Icon name="Calendar" className="size-4" />Due {formatDate(invoice.due_date)}</span>
                    )}
                  </div>

                  {invoice.is_collectable && invoice.balance > 0 && (
                    <div className="mt-4">
                      <Button size="sm" onClick={() => setPaying(invoice)}>
                        <Icon name="Smartphone" className="size-4" /> Pay now
                      </Button>
                    </div>
                  )}
                </Card>
              )
            })}
          </div>
        ))}
      </LoadState>

      <PaySimulationDrawer
        open={Boolean(paying)}
        onClose={() => { setPaying(null); reload() }}
        payee={paying ? { name: paying.planner?.name, phone: paying.planner?.phone } : null}
        balance={paying?.balance ?? 0}
        currency={paying?.currency}
        onSubmit={async ({ amount, payer_phone, network }) => {
          const { data } = await api.post(`/invoices/${paying.id}/pay`, { amount, payer_phone, network })
          return {
            status: data.data.payment.status,
            receiptNumber: data.data.payment.receipt?.receipt_number,
            message: data.message,
          }
        }}
      />
    </div>
  )
}
