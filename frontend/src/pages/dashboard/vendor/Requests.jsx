import { useState } from 'react'
import PageHeader from '../../../components/ui/PageHeader'
import Card from '../../../components/ui/Card'
import Icon from '../../../components/ui/Icon'
import Badge from '../../../components/ui/Badge'
import Button from '../../../components/ui/Button'
import EmptyState from '../../../components/ui/EmptyState'
import LoadState from '../../../components/dashboard/LoadState'
import QuotationBuilder from '../../../components/marketplace/QuotationBuilder'
import { api } from '../../../lib/api'
import { useResource } from '../../../lib/useResource'
import { formatCurrency, formatDate } from '../../../lib/format'
import { statusMeta, BOOKING_STATUS } from '../../../lib/marketplace'

export default function Requests() {
  const { data, loading, error, reload } = useResource('/marketplace/vendor/requests')
  const [quoting, setQuoting] = useState(null)

  const respond = async (id, action) => {
    await api.post(`/marketplace/vendor/requests/${id}/respond`, { action })
    reload()
  }

  return (
    <div className="space-y-6">
      <PageHeader title="Booking requests" description="Respond to planners and send them a quotation." />

      <LoadState loading={loading} error={error} onRetry={reload}>
        {data && (data.booking_requests.length ? (
          <div className="space-y-4">
            {data.booking_requests.map((r) => {
              const meta = statusMeta(BOOKING_STATUS, r.status)
              return (
                <Card key={r.id} className="p-6">
                  <div className="flex flex-wrap items-start justify-between gap-3">
                    <div>
                      <p className="font-bold text-ink">{r.title ?? 'Booking request'}</p>
                      <p className="text-xs text-muted">from {r.planner_name ?? 'a planner'}</p>
                    </div>
                    <Badge tone={meta.tone}>{meta.label}</Badge>
                  </div>

                  <div className="mt-4 flex flex-wrap gap-x-6 gap-y-2 text-sm text-muted">
                    {r.event_date && <span className="flex items-center gap-1.5"><Icon name="Calendar" className="size-4" />{formatDate(r.event_date)}</span>}
                    {r.guest_count != null && <span className="flex items-center gap-1.5"><Icon name="Users2" className="size-4" />{r.guest_count} guests</span>}
                    {r.budget != null && <span className="flex items-center gap-1.5"><Icon name="Wallet" className="size-4" />{formatCurrency(r.budget)}</span>}
                  </div>

                  {r.requirements && <p className="mt-3 text-sm text-muted">{r.requirements}</p>}

                  <div className="mt-4 flex flex-wrap gap-2">
                    {r.is_open && (
                      <>
                        <Button size="sm" variant="emerald" onClick={() => respond(r.id, 'accept')}>Accept</Button>
                        <Button size="sm" variant="secondary" onClick={() => respond(r.id, 'info')}>Request info</Button>
                        <Button size="sm" variant="ghost" onClick={() => respond(r.id, 'decline')}>Decline</Button>
                      </>
                    )}
                    {(r.status === 'accepted' || r.status === 'info_requested') && (
                      <Button size="sm" onClick={() => setQuoting(r)}><Icon name="ReceiptText" className="size-4" /> Send quotation</Button>
                    )}
                    {r.planner_id && (
                      <Button size="sm" variant="ghost" to={`/dashboard/vendor/messages?to=${r.planner_id}`}>
                        <Icon name="MessageSquare" className="size-4" /> Message planner
                      </Button>
                    )}
                  </div>
                </Card>
              )
            })}
          </div>
        ) : (
          <EmptyState icon="Inbox" title="No booking requests yet" description="When planners request your services, they'll show up here." />
        ))}
      </LoadState>

      <QuotationBuilder open={!!quoting} onClose={() => setQuoting(null)} bookingRequest={quoting} onSent={reload} />
    </div>
  )
}
