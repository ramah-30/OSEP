import { useState } from 'react'
import { useNavigate } from 'react-router-dom'
import PageHeader from '../../../components/ui/PageHeader'
import Card from '../../../components/ui/Card'
import Badge from '../../../components/ui/Badge'
import Button from '../../../components/ui/Button'
import Icon from '../../../components/ui/Icon'
import EmptyState from '../../../components/ui/EmptyState'
import LoadState from '../../../components/dashboard/LoadState'
import ConfirmDialog from '../../../components/ui/ConfirmDialog'
import { useResource } from '../../../lib/useResource'
import { api } from '../../../lib/api'
import { formatDate } from '../../../lib/format'

const STATUS_TONE = {
  pending: 'warning',
  accepted: 'success',
  declined: 'danger',
  withdrawn: 'muted',
}

export default function MyRequests() {
  const navigate = useNavigate()
  const { data, loading, error, reload } = useResource('/booking-requests')
  const [withdrawingId, setWithdrawingId] = useState(null)
  const [withdrawing, setWithdrawing] = useState(false)

  async function handleWithdraw() {
    setWithdrawing(true)
    try {
      await api.post(`/booking-requests/${withdrawingId}/withdraw`)
      setWithdrawingId(null)
      reload()
    } finally {
      setWithdrawing(false)
    }
  }

  return (
    <div className="space-y-6">
      <PageHeader
        title="Booking Requests"
        description="Track the status of your planner booking requests."
      />

      <LoadState loading={loading} error={error} onRetry={reload}>
        {data && (
          data.requests.length ? (
            <div className="space-y-4">
              {data.requests.map((r) => (
                <Card key={r.id} className="p-5">
                  <div className="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div className="min-w-0">
                      <div className="flex flex-wrap items-center gap-2">
                        <p className="font-bold text-ink">
                          {r.planner?.company_name || r.planner?.full_name || 'Planner'}
                        </p>
                        <Badge tone={STATUS_TONE[r.status] ?? 'muted'}>{r.status_label}</Badge>
                      </div>
                      <p className="mt-0.5 text-xs font-mono text-muted">{r.reference}</p>

                      <div className="mt-2 flex flex-wrap gap-x-4 gap-y-1 text-sm text-muted">
                        {r.event_type && (
                          <span className="flex items-center gap-1.5">
                            <Icon name="Tag" className="size-3.5" />{r.event_type}
                          </span>
                        )}
                        {r.event_date && (
                          <span className="flex items-center gap-1.5">
                            <Icon name="Calendar" className="size-3.5" />{formatDate(r.event_date)}
                          </span>
                        )}
                        {r.expected_guests && (
                          <span className="flex items-center gap-1.5">
                            <Icon name="Users" className="size-3.5" />{r.expected_guests} guests
                          </span>
                        )}
                        <span className="flex items-center gap-1.5">
                          <Icon name="Clock" className="size-3.5" />Sent {formatDate(r.created_at)}
                        </span>
                      </div>

                      {r.message && (
                        <p className="mt-2 max-w-lg text-sm italic text-muted line-clamp-2">{r.message}</p>
                      )}

                      {r.planner_note && (
                        <div className="mt-2 rounded border border-line bg-surface p-3 text-sm">
                          <p className="font-semibold text-ink">Planner&apos;s response</p>
                          <p className="mt-0.5 text-muted">{r.planner_note}</p>
                        </div>
                      )}

                      {r.status === 'accepted' && r.event_id && (
                        <p className="mt-2 flex items-center gap-1.5 text-sm font-semibold text-green-700 dark:text-green-400">
                          <Icon name="CalendarCheck2" className="size-4" />
                          Your event workspace is ready
                        </p>
                      )}
                    </div>

                    {r.status === 'pending' && (
                      <Button
                        size="sm"
                        variant="ghost"
                        className="shrink-0 text-danger hover:bg-red-50 dark:hover:bg-red-950"
                        onClick={() => setWithdrawingId(r.id)}
                      >
                        Withdraw
                      </Button>
                    )}
                  </div>
                </Card>
              ))}
            </div>
          ) : (
            <EmptyState
              icon="Send"
              title="No booking requests yet"
              description="Find a planner and send them a booking request to get started."
              action={
                <Button size="sm" onClick={() => navigate('/dashboard/client/find-planner')}>
                  <Icon name="Search" className="size-4" /> Find a planner
                </Button>
              }
            />
          )
        )}
      </LoadState>

      <ConfirmDialog
        open={!!withdrawingId}
        onClose={() => setWithdrawingId(null)}
        onConfirm={handleWithdraw}
        title="Withdraw request?"
        description="The planner will no longer see this request. You can send a new one any time."
        confirmLabel="Withdraw"
        loading={withdrawing}
      />
    </div>
  )
}
