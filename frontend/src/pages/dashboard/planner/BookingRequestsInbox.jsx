import { useState } from 'react'
import PageHeader from '../../../components/ui/PageHeader'
import Card from '../../../components/ui/Card'
import Badge from '../../../components/ui/Badge'
import Button from '../../../components/ui/Button'
import Icon from '../../../components/ui/Icon'
import EmptyState from '../../../components/ui/EmptyState'
import LoadState from '../../../components/dashboard/LoadState'
import Drawer from '../../../components/ui/Drawer'
import Textarea from '../../../components/ui/Textarea'
import { Field } from '../../../components/ui/Field'
import { useResource } from '../../../lib/useResource'
import { api, parseApiError } from '../../../lib/api'
import { formatDate, formatCurrency } from '../../../lib/format'

const STATUS_TONE = {
  pending: 'warning',
  accepted: 'success',
  declined: 'danger',
  withdrawn: 'muted',
}

export default function BookingRequestsInbox() {
  const { data, loading, error, reload } = useResource('/planner-booking-requests')
  const [responding, setResponding] = useState(null) // { request, decision }
  const [note, setNote] = useState('')
  const [quotedBudget, setQuotedBudget] = useState('')
  const [submitting, setSubmitting] = useState(false)
  const [responseError, setResponseError] = useState(null)

  const pending = data?.requests?.filter((r) => r.status === 'pending') ?? []
  const history = data?.requests?.filter((r) => r.status !== 'pending') ?? []

  function openRespond(request, decision) {
    setResponding({ request, decision })
    setNote('')
    // Pre-fill with what the client asked for — the planner adjusts from there.
    setQuotedBudget(request.proposed_budget != null ? String(request.proposed_budget) : '')
    setResponseError(null)
  }

  async function submitResponse() {
    if (!responding) return
    setSubmitting(true)
    setResponseError(null)
    try {
      await api.post(`/planner-booking-requests/${responding.request.id}/respond`, {
        decision: responding.decision,
        planner_note: note || undefined,
        quoted_budget: responding.decision === 'accepted' && quotedBudget ? Number(quotedBudget) : undefined,
      })
      setResponding(null)
      reload()
    } catch (err) {
      setResponseError(parseApiError(err).message)
    } finally {
      setSubmitting(false)
    }
  }

  return (
    <div className="space-y-6">
      <PageHeader
        title="Booking Requests"
        description="Clients who want to book your planning services."
      />

      <LoadState loading={loading} error={error} onRetry={reload}>
        {data && (
          <div className="space-y-8">
            {/* Pending */}
            <section>
              <h2 className="mb-3 text-sm font-semibold uppercase tracking-wide text-muted">
                Pending — {pending.length}
              </h2>
              {pending.length ? (
                <div className="space-y-3">
                  {pending.map((r) => (
                    <RequestCard key={r.id} request={r} onAccept={() => openRespond(r, 'accepted')} onDecline={() => openRespond(r, 'declined')} />
                  ))}
                </div>
              ) : (
                <EmptyState icon="Inbox" title="No pending requests" description="New requests from clients will appear here." />
              )}
            </section>

            {/* History */}
            {history.length > 0 && (
              <section>
                <h2 className="mb-3 text-sm font-semibold uppercase tracking-wide text-muted">History</h2>
                <div className="space-y-3">
                  {history.map((r) => <RequestCard key={r.id} request={r} />)}
                </div>
              </section>
            )}
          </div>
        )}
      </LoadState>

      {/* Respond drawer */}
      <Drawer
        open={!!responding}
        onClose={() => setResponding(null)}
        title={responding?.decision === 'accepted' ? 'Accept request' : 'Decline request'}
        description={
          responding?.decision === 'accepted'
            ? "Accepting will create an event workspace for this client."
            : "Let the client know you're unable to take their booking."
        }
      >
        <div className="space-y-4">
          {responding?.request && (
            <div className="rounded-card border border-line bg-surface p-4 text-sm text-muted">
              <p className="font-semibold text-ink">{responding.request.client?.full_name}</p>
              {responding.request.event_type && <p>Type: {responding.request.event_type}</p>}
              {responding.request.event_date && <p>Date: {formatDate(responding.request.event_date)}</p>}
              {responding.request.proposed_budget != null && (
                <p>Proposed budget: {formatCurrency(responding.request.proposed_budget)}</p>
              )}
              {responding.request.message && (
                <p className="mt-2 border-t border-line pt-2 italic">{responding.request.message}</p>
              )}
            </div>
          )}

          {responseError && (
            <p className="text-sm text-danger">{responseError}</p>
          )}

          {responding?.decision === 'accepted' && (
            <Field
              label="Quoted budget (optional)"
              type="number"
              min="0"
              step="1000"
              placeholder="Leave blank to set the budget later"
              value={quotedBudget}
              onChange={(e) => setQuotedBudget(e.target.value)}
            />
          )}

          <Textarea
            label="Note to client (optional)"
            rows={3}
            placeholder="Any message you want to include…"
            value={note}
            onChange={(e) => setNote(e.target.value)}
          />

          <div className="flex justify-end gap-3">
            <Button variant="ghost" onClick={() => setResponding(null)} disabled={submitting}>
              Cancel
            </Button>
            <Button
              tone={responding?.decision === 'accepted' ? 'primary' : 'danger'}
              loading={submitting}
              onClick={submitResponse}
            >
              {responding?.decision === 'accepted' ? (
                <><Icon name="Check" className="size-4" /> Accept &amp; create event</>
              ) : (
                <><Icon name="X" className="size-4" /> Decline</>
              )}
            </Button>
          </div>
        </div>
      </Drawer>
    </div>
  )
}

function RequestCard({ request, onAccept, onDecline }) {
  return (
    <Card className="p-5">
      <div className="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
        <div className="min-w-0">
          <div className="flex items-center gap-2">
            <p className="font-bold text-ink">{request.client?.full_name ?? 'Unknown client'}</p>
            <Badge tone={STATUS_TONE[request.status] ?? 'muted'}>{request.status_label}</Badge>
          </div>
          <p className="mt-0.5 text-xs font-mono text-muted">{request.reference}</p>
          <div className="mt-2 flex flex-wrap gap-x-4 gap-y-1 text-sm text-muted">
            {request.event_type && (
              <span className="flex items-center gap-1.5">
                <Icon name="Tag" className="size-3.5" />{request.event_type}
              </span>
            )}
            {request.event_date && (
              <span className="flex items-center gap-1.5">
                <Icon name="Calendar" className="size-3.5" />{formatDate(request.event_date)}
              </span>
            )}
            {request.expected_guests && (
              <span className="flex items-center gap-1.5">
                <Icon name="Users" className="size-3.5" />{request.expected_guests} guests
              </span>
            )}
            {request.proposed_budget != null && (
              <span className="flex items-center gap-1.5">
                <Icon name="Wallet" className="size-3.5" />Proposed {formatCurrency(request.proposed_budget)}
              </span>
            )}
            {request.quoted_budget != null && (
              <span className="flex items-center gap-1.5">
                <Icon name="CircleDollarSign" className="size-3.5" />Quoted {formatCurrency(request.quoted_budget)}
              </span>
            )}
          </div>
          {request.message && (
            <p className="mt-2 max-w-lg text-sm text-muted line-clamp-2 italic">{request.message}</p>
          )}
          {request.planner_note && (
            <p className="mt-2 text-sm text-muted">
              <span className="font-semibold">Your note:</span> {request.planner_note}
            </p>
          )}
          {request.event_id && (
            <p className="mt-2 flex items-center gap-1.5 text-xs text-green-700 dark:text-green-400">
              <Icon name="CalendarCheck2" className="size-3.5" /> Event created
            </p>
          )}
        </div>

        {request.status === 'pending' && onAccept && onDecline && (
          <div className="flex shrink-0 gap-2">
            <Button size="sm" variant="outline" onClick={onDecline}>
              Decline
            </Button>
            <Button size="sm" onClick={onAccept}>
              Accept
            </Button>
          </div>
        )}
      </div>
    </Card>
  )
}
