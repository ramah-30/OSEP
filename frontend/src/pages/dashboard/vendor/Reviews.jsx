import { useState } from 'react'
import PageHeader from '../../../components/ui/PageHeader'
import Card from '../../../components/ui/Card'
import Avatar from '../../../components/ui/Avatar'
import Icon from '../../../components/ui/Icon'
import Button from '../../../components/ui/Button'
import EmptyState from '../../../components/ui/EmptyState'
import LoadState from '../../../components/dashboard/LoadState'
import RatingStars from '../../../components/marketplace/RatingStars'
import { api } from '../../../lib/api'
import { useResource } from '../../../lib/useResource'
import { formatRelative } from '../../../lib/format'

export default function Reviews() {
  const { data, loading, error, reload } = useResource('/marketplace/vendor/reviews')
  const [replyFor, setReplyFor] = useState(null)
  const [body, setBody] = useState('')

  const submitReply = async (id) => {
    if (!body.trim()) return
    await api.post(`/marketplace/vendor/reviews/${id}/reply`, { body })
    setReplyFor(null)
    setBody('')
    reload()
  }

  const summary = data?.summary
  const maxCount = summary ? Math.max(1, ...Object.values(summary.distribution ?? {})) : 1

  return (
    <div className="space-y-6">
      <PageHeader title="Reviews" description="What planners say about your business — and your replies." />

      <LoadState loading={loading} error={error} onRetry={reload}>
        {data && (
          <>
            {summary && summary.total > 0 && (
              <Card className="flex flex-col gap-6 p-6 sm:flex-row sm:items-center">
                <div className="text-center">
                  <p className="text-4xl font-extrabold text-ink">{summary.average.toFixed(1)}</p>
                  <RatingStars rating={summary.average} showValue={false} />
                  <p className="mt-1 text-sm text-muted">{summary.total} reviews</p>
                </div>
                <div className="flex-1 space-y-1.5">
                  {[5, 4, 3, 2, 1].map((star) => (
                    <div key={star} className="flex items-center gap-2 text-sm">
                      <span className="w-3 text-muted">{star}</span>
                      <Icon name="Star" className="size-3.5 fill-warning text-warning" />
                      <span className="h-2 flex-1 overflow-hidden rounded-full bg-canvas">
                        <span className="block h-full rounded-full bg-navy-700" style={{ width: `${((summary.distribution?.[star] ?? 0) / maxCount) * 100}%` }} />
                      </span>
                      <span className="w-6 text-right text-muted">{summary.distribution?.[star] ?? 0}</span>
                    </div>
                  ))}
                </div>
              </Card>
            )}

            {data.reviews.length ? (
              <div className="space-y-4">
                {data.reviews.map((r) => (
                  <Card key={r.id} className="p-5">
                    <div className="flex items-start justify-between">
                      <div className="flex items-center gap-3">
                        <Avatar name={r.reviewer_name ?? 'Planner'} />
                        <div>
                          <p className="font-bold text-ink">{r.reviewer_name ?? 'Planner'}</p>
                          <p className="text-xs text-muted">{formatRelative(r.created_at)}</p>
                        </div>
                      </div>
                      <RatingStars rating={r.overall_rating} />
                    </div>
                    {r.title && <p className="mt-3 font-semibold text-ink">{r.title}</p>}
                    {r.comment && <p className="mt-1 text-sm text-muted">{r.comment}</p>}

                    {(r.replies ?? []).map((reply) => (
                      <div key={reply.id} className="mt-3 rounded-btn bg-canvas p-3">
                        <p className="text-xs font-bold text-ink">You replied</p>
                        <p className="mt-1 text-sm text-muted">{reply.body}</p>
                      </div>
                    ))}

                    {!r.replies?.length && (
                      replyFor === r.id ? (
                        <div className="mt-3 space-y-2">
                          <textarea
                            value={body}
                            onChange={(e) => setBody(e.target.value)}
                            rows={2}
                            placeholder="Write a reply…"
                            className="w-full rounded-btn border border-line bg-surface px-3 py-2 text-sm text-ink outline-none focus:border-navy-600"
                          />
                          <div className="flex gap-2">
                            <Button size="sm" onClick={() => submitReply(r.id)}>Post reply</Button>
                            <Button size="sm" variant="ghost" onClick={() => { setReplyFor(null); setBody('') }}>Cancel</Button>
                          </div>
                        </div>
                      ) : (
                        <button onClick={() => setReplyFor(r.id)} className="mt-3 flex items-center gap-1.5 text-sm font-semibold text-navy-700 hover:underline">
                          <Icon name="MessageSquare" className="size-4" /> Reply
                        </button>
                      )
                    )}
                  </Card>
                ))}
              </div>
            ) : (
              <EmptyState icon="Star" title="No reviews yet" description="Reviews from completed bookings will appear here." />
            )}
          </>
        )}
      </LoadState>
    </div>
  )
}
