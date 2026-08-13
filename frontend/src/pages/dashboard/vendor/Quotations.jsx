import PageHeader from '../../../components/ui/PageHeader'
import Card from '../../../components/ui/Card'
import Button from '../../../components/ui/Button'
import EmptyState from '../../../components/ui/EmptyState'
import LoadState from '../../../components/dashboard/LoadState'
import QuotationView from '../../../components/marketplace/QuotationView'
import { api } from '../../../lib/api'
import { useResource } from '../../../lib/useResource'

export default function Quotations() {
  const { data, loading, error, reload } = useResource('/marketplace/vendor/quotations')

  const send = async (id) => { await api.post(`/marketplace/vendor/quotations/${id}/send`); reload() }
  const remove = async (id) => { await api.delete(`/marketplace/vendor/quotations/${id}`); reload() }

  return (
    <div className="space-y-6">
      <PageHeader title="Quotations" description="Quotations you have sent to planners." />
      <LoadState loading={loading} error={error} onRetry={reload}>
        {data && (data.quotations.length ? (
          <div className="space-y-4">
            {data.quotations.map((q) => (
              <Card key={q.id} className="p-5">
                <p className="mb-3 text-sm text-muted">To <span className="font-semibold text-ink">{q.planner_name ?? 'planner'}</span></p>
                <QuotationView
                  quotation={q}
                  actions={q.status === 'draft' && [
                    <Button key="s" size="sm" onClick={() => send(q.id)}>Send</Button>,
                    <Button key="d" size="sm" variant="ghost" onClick={() => remove(q.id)}>Delete</Button>,
                  ]}
                />
              </Card>
            ))}
          </div>
        ) : (
          <EmptyState icon="ReceiptText" title="No quotations yet" description="Send a quotation from a booking request to get started." />
        ))}
      </LoadState>
    </div>
  )
}
