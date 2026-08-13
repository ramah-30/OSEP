import Card from '../../../../components/ui/Card'
import Icon from '../../../../components/ui/Icon'
import Badge from '../../../../components/ui/Badge'
import Button from '../../../../components/ui/Button'
import EmptyState from '../../../../components/ui/EmptyState'
import LoadState from '../../../../components/dashboard/LoadState'
import QuotationView from '../../../../components/marketplace/QuotationView'
import { api } from '../../../../lib/api'
import { useResource } from '../../../../lib/useResource'
import { formatCurrency, formatDate } from '../../../../lib/format'
import { statusMeta, BOOKING_STATUS } from '../../../../lib/marketplace'

export default function BookingRequests() {
  const requests = useResource('/marketplace/booking-requests')
  const quotes = useResource('/marketplace/quotations')

  const reload = () => {
    requests.reload()
    quotes.reload()
  }

  const quotesByRequest = {}
  ;(quotes.data?.quotations ?? []).forEach((q) => {
    ;(quotesByRequest[q.booking_request_id] ??= []).push(q)
  })

  const respond = async (id, action) => {
    await api.post(`/marketplace/quotations/${id}/respond`, { action })
    reload()
  }

  const withdraw = async (id) => {
    await api.post(`/marketplace/booking-requests/${id}/withdraw`)
    reload()
  }

  return (
    <LoadState loading={requests.loading} error={requests.error} onRetry={reload}>
      {requests.data && (requests.data.booking_requests.length ? (
        <div className="space-y-4">
          {requests.data.booking_requests.map((r) => {
            const meta = statusMeta(BOOKING_STATUS, r.status)
            const reqQuotes = quotesByRequest[r.id] ?? []
            return (
              <Card key={r.id} className="p-6">
                <div className="flex flex-wrap items-start justify-between gap-3">
                  <div>
                    <div className="flex items-center gap-2">
                      <span className="grid size-9 place-items-center rounded-lg bg-canvas text-navy-700">
                        <Icon name={r.provider_type === 'venue' ? 'Building' : 'Store'} className="size-4" />
                      </span>
                      <div>
                        <p className="font-bold text-ink">{r.provider_name ?? 'Provider'}</p>
                        <p className="text-xs text-muted">{r.title ?? 'Booking request'}</p>
                      </div>
                    </div>
                  </div>
                  <Badge tone={meta.tone}>{meta.label}</Badge>
                </div>

                <div className="mt-4 flex flex-wrap gap-x-6 gap-y-2 text-sm text-muted">
                  {r.event_date && <span className="flex items-center gap-1.5"><Icon name="Calendar" className="size-4" />{formatDate(r.event_date)}</span>}
                  {r.guest_count != null && <span className="flex items-center gap-1.5"><Icon name="Users2" className="size-4" />{r.guest_count} guests</span>}
                  {r.budget != null && <span className="flex items-center gap-1.5"><Icon name="Wallet" className="size-4" />{formatCurrency(r.budget)}</span>}
                  {r.event_title && <span className="flex items-center gap-1.5"><Icon name="CalendarClock" className="size-4" />{r.event_title}</span>}
                </div>

                {r.requirements && <p className="mt-3 text-sm text-muted">{r.requirements}</p>}
                {r.response_note && (
                  <p className="mt-3 rounded-btn bg-navy-50 p-3 text-sm text-ink"><span className="font-semibold">Provider:</span> {r.response_note}</p>
                )}

                {reqQuotes.map((q) => (
                  <div key={q.id} className="mt-4">
                    <QuotationView
                      quotation={q}
                      actions={q.is_actionable && [
                        <Button key="a" size="sm" variant="emerald" onClick={() => respond(q.id, 'accept')}>Accept</Button>,
                        <Button key="n" size="sm" variant="secondary" onClick={() => respond(q.id, 'negotiate')}>Negotiate</Button>,
                        <Button key="r" size="sm" variant="ghost" onClick={() => respond(q.id, 'reject')}>Reject</Button>,
                      ]}
                    />
                  </div>
                ))}

                {r.is_open && (
                  <div className="mt-4">
                    <Button size="sm" variant="ghost" onClick={() => withdraw(r.id)}>Withdraw request</Button>
                  </div>
                )}
              </Card>
            )
          })}
        </div>
      ) : (
        <EmptyState icon="ClipboardList" title="No booking requests yet" description="Request a vendor or venue from the marketplace and it will appear here." />
      ))}
    </LoadState>
  )
}
